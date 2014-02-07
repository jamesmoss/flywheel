<?php

namespace JamesMoss\Flywheel\Formatter;

use \Spyc;

class YAML implements FormatInterface
{
    public function getFileExtension()
    {
        return 'yaml';
    }

    public function encode(array $data)
    {
        return Spyc::YAMLDump($data, false, false, true);
    }

    public function decode($data)
    {
        return Spyc::YAMLLoadString($data);
    }
}
