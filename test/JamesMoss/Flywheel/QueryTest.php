<?php

namespace JamesMoss\Flywheel;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function testWhere()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('countries', $config);
        $query  = new Query($repo);
        

        $query->where('cca2', '==', 'GB');
        $query->execute();
    }

    public function testOrdering()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('countries', $config);
        $query  = new Query($repo);
        
        $query->orderBy('capital DESC');

        $query->execute();
    }

    public function testBadData()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('baddata', $config);
        $query  = new Query($repo);
        $result = $query->execute();
        $this->assertEquals(1, count($result));
    }
}
