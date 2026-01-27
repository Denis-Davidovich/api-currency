<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Currency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Currency>
 */
class CurrencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Currency::class);
    }

    public function findByCode(string $code): ?Currency
    {
        return $this->findOneBy(['code' => strtoupper($code)]);
    }

    /**
     * @return Currency[]
     */
    public function findActive(): array
    {
        return $this->findBy(['isActive' => true], ['code' => 'ASC']);
    }

    /**
     * @return string[]
     */
    public function getActiveCodes(): array
    {
        $result = $this->createQueryBuilder('c')
            ->select('c.code')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('c.code', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'code');
    }

    public function save(Currency $currency, bool $flush = false): void
    {
        $this->getEntityManager()->persist($currency);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
