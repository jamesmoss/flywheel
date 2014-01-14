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
        $repo = new Repository($name, $config);
        $this->assertSame($name, $repo->getName());
    }

    /**
     * @dataProvider invalidNameProvider
     * @expectedException Exception
     */
    public function testInvalidRepoName($name)
    {
        $config = new Config('/tmp');
        $repo = new Repository($name, $config);
    }

    public function testGettingQueryObject()
    {
        $config = new Config('/tmp');
        $repo   = new Repository('flywheeltest', $config);

        $this->assertInstanceOf('JamesMoss\\Flywheel\\Query', $repo->query());
    }

    public function testStoringDocuments()
    {
        if (!is_dir('/tmp/flywheel')) {
            mkdir('/tmp/flywheel');
        }
        $config = new Config('/tmp/flywheel');
        $repo   = new Repository('_pages', $config);

        for ($i = 0; $i < 5; $i++) {

            $data = array(
                'id'   => $i,
                'slug' => '123',
                'body' => 'THIS IS BODY TEXT'
            );

            $document = new Document($data);

            $repo->store($document);

            $name = $i . '_' . sha1($i) . '.json';
            $this->assertSame($data, (array) json_decode(file_get_contents('/tmp/flywheel/_pages/' . $name)));
        }
    }

    public function testDeletingDocuments()
    {
        $config = new Config('/tmp/flywheel');
        $repo   = new Repository('_pages', $config);
        $id     = 'delete_test';
        $name   = $id . '_' . sha1($id) . '.json';
        $path   = '/tmp/flywheel/_pages/' . $name;

        file_put_contents($path, '');

        $this->assertTrue(is_file($path));

        $repo->delete($id);

        $this->assertFalse(is_file($path));
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
