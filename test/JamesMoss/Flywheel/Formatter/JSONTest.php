<?php

namespace JamesMoss\Flywheel\Formatter;

class JSONTest extends \PHPUnit_Framework_TestCase
{
    public function testFileExtension()
    {
        $formatter = new JSON;
        $this->assertSame('json', $formatter->getFileExtension());
    }

    public function testEncoding()
    {
        $formatter = new JSON;
        $data = array(
            'name'     => 'Joe',
            'age'      => 21,
            'employed' => true,
        );

        $options = defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : null;

        $this->assertSame(json_encode($data, $options), $formatter->encode($data));
    }

    public function testDecoding()
    {
        $formatter = new JSON;
        $data = (object)array(
            'name'     => 'Joe',
            'age'      => 21,
            'employed' => true,
        );
        $raw = '{"name":"Joe","age":21,"employed":true}';

        $this->assertEquals($data, $formatter->decode($raw));
    }
}
