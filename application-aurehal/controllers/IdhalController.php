<?php

class IdhalController extends Aurehal_Controller_Referentiel
{
	public function init ()
	{
	    $this->_title = array(
			'browse'    => 'Consultation des IDHAL'
		);

		$this->_description = array (
				'browse'  => 'Ce module vous permet de consulter la liste des IDHAL'
		);
	}

	public function browseAction ()
	{
	}
	
	public function modifyAction () 
	{
		$this->browseAction();
	}
	
	public function createAction () 
	{
		$this->browseAction();
	}
	
	public function replaceAction ()
	{
		$this->browseAction();
	}

	public function readAction ()
	{
        $code = $this->getRequest()->getParam('id', null);

        if (! Hal_Cv::existUri($code)) {
            $this->view->message = "IDHAL {$code} inexistant";
            return $this->renderScript('error/error.phtml');
        }


	    if (Hal_Rdf_Tools::requestRdfFormat($this->getRequest()) || $this->getRequest()->getParam('format') == 'rdf') {
            return parent::readAction();
        }

        $idhal = new Hal_Cv(0, $code);
        $idhal->load(false);
        $this->view->code = $code;
		$this->view->idhal = $idhal;
	}
	
}