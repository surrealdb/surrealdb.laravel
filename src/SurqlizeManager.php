<?php

declare(strict_types=1);

namespace SurrealDB\Laravel;

use Closure;
use InvalidArgumentException;
use SurrealDB\SDK\Contracts\QueryExecutor;
use Surqlize\Connection\ConnectionManager;
use Surqlize\Model\Model;
use Surqlize\Model\SchemaManager;

final class SurqlizeManager
{
    /**
     * @param list<class-string<Model>> $models
     */
    public function __construct(
        private readonly QueryExecutor $executor,
        private readonly SurrealDBManager $connections,
        private readonly SchemaManager $schemas,
        private readonly array $models = [],
    ) {}

    public function executor(): QueryExecutor
    {
        return $this->executor;
    }

    /**
     * @param list<class-string<Model>>|null $models
     *
     * @return list<string>
     */
    public function schemaDefinitions(?array $models = null): array
    {
        return $this->schemas->definitions($models ?? $this->models);
    }

    /**
     * @param list<class-string<Model>>|null $models
     */
    public function applySchema(?array $models = null, ?QueryExecutor $executor = null): void
    {
        $this->schemas->apply($models ?? $this->models, $executor ?? $this->executor);
    }

    public function transaction(Closure $callback, ?QueryExecutor $executor = null, ?string $connection = null): mixed
    {
        if ($executor !== null && $connection !== null) {
            throw new InvalidArgumentException('Pass either a Surqlize transaction executor or a connection name, not both.');
        }

        $executor ??= $connection === null
            ? $this->executor
            : $this->connections->using($connection);

        return ConnectionManager::transaction($callback, $executor);
    }
}
