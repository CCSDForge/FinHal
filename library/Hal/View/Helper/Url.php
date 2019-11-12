<?php
require_once 'Zend/View/Helper/Url.php';
class Hal_View_Helper_Url extends Zend_View_Helper_Url {
    protected $multioptions = [];

	public function url(array $urlOptions = array(), $name = null, $reset = false, $encode = true) {
		if (defined ( 'SPACE_NAME' )) {
			if (PREFIX_URL == '/' && SPACE_NAME == 'AUREHAL') {
				return parent::url ( $urlOptions, $name, $reset, $encode );
			}
		}

		if ($urlOptions == array ()) {
			$urlOptions = array (
					'controller' => Zend_Controller_Front::getInstance ()->getRequest ()->getControllerName (),
					'action' => Zend_Controller_Front::getInstance ()->getRequest ()->getActionName ()
			);
		}

		$url = PREFIX_URL;
		if (isset ( $urlOptions ['controller'] )) {
			$url .= $urlOptions ['controller'] . '/';
		}
		$url .= (isset ( $urlOptions ['action'] ) ? $urlOptions ['action'] : 'index') . '/';
		unset ( $urlOptions ['controller'], $urlOptions ['action'] );

        $url .= '?';
        $sep = '';
		foreach ( $this -> multioptions as $option  ) {
            if (isset($urlOptions [$option])) {
                $url .= $sep . $urlOptions [$option];
                $sep = "&";
            }
            unset($urlOptions [$option]);
		}

		if ($encode === true) {
			$urlOptions = array_map ( 'urlencode', $urlOptions );
		}

        foreach ( $urlOptions as $option => $value ) {
            //Réécriture des paramètres en ?q=valeur&test=1 (> /q/valeur/test/1)
            $url .= $sep . $option . '=' . $value;
            $sep = '&';
		}

		return $url;
	}
}