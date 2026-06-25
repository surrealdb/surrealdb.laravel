<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Tests\Unit;

use SurrealDB\Laravel\Facades\SurrealDB;
use SurrealDB\Laravel\Tests\Fakes\CapturingExecutor;
use SurrealDB\Laravel\Tests\TestCase;
use SurrealDB\SDK\Contracts\QueryExecutor;

final class SchemaApplyCommandTest extends TestCase
{
    public function test_dump_outputs_schema_definitions_without_executing_queries(): void
    {
        $executor = new CapturingExecutor();
        $this->application()->instance(QueryExecutor::class, $executor);

        $this->artisanCommand('surqlize:schema-apply', ['--dump' => true])
            ->expectsOutput('DEFINE TABLE article SCHEMAFULL;')
            ->expectsOutput('DEFINE FIELD title ON article TYPE string;')
            ->assertSuccessful();

        $this->assertSame([], $executor->queries);
    }

    public function test_apply_executes_schema_definitions_with_configured_executor(): void
    {
        $executor = new CapturingExecutor();
        $this->application()->instance(QueryExecutor::class, $executor);

        $this->artisanCommand('surqlize:schema-apply')
            ->expectsOutput('Applied Surqlize schema definitions for 1 model.')
            ->assertSuccessful();

        $this->assertCount(2, $executor->queries);
        $this->assertSame('DEFINE TABLE article SCHEMAFULL;', $executor->queries[0]->query);
        $this->assertSame('DEFINE FIELD title ON article TYPE string;', $executor->queries[1]->query);
    }

    public function test_dry_run_outputs_schema_definitions_without_executing_queries(): void
    {
        $executor = new CapturingExecutor();
        $this->application()->instance(QueryExecutor::class, $executor);

        $this->artisanCommand('surqlize:schema-apply', ['--dry-run' => true])
            ->expectsOutput('DEFINE TABLE article SCHEMAFULL;')
            ->expectsOutput('DEFINE FIELD title ON article TYPE string;')
            ->assertSuccessful();

        $this->assertSame([], $executor->queries);
    }

    public function test_schema_dump_outputs_schema_definitions_without_executing_queries(): void
    {
        $executor = new CapturingExecutor();
        $this->application()->instance(QueryExecutor::class, $executor);

        $this->artisanCommand('surqlize:schema-dump')
            ->expectsOutput('DEFINE TABLE article SCHEMAFULL;')
            ->expectsOutput('DEFINE FIELD title ON article TYPE string;')
            ->assertSuccessful();

        $this->assertSame([], $executor->queries);
    }

    public function test_apply_can_execute_schema_definitions_against_named_connection(): void
    {
        $executor = SurrealDB::fake('tenant');

        $this->artisanCommand('surqlize:schema-apply', ['--connection' => 'tenant'])
            ->expectsOutput('Applied Surqlize schema definitions for 1 model.')
            ->assertSuccessful();

        $this->assertCount(2, $executor->queries);
        $this->assertSame('DEFINE TABLE article SCHEMAFULL;', $executor->queries[0]->query);
        $this->assertSame('DEFINE FIELD title ON article TYPE string;', $executor->queries[1]->query);
    }

    public function test_empty_model_config_is_a_successful_noop(): void
    {
        config()->set('surqlize.models', []);

        $this->artisanCommand('surqlize:schema-apply')
            ->expectsOutput('No Surqlize models configured. Add model classes to config("surqlize.models").')
            ->assertSuccessful();
    }
}
