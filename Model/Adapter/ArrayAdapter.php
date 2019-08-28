<?php

namespace Staempfli\CommerceImport\Model\Adapter;

use Magento\ImportExport\Model\Import\AbstractSource;

/**
 * Class ArrayAdapter
 * @package Staempfli\CommerceImport\Model\Adapter
 */
class ArrayAdapter extends AbstractSource
{
    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var array
     */
    protected $array = [];

    /**
     * @param int $position
     */
    public function seek($position)
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new \OutOfBoundsException("invalid seek position ($position)");
        }
    }

    /**
     * ArrayAdapter constructor.
     * @param array $data
     */
    public function __construct($data)
    {
        $this->array = $data;
        $this->position = 0;
        $colNames = array_keys($this->current());
        parent::__construct($colNames);
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->array[$this->position];
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->array[$this->position]);
    }

    /**
     * @return array
     */
    public function getColNames()
    {
        $colNames =[];
        foreach ($this->array as $row) {
            foreach (array_keys($row) as $key) {
                if (!is_numeric($key) && !isset($colNames[$key])) {
                    $colNames[$key] = $key;
                }
            }
        }
        return $colNames;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setValue($key, $value)
    {
        if (!$this->valid()) {
            return;
        }

        $this->array[$this->position][$key] = $value;
    }

    /**
     * @param $key
     */
    public function unsetValue($key)
    {
        if (!$this->valid()) {
            return;
        }

        unset($this->array[$this->position][$key]);
    }

    /**
     * @return mixed
     */
    protected function _getNextRow()
    {
        $this->next();
        return $this->current();
    }
}
