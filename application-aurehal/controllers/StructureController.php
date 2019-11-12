<?php

class StructureController extends Aurehal_Controller_Referentiel 
{
	const STEP_1 = 'METADATA';
	const STEP_2 = 'PARENTS';
	const STEP_3 = 'RECAP';
	
	public function init ()
	{
		$this->_title = array(
			'browse'    => 'Consultation des structures de recherche',
			'modify_1'  => 'Modification d\'une structure',
			'modify_2'  => 'Structure modifiée',
			'create_1'  => 'Création d\'une structure',
			'create_2'  => 'Impossible de créer la structure',
			'create_3'  => 'Structure créée',
			'replace_1' => 'Remplacement des structures',
			'replace_2' => 'Résumé des modifications des structures',
			'transfer_1' => 'Transfert d\'une structure vers une autre + Fermeture',
			'transfer_2' => 'Résumé du transfert d\'une structure',
			'transfer_3' => 'Résultat du transfert d\'une structure',
			'read'     => 'Fiche d\'une structure'
		);
	
		$this->_info = array (
			'info_1' => 'Les modifications de la structure ? ont été prises en compte',
			'info_2' => 'La réindexation des données peut prendre quelques minutes pour être effective lors de la consultation de nos portails.',
			'info_3' => 'Vous essayer de creer un doublon d\'une structure ?',
			'info_4' => 'La structure ? a été créé',
			'info_5' => 'La réindexation peut prendre quelques minutes, la structure peut apparaitre après quelques minutes sur nos portails.'
		);
	
		$this->_description = array (
			'browse'  => 'Ce module vous permet de consulter la liste des structures.',
			'create'  => 'Ce module vous permet de créer de nouvelles structures.',
			'replace' => 'Ce module vous permet de remplacer des structures non valides par une structure valide.',
            'transfer' => 'Ce module vous permer de remplacer une structure A par une autre structure B. Les structures filles A seront fermées et dupliquées pour être associées à la structure B.',
			'modify'  => 'Ce module vous permet de modifier une structure existante en 3 étapes : 1 - modification des informations sur la'
		        . ' structure, 2- modification et suppression d\'affiliation existante, 3 - ajout d\'une nouvelle affiliation présente dans notre référentiel.'
		);
	
		$this->_name          = 'Structure';
		$this->_partial_form  = "structure/form.phtml";
		$this->_partial_form_r  = "structure/form_r.phtml";
		$this->_class         = 'Ccsd_Referentiels_Structure';
		$this->_head_columns  = array ('id', 'name', 'sigle', 'typestruct', 'adresse', 'url', 'ACTIONS');
		$this->_columns_solR  = array ("id"=>"docid", "name"=>"name_s", "sigle"=>"acronym_s", "adresse" => "address_s", "url" => "url_s", "typestruct" => "type_s", "valid" => "valid_s");
		$this->_columns_dB    = array ("id"=>"STRUCTID", "name"=>"STRUCTNAME", "sigle" => "SIGLE", "adresse" => "ADDRESS", "url" => "URL", "typestruct" => "TYPESTRUCT", "valid" => "VALID", "locked" => "LOCKED");
	}	

	public function indexAction()
	{
		Zend_Session::namespaceUnset(SPACE_NAME);
		parent::indexAction();
	}
	 
	public function browseAction ()
	{
		if ($this->getRequest()->isXmlHttpRequest()) {
			$this->_helper->layout()->disableLayout();
			
			$this->_form = $this->getForm ($this->_class, "/browse");
			
			$this->search();

			$this->renderScript('structure/ajaxbrowse.phtml');
		} else {
		    parent::browseAction();
        }
	}
	
	public function createAction ()
	{
        $request = $this->getRequest();
        if (($id = $request->getParam('id', 0)) != 0 && ($request->getParam('do', false)) == 'restore') {
			if (($obj = $this->find($id)) instanceof Ccsd_Referentiels_Abstract) {
                Zend_Session::namespaceUnset(SPACE_NAME);
                /** @var Ccsd_Referentiels_Structure $obj */
				$data = $obj->toArray();
				$parents = $data['parents'];
		
				unset ($data['STRUCTID']);
				unset ($data['DATEMODIF']);
				unset ($data['parents']);
		
		
				$obj = new Ccsd_Referentiels_Structure();
				$obj->set ($data);
		
				Zend_Registry::get('session')->structure =$obj;
		
                foreach ($parents as $i => &$parent) {
					$o = new Ccsd_Referentiels_Structure($parent['struct']['STRUCTID']);
					if (!$o->isValid()) {
						if (!isset (Zend_Registry::get('session')->invalid_parents)) {
							Zend_Registry::get('session')->invalid_parents = array ();
						}
						Zend_Registry::get('session')->invalid_parents[] = $parent;
						unset ($parents[$i]);
					} else {
					    $parent['struct'] = $o;
                    }
				}
		
				Zend_Registry::get('session')->parents = $parents;
		
				$request->clearParams();
		
			} else {
			    throw new Ccsd_Referentiels_Exception_StructureException ();
            }
		}
		
		
		
		/*if (($id = $this->getRequest()->getParam('id', false)) !== FALSE) {
			$structure = Zend_Registry::get('session')->structure;
			if (isset ($structure)) {
				if (array_key_exists('struct', $structure)) {
					$structure = $structure['struct'];
				}
			}
			
			if (! ($structure instanceof Ccsd_Referentiels_Structure && $structure->getStructid() == $id)) {
				Zend_Session::namespaceUnset(SPACE_NAME);
			}
		}*/
		 
		Zend_Registry::set('actionName', 'create');
		
		$this->modify('create');
 	}

    public function substitute ($id_from, Ccsd_Referentiels_Abstract $from, $id_to, Ccsd_Referentiels_Abstract $to)
	{
		/* @var $to Ccsd_Referentiels_Structure */

		$structids = array ();
		$o = new RecursiveIteratorIterator (new RecursiveArrayIterator($to->toArray()));
		
		foreach ($o as $k => $v) {
			if ('STRUCTID' == $k) {
				$structids[] = $v;
			}
		}

		unset ($o);
		
		if (!in_array($from->getStructid(), $structids)) {
            try {
                Ccsd_Referentiels_Alias::add($id_to, $to::$core, $id_from, '', new Zend_Db_Expr('UNHEX("' . $from->getMd5() . '")'));
            } catch (Zend_Db_Exception $e) {
                error_log('Error add REF_ALIAS - to:' . $id_to . ', from:' . $id_from);
            }
		    $from->fusion($id_to);
		}
	}
 	
	public function modifyAction ()
	{	
		Zend_Registry::set('actionName', 'modify');
		
		if (($id = $this->getRequest()->getParam('id', 0)) != 0 
			&& isset(Zend_Registry::get('session')->structure) 
			&& Zend_Registry::get('session')->structure instanceof Ccsd_Referentiels_Structure
			&& Zend_Registry::get('session')->structure->getStructid() != $id) {
			Zend_Session::namespaceUnset(SPACE_NAME);
		}
		
		$this->modify();
	}

	private function modify ($action = 'modify')
	{
	    if ($action == 'modify'){
            $this->_helper->layout()->title = $this->_title['modify_1'];
            $this->_helper->layout()->description = $this->_description['modify'];
        } else {
            $this->_helper->layout()->title = $this->_title['create_1'];
            $this->_helper->layout()->description = $this->_description['create'];
        }

	
		if (!isset (Zend_Registry::get('session')->steps)) {
			Zend_Registry::get('session')->steps = array (
			self::STEP_1 => false,
			self::STEP_2 => false,
			self::STEP_3 => false
			);
		}
	
		if (!isset ($this->view->step)) {
			$this->view->step = self::STEP_1;
		}
	
		foreach (Zend_Registry::get('session')->steps as $k => $v) {
			if (!$v) {
				$this->view->step = $k;
				break;
			}
		}
	
		$step_ask = $this->getRequest()->getParam('step', $this->view->step);
	
		if ($step_ask != $this->view->step && !$this->getRequest()->isPost()) {
	
			$step_found = false;
	
			foreach (Zend_Registry::get('session')->steps as $k => $v) {
				if (!$v && !$step_found) {
					$this->view->step = $k;
					break;
				}
	
				if ($k == $step_ask && !$step_found) {
					$step_found = true;
					$this->view->step = $step_ask;
					Zend_Registry::get('session')->prev = $step_ask;
				}
	
				if ($step_found) {
					Zend_Registry::get('session')->steps[$k] = false;
				}
			}
	
		}

		$previous_step = $this->view->step;
	
		switch ($this->view->step) {
			case self::STEP_3 :
				$this->recapitulatif();
                if ($previous_step != $this->view->step)
					$this->render('modify');
				break;
			case self::STEP_2 :
				$this->modifyParents();
                if ($previous_step != $this->view->step)
                    $this->render('modify');
                break;
			case self::STEP_1 :
				$this->modifyMetadata();
				$this->render('modify');
				break;
			default:
                break;
		}


	}
	
	private function modifyMetadata ()
	{
	    /** @var Zend_Controller_Request_Http $request */
	    $request = $this->getRequest();
	    /** @var Ccsd_Referentiels_Structure $obj */
        $obj = new $this->_class();

        //Chargement de l'objet structure avant la création du formulaire
        if (isset (Zend_Registry::get('session')->structure)) {
            $obj = Zend_Registry::get('session')->structure;
        } else if (($id = $request->getParam('id', 0)) != 0) {
            $obj->load($id);
        }
        if ($obj->getLocked() && !Hal_Auth::canModifyStructLock($id)) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("La structure est verrouillée");
            $this->redirect('structure/read/id/'.$obj->getStructId());
            return;
        }

        $form = $obj->getForm(true);
        $form -> setAction('/structure/modify');
        $form -> setActions(true);
        $form -> createSubmitButton('modify', array(
									"label" => Ccsd_Form::getDefaultTranslator()->translate("Suivant"),
									"class" => "btn btn-primary",
									"style" => "margin-top: 15px;"
								) );
        $this->_form = $form;
        $this->_form->addElement('hidden', 'step')->getElement('step')->setValue('ok');
		/**
		 * @TODO why value = ok ?
		 */

		if (isset (Zend_Registry::get('session')->structure)) {
            $this->view->locked = $obj->getLocked();
            $this->_form->populate($obj->toArray());
			$this->_form->setAction(URL . "/" . $this->getRequest()->getControllerName() . "/modify/id/" . $obj->getStructId());
		} else if (($id = $this->getRequest()->getParam('id', 0)) != 0) {
            $this->view->locked = $obj->getLocked();
            $this->_form->populate($obj->toArray());
			$this->_form->setAction(URL . "/" . $this->getRequest()->getControllerName() . "/modify/id/$id");
		} else {
			$this->_form->setAction(URL . "/" . $this->getRequest()->getControllerName() . "/create/id/0");

			$this->_helper->layout()->title = $this->_title['create_1'];
			$obj = new $this->_class();
		}

        if ($request->isPost()) {
            $isValid = $this->_form->isValid($request->getPost());
            $nbInvalidStruct = $obj->getNbInvalidStruct(0, $request->getParam('TYPESTRUCT'));

            if ($nbInvalidStruct > 0) {
                $error = $this->view->translate("1 ou plusieurs structures dépendent de la structure modifiée") . $this->view->translate(" et ne seront donc plus valides");
                $this->_form->getElement('TYPESTRUCT')->addError($error);
                $this->view->form = $this->_form;
            } else if ($isValid) {
                $obj->set($this->_form->getValues());

                Zend_Registry::get('session')->structure = $obj;
                Zend_Registry::get('session')->steps[self::STEP_1] = true;
                Zend_Registry::get('session')->prev = self::STEP_1;

                $this->{Zend_Registry::get('actionName') . "Action"}();
            } else {
                $this->view->form = $this->_form;
            }
        } else {
            $this->view->form = $this->_form;
        }
	}

	private function modifyParents ()
	{
		if (isset(Zend_Registry::get('session')->parents) && $this->getRequest()->isPost() && Zend_Registry::get('session')->prev == self::STEP_1) {

			$this->view->parents = Zend_Registry::get('session')->parents;

		} else if ($this->getRequest()->isPost() && Zend_Registry::get('session')->prev == self::STEP_2) {
			
			$parents = $this->getRequest()->getParam('parents', false);

			$typestruct = Zend_Registry::get('session')->structure->getTypestruct();
			
			$forbidden = array ();
			$errors    = array ();
			
			foreach (array ('researchteam', 'department', 'laboratory', 'regrouplaboratory', 'institution', 'regroupinstitution') as $name) {
				if ($name == $typestruct) {
					array_push ($forbidden, $name);
					break;
				}
				
				array_push ($forbidden, $name);
			}
			
			$hasForbidden   = false;
			$hasInstitution = false;
			
			function verifiy_parents ($parents, &$hasForbidden, &$errors, &$hasInstitution, &$forbidden)
			{
				foreach ($parents as $p) {
					
					if ($p['struct'] instanceof Ccsd_Referentiels_Structure) {
						$obj = $p['struct'];
					} else {
					    $obj = new Ccsd_Referentiels_Structure($p['struct']);
                    }

					if (in_array ($obj->getTypestruct(), $forbidden)) {
						$hasForbidden = true;
						$errors[] = $p['struct'];
					}
					if ($obj->getTypestruct() == 'institution' || $obj->getTypestruct() == 'regroupinstitution') {
						$hasInstitution = true;
					}
					
					if ($obj->getParents()) {
						verifiy_parents($obj->getParents(), $hasForbidden, $errors, $hasInstitution, $forbidden);
					}
				}
			}
			
			if (is_array ($parents) && !empty ($parents)) {
				verifiy_parents($parents, $hasForbidden, $errors, $hasInstitution, $forbidden);
			}

			if (!$hasForbidden) {
				if ($typestruct != 'institution' && $typestruct != 'regroupinstitution' && !$hasInstitution) {
					$this->view->message = "La structure doit être au minimum rattachée à une institution";
                } else {
					Zend_Registry::get('session')->parents = $parents;
					Zend_Registry::get('session')->steps[self::STEP_2] = true;	
					Zend_Registry::get('session')->prev = self::STEP_2;

					$this->{Zend_Registry::get('actionName') . "Action"} ();
				}
			} else {
				$this->view->message = "Une structure ne peut pas avoir d'affiliations de même type ou inférieure";
				$this->view->messageClass = 'alert-danger';
			}

			$this->view->errors  = $errors;
			$this->view->parents = $parents;
		}

		$this->view->structure = Zend_Registry::get('session')->structure;
		Zend_Registry::get('session')->prev = self::STEP_2;
    }
	
	private function recapitulatif ()
	{
	    /** @var Ccsd_Referentiels_Structure $structure */
		$structure = Zend_Registry::get('session')->structure;
		$structure->removeParents();
		$structure->set(array('parents' => Zend_Registry::get('session')->parents));
        $this->view->nbInvalidStruct = $structure->getNbInvalidStruct();
        $this->view->canSave = $this->view->nbInvalidStruct == 0;
		$this->view->structure_final = $structure;
		
		Zend_Registry::get('session')->prev = self::STEP_3;

		if ($this->getRequest()->isPost() && $this->getRequest()->getParam('save_modification', false) !== false) {
			$this->forward('read', $this->getRequest()->getControllerName() , null, array ('id' => $structure->save()));
		}
	}

    /**
     * Fonction de transfert d'une structure
     * Ferme et remplace une structure A par une nouvelle structure B
     * Les enfants de la structure A seront fermés et dupliqués pour être associé à la structure B
     */
    public function transferAction ()
    {
        $this->_helper->layout()->title = $this->_title['transfer_1'];
        $this->_helper->layout()->description = $this->_description['transfer'];

        Zend_Registry::set('actionName', 'transfer');

        $row = $this->getRequest()->getParam('row', false); //Etape 1
        $id = $this->getRequest()->getParam('dest', false); //Etape 2
        $transfer = $this->getRequest()->getParam('to_transfer', false); //Etape 3

        if ($row !== false){
            $this->_form = $this->getForm($this->_class, "/transfer");
            /** @var Ccsd_Referentiels_Structure $oldStruct */
            $oldStruct = new $this->_class($row);

            $this->view->class = $this->_class;
            $this->view->name = $this->_name;
            $this->view->partial_form   = $this->_partial_form_r;
            $this->view->controllerName = $this->getRequest()->getControllerName();

            if ($id !== false) {
                $id = (int) $id;
                $newStruct 		= new $this->_class($id);
                // on vérifie que l'objet cible existe toujours en base
                if ((isset($newStruct)) && (!$newStruct->exist($id))) {
                    $this->view->newstruct = $newStruct::$core." indexé(e), mais non trouvé(e) dans la base";
                    $this->view->id = -1;
                    $this->view->trouve = -1;
                    $this->view->params   = $this->getParams();
                    unset($newStruct);
                    $this->view->oldstruct   = $oldStruct;
                    $this->view->form     = $this->_form;
                    $this->_helper->layout()->title = $this->_title['transfer_1'];
                    $this->renderScript('structure/transfer.phtml');
                    return;
                }

                if ($transfer !== false) {
                    $arrayTransfer = $oldStruct->transferStruct($newStruct);

                    $newChild = $arrayTransfer['newchild'];
                    $oldChild = $arrayTransfer['oldchild'];

                    //Transmet à la vue récap toutes les infos
                    $this->view->newstruct = $newStruct;
                    $this->view->oldstruct = $oldStruct;
                    $this->view->newchild = $newChild;
                    $this->view->oldchild = $oldChild;


                    $this->_helper->layout()->title = $this->_title['transfer_3'];
                    $this->renderScript('structure/result.phtml'); //Vue Récapitulative

                } else {
                    $this->view->oldstruct = $oldStruct;
                    $this->view->id = $id;
                    $this->view->params = $this->getParams();
                    if (isset($newStruct)) {
                        $this->view->newstruct = $newStruct;
                    }
                    else {
                        $this->view->newstruct = $this->_name." indexé(e), mais non trouvé(e) dans la base";
                        $this->view->id = -1;
                        $this->view->trouve = -1;
                    }

                    $this->_helper->layout()->title = $this->_title['transfer_2'];
                    $this->renderScript('structure/resume.phtml'); //Vue Confirmation
                }

            } else {
                if ($this->getRequest()->getParam('search', false) || $this->getRequest()->getParam('searching', false)) {
                    $this->search(true);
                }

                $this->view->oldstruct = $oldStruct;
                $this->view->form = $this->_form;
                $this->_helper->layout()->title = $this->_title['transfer_1'];

                $this->renderScript('structure/transfer.phtml'); //Vue Choix du transfert
            }

        } else {
            $this->browseAction();
        }
    }

	protected function getParams ()
	{
		$params = $this->getRequest()->getParams();
	
		$params = array(
				"critere"         => Ccsd_Tools::ifsetor($params["critere"], "*"),
				"solR"            => Ccsd_Tools::ifsetor($params["solR"], true),
				"page"            => Ccsd_Tools::ifsetor($params["page"], 1),
				"nbResultPerPage" => Ccsd_Tools::ifsetor($params["nbResultPerPage"], 50),
				"tri"             => Ccsd_Tools::ifsetor($params["tri"], "valid"),
				"filter"		  => Ccsd_Tools::ifsetor($params["filter"], 'all'),
				"category"	      => Ccsd_Tools::ifsetor($params["category"], "*")
		);
	
		$params = array_merge($params, $this->getRequest()->getParams());
	
		return $params;
	}
}
