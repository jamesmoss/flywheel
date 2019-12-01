<?php

namespace JamesMoss\Flywheel\Index;

interface IndexInterface
{
    /**
     * Checks if the given operator is compatible with this index.
     *
     * @param string $operator the operator to check.
     *
     * @return bool true if it is compatible.
     */
    public function isOperatorCompatible($operator);

    /**
     * Get documents from the index.
     *
     * @param mixed $value the value we are looking for.
     * @param string $operator the operator used for comparision.
     *
     * @return array<int,string> a list of documents ids.
     */
    public function get($value, $operator);

    /**
     * Update a document in the index.
     *
     * @param string $id the id of this document.
     * @param mixed $new the new value of this document for the indexed field.
     * @param mixed $old the old value of this document for the indexed field.
     *
     * @return void
     */
    public function update($id, $new, $old);
}
