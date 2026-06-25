<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Tests\Unit;

use RuntimeException;
use SurrealDB\Laravel\Testing\RefreshSurqlizeState;
use SurrealDB\Laravel\Tests\Fakes\CapturingExecutor;
use SurrealDB\Laravel\Tests\TestCase;
use Surqlize\Connection\ConnectionManager;

final class RefreshSurqlizeStateTest extends TestCase
{
    use RefreshSurqlizeState;

    public function test_it_sets_and_resets_surqlize_executor_state(): void
    {
        $executor = new CapturingExecutor();

        $this->assertSame($executor, $this->useSurqlizeExecutor($executor));
        $this->assertSame($executor, ConnectionManager::get());

        $this->resetSurqlizeState();

        $this->expectException(RuntimeException::class);
        ConnectionManager::get();
    }
}
