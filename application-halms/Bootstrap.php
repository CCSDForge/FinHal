<?php

require_once 'Hal/Application/Bootstrap/Bootstrap.php';

class Bootstrap extends Hal_Application_Bootstrap_Bootstrap
{

    protected function _initApplicationName() {
        Zend_Registry::set('APPLICATION_NAME', 'HALMS');
        parent::_initApplicationName();
    }

    // Keep _initAutoload from parent
    // Keep _initDebug from parent
    // Keep from parent
    // Keep from parent

    protected function _initAutoload()
    {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        $autoloader->setFallbackAutoloader(true);
        return $autoloader;
    }

    protected function _initConfig() {
        parent::_initConfig();
    }

	protected function _initDb() 
    {
        Zend_Db_Table::setDefaultAdapter($this->getPluginResource('db')->getDbAdapter());
        Zend_Db_Table_Abstract::getDefaultAdapter()->getConnection()->exec("SET NAMES 'utf8'");
        return Zend_Db_Table_Abstract::getDefaultAdapter();
    }
      
    protected function _initConst()
    {
    	parent::_initConst();
        define('CACHE_PATH', CACHE_ROOT . '/' . APPLICATION_ENV . '/portail/hal');
        define('DOCS_CACHE_PATH', CACHE_ROOT . '/' . APPLICATION_ENV . '/docs/');
    }

    protected function _initSession ()
    {
    	// Actually start the session
    	Zend_Session::start();

    	$session = new Zend_Session_Namespace(SPACE_NAME);
    	 
    	Zend_Registry::set('session', $session);
    }
    
    /**
     * Ajout des Helpers de vue
     *
     * @return Zend_View
     */
    protected function _initView ()
    {
        $view = new Zend_View();
        
        $view->setEncoding('utf-8');
        $view->headMeta()->appendHttpEquiv('Content-Type', 'text/html; charset=utf-8');
        
        $view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');
        $view->addHelperPath('Ccsd/View/Helper', 'Ccsd_View_Helper');
    	$view->addHelperPath('Hal/View/Helper', 'Hal_View_Helper');
        
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        $acl = new Halms_Acl();
        $navigationFile =  APPLICATION_PATH . '/configs/navigation.json';
        $acl->loadFromNavigation(array($navigationFile));
        Zend_Registry::set('acl', $acl);
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        //Initialisation du menu
        $tmp = new Zend_Config_Json($navigationFile);
        $config = new Zend_Config($tmp->toArray());
        //Zend_Debug::dump(Halms_Auth::getRoles());exit;
        $viewRenderer->view->nav(new Ccsd_Navigation($config))
            ->setAcl($acl)
            ->setRoles(Halms_Auth::getRoles());

        return $view;
    }
    
    /**
     * Définition du DOCTYPE
     */
    protected  function _initDoctype()
    {
    	$doctypeHelper = new Zend_View_Helper_Doctype();
    	$doctypeHelper->doctype('XHTML1_STRICT');
    }
}