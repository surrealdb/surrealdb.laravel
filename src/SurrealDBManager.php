<?php

declare(strict_types=1);

namespace SurrealDB\Laravel;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SurrealDB\Laravel\Factory\SurrealFactory;
use SurrealDB\Laravel\Testing\FakeQueryExecutor;
use SurrealDB\SDK\Connection\ConnectionController;
use SurrealDB\SDK\Contracts\QueryExecutor;
use SurrealDB\SDK\Query\BoundQuery;
use SurrealDB\SDK\Surreal;

final class SurrealDBManager implements QueryExecutor
{
    private const DEFAULT_CONNECTION = 'default';

    private const FLAT_CONNECTION_KEYS = [
        'url',
        'namespace',
        'database',
        'auto_connect',
        'lifecycle',
        'username',
        'password',
        'auth',
        'driver',
    ];

    /** @var array<string, Surreal> */
    private array $clients = [];

    /** @var array<string, FakeQueryExecutor> */
    private array $fakes = [];

    public function __construct(
        private readonly Container $container,
        private readonly ConfigRepository $config,
        private readonly SurrealFactory $factory,
    ) {}

    public function client(?string $name = null): Surreal
    {
        $name = $this->connectionName($name);

        return $this->clients[$name] ??= $this->factory->create(
            $this->connectionSettings($name),
            $this->logger(),
        );
    }

    public function connection(?string $name = null): ConnectionController
    {
        return $this->client($name)->connection();
    }

    public function connect(?string $name = null): Surreal
    {
        $name = $this->connectionName($name);
        $client = $this->client($name);

        if ($client->isConnected()) {
            return $client;
        }

        $settings = $this->connectionSettings($name);
        $url = $settings['url'] ?? null;

        if (! is_string($url) || $url === '') {
            throw new RuntimeException(sprintf('SurrealDB connection [%s] config is missing a URL.', $name));
        }

        $client->connect($url, $this->factory->connectOptions($settings));

        if ($this->bool($this->lifecycle($name)['health_check_on_resolve'] ?? false)) {
            $client->health();
        }

        return $client;
    }

    public function disconnect(?string $name = null): void
    {
        if ($name === null) {
            foreach ($this->clients as $client) {
                $client->close();
            }

            return;
        }

        $name = $this->connectionName($name);

        if (isset($this->clients[$name])) {
            $this->clients[$name]->close();
        }
    }

    public function reconnect(?string $name = null): Surreal
    {
        $name = $this->connectionName($name);
        $this->disconnect($name);

        return $this->connect($name);
    }

    public function isConnected(?string $name = null): bool
    {
        $name = $this->connectionName($name);

        return isset($this->clients[$name]) && $this->clients[$name]->isConnected();
    }

    public function health(?string $name = null): void
    {
        $this->connect($name)->health();
    }

    /**
     * @param array<string, mixed> $bindings
     *
     * @return list<mixed>
     */
    public function run(string $surql, array $bindings = [], ?string $connection = null): array
    {
        $name = $this->connectionName($connection);

        if (isset($this->fakes[$name])) {
            return $this->fakes[$name]->query(new BoundQuery($surql, $bindings));
        }

        return $this->connect($name)->run($surql, $bindings);
    }

    /**
     * @return list<mixed>
     */
    public function query(BoundQuery $query, ?string $connection = null): array
    {
        $name = $this->connectionName($connection);

        if (isset($this->fakes[$name])) {
            return $this->fakes[$name]->query($query);
        }

        return $this->connect($name)->query($query);
    }

    public function version(?string $name = null): string
    {
        return $this->connect($name)->version();
    }

    public function using(string $connection): QueryExecutor
    {
        return new ConnectionQueryExecutor($this, $this->connectionName($connection));
    }

    public function fake(?string $connection = null, ?FakeQueryExecutor $fake = null): FakeQueryExecutor
    {
        $connection = $this->connectionName($connection);
        $fake ??= new FakeQueryExecutor();
        $this->fakes[$connection] = $fake;

        return $fake;
    }

    public function assertSurrealQuerySent(string $expected, ?string $connection = null): void
    {
        $connection = $this->connectionName($connection);
        $fake = $this->fakes[$connection] ?? null;

        if ($fake === null) {
            (new FakeQueryExecutor())->assertQuerySent($expected);

            return;
        }

        $fake->assertQuerySent($expected);
    }

    public function resetFakes(): void
    {
        $this->fakes = [];
    }

    public function disconnectResolvedClientsOnTerminate(): void
    {
        foreach (array_keys($this->clients) as $connection) {
            if ($this->bool($this->lifecycle($connection)['disconnect_on_terminate'] ?? true)) {
                $this->disconnect($connection);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $settings = $this->config->get('surrealdb', []);

        if (! is_array($settings)) {
            throw new RuntimeException('SurrealDB config must be an array.');
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    private function connectionSettings(string $name): array
    {
        $settings = $this->settings();
        $connections = $settings['connections'] ?? null;
        $flat = $this->flatConnectionSettings($settings);

        if (is_array($connections)) {
            $selected = $connections[$name] ?? null;

            if (! is_array($selected)) {
                if ($name === $this->connectionName(null) && $flat !== []) {
                    return $flat;
                }

                throw new RuntimeException(sprintf('SurrealDB connection [%s] is not configured.', $name));
            }

            if ($name === $this->connectionName(null) && $flat !== []) {
                return array_replace_recursive($selected, $flat);
            }

            return $selected;
        }

        if ($name !== $this->connectionName(null)) {
            throw new RuntimeException(sprintf('SurrealDB connection [%s] is not configured.', $name));
        }

        return $flat !== [] ? $flat : $settings;
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array<string, mixed>
     */
    private function flatConnectionSettings(array $settings): array
    {
        $flat = [];

        foreach (self::FLAT_CONNECTION_KEYS as $key) {
            if (array_key_exists($key, $settings)) {
                $flat[$key] = $settings[$key];
            }
        }

        return $flat;
    }

    /**
     * @return array<string, mixed>
     */
    private function lifecycle(string $name): array
    {
        $lifecycle = $this->connectionSettings($name)['lifecycle'] ?? [];

        return is_array($lifecycle) ? $lifecycle : [];
    }

    private function connectionName(?string $name): string
    {
        if (is_string($name) && $name !== '') {
            return $name;
        }

        $default = $this->settings()['default'] ?? self::DEFAULT_CONNECTION;

        return is_string($default) && $default !== '' ? $default : self::DEFAULT_CONNECTION;
    }

    private function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function logger(): ?LoggerInterface
    {
        if (! $this->container->bound(LoggerInterface::class)) {
            return null;
        }

        return $this->container->make(LoggerInterface::class);
    }
}
