<?php

declare(strict_types=1);

namespace App\Tests\Service\FreeCurrencyApi;

use App\Service\FreeCurrencyApi\DTO\ExchangeRateData;
use App\Service\FreeCurrencyApi\Exception\ApiException;
use App\Service\FreeCurrencyApi\FreeCurrencyApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class FreeCurrencyApiClientTest extends TestCase
{
    public function testGetLatestRatesSuccess(): void
    {
        $responseData = [
            'data' => [
                'EUR' => 0.92,
                'GBP' => 0.79,
                'RUB' => 91.5,
            ],
        ];

        $mockResponse = new MockResponse(json_encode($responseData));
        $httpClient = new MockHttpClient($mockResponse);

        $client = new FreeCurrencyApiClient(
            $httpClient,
            'https://api.freecurrencyapi.com/v1',
            'test_api_key'
        );

        $rates = $client->getLatestRates('USD', ['EUR', 'GBP', 'RUB']);

        $this->assertCount(3, $rates);
        $this->assertContainsOnlyInstancesOf(ExchangeRateData::class, $rates);

        $eurRate = $this->findRateByTarget($rates, 'EUR');
        $this->assertNotNull($eurRate);
        $this->assertSame('USD', $eurRate->baseCurrency);
        $this->assertSame('EUR', $eurRate->targetCurrency);
        $this->assertSame('0.92', $eurRate->rate);
    }

    public function testGetLatestRatesWithoutCurrencyFilter(): void
    {
        $responseData = [
            'data' => [
                'EUR' => 0.92,
                'GBP' => 0.79,
            ],
        ];

        $mockResponse = new MockResponse(json_encode($responseData));
        $httpClient = new MockHttpClient($mockResponse);

        $client = new FreeCurrencyApiClient(
            $httpClient,
            'https://api.freecurrencyapi.com/v1',
            'test_api_key'
        );

        $rates = $client->getLatestRates('USD');

        $this->assertCount(2, $rates);
    }

    public function testGetLatestRatesInvalidResponse(): void
    {
        $mockResponse = new MockResponse(json_encode(['error' => 'something']));
        $httpClient = new MockHttpClient($mockResponse);

        $client = new FreeCurrencyApiClient(
            $httpClient,
            'https://api.freecurrencyapi.com/v1',
            'test_api_key'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Missing data field');

        $client->getLatestRates('USD');
    }

    public function testGetLatestRatesHttpError(): void
    {
        $mockResponse = new MockResponse('', ['http_code' => 500]);
        $httpClient = new MockHttpClient($mockResponse);

        $client = new FreeCurrencyApiClient(
            $httpClient,
            'https://api.freecurrencyapi.com/v1',
            'test_api_key'
        );

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('API request failed');

        $client->getLatestRates('USD');
    }

    public function testGetCurrenciesSuccess(): void
    {
        $responseData = [
            'data' => [
                'USD' => [
                    'symbol' => '$',
                    'name' => 'US Dollar',
                ],
                'EUR' => [
                    'symbol' => '€',
                    'name' => 'Euro',
                ],
            ],
        ];

        $mockResponse = new MockResponse(json_encode($responseData));
        $httpClient = new MockHttpClient($mockResponse);

        $client = new FreeCurrencyApiClient(
            $httpClient,
            'https://api.freecurrencyapi.com/v1',
            'test_api_key'
        );

        $currencies = $client->getCurrencies();

        $this->assertCount(2, $currencies);

        $usd = $currencies[0];
        $this->assertSame('USD', $usd->code);
        $this->assertSame('US Dollar', $usd->name);
        $this->assertSame('$', $usd->symbol);
    }

    /**
     * @param ExchangeRateData[] $rates
     */
    private function findRateByTarget(array $rates, string $targetCurrency): ?ExchangeRateData
    {
        foreach ($rates as $rate) {
            if ($rate->targetCurrency === $targetCurrency) {
                return $rate;
            }
        }
        return null;
    }
}
