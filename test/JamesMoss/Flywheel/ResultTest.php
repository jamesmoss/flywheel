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
        foreach($result as $v) {
            $i += $v['id'];
        }
        $this->assertSame(15, $i);
    }

    public function testArrayAccess()
    {
        $result = $this->getTestResult();

        $this->assertSame(array('id' => 3), $result[2]);
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

        $this->assertSame(1234, $result->total());
    }

    protected function getTestResult()
    {
        return new Result(array(
            array('id' => 1),
            array('id' => 2),
            array('id' => 3),
            array('id' => 4),
            array('id' => 5),
        ), 1234);
    }
}