<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class TestBase extends \PHPUnit_Framework_TestCase 
{
    public function normalizeLineendings($content)
    {
        return str_replace("\r\n", "\n", $content);
    }    
}