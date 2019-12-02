<?php

namespace JamesMoss\Flywheel;

use JamesMoss\Flywheel\Index\IndexInterface;

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
    /** @var array<string,IndexInterface> $indexes */
    protected $indexes;

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
        $this->indexes       = $config->getOption('indexes', array());
        $self = $this;
        array_walk($this->indexes, function(&$class, $field) use ($self) {
            if (!is_subclass_of($class, '\JamesMoss\Flywheel\Index\IndexInterface')) {
                throw new \RuntimeException(sprintf('`%s` does not implement IndexInterface.', $class));
            }
            $class = new $class($field, $self);
        });

        // Ensure the repo name is valid
        $this->validateName($this->name);
        $this->ensureDirectory($this->path);
    }

    /**
     * Ensure directory exists and we can write there
     */
    protected function ensureDirectory($path) {
        if (!is_dir($path)) {
            if (!@mkdir($path, 0777, true)) {
                throw new \RuntimeException(sprintf('`%s` doesn\'t exist and can\'t be created.', $path));
            }
        } else if (!is_writable($path)) {
            throw new \RuntimeException(sprintf('`%s` is not writable.', $path));
        }
    }

    /**
     * Adds a directory in the repository.
     *
     * @param string $name The name of the new directory.
     *
     * @return string The path of the directory.
     */
    public function addDirectory($name)
    {
        $path = $this->path . DIRECTORY_SEPARATOR . $name;
        $this->ensureDirectory($path);
        return $path;
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
     * Returns the list of indexes of this repository.
     *
     * @return array<string,IndexInterface> The list of indexes.
     */
    public function getIndexes()
    {
        return $this->indexes;
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
     * @return array<int,Document> An array of Documents.
     */
    public function findAll()
    {
        $ext       = $this->formatter->getFileExtension();
        $files     = $this->getAllFiles();
        $documents = array();

        foreach ($files as $file) {
            $fp       = fopen($file, 'r');
            $contents = null;
            if(($filesize = filesize($file)) > 0) {
                $contents = fread($fp, $filesize);
            }
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
     * @return Document|false The document if it exists, false if not.
     */
    public function findById($id)
    {
        if(!file_exists($path = $this->getPathForDocument($id))) {
            return false;
        }

        $fp       = fopen($path, 'r');
        $contents = null;
        if(($filesize = filesize($path)) > 0) {
            $contents = fread($fp, $filesize);
        }
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
     * Returns a list of documents based on their ID.
     *
     * @param array<int,string> $ids The IDs array of document to find.
     *
     * @return array<int,Document>|false An array of Documents.
     */
    public function findByIds($ids)
    {
        $ext       = $this->formatter->getFileExtension();
        $documents = array();
        foreach ($ids as $id) {
            if(!file_exists($path = $this->getPathForDocument($id))) {
                return false;
            }
            $fp       = fopen($path, 'r');
            $contents = null;
            if(($filesize = filesize($path)) > 0) {
                $contents = fread($fp, $filesize);
            }
            fclose($fp);

            $data = $this->formatter->decode($contents);

            if (null !== $data) {
                $doc = new $this->documentClass((array) $data);
                $doc->setId($this->getIdFromPath($path, $ext));

                $documents[] = $doc;
            }
        }
        return $documents;
    }

    /**
     * Store a Document in the repository.
     *
     * @param Document $document The document to store
     *
     * @return string|false True if stored, otherwise false
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
        $previous = $this->findById($id);
        foreach ($this->indexes as $field => $index) {
            $oldFound = false;
            $newFound = false;
            $oldVal = $previous ? $previous->getNestedProperty($field, $oldFound) : null;
            $newVal = $document->getNestedProperty($field, $newFound);
            if (!$oldFound && $newFound) {
                $index->update($document->getId(), $newVal, null);
            } elseif ($oldFound && !$newFound) {
                $index->update($document->getId(), null, $oldVal);
            } elseif ($oldFound && $newFound) {
                $index->update($document->getId(), $newVal, $oldVal);
            }
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
     * @return string|false the id if stored, otherwise false
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
            $previous = $this->findById($document->getInitialId());
            foreach ($this->indexes as $field => $index) {
                $value = $previous->getNestedProperty($field, $found);
                if ($found) {
                    $index->update($previous->getId(), null, $value);
                }
            }
            unlink($oldPath);
        }

        return $this->store($document);
    }

    /**
     * Delete a document from the repository using its ID.
     *
     * @param mixed $doc The ID of the document (or the document itself) to delete
     *
     * @return boolean True if deleted, false if not.
     */
    public function delete($doc)
    {
        if ($doc instanceof DocumentInterface) {
            $id = $doc->getId();
        } else {
            $id = $doc;
            $doc = $this->findById($id);
        }
        foreach ($this->indexes as $field => $index) {
            $found = false;
            $value = $doc ? $doc->getNestedProperty($field, $found) : null;
            if ($found) {
                $index->update($id, null, $value);
            }
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
