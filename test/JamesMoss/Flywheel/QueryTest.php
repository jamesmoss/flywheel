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
	
	public function testWhereById()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('countries', $config);
        $query  = new Query($repo);

        $query->where('ID', '==', 'Italy_ad79ef0f076d3a686ab9738925f4dd2c7e69d7d1');
        $result = $query->execute();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result->total());
		$this->assertEquals('Italy_ad79ef0f076d3a686ab9738925f4dd2c7e69d7d1', $result->first()->getId());
    }
}
