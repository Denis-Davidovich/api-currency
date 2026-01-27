<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Service\ExchangeRateService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class UpdateExchangeRatesHandler
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function __invoke(UpdateExchangeRatesMessage $message): void
    {
        $this->logger?->info('Starting scheduled exchange rate update', [
            'scheduled_at' => $message->scheduledAt->format('Y-m-d H:i:s'),
        ]);

        try {
            $count = $this->exchangeRateService->updateRates();

            $this->logger?->info('Scheduled exchange rate update completed', [
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            $this->logger?->error('Scheduled exchange rate update failed', [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
