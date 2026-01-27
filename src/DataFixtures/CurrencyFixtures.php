<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Currency;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CurrencyFixtures extends Fixture
{
    private const array CURRENCIES = [
        ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'],
        ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'],
        ['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£'],
        ['code' => 'RUB', 'name' => 'Russian Ruble', 'symbol' => '₽'],
        ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
        ['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥'],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::CURRENCIES as $currencyData) {
            $currency = new Currency(
                $currencyData['code'],
                $currencyData['name'],
                $currencyData['symbol']
            );

            $manager->persist($currency);
        }

        $manager->flush();
    }
}
