<?php

/**
 * Class Ccsd_View_Helper_Gi
 */
class Ccsd_View_Helper_Gi  extends Zend_View_Helper_Abstract
{
    /**
     * @param $glyphType
     * @param string $altLabel
     * @return string
     */
    public function gi($glyphType='', $srOnly = '')
    {
        $res= '<span class="glyphicon ';
        if ($glyphType != '') {
            $res.= 'glyphicon-' . $glyphType;
        }
        $res.='" aria-hidden="true"></span>';
        if ($srOnly !='') {
            $res.='<span class="sr-only">' . $srOnly . '</span>';
        }
        return $res;
    }
}