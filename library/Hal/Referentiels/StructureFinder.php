<?php

namespace Hal\Referentiels;

/**
 * Class StructureFinder
 * @package Hal\Referentiels
 */
class StructureFinder
{
    /** @var StructureFinderPolicy  */
    private $policy = null;
    /** @var StructureFinderPolicy  */
    private $altPolicy = null;
    /**
     * StructureFinder constructor.
     * @param $policy StructureFinderPolicy
     * @param $alternatePolicy StructureFinderPolicy
     * @use Hal\Referentiels\StructureFinderPolicyINRA
     */
    public function __construct($policy = null, $alternatePolicy = null)
    {
        if ($policy == null) {
            $this->policy = new StructureFinderPolicyDefault();
        } else {
            $this->policy = new $policy;
        }

        if ($alternatePolicy != null) {
            $this->altPolicy = new $policy;
        }
    }

    /**
     * With knowledge of this document belong to the structure
     * what is the best structure in Hal we can take for those structure informations
     *
     * @param $document   \Hal_Document
     * @param $structureInfo StructureInfo
     * @return \Ccsd_Referentiels_Structure
     */
    public function getStructureInDocumentContext($document, $structureInfo) {
        $res = $this->policy->getStructureInDocumentContext($document, $structureInfo);
        if (($res === null) && ($this->altPolicy != null)) {
            $res = $this->altPolicy->getStructureInDocumentContext($document, $structureInfo);
        }
        return $res;
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
        $res = $this->policy->getStructureInAuthorContext($author, $structureInfo);
        if (($res === null) && ($this->altPolicy != null)) {
            $res = $this->altPolicy->getStructureInAuthorContext($author, $structureInfo);
        }
        return $res;
    }
}