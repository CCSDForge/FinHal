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
     * @param array $params
     * @dataProvider provideConstruction
     */
    public function testConstruction($params)
    {
        $site = new Hal_Site_Portail($params);

        $this->assertEquals(Hal_Site::TYPE_PORTAIL, $site->getType());
        $this->assertEquals('Test', $site->getShortName());
        $this->assertEquals('Test en cours', $site->getFullName());
    }

    /**
     * @return array
     */
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
        $webSite = new Hal_Site_Collection([]);
        $this->assertEquals(Hal_Site::TYPE_COLLECTION, $webSite->getType());
        $webSite = new Hal_Site_Portail([]);
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
    }

    public function testSearch() {
        $res = Hal_Site::search("INRIA");
        $resIds = array_map(function ($x) { return $x['SID'];} , $res);
        $this->assertContains(423, $resIds);

        $res = Hal_Site::searchObj("INRIA");
        $resIds = array_map(function ($x) { /** @var Hal_Site $x */return $x->getSid();} , $res);
        $this->assertContains(423, $resIds);

    }
}