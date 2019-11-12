<?php
/**
 * Created by PhpStorm.
 * User: Barborini
 * Date: 4/08/17
 * Time: 16:24
 */
class Hal_Rdf_Europeanproject_Test extends PHPUnit_Framework_TestCase
{

    public function testVerifExceptionCreate()
    {
        $this->expectException(Hal_Rdf_Exception::class);
        new Hal_Rdf_Europeanproject("wrong");
    }

    public function testVerifExist()
    {
        $project = new Hal_Rdf_Europeanproject(116726);
        $this->assertEquals(116726, $project->getId());
    }

    public function testVerifInit()
    {
        $project = new Hal_Rdf_Europeanproject(116726);
        $this->assertEquals('116726.rdf', $project->getCacheName());
        $this->assertEquals(CACHE_ROOT . '/' . APPLICATION_ENV . '/rdf/europeanProject/00/11/', $project->getCachePath());
    }

    public function testVerifRdfContent()
    {
        $project = new Hal_Rdf_Europeanproject(116726);
        $this->assertEquals(file_get_contents(RESSOURCESDIR . '/rdf/european-116726.rdf'), $project->getRdf());
    }



}