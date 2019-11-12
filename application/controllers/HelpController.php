<?php

/**
 * Class HelpController
 *
 */
class HelpController extends Hal_Controller_Action
{
    /**
     *
     */
    public function indexAction()
    {
    	$this->renderScript('index/submenu.phtml');
    }
    /**
     *
     */
    public function informationAction() {
    	//Initialisation de la vue
    	$this->view->typdocsNotice    = Hal_Settings::getTypdocNotice();
    	$this->view->typdocsFulltext  = Hal_Settings::getTypdocFulltext();
    	$this->view->typdocs          = Hal_Settings::getTypdocs();
    }
}

