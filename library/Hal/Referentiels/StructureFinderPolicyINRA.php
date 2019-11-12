<?php

namespace Hal\Referentiels;

/**
 * Class StructureFinderPolicyINRA
 * @package Hal\Referentiels
 */
class StructureFinderPolicyINRA implements StructureFinderPolicy {
    /**
     * With knowledge of this document belong to the structure
     * what is the best structure in Hal we can take for those structure informations
     *
     * @param $document   \Hal_Document
     * @param $structureInfo StructureInfo
     * @return \Ccsd_Referentiels_Structure
     */
    public function getStructureInDocumentContext($document, $structureInfo) {
        return null;
    }
    /**
     * With knowledge of this author belong to the structure
     * what is the best structure in Hal we can take for those structure informations
     *
     * @param $author \Hal_Document_Author
     * @param $structureInfo StructureInfo
     * @return \Ccsd_Referentiels_Structure
     */
    public function getStructureInAuthorContext($author, $structureInfo) {
        return null;
    }
}