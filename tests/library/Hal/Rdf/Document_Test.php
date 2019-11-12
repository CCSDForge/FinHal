<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Document_Test extends PHPUnit_Framework_TestCase
{


    public function testVerifException()
    {
        // Exception on inexistant docid
        $this->expectException('Hal_Rdf_Exception');
        $document = new Hal_Rdf_Document(2);
    }

    public function testVerifStructureRoot()
    {
        $document = new Hal_Rdf_Document(496254);
        $this->assertEquals('fabio:Article', $document->getElemRoot('ART'));
    }

    public function testVerifInit()
    {
        $document = new Hal_Rdf_Document(496254);
        $this->assertEquals('496254.rdf', $document->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/document/00/49/', $document->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $document = new Hal_Rdf_Document(496254);
        $rdf = $document->getRdf();

        $this->assertContains('<?xml version="1.0" encoding="UTF-8"?>', $rdf);
        $this->assertContains('https://data.archives-ouvertes.fr/document/hal-00496254', $rdf);
        $this->assertContains('hal:CorrespondingAuthor', $rdf);
        $this->assertContains('PEER_stage2_10.1016%252Fj.vetmic.2009.12.030.pdf', $rdf);
    }
}