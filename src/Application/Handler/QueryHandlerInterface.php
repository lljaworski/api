<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Query\QueryInterface;

/**
 * Interface for query handlers in the CQRS pattern.
 * Query handlers process queries and return data without side effects.
 * 
 * @template T of QueryInterface
 */
interface QueryHandlerInterface
{
    /**
     * Handles the given query.
     * 
     * @param T $query
     * @return mixed The result of the query execution
     */
    public function __invoke(QueryInterface $query): mixed;
}