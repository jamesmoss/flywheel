<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class RespositoryTest extends TestBase
{
    /** @var Repository $repo */
    private $repo;


    protected function setUp()
    {
        parent::setUp();
        
        if (!is_dir('/tmp/flywheel')) {
            mkdir('/tmp/flywheel');
        }
        $config = new Config('/tmp/flywheel');
        $this->repo = new Repository('_pages', $config);
    }

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
        new Repository($name, $config);
    }

    /**
     * @dataProvider validIdProvider
     */
    public function testValidId($id)
    {
        $document = new Document(array());
        $document->setId($id);
        $this->assertEquals($id, $this->repo->store($document));
    }

    /**
     * @dataProvider invalidIdProvider
     * @expectedException Exception
     */
    public function testInvalidId($id)
    {
        $document = new Document(array());
        $document->setId($id);
        $this->repo->store($document);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testDataLocationExistsCheck()
    {
        $config = new Config('/this/path/wont/ever/exist/(probably)');
        new Repository('test', $config);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage not writable
     */
    public function testDataLocationWritableCheck()
    {
        $path   = __DIR__ . '/fixtures/datastore';
        chmod($path . '/notwritable', 0555);
        $config = new Config($path);

        new Repository('notwritable', $config);
    }

    public function testGettingQueryObject()
    {
        $repo   = $this->repo;

        $this->assertInstanceOf('JamesMoss\\Flywheel\\Query', $repo->query());
    }

    public function testStoringDocuments()
    {
        $repo = $this->repo;

        for ($i = 0; $i < 5; $i++) {
            $data = array(
                'slug' => '123',
                'body' => 'THIS IS BODY TEXT'
            );

            $document = new Document($data);
            $document->setId($i);

            $repo->store($document);

            $name = $i . '.json';
            $this->assertSame($data, (array) json_decode(file_get_contents('/tmp/flywheel/_pages/' . $name)));
        }
    }

    public function testDeletingDocuments()
    {
        $repo   = $this->repo;
        $id     = 'delete_test';
        $name   = $id . '.json';
        $path   = '/tmp/flywheel/_pages/' . $name;

        file_put_contents($path, '');

        $this->assertTrue(is_file($path));

        $repo->delete($id);

        $this->assertFalse(is_file($path));
    }

    public function testRenamingDocumentChangesDocumentID()
    {
        $repo   = $this->repo;
        $doc    = new Document(array(
            'test' => '123',
        ));

        $doc->setId('testdoc123');

        $repo->store($doc);

        rename('/tmp/flywheel/_pages/testdoc123.json', '/tmp/flywheel/_pages/newname.json');

        foreach ($repo->findAll() as $document) {
            if ('newname' === $document->getId()) {
                $this->assertEquals('123', $document->test);
                return true;
            }
        }

        $this->fail('No file found with the new ID');
    }

    public function testChangingDocumentIDChangesFilename()
    {
        $repo   = $this->repo;
        $doc    = new Document(array(
            'test' => '123',
        ));

        $doc->setId('test1234');
        $repo->store($doc);

        $this->assertTrue(file_exists('/tmp/flywheel/_pages/test1234.json'));

        $doc->setId('9876test');
        $repo->update($doc);

        $this->assertFalse(file_exists('/tmp/flywheel/_pages/test1234.json'));
    }

    // public function testLockingOnWrite()
    // {
    //     $this->markTestSkipped();
    // }

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
            array('$'),
            array('This_would_be_a_valid_repository_name_except_for_the_fact_it_is_really_really_long'),
        );
    }
    public function validIdProvider()
    {
        return array(
            array('user1'),
            array('User 1-_^à@~&'), // this in my opinion should not be a valid id
        );
    }

    public function invalidIdProvider()
    {
        return array(
            array("user/1"),
            array("user\\1"),
            array("user*"),
            array("user:1"),
            array("user?"),
            array("user;"),
            array("user{1}"),
            array("user\n1"),
            array("user
            1"),
        );
    }
}
