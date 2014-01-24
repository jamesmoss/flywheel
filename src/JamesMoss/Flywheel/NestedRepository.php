<?php

namespace JamesMoss\Flywheel;

/**
 * NestedRepository
 */
class NestedRepository extends Repository
{
    const SEPERATOR = '/';

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

        // This is hacky and bad but we make the directory at this point.
        // This means that checks for missing documents can cause a directory
        // to be created, but this way requires less code modification in
        // Repository. Once we refactor the file stuff this should be fixed.
        // Todo - remove
        if(strpos($id, self::SEPERATOR) !== false) {
            $path = DIRECTORY_SEPARATOR . str_replace(self::SEPERATOR, DIRECTORY_SEPARATOR, dirname($id));
            if(!file_exists($this->path . $path)) {
                mkdir($this->path . $path, 0777, true);
            }
        } else {
            $path = '';
        }

        return  $this->path . $path . DIRECTORY_SEPARATOR . $this->getFilename($id);
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

    /**
     * Get an array containing the path of all files in this repository
     *
     * @return array An array, item is a file path.
     */
    protected function getAllFiles()
    {
        $ext       = $this->formatter->getFileExtension();
        
        $files = array();
        $this->getFilesRecursive($this->path, $files, $ext);

        return $files;
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
