<?php

declare(strict_types=1);

namespace App\Application\Query\SystemPreference;

use App\Application\Query\AbstractQuery;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query to get a collection of system preferences with pagination.
 */
final class GetSystemPreferencesQuery extends AbstractQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,
        
        #[Assert\Range(min: 1, max: 100)]
        public readonly int $itemsPerPage = 30
    ) {
        parent::__construct();
    }
}
