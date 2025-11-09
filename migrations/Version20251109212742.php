<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251109212742 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create invoice_settings table with default format configuration';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE invoice_settings (id INT AUTO_INCREMENT NOT NULL, number_format VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Insert default settings
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $this->addSql("INSERT INTO invoice_settings (number_format, created_at, updated_at) VALUES ('FV/{year}/{month}/{number:4}', '$now', '$now')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE invoice_settings');
    }
}
