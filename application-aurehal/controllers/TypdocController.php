<?php

class TypdocController extends Aurehal_Controller_Referentiel
{
	public function init ()
	{
	    $this->_title = array(
			'browse'    => 'Consultation des types de document'
		);

		$this->_description = array (
				'browse'  => 'Ce module vous permet de consulter la liste des typdes de documents.'
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
        $typdoc = new Ccsd_Referentiels_Typdoc($code);

        if (! $typdoc->isValid($code)){
            $this->view->message = "Typde de document {$code} inexistant";
            return $this->renderScript('error/error.phtml');
        }

	    if (Hal_Rdf_Tools::requestRdfFormat($this->getRequest()) || $this->getRequest()->getParam('format') == 'rdf') {
            return parent::readAction();
        }

        $this->view->code = $code;
		$this->view->typdoc = $typdoc;
	}
	
}