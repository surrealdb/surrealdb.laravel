<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use SurrealDB\Laravel\Factory\SurrealFactory;
use SurrealDB\SDK\Auth\BearerAuth;
use SurrealDB\SDK\Auth\DatabaseAuth;
use SurrealDB\SDK\Auth\NamespaceAuth;
use SurrealDB\SDK\Auth\RecordAccessAuth;
use SurrealDB\SDK\Auth\RootAuth;
use SurrealDB\SDK\Surreal;

final class SurrealFactoryTest extends TestCase
{
    public function test_it_creates_disconnected_client_when_auto_connect_is_false(): void
    {
        $client = (new SurrealFactory())->create([
            'url' => 'ws://127.0.0.1:8000/rpc',
            'auto_connect' => false,
            'driver' => ['format' => 'json'],
        ]);

        $this->assertInstanceOf(Surreal::class, $client);
        $this->assertFalse($client->isConnected());
    }

    public function test_it_builds_root_auth_from_shortcut_credentials(): void
    {
        $options = (new SurrealFactory())->connectOptions([
            'namespace' => 'app',
            'database' => 'app',
            'username' => 'root',
            'password' => 'secret',
        ]);

        $this->assertInstanceOf(RootAuth::class, $options->authentication);
        $this->assertSame(['user' => 'root', 'pass' => 'secret'], $options->authentication->toArray());
    }

    public function test_it_builds_scoped_auth_modes(): void
    {
        $factory = new SurrealFactory();

        $namespace = $factory->connectOptions([
            'auth' => ['mode' => 'namespace', 'namespace' => 'app', 'username' => 'user', 'password' => 'secret'],
        ]);
        $database = $factory->connectOptions([
            'auth' => ['mode' => 'database', 'namespace' => 'app', 'database' => 'main', 'username' => 'user', 'password' => 'secret'],
        ]);
        $record = $factory->connectOptions([
            'auth' => ['mode' => 'record', 'namespace' => 'app', 'database' => 'main', 'access' => 'account', 'variables' => ['email' => 'a@example.com']],
        ]);
        $bearer = $factory->connectOptions([
            'auth' => ['mode' => 'bearer', 'namespace' => 'app', 'database' => 'main', 'access' => 'service', 'key' => 'key'],
        ]);

        $this->assertInstanceOf(NamespaceAuth::class, $namespace->authentication);
        $this->assertInstanceOf(DatabaseAuth::class, $database->authentication);
        $this->assertInstanceOf(RecordAccessAuth::class, $record->authentication);
        $this->assertInstanceOf(BearerAuth::class, $bearer->authentication);
        $this->assertSame(['ns' => 'app', 'db' => 'main', 'ac' => 'account', 'email' => 'a@example.com'], $record->authentication->toArray());
    }

    public function test_it_rejects_unknown_driver_format(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported SurrealDB codec format "xml".');

        (new SurrealFactory())->create([
            'auto_connect' => false,
            'driver' => ['format' => 'xml'],
        ]);
    }
}
