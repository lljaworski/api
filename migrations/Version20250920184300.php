<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;

/**
 * Migration to add admin user with credentials admin:admin
 */
final class Version20250920184300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add admin user with default credentials (admin:admin123!)';
    }

    public function up(Schema $schema): void
    {
        // Use a working bcrypt hash for 'admin123!' password
        $hashedPassword = '$2y$10$9Y8rJ2YkFqMv8L5uK0vYs.gJ8Q7QfvFzYzgGx8KjJ5ZJ5tE6Q2VGy'; // admin123!
        
        $this->addSql('INSERT INTO users (username, password, roles) VALUES (?, ?, ?)', [
            'admin',
            $hashedPassword,
            '["ROLE_ADMIN", "ROLE_USER"]'
        ]);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM users WHERE username = ?', ['admin']);
    }
}