<?php

declare(strict_types=1);

namespace App\Service\FreeCurrencyApi\DTO;

final readonly class ExchangeRateData
{
    public function __construct(
        public string $baseCurrency,
        public string $targetCurrency,
        public string $rate,
    ) {}
}
