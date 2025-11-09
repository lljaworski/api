<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109211409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert payment_method column from INT to VARCHAR enum type for PaymentMethodEnum';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices CHANGE payment_method payment_method VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoices CHANGE payment_method payment_method INT DEFAULT NULL');
    }
}
