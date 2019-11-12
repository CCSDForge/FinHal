<?php

/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 10/01/2014
 * Time: 10:04
 */
class ModerateController extends Hal_Controller_Action {

    public function init() {

        if (Hal_Settings_Features::hasDocModerate() === false) {
            $this->redirect('/error/feature-disabled');
        }
        // TODO: Hum, a priori, ne sert a rien! Devrait rediriger
        if ( ( !Hal_Auth::isModerateur(SITEID) ) && ( !Hal_Auth::isAdministrator()) ) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne disposez pas des privilèges pour accéder à cette action");
            $this->view->message = "Accès non autorisé";
            $this->view->description = "";
            $this->renderScript('error/error.phtml');
        }

    }

    public function indexAction() {
        $this->renderScript('index/submenu.phtml');
    }

    /**
     * Sauvegarde expert
     * + document status passe en validation
     * + envoi mail experts
     */
    public function saveexpertAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        Hal_Validation::saveExpert($this->getRequest());

        $forwardAction = htmlspecialchars($this->getRequest()->getParam('forwardAction'));

        return $this->redirect($this->view->url(array(
                            'controller' => 'administrate',
                            'action' => $forwardAction,
                            'docid' => (int) $this->getRequest()->getParam('docid')
                )));
    }

    /**
     * Retourne le formulaire de selection des experts
     *
     * @return boolean
     */
    public function ajaxselectexpertAction() {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return false;
        }

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->view->docid = $this->getRequest()->getParam('docid');

        $this->view->currentExpertList = Hal_Validation::getDocValidators($this->view->docid);
        $this->view->expertList = '[' . implode(',', Hal_Validation::getSelectExpertData($this->getRequest())) . ']';
        $this->view->saveExpertFormTarget = '/' . $this->getRequest()->getControllerName() . '/saveexpert';
        $this->view->forwardAction = $this->getRequest()->getParam('forwardAction');
        $this->renderScript('partials/expert-list.phtml');
    }

    /**
     * Liste des documents à modérer
     */
    public function documentsAction() {
        $render = 'documents';
        $moderation = new Hal_Moderation();
        $request = $this->getRequest();
        $params = $request->getParams();

        $this->view->forwardAction = $this->getRequest()->getActionName();
        $this->view->mode = $this; // Mode moderation pour les scripts de la vue

        if (isset($params ['docid'])) {
            // Affichage des documents selectionnes ou bien action sur les documents selectionnes
            if (!is_array($params ['docid'])) {
                $params ['docid'] = [
                    $params ['docid']
                ];
            }
            if (isset($params ['evaluate-action'])) {
                // Action sur les documents
                $comment = isset($params ['comment']) ? $params ['comment'] : '';
                if ($params ['evaluate-action'] != 'back') {
                    $document = new Hal_Document();
                    $tags = array(
                        'MSG_MODERATEUR' => $comment
                    );
                    $res = $error = array();
                    foreach ($params ['docid'] as $docid) {
                        $document->setDocid($docid, true);
                        $tags ['document'] = $document;
                        if ($params ['evaluate-action'] == 'edit') {
                            // Modification du dépôt dans son portail de dépôt plutôt que dans son portail de modération
                            $site = Hal_Site::loadSiteFromId($document->getSid());
                            $this->redirect($site->getUrl().'/submit/moderate/docid/' . $docid);
                            exit();
                        } else if ($params ['evaluate-action'] == 'online') {
                            // Mise en ligne d'un document
                            $putonline = true;
                            $forceTransfert = isset($params ['force']);
                            $transfert = Hal_Transfert_Arxiv::transfert($document);
                            if ($transfert !== false) {
                                $response = $transfert->send($forceTransfert);
                                switch ($response ['result']) {
                                    case Hal_Transfert_Response::OK :
                                        // Table deja mise a jour.  Rien a faire
                                        break;
                                    default:
                                        $error [] = 'SWORD arXiv response: ' . $response ['reason'] . ' ' . $response ['alternate'];
                                        $messages = new Hal_Evaluation_Moderation_Message();
                                        $this->view->responses = $messages->getList(Hal_Auth::getUid());
                                        $this->view->document = new Hal_Document();
                                        $this->view->docids = [
                                            $docid
                                        ];
                                        $render = 'documents-actions';
                                        $putonline = false;
                                        break;
                                }
                            }
                            $transfert = Hal_Transfert_SoftwareHeritage::transfert($document);
                            if ($transfert !== false) {
                                $response = $transfert->send($forceTransfert);
                                switch ($response ['result']) {
                                    case Hal_Transfert_Response::OK :
                                        Hal_Document_Meta_Identifier::addIdExtDb($document->getDocid(), Hal_Transfert_SoftwareHeritage::$IDCODE, $transfert->getRemoteId());
                                        Hal_Document_Logger::log($document->getDocid(), Hal_Auth::getUid(), Hal_Document_Logger::ACTION_ANNOTATE, "Transfert a SWH");
                                        break;
                                    default:
                                        $error [] = 'SWORD SWH response: ' . $response ['reason'] . ' ' . $response ['alternate'];
                                        $messages = new Hal_Evaluation_Moderation_Message();
                                        $this->view->responses = $messages->getList(Hal_Auth::getUid());
                                        $this->view->document = new Hal_Document();
                                        $this->view->docids = [
                                            $docid
                                        ];
                                        Hal_Document_Logger::log($document->getDocid(), Hal_Auth::getUid(), Hal_Document_Logger::ACTION_ANNOTATE, $response ['reason']);
                                        $render = 'documents-actions';
                                        $putonline = false;
                                        break;
                                }
                            }
                            if ($document->gotoPMC()) {
                                //L'auteur a demandé le transfert PMC, on l'envoie dans HALMS
                                $halmsDoc = new Halms_Document($document->getDocid());
                                $halmsDoc->changeStatus(Halms_Document::STATUS_INITIAL);
                            }
                            if ($putonline && $document->putOnline(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            }
                        } else if ($params ['evaluate-action'] == 'update') {
                            // Demande de modifications
                            if ($document->toUpdate(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            }
                        } else if ($params ['evaluate-action'] == 'annotate') {
                            // Annotation du document
                            $document->annotate(Hal_Auth::getUid(), $comment);
                        } else if ($params ['evaluate-action'] == 'notice') {
                            // Transformation en notice
                            if ($document->notice(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            } else {
                                $error[] = $this->view->translate('Transformation en notice impossible pour le dépôt ').$document->getId() . 'v' . $document->getVersion();
                                $error[] .= $this->view->translate(' car un dépôt est déjà en ligne pour cet identifiant');
                            }
                        } else if ($params ['evaluate-action'] == 'refused') {
                            // Refus d'un document
                            if ($document->refused(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            }
                        } else if ($params ['evaluate-action'] == 'delete') {
                            // Suppression du document
                            if ($document->delete(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            }
                        } else if ($params ['evaluate-action'] == 'validate') {
                            // Mise en validation scientifique
                            if ($document->validate(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            }
                        } else if ($params ['evaluate-action'] == 'hal') {
                            // Transfert dans HAL
                            if ($document->changeInstance(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            }
                        }
                        Hal_Moderation::delDocInProgress($this->getRequest()->getClientIp(), $docid);
                    }
                    if (count($res) == 1) {
                        $text = $this->view->translate('moderate-action-' . $params ['evaluate-action']);
                        $text = str_replace('%%DOCUMENT%%', $res [0], $text);
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage($text);
                    } else if (count($res) > 1) {
                        $text = $this->view->translate('moderate-action-' . $params ['evaluate-action'] . '-multiple');
                        $text = str_replace('%%DOCUMENTS%%', implode(', ', $res), $text);
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage($text);
                    } else if (count($error)) {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage(implode(',', $error));
                    }
                } else {
                    Hal_Moderation::delDocInProgress($this->getRequest()->getClientIp());
                }
            } else {
                // Affichage de documents
                $render = 'documents-actions';
                $messages = new Hal_Evaluation_Moderation_Message();
                $this->view->responses = $messages->getList(Hal_Auth::getUid());
                $this->view->document = new Hal_Document();
                $this->view->docids = $params ['docid'];
                // Indicateur de modération
                foreach ($params ['docid'] as $docid) {
                    Hal_Moderation::addDocInProgress($docid, Hal_Auth::getUid(), $this->getRequest()->getClientIp());
                }
            }
        }
        if ($render == 'documents') {
            /**
             *  Les Sites semblent etre recuperes par $moderation->getDocuments()
            $this->view->sites = $moderation->getSites();
             */
            /** Recuperation des documents pour cet utilisateur */
            $req = $moderation->getDocuments();
            /** ajout des filtre d';interfaces */
            $filterList = $this->addFilterToRequest($req, $request);
            /** Ajout ordre interface */
            $orderTerm = isset($params['order']) ? $params['order'] : null;
            $sqlOrder = self::getSqlOrderField($orderTerm);
            $req->order($sqlOrder);

            /** Pagination */
            $this->getDocumentsPagination($req);
            $this->view->inProgress = Hal_Moderation::documentsInProgress(Hal_Auth::getUid());
            $this->view->filterList = $filterList;
            $this->view->pageDescription = "Merci de bien vouloir vérifier, valider, modérer les articles suivants";
            $this->view->addFilterMenu = true;
        }
        $this->render($render);
    }

    /**
     * @param $req Zend_Db_Select
     * @param Zend_Controller_Request_Http $request
     * @return array
     */
    private function addFilterToRequest($req, $request) {
        $filter     = $request->getParam('filtre',     null);
        $queryid    = $request->getParam('queryid',    null);
        $queryuid   = $request->getParam('queryuid',   null);
        $querytype  = $request->getParam('querytype',  null);
        $querydate  = $request->getParam('querydate',  null);
        $querynbdoc = $request->getParam('querynbdoc', null);
        $querypor   = $request->getParam('querypor',   null);

        $filterList = [
            'filter'     => $filter,
            'queryid'    => $queryid ,
            'queryuid'   => $queryuid,
            'queryuid'   => $queryuid,
            'querytype'  => $querytype,
            'querydate'  => $querydate,
            'querynbdoc' => $querynbdoc,
            'querypor'   => $querypor,
            ];

        if ($filter){
                if ($filter == 'arxiv'){
                    $query = 'd.DOCSTATUS LIKE ?';
                    $value = Hal_Document::STATUS_TRANSARXIV;
                    $req->where($query,$value);
                } else {
                    $query = 'l.LOGACTION LIKE ?';
                    $value = $filter;
                    $req->where($query,$value);
                }
            }
        if ($queryid) {
                $query = 'd.IDENTIFIANT LIKE ?';
                $value = "%$queryid%";
                $req->where($query,$value);
            }
        if ($queryuid) {
                $query = 'u.SCREEN_NAME LIKE ?';
                $value = "%$queryuid%";
                $req->where($query,$value);
            }
        if ($querytype) {
                $query = 'd.TYPDOC LIKE ?';
                $value = "%$querytype%";
                $req->where($query,$value);
            }
        if ($querydate) {
                $query = 'd.DATESUBMIT LIKE ?';
                $value = "%$querydate%";
                $req->where($query,$value);
            }
        if ($querynbdoc) {
                $query = 'u.NBDOCVIS LIKE ?';
                $value = $querynbdoc;
                $req->where($query,$value);
            }
        if ($querypor) {
                $query = 's.SITE LIKE ?';
                $value = $querypor;
                $req->where($query,$value);
            }
        return $filterList;
    }

    /**
     * Modification d'une donnée dans un article
     */
    public function ajaxupdateAction() {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();
            $docid = $params ['pk'];
            $meta = explode('@', $params ['name']);
            $metaname = $meta [0];
            $metagroup = $meta [1];
            Hal_Document::updateMeta($docid, $metaname, $params ['value'], null, $metagroup);
        }
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     * Gestion des réponses prédéfinies du modérateur
     */
    public function messageAction() {
        $request = $this->getRequest();
        # L'ensemble des messages de l'utilisateur et Uid0 (tableau index est messageId)
        $newObj = new Hal_Evaluation_Moderation_Message ();   // Pour le formulaire vide
        $messages = $newObj->getList(Hal_Auth::getUid());
        $error = '';
        $UserId = Hal_Auth::getUid();
        $isadmin = Hal_Auth::isAdministrator();
        if ($request->isPost()) {
            $modif = false;
            $params = $request->getPost();
            if (isset($params['uid']) && ($params['uid'] != $UserId)) {
                $error = 'Erreur de droit... Page ouverte par ' . $params['uid'] . " et valide par $UserId";
            } else {
                foreach ($params as $data) {
                    if (!isset($data ['title'])) {
                        continue; // on passe les parametres qui ne sont pas des messages
                    }
                    $title   = $data ['title'];
                    $message = $data ['message'];
                    $status  = $data ['status'];
                    $mid = $data ['messageid'];
                    switch ($status) {
                    case "nop":
                        // No Operation
                        continue;
                        break;
                    case "deleted":
                        // On autorise seulement l'effacement de ce qui est proposable (/propose) a l'utilisateur
                        if (isset($messages[$mid])) {
                            $m = $messages[$mid];
                            $m->delete();
                            unset($messages[$mid]); # On met a jour la liste qui sera affiche en retour!
                            $modif = true;
                        } else {
                            $error = "Message $title ($mid) non accessible ou inexistant";
                        }
                        continue;
                        break;
                    case "edited":
                        if (trim($title) == '' || trim($message) == '') {
                            continue;
                        }
                        # Creation ou bien Edition
                        $mid =  substr($mid, 0, 4) == "tmp_" ? 0 : $mid;
                        if (!isset($data ['uid']) || ($mid == 0)) {
                            #  Si pas d'uid (a priori nouveau) ou si nouveau message, on force lId du user
                            $uid = $data ['uid'] = $UserId;
                        }
                        if (($uid == 0) && (!$isadmin)) {
                            // Pas d'edition des uid 0 si pas admin
                            error_log("Tentative Edition de $mid (uid 0) par $UserId");
                            continue;
                        }
                        if ($mid == 0) {   //ajout
                            $m = new Hal_Evaluation_Moderation_Message ();
                        } else {   //modif
                            $m = $messages[$mid];
                            if (($uid != $m -> getId()) && !$isadmin) {
                                error_log("Tentative de changer un uid de message hors admin");
                                $error("Operation non autorise par " . $title);
                                continue;
                            }
                        }
                        unset($data['messageid']); // on ne change pas le messageId d'un objet!
                        $m->setOptions($data);
                        $ret = $m->save();
                        $messages[$m -> getMessageid()] = $m;  # On met a jour la liste qui sera affiche en retour!
                        $modif = true;
                        break;
                    default:
                        error_log("Message status ($status) is unknown");
                    }
                    # Attention: Ne rien mettre ici sinon, les continue du blocs switch vont l'executer
                    # Ou bien forcer le continue a sauter d'iteration de boucle
                }
            }

            if ($error != '') {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("$error");
            }
            if ($modif) {
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les réponses prédéfinies ont été mises à jour !");
            }
        }
        $this->view->uid = $UserId;
        $this->view->form = $newObj->getForm();
        $this->view->messages_list = $messages;
        $this->view->isAdmin = $isadmin;
    }

    /**
     * @param $order   string
     * @param $default string
     * @return string
     */
    static public function getSqlOrderField($order,$default = 'datedesc')
    {

        $corresp = [
            'docdesc' => 'IDENTIFIANT DESC',

            'docasc'   => 'IDENTIFIANT ASC',
            'contdesc' => 'SCREEN_NAME DESC',
            'contasc'  => 'SCREEN_NAME ASC',
            'typedesc' => 'TYPDOC DESC',
            'typeasc'  => 'TYPDOC ASC',
            'nbdesc'   => 'NBDOCVIS DESC',
            'nbasc'    => 'NBDOCVIS ASC',
            'datedesc' => 'DATESUBMIT DESC',
            'dateasc'  => 'DATESUBMIT ASC',
            'pordesc'  => 'SITE DESC',
            'porasc'   => 'SITE ASC',
        ];
        if (!array_key_exists($default, $corresp)) {
            $default  = 'datedesc';
        }
        if (array_key_exists($order, $corresp)) {
            return $corresp[$order];
        } else {
                return $corresp[$default];
        }
    }

    /**
     * Liste des documents sous embargo
     */
    public function embargoAction() {

        $this->view->forwardAction = $this->getRequest()->getActionName();

        $moderation = new Hal_Moderation ();
        $params = $this->getRequest()->getPost();
        if ($this->getRequest()->isPost() && isset($params ['docid'])) {
            $document = new Hal_Document ();
            $res = true;
            foreach ($params ['docid'] as $docid) {
                $document->setDocid($docid, true);
                $res = $res && $document->putOnModeration();
                if ($res) {
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les documents sélectionnés sont en modération");
                }
            }
        }
        /** $this->view->sites = $moderation->getSites(); */

        $orderTerm = isset($_GET['order']) ? $_GET['order'] : null;
        $sqlOrder = self::getSqlOrderField($orderTerm);
        $req = $moderation->getEmbargoDocuments()->order($sqlOrder);

        if (isset($_GET['queryid'])) {
            $query = 'd.IDENTIFIANT LIKE ?';
            $value = '%'.$_GET['queryid'].'%';
        } elseif (isset($_GET['queryuid'])) {
            $query = 'u.SCREEN_NAME LIKE ?';
            $value = '%' . $_GET['queryuid'] . '%';
        } elseif (isset($_GET['querytype'])) {
            $query = 'd.TYPDOC LIKE ?';
            $value = '%'.$_GET['querytype'].'%';
        } elseif (isset($_GET['querydate'])) {
            $query = 'd.DATESUBMIT LIKE ?';
            $value = '%'.$_GET['querydate'].'%';
        } elseif (isset($_GET['querypor'])) {
            $query = 's.SITE LIKE ?';
            $value = $_GET['querypor'];
        }
        if (isset($query) && isset($value)){
            $req->where($query,$value);
        }
        $this->getDocumentsPagination($req);
    }

    /**
     * @param Zend_Db_Select $request
     * @throws Zend_Paginator_Exception
     */
    public function getDocumentsPagination($request)
    {
        $currentPage = $this->_getParam ( 'page', 1 );
        $currentRows = $this->_getParam ( 'rows', 50);

        $adapter = new Zend_Paginator_Adapter_DbSelect($request);
        $documents = new Zend_Paginator ( $adapter );
        $documents->setItemCountPerPage ( $currentRows );
        $documents->setCurrentPageNumber ( $currentPage );

        $this->view->documents = $documents;
        Zend_Paginator::setDefaultScrollingStyle ( Hal_Settings_Search::PAGINATOR_STYLE );
        Zend_View_Helper_PaginationControl::setDefaultViewPartial ( 'partial/pagination.phtml' );
    }

    /**
     * Formulaire d'ajout/changement d'un embargo
     */
    public function ajaxgetfilecalendarAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return;
        }

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $params = $this->getRequest()->getPost();

        if ($this->getRequest()->isPost()) {
            $this->view->docid = $params['docid'];
            $this->view->id = $params['id'];
            $this->view->uid = $params['uid'];
            $this->view->datelimit = $params['datelimit'];
            $this->view->datevisible = $params['datevisible'];

            $this->render('file-embargo');
        }
    }

    /**
     * Retirer l'embargo d'un fichier
     */
    public function ajaxdelfileembargoAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return;
        }

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $params = $this->getRequest()->getPost();

        if ($this->getRequest()->isPost() && isset($params['id'])) {
            Hal_Moderation::putfileembargo($params ['id'],$params['uid']);
        }

        $document = new Hal_Document();
        $document->setDocid($params['docid']);
        $document->load('DOCID',true);

        $file = $document->getFileByFileId((int)$params['id']);

        $href = PREFIX_URL . $document->getId();
        if(count($document->getDocVersions()) > 1) {
            $href .= 'v' . $document->getVersion();
        }

        $href .= '/file/' . rawurlencode($file->getName());
        $this->view->data = $document->getDocid();
        $this->view->file = $file;
        $this->view->href = $href;
        $this->view->showType = true;
        $this->view->readEmbargo = true;
        $this->view->document = $document;

        $this->renderScript('partials/file-embargo.phtml');
    }

    /**
     * Mettre un embargo à un fichier
     */
    public function ajaxputfileembargoAction()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return;
        }
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $params = $this->getRequest()->getPost();

        if ($this->getRequest()->isPost()) {
            if (isset($params['id'])) {
                if (isset($params['date']) && $params['date'] <= $params['datelimit']) {
                    Hal_Moderation::putfileembargo($params ['id'], $params['uid'], $params['date']);
                }
            }
        }


        $document = new Hal_Document();
        $document->setDocid($params['docid']);
        $document->load('DOCID', true);
        $href = PREFIX_URL . $document->getId();
        $file = $document->getFileByFileId((int)$params['id']);
        if (count($document->getDocVersions()) > 1) {
            $href .= 'v' . $document->getVersion();
        }
        $href .= '/file/' . rawurlencode($file->getName());

        $this->view->docid = $document->getDocid();
        $this->view->datesubmit = $document->getSubmittedDate();
        $this->view->datevisible = $document->getDateVisibleMainFile();
        $this->view->file = $file;
        $this->view->href = $href;
        $this->view->showType = true;
        $this->view->readEmbargo = true;
        $this->view->document = $document;

        $this->renderScript('partials/file-embargo.phtml');
    }

    /**
     * Modification de l'ordre des auteurs en base
     */
    public function ajaxsortauthorsbaseAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $request = $this->getRequest();
        $params = $request->getPost();

        if ($request->isXmlHttpRequest() && $request->isPost() && isset($params['old_position']) && isset($params['new_position']) && isset($params['docid'])) {
            // Récupère les informations des auteurs dans les tables DOC_AUTHOR et DOC_AUTSTRUCT
            // $params['old_position'] tableau de DOCAUTHID
            for($i=0; $i < count($params['old_position']); $i++){
                $infonew[$i] = Hal_Document_Author::getInfoAuthor($params['new_position'][$i]);
                $infostructnew[$i] = Hal_Document_Author::getInfoAuthorStruct($params['new_position'][$i]);
            }
            // Supprime les auteurs en base
            for($i=0; $i < count($params['old_position']); $i++) {
                Hal_Document_Author::deleteAuthorStruct($params['old_position'][$i]);
                Hal_Document_Author::deleteAuthor($params['old_position'][$i]);
            }
            // Ajoute les auteurs avec les nouvelles valeurs
            for($i=0; $i < count($params['new_position']); $i++){
                Hal_Document_Author::insertAuthor($params['old_position'][$i],$infonew[$i]['DOCID'],$infonew[$i]['AUTHORID'],$infonew[$i]['QUALITY']);
                if (is_array($infostructnew[$i])) {
                    Hal_Document_Author::insertAuthorStruct($infostructnew[$i]['AUTSTRUCTID'],$params['old_position'][$i],$infostructnew[$i]['STRUCTID']);
                }
            }
        }
    }
    /**
     * Retourne l'Url de moderation d'un document
     * @param $docid
     * @return string
     */
    public function getUrlForViewingDocument ($docid) {
        return PREFIX_URL . 'moderate/documents/docid/' . $docid;
    }

    /**
     * @return string
     */
    public function getFormAction() {
        return '/moderate/documents';
    }
}
