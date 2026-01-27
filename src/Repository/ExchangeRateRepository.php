<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Currency;
use App\Entity\ExchangeRate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExchangeRate>
 */
class ExchangeRateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExchangeRate::class);
    }

    public function findRate(
        Currency $baseCurrency,
        Currency $targetCurrency,
        ?\DateTimeImmutable $date = null
    ): ?ExchangeRate {
        $date = $date ?? new \DateTimeImmutable('today');

        return $this->createQueryBuilder('er')
            ->where('er.baseCurrency = :base')
            ->andWhere('er.targetCurrency = :target')
            ->andWhere('er.rateDate <= :date')
            ->setParameter('base', $baseCurrency)
            ->setParameter('target', $targetCurrency)
            ->setParameter('date', $date, 'date_immutable')
            ->orderBy('er.rateDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRateByCode(
        string $baseCode,
        string $targetCode,
        ?\DateTimeImmutable $date = null
    ): ?ExchangeRate {
        $date = $date ?? new \DateTimeImmutable('today');

        return $this->createQueryBuilder('er')
            ->join('er.baseCurrency', 'bc')
            ->join('er.targetCurrency', 'tc')
            ->where('bc.code = :baseCode')
            ->andWhere('tc.code = :targetCode')
            ->andWhere('er.rateDate <= :date')
            ->setParameter('baseCode', strtoupper($baseCode))
            ->setParameter('targetCode', strtoupper($targetCode))
            ->setParameter('date', $date, 'date_immutable')
            ->orderBy('er.rateDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ExchangeRate[]
     */
    public function findLatestRates(?\DateTimeImmutable $date = null): array
    {
        $date = $date ?? new \DateTimeImmutable('today');

        return $this->createQueryBuilder('er')
            ->join('er.baseCurrency', 'bc')
            ->join('er.targetCurrency', 'tc')
            ->where('er.rateDate = :date')
            ->andWhere('bc.isActive = :active')
            ->andWhere('tc.isActive = :active')
            ->setParameter('date', $date, 'date_immutable')
            ->setParameter('active', true)
            ->orderBy('bc.code', 'ASC')
            ->addOrderBy('tc.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ExchangeRate[]
     */
    public function findRatesForDate(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('er')
            ->join('er.baseCurrency', 'bc')
            ->join('er.targetCurrency', 'tc')
            ->where('er.rateDate = :date')
            ->setParameter('date', $date, 'date_immutable')
            ->orderBy('bc.code', 'ASC')
            ->addOrderBy('tc.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findExistingRate(
        Currency $baseCurrency,
        Currency $targetCurrency,
        \DateTimeImmutable $date
    ): ?ExchangeRate {
        return $this->findOneBy([
            'baseCurrency' => $baseCurrency,
            'targetCurrency' => $targetCurrency,
            'rateDate' => $date,
        ]);
    }

    public function save(ExchangeRate $rate, bool $flush = false): void
    {
        $this->getEntityManager()->persist($rate);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
