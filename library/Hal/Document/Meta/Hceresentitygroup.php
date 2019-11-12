<?php

class Hal_Document_Meta_Hceresentitygroup extends Hal_Document_Meta_Complex
{
    /**
     * Hal_Document_Meta_Hceresentitygroup constructor.
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

        $this->_value[$group] = new Hal_Document_Meta_Hceresentity($key, $value, $group, $source, $uid, $status);;
    }
}
