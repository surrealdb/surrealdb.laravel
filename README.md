# SurrealDB Laravel

Laravel integration for the official SurrealDB PHP SDK and the Surqlize ORM.

This package gives Laravel applications a publishable configuration file, service-container bindings, facades, an Artisan schema command, and testing helpers. Query execution and database protocol behavior are delegated to `surrealdb/surrealdb.php`; ORM models, query compilation, graph relations, and schema definitions are delegated to `surqlize/surqlize`.

## Requirements

| Requirement | Version |
| --- | --- |
| PHP | `>=8.4` |
| Laravel components | `^11.0 || ^12.0 || ^13.0` |
| Surqlize | `surqlize/surqlize` `dev-main` |
| SurrealDB PHP SDK | `surrealdb/surrealdb.php` `2.0.0-alpha.0` |

## Installation

Install the package with Composer:

```bash
composer require surrealdb/laravel
```

While the SDK and ORM are still in active development, local workspace installs can use path repositories:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../surrealdb.laravel",
            "options": { "symlink": true }
        },
        {
            "type": "path",
            "url": "../surqlize.php",
            "options": { "symlink": true }
        },
        {
            "type": "path",
            "url": "../surrealdb.php",
            "options": { "symlink": true },
            "versions": {
                "surrealdb/surrealdb.php": "2.0.0-alpha.0"
            }
        }
    ],
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

Publish the configuration:

```bash
php artisan vendor:publish --tag=surrealdb-config
php artisan vendor:publish --tag=surqlize-config
```

## Configuration

The package intentionally keeps SDK and ORM configuration separate:

- `config/surrealdb.php` configures the raw SurrealDB PHP SDK client.
- `config/surqlize.php` configures Surqlize ORM behavior.

`config/surrealdb.php` reads these common environment variables:

```dotenv
SURREALDB_CONNECTION=default
SURREALDB_URL=ws://127.0.0.1:8000/rpc
SURREALDB_NAMESPACE=test
SURREALDB_DATABASE=test
SURREALDB_USERNAME=root
SURREALDB_PASSWORD=root
SURREALDB_AUTO_CONNECT=true
SURREALDB_CONNECT_ON_RESOLVE=true
SURREALDB_DISCONNECT_ON_TERMINATE=true
SURREALDB_HEALTH_CHECK_ON_RESOLVE=false
```

If `SURREALDB_USERNAME` is set, the package uses SDK `RootAuth` by default. For scoped authentication, set `SURREALDB_AUTH_MODE` to `namespace`, `database`, `record`, `bearer`, `token`, or `none`, then fill the matching keys in the published config.

The SDK config supports named connections:

```php
'default' => env('SURREALDB_CONNECTION', 'default'),

'connections' => [
    'default' => [
        'url' => env('SURREALDB_URL', 'ws://127.0.0.1:8000/rpc'),
        'namespace' => env('SURREALDB_NAMESPACE', 'test'),
        'database' => env('SURREALDB_DATABASE', 'test'),
        // auth, lifecycle, and driver options...
    ],

    'analytics' => [
        'url' => env('SURREALDB_ANALYTICS_URL'),
        'namespace' => env('SURREALDB_ANALYTICS_NAMESPACE'),
        'database' => env('SURREALDB_ANALYTICS_DATABASE'),
        'auto_connect' => false,
    ],
],
```

`config/surqlize.php` contains the ORM model list and executor binding:

```php
'executor' => env('SURQLIZE_EXECUTOR', 'surrealdb.connection'),

'models' => [
    App\Models\User::class,
],
```

## Service Container

Laravel auto-discovers two service providers:

- `SurrealDB\Laravel\SurrealDBServiceProvider` owns SDK configuration, client construction, and the `surrealdb` container alias.
- `SurrealDB\Laravel\SurqlizeServiceProvider` owns ORM configuration, schema commands, Surqlize's executor binding, and `ConnectionManager` setup.

The SDK provider binds:

- `SurrealDB\SDK\Surreal` and the `surrealdb` container alias for the raw SDK client.
- `SurrealDB\Laravel\SurrealDBManager` plus the `surrealdb.manager` and `surrealdb.connection` aliases for Laravel-style lifecycle helpers.

The ORM provider binds:

- `SurrealDB\SDK\Contracts\QueryExecutor` for Surqlize query execution.
- `Surqlize\Model\SchemaManager` for schema definitions and application.
- `SurrealDB\Laravel\SurqlizeManager` and the `surqlize` container alias for Laravel-friendly helpers.

The ORM provider also registers Surqlize's global `ConnectionManager` with a lazy executor that resolves the configured `surqlize.executor` binding only when a query executes. By default, that binding points at `surrealdb.connection`, so Surqlize model queries use the Laravel manager and connect lazily.

## Models

Surqlize models are not Eloquent models. They extend `Surqlize\Model\Model` and use Surqlize attributes:

```php
<?php

namespace App\Models;

use App\Schemas\UserSchema;
use Surqlize\Attributes\Id;
use Surqlize\Attributes\Schema;
use Surqlize\Attributes\Table;
use Surqlize\Model\Model;
use SurrealDB\SDK\Types\RecordId;

#[Table('user')]
#[Schema(UserSchema::class)]
final class User extends Model
{
    /** @var RecordId<'user'> */
    #[Id]
    public RecordId $id;

    public string $name;
}
```

Register models that should participate in schema commands:

```php
// config/surqlize.php
'models' => [
    App\Models\User::class,
],
```

## Schemas

Schemas implement `Surqlize\Model\SchemaContract`:

```php
<?php

namespace App\Schemas;

use Surqlize\Model\SchemaContract;

final class UserSchema implements SchemaContract
{
    public function definitions(): array
    {
        return [
            'DEFINE TABLE user SCHEMAFULL;',
            'DEFINE FIELD name ON user TYPE string;',
        ];
    }

    public function rules(): array
    {
        return [];
    }
}
```

Preview schema statements:

```bash
php artisan surqlize:schema-dump
php artisan surqlize:schema-apply --dry-run
```

Apply schema statements:

```bash
php artisan surqlize:schema-apply
php artisan surqlize:schema-apply --connection=analytics
```

## Queries

Use Surqlize's model APIs:

```php
$users = User::select(fn ($user) => [$user->id, $user->name])
    ->where(fn ($user) => $user->name->eq('beau'))
    ->collectModels();
```

No manual executor is needed for normal Laravel usage. The package configures Surqlize to use the Laravel-managed SurrealDB executor:

```php
$users = User::query()->collectModels();
```

Use the raw SDK when you need lower-level access:

```php
use SurrealDB\SDK\Surreal;

$result = app(Surreal::class)->run('RETURN $message', [
    'message' => 'hello',
]);
```

Use the Laravel manager facade when you want lifecycle helpers around the same SDK client:

```php
use SurrealDB\Laravel\Facades\SurrealDB;

SurrealDB::health();

$result = SurrealDB::run('RETURN $message', [
    'message' => 'hello',
]);

$analytics = SurrealDB::run(
    'RETURN $message',
    ['message' => 'hello'],
    connection: 'analytics',
);
```

The `Surqlize` facade exposes schema and transaction helpers:

```php
use SurrealDB\Laravel\Facades\Surqlize;

Surqlize::transaction(function ($transaction): void {
    User::createQuery(['name' => 'beau'], executor: $transaction)->execute();
});

Surqlize::transaction(
    fn ($transaction) => User::createQuery(['name' => 'beau'], executor: $transaction)->execute(),
    connection: 'analytics',
);
```

`Surqlize::transaction()` uses Surqlize's executor-based SurrealQL transaction batching. SDK-native transaction IDs are not exposed through the Laravel facade yet because they depend on WebSocket transport and server feature support.

## Testing

Use the package testing trait to reset Surqlize's global caches:

```php
use SurrealDB\Laravel\Testing\RefreshSurqlizeState;

final class UserTest extends TestCase
{
    use RefreshSurqlizeState;

    protected function tearDown(): void
    {
        $this->resetSurqlizeState();

        parent::tearDown();
    }
}
```

For unit tests, pass a fake `QueryExecutor` to Surqlize queries or call `useSurqlizeExecutor()` from the trait.

You can also fake the Laravel-managed executor:

```php
use SurrealDB\Laravel\Facades\SurrealDB;

$fake = SurrealDB::fake();

SurrealDB::run('RETURN true;');

SurrealDB::assertSurrealQuerySent('RETURN true;');
$fake->assertQuerySent('RETURN true;');
```

Named fakes are scoped by connection:

```php
SurrealDB::fake('analytics');

SurrealDB::run('RETURN true;', connection: 'analytics');

SurrealDB::assertSurrealQuerySent('RETURN true;', connection: 'analytics');
```

## Development

```bash
composer validate --no-check-publish
composer audit --locked
composer analyse
composer test
```

## Notes

- The SDK v2 API is still alpha, so expect dependency constraints to tighten as the SDK stabilizes.
- Laravel singleton scope follows the application container: normally once per request under PHP-FPM, and longer-lived under Octane, queue workers, or long-running commands.
- Surqlize currently has a singleton `ConnectionManager`. Use per-query `withExecutor()` or explicit `executor:` arguments when you need multiple executors in the same process.
- Live queries, sessions, and native SDK transactions require WebSocket support and depend on the underlying SurrealDB server version.
