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
        // Generate a cache key by comparing our parameters to see if we've
        // made this query before
        $key = $this->getParameterHash() . $this->getFileHash();

        // Try and fetch a cached result object from APC
        $result = apc_fetch($key, $success);

        // If the result isn't in the cache then we run the real query
        if (!$success) {
            $result = parent::execute();
            apc_store($key, $result);
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
        $path  = $this->repo->getPath();
        $files = scandir($path);
        $hash  = '';

        foreach ($files as $file) {
            if ($file == '..' || $file == '.') {
                continue;
            }

            $hash.= $file . '|';
            $hash.= (string) filemtime($path . '/' . $file) . '|';
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
            serialize((array) $this->where),
        );

        return md5(implode('|', $parts));
    }
}
