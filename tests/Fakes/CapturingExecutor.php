<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Tests\Fakes;

use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;

final class CapturingExecutor implements QueryExecutor
{
    /** @var list<BoundQuery> */
    public array $queries = [];

    /**
     * @param list<mixed> $result
     */
    public function __construct(
        private readonly array $result = [],
    ) {}

    /**
     * @return list<mixed>
     */
    public function query(BoundQuery $query): array
    {
        $this->queries[] = $query;

        return $this->result;
    }
}
