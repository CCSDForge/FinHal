<?php

class SettingsController extends Hal_Controller_Action
{

	public function indexAction()
	{
		$this->view->title = "Paramétrage";
		$this->renderScript('index/submenu.phtml');
	}

	public function typdocAction()
	{

        $request = $this->getRequest();

	    if ($request->isPost()) {
	        $params = $request->getPost();
            if (isset($params['typdoc-src']) && $params['typdoc-src'] == 'default') {
                Hal_Typdoc::init(SITEID);
            } else {
                $params['typdoc'] = Ccsd_Tools::ifsetor($params['typdoc'], array());

                if (isset($params['typdoc'])) {
                    $arr = array();
                    $update_ids = array();
                    $i = 0;

                    foreach ($params['typdoc'] as $typdoc) {

                        $tmp = explode('_', $typdoc['id'], 2);

                        if (count($tmp) == 2 && in_array($tmp[0], array('tmp', 'typdoc'))) {
                            $update_ids[$typdoc['id']] = 'typdoc_'.++$i;
                            $typdoc['id'] = $update_ids[$typdoc['id']];
                        }

                        if ($typdoc['parentid'] == 'root') {
                            $flag = &$arr[$typdoc['id']];
                        } else {
                            $parentid = isset($update_ids[$typdoc['parentid']]) ? $update_ids[$typdoc['parentid']] : $typdoc['parentid'];
                            $flag = &$arr[$parentid]['children'][$typdoc['id']];
                        }

                        $flag['id'] = $typdoc['id'];
                        foreach(Zend_Registry::get('languages') as $lang) {
                            $flag['labels'][$lang] = $typdoc[$lang];
                        }
                        $flag['type'] = $typdoc['type'];

                        if (isset($typdoc['check'])) {
                            $flag['check'] = 'on';
                        }
                    }
                    Hal_Typdoc::save(SITEID, $arr);
                    $this->redirect('/settings/typdoc');
                }
	        }
	    }
            
            $typdoc = new Hal_Typdoc(array('languages' => Zend_Registry::get('languages'), 'sid' => SITEID));
	    $this->view->list_default_typdoc = Hal_Settings::getListDefaultTypdoc();
	    $this->view->typdoc_list = $typdoc->getTypdoc();
	    $this->view->using_keys = $typdoc->getUsingKeys();
	}

	public function domainAction ()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
	    $domain = new Ccsd_Referentiels_Domain(SITEID);
	    $tableauDomain = $domain->arborescence();
    	$jsonDomain = $domain->creeArborescenceJson($tableauDomain);
	    // cas particulier de HAL qui a déjà été créé dans default
	    // PAs de remplacement dans default pour l'instant
	    if (SITEID != 1 ) {
	        $langueOri = Zend_Registry::get('lang');
    	    foreach(Zend_Registry::get('languages') as $lang)
    	    {

    	           Zend_Registry::get('Zend_Translate')->setLocale($lang);
    	           $tableauDomain = $domain->arborescence();
    	           $jsonDomain = $domain->creeArborescenceJson($tableauDomain);

    	           $filename = SPACE.CONFIG.'domains.'.$lang.'.json';
    	           $dir = substr($filename, 0, strrpos($filename, '/'));
    	           if (! is_dir($dir)) {
    	               mkdir($dir, 0777, true);
    	           }
    	           file_put_contents($filename,$jsonDomain);

    	    }
    	    Zend_Registry::get('Zend_Translate')->setLocale($langueOri);
	    }


	}


	public function typdocelementAction () {
	    $this->_helper->layout()->disableLayout();
    	$this->_helper->viewRenderer->setNoRender();

	    $request = $this->getRequest();
        if ($request->isPost()) {
	        $params = $request->getPost();
	        $this->view->id = $params['id'];
	        $this->view->type = $params['type'];
	        $this->view->list_default_typdoc = Hal_Settings::getDefaultTypdoc();
	        $this->render('typdoc-element');
	    }

	}

	public function metadataAction()
	{
            $this->view->referentials = Hal_Referentiels_Metadata::metaList((Hal_Auth::isHALAdministrator() ? null : SITEID), false);
            $this->view->displaySid = Hal_Auth::isHALAdministrator();
            
		if ($this->getRequest()->isPost() && ($metaname = $this->getRequest()->getParam('metaname', false)) !== false) {

    		$this->view->referential = $metaname;
    		$this->view->data = Hal_Referentiels_Metadata::getValues($metaname);               
            }

                if ($this->getRequest()->isXmlHttpRequest() && ($metaname = $this->getRequest()->getParam('metaname', false)) !== false && ($method = $this->getRequest()->getParam('method', false)) !== false) {
	            
                    $sid = Hal_Referentiels_Metadata::getSid($metaname);
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender();

                    if ($metaname !== false && in_array ($method, array('edit', 'add'))) {
	    		$form = new Ccsd_Form();

	    		foreach (Hal_Referentiels_Metadata::getValues($metaname) as $k => $v) {
	    			$form->addElement('hidden', "$k", array('value' => $v, 'belongsTo' => 'metaname'));
	    		}

	    		$form->addElement('multiTextSimpleLang', 'metadata', array (
	    				'label' => 'Valeur',
	    				'populate' => array_combine(Zend_Registry::get('languages'), Zend_Registry::get('languages')),
	    				'required' => true
	    		));
                    }

	    	/**
	    	 * SUPPRESSION D'UNE 'METAVALUE' POUR UNE 'METANAME'
	    	 */
                    if ($method == 'delete' && ($metavalue = $this->getRequest()->getParam('value', false)) !== false) {

                        if (!Hal_Document::existMetaValueFor($metaname, $metavalue)) {
                            Hal_Referentiels_Metadata::delete($metaname, $metavalue, $sid);

                            $path = SHARED_DATA  . LANGUAGES;

                            foreach (Zend_Registry::get('languages') as $lang) {
                                $filepath = $path . $lang . DIRECTORY_SEPARATOR . $metaname . ".php";

                                if (!file_exists ($filepath)) {
                                    continue;
                                }

                                $content = include $filepath;

                                if (!is_array ($content)) 
                                    $content = array();

                                    unset ($content[$metaname . "_" . $metavalue]);

                                    Ccsd_Tools::write_translations($filepath, $content);
                            }
                            echo "1";
                        } else {
                            echo "0";
                        }
                    }

	    	/**
	    	 * EDITION D'UNE 'METAVALUE' POUR UNE 'METANAME'
	    	 */
                    else if ($method == 'edit' && ($metavalue = $this->getRequest()->getParam('value', false)) !== false) {

	    		$values = array ();
	    		foreach (Zend_Registry::get('languages') as $k => $v) {
	    			$values[$v] = Zend_Registry::get('Zend_Translate')->translate($metaname . "_" . $metavalue, $v);
	    		}

	    		$form->getElement('metadata')->setValue($values);

	    		$form->setActions(true)->createSubmitButton("Modifier");

	    		if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

	    			$tmp  = $form->getValues();
	    			$path = SHARED_DATA  . LANGUAGES;

	    			foreach ($tmp['metadata'] as $lang => $value) {
	    				$filepath = $path . $lang . DIRECTORY_SEPARATOR . $metaname . ".php";

	    				if (!file_exists ($filepath)) {
                                            touch($filepath);
	    				}

	    				$content = include $filepath;

	    				if (!is_array ($content)) $content = array();

						$content[$metaname . "_" . $metavalue] = $value;

						Ccsd_Tools::write_translations($filepath, $content);
	    			}

	    			echo $tmp['metadata'][Zend_Registry::get('lang')] ? $tmp['metadata'][Zend_Registry::get('lang')] : $metaname . "_" . $metavalue;
	    		} else echo $form;

                    }

	    	/**
	    	 * AJOUT D'UNE 'METAVALUE' POUR UNE 'METANAME'
	    	 */
                    else if ($method == 'add') {
                    
			$form->setActions(true)->createSubmitButton("Ajouter");

	    		if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {

                            $path = SHARED_DATA  . LANGUAGES;
                            $tmp  = $form->getValues();
                            $metavalue = Hal_Referentiels_Metadata::getMaxValue($metaname) + 1;

                            foreach ($tmp['metadata'] as $lang => $value) {

                                $filepath = $path  . $lang . DIRECTORY_SEPARATOR . $metaname . ".php";
                                
                                if (!file_exists ($filepath)) {
                                    touch($filepath);
                                }

                                $content = include $filepath;

                                if (!is_array ($content)) {
                                    $content = array();
                                }

                                $content[$metaname . "_" . $metavalue] = $value;

                                Ccsd_Tools::write_translations($filepath, $content);
                            }

                            Hal_Referentiels_Metadata::addValue($metaname, $metavalue, $sid);

                            $this->view->traduction = $tmp['metadata'][Zend_Registry::get('lang')] ? $tmp['metadata'][Zend_Registry::get('lang')] : $metaname . "_" . $metavalue;
                            $this->view->code = $metavalue;

                            $this->render("row-metadata");

	    		} else echo $form;
	    	}

	    }

	}

	public function submitAction()
	{

	}

	/**
	 * Gestion des fichiers de configuration d'un portail
	 * @throws Zend_Form_Exception
	 */
	public function filesAction()
	{
		$this->view->form = Hal_Site_Settings_Portail::getFormConfEdition();
	}

	public function ajaxloadcontentAction()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

        //todo : tester
        $site = Hal_Site::loadSiteFromId(SITEID);
        $settings = new Hal_Site_Settings_Portail($site);
		echo $settings->getConfigFileContent($this->getRequest()->getPost('fileId'));
	}

}