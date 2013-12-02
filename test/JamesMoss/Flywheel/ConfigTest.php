<?php

namespace JamesMoss\Flywheel;

use org\bovigo\vfs\vfsStream;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException RuntimeException
     */
    public function testDataLocationExistsCheck()
    {
        $config = new Config('/this/path/wont/ever/exist/(probably)');
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage not writable
     */
    public function testDataLocationWritableCheck()
    {
        $path   = __DIR__ . '/fixtures/datastore/notwritable';
        $config = new Config($path);
    }

    public function testSlashesTidedUp()
    {
        $path   = __DIR__ . '/fixtures/datastore/writable';
        $config = new Config($path . '/');

        $this->assertSame($path, $config->getPath());
    }
}