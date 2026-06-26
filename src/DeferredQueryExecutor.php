<?php

declare(strict_types=1);

namespace SurrealDB\Laravel;

use Illuminate\Contracts\Container\Container;
use LogicException;
use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;

final class DeferredQueryExecutor implements QueryExecutor
{
    public function __construct(
        private readonly Container $container,
        private readonly string $abstract,
    ) {}

    /**
     * @return list<mixed>
     */
    public function query(BoundQuery $query): array
    {
        $executor = $this->container->make($this->abstract);

        if (! $executor instanceof QueryExecutor) {
            throw new LogicException(sprintf('Container entry "%s" must resolve to a QueryExecutor.', $this->abstract));
        }

        return $executor->query($query);
    }
}
