<?php

namespace JamesMoss\Flywheel\Index;

use JamesMoss\Flywheel\Index\StoredIndex;
use stdClass;

class HashIndex extends StoredIndex
{

    /**
     * @inheritdoc
     */
    protected function getEntries($value)
    {
        if (!isset($this->data->$value)) {
            return array();
        }
        return array_keys(get_object_vars($this->data->$value));
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
        if(!isset($this->data->$value)) {
            return;
        }
        unset($this->data->$value->$id);
        if (count(get_object_vars($this->data->$value)) === 0) {
            unset($this->data->$value);
        }
    }

    protected function updateEntry($id, $new, $old)
    {
        $this->removeEntry($id, $old);
        $this->addEntry($id, $new);
    }
}
