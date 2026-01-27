<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    public function testHomePage(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'API MPO');
    }

    public function testApiStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/status');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('php_version', $response);
        $this->assertArrayHasKey('symfony_version', $response);
        $this->assertSame('ok', $response['status']);
    }
}
