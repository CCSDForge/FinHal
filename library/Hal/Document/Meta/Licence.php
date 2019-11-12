<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 14/12/16
 * Time: 15:10
 */
class Hal_Document_Meta_Licence extends Hal_Document_Meta_Simple
{
    /**
     * @return bool
     */
    public function isValid() {
        return ($this->_value == '' || in_array($this->_value, Hal_Settings::getKnownLicences()));
    }
}
