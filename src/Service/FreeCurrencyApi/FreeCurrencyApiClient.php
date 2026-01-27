<?php

declare(strict_types=1);

namespace App\Service\FreeCurrencyApi;

use App\Service\FreeCurrencyApi\DTO\CurrencyData;
use App\Service\FreeCurrencyApi\DTO\ExchangeRateData;
use App\Service\FreeCurrencyApi\Exception\ApiException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;

class FreeCurrencyApiClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {}

    /**
     * @param string[] $currencies Target currencies to get rates for
     * @return ExchangeRateData[]
     */
    public function getLatestRates(string $baseCurrency, array $currencies = []): array
    {
        $query = [
            'apikey' => $this->apiKey,
            'base_currency' => strtoupper($baseCurrency),
        ];

        if (!empty($currencies)) {
            $query['currencies'] = implode(',', array_map('strtoupper', $currencies));
        }

        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/latest', [
                'query' => $query,
            ]);

            $data = $response->toArray();
        } catch (ExceptionInterface $e) {
            throw ApiException::requestFailed($e->getMessage(), $e);
        }

        if (!isset($data['data']) || !is_array($data['data'])) {
            throw ApiException::invalidResponse('Missing data field');
        }

        $rates = [];
        foreach ($data['data'] as $currencyCode => $rate) {
            $rates[] = new ExchangeRateData(
                baseCurrency: strtoupper($baseCurrency),
                targetCurrency: (string) $currencyCode,
                rate: (string) $rate,
            );
        }

        return $rates;
    }

    /**
     * @return CurrencyData[]
     */
    public function getCurrencies(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->baseUrl . '/currencies', [
                'query' => [
                    'apikey' => $this->apiKey,
                ],
            ]);

            $data = $response->toArray();
        } catch (ExceptionInterface $e) {
            throw ApiException::requestFailed($e->getMessage(), $e);
        }

        if (!isset($data['data']) || !is_array($data['data'])) {
            throw ApiException::invalidResponse('Missing data field');
        }

        $currencies = [];
        foreach ($data['data'] as $code => $currencyInfo) {
            $currencies[] = CurrencyData::fromArray((string) $code, $currencyInfo);
        }

        return $currencies;
    }
}
