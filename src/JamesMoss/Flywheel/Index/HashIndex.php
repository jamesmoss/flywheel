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
     * Adds an entry in the index
     *
     * @param string $id
     * @param string $value
     */
    protected function addEntry($id, $value)
    {
        if (!isset($this->data->$value)) {
            $this->data->$value = new stdClass();
        }
        $this->data->$value->$id = 1;
    }

    /**
     * Removes an entry from the index
     *
     * @param string $id
     * @param string $value
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
        if (!empty($new)) {
            $this->addEntry($id, $new);
        }
        if (!empty($old)) {
            $this->removeEntry($id, $old);
        }
    }
}
