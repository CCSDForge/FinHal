<?php
/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 17/01/18
 * Time: 11:11
 */

class Hal_Site_Test extends PHPUnit_Framework_TestCase
{

    /**
     * Test de constructions de sites
     * @dataProvider provideConstruction
     */
    public function testConstruction($params)
    {
        $site = new Hal_Site($params);

        $this->assertEquals(Hal_Site::TYPE_UNDEFINED, $site->getType());
        $this->assertEquals('Test', $site->getSite());
        $this->assertEquals('Test en cours', $site->getName());
    }

    public function provideConstruction()
    {
        return [
          'Upper Case' => [['SITE' => 'Test', 'NAME' => 'Test en cours']],
          'Lower Case' => [['site' => 'Test', 'name' => 'Test en cours']]
        ];
    }

    /**
     * Test la validation de collection
     */
    public function testValidType()
    {
        $webSite = new Hal_Site([]);
        $webSite->setType('COLLECTION');
        $this->assertEquals(Hal_Site::TYPE_COLLECTION, $webSite->getType());
        $webSite->setType('PORTAIL');
        $this->assertEquals(Hal_Site::TYPE_PORTAIL, $webSite->getType());
    }

    /**
     * Teste deduire le type de site d'après le nom
     */
    public function testFindTypeFromName()
    {
        $res = Hal_Site::getTypeFromShortName('UNE_COLLECTION');
        $this->assertEquals(Hal_Site::TYPE_COLLECTION, $res);

        $res = Hal_Site::getTypeFromShortName('un-portail');
        $this->assertEquals(Hal_Site::TYPE_PORTAIL, $res);

        $res = Hal_Site::getTypeFromShortName('mixedCasePoRtalWhateVer');
        $this->assertEquals(Hal_Site::TYPE_PORTAIL, $res);

    }

    /**
     * Vérifie que le sid est un entier
     */
    public function testSetSidIsInt()
    {

        $webSite = Hal_Site::loadSiteFromId(1);
        $this->assertTrue(is_integer($webSite->getSid()));

        $this->assertEquals($webSite->getSid(), 1);

        $webSite->setSid('mystring');
        $this->assertEquals($webSite->getSid(), 0);

        $webSite->setSid("1");
        $this->assertEquals($webSite->getSid(), 1);

    }
}