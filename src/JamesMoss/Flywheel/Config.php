<?php

namespace JamesMoss\Flywheel;

/**
 * Config
 *
 * Responsible for storing variables used throughout a Flywheel instance
 */
class Config
{
    protected $path;
    protected $options;

    /**
     * Constructor
     *
     * @param string $path    The full path to a writeable directory, with or
     *                        without a trailing slash.
     * @param array  $options Any other configuration options.
     */
    public function __construct($path, array $options = array())
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        // Merge supplied options with the defaults
        $options = array_merge(array(
            'formatter'      => new Formatter\JSON,
            'query_class'    => $this->hasAPC() ? '\\JamesMoss\\Flywheel\\CachedQuery' : '\\JamesMoss\\Flywheel\\Query',
            'document_class' => '\\JamesMoss\\Flywheel\\Document',
        ), $options);

        $this->path    = $path;
        $this->options = $options;
    }

    /**
     * Gets the path set during initialisation
     *
     * @return string The full file path, with no trailing slash.
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Gets a specific option from the config
     *
     * @param string $name The name of the option to return.
     *
     * @return mixed The value of the option if it exists or null if it doesnt.
     */
    public function getOption($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function hasAPC()
    {
        return function_exists('apcu_fetch') || function_exists('apc_fetch');
    }
}
