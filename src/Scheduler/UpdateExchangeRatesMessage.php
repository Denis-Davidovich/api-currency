<?php

declare(strict_types=1);

namespace App\Scheduler;

class UpdateExchangeRatesMessage
{
    public function __construct(
        public readonly \DateTimeImmutable $scheduledAt = new \DateTimeImmutable(),
    ) {}
}
