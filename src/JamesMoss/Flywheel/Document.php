<?php

namespace JamesMoss\Flywheel;

class Document
{
    public function __construct($id, $data)
    {
        $this->id = $id;

        foreach($data as $key => $value) {
            $this->{$key} = $value;
        }
    }
}