<?php

namespace JamesMoss\Flywheel\Formatter;

use Symfony\Component\Yaml\Yaml as SymfonyYAML;

class YAML implements Format
{
    public function getFileExtension()
    {
        return 'yaml';
    }

    public function encode(array $data)
    {
        return SymfonyYAML::dump($data);
    }

    public function decode($data)
    {
        return SymfonyYAML::parse($data);
    }
}