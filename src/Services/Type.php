<?php

namespace Geeky\Historical\Services;

use Doctrine\DBAL\Schema\Column;

class Type
{
    /**
     * @var string
     */
    private $type;

    public function __construct(Column $column) {
        $type = $column->getType()->getName();
        $this->setColumnType($type);
    }

    /**
     * @param $type
     */
    private function setColumnType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getColumnType()
    {
        switch ($this->type){
            case $this->type === 'integer':
                return $this->type;
                break;
            case strpos($this->type, 'int') !== false:
                return str_replace('int', 'Integer', $this->type);
                break;
            default:
                return $this->type;
        }
    }
}