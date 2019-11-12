<?php


namespace Hal;

/**
 * Class Patrol_Test
 * @package Hal
 */
class Patrol_Test extends \PHPUnit_Framework_TestCase
{
    /**
     *@throws
     */
    public function testConstruction()
    {
        $site = \Hal_Site::loadSiteFromId(1);
        $patrol = Patrol::construct("hal-00000001", $site);

        $this->assertEquals("hal-00000001", $patrol->getIdentifiant());
        $this->assertEquals(false, $patrol->isStatus());
        $this->assertEquals(1 , $patrol->getSiteid());

        $patrol->markPatrol(2);
        $this->assertEquals(true, $patrol->isStatus());
        $this->assertEquals(2, $patrol->getVersion());

        $patrol->unmarkPatrol();
        $this->assertEquals(false, $patrol->isStatus());
    }

    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    public function testSaveLoad() {
        $site = \Hal_Site::loadSiteFromId(1);
        /**  nettoyage */
        $patrol2 = Patrol::load("hal-00000001", $site);
        if ($patrol2) {
            $patrol2->delete();
        }
        $patrol = Patrol::construct("hal-00000001", $site);
        $patrol->save();
        $patrol2 = Patrol::load("hal-00000001", $site);
        $patrol2->delete();
    }

}