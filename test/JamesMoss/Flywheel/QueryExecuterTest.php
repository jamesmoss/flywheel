<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class QueryExecuterTest extends TestBase
{
    public function testParams()
    {
        $pred = $this->getPredicate()->where('cca2', '==', 'GB');
        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array());

        $result = $qe->run();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result->total());
    }

    public function testWhereWithNonExistantField()
    {
        $pred = $this->getPredicate()->where('this_doesnt_exist', '==', 'GB');
        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array());

        $result = $qe->run();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(0, count($result));
        $this->assertEquals(0, $result->total());
    }

    public function testWhereMultiDimensionalKey()
    {
        $pred = $this->getPredicate()->where('name.title.first', '==', 'Afghanistan');
        $qe = new QueryExecuter($this->getRepo('multidimensionalkey'), $pred, array(), array());

        $result = $qe->run();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result->total());
    }

    public function testWhereMultiDimensionalIndex()
    {
        $pred = $this->getPredicate()->where('Tags.0.Key', '==', 'aws:autoscaling:groupName');
        $qe = new QueryExecuter($this->getRepo('multidimensionalindex'), $pred, array(), array());

        $result = $qe->run();
        $this->assertInstanceOf('\\JamesMoss\\Flywheel\\Result', $result);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, $result->total());
    }

    public function testSimpleOrdering()
    {
        $qe = new QueryExecuter($this->getRepo('countries'), $this->getPredicate(), array(), array('capital DESC'));

        $result = $qe->run();
        $this->assertEquals('Croatia', $result->first()->id);
        $this->assertEquals('Heard Island and McDonald Islands', $result[$result->count() -1]->id);
    }

    public function testOrderingBySubKey()
    {
        $pred = $this->getPredicate()->where('translations.de', '!=', false);
        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array('translations.de ASC'));

        $result = $qe->run();

        $this->assertEquals('Afghanistan', $result->first()->translations->de);
        $this->assertEquals('Österreich', $result[$result->count() - 1]->translations->de);

        $pred = $this->getPredicate()->where('currency.0', '!=', false);
        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array('currency.0 DESC'));

        $result = $qe->run();

        $this->assertEquals('Zimbabwe', $result->first()->id);
        $this->assertEquals('United Arab Emirates', $result[$result->count() - 1]->id);
    }

    public function testOrderingWithInteger()
    {
        $repo = $this->getRepo('countries');
        $qe = new QueryExecuter($repo, $this->getPredicate(), array(), array('population DESC'));

        $result = $qe->run();

        $this->assertEquals('China', $result->first()->id);
        $this->assertEquals('India', $result[1]->id);

        $pred = $this->getPredicate()->where('population', '>', 0);
        $qe = new QueryExecuter($repo, $pred, array(), array('population'));

        $result = $qe->run();

        $this->assertEquals('Pitcairn Islands', $result->first()->name);
        $this->assertEquals('Cocos (Keeling) Islands', $result[1]->name);
    }

    public function testBadData()
    {
        $qe = new QueryExecuter($this->getRepo('baddata'), $this->getPredicate(), array(), array());

        $result = $qe->run();
        $this->assertEquals(1, count($result));
    }

    public function testFindingById()
    {
        $pred = $this->getPredicate()->where('__id', '==', 'EE');
        $qe = new QueryExecuter($this->getRepo('querybyid'), $pred, array(), array());

        $result = $qe->run();
        $this->assertEquals(1, count($result));
        $this->assertEquals('Estonia', $result->first()->name);
    }

    public function testOrderingById()
    {
        $pred = $this->getPredicate();
        $qe = new QueryExecuter($this->getRepo('querybyid'), $pred, array(), array('__id DESC'));

        $result = $qe->run();
        $this->assertEquals(3, count($result));
        $this->assertEquals('Sweden', $result->first()->name);
        $this->assertEquals('Estonia', $result[1]->name);
        $this->assertEquals('Djibouti', $result[2]->name);
    }


    protected function getRepo($repoName)
    {
        $path = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/');

        return new Repository($repoName, $config);
    }

    protected function getPredicate()
    {
        return new Predicate();
    }
}
