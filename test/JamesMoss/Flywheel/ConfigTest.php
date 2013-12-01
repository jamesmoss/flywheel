<?php

namespace JamesMoss\Flywheel;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testDataLocationExists()
    {
        $config = new Config('/this/path/wont/ever/exist/(probably)');
    }
}