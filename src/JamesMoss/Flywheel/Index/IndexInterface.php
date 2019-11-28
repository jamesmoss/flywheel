<?php

namespace JamesMoss\Flywheel\Index;

interface IndexInterface
{
    /**
     * Get documents from the index.
     *
     * @param mixed $value the value we are looking for.
     *
     * @return array<int,string> a list of documents ids.
     */
    public function get($value);

    /**
     * Add a document to the index.
     *
     * @param string $id the id of this document.
     * @param mixed $value the value of this document for the indexed field.
     *
     * @return void
     */
    public function add($id, $value);

    /**
     * Remove a document from the index.
     *
     * @param string $id the id of this document.
     * @param mixed $value the value of this document for the indexed field.
     *
     * @return void
     */
    public function remove($id, $value);

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
