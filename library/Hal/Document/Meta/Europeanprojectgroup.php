<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 16/12/16
 * Time: 11:32
 */
class Hal_Document_Meta_Europeanprojectgroup extends Hal_Document_Meta_Complex
{
    /**
     * Hal_Document_Meta_Europeanprojectgroup constructor.
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

        $this->_value[$group] = new Hal_Document_Meta_Europeanproject($key, $value, $group, $source, $uid, $status);;
    }
}
