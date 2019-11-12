<?php

/**
 * OAI Controler
 *
*/
class OaiController extends Zend_Controller_Action
{

    public function init ()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $action = strtolower($this->getRequest()->getActionName());
        if ( $action != 'xsl' ) {
            $website = Hal_Site::exist($action, Hal_Site::TYPE_PORTAIL, true);
            // detection du portail
            if ( !$website ) {
                $this->redirect('/docs/oai/');
            }
            Zend_Registry::set('website', $website);
            Hal_Site::setCurrentPortail($website);
            $website -> registerSiteConstants();
            $this->indexAction();
        }

    }

	public function indexAction()
	{
        return new Hal_Oai_Server($this->getRequest());
	}
	
	public function xslAction()
	{
		header('Content-Type: text/xml; charset=UTF-8');
		ob_clean();
		flush();
		echo Hal_Oai_Server::getXsl();
		exit;
	}
	
}

