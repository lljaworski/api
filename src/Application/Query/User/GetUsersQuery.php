<?php

declare(strict_types=1);

namespace App\Application\Query\User;

use App\Application\Query\AbstractQuery;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query to get a collection of users with pagination.
 */
final class GetUsersQuery extends AbstractQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,
        
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $itemsPerPage = 30,
        
        public readonly ?string $search = null
    ) {
        parent::__construct();
    }
}