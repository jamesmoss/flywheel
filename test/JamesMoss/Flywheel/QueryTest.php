<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class QueryTest extends TestBase
{
    public function testPredicate()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('countries', $config);
        $query  = new Query($repo);
        $query->where('cca2', '==', 'GB');

        $predicate = new Predicate();
        $predicate->where('cca2', '==', 'GB');

        $this->assertAttributeEquals($predicate, 'predicate', $query);
    }

    public function testLimit()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('countries', $config);
        $query  = new Query($repo);
        $query->limit('10');
        $this->assertAttributeEquals(array(10, 0), 'limit', $query);
        $query->limit(5, 10);
        $this->assertAttributeEquals(array(5, 10), 'limit', $query);
        $query->limit(9, '11');
        $this->assertAttributeEquals(array(9, 11), 'limit', $query);

    }

    public function testOrderBy()
    {
        $path   = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');
        $repo   = new Repository('countries', $config);
        $query  = new Query($repo);
        $query->orderBy('age ASC');
        $this->assertAttributeEquals(array('age ASC'), 'orderBy', $query);
        $query->orderBy(array('surname DESC', 'age DESC'));
        $this->assertAttributeEquals(array('surname DESC', 'age DESC'), 'orderBy', $query);

    }
}
