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
        $result = $query->execute();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result->total());
    }

    public function testOrdering()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('countries', $config);
        $query  = new Query($repo);

        $query->orderBy('capital DESC');

        $result = $query->execute();
        $this->assertEquals('Croatia', $result->first()->id);
        $this->assertEquals('Heard Island and McDonald Islands', $result[$result->count() -1]->id);
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
