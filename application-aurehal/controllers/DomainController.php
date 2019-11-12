<?php

class DomainController extends Aurehal_Controller_Referentiel
{
	public function init ()
	{
		$this->_title = array(
            'browse'    => 'Consultation des domaines'
		);

		$this->_description = array (
            'browse'  => 'Ce module vous permet de consulter la liste des domaines.'
		);
	}

	/**
	 * Recherche des projets ANR
	 */
	public function browseAction ()
	{
		/* @var $form Ccsd_Form */
		$form      = new Ccsd_Form();

		$form->addElement('thesaurus', 'domain', array (
			'data' => array(
				'class'  => 'Hal_Settings',
				'method' => 'getDomains',
				'args' => array('sid' => 1, 'lang' => $this->getRequest()->getParam('locale', 'fr'))
			),
			'typeahead_label' => 'Filtrer par nom',
			'typeahead_description' => 'Saisissez un mot pour accélérer votre recherche',
			'typeahead_height' => 'auto',
			'list_title' => '&nbsp;',
			'list_values' => '',
			'collapsable' => false,
			'selectable' => false,
			'prefix_translation' => 'domain_',
			'locale' => $this->getRequest()->getParam('locale', 'fr'),
			'decorators' => array (
				'Thesaurus',
				array ('decorator' => 'HtmlTag', 'options' => array('tag' => 'div', 'class'  => "col-md-12"))
			)
		));

        $this->_helper->layout()->title       = $this->_title['browse'];
        $this->_helper->layout()->description = $this->_description['browse'];

        $this->view->locale = $this->getRequest()->getParam('locale', 'fr');
        $this->view->form   = $form;
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
        $domain = new Ccsd_Referentiels_Domain();

        if (! $domain->isValidCode($code)){
            $this->view->message = "Domaine {$code} inexistant";
            $this->renderScript('error/error.phtml');
            return true;
        }

	    if (Hal_Rdf_Tools::requestRdfFormat($this->getRequest()) || $this->getRequest()->getParam('format') == 'rdf') {
            return parent::readAction();
        }

        $codeId = $domain->getId($code);

        $this->view->codeId = $codeId;
        $this->view->broader = $domain->getBroader($code);
        $this->view->narrower = $domain->getNarrower($codeId);
        $this->view->code = $code;
		$this->view->domain = $domain;
	}
	
}