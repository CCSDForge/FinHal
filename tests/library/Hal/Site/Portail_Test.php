<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 13/07/17
 * Time: 09:51
 */

class Hal_Site_Portail_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var Hal_Site_Portail
     */
    private $_portail = null;

    public function setup()
    {
        $this->_portail = new Hal_Site_Portail([
            'site' => 'Test',
            'name' => 'Test en cours',
            'id' => 'port-',
            'url' => 'http://testtest.truc.fr',
            'category' => Hal_Site::CAT_INSTITUTION,
            'contact' => 'prenom.nom@crns.fr',
            'imagette' => '1234'
        ]);
    }

    /**
     * Test de constructions de sites
     */
    public function testConstruction()
    {
        $this->assertEquals(Hal_Site::TYPE_PORTAIL, $this->_portail->getType());
        $this->assertEquals('Test', $this->_portail->getShortname());
        $this->assertEquals('Test en cours', $this->_portail->getName());
    }

    /**
     * Test du chargement lazy des settings du portail
     */
    public function testLazySettings()
    {
        $this->assertEquals(false, $this->_portail->areSettingsLoaded());
        $piwikid = $this->_portail->getSetting('piwikid');
        $this->assertEquals(true, $this->_portail->areSettingsLoaded());
        $this->assertEquals(0, $piwikid);
    }

    /**
     * Test de constructions de sites
     */
    public function testMauvaiseConstruction()
    {
        // to do : mettre des contrôles pour qu'on puisse pas faire ça
        $this->_portail->setId("nimp");

        $this->_portail->setUrl("nimp");

        $this->_portail->setCategory("nimp");

        $this->_portail->setContact("nimp");

        $this->_portail->setImagette("nimp");

    }
}