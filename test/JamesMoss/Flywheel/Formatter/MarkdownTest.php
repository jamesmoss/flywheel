<?php

namespace JamesMoss\Flywheel\Formatter;

class MarkdownTest extends \PHPUnit_Framework_TestCase
{
    public function testFileExtension()
    {
        $formatter = new Markdown;
        $this->assertSame('md', $formatter->getFileExtension());
    }

    public function testEncoding()
    {
        $formatter = new Markdown;
        $data = array(
            'name'     => 'Joe',
            'age'      => 21,
            'employed' => true,
            'body'     => "Lorem ipsum dolor\nsit amet",
        );

        $this->assertSame(file_get_contents(__DIR__ . '/fixtures/joe.md'), $formatter->encode($data));
    }

    public function testDecoding()
    {
        $formatter = new Markdown;
        $data = array(
            'name'     => 'Joe',
            'age'      => 21,
            'employed' => true,
            'body'     => "Lorem ipsum dolor\nsit amet",
        );
        $raw = file_get_contents(__DIR__ . '/fixtures/joe.md');

        $this->assertEquals($data, $formatter->decode($raw));
    }
}
