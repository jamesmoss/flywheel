<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class QueryTest extends TestBase
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

    public function testWhereMultiDimensionalKey()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('multidimensionalkey', $config);
        $query  = new Query($repo);

        $query->where('name.title.first', '==', 'Afghanistan');
        $result = $query->execute();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result->total());
    }

    public function testWhereMultiDimensionalIndex()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('multidimensionalindex', $config);
        $query  = new Query($repo);

        $query->where('Tags.0.Key', '==', 'aws:autoscaling:groupName');
        $result = $query->execute();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result->total());
    }

    public function testWhereAnd()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('whereand', $config);
        $query  = new Query($repo);

        $query->where('key', '==', 1);
        $query->whereAnd('name', '==', 'b');
        $result = $query->execute();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result->total());
    }

    public function testWhereOr()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('whereor', $config);
        $query  = new Query($repo);

        $query->where('key', '===', 1);
        $query->whereOr('key', '===', 2);
        $result = $query->execute();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(2, count($result));
        $this->assertEquals(2, $result->total());
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
