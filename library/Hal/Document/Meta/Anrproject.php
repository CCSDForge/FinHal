<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 16/12/16
 * Time: 11:32
 */
class Hal_Document_Meta_Anrproject extends Hal_Document_Meta_Object
{
    /**
     * Hal_Document_Meta_Anrproject constructor.
     * @param     $key
     * @param     Ccsd_Referentiels_Anrproject|int $value :
     *                  if value is an Ccsd_Referentiels_Anrproject , it is used as is
     *                  if value is an Id of Ccsd_Referentiels_Anrproject, it is loaded
     * @param     $group
     * @param     $source
     * @param     $uid
     * @param int $status
     */
    public function __construct($key, $value, $group, $source, $uid, $status)
    {
        parent::__construct($key, $value, $group, $source, $uid, $status);

        if ($value instanceof Ccsd_Referentiels_Anrproject) {
            $this->_value = $value;
        } else {
            $this->_value = (new Ccsd_Referentiels_Anrproject())->load($value);
        }
    }
}
