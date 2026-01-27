<?php

declare(strict_types=1);

namespace App\Service\FreeCurrencyApi\Exception;

class ApiException extends \RuntimeException
{
    public static function requestFailed(string $message, ?\Throwable $previous = null): self
    {
        return new self("API request failed: {$message}", 0, $previous);
    }

    public static function invalidResponse(string $reason): self
    {
        return new self("Invalid API response: {$reason}");
    }
}
