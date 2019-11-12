<?php

namespace Hal\Referentiels;

/**
 * Class StructureInfo
 * @package Hal\Referentiels
 *
 * Factory for getting structure information: the information can be string, array, or Ccsd_Referentiel_Structure
 *
 */
abstract class StructureInfo
{
    /**
     * @param $struct \Ccsd_Referentiels_Structure | array | string
     * @return StructureInfo
     * @throws Exception
     */
    public static function getStructureInfoByObject($struct) {
        return new StructureInfoObject($struct);
    }

    /**
     * @param $struct
     * @return StructureInfoObject
     * @throws \Exception
     *
     */
    public static function getStructureInfo($struct) {
        if(is_object($struct)) {
            $class = get_class($struct);
            switch ($class) {
                case "Hal_Document_Structure":
                    return new StructureInfoObject($struct);
                    break;
                default:
                    throw new \Exception("Can't give a StructureInfo from ");
            }
        } elseif (is_string($struct)) {
            return new StructureInfoString($struct);
        }
    }

    abstract public function getName() ;

    abstract public function getSigle();



}