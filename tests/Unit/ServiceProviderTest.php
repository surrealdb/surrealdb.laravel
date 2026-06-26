<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Tests\Unit;

use SurrealDB\Laravel\DeferredQueryExecutor;
use SurrealDB\Laravel\Facades\SurrealDB;
use SurrealDB\Laravel\Facades\Surqlize;
use SurrealDB\Laravel\SurrealDBManager;
use SurrealDB\Laravel\SurqlizeManager;
use SurrealDB\Laravel\Tests\Fixtures\Article;
use SurrealDB\Laravel\Tests\TestCase;
use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use SurrealDB\SDK\Surreal;
use Surqlize\Connection\ConnectionManager;
use Surqlize\Model\SchemaManager;

final class ServiceProviderTest extends TestCase
{
    public function test_it_merges_configuration_and_registers_core_bindings(): void
    {
        $this->assertSame('ws://127.0.0.1:8000/rpc', config('surrealdb.connections.default.url'));
        $this->assertSame('surrealdb.connection', config('surqlize.executor'));
        $this->assertInstanceOf(Surreal::class, $this->application()->make(Surreal::class));
        $this->assertInstanceOf(SurrealDBManager::class, $this->application()->make(SurrealDBManager::class));
        $this->assertInstanceOf(DeferredQueryExecutor::class, $this->application()->make(QueryExecutor::class));
        $this->assertInstanceOf(SchemaManager::class, $this->application()->make(SchemaManager::class));
        $this->assertSame($this->application()->make(Surreal::class), $this->application()->make('surrealdb'));
        $this->assertSame($this->application()->make(SurrealDBManager::class), $this->application()->make('surrealdb.manager'));
        $this->assertSame($this->application()->make(SurrealDBManager::class), $this->application()->make('surrealdb.connection'));
    }

    public function test_it_configures_surqlize_connection_manager_with_bound_executor(): void
    {
        $executor = $this->application()->make(QueryExecutor::class);

        $this->assertSame($executor, ConnectionManager::get());
    }

    public function test_surreal_client_is_not_connected_when_auto_connect_is_disabled(): void
    {
        $client = $this->application()->make(Surreal::class);

        $this->assertFalse($client->isConnected());
    }

    public function test_surrealdb_manager_uses_the_same_raw_client_singleton(): void
    {
        $manager = $this->application()->make(SurrealDBManager::class);
        $client = $this->application()->make(Surreal::class);

        $this->assertSame($client, $manager->client());
        $this->assertSame($manager->isConnected(), SurrealDB::isConnected());
    }

    public function test_surqlize_executor_bridge_resolves_the_configured_laravel_executor(): void
    {
        $executor = SurrealDB::fake();

        ConnectionManager::get()->query(new BoundQuery('RETURN true;'));

        $executor->assertQuerySent('RETURN true;');
    }

    public function test_surqlize_model_query_without_explicit_executor_uses_connection_manager_bridge(): void
    {
        $executor = SurrealDB::fake();

        Article::query()->collect();

        $this->assertCount(1, $executor->queries);
        $this->assertSame('SELECT * FROM article', $executor->queries[0]->query);
    }

    public function test_surqlize_manager_schema_apply_uses_connection_manager_bridge(): void
    {
        $executor = SurrealDB::fake();

        $this->application()->make(SurqlizeManager::class)->applySchema([Article::class]);

        $this->assertCount(2, $executor->queries);
        $this->assertSame('DEFINE TABLE article SCHEMAFULL;', $executor->queries[0]->query);
        $this->assertSame('DEFINE FIELD title ON article TYPE string;', $executor->queries[1]->query);
    }

    public function test_manager_and_facade_expose_schema_definitions(): void
    {
        $manager = $this->application()->make(SurqlizeManager::class);

        $this->assertSame(
            ['DEFINE TABLE article SCHEMAFULL;', 'DEFINE FIELD title ON article TYPE string;'],
            $manager->schemaDefinitions(),
        );

        $this->assertSame($manager->schemaDefinitions(), Surqlize::schemaDefinitions());
    }

    public function test_manager_routes_queries_to_named_connection_fakes(): void
    {
        $default = SurrealDB::fake();
        $tenant = SurrealDB::fake('tenant');

        SurrealDB::run('RETURN $message;', ['message' => 'hello'], connection: 'tenant');

        $default->assertNothingSent();
        $tenant->assertQuerySent('RETURN $message;');
        SurrealDB::assertSurrealQuerySent('RETURN $message;', connection: 'tenant');
    }

    public function test_manager_accepts_legacy_flat_configuration_shape(): void
    {
        config()->set('surrealdb', [
            'url' => 'ws://127.0.0.1:8000/rpc',
            'auto_connect' => false,
            'driver' => ['format' => 'json'],
        ]);

        $manager = $this->application()->make(SurrealDBManager::class);

        $this->assertInstanceOf(Surreal::class, $manager->client());
        $this->assertFalse($manager->isConnected());
    }

    public function test_manager_uses_named_connection_configuration(): void
    {
        config()->set('surrealdb.connections.analytics', [
            'url' => 'ws://127.0.0.1:8000/rpc',
            'auto_connect' => false,
            'driver' => ['format' => 'xml'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported SurrealDB codec format "xml".');

        $this->application()->make(SurrealDBManager::class)->client('analytics');
    }

    public function test_disconnect_without_connection_name_disconnects_all_resolved_clients(): void
    {
        config()->set('surrealdb.connections.tenant', [
            'url' => 'ws://127.0.0.1:8000/rpc',
            'auto_connect' => false,
            'driver' => ['format' => 'json'],
        ]);

        $manager = $this->application()->make(SurrealDBManager::class);
        $manager->client();
        $manager->client('tenant');

        $manager->disconnect();

        $this->assertFalse($manager->isConnected());
        $this->assertFalse($manager->isConnected('tenant'));
    }

    public function test_surqlize_transaction_can_use_named_connection_executor(): void
    {
        $default = SurrealDB::fake();
        $tenant = SurrealDB::fake('tenant');

        Surqlize::transaction(
            function (QueryExecutor $transaction): void {
                $transaction->query(new BoundQuery('RETURN true'));
            },
            connection: 'tenant',
        );

        $default->assertNothingSent();
        $tenant->assertQuerySent('BEGIN TRANSACTION; RETURN true; COMMIT TRANSACTION;');
    }
}
