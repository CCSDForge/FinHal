<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 16/12/16
 * Time: 11:29
 */
class Hal_Document_Meta_Journal extends Hal_Document_Meta_Object
{
    /**
     * Hal_Document_Meta_Journal constructor.
     * @param string    $key
     * @param string    $value
     * @param string    $group
     * @param string    $source
     * @param int       $uid
     * @param int       $status
     */
    public function __construct($key, $value, $group, $source, $uid, $status)
    {
        parent::__construct($key, $value, $group, $source, $uid, $status);

        if (!isset($value)) {
            return;
        }
        if ($value instanceof Ccsd_Referentiels_Journal) {
            $this->_value = $value;
        } else {
            $this->_value = (new Ccsd_Referentiels_Journal())->load($value);
        }
    }

    public function __get($key) {
        return $this->getValueObj()->__get($key);
    }
}
