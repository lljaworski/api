<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\InvoiceSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InvoiceSettings>
 *
 * @method InvoiceSettings|null find($id, $lockMode = null, $lockVersion = null)
 * @method InvoiceSettings|null findOneBy(array $criteria, array $orderBy = null)
 * @method InvoiceSettings[]    findAll()
 * @method InvoiceSettings[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InvoiceSettingsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InvoiceSettings::class);
    }

    /**
     * Get the current invoice settings (there should only be one record)
     */
    public function getSettings(): ?InvoiceSettings
    {
        return $this->findOneBy([]);
    }

    /**
     * Get or create settings with default values
     */
    public function getOrCreateSettings(): InvoiceSettings
    {
        $settings = $this->getSettings();
        
        if ($settings === null) {
            $settings = new InvoiceSettings();
            $this->save($settings, true);
        }
        
        return $settings;
    }

    /**
     * Save settings
     */
    public function save(InvoiceSettings $settings, bool $flush = false): void
    {
        $this->getEntityManager()->persist($settings);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
