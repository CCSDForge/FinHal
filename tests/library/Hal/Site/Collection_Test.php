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
        $this->assertEquals('TEST', $site->getSite());
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
        $this -> assertEquals([ "es" ], $newsite -> getLanguages());
        $this -> assertEquals('LABO', $newcoll -> getCategory());
    }
    /**  @afterClass */
    public function clean () {
        foreach (self::$colls as $coll) {
            $coll -> delete();
        }
    }
}