<?php

declare(strict_types=1);

namespace App\Tests\Trait;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

trait DatabaseTestTrait
{
    private array $createdEntities = [];
    private static bool $databaseInitialized = false;
    
    protected function ensureTestAdmin(): User
    {
        $entityManager = $this->entityManager ?? static::getContainer()->get(EntityManagerInterface::class);
        $userRepository = $entityManager->getRepository(User::class);
        
        // Check if admin already exists
        $existingAdmin = $userRepository->findOneBy(['username' => 'admin']);
        if ($existingAdmin) {
            return $existingAdmin;
        }
        
        // Create admin if it doesn't exist
        return $this->createTestAdmin();
    }
    
    protected function createTestAdmin(): User
    {
        $entityManager = $this->entityManager ?? static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $admin = new User('admin', 'admin123!');
        $admin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $passwordHasher->hashPassword($admin, 'admin123!');
        $admin->setPassword($hashedPassword);
        
        $entityManager->persist($admin);
        $entityManager->flush();
        
        $this->createdEntities[] = $admin;
        
        return $admin;
    }
    
    protected function createTestUser(string $username, string $password, array $roles = ['ROLE_USER']): User
    {
        $entityManager = $this->entityManager ?? static::getContainer()->get(EntityManagerInterface::class);
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        
        $user = new User($username, $password);
        $user->setRoles($roles);
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        $this->createdEntities[] = $user;
        
        return $user;
    }
    
    protected function cleanupTestData(): void
    {
        $entityManager = $this->entityManager ?? static::getContainer()->get(EntityManagerInterface::class);
        
        try {
            // Remove all users except admin
            $userRepository = $entityManager->getRepository(User::class);
            $testUsers = $userRepository->createQueryBuilder('u')
                ->where('u.username != :admin')
                ->setParameter('admin', 'admin')
                ->getQuery()
                ->getResult();
            
            foreach ($testUsers as $user) {
                $entityManager->remove($user);
            }
            
            $entityManager->flush();
        } catch (\Exception $e) {
            // Ignore errors during cleanup
        }
        
        $this->createdEntities = [];
    }
    
    protected function trackEntityForCleanup($entity): void
    {
        if ($entity && !in_array($entity, $this->createdEntities, true)) {
            $this->createdEntities[] = $entity;
        }
    }
    
    protected function generateUniqueUsername(string $base = 'testuser'): string
    {
        return $base . '_' . uniqid();
    }
}