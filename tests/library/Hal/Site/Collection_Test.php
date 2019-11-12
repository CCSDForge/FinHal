<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 13/07/17
 * Time: 09:51
 */

class Hal_Site_Collection_Test extends PHPUnit_Framework_TestCase
{
    protected static  $colls = [];

    /**
     * On s'assure qu'on peut jouer le test...
     * Destruction de la collection si elle existe deja
     */
    public function setUp()
    {
        /**
         * @var Hal_Site_Collection $coll
         */
        $coll = Hal_Site_Collection::exist('COPYOFTEST', Hal_Site::TYPE_COLLECTION, true);
        if ($coll) {
            $coll->delete();
        }
    }
    /** callback pour test
     * @param Hal_Site $c
     */
    public function  toId($c) {
        return $c->getSid();
    }
    /**
     * Test de constructions de sites
     */
    public function testConstruction()
    {
        $site = new Hal_Site_Collection([
            'SITE' => 'Test',
            'NAME' => 'Test en cours',
        ]);

        $this->assertEquals(Hal_Site::TYPE_COLLECTION, $site->getType());
        $this->assertEquals('TEST', $site->getShortname());
        $this->assertEquals('Test en cours', $site->getFullname());
    }

    /** Can go in Test of library CCSD Tools... */
    public function test_copy_tree() {
        try {
            $this -> assertTrue(Ccsd_Tools::copy_tree(__DIR__ . '/../../../ressources/DirTree', '/tmp/phpunitTest',0644,0755, ['/filePruned.*/', '/o.*pruned/']));
            $this->assertDirectoryExists('/tmp/phpunitTest/dir1');
            $this->assertDirectoryExists('/tmp/phpunitTest/dir2');
            $this->assertDirectoryExists('/tmp/phpunitTest/dir1/dir3');
            $this->assertFileExists('/tmp/phpunitTest/dir1/dir3/File3');
            $this->assertFileNotExists('/tmp/phpunitTest/dir1/dir3/otherFile_pruned');
        } finally {
            Ccsd_Tools::rrmdir('/tmp/phpunitTest');
        }
    }

    public function test_duplicate() {
        $coll = Hal_Site::exist('LKB', Hal_Site::TYPE_COLLECTION, true);
        $collTargetInfo=[
            'Site' => 'COPYOFTEST',
            'Name' => "Duplication de test",
            'Url' => "http://Labas.com/foo_coll" ];
        $target = new Hal_Site_Collection($collTargetInfo);

        $coll -> duplicate($target);
        $newcoll = Hal_Site::exist('COPYOFTEST', Hal_Site::TYPE_COLLECTION, true);
        self::$colls[] = $newcoll;
        $newsite = Hal_Site::loadSiteFromId($newcoll->getSid());
        $this -> assertEquals([ "fr", "en" ], $newsite -> getLanguages());
        $this -> assertEquals('LABO', $newcoll -> getCategory());
    }
    /**  @afterClass */
    public function clean () {
        foreach (self::$colls as $coll) {
            $coll -> delete();
        }
    }

    public function test_getParentCollections() {
        $coll1 = Hal_Site_Collection::loadSiteFromId(2709);
        $parents = $coll1 -> getParents();
        $parentsName = array_map(function ($s) { /** @var Hal_Site $s */ return $s->getShortname(); }, $parents);
        $this->assertContains('UNIV-PARIS1', $parentsName);
        $this->assertContains('UPEC', $parentsName);
        $this->assertContains('CV_LGP', $parentsName);

        $parentsAuto = $coll1->getParentCollections();
        $parentsName = array_map(function ($s) { /** @var Hal_Site $s */ return $s->getShortname(); }, $parentsAuto);
        $this->assertContains('UNIV-PARIS1', $parentsName);
        $this->assertNotContains('UPEC', $parentsName);

    }

    public function test_isAuto() {
        $coll1 = Hal_Site_Collection::loadSiteFromId(3025);
        $this -> assertEquals(false, $coll1 -> isAuto());
        $coll2 = Hal_Site_Collection::loadSiteFromId(114);
        $this -> assertEquals(true, $coll2 -> isAuto());
    }

    public function test_getAncestors() {
        $coll1 = Hal_Site_Collection::loadSiteFromId(3504);
        $ids = array_map(function($c) { /** @var Hal_Site $c */return $c->getSid(); }, $coll1->getAncestors());
        $this -> assertContains(3481, $ids);
        $this -> assertContains(3478, $ids);
    }
    public function test_getCollections() {
        $collections = Hal_Document_Collection::getCollections(144);
        $ids = array_map([$this, 'toId'] , $collections);
        $this->assertContains(221, $ids);
        $this->assertContains(1052, $ids);
        $this->assertNotContains(1478458, $ids);
    }

    public function test_addTampon() {
        $site = Hal_Site::loadSiteFromId(221);
        $collections = Hal_Document_Collection::getCollections(144);
        $ids = array_map([$this, 'toId'] , $collections);
        $this->assertContains(221, $ids);

        Hal_Document_Collection::del(144, $site);
        $collections = Hal_Document_Collection::getCollections(144);
        $ids = array_map([$this, 'toId'] , $collections);
        $this->assertNotContains(221, $ids);

        Hal_Document_Collection::add(144, $site);
        $collections = Hal_Document_Collection::getCollections(144);
        $ids = array_map([$this, 'toId'] , $collections);
        $this->assertContains(221, $ids);

    }
}