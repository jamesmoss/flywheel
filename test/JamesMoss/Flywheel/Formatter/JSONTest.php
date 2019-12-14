<?php

namespace JamesMoss\Flywheel\Formatter;

use \JamesMoss\Flywheel\TestBase;

class JSONTest extends TestBase
{
    public function testFileExtension()
    {
        $formatter = new JSON;
        $this->assertSame('json', $formatter->getFileExtension());
    }

    public function testEncoding()
    {
        $formatter = new JSON();
        $formatterPretty = new JSON(JSON_PRETTY_PRINT);
        $data = array(
            'name'     => 'Joe',
            'age'      => 21,
            'employed' => true,
        );

        $this->assertSame(json_encode($data), $formatter->encode($data));
        $this->assertSame(json_encode($data, JSON_PRETTY_PRINT), $formatterPretty->encode($data));
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
