<?php

namespace JamesMoss\Flywheel;

/**
* Interface for documents
*/
interface DocumentInterface
{

    /**
     * Constructor
     *
     * @param array $data An associative array, each key/value pair will be
     *                    turned into properties on this object.
     */
    public function __construct(array $data = array());

    /**
     * Set the document ID.
     *
     * @param string $id
     */
    public function setId($id);

    /**
     * Get the document ID.
     *
     * @return string
     */
    public function getId();

    /**
     * Get the initial document ID.
     *
     * @return string
     */
    public function getInitialId();
}
