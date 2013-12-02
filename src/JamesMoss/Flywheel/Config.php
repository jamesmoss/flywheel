<?php

namespace JamesMoss\Flywheel;

class Config
{
    protected $path;

    public function __construct($path)
    {
        $path = rtrim($path, '/');

        if(!is_dir($path)) {
            throw new \RuntimeException(sprintf('`%s` is not a directory.', $path));
        }

        if(!is_writable($path)) {
            throw new \RuntimeException(sprintf('`%s` is not writable.', $path));
        }

        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}