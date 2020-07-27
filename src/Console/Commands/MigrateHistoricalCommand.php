<?php

namespace Geeky\Historical\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Laracasts\Generators\Commands\MigrationMakeCommand;
use Laracasts\Generators\Migrations\NameParser;
use Symfony\Component\Console\Input\InputOption;

class MigrateHistoricalCommand extends MigrationMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'historical-model:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new migration class and apply schema at the same time';

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        $this->composer = app()['composer'];
    }

    public function handle(): void
    {
        $this->fire();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->meta = (new NameParser())->parse($this->argument('name'));

        $this->makeMigration();
        $this->makeModel();
    }

    /**
     * Generate the desired migration.
     */
    protected function makeMigration()
    {
        $name = $this->argument('name');

        if ($this->files->exists($path = $this->getPath($name))) {
            return $this->error($this->type.' already exists!');
        }

        $bPath = Str::before($path, 'database');

        $path = Str::after($path, $bPath);

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileMigrationStub());

        $this->info('Migration created successfully.');

        $this->composer->dumpAutoloads();

        $this->runMigrationCommand($path);
    }

    /**
     * @return void
     */
    public function compileMigrationStub()
    {
        return str_replace('$table->timestamps();', '', parent::compileMigrationStub());
    }

    /**
     * @param string $path
     *
     * @return void
     */
    protected function runMigrationCommand($path)
    {
        if ($this->option('migrate')) {
            $this->call('migrate', ['--path' => $path]);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['schema', 's', InputOption::VALUE_OPTIONAL, 'Optional schema to be attached to the migration', null],
            ['model', null, InputOption::VALUE_OPTIONAL, 'Want a model for this table?', true],
            ['migrate', null, InputOption::VALUE_OPTIONAL, 'Want to migrate the migration file?', null],
        ];
    }
}
