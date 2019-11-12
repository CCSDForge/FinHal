<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Idhal_Test extends PHPUnit_Framework_TestCase
{

    public function testVerifExceptionCreate()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        $author = new Hal_Rdf_Idhal("wrong");

    }

    public function testVerifExist()
    {
        $author = new Hal_Rdf_Idhal("laurentromary");
        $this->assertEquals(307, $author->getIdhal()->getIdHal());
    }

    public function testVerifInit()
    {
        $author = new Hal_Rdf_Idhal("laurentromary");
        $this->assertEquals('author', $author->getGraph());
        $this->assertEquals('laurentromary.rdf', $author->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/author/idhal/', $author->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $author = new Hal_Rdf_Idhal("laurentromary");
        $rdf = $author->getRdf();

        $this->assertContains('<?xml version="1.0" encoding="UTF-8"?>', $rdf);
        $this->assertContains('https://data.archives-ouvertes.fr/author/laurentromary', $rdf);
        $this->assertContains('ore:aggregates', $rdf);
        $this->assertContains('0000-0002-0756-0508', $rdf);
    }



}