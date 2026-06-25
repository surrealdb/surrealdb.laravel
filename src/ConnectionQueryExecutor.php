<?php

declare(strict_types=1);

namespace SurrealDB\Laravel;

use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;

final class ConnectionQueryExecutor implements QueryExecutor
{
    public function __construct(
        private readonly SurrealDBManager $manager,
        private readonly string $connection,
    ) {}

    /**
     * @return list<mixed>
     */
    public function query(BoundQuery $query): array
    {
        return $this->manager->query($query, $this->connection);
    }
}
