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
    protected $path;
    protected $formatter;

    /**
     * Constructor
     *
     * @param string $name   The name of the repository. Must match /[A-Za-z0-9_-]{1,63}+/
     * @param Config $config The config to use for this repo
     */
    public function __construct($name, Config $config)
    {
        // Setup class properties
        $this->name      = $name;
        $this->path      = $config->getPath() . DIRECTORY_SEPARATOR . $name;
        $this->formatter = $config->getOption('formatter');

        // Ensure the repo name is valid
        $this->validateName($this->name);

        // Ensure directory exists and we can write there
        if (!file_exists($this->path)) {
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
     * A factory method that initialises and returns an instance of a Query object.
     *
     * @return Query A new Query class for this repo.
     */
    public function query()
    {
        return new Query($this);
    }

    /**
     * Returns all the documents within this repo.
     *
     * @return array An array of Documents.
     */
    public function findAll()
    {
        $ext       = $this->formatter->getFileExtension();
        $files     = glob($this->path . DIRECTORY_SEPARATOR . '*.' . $ext);
        $documents = array();

        foreach ($files as $file) {
            $data = $this->formatter->decode(file_get_contents($file));
            if (null !== $data) {
                $documents[] = new Document((array) $data);
            }
        }

        return $documents;
    }

    /**
     * Validates the name of the repo to ensure it can be stored in the
     * filesystem.
     *
     * @param string $name The name to validate against
     *
     * @return bool Returns true if valid. Throws an exception if not.
     */
    protected function validateName($name)
    {
        if (!preg_match('/^[0-9A-Za-z\_\-]{1,63}$/', $name)) {
            throw new \Exception(sprintf('`%s` is not a valid repository name.', $name));
        }

        return true;
    }

    /**
     * Store a Document in the repository.
     *
     * @param Document $document The document to store
     *
     * @return bool True if stored, otherwise false
     */
    public function store(Document $document)
    {
        if (!isset($document->id)) {
            $document->id = $this->generateId();
        }

        $path    = $this->getPathForDocument($document->id);
        $data    = $this->formatter->encode((array) $document);

        return file_put_contents($path, $data);
    }

    /**
     * Delete a document from the repository using its ID.
     *
     * @param string $id The ID of the document to delete
     *
     * @return boolean True or deleted, false if not.
     */
    public function delete($id)
    {
        $path = $this->getPathForDocument($id);

        return unlink($path);
    }

    /**
     * Get the filesystem path for a document based on it's ID.
     *
     * @param string $id The ID of the document.
     *
     * @return string The full filesystem path of the document.
     */
    public function getPathForDocument($id)
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->getFilename($id);
    }

    /**
     * Gets just the filename for a document based on it's ID.
     *
     * @param string $id The ID of the document.
     *
     * @return string The filename of the document, including extension.
     */
    public function getFilename($id)
    {
        return $id . '_' . sha1($id) . '.' . $this->formatter->getFileExtension();
    }

    /**
     * Generates a random, unique ID for a document. The result is returned in
     * base62. This keeps it shorted but still human readable if shared in URLs.
     *
     * @return string The generated ID.
     */
    protected function generateId()
    {
        static $choices = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $id = '';
        while (strlen($id) < 9) {
            $id .= $choices[ mt_rand(0, strlen($choices) - 1) ];
        }
        return $id;
    }

}
