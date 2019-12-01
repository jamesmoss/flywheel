<?php

namespace JamesMoss\Flywheel\Formatter;

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
        return json_decode($data);
    }
}