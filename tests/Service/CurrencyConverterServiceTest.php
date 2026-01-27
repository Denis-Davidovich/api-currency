<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Repository\CurrencyRepository;
use App\Repository\ExchangeRateRepository;
use App\Service\CurrencyConverterService;
use App\Service\Exception\ConversionException;
use App\Service\Exception\CurrencyNotFoundException;
use App\Service\Exception\RateNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CurrencyConverterServiceTest extends TestCase
{
    private CurrencyRepository&MockObject $currencyRepository;
    private ExchangeRateRepository&MockObject $exchangeRateRepository;
    private CurrencyConverterService $converter;

    private Currency $usd;
    private Currency $eur;
    private Currency $rub;

    protected function setUp(): void
    {
        $this->currencyRepository = $this->createMock(CurrencyRepository::class);
        $this->exchangeRateRepository = $this->createMock(ExchangeRateRepository::class);

        $this->converter = new CurrencyConverterService(
            $this->currencyRepository,
            $this->exchangeRateRepository
        );

        $this->usd = new Currency('USD', 'US Dollar', '$');
        $this->eur = new Currency('EUR', 'Euro', '€');
        $this->rub = new Currency('RUB', 'Russian Ruble', '₽');
    }

    public function testConvertSameCurrency(): void
    {
        $result = $this->converter->convert(100, 'USD', 'USD');

        $this->assertSame('100.00', $result);
    }

    public function testConvertSameCurrencyDifferentCase(): void
    {
        $result = $this->converter->convert(100, 'usd', 'USD');

        $this->assertSame('100.00', $result);
    }

    public function testConvertDirectRate(): void
    {
        $this->currencyRepository
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $this->usd],
                ['EUR', $this->eur],
            ]);

        $rate = new ExchangeRate($this->usd, $this->eur, '0.92');

        $this->exchangeRateRepository
            ->method('findRateByCode')
            ->with('USD', 'EUR')
            ->willReturn($rate);

        $result = $this->converter->convert(100, 'USD', 'EUR');

        $this->assertSame('92.00', $result);
    }

    public function testConvertInverseRate(): void
    {
        $this->currencyRepository
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $this->usd],
                ['EUR', $this->eur],
            ]);

        $rate = new ExchangeRate($this->eur, $this->usd, '1.087');

        $this->exchangeRateRepository
            ->method('findRateByCode')
            ->willReturnCallback(function ($from, $to) use ($rate) {
                if ($from === 'EUR' && $to === 'USD') {
                    return $rate;
                }
                return null;
            });

        $result = $this->converter->convert(100, 'USD', 'EUR');

        $this->assertSame('91.99', $result);
    }

    public function testConvertCrossRate(): void
    {
        $this->currencyRepository
            ->method('findByCode')
            ->willReturnMap([
                ['EUR', $this->eur],
                ['RUB', $this->rub],
            ]);

        $usdToEur = new ExchangeRate($this->usd, $this->eur, '0.92');
        $usdToRub = new ExchangeRate($this->usd, $this->rub, '91.5');

        $this->exchangeRateRepository
            ->method('findRateByCode')
            ->willReturnCallback(function ($from, $to) use ($usdToEur, $usdToRub) {
                if ($from === 'USD' && $to === 'EUR') {
                    return $usdToEur;
                }
                if ($from === 'USD' && $to === 'RUB') {
                    return $usdToRub;
                }
                return null;
            });

        $result = $this->converter->convert(100, 'EUR', 'RUB');

        $this->assertSame('9945.65', $result);
    }

    public function testConvertWithPrecision(): void
    {
        $this->currencyRepository
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $this->usd],
                ['EUR', $this->eur],
            ]);

        $rate = new ExchangeRate($this->usd, $this->eur, '0.923456');

        $this->exchangeRateRepository
            ->method('findRateByCode')
            ->willReturn($rate);

        $result = $this->converter->convert(100, 'USD', 'EUR', 4);

        $this->assertSame('92.3456', $result);
    }

    public function testConvertWithStringAmount(): void
    {
        $this->currencyRepository
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $this->usd],
                ['EUR', $this->eur],
            ]);

        $rate = new ExchangeRate($this->usd, $this->eur, '0.92');

        $this->exchangeRateRepository
            ->method('findRateByCode')
            ->willReturn($rate);

        $result = $this->converter->convert('100.50', 'USD', 'EUR');

        $this->assertSame('92.46', $result);
    }

    public function testConvertThrowsOnInvalidAmount(): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Invalid amount');

        $this->converter->convert('invalid', 'USD', 'EUR');
    }

    public function testConvertThrowsOnNegativeAmount(): void
    {
        $this->expectException(ConversionException::class);
        $this->expectExceptionMessage('Invalid amount');

        $this->converter->convert(-100, 'USD', 'EUR');
    }

    public function testConvertThrowsOnUnknownFromCurrency(): void
    {
        $this->currencyRepository
            ->method('findByCode')
            ->willReturn(null);

        $this->expectException(CurrencyNotFoundException::class);
        $this->expectExceptionMessage('XYZ');

        $this->converter->convert(100, 'XYZ', 'EUR');
    }

    public function testConvertThrowsOnUnknownToCurrency(): void
    {
        $this->currencyRepository
            ->method('findByCode')
            ->willReturnCallback(function ($code) {
                return $code === 'USD' ? $this->usd : null;
            });

        $this->expectException(CurrencyNotFoundException::class);
        $this->expectExceptionMessage('XYZ');

        $this->converter->convert(100, 'USD', 'XYZ');
    }

    public function testConvertThrowsOnMissingRate(): void
    {
        $this->currencyRepository
            ->method('findByCode')
            ->willReturnMap([
                ['USD', $this->usd],
                ['EUR', $this->eur],
            ]);

        $this->exchangeRateRepository
            ->method('findRateByCode')
            ->willReturn(null);

        $this->expectException(RateNotFoundException::class);

        $this->converter->convert(100, 'USD', 'EUR');
    }
}
