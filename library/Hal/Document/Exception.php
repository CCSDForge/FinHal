<?php

/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 31/01/2017
 * Time: 17:05
 */
class Hal_Document_Exception extends Exception
{
    protected $_errors = [];

    public function setErrors($errors)
    {
        if (! is_array($errors)) {
            $errors = [];
        }
        $this->_errors = $errors;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

}