<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SystemPreference;
use App\Enum\PreferenceKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemPreference>
 */
class SystemPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemPreference::class);
    }

    /**
     * Find preference by key
     */
    public function findByKey(PreferenceKey $key): ?SystemPreference
    {
        return $this->createQueryBuilder('sp')
            ->andWhere('sp.preferenceKey = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all preferences
     * 
     * @return SystemPreference[]
     */
    public function findAllPreferences(): array
    {
        return $this->createQueryBuilder('sp')
            ->orderBy('sp.preferenceKey', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find preferences with pagination
     * 
     * @return SystemPreference[]
     */
    public function findPreferences(int $limit, int $offset = 0): array
    {
        return $this->createQueryBuilder('sp')
            ->orderBy('sp.preferenceKey', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count all preferences
     */
    public function countPreferences(): int
    {
        return (int) $this->createQueryBuilder('sp')
            ->select('COUNT(sp.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
