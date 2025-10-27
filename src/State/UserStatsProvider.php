<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\UserStats;
use App\Application\Query\User\GetUserStatsQuery;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class UserStatsProvider implements ProviderInterface
{
    public function __construct(
        private readonly MessageBusInterface $queryBus
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): UserStats
    {
        $query = new GetUserStatsQuery();
        $envelope = $this->queryBus->dispatch($query);
        $statsDto = $envelope->last(HandledStamp::class)->getResult();

        return new UserStats(
            total: $statsDto->total,
            active: $statsDto->active,
            inactive: $statsDto->inactive,
            adminCount: $statsDto->adminCount,
            regularUserCount: $statsDto->regularUserCount,
            timestamp: $statsDto->timestamp
        );
    }
}