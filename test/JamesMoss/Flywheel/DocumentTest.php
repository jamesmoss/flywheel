<?php

namespace JamesMoss\Flywheel;

use \JamesMoss\Flywheel\TestBase;

class DocumentTest extends TestBase
{
    public function testGettingId()
    {
        $doc  = new Document(array(
            'mike' => 'snow',
        ));

        $doc->setId('albums');
        $this->assertEquals('albums', $doc->getId());
    }

    public function testGettingInitialId()
    {
        $doc  = new Document(array(
            'mike' => 'snow',
        ));

        $doc->setId('albums');
        $this->assertEquals('albums', $doc->getInitialId());

        $doc->setId('singles');
        $this->assertEquals('albums', $doc->getInitialId());
    }
}
