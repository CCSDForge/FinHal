<?php

namespace Tests\Hal\Referentiels;

/**
 * Class StructureFinderTest
 * @package Tests\Hal\Referentiels
 */
class StructureFinderTest extends \PHPUnit_Framework_TestCase {
    /**
     * @throws \Hal\Referentiels\Exception
     */
    public function test1 () {
        $inraPolicy = new \Hal\Referentiels\StructureFinderPolicyINRA();
        $finder = new \Hal\Referentiels\StructureFinder($inraPolicy);
        $filename = __DIR__.'/../../../ressources/test_sword_preprint.xml';
        $document = \Hal_Document::loadFromTEIFile($filename);

        $structure = new \Ccsd_Referentiels_Structure([
            'STRUCTNAME' => 'Unité Mixte de Recherche sur le Fromage	',
            'SIGLE'      => '',
            'ADDRESS'    => 'Aurillac',
            'PAYSID'     => '',
            'TYPESTRUCT' => 'UMR',
            'URL'        => '',
            'SDATE'      => '',
            'EDATE'      => '',
            'IDEXT'      => ['INRA' => '0614', 'RNSR' => '199417885W'],
        ]);
        $structureInfo = \Hal\Referentiels\StructureInfo::getStructureInfoByObject($structure);
        $finder -> getStructureInDocumentContext($document, $structureInfo);

    }
}