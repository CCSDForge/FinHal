<?php

abstract class Aurehal_Controller_Referentiel extends Hal_Controller_Action
{
    /* @var $_paginator Zend_Paginator */
    protected $_paginator = null;

    /* @var $_form Ccsd_Form */
    protected $_form;

    /* @var $_title array */
    protected $_title = array();

    protected $_create = true;

    /* @var $_info array */
    protected $_info = array();

    /* @var $_description array */
    protected $_description = array();

    /* @var $_partial_form string */
    protected $_partial_form = 'partials/formSearch.phtml';

    /* @var $_partial_form_r string */
    protected $_partial_form_r = 'partials/formReplace.phtml';

    /* @var string $_class */
    protected $_class = '';

    /* @var $_head_columns array */
    protected $_head_columns = array();

    /* @var $_columns_solR array */
    protected $_columns_solR = array();

    /* @var $_columns_dB array */
    protected $_columns_dB = array();

    /* @var $_name string */
    protected $_name = '';

    /**
     * Redirection vers la consultation
     *
     * @return void
     * @throws Zend_Form_Exception
     * @throws Zend_Paginator_Exception
     * @todo: traiter les exceptions
     */
    public function indexAction ()
    {
        $this->browseAction();
    }

    /**
     * Consultation
     *
     * @return void
     * @throws Zend_Form_Exception
     * @throws Zend_Paginator_Exception
     * @todo: traiter les exceptions
     */
    public function browseAction ()
    {
        $this->_form = $this->getForm($this->_class, "/browse");

        if ($this->getRequest()->getParam('critere', false)) {
            $this->search();
        }

        $params = $this->getRequest()->getParams();

        if (array_key_exists('row', $params)) {
            $this->view->row = $params['row'];
            $this->view->message = $params['message'];
            unset ($params['row']);
            unset ($params['message']);
            $this->getRequest()->clearParams()->setParams($params);
        }

        $this->view->create         = $this->_create;
        $this->view->partial_form   = $this->_partial_form;
        $this->view->form           = $this->_form;
        $this->view->isPost         = $this->getRequest()->isPost();
        $this->view->controllerName = $this->getRequest()->getControllerName();

        $this->_helper->layout()->title       = $this->_title['browse'];
        $this->_helper->layout()->description = $this->_description['browse'];

        $this->renderScript('referentiel/browse.phtml');
    }
 
    /**
     * Modification
     *
     * @return void
     */
    public function modifyAction ()
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        //Affichage d'un element du referentiel en modification
        if (($id = $request->getParam('id', false)) !== false) {
            /** @var Ccsd_Referentiels_Abstract $obj */
            $obj = new $this->_class($id);
            $form = $obj->getForm(true, true, $id);
            $this->_form = $form;
            $form->setAction(URL . "/" . $request->getControllerName() . "/modify/id/$id");
            $form->setActions(true)
                ->createSubmitButton(
                    'modify', array(
                        "label" => Ccsd_Form::getDefaultTranslator()->translate("Modifier"),
                        "class" => "btn btn-primary",
                        "style" => "margin-top: 15px;"
                    )
                );

            if ($request->isPost() && $this->_form->isValid($request->getPost())) {

                // TO DO : ajouter une fonction getChangeableValues au Ccsd_Form plutôt qu'au Referentiels_Abstract
                $filteredValues = $obj->getFilteredData($this->_form->getValues(), $obj->getChangeableValues());
                $obj->set($filteredValues);
                $newid = $obj->save();
                
                if (($newid) && ( $newid != $id )) {
                    $this->view->message = "La fiche ". $id . " n'a pas été modifiée car la fiche obtenue après modification existe déjà avec l'identifiant ". $newid. ". Vous pouvez donc les fusionner.";
                    $this->forward('read', $request->getControllerName(), null, array('id' => $newid));
                } else {
                    $this->view->message = "La fiche a été modifiée avec l'identifiant ". $id. ".";
                    $this->forward('read', $request->getControllerName(), null, array('id' => $id));
                }
            } else {
                $this->view->form   = $this->_form;
                $this->_helper->layout()->title = $this->_title['modify_1'];
            }

            $this->_helper->layout()->description = $this->_description['modify'];

            $this->renderScript('referentiel/modify.phtml');
        } else {
            $this->browseAction();
        }
    }

    /**
     * Création
     *
     * @return void
     * @throws Ccsd_Referentiels_Exception_AuthorException
     */
    public function createAction()
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        if ($this->_create){
            if ($id = $request ->getParam('id', false)) {
                /** @var Ccsd_Referentiels_Abstract $o */
                if (($o = $this->find($id)) instanceof Ccsd_Referentiels_Abstract) {
                    $this->view->message = "La restauration s'est bien effectuée, quelques minutes sont nécessaires afin de retrouver l'élément dans la recherche.";
                    $this->forward('read', $request -> getControllerName(), null, array ('id' => $o->restore($o->toArray())));
                } else {
                    throw new Ccsd_Referentiels_Exception_AuthorException();
                }

                /**
                 * @TODO pk AuthorException ?
                 */

            } else {
                /** @var Ccsd_Referentiels_Abstract $obj */
                $obj = new $this->_class();

                $this->_form  = $obj->getForm(true)
                    ->setAction(URL . "/" . $request ->getControllerName() . "/create")
                    ->setActions(true)
                    ->createSubmitButton(
                        'create', array(
                            "label" => Ccsd_Form::getDefaultTranslator()->translate("Créer"),
                            "class" => "btn btn-primary",
                            "style" => "margin-top: 15px;"
                        )
                    );

                if ($request->isPost() && $this->_form->isValid($request->getPost())) {
                    $obj->set($this->_form->getValues());
                    if ($id = $obj->save() ) {
                        $this->forward('read', $request ->getControllerName(), null, array ('id' => $id));
                    } else {
                        $this->view->info   = $this->_info['info_5'];
                    }
                } else {
                    $this->_helper->layout()->title = $this->_title['create_1'];
                    $this->view->form = $this->_form;
                }

                $this->_helper->layout()->description = $this->_description['create'];

                $this->renderScript('referentiel/modify.phtml');
            }
        } else {
            $this->renderScript('error/refused.phtml');
        }
    }

    /**
     * Remplacement
     *
     * @return void
     */
    public function replaceAction ()
    {
       
        
        if (($row = $this->getRequest()->getParam('row', false)) !== false && ($rows = new RecursiveIteratorIterator(new RecursiveArrayIterator($row))) !== false) {

            $this->_form = $this->getForm($this->_class, "/replace");
            $id = $this->getRequest()->getParam('dest', false);

            $rowids = $unavailable = array ();
            foreach ($rows as $i => $r) {
                /** @var Ccsd_Referentiels_Abstract $o */
                if (($o = (new $this->_class())->isValid($r)) == FALSE) {
                    $o = Ccsd_Referentiels_Logs::findByReplaceID($r);
                }

                /* Pour éviter que l'id se remplace par lui-même lors d'un reload de la page,
                 * et que l'on perde une entrée de référentiel
                 */
                if ($o->{$o->getPK()} == $id) {
                    unset($row[$i]);
                }


                if ('VALID' == $o->VALID) {
                    $unavailable[] = $r;
                }

                array_push($rowids, array($r => $o));
            }
            if (count($unavailable) > 0) {
                $router = $this->getFrontController()->getRouter();
                $request = new Zend_Controller_Request_Http(Zend_Uri_Http::factory(URL . '/' . $this->getRequest()->getParam('browse_url')));
                $params = $request->getParams();
                unset ($params['browse_url']);
                $request->setParams($params);
                $router->route($request);
                $url    = $router->assemble($params + array('message' => 'Impossible de remplacer un ' . Zend_Registry::get(ZT)->translate($this->_class) . ' valide'));
                $this->redirect($url);
            }

            $this->view->class = $this->_class;
            $this->view->name = $this->_name;
            $this->view->partial_form   = $this->_partial_form_r;
            $this->view->controllerName = $this->getRequest()->getControllerName();

            $this->documents();

            if ($id = $this->getRequest()->getParam('dest', false)) {

                $id = (int) $id;
                /** @var Ccsd_Referentiels_Abstract $obj */
                $obj 		= new $this->_class($id);
                // on vérifie que l'objet cible existe bien en base
                if (!$obj->exist($id)) {
                    $this->view->trouve = -1;
                    unset($obj);
                }
                else {
                    $this->view->trouve=1;
                }

                $to_replace = array_diff($row, array($id));

                $obj_src = array();
                foreach ($to_replace as $id_toreplace) {
                    $obj_src[$id_toreplace] = new $this->_class($id_toreplace);
                }

                if ($this->getRequest()->getParam('to_replace', false)) {
                    $obj 		= new $this->_class($id);
                    // on vérifie que l'objet cible existe toujours en base
                    if ((!isset($obj)) || ((isset($obj)) && (!$obj->exist($id)))) {
                        $this->view->obj_dest = $obj::$core." indexé(e), mais non trouvé(e) dans la base";
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

                    //Opération standard de remplacement
                    if (array_filter($obj_src)) {
                        //Action en base et logs
                        foreach ($obj_src as $i => $o) {
                            $this->substitute($i, $o, $id, $obj);
                        }
                    }

                    //On déplace les documents
                    if (array_filter($documents_src)) {
                        foreach ($documents_src as $objid => $ds) {
                            $docids = array ();
                            foreach ($ds as $docid => $move) {
                                if ($move) {
                                    $docids[] = $docid;
                                }
                            }
                            Ccsd_Referentiels_Update::moveDocument($docids, $objid, $id, $obj::$core);
                            //Premier log pour informer qu'il a gagné ses documents
                            Ccsd_Referentiels_Logs::log($id, 		$obj::$core, Hal_Auth::getUid(), 'BOUNDED', 	Zend_Json::encode(array($objid => $docids)));
                            //Deuxième log pour informer qu'il a perdu des documents
                            Ccsd_Referentiels_Logs::log($objid, 	$obj::$core, Hal_Auth::getUid(), 'UNBOUNDED', 	Zend_Json::encode(array($id => $docids)));
                            //Réindexationthis->getRequest()->getControllerName() == 'structure')
                            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($objid), SPACE_NAME, 'UPDATE', $obj::$core, 10);
                        }
                    }

                    Ccsd_Search_Solr_Indexer::addToIndexQueue(array($id), SPACE_NAME, 'UPDATE', $obj::$core, 10);

                    if ($this->getRequest()->getControllerName() == 'structure') {
                        /** @var Ccsd_Referentiels_Structure $obj  */
                        $newTypeStructure = $obj->getTypestruct();
                        $structure = new Ccsd_Referentiels_Structure();
                        $idReplace = array();
                        foreach ($this->getRequest()->getParam('row') as $row) {
                            $canReplace = $structure->isValidTypeStruct($row, $newTypeStructure);
                            if (!$canReplace) {
                                $idReplace[] = $row;
                            }
                        }
                        $obj_src = array_diff_key($obj_src, array_flip($idReplace));
                    }

                    $this->view->title       = $this->_title['replace_2'];
                    $this->view->obj_src     = $obj_src;
                    if (isset($obj)) {
                        $this->view->obj_dest     = $obj;
                    }
                    else {
                        $this->view->obj_dest = $obj::$core." indexé(e), mais non trouvé(e) dans la base";
                        $this->view->id = -1;
                        $this->view->trouve = -1;
                    }

                    $this->_helper->layout()->title = $this->_title['replace_2'];

                    $this->forward('read', $this->getRequest()->getControllerName(), null, array ('id' => $id));

                } else {

                    if ($this->getRequest()->getControllerName() == 'structure') {
                        /** @var Ccsd_Referentiels_Structure $obj  */
                        //Indique si une structure peut être remplacée par une autre (se base sur le type des structures)
                        if (isset($obj)) {
                            $newTypeStructure = $obj->getTypestruct();
                            $structure = new Ccsd_Referentiels_Structure();
                            $idReplace = array();
                            foreach ($this->getRequest()->getParam('row') as $row) {
                                $canReplace = $structure->isValidTypeStruct($row, $newTypeStructure);
                                /*if ($canReplace) {
                                    //On regarde si les nouvelles structures seront valides
                                    $structTmp = new Ccsd_Referentiels_Structure($row);
                                    if ($structTmp->hasParent()) {
                                        foreach ($structTmp->getParents() as $parent) {
                                            $canReplace = $canReplace && Ccsd_Referentiels_Structure::compareTypeStruct($newTypeStructure, $parent["struct"]->getTypestruct());
                                        }
                                    }
                                }*/
                                if (!$canReplace) {
                                    $idReplace[] = $row;
                                }
                            }
                            if (!empty($idReplace)){
                                $this->view->canReplace = false;
                            } else {
                                $this->view->canReplace = true;
                            }
                            $this->view->idReplace = $idReplace;
                        }
                    }
                    if (isset ($obj_documents_src)) {
                        $this->view->obj_documents_src 	= $obj_documents_src;
                    }
                    if (isset ($documents_src)) {
                        $this->view->documents_src = $documents_src;
                    }

                    $this->view->obj_src  			= $obj_src;

                    $this->view->id       = $id;
                    $this->view->params   = $this->getParams();
                    if (isset($obj)) {
                        $this->view->obj_dest = $obj;
                    }
                    else {
                        $this->view->obj_dest = $this->_name." indexé(e), mais non trouvé(e) dans la base";
                        $this->view->id = -1;
                        $this->view->trouve = -1;
                    }

                    $this->_helper->layout()->title = $this->_title['replace_2'];
                    $this->renderScript('referentiel/resume.phtml');
                }

            } else {
                if ($this->getRequest()->getParam('search', false) || $this->getRequest()->getParam('searching', false)) {
                    $this->search(true);
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
     * Substitution dans la base
     *
     * @param int                        $id_from id à remplacer
     * @param Ccsd_Referentiels_Abstract $from    objet à remplacer
     * @param int                        $id_to   id remplaçant
     * @param Ccsd_Referentiels_Abstract $to      objet remplaçcant
     *
     * @return void
     */

    public function substitute ($id_from, Ccsd_Referentiels_Abstract $from, $id_to, Ccsd_Referentiels_Abstract $to)
    {
        Ccsd_Referentiels_Logs::log($id_from,  $to::$core, Hal_Auth::getUid(), "REPLACED_BY", Zend_Json::encode(array((int)$id_to)));
        Ccsd_Referentiels_Logs::log($id_to,    $to::$core, Hal_Auth::getUid(), "REPLACE",     Zend_Json::encode(array ($id_from)));

        $from->delete();

        try {
            Ccsd_Referentiels_Alias::add($id_to, $to::$core, $id_from, '', new Zend_Db_Expr('UNHEX("' . $from->getMd5() . '")') );
        } catch (Zend_Db_Exception $e) {
            echo "erreur de requête : ".$e->getMessage();
        }
        Ccsd_Referentiels_Update::add($to::$core, $id_to, $id_from);
    }

    /**
     * Lecture d'une fiche
     *
     * @return void
     */
    public function readAction ()
    {
        if (($id = $this->getRequest()->getParam('id', false)) !== false) {
            if (Hal_Rdf_Tools::requestRdfFormat($this->getRequest())) {
                $this->getRequest()->setParam('format', 'rdf');
            }

            if (('rdf' == $this->getRequest()->getParam('format', false))) {

                $this->_helper->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender();
                /**
                 * @uses Hal_Rdf_Author
                 * @uses Hal_Rdf_Anrproject
                 * @uses Hal_Rdf_Document
                 * @uses Hal_Rdf_Domain
                 * @uses Hal_Rdf_Europeanproject
                 * @uses Hal_Rdf_Idhal
                 * @uses Hal_Rdf_Journal
                 */
                $class = "Hal_Rdf_" . ucfirst($this->getRequest()->getControllerName());
                /** @var Hal_Rdf_Abstract $rdf */
                $rdf = new $class($id);
                header("Content-type: text/xml");
                echo $rdf->getRdf();
                return;
            }

            /** @var Ccsd_Referentiels_Abstract $objet */
            $objet = new $this->_class($id);
            $data = $objet->toArray();

            if (array_filter($data)) {
                $this->view->id     = $id;
                $this->view->core   = $objet::$core;
                $this->view->objet  = $objet;
            } else {
                $this->view->message = "Objet non trouvé";
                $objet = new $this->_class();
                $newid = Ccsd_Referentiels_Alias::getAliasNewId($id, $objet::$core);
                if ($newid != $id) {
                    // Alias trouvé : newid
                    $this->forward('read', $this->getRequest()->getControllerName(), null, array('id' => $newid));
                    return;
                } else {
                    $this->view->message = Zend_Registry::get('Zend_Translate')->translate($this->_class)." inexistant";
                    $this->renderScript('error/error.phtml');
                    return;
                }
            }

            if (($message = $this->getRequest()->getParam('message', false)) !== false) {
                $this->view->message = $message;
            }

            $this->view->controllerName = $this->getRequest()->getControllerName();

            $this->_helper->layout()->title  = $this->_title['read'];
            $this->_helper->layout()->description = "";

            if (!file_exists($this->view->getScriptPath("") . $this->getRequest()->getControllerName() . "/" . $this->getRequest()->getActionName() . ".phtml")) {
                $this->renderScript('referentiel/read.phtml');
            }
        } else {
            $this->view->message = "L'identifiant de  l'".Zend_Registry::get('Zend_Translate')->translate($this->_class)." est obligatoire";
            $this->renderScript('error/error.phtml');
        }
    }

    /**
     * Historique
     *
     * @return void
     */
    public function historyAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->layout()->disableLayout();
            $this->_helper->viewRenderer->setNoRender();

            if (($id = $this->getRequest()->getParam('id', false)) !== false) {
                $o = $this->find($id);
                if ($o instanceof Ccsd_Referentiels_Abstract) {
                    echo $o->toHtml(array('showParents' => true));
                } else {
                    echo $o;
                }
            }
        } else {
            $this->browseAction();
        }
    }

    /**
     * Récupération des documents
     *
     * @return void
     */
    public function ajaxloaddocumentAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && ($obj = $this->getRequest()->getParam('obj', FALSE)) !== FALSE && ($id = $this->getRequest()->getParam('id', FALSE)) !== FALSE) {
            $this->_helper->layout()->disableLayout();

            $class     = "Ccsd_Referentiels_$obj";
            $documents = array();

            $documents = $relations = array ();

            //Mettre en avant les documents non rattachés à des structures de recherches
            $documents[null] = array ();

            foreach ( $class::getRelatedDocid($id) as $docid ) {
                if  ($document = Hal_Document::find($docid) ) {

                    $structures = array ();
                    if ($obj == "Author") {
                        $doc_structures = $document->getStructuresAuthor($id);
                    } else {
                        $doc_structures = $document->getStructures();
                    }

                    if (is_array($doc_structures) && !empty ($doc_structures)) {
                        foreach ($doc_structures as $structure) {
                            $structures[$structure->getStructid()]  = $structure->getStructname();
                            $relations[$structure->getStructid()] = array('name' => $structure->getStructname(), 'type' => $structure->getTypestruct());
                            $documents[$structure->getStructid()][] = array(
                                'docid'     => $docid,
                                'citation'  => $document->getCitation('full'),
                                'format'    => $document->getFormat(),
                                'ref'		=> $document->getId(true),
                                'ref_url'   => $document->getUri(true),
                                'structids' => $structures
                            );
                        }
                    } else {
                        $documents[null][] = array(
                            'docid'     => $docid,
                            'citation'  => $document->getCitation('full'),
                            'format'    => $document->getFormat(),
                            'ref'		=> $document->getId(true),
                            'ref_url'   => $document->getUri(true),
                            'structids' => array()
                        );
                    }
                }
            }

            $this->view->id        = $id;
            $this->view->documents = $documents;
            $this->view->relations = $relations;

            $this->renderScript('partials/documents.phtml');

        } else {
            $this->browseAction();
        }
    }

    /**
     * documents
     * Todo: pas une action de controller: ne devrait pas utiliser getRequest: passer des parametres a la fonction
     * @return void
     */
    protected function documents ()
    {
        if (($docs = $this->getRequest()->getParam('docs', false)) !== false) {
            foreach ($docs as $authorid => $doc) {
                $this->view->{"OBJID_$authorid"} = array();
                foreach ($doc as $docid => $checked) {
                    $this->view->{"OBJID_$authorid"}[$docid] = (bool)$checked;
                }
            }

            $this->view->panel = $this->getRequest()->getParam('panel', array());

        }
    }

    /**
     * find
     * For an referential Id, look into the Log table to find history and get ONLY ONE row!!!
     *
     * @param int $id : identifiant de l'objet à rechercher
     * @return false | string | Ccsd_Referentiels_Abstract du référentiel
     * @throws Zend_Json_Exception
     */
    protected function find ($id)
    {
        $row = Ccsd_Referentiels_Logs::find($id);
        if (!$row) {
            return false;
        }
        if ($row['ACTION'] == 'REPLACE') {
            $row = Ccsd_Referentiels_Logs::findDeleted($row['PREV_VALUES'], $row['DATE_ACTION']);
        }

        if ($row) {
            try {
                $row['PREV_VALUES'] = Zend_Json::decode($row['PREV_VALUES']);
                $row['PREV_VALUES'] = array_shift($row['PREV_VALUES']);
            } catch (Zend_Json_Exception $e) {}

            if (in_array($row['ACTION'], array ('BOUNDED', 'UNBOUNDED'))) {

                $docs = array ();
                foreach ($row['PREV_VALUES'] as $docid) {
                    $docs[] = Hal_Document::find($docid)->getCitation('full');
                }
                return "<div class='row'><div class='col-md-12'>" . implode("</div></div><div class='row'><div class='col-md-12'>", $docs) . "</div></div>";

            } else {
                $class = "Ccsd_Referentiels_";
                if ("REF_JOURNAL" == $row["TABLE_NAME"]) {
                    $class .= "Journal";
                } else if ("REF_PROJANR" == $row["TABLE_NAME"]) {
                    $class .= "Anrproject";
                } else if ("REF_PROJEUROP" == $row["TABLE_NAME"]) {
                    $class .= "Europeanproject";
                } else if ("REF_AUTHOR" == $row["TABLE_NAME"]) {
                    $class .= "Author";
                } else if ("REF_STRUCTURE" == $row["TABLE_NAME"]) {
                    $class .= "Structure";
                } else {
                    Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Bad class name: " . $row["TABLE_NAME"]);
                    return false;
                }

                /** @var Ccsd_Referentiels_Abstract $o */
                $o = new $class;

                $data = $row['PREV_VALUES'];

                if ($row['ACTION'] == 'DELETED') {
                    $data['VALID'] = 'OLD';
                }

                $o->set($data);
                return $o;
            }
        }
    }

    /**
     * getParams
     *
     * @return array params
     */
    protected function getParams ()
    {
        $params = $this->getRequest()->getParams();

        $params = array(
            "critere"         => Ccsd_Tools::ifsetor($params["critere"], "*"),
            "solR"            => Ccsd_Tools::ifsetor($params["solR"], true),
            "page"            => Ccsd_Tools::ifsetor($params["page"], 1),
            "nbResultPerPage" => Ccsd_Tools::ifsetor($params["nbResultPerPage"], 50),
            "tri"             => Ccsd_Tools::ifsetor($params["tri"], "valid"),
            "filter"		  => Ccsd_Tools::ifsetor($params["filter"], 'all')
        );

        $params = array_merge($params, $this->getRequest()->getParams());

        return $params;
    }

    /**
     * Recherche
     *
     * @param boolean $replace indicateur
     *
     * @return aucune valeur
     * @throws Zend_Paginator_Exception
     */
    protected function search ($replace = false)
    {
        $params = $this->getParams();

        $this->_form->populate(array("critere" => $params['critere'], "nbResultPerPage" => $params['nbResultPerPage'], "tri" => $params['tri'], "filter" => $params['filter']));

        /* @var $obj Ccsd_Referentiels_Abstract */
        $obj = new $this->_class();

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

    /**
     * Lecture formulaire
     *
     * @param string $class classe du référentiel
     * @param string $url   adresse du formulaire
     *
     * @return Ccsd_Form
     * @throws Zend_Form_Exception
     */
    protected function getForm ($class, $url)
    {
        return (new Ccsd_Form())
            ->setAction("/" . $this->getRequest()->getControllerName() . $url)
            ->setMethod('GET')
            ->addElement("text", "critere", array("Label" => "Critère"))
            ->addElement('hidden', 'solR', array('value' => 1))
            ->addElement("select", "filter", array(
                    "Label"        => "filter",
                    "multioptions" => $class::$_optionsFilter
            ))
            ->addElement("select", "nbResultPerPage", array(
                    "Label"        => "nbResultPerPage",
                    "multioptions" => array("50" => "50 résultats par page", "100" => "100 résultats par page", "200" => "200 résultats par page")
            ))
            ->addElement("select", "tri", array(
                    "Label"        => "Tri",
                    "multioptions" => $class::$_optionsTri
            ))
            ->setActions(true)->createSubmitButton('search', array(
                    "label" => Ccsd_Form::getDefaultTranslator()->translate("Rechercher"),
                    "class" => "btn btn-primary",
                    "style" => "margin-top: 15px;"
            ));
    }
}