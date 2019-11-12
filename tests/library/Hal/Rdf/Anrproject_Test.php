<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Anrproject_Test extends PHPUnit_Framework_TestCase
{

    public function testVerifExceptionCreate()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        new Hal_Rdf_Anrproject("wrong");

    }

    public function testVerifExist()
    {
        $project = new Hal_Rdf_Anrproject(1);
        $this->assertEquals(1, $project->getId());
    }

    public function testVerifInit()
    {
        $project = new Hal_Rdf_Anrproject(1);
        $this->assertEquals('1.rdf', $project->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/anrProject/00/00/', $project->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $project = new Hal_Rdf_Anrproject(1);
        $this->assertEquals(file_get_contents(RESSOURCESDIR . '/rdf/anr-1.rdf'), $project->getRdf());
    }



}