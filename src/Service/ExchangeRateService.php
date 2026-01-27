<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use App\Repository\CurrencyRepository;
use App\Repository\ExchangeRateRepository;
use App\Service\FreeCurrencyApi\FreeCurrencyApiClient;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ExchangeRateService
{
    private const string BASE_CURRENCY = 'USD';

    public function __construct(
        private readonly FreeCurrencyApiClient $apiClient,
        private readonly CurrencyRepository $currencyRepository,
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function updateRates(): int
    {
        $activeCurrencies = $this->currencyRepository->findActive();

        if (empty($activeCurrencies)) {
            $this->logger?->warning('No active currencies found');
            return 0;
        }

        $currencyCodes = array_map(
            fn (Currency $c) => $c->getCode(),
            $activeCurrencies
        );

        $rates = $this->apiClient->getLatestRates(self::BASE_CURRENCY, $currencyCodes);
        $today = new \DateTimeImmutable('today');
        $savedCount = 0;

        $currencyMap = [];
        foreach ($activeCurrencies as $currency) {
            $currencyMap[$currency->getCode()] = $currency;
        }

        $baseCurrency = $currencyMap[self::BASE_CURRENCY] ?? null;
        if ($baseCurrency === null) {
            $this->logger?->error('Base currency USD not found in active currencies');
            return 0;
        }

        foreach ($rates as $rateData) {
            $targetCurrency = $currencyMap[$rateData->targetCurrency] ?? null;

            if ($targetCurrency === null) {
                continue;
            }

            if ($rateData->targetCurrency === self::BASE_CURRENCY) {
                continue;
            }

            $existingRate = $this->exchangeRateRepository->findExistingRate(
                $baseCurrency,
                $targetCurrency,
                $today
            );

            if ($existingRate !== null) {
                $existingRate->setRate($rateData->rate);
                $this->logger?->debug('Updated rate', [
                    'from' => self::BASE_CURRENCY,
                    'to' => $rateData->targetCurrency,
                    'rate' => $rateData->rate,
                ]);
            } else {
                $rate = new ExchangeRate(
                    $baseCurrency,
                    $targetCurrency,
                    $rateData->rate,
                    $today
                );
                $this->entityManager->persist($rate);
                $this->logger?->debug('Created rate', [
                    'from' => self::BASE_CURRENCY,
                    'to' => $rateData->targetCurrency,
                    'rate' => $rateData->rate,
                ]);
            }

            $savedCount++;
        }

        $this->entityManager->flush();

        $this->logger?->info('Exchange rates updated', ['count' => $savedCount]);

        return $savedCount;
    }

    public function syncCurrencies(): int
    {
        $apiCurrencies = $this->apiClient->getCurrencies();
        $syncedCount = 0;

        foreach ($apiCurrencies as $currencyData) {
            $currency = $this->currencyRepository->findByCode($currencyData->code);

            if ($currency === null) {
                $currency = new Currency(
                    $currencyData->code,
                    $currencyData->name,
                    $currencyData->symbol
                );
                $this->entityManager->persist($currency);
                $this->logger?->debug('Created currency', ['code' => $currencyData->code]);
            } else {
                $currency->setName($currencyData->name);
                $currency->setSymbol($currencyData->symbol);
                $this->logger?->debug('Updated currency', ['code' => $currencyData->code]);
            }

            $syncedCount++;
        }

        $this->entityManager->flush();

        $this->logger?->info('Currencies synced', ['count' => $syncedCount]);

        return $syncedCount;
    }
}
