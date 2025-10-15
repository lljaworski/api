<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251015205926 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE companies (id INT AUTO_INCREMENT NOT NULL, tax_id VARCHAR(20) DEFAULT NULL, name VARCHAR(255) NOT NULL, taxpayer_prefix VARCHAR(4) DEFAULT NULL, eori_number VARCHAR(17) DEFAULT NULL, eu_country_code VARCHAR(4) DEFAULT NULL, vat_reg_number_eu VARCHAR(20) DEFAULT NULL, other_id_country_code VARCHAR(4) DEFAULT NULL, other_id_number VARCHAR(50) DEFAULT NULL, no_id_marker TINYINT(1) DEFAULT NULL, internal_id VARCHAR(50) DEFAULT NULL, buyer_id VARCHAR(50) DEFAULT NULL, client_number VARCHAR(50) DEFAULT NULL, country_code VARCHAR(4) DEFAULT NULL, address_line1 VARCHAR(255) DEFAULT NULL, address_line2 VARCHAR(255) DEFAULT NULL, gln VARCHAR(13) DEFAULT NULL, correspondence_country_code VARCHAR(4) DEFAULT NULL, correspondence_address_line1 VARCHAR(255) DEFAULT NULL, correspondence_address_line2 VARCHAR(255) DEFAULT NULL, correspondence_gln VARCHAR(13) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, taxpayer_status INT DEFAULT NULL, jst_marker INT DEFAULT NULL, gv_marker INT DEFAULT NULL, role INT DEFAULT NULL, other_role_marker TINYINT(1) DEFAULT NULL, role_description VARCHAR(255) DEFAULT NULL, share_percentage NUMERIC(5, 2) DEFAULT NULL, deleted_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE companies');
    }
}
