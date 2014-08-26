<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class NestedRespositoryTest extends TestBase
{
    public function testStoringDocuments()
    {
        exec('rm -rf /tmp/flywheel/_pages');
        if (!is_dir('/tmp/flywheel')) {
            mkdir('/tmp/flywheel');
        }
        $config = new Config('/tmp/flywheel');
        $repo   = new NestedRepository('_pages', $config);

        for ($i = 0; $i < 5; $i++) {
            $data = array(
                'slug' => '123',
                'body' => 'THIS IS BODY TEXT'
            );

            $document = new Document($data);
            $document->setId($i . '/test/' . $i);

            $repo->store($document);

            $name = $i . '.json';
            $this->assertSame($data, (array) json_decode(file_get_contents('/tmp/flywheel/_pages/'.$i.'/test/' . $name)));
        }
    }

    public function testDeletingDocuments()
    {
        exec('rm -rf /tmp/flywheel/_pages');
        $config = new Config('/tmp/flywheel');
        $repo   = new NestedRepository('_pages', $config);
        $id     = 'delete_test/within/a/nested/directory';
        $name   = $id . '.json';
        $path   = '/tmp/flywheel/_pages/' . $name;

        mkdir(dirname($path), 0777, true);
        file_put_contents($path, '');

        $this->assertTrue(is_file($path));

        $repo->delete($id);

        $this->assertFalse(is_file($path));
    }

    public function testDeletingDocumentsAndEmptyDirs()
    {
        exec('rm -rf /tmp/flywheel/_pages');
        $config = new Config('/tmp/flywheel', array(
            'delete_empty_dirs' => true,
        ));
        $repo   = new NestedRepository('_pages', $config);
        $id     = 'delete_test/within/a/nested/directory';
        $name   = $id . '.json';
        $path   = '/tmp/flywheel/_pages/' . $name;

        mkdir(dirname($path), 0777, true);
        file_put_contents($path, '');

        $this->assertTrue(is_file($path));

        $repo->delete($id);

        $this->assertFalse(is_file($path));
        $this->assertFalse(file_exists(dirname($path)));
    }

    public function testGettingNestedDocuments()
    {
        exec('rm -rf /tmp/flywheel/_dummy');
        $config = new Config('/tmp/flywheel');
        $repo   = new NestedRepository('_dummy', $config);
        $IDs = array_flip(array(
            'some/asd/4364as/asa_sd',
            'some/asd/4364as',
            'asd/4364as/asa_sd',
            'some/asd/lklkljd/as',
            'nmnmn',
        ));

        $path   = '/tmp/flywheel/_dummy/';

        foreach($IDs as $id => $index) {
            if(!is_dir(dirname($path . $id))) {
                mkdir(dirname($path . $id), 0777, true);
            }

            file_put_contents($path . $id . '.json', json_encode(array('name' => 'Joe Bloggs')));
        }

        foreach($repo->findAll() as $doc) {
            $this->assertArrayHasKey($doc->getId(), $IDs);
            unset($IDs[$doc->getId()]);
        }
    }
}
