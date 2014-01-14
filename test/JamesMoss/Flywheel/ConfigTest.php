<?php

namespace JamesMoss\Flywheel;

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
        chmod($path, 0555);
        $config = new Config($path);
    }

    public function testSlashesTidedUp()
    {
        $path   = __DIR__ . '/fixtures/datastore/writable';
        $config = new Config($path . '/');

        $this->assertSame($path, $config->getPath());
    }

    public function testSettingOptions()
    {
        $path   = __DIR__ . '/fixtures/datastore/writable';
        $config = new Config($path, array('bob' => true));

        $this->assertTrue($config->getOption('bob'));
        $this->assertNull($config->getOption('nonexistant'));
    }

    public function testSettingFormatter()
    {
        $path   = __DIR__ . '/fixtures/datastore/writable';
        $config = new Config($path . '/', array(
            'formatter' => new Formatter\YAML,
        ));

        $this->assertInstanceOf('JamesMoss\\Flywheel\\Formatter\\YAML', $config->getOption('formatter'));
    }
}
