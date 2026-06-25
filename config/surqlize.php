<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Surqlize Query Executor
    |--------------------------------------------------------------------------
    |
    | Surqlize executes compiled ORM queries through the SDK QueryExecutor
    | contract. The default points at the SurrealDB SDK container alias, but
    | tests or multi-connection applications may bind another executor.
    |
    */
    'executor' => env('SURQLIZE_EXECUTOR', 'surrealdb.connection'),

    /*
    |--------------------------------------------------------------------------
    | ORM Models
    |--------------------------------------------------------------------------
    */
    'models' => [
        // App\Models\User::class,
    ],
];
