<?php
class AuthorController extends Aurehal_Controller_Referentiel
{
    protected $_create = false;
    
    public function init ()
    {
        $this->_title = array(
            'browse'    => 'Consultation des formes auteurs',
            'modify_1'  => "Modification d'une forme auteur",
            'modify_2'  => 'Forme auteur modifié',
            'create_1'  => "Création d'une forme auteur",
            'create_2'  => 'Impossible de créer la forme auteur',
            'create_3'  => 'Forme auteur créée',
            'replace_1' => 'Remplacement des formes auteurs',
            'replace_2' => 'Résumé des modifications des auteurs',
            'read'     => "Fiche d'une forme auteur"
        );

        $this->_info = array (
            'info_1' => "Les modifications de la forme auteur ont été prises en compte",
            'info_2' => "La réindexation des données peut prendre quelques minutes pour être effective lors de la consultation de AURéHAL.",
            'info_3' => "Vous essayer de créer un doublon d'une forme auteur !",
            'info_4' => 'La forme auteur a été créée',
            'info_5' => "La réindexation peut prendre quelques minutes, la forme auteur apparaitra après quelques minutes sur AURéHAL (généralemeent moins de 2 minutes)."
        );

        $this->_description = array (
            'browse'  => 'Ce module vous permet de consulter la liste des formes auteurs.',
            'create'  => 'Ce module vous permet de créer de nouvelles formes auteurs.',
            'replace' => 'Ce module vous permet de remplacer des formes auteurs par une autre.',
            'modify'  => 'Ce module vous permet de modifier une forme auteur existante.<br><i class=\'glyphicon glyphicon-warning-sign\' style=\'color: #a30000; font-size: 10px;\';></i> <b>Attention: La modification portera sur la forme auteur et tous les documents rattachés à cette forme se verront modifiés, de façon NON reversible!</b>'
        );

        $this->_name          = 'Author';	
        $this->_class         = 'Ccsd_Referentiels_Author';
        $this->_head_columns = array('id', 'idHAL', 'nom', 'prenom', 'emailDomain', 'ACTIONS');
        $this->_columns_solR = array("id" => "docid", "idHAL" => "idHal_i", "nom" => "lastName_s", "prenom" => "firstName_s", 'emailDomain' => 'emailDomain_s', "email" => "email_s", "valid" => "valid_s");
        $this->_columns_dB    = array ("id"=>"AUTHORID", "idHAL" => "IDHAL", "nom"=>"LASTNAME", "prenom" => "FIRSTNAME", "email" => "EMAIL", "valid" => "VALID");
    }

    public function indexAction()
    {
        parent::indexAction();
    }

    public function createAction()
    {
        parent::createAction();
    }
    /**
     * @see Aurehal_Controller_Referentiel::replaceAction()
     */
    public function replaceAction ()
    {
        if (($row = $this->getRequest()->getParam('row', false)) !== false && ($rows = new RecursiveIteratorIterator(new RecursiveArrayIterator($row))) !== false) {

            $id = $this->getRequest()->getParam('dest', false);

            $this->_form = $this->getForm($this->_class, "/replace");

            $rowids = $idhals = $unavailable = array ();
            foreach ($rows as $i => $r) {
                /** @var Ccsd_Referentiels_Abstract $o */
                if (($o = (new $this->_class())->isValid($r)) == FALSE) {
                    $o = Ccsd_Referentiels_Logs::findByReplaceID($r);
                }

                if ($o->AUTHORID == $id) {
                    unset($row[$i]);
                    continue;
                }

                if ($o->IDHAL) {
                    $idhals[$o->IDHAL] = true;
                }

                if ('VALID' == $o->VALID) {
                    $unavailable[] = $r;
                }

                array_push ($rowids, array($r => $o));
            }

            if (count($idhals) > 1 || count($unavailable) > 0) {
                $router = $this->getFrontController()->getRouter();

                $request = new Zend_Controller_Request_Http(Zend_Uri_Http::factory(URL .'/'. $this->getRequest()->getParam('browse_url')));

                $params = $request->getParams();
                unset ($params['browse_url']);

                $request->setParams($params);

                $router->route($request);

                $message = 'Impossible de fusionner deux auteurs appartenant à un IDHAL différent';

                if (count($unavailable) > 0) {
                    $message = 'Impossible de remplacer une forme auteur valide';
                }

                $url    = $router->assemble($params + array('message' => $message));
                
                $this->redirect($url);
            }

            $this->view->class = $this->_class;
            $this->view->name = $this->_name;
            $this->view->partial_form   = $this->_partial_form_r;
            $this->view->controllerName = $this->getRequest()->getControllerName();

            $this->documents();


            if ($id && count($row) > 0) {
                /** @var Ccsd_Referentiels_Abstract $obj */
                $obj 		= new $this->_class($id);
                // on vérifie que l'auteur cible existe bien en base
                if (!$obj->exist($id)) {
                    $this->view->trouve = -1;
                    unset($obj);
                }
                else {
                    $this->view->trouve=1;
                }
                $to_replace = array_diff($row, array($id));
                $documents = $this->getRequest()->getParam('docs', array());

                $obj_src = $documents_src = $obj_documents_src = array();
                foreach ($to_replace as $id_toreplace) {
                    if (array_key_exists($id_toreplace, $documents)) {
                        //On a des documents à déplacer
                        $documents_src[$id_toreplace] = $documents[$id_toreplace];
                        if (!array_key_exists($id_toreplace, $obj_documents_src)) {
                            $obj_documents_src[$id_toreplace] = new $this->_class($id_toreplace);
                        }
                    } else {
                        //Procédure de remplacement
                        $obj_src[$id_toreplace] = new $this->_class($id_toreplace);
                    }
                }

                if ($this->getRequest()->getParam('to_replace', false)) {
                    // on vérifie que l'objet cible existe toujours en base
                    if ((!isset($obj)) || ((isset($obj)) && (!$obj->exist($id)))) {
                        $this->view->obj_dest = "Auteur indexé, mais non trouvé dans la base";
                        $this->view->id = -1;
                        $this->view->trouve = -1;
                        $this->view->params   = $this->getParams();
                        unset($obj);
                        $this->view->rowids   = $rowids;
                        $this->view->form     = $this->_form;
                        $this->_helper->layout()->title = $this->_title['replace_1'];
                        $this->renderScript('referentiel/replace.phtml');
                        return;
                    }

                    //Opération standard de remplacement
                    if (array_filter($obj_src)) {
                        foreach ($obj_src as $i => $o) {
                            Ccsd_Referentiels_Logs::log($i,  $obj::$core, Hal_Auth::getUid(), "REPLACED_BY", Zend_Json::encode(array((int)$id)));
                            Ccsd_Referentiels_Logs::log($id, $obj::$core, Hal_Auth::getUid(), "REPLACE",     Zend_Json::encode(array($i)));
                            if ($o->delete() != 1) {
                                continue;
                            }

                            try {
                                Ccsd_Referentiels_Alias::add($id, $obj::$core, $i, '', new Zend_Db_Expr('UNHEX("' . $o->getMd5() . '")'));
                            } catch (Zend_Db_Exception $e) {
                                printf("erreur de requête : %s", $e->getMessage());
                            }
                            //On réindexe l'auteur remplaçé
                            Ccsd_Referentiels_Update::add($obj::$core, $id, $i);
                        }
                    }

                    //On déplace les documents
                    if (array_filter($documents_src)) {
                        foreach ($documents_src as $authorid => $ds) {
                            $docids = array ();
                            foreach ($ds as $docid => $move) {
                                if ($move) {
                                    $docids[] = $docid;									
                                }
                            }
                            Ccsd_Referentiels_Update::moveDocument($docids, $authorid, $id, $obj::$core);
                            //Premier log pour informer qu'il a gagné ses documents
                            Ccsd_Referentiels_Logs::log($id,       $obj::$core, Hal_Auth::getUid(), 'BOUNDED', 	 Zend_Json::encode(array($authorid => $docids)));
                            //Deuxième log pour informer qu'il a perdu des documents
                            Ccsd_Referentiels_Logs::log($authorid, $obj::$core, Hal_Auth::getUid(), 'UNBOUNDED', Zend_Json::encode(array($id => $docids)));
                            //Réindexation
                            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($authorid), SPACE_NAME, 'UPDATE', $obj::$core, 10);
                        }
                    }

                    //On réindexe l'auteur remplaçant
                    Ccsd_Search_Solr_Indexer::addToIndexQueue(array($id), SPACE_NAME, 'UPDATE', $obj::$core, 10);

                    $this->view->title       = $this->_title['replace_2'];
                    $this->view->obj_src     = $obj_src;
                    $this->view->obj_dest     = $obj;

                    $this->_helper->layout()->title = $this->_title['replace_2'];

                    $this->forward('read', $this->getRequest()->getControllerName(), null, array ('id' => $id));
                } else {
                    
                    $this->view->obj_documents_src 	= $obj_documents_src;
                    $this->view->documents_src 		= $documents_src;
                    $this->view->obj_src  			= $obj_src;

                    $this->view->id       = $id;
                    if (isset($obj)) {
                        $this->view->obj_dest = $obj;
                    }
                    else {
                        $this->view->obj_dest = "Auteur indexé, mais non trouvé dans la base";
                        $this->view->id = -1;
                    }
                    
                    $this->view->params   = $this->getParams();

                    $this->_helper->layout()->title = $this->_title['replace_2'];
                    $this->renderScript('referentiel/resume.phtml');
                 }
            } else {
                if ($this->getRequest()->getParam('search', false) || $this->getRequest()->getParam('searching', false)) {
                    $this->search(true, $idhals);
                }

                $this->view->rowids        		= $rowids;
                $this->view->form          		= $this->_form;
                $this->_helper->layout()->title = $this->_title['replace_1'];

                $this->renderScript('referentiel/replace.phtml');
            }	
            $this->_helper->layout()->description = $this->_description['replace'];
        } else {
            $this->browseAction();
        }
    }

    /**
     * @see Aurehal_Controller_Referentiel::search()
     */
    protected function search ($replace = false, $idhals = array())
    {
        $params = $this->getParams();
        
        $this->_form->populate(array("critere" => $params['critere'], "nbResultPerPage" => $params['nbResultPerPage'], "tri" => $params['tri'], "filter" => $params['filter']));
        
        /* @var $obj Ccsd_Referentiels_Abstract */
        $obj = new $this->_class();

        if (!empty ($idhals)) {
            $params['IDHAL'] = key($idhals);
        }

        /**
         *  Je récupère les paramètres de la recherche parametre de la première requete et parametres implicites
         *  d'un recherche solR que je dois passer à la vue et la pagination
        */
        $obj->setPaginatorAdapter(
            ($params['solR'] ? Ccsd_Referentiels_Abstract::PAGINATOR_ADAPTER_SOLR : Ccsd_Referentiels_Abstract::PAGINATOR_ADAPTER_BASE),
            $params,
            $replace
        );

        $this->_paginator = new Zend_Paginator($obj->getPaginatorAdapter());
        $this->_paginator->setItemCountPerPage($params['nbResultPerPage']);
        $this->_paginator->setPageRange(Ccsd_Referentiels_Abstract::NB_PAGES_IN_RANGE);
        $this->_paginator->setCurrentPageNumber($params['page']);

        $this->view->params = $params;

        $this->view->head_columns = $this->_head_columns;

        if ($params['solR']) {
            $this->view->columns = $this->_columns_solR;
        } else {
            $this->view->columns = $this->_columns_dB;
        }

        $this->view->paginator = $this->_paginator;

        $this->view->filter    = $this->_form->getElement('filter')->getMultiOptions();
        $this->view->tri       = $this->_form->getElement('tri')->getMultiOptions();
        $this->view->nb        = $this->_form->getElement('nbResultPerPage')->getMultiOptions();
    }
}