<?php
/**
 * Created by PhpStorm.
 * User: bblondelle
 * Date: 03/04/19
 * Time: 16:13
 */

class Hal_Document_Meta_Researchdatagroup extends Hal_Document_Meta_Complex
{
    /** TODO : Tableau d'Objet ResearchData (Données associées champ multivalué) */

    /**
     * Hal_Document_Meta_Researchdatagroup constructor.
     * @param string $key
     * @param string $value
     * @param string $group
     * @param string $source
     * @param int    $uid
     * @param int    $status
     */
    public function __construct($key, $value, $group, $source, $uid, $status)
    {
        parent::__construct($key, $value, $group, $source, $uid, $status);
        $this->_value = [];

        if (!isset($value)) {
            return;
        }

        $this->_value[$group] = new Hal_Document_Meta_Researchdata($key, $value, $group, $source, $uid, $status);;
    }

    /**
     *  Retourne l'ensemble des objects meta de
     * @return Hal_Researchdata[]
     */
    public function getValuesObj() {
        $res = [];
        foreach ($this->_value as $metaobj) {
            /** @var Hal_Document_Meta_Researchdata $metaobj */
            $res [] = $metaobj-> getValueObj();
        }
        return $res;
    }
}