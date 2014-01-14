<?php

namespace JamesMoss\Flywheel;

class CachedQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testCache()
    {
        if (!ini_get('apc.enable_cli')) {
            $this->markTestSkipped(
              'The APC extension is not enabled on the command line. Set apc.enable_cli=1.'
            );
        }

        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('countries', $config);
        $query  = new CachedQuery($repo);

        $total = 0;

        for ($i = 0; $i < 100; $i++) {
            $start = microtime(true);
            $query->where('cca2', '==', 'GB');
            $query->execute();
            $total += microtime(true) - $start;
        }

    }
}
