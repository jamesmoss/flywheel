<?php

namespace JamesMoss\Flywheel\Formatter;

use Symfony\Component\Yaml\Yaml as SymfonyYAML;

class Markdown implements Format
{
    protected $contentFieldName;

    public function __construct($contentFieldName = 'body')
    {
        $this->contentFieldName = $contentFieldName;
    }

    public function getFileExtension()
    {
        return 'md';
    }

    public function encode(array $data)
    {
        $body = $data[$this->contentFieldName];
        unset($data[$this->contentFieldName]);

        $str = "---\n";
        $str.= SymfonyYAML::dump($data);
        $str.= "---\n";
        $str.= $body;

        return $str;
    }

    public function decode($data)
    {
        $parts = preg_split('/[\n]*[-]{3}[\n]/', $data, 3);

        $yaml = SymfonyYAML::parse($parts[1]);
        $yaml[$this->contentFieldName] = $parts[2];

        return $yaml;
    }
}