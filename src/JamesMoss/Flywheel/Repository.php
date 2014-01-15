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
    protected $queryClass;

    /**
     * Constructor
     *
     * @param string $name   The name of the repository. Must match /[A-Za-z0-9_-]{1,63}+/
     * @param Config $config The config to use for this repo
     */
    public function __construct($name, Config $config)
    {
        // Setup class properties
        $this->name       = $name;
        $this->path       = $config->getPath() . DIRECTORY_SEPARATOR . $name;
        $this->formatter  = $config->getOption('formatter');
        $this->queryClass = $config->getOption('query_class');

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
        $className = $this->queryClass;

        return new $className($this);
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
            $fp       = fopen($file, 'r');
            $contents = fread($fp, filesize($file));
            fclose($fp);
            
            $data = $this->formatter->decode($contents);

            if (null !== $data) {
                $doc = new Document((array) $data);
                $doc->setId(basename($file, '.' . $ext));

                $documents[] = $doc;
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
        $id = $document->getId();

        // Generate an id if none has been defined
        if (!$id) {
            $document->setId($this->generateId());
        }

        if (!$this->validateId($id)) {
            throw new \Exception(sprintf('`%s` is not a valid document ID.', $id));
        }

        $path = $this->getPathForDocument($id);
        $data = get_object_vars($document);
        $data = $this->formatter->encode($data);

        $fp = fopen($path, 'w');
        if(!flock($fp, LOCK_EX)) {
            return false;
        }
        $result = fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result;
    }

    /**
     * Store a Document in the repository, but only if it already
     * exists. The document must have an ID.
     *
     * @param Document $document The document to store
     *
     * @return bool True if stored, otherwise false
     */
    public function update(Document $document)
    {
        if (!$document->getId()) {
            return false;
        }

        $path = $this->getPathForDocument($document->getId());

        if(!file_exists($path)) {
            return false;
        }

        return $this->store($document);
    }

    /**
     * Delete a document from the repository using its ID.
     *
     * @param mixed $id The ID of the document (or the document itself) to delete
     *
     * @return boolean True if deleted, false if not.
     */
    public function delete($id)
    {
        if ($id instanceof Document) {
            $id = $id->getId();
        }

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
        if(!$this->validateId($id)) {
            throw new \Exception(sprintf('`%s` is not a valid ID.', $id));
        }

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
        return $id . '.' . $this->formatter->getFileExtension();
    }

    /**
     * Checks to see if a document ID is valid
     *
     * @param  string $id The ID to check
     *
     * @return bool     True if valid, otherwise false
     */
    protected function validateId($id)
    {
        return (boolean)preg_match('/^[^\\/\\?\\*:;{}\\\\\\n]+$/us', $id);
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
