<?php

declare(strict_types=1);

namespace App\Service\Exception;

class ConversionException extends \RuntimeException
{
    public static function invalidAmount(string $amount): self
    {
        return new self(sprintf('Invalid amount: "%s"', $amount));
    }

    public static function sameCurrency(string $code): self
    {
        return new self(sprintf('Cannot convert currency "%s" to itself', $code));
    }
}
