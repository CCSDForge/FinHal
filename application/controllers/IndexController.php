<?php

class IndexController extends Hal_Controller_Action
{
    protected $_counter = 0;

    /**
     * Page d'accueil du site
     */
    public function indexAction ()
    {
        $this->view->controller = 'index';
    	$this->forward('index', 'page');
    }
    
    public function langAction()
    {
    	$this->_helper->layout()->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();
    	echo Zend_Json::encode(Zend_Registry::get('Zend_Translate')->getMessages('all'));
    }

}

