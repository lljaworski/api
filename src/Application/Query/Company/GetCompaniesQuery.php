<?php

declare(strict_types=1);

namespace App\Application\Query\Company;

use App\Application\Query\AbstractQuery;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query to get a paginated list of companies with optional search.
 */
final class GetCompaniesQuery extends AbstractQuery
{
    public function __construct(
        #[Assert\Positive]
        public readonly int $page = 1,
        
        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        public readonly int $itemsPerPage = 30,
        
        public readonly ?string $search = null
    ) {
        parent::__construct();
    }
}