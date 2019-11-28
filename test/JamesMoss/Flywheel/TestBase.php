<?php

namespace JamesMoss\Flywheel;

class TestBase extends \PHPUnit\Framework\TestCase
{
    public function normalizeLineendings($content)
    {
        return str_replace("\r\n", "\n", $content);
    }

    public function recurseRmdir($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
          (is_dir("$dir/$file")) ? $this->recurseRmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}