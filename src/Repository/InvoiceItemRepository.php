<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InvoiceItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InvoiceItem>
 *
 * @method InvoiceItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoiceItem|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoiceItem[]    findAll()
 * @method InvoiceItem[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceItem::class);
    }

    /**
     * Find items by invoice ID ordered by sort order
     */
    public function findByInvoice(int $invoiceId): array
    {
        return $this->createQueryBuilder('ii')
            ->andWhere('ii.invoice = :invoiceId')
            ->setParameter('invoiceId', $invoiceId)
            ->orderBy('ii.sortOrder', 'ASC')
            ->addOrderBy('ii.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get VAT summary for an invoice (grouped by VAT rate)
     */
    public function getVatSummaryByInvoice(int $invoiceId): array
    {
        return $this->createQueryBuilder('ii')
            ->select([
                'ii.vatRate',
                'SUM(ii.netAmount) as netAmount',
                'SUM(ii.vatAmount) as vatAmount',
                'SUM(ii.grossAmount) as grossAmount'
            ])
            ->andWhere('ii.invoice = :invoiceId')
            ->setParameter('invoiceId', $invoiceId)
            ->groupBy('ii.vatRate')
            ->orderBy('ii.vatRate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total amounts for an invoice
     */
    public function getTotalsByInvoice(int $invoiceId): array
    {
        $result = $this->createQueryBuilder('ii')
            ->select([
                'SUM(ii.netAmount) as totalNet',
                'SUM(ii.vatAmount) as totalVat',
                'SUM(ii.grossAmount) as totalGross'
            ])
            ->andWhere('ii.invoice = :invoiceId')
            ->setParameter('invoiceId', $invoiceId)
            ->getQuery()
            ->getSingleResult();

        return [
            'totalNet' => $result['totalNet'] ?? '0.00',
            'totalVat' => $result['totalVat'] ?? '0.00',
            'totalGross' => $result['totalGross'] ?? '0.00'
        ];
    }

    /**
     * Count items in an invoice
     */
    public function countByInvoice(int $invoiceId): int
    {
        return (int) $this->createQueryBuilder('ii')
            ->select('COUNT(ii.id)')
            ->andWhere('ii.invoice = :invoiceId')
            ->setParameter('invoiceId', $invoiceId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get the highest sort order for an invoice
     */
    public function getMaxSortOrderByInvoice(int $invoiceId): int
    {
        $result = $this->createQueryBuilder('ii')
            ->select('MAX(ii.sortOrder)')
            ->andWhere('ii.invoice = :invoiceId')
            ->setParameter('invoiceId', $invoiceId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int) $result : 0;
    }

    /**
     * Find items with specific description pattern (for product suggestions)
     */
    public function findByDescriptionPattern(string $pattern, int $limit = 10): array
    {
        return $this->createQueryBuilder('ii')
            ->select('DISTINCT ii.description, ii.unit, ii.unitPrice, ii.vatRate')
            ->andWhere('ii.description LIKE :pattern')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->orderBy('ii.description', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get most used products/services for suggestions
     */
    public function getMostUsedItems(int $limit = 20): array
    {
        return $this->createQueryBuilder('ii')
            ->select([
                'ii.description',
                'ii.unit',
                'AVG(ii.unitPrice) as avgPrice',
                'ii.vatRate',
                'COUNT(ii.id) as usageCount'
            ])
            ->groupBy('ii.description', 'ii.unit', 'ii.vatRate')
            ->orderBy('usageCount', 'DESC')
            ->addOrderBy('ii.description', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Save an invoice item
     */
    public function save(InvoiceItem $invoiceItem, bool $flush = false): void
    {
        $this->getEntityManager()->persist($invoiceItem);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Remove an invoice item
     */
    public function remove(InvoiceItem $invoiceItem, bool $flush = false): void
    {
        $this->getEntityManager()->remove($invoiceItem);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Bulk save multiple invoice items
     */
    public function saveMultiple(array $invoiceItems, bool $flush = false): void
    {
        foreach ($invoiceItems as $item) {
            $this->getEntityManager()->persist($item);
        }

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Update sort orders for items in an invoice
     */
    public function updateSortOrders(int $invoiceId, array $itemIdToSortOrder): void
    {
        foreach ($itemIdToSortOrder as $itemId => $sortOrder) {
            $this->createQueryBuilder('ii')
                ->update()
                ->set('ii.sortOrder', ':sortOrder')
                ->andWhere('ii.id = :itemId')
                ->andWhere('ii.invoice = :invoiceId')
                ->setParameter('sortOrder', $sortOrder)
                ->setParameter('itemId', $itemId)
                ->setParameter('invoiceId', $invoiceId)
                ->getQuery()
                ->execute();
        }
    }
}