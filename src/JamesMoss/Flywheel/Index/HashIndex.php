<?php

namespace JamesMoss\Flywheel\Index;

use JamesMoss\Flywheel\Index\StoredIndex;
use stdClass;

class HashIndex extends StoredIndex
{
    protected static $operators = array(
        '==', '===', '!=', '!=='
    );

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
    protected function getEntries($value, $operator)
    {
        if (!isset($this->data->$value)) {
            return array();
        }
        $ids = array_keys(get_object_vars($this->data->$value));
        switch ($operator) {
            case '==':
            case '===': return $ids;
            case '!=':
            case '!==': return array_diff(get_object_vars($this->data), $ids);
            default: throw new \InvalidArgumentException('Incompatible operator `'.$operator.'`.');
        }
    }

    /**
     * @inheritdoc
     */
    protected function addEntry($id, $value)
    {
        if (!isset($this->data->$value)) {
            $this->data->$value = new stdClass();
        }
        $this->data->$value->$id = 1;
    }

    /**
     * @inheritdoc
     */
    protected function removeEntry($id, $value)
    {
        if (!isset($this->data->$value)) {
            return;
        }
        unset($this->data->$value->$id);
        if (count(get_object_vars($this->data->$value)) === 0) {
            unset($this->data->$value);
        }
    }

    /**
     * @inheritdoc
     */
    protected function updateEntry($id, $new, $old)
    {
        $this->removeEntry($id, $old);
        $this->addEntry($id, $new);
    }
}
