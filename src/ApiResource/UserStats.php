<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\UserStatsProvider;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/dashboard/users/stats',
            provider: UserStatsProvider::class,
            security: "is_granted('ROLE_ADMIN')",
            name: 'user_stats'
        )
    ],
    shortName: 'UserStats',
    description: 'User statistics for dashboard widgets',
    normalizationContext: ['groups' => ['user-stats:read']]
)]
class UserStats
{
    #[Groups(['user-stats:read'])]
    public int $total;

    #[Groups(['user-stats:read'])]
    public int $active;

    #[Groups(['user-stats:read'])]
    public int $inactive;

    #[Groups(['user-stats:read'])]
    public int $adminCount;

    #[Groups(['user-stats:read'])]
    public int $regularUserCount;

    #[Groups(['user-stats:read'])]
    public string $timestamp;

    public function __construct(
        int $total,
        int $active,
        int $inactive,
        int $adminCount,
        int $regularUserCount,
        string $timestamp,
    ) {
        $this->total = $total;
        $this->active = $active;
        $this->inactive = $inactive;
        $this->adminCount = $adminCount;
        $this->regularUserCount = $regularUserCount;
        $this->timestamp = $timestamp;
    }
}