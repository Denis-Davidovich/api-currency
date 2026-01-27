<?php

declare(strict_types=1);

namespace App\Service\Exception;

class RateNotFoundException extends \RuntimeException
{
    public static function forPair(string $from, string $to): self
    {
        return new self(sprintf('Exchange rate from "%s" to "%s" not found', $from, $to));
    }
}
