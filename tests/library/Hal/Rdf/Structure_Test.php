<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Structure_Test extends PHPUnit_Framework_TestCase
{

    public function init()
    {

    }

    public function testVerifExceptionCreate()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        $structure = new Hal_Rdf_Structure("wrong");
    }

    public function testVerifExceptionUnknown()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        $structure = new Hal_Rdf_Structure(1234567890123456789);
    }

    public function testVerifStructureExist()
    {
        $structure = new Hal_Rdf_Structure(1);
        $this->assertEquals(1, $structure->getStructure()->getStructid());
    }

    public function testVerifStructureRoot()
    {
        $structure = new Hal_Rdf_Structure(1);
        $this->assertEquals(Hal_Rdf_Schema::ORG_ORGANIZATION, $structure->getElemRoot());
    }

    /**
     * Vérifie que les attributs sont bien initialisés
     */
    public function testVerifInit()
    {
        $structure = new Hal_Rdf_Structure(1);
        $this->assertEquals('structure', $structure->getGraph());
        $this->assertEquals('1.rdf', $structure->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/structure/00/00/', $structure->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $structure = new Hal_Rdf_Structure(1);
        $this->assertEquals(file_get_contents(RESSOURCESDIR . '/rdf/structure-1.rdf'), $structure->getRdf());
    }



}