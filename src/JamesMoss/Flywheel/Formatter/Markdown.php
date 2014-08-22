<?php

namespace JamesMoss\Flywheel\Formatter;

class Markdown extends YAML
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
        $body = isset($data[$this->contentFieldName]) ? $data[$this->contentFieldName] : '';
        unset($data[$this->contentFieldName]);

        $str = "---\n";
        $str.= parent::encode($data);
        $str.= "---\n";
        $str.= $body;

        return $str;
    }

    public function decode($data)
    {
        $parts = preg_split('/[\n]*[-]{3}[\n]/', $data, 3);

        $yaml = parent::decode($parts[1]);
        $yaml[$this->contentFieldName] = $parts[2];

        return $yaml;
    }
}
