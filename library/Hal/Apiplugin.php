<?php

/**
 * Plugin permettant l'initialisation du site (portail ou collection)
 * Permet de charger la navigation, les acl et définition des différentes constantes spécifiques à l'environnement
 *
 */
class Hal_Apiplugin extends Zend_Controller_Plugin_Abstract
{
	/**
	 * @param Zend_Controller_Request_Abstract
	 */
	public function  dispatchLoopStartup (Zend_Controller_Request_Abstract $request)
	{
            if ( $request->getControllerName() != 'search' ) {
                define('MODULE', Hal_Site_Portail::MODULE);
            } else {
                define('MODULE', ( preg_match('/^[A-Z0-9_-]+$/', $request->getActionName()) ) ? Hal_Site_Collection::MODULE : Hal_Site_Portail::MODULE);
            }
            define('SPACE_URL', '/public/');
            define('DEFAULT_SPACE_URL', '/default/');
            define('PREFIX_URL', '/');
            define('DEFAULT_CONFIG_ROOT', APPLICATION_PATH . '/../' . CONFIG );
            define('DEFAULT_CONFIG_PATH', DEFAULT_CONFIG_ROOT .  MODULE);
            define('SESSION_NAMESPACE', 'halapi-' . session_id());

            if ( $request->getControllerName() != 'sword' )
                define('SPACE', SPACE_DATA . '/'. MODULE . '/' . PORTAIL . '/');
            
            define('DEFAULT_CACHE_PATH', CACHE_ROOT . '/'. APPLICATION_ENV . '/'. MODULE . '/' . SPACE_DEFAULT);
                                    
            foreach (array(DEFAULT_CONFIG_PATH, DEFAULT_CACHE_PATH, DOCS_CACHE_PATH, PATHDOCS, PATHTEMPDOCS, CCSD_USER_PHOTO_PATH) as $dir) {
                if (!is_dir($dir)) {
                    $res = mkdir($dir, 0777, true);
                    if (!$res) {
                        error_log('API plugin: Error mkdir: ' . $dir);
                    }
                }
            }
	}
}
