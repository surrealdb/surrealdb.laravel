<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Factory;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SurrealDB\SDK\Auth\BearerAuth;
use SurrealDB\SDK\Auth\Credentials;
use SurrealDB\SDK\Auth\DatabaseAuth;
use SurrealDB\SDK\Auth\NamespaceAuth;
use SurrealDB\SDK\Auth\RecordAccessAuth;
use SurrealDB\SDK\Auth\RootAuth;
use SurrealDB\SDK\Connection\ConnectOptions;
use SurrealDB\SDK\Connection\DriverOptions;
use SurrealDB\SDK\Enum\CodecEnum;
use SurrealDB\SDK\Surreal;

final class SurrealFactory
{
    /**
     * @param array<string, mixed> $config
     */
    public function create(array $config, ?LoggerInterface $logger = null): Surreal
    {
        $surreal = new Surreal($this->driverOptions($config, $logger));

        if ($this->connectOnResolve($config) && $this->stringOrNull($config['url'] ?? null) !== null) {
            $surreal->connect(
                $this->string($config['url'] ?? ''),
                $this->connectOptions($config),
            );

            if ($this->healthCheckOnResolve($config)) {
                $surreal->health();
            }
        }

        return $surreal;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function connectOptions(array $config): ConnectOptions
    {
        return new ConnectOptions(
            namespace: $this->stringOrNull($config['namespace'] ?? null),
            database: $this->stringOrNull($config['database'] ?? null),
            authentication: $this->credentials($config),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function driverOptions(array $config, ?LoggerInterface $logger): DriverOptions
    {
        $driver = $this->array($config['driver'] ?? []);

        return new DriverOptions(
            format: $this->codec($driver['format'] ?? 'json'),
            logger: $logger,
            pingInterval: (int) ($driver['ping_interval'] ?? 30),
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function credentials(array $config): Credentials|string|null
    {
        $auth = $this->array($config['auth'] ?? []);
        $mode = $this->stringOrNull($auth['mode'] ?? null);

        if (($mode === null || $mode === 'root')
            && $this->stringOrNull($config['username'] ?? null) !== null) {
            return new RootAuth(
                $this->string($config['username'] ?? ''),
                $this->string($config['password'] ?? ''),
            );
        }

        return match ($mode) {
            null, 'none' => null,
            'root' => new RootAuth(
                $this->string($auth['username'] ?? ''),
                $this->string($auth['password'] ?? ''),
            ),
            'namespace' => new NamespaceAuth(
                $this->string($auth['namespace'] ?? $config['namespace'] ?? ''),
                $this->string($auth['username'] ?? ''),
                $this->string($auth['password'] ?? ''),
            ),
            'database' => new DatabaseAuth(
                $this->string($auth['namespace'] ?? $config['namespace'] ?? ''),
                $this->string($auth['database'] ?? $config['database'] ?? ''),
                $this->string($auth['username'] ?? ''),
                $this->string($auth['password'] ?? ''),
            ),
            'record' => new RecordAccessAuth(
                $this->string($auth['namespace'] ?? $config['namespace'] ?? ''),
                $this->string($auth['database'] ?? $config['database'] ?? ''),
                $this->string($auth['access'] ?? ''),
                $this->array($auth['variables'] ?? []),
            ),
            'bearer' => new BearerAuth(
                $this->string($auth['namespace'] ?? $config['namespace'] ?? ''),
                $this->string($auth['database'] ?? $config['database'] ?? ''),
                $this->string($auth['access'] ?? ''),
                $this->string($auth['key'] ?? ''),
            ),
            'token' => $this->string($auth['token'] ?? ''),
            default => throw new InvalidArgumentException(sprintf('Unsupported SurrealDB auth mode "%s".', $mode)),
        };
    }

    private function codec(mixed $format): CodecEnum
    {
        $format = strtolower($this->string($format));

        return CodecEnum::tryFrom($format)
            ?? throw new InvalidArgumentException(sprintf('Unsupported SurrealDB codec format "%s".', $format));
    }

    /**
     * @return array<string, mixed>
     */
    private function array(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            throw new InvalidArgumentException('Expected SurrealDB configuration value to be an array.');
        }

        return $value;
    }

    private function string(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        if ($value === null) {
            return '';
        }

        throw new InvalidArgumentException('Expected SurrealDB configuration value to be a string.');
    }

    private function stringOrNull(mixed $value): ?string
    {
        $value = $this->string($value);

        return $value === '' ? null : $value;
    }

    private function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function connectOnResolve(array $config): bool
    {
        $lifecycle = $this->array($config['lifecycle'] ?? []);

        return $this->bool($lifecycle['connect_on_resolve'] ?? $config['auto_connect'] ?? true);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function healthCheckOnResolve(array $config): bool
    {
        $lifecycle = $this->array($config['lifecycle'] ?? []);

        return $this->bool($lifecycle['health_check_on_resolve'] ?? false);
    }
}
