<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SurrealDB\Laravel\SurrealDBManager;

/**
 * @method static \SurrealDB\SDK\Surreal client(?string $name = null)
 * @method static \SurrealDB\SDK\Connection\ConnectionController connection(?string $name = null)
 * @method static \SurrealDB\SDK\Surreal connect(?string $name = null)
 * @method static void disconnect(?string $name = null)
 * @method static \SurrealDB\SDK\Surreal reconnect(?string $name = null)
 * @method static bool isConnected(?string $name = null)
 * @method static void health(?string $name = null)
 * @method static string version(?string $name = null)
 * @method static \SurrealDB\SDK\Contracts\QueryExecutor using(string $connection)
 * @method static \SurrealDB\Laravel\Testing\FakeQueryExecutor fake(?string $connection = null, ?\SurrealDB\Laravel\Testing\FakeQueryExecutor $fake = null)
 * @method static void assertSurrealQuerySent(string $expected, ?string $connection = null)
 * @method static void resetFakes()
 * @method static list<mixed> run(string $surql, array<string, mixed> $bindings = [], ?string $connection = null)
 * @method static list<mixed> query(\SurrealDB\SDK\Query\BoundQuery $query, ?string $connection = null)
 *
 * @see SurrealDBManager
 */
final class SurrealDB extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SurrealDBManager::class;
    }
}
