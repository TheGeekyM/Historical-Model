<?php

namespace Geeky\Historical\Console\Commands;

use Geeky\Historical\Services\Generator;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
    protected $signature = 'historical-model:make {--m|migrate : Migrate your migration file} {--s|sync : Sync your data to the historical table}';

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

        $this->createMigrationFile();

        if ($this->option('sync')) {
            $this->runSyncDataCommand($baseModel);
        }

        $this->endOutput($baseModel);
    }

    /**
     * @throws FileNotFoundException
     */
    private function createModelFile()
    {
        $stub = $this->files->get(__DIR__.'/../../stubs/model.stub');
        $stub = $this->replaceModelClassName($stub);
        $stub = $this->replaceModelTableName($stub);
        $stub = $this->AddFillableColumns($stub);
        $fileNamePath = base_path('app/').$this->table.'History'.'.php';

        if ($this->files->exists($fileNamePath)) {
            $this->warn('The history model is already existed before !!');

            return;
        }

        $this->files->put($fileNamePath, $stub);
        $this->info('Model published successfully.');

        return;
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
            return ($column === $this->primaryKey) ? Str::singular($this->table).'_'.$column : $column;
        })->implode("', '");

        return str_replace('{{columns}}', "'".$columns."'", $stub);
    }

    private function addColumnsStructure(): void
    {
        $this->mapUnknownColumnsType();

        $columns = array_merge($this->AddColumnsWithTypesToMigrationSchema(), $this->addDefaultColumnsToMigrationSchema());
        $columns = collect($columns)->implode(', ');

        $this->call('historical-model:migrate', [
            'name' => "create_{$this->table}_history_table",
            '--schema' => $columns,
            '--model' => 0,
            '--migrate' => $this->option('migrate') || $this->option('sync'),
        ]);
    }

    private function AddColumnsWithTypesToMigrationSchema(): array
    {
        $columns = [];

        foreach ($this->columns as $column) {
            $column = Schema::getConnection()->getDoctrineColumn($this->table, $column);
            $column = new Generator($this->table, $column, $this->primaryKey);
            $columns[] = (string) $column;
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
        if (class_exists($model)) {
            return new $model();
        }

        $this->error("The model ({$model}) you specified doesn't exist");
        exit();
    }

    public function runSyncDataCommand()
    {
        $this->call('historical-model:sync', [
        ]);
    }

    public function mapUnknownColumnsType(): void
    {
        $platform = DB::getDoctrineSchemaManager()->getDatabasePlatform();
        $platform->registerDoctrineTypeMapping('geography', 'text');
    }

    private function endOutput($baseModel): void
    {
        $this->info("\n|-------------------------------------------------- Important ----------------------------------------------------------------|");
        $this->info("| Use this trait `use App\Geeky\Concerns\Historical` in your base model `$baseModel`");
        $this->info('|-------------------------------------------------- Important ----------------------------------------------------------------|');

        $this->info("\n Done :D");
    }
}
