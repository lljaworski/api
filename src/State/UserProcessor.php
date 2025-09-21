<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\DeleteOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<User, User|void>
 */
final class UserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof User) {
            return $data;
        }

        // Handle delete operations (soft delete)
        if ($operation instanceof DeleteOperationInterface) {
            $existingUser = $this->userRepository->findActiveById($data->getId());
            if ($existingUser) {
                $existingUser->softDelete();
                $this->entityManager->flush();
            }
            return null;
        }

        // Handle create and update operations
        $isUpdate = $data->getId() !== null;
        
        if ($isUpdate) {
            // For updates, get the existing user and update only the provided fields
            $existingUser = $this->userRepository->findActiveById($data->getId());
            if (!$existingUser) {
                throw new \RuntimeException('User not found');
            }

            // Update fields if they were provided
            if ($data->getUsername() !== $existingUser->getUsername()) {
                $existingUser->setUsername($data->getUsername());
            }

            if ($data->getRoles() !== $existingUser->getRoles()) {
                $existingUser->setRoles($data->getRoles());
            }

            // Only hash password if it's different (assuming it's a new plain password)
            if ($data->getPassword() && $data->getPassword() !== $existingUser->getPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($existingUser, $data->getPassword());
                $existingUser->setPassword($hashedPassword);
            }

            $this->entityManager->flush();
            return $existingUser;
        } else {
            // For creation, hash the password and persist
            $hashedPassword = $this->passwordHasher->hashPassword($data, $data->getPassword());
            $data->setPassword($hashedPassword);

            $this->entityManager->persist($data);
            $this->entityManager->flush();

            return $data;
        }
    }
}