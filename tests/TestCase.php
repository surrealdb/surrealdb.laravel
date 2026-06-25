<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Testing\PendingCommand;
use LogicException;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use SurrealDB\Laravel\SurrealDBManager;
use SurrealDB\Laravel\SurrealDBServiceProvider;
use SurrealDB\Laravel\SurqlizeServiceProvider;
use SurrealDB\Laravel\Tests\Fixtures\Article;
use Surqlize\Connection\ConnectionManager;
use Surqlize\Model\ModelMetadata;
use Surqlize\Query\Fields\FieldSetRegistry;

abstract class TestCase extends OrchestraTestCase
{
    protected function tearDown(): void
    {
        if ($this->app instanceof Application && $this->app->bound(SurrealDBManager::class)) {
            $this->app->make(SurrealDBManager::class)->resetFakes();
        }

        ConnectionManager::reset();
        ModelMetadata::clear();
        FieldSetRegistry::clear();

        parent::tearDown();
    }

    /**
     * @param mixed $app
     *
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            SurrealDBServiceProvider::class,
            SurqlizeServiceProvider::class,
        ];
    }

    /**
     * @param mixed $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('surrealdb.connections.default.auto_connect', false);
        $app['config']->set('surrealdb.connections.default.lifecycle.connect_on_resolve', false);
        $app['config']->set('surrealdb.connections.default.lifecycle.health_check_on_resolve', false);
        $app['config']->set('surqlize.models', [
            Article::class,
        ]);
    }

    protected function application(): Application
    {
        if (! $this->app instanceof Application) {
            throw new LogicException('The Laravel test application has not been booted.');
        }

        return $this->app;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    protected function artisanCommand(string $command, array $parameters = []): PendingCommand
    {
        $pending = $this->artisan($command, $parameters);

        if (! $pending instanceof PendingCommand) {
            throw new LogicException(sprintf('Artisan command "%s" returned an exit code before assertions were registered.', $command));
        }

        return $pending;
    }
}
