<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class RespositoryTest extends TestBase
{
    const REPO_DIR  = '/tmp/flywheel';
    const REPO_NAME = '_pages';
    const REPO_PATH = '/tmp/flywheel/_pages/';

    /** @var Repository $repo */
    private $repo;


    protected function setUp()
    {
        parent::setUp();
        if (!is_dir(self::REPO_DIR)) {
            mkdir(self::REPO_DIR);
        }
        $config = new Config(self::REPO_DIR);
        $this->repo = new Repository(self::REPO_NAME, $config);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->recurseRmdir(self::REPO_PATH);
    }

    /**
     * @dataProvider validNameProvider
     */
    public function testValidRepoName($name)
    {
        $config = new Config(self::REPO_DIR);
        $repo = new Repository($name, $config);
        $this->assertSame($name, $repo->getName());
        $this->recurseRmdir($repo->getPath());
    }

    /**
     * @dataProvider invalidNameProvider
     * @expectedException Exception
     */
    public function testInvalidRepoName($name)
    {
        $config = new Config(self::REPO_DIR);
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
            $this->assertSame($data, (array) json_decode(file_get_contents(self::REPO_PATH . $name)));
        }
    }

    public function testDeletingDocuments()
    {
        $repo   = $this->repo;
        $id     = 'delete_test';
        $name   = $id . '.json';
        $path   = self::REPO_PATH . $name;

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

        rename(self::REPO_PATH . 'testdoc123.json', self::REPO_PATH . 'newname.json');

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

        $this->assertTrue(file_exists(self::REPO_PATH . 'test1234.json'));

        $doc->setId('9876test');
        $repo->update($doc);

        $this->assertFalse(file_exists(self::REPO_PATH . 'test1234.json'));
    }

    public function testFindByIds()
    {
        for ($i=0; $i < 10; $i++) {
            $doc = new Document(array(
                'test' => $i,
            ));
            $doc->setId("doc$i");
            $this->repo->store($doc);
        }
        $docs = $this->repo->findByIds(array('doc1', 'doc3', 'doc4'));
        $this->assertCount(3, $docs);
        $this->assertEquals(1, $docs[0]->test);
        $this->assertEquals(3, $docs[1]->test);
        $this->assertEquals(4, $docs[2]->test);
        $docs = $this->repo->findByIds(array('doc1', 'DOC3', 'doc4'));
        $this->assertFalse($docs);
        $docs = $this->repo->findByIds(array());
        $this->assertCount(0, $docs);
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
