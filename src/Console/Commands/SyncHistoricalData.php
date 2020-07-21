<?php

namespace Geeky\Historical\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class SyncHistoricalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'historical-model:sync {base-model : The base model} {historical-model : The historical model} {columns? : specified columns you want to sync}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from your base table to the historical table';

    public function handle(): void
    {
        $this->columns = !empty($this->argument('columns')) ? explode(',', $this->argument('columns')) : [];
        $baseModel = $this->getModelObjectOrFail($this->argument('base-model'));
        $historicalModel = $this->getModelObjectOrFail($this->argument('historical-model'));

        $currentDateTime = Carbon::now()->tz('Africa/Cairo')->format('Y-m-d H:i:s');

        $bar = $this->output->createProgressBar($baseModel->count() / 100);
        $bar->start();

        $baseModel->chunk(100, function (Collection $data) use ($currentDateTime, $bar) {
            $bar->advance();

            $rows = $data->map(function ($row) {
                $row->status_control = 'c';
                $row->start_datetime = $row->{$row::CREATED_AT};
                $row->end_datetime = null;
                $row->created_by_id = null;

                dd($row->diff($this->columns));

                return $row->only($this->columns + ['start_datetime' , 'status_control' , 'end_datetime' , 'created_by_id']);
            })->toArray();

            dd($rows);
            $rows = array_diff($this->columns, $rows);

            dd($rows);
            dd($rows);
//            $historicalModel->insert($rows);
        });

        $bar->finish();
    }

    /**
     * @param $model
     *
     * @return mixed
     */
    private function getModelObjectOrFail($model)
    {
        if (!class_exists($model)) {
            $this->error("The model ({$model}) you specified does't exist");
            exit();
        }

        return new $model();
    }
}
