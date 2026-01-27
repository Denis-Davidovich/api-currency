<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\ExchangeRateRepository;
use App\Service\ExchangeRateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly ExchangeRateRepository $exchangeRateRepository,
        private readonly ExchangeRateService $exchangeRateService,
    ) {}

    #[Route('', name: 'admin_rates', methods: ['GET'])]
    public function rates(Request $request): Response
    {
        $dateStr = $request->query->get('date');
        $date = $dateStr
            ? new \DateTimeImmutable($dateStr)
            : new \DateTimeImmutable('today');

        $rates = $this->exchangeRateRepository->findRatesForDate($date);

        return $this->render('admin/rates.html.twig', [
            'rates' => $rates,
            'selectedDate' => $date,
        ]);
    }

    #[Route('/update', name: 'admin_rates_update', methods: ['POST'])]
    public function update(): Response
    {
        try {
            $count = $this->exchangeRateService->updateRates();
            $this->addFlash('success', sprintf('Updated %d exchange rates', $count));
        } catch (\Throwable $e) {
            $this->addFlash('error', sprintf('Failed to update rates: %s', $e->getMessage()));
        }

        return $this->redirectToRoute('admin_rates');
    }
}
