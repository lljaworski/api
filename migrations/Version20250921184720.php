<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250921184720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add soft delete and timestamp fields to users table';
    }

    public function up(Schema $schema): void
    {
        // Add new columns
        $this->addSql('ALTER TABLE users ADD deleted_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD updated_at DATETIME DEFAULT NULL');
        
        // Set default values for existing records
        $this->addSql('UPDATE users SET created_at = NOW(), updated_at = NOW() WHERE created_at IS NULL');
        
        // Make created_at and updated_at NOT NULL after setting defaults
        $this->addSql('ALTER TABLE users MODIFY created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE users MODIFY updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users DROP deleted_at, DROP created_at, DROP updated_at');
    }
}
