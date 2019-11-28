<?php

namespace JamesMoss\Flywheel\Index;

use JamesMoss\Flywheel\Config;
use JamesMoss\Flywheel\Document;
use JamesMoss\Flywheel\Formatter\JSON;
use JamesMoss\Flywheel\Index\HashIndex;
use JamesMoss\Flywheel\Repository;
use JamesMoss\Flywheel\TestBase;

class HashIndexTest extends TestBase
{
    const REPO_DIR = '/tmp/flywheel';
    const REPO_NAME = '_index_test';

    /** @var Repository $repo */
    private $repo;

    /** @var HashIndex $index */
    private $index;

    protected function setUp()
    {
        parent::setUp();
        if (!is_dir(self::REPO_DIR)) {
            mkdir(self::REPO_DIR);
        }
        $config = new Config(self::REPO_DIR, array(
            'indexes' => array(
                'col1' => "JamesMoss\Flywheel\Index\HashIndex",
            )
        ));
        $this->repo = new Repository(self::REPO_NAME, $config);
        $this->index = new HashIndex('col1', $this->repo->getPath() . DIRECTORY_SEPARATOR . 'index', new JSON(), $this->repo);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->recurseRmdir(self::REPO_DIR . DIRECTORY_SEPARATOR . self::REPO_NAME);
    }

    public function testAddEntry()
    {
        $id = 'testdoc123';
        $this->index->add($id, '123');
        $this->assertEquals(array($id), $this->index->get('123'));
    }

    public function testRemoveEntry()
    {
        $id = 'testdoc123';
        $this->index->add($id, '123');
        $this->assertEquals(array($id), $this->index->get('123'));
        $this->index->remove($id, '123');
        $this->assertEquals(array(), $this->index->get('123'));
    }

    public function testUpdateEntry()
    {
        $id = 'testdoc123';
        $this->index->add($id, '123');
        $this->assertEquals(array($id), $this->index->get('123'));
        $this->index->update($id, '456', '123');
        $this->assertEquals(array(), $this->index->get('123'));
        $this->assertEquals(array($id), $this->index->get('456'));
    }

    public function testStoreDocument()
    {
        $doc = new Document(array(
            'col1' => '123',
        ));
        $id = 'testdoc123';
        $doc->setId($id);
        $this->assertEquals($id, $this->repo->store($doc));
        $this->assertEquals(array($id), $this->index->get('123'));
    }

    public function testReStoreDocumentUpdate()
    {
        $doc = new Document(array(
            'col1' => '123',
        ));
        $id = 'testdoc123';
        $doc->setId($id);
        $this->assertEquals($id, $this->repo->store($doc));
        $doc->col1 = '456';
        $this->assertEquals($id, $this->repo->store($doc));
        $this->assertEquals(array(), $this->index->get('123'));
        $this->assertEquals(array($id), $this->index->get('456'));
    }

    public function testReStoreDocumentRemove()
    {
        $doc = new Document(array(
            'col1' => '123',
        ));
        $id = 'testdoc123';
        $doc->setId($id);
        $this->assertEquals($id, $this->repo->store($doc));
        unset($doc->col1);
        $this->assertEquals($id, $this->repo->store($doc));
        $this->assertEquals(array(), $this->index->get('123'));
    }

    public function testUpdateDocument()
    {
        $doc = new Document(array(
            'col1' => '123',
        ));
        $id = 'testdoc123';
        $doc->setId($id);
        $this->assertEquals($id, $this->repo->store($doc));
        $id = 'testdoc456';
        $doc->setId($id);
        $doc->col1 = '456';
        $this->assertEquals($id, $this->repo->update($doc));
        $this->assertEquals(array(), $this->index->get('123'));
        $this->assertEquals(array($id), $this->index->get('456'));
    }

}