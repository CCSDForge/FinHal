<?php

/**
 * Class Hal_Arxiv_Exceptions
 *
 */
class Hal_Transfert_Exception extends Exception
{
    public $status;

    /**
     * Hal_Transfert_Exception constructor.
     * @param int     $status
     * @param string  $msg
     * @param string  $debug
     */
    public function __construct($status, $msg, $tracking = null, $debug='') {
        parent::__construct($msg);
        $this -> status = $status;
        $this -> debug  = $debug;
        $this -> tracking = $tracking;
    }
}