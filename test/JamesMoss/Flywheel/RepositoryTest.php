<?php

namespace JamesMoss\Flywheel;

class RespositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider validNameProvider
     */
    public function testValidRepoName($name)
    {
        $config = new Config('/tmp');
        $repo = new Repository($config, $name);
        $this->assertSame($name, $repo->getName());
    }

    /**
     * @dataProvider invalidNameProvider
     * @expectedException Exception
     */
    public function testInvalidRepoName($name)
    {
        $config = new Config('/tmp');
        $repo = new Repository($config, $name);
    }

    public function validNameProvider()
    {
        return array(
            array('Users'),
            array('Photos_and_memories'),
            array('12'),
        );
    }

    public function invalidNameProvider()
    {
        return array(
            array(''),
            array('This_would_be_a_valid_repository_name_except_for_the_fact_it_is_really_really_long'),
        );
    }
}