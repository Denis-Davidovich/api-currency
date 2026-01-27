<?php

declare(strict_types=1);

namespace App\Service\Exception;

class CurrencyNotFoundException extends \RuntimeException
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('Currency with code "%s" not found', $code));
    }
}
