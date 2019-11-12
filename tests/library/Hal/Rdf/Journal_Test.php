<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Journal_Test extends PHPUnit_Framework_TestCase
{

    public function testVerifExceptionCreate()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        $revue = new Hal_Rdf_Journal("WRONG");
    }

    public function testVerifInit()
    {
        $revue = new Hal_Rdf_Journal(7333);
        $this->assertEquals('revue', $revue->getGraph());
        $this->assertEquals('7333.rdf', $revue->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/revue/00/00/', $revue->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $revue = new Hal_Rdf_Journal(7333);
        $this->assertEquals(file_get_contents(RESSOURCESDIR . '/rdf/revue-7333.rdf'), $revue->getRdf());
    }



}