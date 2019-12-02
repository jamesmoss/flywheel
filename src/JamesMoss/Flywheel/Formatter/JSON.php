<?php

namespace JamesMoss\Flywheel\Formatter;

defined('JSON_OBJECT_AS_ARRAY') or define('JSON_OBJECT_AS_ARRAY', 1);
defined('JSON_PRETTY_PRINT') or define('JSON_PRETTY_PRINT', 128);

class JSON implements FormatInterface
{
    protected $jsonOptions;

    public function __construct($jsonOptions = 0)
    {
        $this->jsonOptions = $jsonOptions;
    }

    public function getFileExtension()
    {
        return 'json';
    }

    public function encode(array $data)
    {
        return json_encode($data, $this->jsonOptions);
    }

    public function decode($data)
    {
        return json_decode($data, $this->jsonOptions&JSON_OBJECT_AS_ARRAY);
    }
}