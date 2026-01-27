<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\CurrencyRepository;
use App\Repository\ExchangeRateRepository;
use App\Service\Exception\ConversionException;
use App\Service\Exception\CurrencyNotFoundException;
use App\Service\Exception\RateNotFoundException;

class CurrencyConverterService
{
    private const string CROSS_CURRENCY = 'USD';

    public function __construct(
        private readonly CurrencyRepository $currencyRepository,
        private readonly ExchangeRateRepository $exchangeRateRepository,
    ) {}

    public function convert(
        float|int|string $amount,
        string $fromCurrency,
        string $toCurrency,
        int $precision = 2
    ): string {
        $amountStr = (string) $amount;

        if (!is_numeric($amountStr) || bccomp($amountStr, '0', 10) < 0) {
            throw ConversionException::invalidAmount($amountStr);
        }

        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return bcadd($amountStr, '0', $precision);
        }

        $fromEntity = $this->currencyRepository->findByCode($fromCurrency);
        if ($fromEntity === null) {
            throw CurrencyNotFoundException::withCode($fromCurrency);
        }

        $toEntity = $this->currencyRepository->findByCode($toCurrency);
        if ($toEntity === null) {
            throw CurrencyNotFoundException::withCode($toCurrency);
        }

        $rate = $this->getRate($fromCurrency, $toCurrency);

        return bcmul($amountStr, $rate, $precision);
    }

    private function getRate(string $from, string $to): string
    {
        $directRate = $this->exchangeRateRepository->findRateByCode($from, $to);
        if ($directRate !== null) {
            return $directRate->getRate();
        }

        $inverseRate = $this->exchangeRateRepository->findRateByCode($to, $from);
        if ($inverseRate !== null) {
            return bcdiv('1', $inverseRate->getRate(), 10);
        }

        if ($from !== self::CROSS_CURRENCY && $to !== self::CROSS_CURRENCY) {
            $fromToUsd = $this->exchangeRateRepository->findRateByCode(self::CROSS_CURRENCY, $from);
            $usdToTarget = $this->exchangeRateRepository->findRateByCode(self::CROSS_CURRENCY, $to);

            if ($fromToUsd !== null && $usdToTarget !== null) {
                $fromInUsd = bcdiv('1', $fromToUsd->getRate(), 10);
                return bcmul($fromInUsd, $usdToTarget->getRate(), 10);
            }
        }

        throw RateNotFoundException::forPair($from, $to);
    }
}
