<?php

declare(strict_types=1);

namespace SurrealDB\Laravel;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use SurrealDB\Laravel\Factory\SurrealFactory;
use SurrealDB\SDK\Surreal;

final class SurrealDBServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/surrealdb.php', 'surrealdb');

        $this->app->singleton(SurrealFactory::class);

        $this->app->singleton(SurrealDBManager::class, function ($app): SurrealDBManager {
            /** @var ConfigRepository $config */
            $config = $app->make('config');

            return new SurrealDBManager(
                $app,
                $config,
                $app->make(SurrealFactory::class),
            );
        });

        $this->app->singleton(Surreal::class, fn ($app): Surreal => $app->make(SurrealDBManager::class)->client());

        $this->app->alias(Surreal::class, 'surrealdb');
        $this->app->alias(SurrealDBManager::class, 'surrealdb.manager');
        $this->app->alias(SurrealDBManager::class, 'surrealdb.connection');
    }

    public function boot(): void
    {
        $this->app->terminating(function (): void {
            $this->app->make(SurrealDBManager::class)->disconnectResolvedClientsOnTerminate();
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/surrealdb.php' => config_path('surrealdb.php'),
            ], 'surrealdb-config');
        }
    }
}
