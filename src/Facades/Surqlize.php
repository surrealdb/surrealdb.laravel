<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use SurrealDB\Laravel\SurqlizeManager;

/**
 * @method static \SurrealDB\SDK\Contracts\QueryExecutor executor()
 * @method static list<string> schemaDefinitions(?array<int, class-string<\Surqlize\Model\Model>> $models = null)
 * @method static void applySchema(?array<int, class-string<\Surqlize\Model\Model>> $models = null, ?\SurrealDB\SDK\Contracts\QueryExecutor $executor = null)
 * @method static mixed transaction(\Closure $callback, ?\SurrealDB\SDK\Contracts\QueryExecutor $executor = null, ?string $connection = null)
 *
 * @see SurqlizeManager
 */
final class Surqlize extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SurqlizeManager::class;
    }
}
