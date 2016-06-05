<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class PredicateTest extends TestBase
{
    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidEmptyFieldName()
    {
        $pred  = new Predicate();
        $pred->where('', '==', true);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidWhitespaceFieldName()
    {
        $pred  = new Predicate();
        $pred->where('    ', '==', true);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidOperator()
    {
        $pred  = new Predicate();
        $pred->where('name', '<~>', 'bob');
    }

    public function testAddingAnd()
    {
        $pred = new Predicate();
        $pred
            ->where('name', '==', 'mike')
            ->andWhere('age', '>', '39')
        ;

        $this->assertEquals(array(
            array(false, 'name', '==', 'mike'),
            array('and', 'age', '>', '39'),
        ), $pred->getAll());
    }

    public function testAddingOr()
    {
        $pred = new Predicate();
        $pred
            ->where('name', '==', 'fred')
            ->orWhere('name', '==', 'alice')
            ->orWhere('name', '==', 'hugo')
        ;

        $this->assertEquals(array(
            array(false, 'name', '==', 'fred'),
            array('or', 'name', '==', 'alice'),
            array('or', 'name', '==', 'hugo'),
        ), $pred->getAll());
    }

    public function testAddingBoth()
    {
        $pred = new Predicate();
        $pred
            ->where('name', '==', 'fred')
            ->orWhere('name', '==', 'alice')
            ->orWhere('name', '==', 'hugo')
            ->andWhere('age', '>', '28')
        ;

        $this->assertEquals(array(
            array(false, 'name', '==', 'fred'),
            array('or', 'name', '==', 'alice'),
            array('or', 'name', '==', 'hugo'),
            array('and', 'age', '>', '28'),
        ), $pred->getAll());
    }

    public function testAddingSubPredicate()
    {
        $pred = new Predicate();
        $pred
            ->where('name', '==', 'hannah')
            ->andWhere(function($query){
                $query->where('age', '<', 20)->orWhere('age', '>', 30);
            })
        ;

        $this->assertEquals(array(
            array(false, 'name', '==', 'hannah'),
            array('and', array(
                array(false, 'age', '<', 20),
                array('or', 'age', '>', 30),
            )),
        ), $pred->getAll());
    }
}
