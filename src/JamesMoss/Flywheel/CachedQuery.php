<?php

namespace JamesMoss\Flywheel;

/**
 * CachedQuery
 *
 * A extension of Query that is able to store results in shared memory (via
 * the apc_* functions) so that they can be retieved for future use with
 * minimal performance impact.
 */
class CachedQuery extends Query
{
    /**
     * Checks the cache to see if this query exists and returns it. If it's
     * not in the cache then the query is run, stored and then returned.
     *
     * @return Result The result of the query.
     */
    public function execute()
    {
        static $apcPrefix = null;
        if($apcPrefix === null) {
            $apcPrefix = function_exists('apcu_fetch') ? 'apcu' : 'apc';
        }

        // Generate a cache key by comparing our parameters to see if we've
        // made this query before
        $key = $this->getParameterHash() . $this->getFileHash();

        // Try and fetch a cached result object from APC
        $funcName = $apcPrefix . '_fetch';
        $success  = false;
        $result   = $funcName($key, $success);

        // If the result isn't in the cache then we run the real query
        if (!$success) {
            $result = parent::execute();
            $funcName = $apcPrefix . '_store';
            $funcName($key, $result);
        }

        return $result;
    }

    /**
     * Gets a hash based on the files in the repo directory. If the contents
     * of a file changes, or other files are added/deleted the hash will change.
     * Uses filematime() for speed when checking for file changes (rather than
     * using crc32 or md5 etc)
     *
     * @return string A 128bit hash in hexadecimal format.
     */
    protected function getFileHash()
    {
        $files = $this->repo->getAllFiles();
        $hash  = '';

        foreach ($files as $file) {
            $hash.= $file . '|';
            $hash.= (string) filemtime($file) . '|';
        }

        $hash = md5($hash);

        return $hash;
    }

    /**
     * Generates a hash based on the parameters set in the query.
     *
     * @return string A 128bit hash in hexadecimal format.
     */
    protected function getParameterHash()
    {
        $parts = array(
            $this->repo->getName(),
            serialize((array) $this->limit),
            serialize((array) $this->orderBy),
            serialize((array) $this->predicate),
        );

        return md5(implode('|', $parts));
    }
}
