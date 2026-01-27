<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Currency;
use App\Repository\CurrencyRepository;
use App\Repository\ExchangeRateRepository;
use App\Service\ExchangeRateService;
use App\Service\FreeCurrencyApi\DTO\ExchangeRateData;
use App\Service\FreeCurrencyApi\FreeCurrencyApiClient;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ExchangeRateServiceTest extends TestCase
{
    private FreeCurrencyApiClient&MockObject $apiClient;
    private CurrencyRepository&MockObject $currencyRepository;
    private ExchangeRateRepository&MockObject $exchangeRateRepository;
    private EntityManagerInterface&MockObject $entityManager;
    private ExchangeRateService $service;

    protected function setUp(): void
    {
        $this->apiClient = $this->createMock(FreeCurrencyApiClient::class);
        $this->currencyRepository = $this->createMock(CurrencyRepository::class);
        $this->exchangeRateRepository = $this->createMock(ExchangeRateRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->service = new ExchangeRateService(
            $this->apiClient,
            $this->currencyRepository,
            $this->exchangeRateRepository,
            $this->entityManager
        );
    }

    public function testUpdateRatesSuccess(): void
    {
        $usd = new Currency('USD', 'US Dollar', '$');
        $eur = new Currency('EUR', 'Euro', '€');
        $rub = new Currency('RUB', 'Russian Ruble', '₽');

        $this->currencyRepository
            ->method('findActive')
            ->willReturn([$usd, $eur, $rub]);

        $apiRates = [
            new ExchangeRateData('USD', 'EUR', '0.92'),
            new ExchangeRateData('USD', 'RUB', '91.5'),
        ];

        $this->apiClient
            ->method('getLatestRates')
            ->with('USD', ['USD', 'EUR', 'RUB'])
            ->willReturn($apiRates);

        $this->exchangeRateRepository
            ->method('findExistingRate')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $count = $this->service->updateRates();

        $this->assertSame(2, $count);
    }

    public function testUpdateRatesWithNoCurrencies(): void
    {
        $this->currencyRepository
            ->method('findActive')
            ->willReturn([]);

        $count = $this->service->updateRates();

        $this->assertSame(0, $count);
    }

    public function testUpdateRatesWithoutBaseCurrency(): void
    {
        $eur = new Currency('EUR', 'Euro', '€');

        $this->currencyRepository
            ->method('findActive')
            ->willReturn([$eur]);

        $this->apiClient
            ->method('getLatestRates')
            ->willReturn([]);

        $count = $this->service->updateRates();

        $this->assertSame(0, $count);
    }

    public function testUpdateRatesUpdatesExisting(): void
    {
        $usd = new Currency('USD', 'US Dollar', '$');
        $eur = new Currency('EUR', 'Euro', '€');

        $this->currencyRepository
            ->method('findActive')
            ->willReturn([$usd, $eur]);

        $apiRates = [
            new ExchangeRateData('USD', 'EUR', '0.93'),
        ];

        $this->apiClient
            ->method('getLatestRates')
            ->willReturn($apiRates);

        $existingRate = $this->createMock(\App\Entity\ExchangeRate::class);
        $existingRate
            ->expects($this->once())
            ->method('setRate')
            ->with('0.93');

        $this->exchangeRateRepository
            ->method('findExistingRate')
            ->willReturn($existingRate);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $count = $this->service->updateRates();

        $this->assertSame(1, $count);
    }

    public function testSyncCurrenciesSuccess(): void
    {
        $apiCurrencies = [
            new \App\Service\FreeCurrencyApi\DTO\CurrencyData('USD', 'US Dollar', '$'),
            new \App\Service\FreeCurrencyApi\DTO\CurrencyData('EUR', 'Euro', '€'),
        ];

        $this->apiClient
            ->method('getCurrencies')
            ->willReturn($apiCurrencies);

        $this->currencyRepository
            ->method('findByCode')
            ->willReturn(null);

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $count = $this->service->syncCurrencies();

        $this->assertSame(2, $count);
    }

    public function testSyncCurrenciesUpdatesExisting(): void
    {
        $apiCurrencies = [
            new \App\Service\FreeCurrencyApi\DTO\CurrencyData('USD', 'US Dollar Updated', '$'),
        ];

        $this->apiClient
            ->method('getCurrencies')
            ->willReturn($apiCurrencies);

        $existingCurrency = new Currency('USD', 'US Dollar', '$');

        $this->currencyRepository
            ->method('findByCode')
            ->with('USD')
            ->willReturn($existingCurrency);

        $this->entityManager
            ->expects($this->never())
            ->method('persist');

        $this->entityManager
            ->expects($this->once())
            ->method('flush');

        $count = $this->service->syncCurrencies();

        $this->assertSame(1, $count);
        $this->assertSame('US Dollar Updated', $existingCurrency->getName());
    }
}
