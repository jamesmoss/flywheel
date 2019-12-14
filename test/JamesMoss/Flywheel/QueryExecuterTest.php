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

    public function testMultipleAndPredicates()
    {
        $pred = $this->getPredicate()
            ->where('language.0', '==', 'English')
            ->andWhere('population', '>', 300000)
            ->andWhere('region', '==', 'Americas');
        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array());

        $result = $qe->run();

        $this->assertEquals(5, $result->total());
    }

    public function testMultipleOrPredicates()
    {
        $pred = $this->getPredicate()
            ->where('subregion', '==', 'Micronesia')
            ->orWhere('subregion', '==', 'Eastern Africa');
        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array());

        $result = $qe->run();

        $this->assertEquals(27, $result->total());
    }

    public function testSubPredicates()
    {
        $pred = $this->getPredicate()
            ->where('region', '==', 'Europe')
            ->andWhere('population', '<', 40000)
            ->andWhere(function($query){
                $query->where('language.0', '==', 'Italian')
                  ->orWhere('language.0', '==', 'English');
            });

        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array('name ASC'));

        $result = $qe->run();

        $this->assertEquals(3, $result->total());

        $this->assertEquals('Gibraltar', $result->first()->name);
        $this->assertEquals('San Marino', $result[1]->name);
        $this->assertEquals('Vatican City', $result[2]->name);
    }

    public function testInOperator()
    {
        $pred = $this->getPredicate()
            ->where('region', '==', 'Europe')
            ->andWhere('population', '<', 40000)
            ->andWhere('language.0', 'IN', array('Italian', 'English'));

        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array('name ASC'));

        $result = $qe->run();

        $this->assertEquals(3, $result->total());

        $this->assertEquals('Gibraltar', $result->first()->name);
        $this->assertEquals('San Marino', $result[1]->name);
        $this->assertEquals('Vatican City', $result[2]->name);
    }

    public function testSimpleOrdering()
    {
        $qe = new QueryExecuter($this->getRepo('countries'), $this->getPredicate(), array(), array('capital DESC', 'name ASC'));

        $result = $qe->run();
        $this->assertEquals('Croatia', $result->first()->id);
        $this->assertEquals('United States Minor Outlying Islands', $result[$result->count() -1]->id);
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

    /**
     * @dataProvider hashIndexOperatorsProvider
     */
    public function testFindByIndex($operator)
    {
        $pred = $this->getPredicate()
            ->where('region', $operator, 'Europe');
        $options = array(
            'indexes' => array(
                'region' => '\JamesMoss\Flywheel\Index\HashIndex'
            )
        );
        $n = 5;

        $qe = new QueryExecuter($this->getRepo('countries', $options), $pred, array(), array());
        $start = microtime(true);
        for ($i=0; $i < $n; $i++) {
            $withIndex = $qe->run();
        }
        $timeWithIndex = microtime(true) - $start;

        $qe = new QueryExecuter($this->getRepo('countries'), $pred, array(), array());
        $start = microtime(true);
        for ($i=0; $i < $n; $i++) {
            $withoutIndex = $qe->run();
        }
        $timeWithoutIndex = microtime(true) - $start;

        $this->assertSameSize($withoutIndex, $withIndex);
        $this->assertEqualsUnordered(get_object_vars($withoutIndex), get_object_vars($withIndex));
        $this->assertLessThan($timeWithoutIndex, $timeWithIndex);
    }


    protected function getRepo($repoName, $options = array())
    {
        $path = __DIR__ . '/fixtures/datastore/querytest';
        $config = new Config($path . '/', $options);

        return new Repository($repoName, $config);
    }

    protected function getPredicate()
    {
        return new Predicate();
    }

    public function hashIndexOperatorsProvider()
    {
        return array(
            array('=='),
            array('!='),
        );
    }
}
