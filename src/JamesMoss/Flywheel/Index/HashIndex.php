<?php

namespace JamesMoss\Flywheel\Index;

use JamesMoss\Flywheel\Formatter\JSON;
use JamesMoss\Flywheel\Index\StoredIndex;
use stdClass;

class HashIndex extends StoredIndex
{
    protected static $operators = array(
        '==', '!='
    );

    /**
     * @inheritdoc
     */
    public function __construct($field, $repository) {
        $this->construct($field, $repository, new JSON(JSON_OBJECT_AS_ARRAY));
    }

    /**
     * @inheritdoc
     */
    public function isOperatorCompatible($operator)
    {
        return in_array($operator, self::$operators);
    }

    /**
     * @inheritdoc
     */
    protected function initData()
    {
        $this->data = array();
    }

    /**
     * @inheritdoc
     */
    protected function getEntries($value, $operator)
    {
        if (!isset($this->data[$value])) {
            return array();
        }
        switch ($operator) {
            case '==': return array_keys($this->data[$value]);
            case '!=': return $this->idsExcept($value);
            default: throw new \InvalidArgumentException('Incompatible operator `'.$operator.'`.');
        }
    }

    /**
     * Adds an entry in the index
     *
     * @param string $id
     * @param string $value
     */
    protected function addEntry($id, $value)
    {
        if (!isset($this->data[$value])) {
            $this->data[$value] = array();
        }
        $this->data[$value][$id] = 1;
    }

    /**
     * Removes an entry from the index
     *
     * @param string $id
     * @param string $value
     */
    protected function removeEntry($id, $value)
    {
        if (!isset($this->data[$value])) {
            return;
        }
        unset($this->data[$value][$id]);
        if (count($this->data[$value]) === 0) {
            unset($this->data[$value]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function updateEntry($id, $new, $old)
    {
        if ($new !== null) {
            $this->addEntry($id, $new);
        }
        if ($old !== null) {
            $this->removeEntry($id, $old);
        }
    }

    protected function idsExcept($value) {
        $data = $this->data;
        unset($data[$value]);
        return array_keys(array_reduce($data, function($prev, $val) {
            return array_merge($prev, $val);
        }, array()));
    }
}
