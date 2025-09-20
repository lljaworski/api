<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreation(): void
    {
        $user = new User('testuser', 'hashedpassword', ['ROLE_USER']);

        $this->assertEquals('testuser', $user->getUsername());
        $this->assertEquals('testuser', $user->getUserIdentifier());
        $this->assertEquals('hashedpassword', $user->getPassword());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testUserCreationWithDefaultRole(): void
    {
        $user = new User('testuser', 'hashedpassword');

        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testGetRolesEnsuresRoleUser(): void
    {
        $user = new User('testuser', 'hashedpassword', ['ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertCount(2, $roles);
    }

    public function testGetRolesRemovesDuplicates(): void
    {
        $user = new User('testuser', 'hashedpassword', ['ROLE_USER', 'ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertCount(2, array_unique($roles));
        $this->assertContains('ROLE_USER', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
    }

    public function testSetUsername(): void
    {
        $user = new User('testuser', 'hashedpassword');
        $user->setUsername('newusername');

        $this->assertEquals('newusername', $user->getUsername());
        $this->assertEquals('newusername', $user->getUserIdentifier());
    }

    public function testSetPassword(): void
    {
        $user = new User('testuser', 'hashedpassword');
        $user->setPassword('newhashedpassword');

        $this->assertEquals('newhashedpassword', $user->getPassword());
    }

    public function testSetRoles(): void
    {
        $user = new User('testuser', 'hashedpassword');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_MODERATOR']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_USER', $roles); // Always added
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_MODERATOR', $roles);
    }

    public function testEraseCredentials(): void
    {
        $user = new User('testuser', 'hashedpassword');
        
        // This method should not throw any exceptions
        $user->eraseCredentials();
        
        // Password should remain unchanged (no plain password to erase)
        $this->assertEquals('hashedpassword', $user->getPassword());
    }

    public function testUserImplementsCorrectInterfaces(): void
    {
        $user = new User('testuser', 'hashedpassword');

        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\UserInterface::class, $user);
        $this->assertInstanceOf(\Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface::class, $user);
    }

    public function testUserIdIsNullInitially(): void
    {
        $user = new User('testuser', 'hashedpassword');

        $this->assertNull($user->getId());
    }
}