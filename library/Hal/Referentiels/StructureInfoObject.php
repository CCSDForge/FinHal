<?php


namespace Hal\Referentiels;

/**
 * Class StructureInfoObject
 * @package Hal\Referentiels
 *
 *
 */
class StructureInfoObject extends StructureInfo
{
    /** @var \Ccsd_Referentiels_Structure */
    private $structure = null;

    /**
     * StructureInfoObject constructor.
     * @param \Ccsd_Referentiels_Structure $obj
     * @throws Exception
     */
    public function __construct($obj)
    {
        if (is_a($obj, "Ccsd_Referentiels_Structure")) {
            $this->structure = $obj;
        } else {
            throw new Exception('Given param must be a Ccsd_Referentiels_Structure, get a ' .  get_class($obj));
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->structure->getStructname();
    }

    /**
     * @return string
     */
    public function getSigle()
    {
        return $this->structure->getSigle();
    }


}