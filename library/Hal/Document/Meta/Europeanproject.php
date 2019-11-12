<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 16/12/16
 * Time: 11:33
 */
class Hal_Document_Meta_Europeanproject extends Hal_Document_Meta_Object
{
    /**
     * Hal_Document_Meta_Europeanproject constructor.
     * @param     $key
     * @param Ccsd_Referentiels_Europeanproject|int $value :
     *                  if value is an Ccsd_Referentiels_Europeanprojec , it is used as is
     *                  if value is an Id of Ccsd_Referentiels_Europeanprojec, it is loaded
     * @param     $group
     * @param     $source
     * @param     $uid
     * @param int $status
     */
    public function __construct($key, $value, $group, $source, $uid, $status)
    {
        parent::__construct($key, $value, $group, $source, $uid, $status);

        if ($value instanceof Ccsd_Referentiels_Europeanproject) {
            $this->_value = $value;
        } else {
            $this->_value = (new Ccsd_Referentiels_Europeanproject())->load($value);
        }
    }
}
