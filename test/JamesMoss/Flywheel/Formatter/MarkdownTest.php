<?php

namespace JamesMoss\Flywheel\Formatter;

use \JamesMoss\Flywheel\TestBase;

class MarkdownTest extends TestBase
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
        $content = $this->normalizeLineendings(file_get_contents(__DIR__ . '/fixtures/joe.md'));
        $this->assertSame($content, $formatter->encode($data));
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
