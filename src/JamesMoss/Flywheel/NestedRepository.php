<?php

namespace JamesMoss\Flywheel;

/**
 * NestedRepository
 */
class NestedRepository extends Repository
{
    const SEPERATOR = '/';

    /**
     * @inherit
     */
    public function __construct($name, Config $config)
    {
        parent::__construct($name, $config);

        $this->deleteEmptyDirs = $config->getOption('delete_empty_dirs') === true;
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

        if($this->isNestedId($id)) {
            $path = DIRECTORY_SEPARATOR . str_replace(self::SEPERATOR, DIRECTORY_SEPARATOR, dirname($id));
        } else {
            $path = '';
        }

        return  $this->path . $path . DIRECTORY_SEPARATOR . $this->getFilename($id);
    }

    /**
     * @inherit
     */
    public function delete($id)
    {
        $result = parent::delete($id);

        if ($id instanceof DocumentInterface) {
            $id = $id->getId();
        }

        if(!$result || !$this->deleteEmptyDirs || !$this->isNestedId($id)) {
            return $result;
        }

        $path = $this->getPathForDocument($id);
        $dir  = dirname($path);

        if(file_exists($dir) && count(glob($dir . '/*')) === 0) {
            rmdir($dir);
        }

        return $result;
    }

    /**
     * @inherit
     */
    protected function write($path, $contents)
    {
        // ensure path exists by making directories beforehand
        if(!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        return parent::write($path, $contents);
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
        return basename($id) . '.' . $this->formatter->getFileExtension();
    }

    /**
     * Get an array containing the path of all files in this repository
     *
     * @return array An array, item is a file path.
     */
    public function getAllFiles()
    {
        $ext   = $this->formatter->getFileExtension();
        $files = array();
        $this->getFilesRecursive($this->path, $files, $ext);

        return $files;
    }

    protected function isNestedId($id)
    {
        return strpos($id, self::SEPERATOR) !== false;
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
        // Similar regex to the one in the parent method, this allows forward slashes
        // in the key name, except for at the start or end.
        return (boolean)preg_match('/^[^\\/]?[^\\?\\*:;{}\\\\\\n]+[^\\/]$/us', $id);
    }

    protected function getFilesRecursive($dir, array &$result, $ext)
    {
        $extensionLength = strlen($ext) + 1; // one is for the dot!
        $files           = scandir($dir);
        foreach($files as $file) {
            if($file === '.' || $file === '..') {
                continue;
            }

            if(is_dir($newDir = $dir . DIRECTORY_SEPARATOR . $file)) {
                $this->getFilesRecursive($newDir, $result, $ext);
                continue;
            }

            if(substr($file, -$extensionLength) !== '.' . $ext) {
                continue;
            }

            $result[] = $dir . DIRECTORY_SEPARATOR . $file;
        }

        return $result;
    }

    /**
     * @inherit
     */
    protected function getIdFromPath($path, $ext)
    {
        return substr($path, strlen($this->path) + 1, -strlen($ext) - 1);
    }

}
