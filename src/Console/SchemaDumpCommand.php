<?php

declare(strict_types=1);

namespace SurrealDB\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Surqlize\Model\Model;
use Surqlize\Model\SchemaManager;

final class SchemaDumpCommand extends Command
{
    protected $signature = 'surqlize:schema-dump {--connection= : The SurrealDB connection name the dump is intended for}';

    protected $description = 'Print Surqlize schema definitions without executing them.';

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly SchemaManager $schemas,
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

        foreach ($this->schemas->definitions($models) as $definition) {
            $this->line($definition);
        }

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
}
