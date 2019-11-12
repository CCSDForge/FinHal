<?php

/**
 * Class Hal_Application_Bootstrap_Bootstrap
 * Encapsulation for sharing Bootstrap operations between Zend applications
 */
class Hal_Application_Bootstrap_Bootstrap extends Zend_Application_Bootstrap_Bootstrap {

    protected function _initApplicationName() {
        Hal_Instance::getInstance(getenv('INSTANCE'));
        //error_log('Panic: Function _initApplicationName not defined in subclass');
        //exit(1);
    }

    protected function _initConfig() {
        \Hal\Config::init($this->getOptions());
    }

    /**
     * Chargement automatique des différents modèles
     *
     * @return Zend_Loader_Autoloader
     */
    protected function _initAutoload() {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->setFallbackAutoloader(true);
        return $autoloader;
    }

    /**
     * @return Zend_Session_Namespace
     */
    protected function _initSession() {
        // Initialisation de la *Class*.  L'instance ne sert a rien...
        return new Zend_Session_Namespace();
    }
    
    protected function _initDebug() {
        Zend_Registry::set('debug', ['user' => '', 'site' => '']);
    }

    protected function _initConst() {
        //Definition des constantes : CCSDLIB
        $glob = new Globales();
        foreach ($this->getOption('consts') as $key => $value) {
            if (!defined($key)) {
                define($key, $value);
                if ($glob -> is_recorded($key)) {
                    $glob->$key = $value;
                }
            }
        }
    }

    /**
     * Ajout des Helpers de vue
     *
     * @return Zend_View
     */
    protected function _initView() {
        $view = new Hal_View();
        $view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');
        $view->addHelperPath('Ccsd/View/Helper', 'Ccsd_View_Helper');
        $view->addHelperPath('Hal/View/Helper', 'Hal_View_Helper');
        /** @var Zend_Controller_Action_Helper_ViewRenderer $viewRenderer */
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);
        return $view;
    }
}
