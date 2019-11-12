<?php

/**
 * Class Oai2Controller
 * @package controllers
 *
 * Reimplementation de l'OAI mais avec un identifiant qui verifie la Norme!!!
 *
 * Copy: car pour l'instant, les controller ne sont pas dynamiquement loadable!!!!
 * Todo: pouvoir heriter de OaiController
 *
 */
class Oai2Controller extends Zend_Controller_Action
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
            Hal_Site::setCurrentPortail($website);
            Zend_Registry::set('website', $website);
            $website -> registerSiteConstants();
            $this->indexAction();
        }

    }

	public function xslAction()
	{
		header('Content-Type: text/xml; charset=UTF-8');
		ob_clean();
		flush();
		echo Hal_Oai_Server::getXsl();
		exit;
	}
	/**
     * @return Hal_Oai_Server
     */
    public function indexAction()
	{
        return new Hal_Oai_Server($this->getRequest(), "v2");
	}
}