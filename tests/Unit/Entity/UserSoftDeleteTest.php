<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserSoftDeleteTest extends TestCase
{
    public function testUserSoftDelete(): void
    {
        $user = new User('testuser', 'password123');
        
        $this->assertFalse($user->isDeleted());
        $this->assertNull($user->getDeletedAt());
        
        $user->softDelete();
        
        $this->assertTrue($user->isDeleted());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getDeletedAt());
    }

    public function testUserRestore(): void
    {
        $user = new User('testuser', 'password123');
        
        $user->softDelete();
        $this->assertTrue($user->isDeleted());
        
        $user->restore();
        
        $this->assertFalse($user->isDeleted());
        $this->assertNull($user->getDeletedAt());
    }

    public function testTimestampsAreSetOnCreation(): void
    {
        $user = new User('testuser', 'password123');
        
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getCreatedAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $user->getUpdatedAt());
    }

    public function testUpdatedAtIsUpdatedOnModification(): void
    {
        $user = new User('testuser', 'password123');
        $originalUpdatedAt = $user->getUpdatedAt();
        
        // Wait a small moment to ensure timestamp difference
        usleep(1000);
        
        $user->setUsername('newusername');
        
        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testUpdatedAtIsUpdatedOnPasswordChange(): void
    {
        $user = new User('testuser', 'password123');
        $originalUpdatedAt = $user->getUpdatedAt();
        
        usleep(1000);
        
        $user->setPassword('newpassword123');
        
        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testUpdatedAtIsUpdatedOnRoleChange(): void
    {
        $user = new User('testuser', 'password123');
        $originalUpdatedAt = $user->getUpdatedAt();
        
        usleep(1000);
        
        $user->setRoles(['ROLE_ADMIN']);
        
        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testUpdatedAtIsUpdatedOnSoftDelete(): void
    {
        $user = new User('testuser', 'password123');
        $originalUpdatedAt = $user->getUpdatedAt();
        
        usleep(1000);
        
        $user->softDelete();
        
        $this->assertGreaterThan($originalUpdatedAt, $user->getUpdatedAt());
    }

    public function testUpdatedAtIsUpdatedOnRestore(): void
    {
        $user = new User('testuser', 'password123');
        $user->softDelete();
        $deleteUpdatedAt = $user->getUpdatedAt();
        
        usleep(1000);
        
        $user->restore();
        
        $this->assertGreaterThan($deleteUpdatedAt, $user->getUpdatedAt());
    }

    public function testSetDeletedAtDirectly(): void
    {
        $user = new User('testuser', 'password123');
        $deleteTime = new \DateTime();
        
        $user->setDeletedAt($deleteTime);
        
        $this->assertTrue($user->isDeleted());
        $this->assertEquals($deleteTime, $user->getDeletedAt());
    }

    public function testSetCreatedAt(): void
    {
        $user = new User('testuser', 'password123');
        $customTime = new \DateTime('2023-01-01 12:00:00');
        
        $user->setCreatedAt($customTime);
        
        $this->assertEquals($customTime, $user->getCreatedAt());
    }

    public function testSetUpdatedAt(): void
    {
        $user = new User('testuser', 'password123');
        $customTime = new \DateTime('2023-01-01 12:00:00');
        
        $user->setUpdatedAt($customTime);
        
        $this->assertEquals($customTime, $user->getUpdatedAt());
    }
}