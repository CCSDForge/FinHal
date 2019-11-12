<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Author_Test extends PHPUnit_Framework_TestCase
{

    public function init()
    {

    }

    public function testVerifExceptionCreate()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        $author = new Hal_Rdf_Author("wrong");

    }

    public function testVerifExceptionUnknown()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        $author = new Hal_Rdf_Author(1234567890);
    }

    public function testVerifExist()
    {
        $author = new Hal_Rdf_Author(49567);
        $this->assertEquals(49567, $author->getAuthor()->getAuthorid());
    }

    public function testVerifInit()
    {
        $author = new Hal_Rdf_Author(49567);
        $this->assertEquals('author', $author->getGraph());
        $this->assertEquals('49567.rdf', $author->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/author/00/04/', $author->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $author = new Hal_Rdf_Author(49567);
        $rdf = $author->getRdf();

        $this->assertContains('<?xml version="1.0" encoding="UTF-8"?>', $rdf);
        $this->assertContains('https://data.archives-ouvertes.fr/author/49567', $rdf);
        $this->assertContains('foaf:topic_interest', $rdf);
        $this->assertContains('foaf:familyName', $rdf);
    }



}