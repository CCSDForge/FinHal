<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Domain_Test extends PHPUnit_Framework_TestCase
{

    public function testVerifExceptionCreate()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        $domain = new Hal_Rdf_Domain("wrong");
    }

    public function testVerifInit()
    {
        $domain = new Hal_Rdf_Domain("phys");
        $this->assertEquals('subject', $domain->getGraph());
        $this->assertEquals('phys.rdf', $domain->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/subject/', $domain->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $domain = new Hal_Rdf_Domain("phys");
        $this->assertEquals(file_get_contents(RESSOURCESDIR . '/rdf/subject-phys.rdf'), $domain->getRdf());
    }



}