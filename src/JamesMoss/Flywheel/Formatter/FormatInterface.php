<?php

namespace JamesMoss\Flywheel\Formatter;

interface FormatInterface
{
    public function getFileExtension();
    public function encode(array $data);
    public function decode($data);
}