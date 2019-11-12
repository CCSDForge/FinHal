<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Typdoc_Test extends PHPUnit_Framework_TestCase
{

    public function testVerifExceptionCreate()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        $doctype = new Hal_Rdf_Typdoc("WRONG");
    }

    public function testVerifInit()
    {
        $doctype = new Hal_Rdf_Typdoc("ART");
        $this->assertEquals('doctype', $doctype->getGraph());
        $this->assertEquals('ART.rdf', $doctype->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/doctype/', $doctype->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $doctype = new Hal_Rdf_Typdoc("ART");
        $this->assertEquals(file_get_contents(RESSOURCESDIR . '/rdf/doctype-art.rdf'), $doctype->getRdf());
    }
}