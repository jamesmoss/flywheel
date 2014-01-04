<?php

namespace JamesMoss\Flywheel;

/**
 * Repository
 *
 * Analageous to a table in a traditional RDBMS, a repository is a siloed
 * collection where documents live. 
 */
class Repository
{
    protected $name;

    /**
     * Constructor
     *
     * @param string $name   The name of the repository. Must match /[A-Za-z0-9_-]{1,63}+/
     * @param Config $config The config to use for this repo
     */
    public function __construct($name, Config $config)
    {
        // Setup class properties
        $this->name = $name;
        $this->path = $config->getPath() . '/' . $name;

        // Ensure the repo name is valid
        $this->validateName($this->name);

        // Ensure directory exists and we can write there
        if(!file_exists($this->path)) {
            mkdir($this->path);
            chmod($this->path, 0777);
        } 
    }


    /**
     * Returns the name of this repository
     *
     * @return string The name of the repo
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the filesystem path of this repository.
     *
     * @return string The path where documents are stored.
     */
    public function getPath()
    {
        return $this->path;
    }


    /**
     * A factory method that initialises and returns an instance of a Query
     * object (or a class that extends it such as CachedQuery).
     *
     * @return Query A new Query class for this repo.
     */
    public function query()
    {
        $className = '\\JamesMoss\\Flywheel\\Query';

        return new $className($this);
    }

    /**
     * Returns all the documents within this repo.
     *
     * @return array An array of Documents.
     */
    public function findAll()
    {
        $files     = glob($this->path . '/*.json') or array();
        $documents = array();
        
        foreach ($files as $file) {
            $documents[] = new Document((array)json_decode(file_get_contents($file)));
        }

        return $documents;
    }

    /**
     * Validates the name of the repo to ensure it can be stored in the
     * filesystem.
     *
     * @param  string $name The name to validate against
     *
     * @return bool       Returns true if valid. Throws an exception if not.
     */
    protected function validateName($name)
    {
        if(!preg_match('/^[0-9A-Za-z\_\-]{1,63}$/', $name)) {
            throw new \Exception(sprintf('`%s` is not a valid repository name.', $name));
        }

        return true;
    }

    /**
     * Store a Document in the repository.
     *
     * @param  Document $document The document to store
     *
     * @return bool             True if stored, otherwise false
     */
    public function store(Document $document)
    {
        if(!isset($document->id)) {
            $document->id = $this->generateId();
        }

        $path    = $this->getPathForDocument($document->id);
        $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : null;
        $data    = json_encode((array)$document, $options);

        return file_put_contents($path, $data);
    }

    /**
     * Delete a document from the repository using its ID.
     *
     * @param  string $id The ID of the document to delete
     *
     * @return boolean     True or deleted, false if not.
     */
    public function delete($id)
    {
        $path = $this->getPathForDocument($id);

        return unlink($path);
    }

    /**
     * Get the filesystem path for a document based on it's ID.
     *
     * @param  string $id The ID of the document.
     *
     * @return string     The full filesystem path of the document.
     */
    public function getPathForDocument($id)
    {
        return $this->path . '/' . $this->getFilename($id);
    }

    /**
     * Gets just the filename for a document based on it's ID.
     *
     * @param  string $id The ID of the document.
     *
     * @return string     The filename of the document, including extension.
     */
    public function getFilename($id)
    {
        return $id . '_' . sha1($id) . '.json';
    }

    /**
     * Generates a random, unique ID for a document. The result is returned in
     * base62. This keeps it shorted but still human readable if shared in URLs.
     *
     * @return string The generated ID.
     */
    protected function generateId()
    {
        //openssl_random_pseudo_bytes
        $num = str_replace(' ', '', microtime());
        $id  = gmp_strval(gmp_init($num, 10), 62);

        return $id;
    }


}