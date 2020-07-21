<?php

namespace Geeky\Historical\Services;

use Doctrine\DBAL\Schema\Column;

class Generator
{
    /**
     * @var Column
     */
    private $column;

    /**
     * @var string
     */
    private $table;

    /**
     * @var
     */
    private $primaryKey;

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var string|null
     */
    private $nullable;

    /**
     * @var string|null
     */
    private $unsigned;

    /**
     * @var string|null
     */
    private $foreign;

    /**
     * @var string
     */
    private $columnType;

    /**
     * Generator constructor.
     *
     * @param $table
     * @param $primaryKey
     */
    public function __construct($table, Column $column, $primaryKey)
    {
        $this->column = $column;
        $this->table = $table;
        $this->primaryKey = $primaryKey;

        $this->setColumnName();
        $this->setColumnType();
        $this->setNullable();
        $this->setUnsigned();
        $this->setForeign();
    }

    /**
     * @param $table
     */
    public function setColumnName(): void
    {
        $this->columnName = $this->column->getName() . ':';
    }

    public function setColumnType()
    {
        $this->columnType = (new Type($this->column))->getColumnType();
    }

    public function setNullable(): void
    {
        $this->nullable = $this->column->getNotnull() ? ':nullable' : '';
    }

    public function setUnsigned(): void
    {
        $this->unsigned = $this->column->getUnsigned() ? ':unsigned' : '';
    }

    public function setForeign(): void
    {
        $this->foreign = ($this->columnName === $this->primaryKey . ":") ? ':foreign' : '';
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $columnName = $this->foreign ? $this->table.'_'.$this->columnName : $this->columnName;

        return $columnName.$this->unsigned.$this->columnType.$this->foreign.$this->nullable;
    }
}
