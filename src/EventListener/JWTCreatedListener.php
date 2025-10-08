<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;

/**
 * JWT Created Event Listener
 * 
 * Adds userId field to JWT token payload when token is created for User entities.
 * This allows frontend applications to access the user ID without additional API calls.
 */
final class JWTCreatedListener
{
    /**
     * Handle the JWT created event by adding userId to the token payload.
     * 
     * @param JWTCreatedEvent $event The JWT creation event containing user and payload data
     */
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        $user = $event->getUser();
        $payload = $event->getData();

        // Only enhance payload for User entities to ensure type safety
        if ($user instanceof User) {
            // Add user ID to the token payload - primary requirement
            $payload['userId'] = $user->getId();
            
            // Add username and roles for frontend convenience
            $payload['username'] = $user->getUserIdentifier();
            $payload['roles'] = $user->getRoles();
        }

        // Update the JWT payload with enhanced data
        $event->setData($payload);
    }
}