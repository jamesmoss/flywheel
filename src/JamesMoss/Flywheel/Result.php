<?php

namespace JamesMoss\Flywheel;

/**
 * Result
 *
 * A collection of Documents returned from a Query.
 *
 * This class acts like an array but also has some helper methods for
 * manipulating the collection in useful ways.
 */
class Result implements \IteratorAggregate, \ArrayAccess, \Countable
{
    protected $documents;
    protected $total;

    /**
     * Constructor
     *
     * @param array   $documents An array of Documents
     * @param integer $total     If this result only represents a small slice of
     *                           the total (when using limit), this parameter
     *                           represents the total number of documents.
     */
    public function __construct($documents, $total)
    {
        $this->documents = $documents;
        $this->total     = $total;
    }

    /**
     * Returns the number of documents in this result
     *
     * @return integer The number of documents
     */
    public function count()
    {
        return count($this->documents);
    }

    /**
     * Returns the total number of documents (if using limit in a query).
     * Useful for working out pagination.
     *
     * @return integer The total number of documents
     */
    public function total()
    {
        return $this->total;
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->documents);
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception('Cannot set values on Flywheel\Result');
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return isset($this->documents[$offset]);
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($offset)
    {
        throw new \Exception('Cannot unset values on Flywheel\Result');
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return isset($this->documents[$offset]) ? $this->documents[$offset] : null;
    }

    /**
     * Gets the first document from the result.
     *
     * @return mixed The first document, or false if the result is empty.
     */
    public function first()
    {
        return !empty($this->documents) ? $this->documents[0] : false;
    }

    /**
     * Gets the last document from the results.
     *
     * @return mixed The last document, or false if the result is empty.
     */
    public function last()
    {
        return !empty($this->documents) ? $this->documents[count($this->documents) - 1] : false;
    }

    /**
     * Get the  value specified by $key of the first object in the result.
     *
     * @return mixed The value, or false if there are no documents or the key
     *               doesnt exist.
     */
    public function value($key)
    {
        $first = $this->first();

        if (!$first) {
            return false;
        }

        return isset($first->{$key}) ? $first->{$key} : false;
    }

    /**
     * Returns an array where each value is a single property from each
     * document. If the property doesnt exist on the document then it won't
     * be in the returned array.
     *
     * @param string $field The name of the field to pick.
     *
     * @return array The array of values, one from each document.
     */
    public function pick($field)
    {
        $result = array();

        foreach ($this->documents as $document) {
            if (isset($document->{$field})) {
                $result[] = $document->{$field};
            }
        }

        return $result;
    }

    /**
     * Returns an assoiative array (a hash), where for each document the
     * value of one property is the key, and another property is the value.
     *
     * @param string $keyField   The name of the property to use for the key.
     * @param string $valueField Name of the property to use for the value.
     *
     * @return array An associative array.
     */
    public function hash($keyField, $valueField)
    {
        $result = array();

        foreach ($this->documents as $document) {
            $result[$document->{$keyField}] = $document->{$valueField};
        }

        return $result;
    }
}
