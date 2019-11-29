<?php

namespace JamesMoss\Flywheel;

class TestBase extends \PHPUnit\Framework\TestCase
{
    public function normalizeLineendings($content)
    {
        return str_replace("\r\n", "\n", $content);
    }

    public function recurseRmdir($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recurseRmdir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    private function arraysEqualsUnordered($a, $b)
    {
        // if the indexes don't match, return immediately
        if (count(array_diff_assoc($a, $b))) {
            return false;
        }
        // we know that the indexes, but maybe not values, match.
        // compare the values between the two arrays
        foreach ($a as $k => $v) {
            if ($v !== $b[$k]) {
                return false;
            }
        }
        // we have identical indexes, and no unequal values
        return true;
    }

    /**
    * Determine if two associative arrays are similar
    *
    * Both arrays must have the same indexes with identical values
    * without respect to key ordering 
    * 
    * @param array $expected
    * @param array $actual
    * @return bool
    */
    public function assertEqualsUnordered($expected, $actual)
    {
        $this->assertTrue($this->arraysEqualsUnordered($expected, $actual));
    }
}
