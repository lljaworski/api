<?php

declare(strict_types=1);

namespace App\Application\Handler\User;

use App\Application\DTO\UserStatsDTO;
use App\Application\Query\User\GetUserStatsQuery;
use App\Repository\UserRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class GetUserStatsQueryHandler
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {}

    public function __invoke(GetUserStatsQuery $query): UserStatsDTO
    {
        $active = $this->userRepository->countActiveUsers();
        $inactive = $this->userRepository->countInactiveUsers();
        $total = $active + $inactive;
        
        // Count users by roles
        $adminCount = $this->userRepository->countUsersByRole('ROLE_ADMIN');
        $regularUserCount = $this->userRepository->countUsersByRole('ROLE_USER');

        return new UserStatsDTO(
            total: $total,
            active: $active,
            inactive: $inactive,
            adminCount: $adminCount,
            regularUserCount: $regularUserCount,
            timestamp: (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)
        );
    }
}