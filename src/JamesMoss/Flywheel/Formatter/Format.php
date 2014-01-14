<?php

namespace JamesMoss\Flywheel\Formatter;

interface Format
{
    public function getFileExtension();
    public function encode(array $data);
    public function decode($data);
}