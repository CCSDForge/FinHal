<?php

require_once 'Hal/Application/Bootstrap/Bootstrap.php';
/**
 * Class Bootstrap
 */
class Bootstrap extends Hal_Application_Bootstrap_Bootstrap
{

    // Keep _initAutoload from parent
    // Keep _initDebug from parent
    // Keep _initConst from parent

    /** Argh... Order is important... so we must declare this firstly */
    protected function _initAutoload() {
        parent::_initAutoload();
    }

    protected function _initConfig() {
        parent::_initConfig();
    }

    protected function _initApplicationName()
    {
        Zend_Registry::set('APPLICATION_NAME', 'AUREHAL');
        parent::_initApplicationName();
    }

    /**
     * @return Zend_Db_Adapter_Abstract
     */
	protected function _initDb()
    {
        Zend_Db_Table::setDefaultAdapter($this->getPluginResource('db')->getDbAdapter());
        Zend_Db_Table_Abstract::getDefaultAdapter()->getConnection()->exec("SET NAMES 'utf8'");
        return Zend_Db_Table_Abstract::getDefaultAdapter();
    }

    protected function _initConst() {
        parent::_initConst();
    }

    /**
     *
     */
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
        $view->headTitle('AureHAL');

        $view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');
        $view->addHelperPath('Ccsd/View/Helper', 'Ccsd_View_Helper');
    	$view->addHelperPath('Hal/View/Helper', 'Hal_View_Helper');
    	$view->addHelperPath('Aurehal/View/Helper', 'Aurehal_View_Helper');
        /** @var Zend_Controller_Action_Helper_ViewRenderer $viewRenderer */
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

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

    protected function _initNav()
    {

    	//Chargement des Acl
    	/**  $acl
         * $acl = new Hal_Acl(); */
		$navigationFile = APPLICATION_PATH . '/configs/navigation.json';
    	/**
         * $acl->loadFromNavigation(array($navigationFile));
         *
         * echo $acl->write();exit;
    	 * $acl->write(APPLICATION_PATH . '/../data/' . RVCODE . '/config/', 'acl.ini');
         */
    	$config = new Zend_Config_Json($navigationFile);
        /** @var Zend_Controller_Action_Helper_ViewRenderer $viewRenderer */
    	$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
    	//Initialisation du menu
    	$viewRenderer->view->nav(new Ccsd_Navigation($config));
    	/**  ->setAcl($acl)
    	 * ->setRoles(Hal_Auth::getRoles(true));

    	 * return $acl; */
    }

    protected function _initAcl()
    {
		Zend_Controller_Front::getInstance()->registerPlugin(new Aurehal_Acl_Plugin(new Aurehal_Acl()));
    }
}

