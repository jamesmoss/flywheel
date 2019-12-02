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
        $this->index = new HashIndex('col1', $this->repo);
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->recurseRmdir(self::REPO_PATH);
    }

    public function testAddEntry()
    {
        $id = 'testdoc123';
        $this->index->update($id, '123', null);
        $this->assertEquals(array($id), $this->index->get('123', '=='));
    }

    public function testRemoveEntry()
    {
        $id = 'testdoc123';
        $this->index->update($id, '123', null);
        $this->assertEquals(array($id), $this->index->get('123', '=='));
        $this->index->update($id, null, '123');
        $this->assertEquals(array(), $this->index->get('123', '=='));
    }

    public function testUpdateEntry()
    {
        $id = 'testdoc123';
        $this->index->update($id, '123', null);
        $this->assertEquals(array($id), $this->index->get('123', '=='));
        $this->index->update($id, '456', '123');
        $this->assertEquals(array(), $this->index->get('123', '=='));
        $this->assertEquals(array($id), $this->index->get('456', '=='));
    }

    public function testGet()
    {
        $n = 4;
        for ($i=1; $i <= $n; $i++) {
            $id = "doc$i";
            $this->index->update($id, "val$i", null);
        }
        $this->assertEquals(array('doc1'), $this->index->get('val1', '=='));
        $this->assertEquals(array('doc1', 'doc2', 'doc4'), $this->index->get('val3', '!='));
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

    public function testMultidimentionalKey()
    {
        $id = 'testdoc456';
        $doc = new Document(array(
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

        // test generating index from fs
        $this->assertEquals($id, $this->repo->store($doc));
        $index1 = new HashIndex('col2.0', $this->repo);
        $this->assertEquals(array($id), $index1->get('4', '=='));
        $this->assertTrue($this->repo->delete($doc));

        // test generating index on store document
        $this->assertEquals($id, $repo2->store($doc));
        $index2 = $repo2->getIndexes()['col2.0'];
        $this->assertEquals(array($id), $index2->get('4', '=='));
        $id = 'doc456';
        $doc->setId($id);
        $this->assertEquals($id, $repo2->update($doc));
        $this->assertEquals(array($id), $index2->get('4', '=='));
        $this->assertTrue($repo2->delete($id));
        $this->assertEquals(array(), $index2->get('4', '=='));
    }

    public function testInconsitentData()
    {
        $doc1 = new Document(array('col1' => '1'));
        $doc1->setId('doc1');
        $doc2 = new Document(array('col2' => '2'));
        $doc2->setId('doc2');
        $doc3 = new Document(array('col1' => ''));
        $doc3->setId('doc3');
        $doc4 = new Document(array('col2' => '4'));
        $doc4->setId('doc4');
        $doc5 = new Document(array('col1' => ''));
        $doc5->setId('doc5');

        $repo2 = new Repository(self::REPO_NAME, new Config(self::REPO_DIR, array()));
        $query11 = $this->repo->query()->where('col1', '==', 1)->orderBy('__id');
        $query12 = $this->repo->query()->where('col1', '!=', 1)->orderBy('__id');
        $query21 = $repo2->query()->where('col1', '==', 1)->orderBy('__id');
        $query22 = $repo2->query()->where('col1', '!=', 1)->orderBy('__id');

        // test generating index from document files
        $this->assertEquals('doc1', $repo2->store($doc1));
        $this->assertEquals('doc2', $repo2->store($doc2));
        $this->assertEquals('doc3', $repo2->store($doc3));
        $this->assertEquals($query21->execute(), $query11->execute());
        $this->assertEquals($query22->execute(), $query12->execute());

        // test generating index on store document
        $this->assertEquals('doc4', $this->repo->store($doc4));
        $this->assertEquals('doc4', $repo2->store($doc4));
        $this->assertEquals('doc5', $this->repo->store($doc5));
        $this->assertEquals('doc5', $repo2->store($doc5));
        $this->assertEquals($query21->execute(), $query11->execute());
        $this->assertEquals($query22->execute(), $query12->execute());
    }

}