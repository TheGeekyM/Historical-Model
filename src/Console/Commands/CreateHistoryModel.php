<?php

namespace Geeky\Historical\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Schema;

class CreateHistoryModel extends Command
{
    /**
     * @var Filesystem
     */
    private $files;

    /**
     * @var Collection
     */
    private $columns;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:history-model';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a history for any table you want';

    /**
     * @var
     */
    private $table;

    /**
     * @var
     */
    private $primaryKey;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $baseModel = $this->ask('What table you want to make historical? eg: App\Models\User');

        $model = $this->getModelObjectOrFail($baseModel);

        $this->table = $model->getTable();

        $this->primaryKey = $model->getKeyName();

        $columns = Schema::getColumnListing($this->table);

        $this->columns = collect($columns)->filter(function ($column) {
            if ($column === $this->primaryKey) {
                return $this->confirm('Do you want to make history of: ('.$column.') , "This column is your primary key I recommend to enter yes"', 'yes');
            }

            return $this->confirm('Do you want to make history of: ('.$column.')', 'yes');
        });

        $this->createModelFile();

        $this->info('Model published successfully.');

        $this->createMigrationFile();

        $this->endOutput($baseModel);
    }

    /**
     * @throws FileNotFoundException
     */
    private function createModelFile(): void
    {
        $stub = $this->files->get(__DIR__.'/../../stubs/model.stub');
        $stub = $this->replaceModelClassName($stub);
        $stub = $this->replaceModelTableName($stub);
        $stub = $this->AddFillableColumns($stub);

        $this->files->put(base_path('app/').$this->table.'History'.'.php', $stub);
    }

    private function createMigrationFile(): void
    {
        $this->addColumnsStructure();
    }

    /**
     * @param $stub
     *
     * @return string|string[]
     */
    private function replaceModelClassName($stub)
    {
        return str_replace('{{class}}', $this->table.'History', $stub);
    }

    /**
     * @param $stub
     *
     * @return string|string[]
     */
    private function replaceModelTableName($stub)
    {
        return str_replace('{{table}}', $this->table.'_history', $stub);
    }

    /**
     * @param $stub
     *
     * @return string|string[]
     */
    private function AddFillableColumns($stub)
    {
        $columns = $this->columns->merge(['status_control', 'start_datetime', 'end_datetime', 'created_by_id']);

        $columns = collect($columns)->transform(function ($column) {
            return ($column === $this->primaryKey) ? $this->table.'_'.$column : $column;
        })->implode("', '");

        return str_replace('{{columns}}', "'".$columns."'", $stub);
    }

    private function addColumnsStructure(): void
    {
        $columns = array_merge($this->AddColumnsWithTypesToMigrationSchema(), $this->addDefaultColumnsToMigrationSchema());

        $columns = collect($columns)->implode(', ');

        $this->call('make:migration:schema', [
            'name' => "create_{$this->table}_history_table",
            '--schema' => $columns,
        ]);
    }

    /**
     * @param $table
     * @param $columns
     */
    private function getPrimaryKeysColumn($table, $columns): void
    {
        $indexes = Schema::getConnection()->getDoctrineSchemaManager()->listTableIndexes($table);
        $keys = $indexes['primary']->getUnquotedColumns();

        foreach ($keys as $key) {
            $column = Schema::getConnection()->getDoctrineColumn($table, $key);
            $columnName = $column->getName();

            if (false === $columns->search($columnName)) {
                continue;
            }

            $this->columns[] = $table.'_'.$columnName.':'.($column->getUnsigned() ? 'unsigned' : '').ucfirst($column->getType()->getName()).':foreign'.''.($column->getNotnull() ? ':nullable' : '');
        }
    }

    private function AddColumnsWithTypesToMigrationSchema(): array
    {
        $columns = [];

        foreach ($this->columns as $column) {
            $column = Schema::getConnection()->getDoctrineColumn($this->table, $column);
            $columnName = $column->getName();

            if ($columnName === $this->primaryKey) {
                $columns[] = $this->table.'_'.$columnName.':'.($column->getUnsigned() ? 'unsigned' : '').ucfirst($column->getType()->getName()).':foreign'.''.($column->getNotnull() ? ':nullable' : '');
            } else {
                $columns[] = $columnName.':'.($column->getUnsigned() ? 'unsigned' : '').ucfirst($column->getType()->getName()).''.($column->getNotnull() ? ':nullable' : '');
            }
        }

        return $columns;
    }

    /**
     * @return array|string[]
     */
    private function addDefaultColumnsToMigrationSchema(): array
    {
        return [
            "status_control:enum(['a','c'])",
            'start_datetime:dateTime:index',
            'end_datetime:dateTime:nullable:index',
            'created_by_id:unsignedInteger:nullable:index',
        ];
    }

    /**
     * @param $model
     *
     * @return mixed
     */
    private function getModelObjectOrFail($model)
    {
        if (!class_exists($model)) {
            $this->error('The model you specified does\'t exist');
            exit();
        }

        return new $model();
    }

    private function endOutput($baseModel): void
    {
        $this->info("\n|-------------------------------------------------- Important ----------------------------------------------------------------|");
        $this->info("| Use this trait `use App\Geeky\Concerns\Historical` in your base model `$baseModel`");
        $this->warn('| In your migration file just remove this line `$table->timestamps()` we will do it automatically in the next release sorry :(|');
        $this->info('|-------------------------------------------------- Important ----------------------------------------------------------------|');

        $this->info("\n Done :D");
    }
}
