<?php

declare(strict_types=1);

namespace App\Dictionary;

use App\Application\DTO\DictionaryItem;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Registry for managing dictionary providers and their security rules.
 */
final class DictionaryRegistry
{
    /** @var DictionaryProviderInterface[] */
    private array $providers = [];

    /** @var array<string, string[]> */
    private array $securityRules = [];

    public function __construct(
        private readonly Security $security
    ) {
    }

    /**
     * Register a dictionary provider with optional security rules.
     * 
     * @param string[] $requiredRoles Roles required to access this dictionary
     */
    public function registerProvider(
        DictionaryProviderInterface $provider,
        array $requiredRoles = []
    ): void {
        $this->providers[$provider->getType()] = $provider;
        $this->securityRules[$provider->getType()] = $requiredRoles;
    }



    /**
     * Get dictionary items for a specific type.
     * 
     * @return DictionaryItem[]
     * @throws \InvalidArgumentException If dictionary type is not found
     * @throws AccessDeniedException If user doesn't have required permissions
     */
    public function getDictionary(string $type): array
    {
        if (!isset($this->providers[$type])) {
            throw new \InvalidArgumentException("Dictionary type '{$type}' not found");
        }

        $this->checkAccess($type);

        return $this->providers[$type]->getItems();
    }

    /**
     * Check if a dictionary type exists.
     */
    public function hasDictionary(string $type): bool
    {
        return isset($this->providers[$type]);
    }

    /**
     * Get all available dictionary types (that user has access to).
     * 
     * @return string[]
     */
    public function getAvailableTypes(): array
    {
        $availableTypes = [];

        foreach (array_keys($this->providers) as $type) {
            try {
                $this->checkAccess($type);
                $availableTypes[] = $type;
            } catch (AccessDeniedException) {
                // Skip types user doesn't have access to
            }
        }

        return $availableTypes;
    }

    /**
     * Check if user has access to a dictionary type.
     * 
     * @throws AccessDeniedException If access is denied
     */
    private function checkAccess(string $type): void
    {
        $requiredRoles = $this->securityRules[$type] ?? [];

        if (empty($requiredRoles)) {
            return; // No security rules, access allowed
        }

        foreach ($requiredRoles as $role) {
            if ($this->security->isGranted($role)) {
                return; // User has at least one required role
            }
        }

        throw new AccessDeniedException("Access denied to dictionary type '{$type}'");
    }
}