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
    protected $documentClass;

    /**
     * Constructor
     *
     * @param string $name   The name of the repository. Must match /[A-Za-z0-9_-]{1,63}+/
     * @param Config $config The config to use for this repo
     */
    public function __construct($name, Config $config)
    {
        // Setup class properties
        $this->name          = $name;
        $this->path          = $config->getPath() . DIRECTORY_SEPARATOR . $name;
        $this->formatter     = $config->getOption('formatter');
        $this->queryClass    = $config->getOption('query_class');
        $this->documentClass = $config->getOption('document_class');

        // Ensure the repo name is valid
        $this->validateName($this->name);

        // Ensure directory exists and we can write there
        if (!is_dir($this->path)) {
            if (!@mkdir($this->path, 0777, true)) {
                throw new \RuntimeException(sprintf('`%s` doesn\'t exist and can\'t be created.', $this->path));
            }
        } else if (!is_writable($this->path)) {
            throw new \RuntimeException(sprintf('`%s` is not writable.', $this->path));
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
        $files     = $this->getAllFiles();
        $documents = array();

        foreach ($files as $file) {
            $fp       = fopen($file, 'r');
            $contents = fread($fp, filesize($file));
            fclose($fp);

            $data = $this->formatter->decode($contents);

            if (null !== $data) {
                $doc = new $this->documentClass((array) $data);
                $doc->setId($this->getIdFromPath($file, $ext));

                $documents[] = $doc;
            }
        }

        return $documents;
    }

    /**
     * Returns a single document based on it's ID
     *
     * @param  string $id The ID of the document to find
     *
     * @return Document|boolean  The document if it exists, false if not.
     */
    public function findById($id)
    {
        if(!file_exists($path = $this->getPathForDocument($id))) {
            return false;
        }

        $fp       = fopen($path, 'r');
        $contents = fread($fp, filesize($path));
        fclose($fp);

        $data = $this->formatter->decode($contents);

        if($data === null) {
            return false;
        }

        $ext = $this->formatter->getFileExtension();

        $doc = new $this->documentClass((array) $data);
        $doc->setId($this->getIdFromPath($path, $ext));

        return $doc;
    }

    /**
     * Store a Document in the repository.
     *
     * @param Document $document The document to store
     *
     * @return bool True if stored, otherwise false
     */
    public function store(DocumentInterface $document)
    {
        $id = $document->getId();

        // Generate an id if none has been defined
        if (is_null($id)) {
            $id = $document->setId($this->generateId());
        }

        if (!$this->validateId($id)) {
            throw new \Exception(sprintf('`%s` is not a valid document ID.', $id));
        }

        $path = $this->getPathForDocument($id);
        $data = get_object_vars($document);
        $data = $this->formatter->encode($data);

        if(!$this->write($path, $data)) {
            return false;
        }

        return $id;
    }

    /**
     * Store a Document in the repository, but only if it already
     * exists. The document must have an ID.
     *
     * @param Document $document The document to store
     *
     * @return bool True if stored, otherwise false
     */
    public function update(DocumentInterface $document)
    {
        if (!$document->getId()) {
            return false;
        }

        $oldPath = $this->getPathForDocument($document->getInitialId());

        if(!file_exists($oldPath)) {
            return false;
        }

        // If the ID has changed we need to delete the old document.
        if($document->getId() !== $document->getInitialId()) {
            if(file_exists($oldPath)) {
                unlink($oldPath);
            }
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
        if ($id instanceof DocumentInterface) {
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
            throw new \Exception(sprintf('`%s` is not a valid document ID.', $id));
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
     * Get an array containing the path of all files in this repository
     *
     * @return array An array, item is a file
     */
    public function getAllFiles()
    {
        $ext       = $this->formatter->getFileExtension();
        $filesystemIterator = new \FilesystemIterator($this->path, \FilesystemIterator::SKIP_DOTS);
        $files = new \RegexIterator($filesystemIterator, "/\\.{$ext}$/");

        return $files;
    }

    /**
     * Writes data to the filesystem.
     *
     * @todo  Abstract this into a filesystem layer.
     *
     * @param  string $path     The absolute file path to write to
     * @param  string $contents The contents of the file to write
     *
     * @return boolean          Returns true if write was successful, false if not.
     */
    protected function write($path, $contents)
    {
        $fp = fopen($path, 'w');
        if(!flock($fp, LOCK_EX)) {
            return false;
        }
        $result = fwrite($fp, $contents);
        flock($fp, LOCK_UN);
        fclose($fp);

        return $result !== false;
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

    /**
     * Get a document's ID base on its filesystem path
     *
     * @param  string $path The full path to the file (including file extension)
     * @param  string $ext  The file extension (without the period)
     *
     * @return string       The ID of the document
     */
    protected function getIdFromPath($path, $ext)
    {
        return basename($path, '.' . $ext);
    }

}
