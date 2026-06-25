<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default SurrealDB Connection
    |--------------------------------------------------------------------------
    */
    'default' => env('SURREALDB_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | SurrealDB Connections
    |--------------------------------------------------------------------------
    |
    | Use an HTTP URL for stateless request/response workloads, or a WebSocket
    | URL when your application needs SurrealDB features such as live queries.
    |
    */
    'connections' => [
        'default' => [
            'url' => env('SURREALDB_URL', 'ws://127.0.0.1:8000/rpc'),
            'namespace' => env('SURREALDB_NAMESPACE', 'test'),
            'database' => env('SURREALDB_DATABASE', 'test'),
            'auto_connect' => env('SURREALDB_AUTO_CONNECT', true),

            /*
            |--------------------------------------------------------------------------
            | Connection Lifecycle
            |--------------------------------------------------------------------------
            */
            'lifecycle' => [
                'connect_on_resolve' => env('SURREALDB_CONNECT_ON_RESOLVE', env('SURREALDB_AUTO_CONNECT', true)),
                'disconnect_on_terminate' => env('SURREALDB_DISCONNECT_ON_TERMINATE', true),
                'health_check_on_resolve' => env('SURREALDB_HEALTH_CHECK_ON_RESOLVE', false),
            ],

            /*
            |--------------------------------------------------------------------------
            | Authentication
            |--------------------------------------------------------------------------
            |
            | Supported modes: null, root, namespace, database, record, bearer, token.
            | The username/password keys are kept as a simple root-auth shortcut.
            |
            */
            'username' => env('SURREALDB_USERNAME'),
            'password' => env('SURREALDB_PASSWORD'),
            'auth' => [
                'mode' => env('SURREALDB_AUTH_MODE'),
                'username' => env('SURREALDB_USERNAME'),
                'password' => env('SURREALDB_PASSWORD'),
                'namespace' => env('SURREALDB_AUTH_NAMESPACE', env('SURREALDB_NAMESPACE', 'test')),
                'database' => env('SURREALDB_AUTH_DATABASE', env('SURREALDB_DATABASE', 'test')),
                'access' => env('SURREALDB_AUTH_ACCESS'),
                'key' => env('SURREALDB_AUTH_KEY'),
                'token' => env('SURREALDB_AUTH_TOKEN'),
                'variables' => [],
            ],

            /*
            |--------------------------------------------------------------------------
            | SDK Driver Options
            |--------------------------------------------------------------------------
            */
            'driver' => [
                'format' => env('SURREALDB_FORMAT', 'json'),
                'ping_interval' => (int) env('SURREALDB_PING_INTERVAL', 30),
            ],
        ],
    ],
];
