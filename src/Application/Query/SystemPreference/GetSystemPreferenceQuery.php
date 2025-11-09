<?php

declare(strict_types=1);

namespace App\Application\Query\SystemPreference;

use App\Application\Query\AbstractQuery;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Query to get a single system preference by ID.
 */
final class GetSystemPreferenceQuery extends AbstractQuery
{
    public function __construct(
        #[Assert\NotNull]
        #[Assert\Positive]
        public readonly int $id
    ) {
        parent::__construct();
    }
}
