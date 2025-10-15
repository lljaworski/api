<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Company;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for Company entity operations.
 * 
 * @extends ServiceEntityRepository<Company>
 */
class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    /**
     * Find an active company by ID (excludes soft deleted companies).
     */
    public function findActiveById(int $id): ?Company
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.id = :id')
            ->andWhere('c.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find active companies with pagination and optional search.
     * 
     * @param int $limit Maximum number of results
     * @param int $offset Number of results to skip
     * @param string|null $search Search term for name, taxId, email, or phoneNumber
     * @return Company[]
     */
    public function findActiveCompanies(int $limit, int $offset = 0, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.deletedAt IS NULL')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($search !== null && $search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('c.name', ':search'),
                    $qb->expr()->like('c.taxId', ':search'),
                    $qb->expr()->like('c.email', ':search'),
                    $qb->expr()->like('c.phoneNumber', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count active companies with optional search filter.
     * 
     * @param string|null $search Search term for name, taxId, email, or phoneNumber
     */
    public function countActiveCompanies(?string $search = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.deletedAt IS NULL');

        if ($search !== null && $search !== '') {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('c.name', ':search'),
                    $qb->expr()->like('c.taxId', ':search'),
                    $qb->expr()->like('c.email', ':search'),
                    $qb->expr()->like('c.phoneNumber', ':search')
                )
            )
            ->setParameter('search', '%' . $search . '%');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Save a company entity (shortcut method).
     */
    public function save(Company $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove a company entity (shortcut method).
     */
    public function remove(Company $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}