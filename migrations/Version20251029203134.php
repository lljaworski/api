<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251029203134 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invoices and invoice_items tables for Phase 1 invoice management system';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice_items (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, description VARCHAR(255) NOT NULL, quantity NUMERIC(10, 3) NOT NULL, unit VARCHAR(10) NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, net_amount NUMERIC(10, 2) NOT NULL, vat_rate NUMERIC(5, 2) NOT NULL, vat_amount NUMERIC(10, 2) NOT NULL, gross_amount NUMERIC(10, 2) NOT NULL, sort_order INT NOT NULL, INDEX IDX_DCC4B9F82989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoices (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, number VARCHAR(50) NOT NULL, issue_date DATE NOT NULL, sale_date DATE NOT NULL, due_date DATE DEFAULT NULL, currency VARCHAR(3) NOT NULL, payment_method INT DEFAULT NULL, status VARCHAR(255) NOT NULL, is_paid TINYINT(1) NOT NULL, paid_at DATETIME DEFAULT NULL, notes LONGTEXT DEFAULT NULL, ksef_number VARCHAR(100) DEFAULT NULL, ksef_submitted_at DATETIME DEFAULT NULL, subtotal NUMERIC(10, 2) NOT NULL, vat_amount NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_6A2F2F959395C3F3 (customer_id), UNIQUE INDEX UNIQ_INVOICE_NUMBER (number), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE invoice_items ADD CONSTRAINT FK_DCC4B9F82989F1FD FOREIGN KEY (invoice_id) REFERENCES invoices (id)');
        $this->addSql('ALTER TABLE invoices ADD CONSTRAINT FK_6A2F2F959395C3F3 FOREIGN KEY (customer_id) REFERENCES companies (id)');
        $this->addSql('ALTER TABLE companies DROP internal_id, DROP buyer_id, DROP role, DROP other_role_marker, DROP role_description, DROP share_percentage');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE invoice_items DROP FOREIGN KEY FK_DCC4B9F82989F1FD');
        $this->addSql('ALTER TABLE invoices DROP FOREIGN KEY FK_6A2F2F959395C3F3');
        $this->addSql('DROP TABLE invoice_items');
        $this->addSql('DROP TABLE invoices');
        $this->addSql('ALTER TABLE companies ADD internal_id VARCHAR(50) DEFAULT NULL, ADD buyer_id VARCHAR(50) DEFAULT NULL, ADD role INT DEFAULT NULL, ADD other_role_marker TINYINT(1) DEFAULT NULL, ADD role_description VARCHAR(255) DEFAULT NULL, ADD share_percentage NUMERIC(5, 2) DEFAULT NULL');
    }
}
