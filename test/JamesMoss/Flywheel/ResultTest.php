<?php

namespace JamesMoss\Flywheel;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function testCountable()
    {
        $result = $this->getTestResult();

        $this->assertSame(5, count($result));
    }

    public function testIterable()
    {
        $result = $this->getTestResult();

        $i = 0;
        foreach ($result as $v) {
            $i += $v->id;
        }
        $this->assertSame(26, $i);
    }

    public function testArrayAccess()
    {
        $doc = new Document(array(
            'id' => 3
        ));
        $result = new Result(array(
            $doc,
        ), 1);

        $this->assertSame($doc, $result[0]);
    }

    /**
     * @expectedException Exception
     */
    public function testArrayAccessCantSetValues()
    {
        $result = $this->getTestResult();

        $result[] = 'this wont work';
    }

    /**
     * @expectedException Exception
     */
    public function testArrayAccessCantUnsetValues()
    {
        $result = $this->getTestResult();

        unset($result[2]);
    }

    public function testTotal()
    {
        $result = $this->getTestResult();

        $this->assertSame(5, $result->total());
    }

    public function testFirst()
    {
        $result = $this->getTestResult();
        $doc    = $result->first();
        $testDoc = new Document(array('id' => 6, 'name' => 'Bob Jones', 'age' => 35));

        $this->assertEquals($testDoc, $doc);
    }

    public function testLast()
    {
        $result = $this->getTestResult();
        $doc    = $result->last();
        $testDoc = new Document(array('id' => 1, 'name' => 'Katie Smith', 'age' => 21));

        $this->assertEquals($testDoc, $doc);

    }

    public function testFirstNoResults()
    {
        $result = new Result(array(), 0);
        $doc    = $result->first();

        $this->assertFalse($doc);
    }

    public function testValue()
    {
        $result = $this->getTestResult();
        $doc    = $result->value('id');
        $this->assertSame(6, $doc);
    }

    public function testValueNoResults()
    {
        $result = $this->getEmptyTestResult();
        $doc    = $result->value('age');

        $this->assertFalse($doc);
    }

    public function testValueKeyDoesntExist()
    {
        $result = $this->getTestResult();
        $doc    = $result->value('height');
        $this->assertFalse($doc);
    }

    public function testPick()
    {
        $result = $this->getTestResult();
        $doc    = $result->pick('id');
        $this->assertSame(array(6,7,3,9,1), $doc);
    }

    public function testHash()
    {
        $result = $this->getTestResult();
        $doc    = $result->hash('id', 'age');
        $this->assertSame(array(6 => 35, 7 => 19, 3 => 43, 9 => 37, 1 => 21), $doc);
    }

    protected function getTestResult()
    {
        return new Result(array(
            new Document(array('id' => 6, 'name' => 'Bob Jones',      'age' => 35)),
            new Document(array('id' => 7, 'name' => 'Fred Smith',     'age' => 19)),
            new Document(array('id' => 3, 'name' => 'John Appleseed', 'age' => 43)),
            new Document(array('id' => 9, 'name' => 'Mary Jones',     'age' => 37)),
            new Document(array('id' => 1, 'name' => 'Katie Smith',    'age' => 21)),
        ), 5);
    }

    protected function getEmptyTestResult()
    {
        return new Result(array(), 0);
    }
}
