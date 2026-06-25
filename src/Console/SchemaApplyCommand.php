<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use SurrealDB\Laravel\SurrealDBManager;
use SurrealDB\SDK\Contracts\QueryExecutor;
use Surqlize\Model\Model;
use Surqlize\Model\SchemaManager;

final class SchemaApplyCommand extends Command
{
    protected $signature = 'surqlize:schema-apply
        {--dump : Print schema DDL instead of executing it}
        {--dry-run : Print schema DDL instead of executing it}
        {--connection= : The SurrealDB connection name to use}';

    protected $description = 'Apply Surqlize schema definitions to SurrealDB.';

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly SchemaManager $schemas,
        private readonly QueryExecutor $executor,
        private readonly SurrealDBManager $connections,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $models = $this->models();

        if ($models === []) {
            $this->warn('No Surqlize models configured. Add model classes to config("surqlize.models").');

            return self::SUCCESS;
        }

        if ($this->option('dump') || $this->option('dry-run')) {
            foreach ($this->schemas->definitions($models) as $definition) {
                $this->line($definition);
            }

            return self::SUCCESS;
        }

        $this->schemas->apply($models, $this->executor());
        $this->info(sprintf('Applied Surqlize schema definitions for %d model%s.', count($models), count($models) === 1 ? '' : 's'));

        return self::SUCCESS;
    }

    /**
     * @return list<class-string<Model>>
     */
    private function models(): array
    {
        $models = $this->config->get('surqlize.models', []);

        if (! is_array($models) || ! array_is_list($models)) {
            $this->fail('The surqlize.models config value must be a list of model class names.');
        }

        foreach ($models as $model) {
            if (! is_string($model) || ! is_subclass_of($model, Model::class)) {
                $this->fail(sprintf('Configured Surqlize model "%s" must extend %s.', is_scalar($model) ? (string) $model : get_debug_type($model), Model::class));
            }
        }

        /** @var list<class-string<Model>> $models */
        return $models;
    }

    private function executor(): QueryExecutor
    {
        $connection = $this->option('connection');

        if (is_string($connection) && $connection !== '') {
            return $this->connections->using($connection);
        }

        return $this->executor;
    }
}
