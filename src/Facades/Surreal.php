<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SurrealDB\SDK\Surreal as SurrealClient;

/**
 * @method static void connect(string|\SurrealDB\SDK\Connection\Endpoint $url, ?\SurrealDB\SDK\Connection\ConnectOptions $options = null)
 * @method static void close()
 * @method static bool isConnected()
 * @method static list<mixed> run(string $surql, array<string, mixed> $bindings = [])
 * @method static list<mixed> query(\SurrealDB\SDK\Query\BoundQuery $query)
 *
 * @see SurrealClient
 */
final class Surreal extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SurrealClient::class;
    }
}
