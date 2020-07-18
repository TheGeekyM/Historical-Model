<?php

namespace Geeky\Historical\Concerns;

use Geeky\Historical\Enum\HistoryFlag;
use Carbon\Carbon;

/**
 * Trait Historical.
 */
trait Historical
{
    /**
     * @var bool
     */
    private $isDirtyData = false;

    /**
     * @param array|\array[][] $attributes
     *
     * @param array $options
     *
     * @return mixed
     */
    public function update(array $attributes = [], array $options = [])
    {
        $this->isHistoryModelExist();

        $this->checkIfDataIsDirty($attributes);

        $updated = parent::update($attributes, $options);

        $this->makeItHistorical($this->toArray(), 'update');

        return $updated;
    }

    /**
     * @param $data
     *
     * @return mixed
     */
    public static function create($data)
    {
        return (new self())->createData($data);
    }

    /**
     * @param $attributes
     *
     * @return mixed
     */
    private function createData($attributes)
    {
        $this->isHistoryModelExist();

        $createdData = parent::create($attributes);

        $this->makeItHistorical($createdData->toArray(), 'create');

        return $createdData;
    }

    /**
     * @param $data
     * @param $methodType
     */
    private function makeItHistorical($data, $methodType): void
    {
        $historicalModel = new $this->historicalModel();

        if ('update' === $methodType) {
            $this->updateLastSavedHistoryRow($historicalModel);
        }

        $this->insertNewHistoryRow($data, $historicalModel);
    }

    private function updateLastSavedHistoryRow($historicalModel): void
    {
        $historicalModel->where($this->primaryKey, $this->{$this->primaryKey})
            ->where('status_control', HistoryFlag::CURRENT)
            ->update([
                'end_datetime' => Carbon::now()->tz('Africa/Cairo')->format('Y-m-d H:i:s'),
                'status_control' => HistoryFlag::ARCHIVED,
            ]);
    }

    /**
     * @param $data
     * @param $historicalModel
     */
    private function insertNewHistoryRow($data, $historicalModel): void
    {
        $historicalModel->create($data + [
                'status_control' => HistoryFlag::CURRENT,
                'start_datetime' => Carbon::now()->tz('Africa/Cairo')->format('Y-m-d H:i:s'),
                'end_datetime' => null,
                'created_by_id' => auth()->user()->Id,
            ]);
    }

    private function checkIfDataIsDirty($attributes): void
    {
        foreach ($attributes as $key => $value) {
            if ($this->getOriginal($key) == $attributes[$key]) {
                $this->isDirtyData = true;
            }
        }
    }

    private function isHistoryModelExist(): void
    {
        if (!$this->historicalModel || !class_exists($this->historicalModel)) {
            throw new \RuntimeException('The specified history model doesn\'t not exist');
        }
    }
}
