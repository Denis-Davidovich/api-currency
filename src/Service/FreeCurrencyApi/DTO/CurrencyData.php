<?php

declare(strict_types=1);

namespace App\Service\FreeCurrencyApi\DTO;

final readonly class CurrencyData
{
    public function __construct(
        public string $code,
        public string $name,
        public string $symbol,
    ) {}

    /**
     * @param array{symbol: string, name: string, symbol_native?: string, code?: string} $data
     */
    public static function fromArray(string $code, array $data): self
    {
        return new self(
            code: $code,
            name: $data['name'],
            symbol: $data['symbol'],
        );
    }
}
