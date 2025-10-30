<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Invoice;
use App\Enum\InvoiceStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 *
 * @method Invoice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Invoice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Invoice[]    findAll()
 * @method Invoice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invoice::class);
    }

    /**
     * Find active invoice by ID (not soft deleted)
     */
    public function findActiveById(int $id): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.id = :id')
            ->andWhere('i.deletedAt IS NULL')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find active invoices with pagination and optional filtering
     */
    public function findActiveInvoices(
        int $limit,
        int $offset = 0,
        ?string $search = null,
        ?InvoiceStatus $status = null,
        ?bool $isPaid = null,
        ?int $customerId = null,
        ?\DateTimeInterface $issueDateFrom = null,
        ?\DateTimeInterface $issueDateTo = null
    ): array {
        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.customer', 'c')
            ->addSelect('c')
            ->leftJoin('i.items', 'items')
            ->addSelect('items')
            ->andWhere('i.deletedAt IS NULL')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        if ($search !== null && $search !== '') {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('i.number', ':search'),
                $qb->expr()->like('c.name', ':search'),
                $qb->expr()->like('c.taxId', ':search'),
                $qb->expr()->like('i.notes', ':search')
            ))
            ->setParameter('search', '%' . $search . '%');
        }

        if ($status !== null) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $status);
        }

        if ($isPaid !== null) {
            $qb->andWhere('i.isPaid = :isPaid')
               ->setParameter('isPaid', $isPaid);
        }

        if ($customerId !== null) {
            $qb->andWhere('c.id = :customerId')
               ->setParameter('customerId', $customerId);
        }

        if ($issueDateFrom !== null) {
            $qb->andWhere('i.issueDate >= :issueDateFrom')
               ->setParameter('issueDateFrom', $issueDateFrom);
        }

        if ($issueDateTo !== null) {
            $qb->andWhere('i.issueDate <= :issueDateTo')
               ->setParameter('issueDateTo', $issueDateTo);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Count active invoices for pagination with optional filtering
     */
    public function countActiveInvoices(
        ?string $search = null,
        ?InvoiceStatus $status = null,
        ?bool $isPaid = null,
        ?int $customerId = null,
        ?\DateTimeInterface $issueDateFrom = null,
        ?\DateTimeInterface $issueDateTo = null
    ): int {
        $qb = $this->createQueryBuilder('i')
            ->select('COUNT(i.id)')
            ->leftJoin('i.customer', 'c')
            ->andWhere('i.deletedAt IS NULL');

        if ($search !== null && $search !== '') {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('i.number', ':search'),
                $qb->expr()->like('c.name', ':search'),
                $qb->expr()->like('c.taxId', ':search'),
                $qb->expr()->like('i.notes', ':search')
            ))
            ->setParameter('search', '%' . $search . '%');
        }

        if ($status !== null) {
            $qb->andWhere('i.status = :status')
               ->setParameter('status', $status);
        }

        if ($isPaid !== null) {
            $qb->andWhere('i.isPaid = :isPaid')
               ->setParameter('isPaid', $isPaid);
        }

        if ($customerId !== null) {
            $qb->andWhere('c.id = :customerId')
               ->setParameter('customerId', $customerId);
        }

        if ($issueDateFrom !== null) {
            $qb->andWhere('i.issueDate >= :issueDateFrom')
               ->setParameter('issueDateFrom', $issueDateFrom);
        }

        if ($issueDateTo !== null) {
            $qb->andWhere('i.issueDate <= :issueDateTo')
               ->setParameter('issueDateTo', $issueDateTo);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Find overdue invoices (issued, unpaid, past due date)
     */
    public function findOverdueInvoices(): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.customer', 'c')
            ->addSelect('c')
            ->andWhere('i.deletedAt IS NULL')
            ->andWhere('i.status = :status')
            ->andWhere('i.isPaid = false')
            ->andWhere('i.dueDate IS NOT NULL')
            ->andWhere('i.dueDate < :today')
            ->setParameter('status', InvoiceStatus::ISSUED)
            ->setParameter('today', new \DateTime('today'))
            ->orderBy('i.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find invoices by customer
     */
    public function findByCustomer(int $customerId, int $limit = 50, int $offset = 0): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.customer', 'c')
            ->addSelect('c')
            ->leftJoin('i.items', 'items')
            ->addSelect('items')
            ->andWhere('i.deletedAt IS NULL')
            ->andWhere('c.id = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find invoice by number (for uniqueness check)
     */
    public function findByNumber(string $number): ?Invoice
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.number = :number')
            ->andWhere('i.deletedAt IS NULL')
            ->setParameter('number', $number)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get next sequence number for invoice number generation
     */
    public function getNextSequenceNumber(\DateTimeInterface $date): int
    {
        $year = $date->format('Y');
        $month = $date->format('m');
        
        $result = $this->createQueryBuilder('i')
            ->select('COUNT(i.id) + 1')
            ->andWhere('i.deletedAt IS NULL')
            ->andWhere('YEAR(i.issueDate) = :year')
            ->andWhere('MONTH(i.issueDate) = :month')
            ->setParameter('year', $year)
            ->setParameter('month', $month)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $result;
    }

    /**
     * Get invoice statistics for dashboard
     */
    public function getInvoiceStatistics(): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select([
                'COUNT(i.id) as total_count',
                'COUNT(CASE WHEN i.status = :draft THEN 1 END) as draft_count',
                'COUNT(CASE WHEN i.status = :issued THEN 1 END) as issued_count',
                'COUNT(CASE WHEN i.status = :paid THEN 1 END) as paid_count',
                'COUNT(CASE WHEN i.status = :cancelled THEN 1 END) as cancelled_count',
                'SUM(CASE WHEN i.status != :cancelled THEN i.total ELSE 0 END) as total_amount',
                'SUM(CASE WHEN i.isPaid = true THEN i.total ELSE 0 END) as paid_amount',
                'SUM(CASE WHEN i.status = :issued AND i.isPaid = false THEN i.total ELSE 0 END) as outstanding_amount'
            ])
            ->andWhere('i.deletedAt IS NULL')
            ->setParameter('draft', InvoiceStatus::DRAFT)
            ->setParameter('issued', InvoiceStatus::ISSUED)
            ->setParameter('paid', InvoiceStatus::PAID)
            ->setParameter('cancelled', InvoiceStatus::CANCELLED);

        return $qb->getQuery()->getSingleResult();
    }

    /**
     * Find recent invoices for dashboard
     */
    public function findRecentInvoices(int $limit = 10): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.customer', 'c')
            ->addSelect('c')
            ->andWhere('i.deletedAt IS NULL')
            ->orderBy('i.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Save an invoice (create or update)
     */
    public function save(Invoice $invoice, bool $flush = false): void
    {
        $this->getEntityManager()->persist($invoice);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an invoice (hard delete - use with caution)
     */
    public function remove(Invoice $invoice, bool $flush = false): void
    {
        $this->getEntityManager()->remove($invoice);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}