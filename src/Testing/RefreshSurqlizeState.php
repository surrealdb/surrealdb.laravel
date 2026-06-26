<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Testing;

use SurrealDB\Laravel\SurrealDBManager;
use SurrealDB\SDK\Contracts\QueryExecutor;
use Surqlize\Connection\ConnectionManager;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Fields\FieldSetRegistry;

trait RefreshSurqlizeState
{
    protected function resetSurqlizeState(): void
    {
        ConnectionManager::reset();
        ModelMetadata::clear();
        FieldSetRegistry::clear();
    }

    protected function useSurqlizeExecutor(QueryExecutor $executor): QueryExecutor
    {
        ConnectionManager::set($executor);

        return $executor;
    }

    protected function fakeSurrealDB(?string $connection = null): FakeQueryExecutor
    {
        return app(SurrealDBManager::class)->fake($connection);
    }

    protected function resetSurrealDBFakes(): void
    {
        app(SurrealDBManager::class)->resetFakes();
    }
}
