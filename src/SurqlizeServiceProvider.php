<?php

declare(strict_types=1);

namespace SurrealDB\Laravel;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\ServiceProvider;
use LogicException;
use SurrealDB\Laravel\Console\SchemaApplyCommand;
use SurrealDB\Laravel\Console\SchemaDumpCommand;
use SurrealDB\SDK\Contracts\QueryExecutor;
use Surqlize\Connection\ConnectionManager;
use Surqlize\Model\Model;
use Surqlize\Model\SchemaManager;

final class SurqlizeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/surqlize.php', 'surqlize');

        $this->app->singleton('surqlize.executor', fn ($app): QueryExecutor => new DeferredQueryExecutor($app, $this->executorAbstract()));
        $this->app->singleton(QueryExecutor::class, function ($app): QueryExecutor {
            $executor = $app->make('surqlize.executor');

            if (! $executor instanceof QueryExecutor) {
                throw new LogicException('The surqlize.executor binding must resolve to a QueryExecutor.');
            }

            return $executor;
        });
        $this->app->singleton(SchemaManager::class);

        $this->app->singleton(SurqlizeManager::class, function ($app): SurqlizeManager {
            /** @var ConfigRepository $config */
            $config = $app->make('config');
            $settings = $config->get('surqlize.models', []);

            /** @var list<class-string<Model>> $models */
            $models = is_array($settings) && array_is_list($settings) ? $settings : [];

            return new SurqlizeManager(
                $app->make(QueryExecutor::class),
                $app->make(SurrealDBManager::class),
                $app->make(SchemaManager::class),
                $models,
            );
        });

        $this->app->alias(SurqlizeManager::class, 'surqlize');
    }

    public function boot(): void
    {
        ConnectionManager::set($this->app->make(QueryExecutor::class));

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/surqlize.php' => config_path('surqlize.php'),
            ], 'surqlize-config');

            $this->commands([
                SchemaApplyCommand::class,
                SchemaDumpCommand::class,
            ]);
        }
    }

    private function executorAbstract(): string
    {
        /** @var ConfigRepository $config */
        $config = $this->app->make('config');
        $executor = $config->get('surqlize.executor', 'surrealdb');

        return is_string($executor) && $executor !== '' ? $executor : 'surrealdb';
    }
}
