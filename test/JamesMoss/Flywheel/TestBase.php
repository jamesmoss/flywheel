<?php

namespace JamesMoss\Flywheel;

class TestBase extends \PHPUnit\Framework\TestCase
{
    public function normalizeLineendings($content)
    {
        return str_replace("\r\n", "\n", $content);
    }    
}