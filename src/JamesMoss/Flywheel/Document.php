<?php

namespace JamesMoss\Flywheel;

class Document
{
    public function __construct($data)
    {
        foreach($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}