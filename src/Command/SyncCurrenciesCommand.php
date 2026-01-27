<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\ExchangeRateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:currencies:sync',
    description: 'Sync currencies from FreeCurrencyAPI',
)]
class SyncCurrenciesCommand extends Command
{
    public function __construct(
        private readonly ExchangeRateService $exchangeRateService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->info('Syncing currencies from FreeCurrencyAPI...');

        try {
            $count = $this->exchangeRateService->syncCurrencies();
            $io->success(sprintf('Successfully synced %d currencies', $count));

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(sprintf('Failed to sync currencies: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
