<?php

namespace JamesMoss\Flywheel\Formatter;

use \JamesMoss\Flywheel\TestBase;

class YAMLTest extends TestBase
{
    public function testFileExtension()
    {
        $formatter = new YAML;
        $this->assertSame('yaml', $formatter->getFileExtension());
    }

    public function testEncoding()
    {
        $formatter = new YAML;
        $data = array(
            'name'     => 'Joe',
            'age'      => 21,
            'employed' => true,
        );
        $content = $this->normalizeLineendings(file_get_contents(__DIR__ . '/fixtures/joe.yaml'));
        $this->assertSame($content, $formatter->encode($data));
    }

    public function testDecoding()
    {
        $formatter = new YAML;
        $data = array(
            'name'     => 'Joe',
            'age'      => 21,
            'employed' => true,
        );
        $raw = file_get_contents(__DIR__ . '/fixtures/joe.yaml');

        $this->assertEquals($data, $formatter->decode($raw));
    }
}
