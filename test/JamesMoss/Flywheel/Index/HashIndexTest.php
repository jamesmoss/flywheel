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
    const REPO_DIR  = '/tmp/flywheel';
    const REPO_NAME = '_index_test';
    const REPO_PATH = '/tmp/flywheel/_index_test/';

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
        $this->index = new HashIndex('col1', self::REPO_PATH . '.indexes', new JSON(), $this->repo);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->recurseRmdir(self::REPO_PATH);
    }

    public function testAddEntry()
    {
        $id = 'testdoc123';
        $this->index->add($id, '123');
        $this->assertEquals(array($id), $this->index->get('123', '=='));
    }

    public function testRemoveEntry()
    {
        $id = 'testdoc123';
        $this->index->add($id, '123');
        $this->assertEquals(array($id), $this->index->get('123', '=='));
        $this->index->remove($id, '123');
        $this->assertEquals(array(), $this->index->get('123', '=='));
    }

    public function testUpdateEntry()
    {
        $id = 'testdoc123';
        $this->index->add($id, '123');
        $this->assertEquals(array($id), $this->index->get('123', '=='));
        $this->index->update($id, '456', '123');
        $this->assertEquals(array(), $this->index->get('123', '=='));
        $this->assertEquals(array($id), $this->index->get('456', '=='));
    }

    public function testStoreDocument()
    {
        $doc = new Document(array(
            'col1' => '123',
        ));
        $id = 'testdoc123';
        $doc->setId($id);
        $this->assertEquals($id, $this->repo->store($doc));
        $this->assertEquals(array($id), $this->index->get('123', '=='));
    }

    public function testReStoreDocumentNochange()
    {
        $doc = new Document(array(
            'col1' => '123',
        ));
        $id = 'testdoc123';
        $doc->setId($id);
        $this->assertEquals($id, $this->repo->store($doc));
        $this->assertEquals($id, $this->repo->store($doc));
        $this->assertEquals(array($id), $this->index->get('123', '=='));
    }

    public function testReStoreDocumentAdd()
    {
        $doc = new Document(array(
            'col2' => '123',
        ));
        $id = 'testdoc123';
        $doc->setId($id);
        $this->assertEquals($id, $this->repo->store($doc));
        $doc->col1 = '456';
        $this->assertEquals($id, $this->repo->store($doc));
        $this->assertEquals(array($id), $this->index->get('456', '=='));
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
        $this->assertEquals(array(), $this->index->get('123', '=='));
        $this->assertEquals(array($id), $this->index->get('456', '=='));
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
        $this->assertEquals(array(), $this->index->get('123', '=='));
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
        $this->assertEquals(array(), $this->index->get('123', '=='));
        $this->assertEquals(array($id), $this->index->get('456', '=='));
    }

    public function testExistingData()
    {
        $repo = new Repository(self::REPO_NAME, new Config(
            self::REPO_DIR
        ));
        $doc = new Document(array(
            'col1' => '123',
        ));
        $id = 'testdoc123';
        $doc->setId($id);
        $this->assertEquals($id, $repo->store($doc));
        $this->assertEquals(array($id), $this->index->get('123', '=='));
    }

    public function testDeepKey()
    {
        $id = 'testdoc123456';
        $doc = new Document(array(
            'col1' => '123',
            'col2' => array('4', '5', '6'),
        ));
        $doc->setId($id);

        $repo2 = new Repository(self::REPO_NAME, new Config(
            self::REPO_DIR, array(
                'indexes' => array(
                    'col2.0' => "JamesMoss\Flywheel\Index\HashIndex",
                )
            )
        ));

        $this->assertEquals($id, $this->repo->store($doc));
        $this->assertEquals($id, $repo2->store($doc));

        // test generating index from fs
        $index1 = new HashIndex('col2.0', self::REPO_PATH . '.indexes', new JSON(), $this->repo);
        $this->assertEquals(array($id), $index1->get('4', '=='));

        // test generating index on store document
        $index2 = $repo2->getIndexes()['col2.0'];
        $this->assertEquals(array($id), $index2->get('4', '=='));


    }

}