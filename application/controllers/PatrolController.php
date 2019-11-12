<?php

/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 10/01/2014
 * Time: 10:04
 */
class PatrolController extends Hal_Controller_Action {

    public function preDispatch() {
        // On patrouille une collection: soit la collection du portail courant, soit la collection courant
        // Dans tous les cas, le Portail sous jacent doit avoir l'option de patrouillage
        $coll = \Hal\Patrol::getPatrolSite();
        if ($coll && Hal_Auth::isPatroller($coll -> getSid())) {
            return;
        }
        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne disposez pas des privilèges pour accéder à cette action");
        $this->view->message = "Accès non autorisé";
        $this->view->description = "";
        $this->renderScript('error/error.phtml');
    }


    public function indexAction() {
        $this->forward('listdocuments');
    }

    /**
     * @param Hal_Site $portal
     * @param array $filterInfo
     * @param string $orderTerm
     * @param string $filter
     * @param string $queryid
     * @param string $queryuid
     * @param string $querytype
     * @param string $querydate
     * @param string $querynbdoc
     * @param string $querypor
     * @param null $orderTerm
     * @throws Zend_Paginator_Exception
     */
    public function listDocument($portal, &$filterInfo, $orderTerm = null, $filter = null,$queryid = null, $queryuid =null, $querytype=null, $querydate  =null, $querynbdoc = null, $querypor = null) {

        /**
         *  Les Sites semblent etre recuperes par $moderation->getDocuments()
        $this->view->sites = $moderation->getSites();
         */
        /** Recuperation des documents pour cet utilisateur */

        $req = \Hal\Patrol::getDocuments($portal);
        $filterInfo = [];

        /** ajout des filtre d'interfaces */
        $filterList = $this->addFilterToRequest($req, $filter, $queryid, $queryuid, $querytype, $querydate, $querynbdoc, $querypor);
        /** Ajout ordre interface */
        $sqlOrder = self::getSqlOrderField($orderTerm);
        $req->order($sqlOrder);

        /** Pagination */
        $this->getDocumentsPagination($req);
        $filterInfo = $filterList;
    }

    public function listdocumentsAction()
    {
        /**
         * Les documents sont ceux marque a patrouiller sur le portail,
         * filtrés si besoin pas les filtres de moderation/patrouillage
         */
        $render = "documents";
        $request = $this->getRequest();
        $filter     = $request->getParam('filtre', null);
        $queryid    = $request->getParam('queryid', null);
        $queryuid   = $request->getParam('queryuid', null);
        $querytype  = $request->getParam('querytype', null);
        $querydate  = $request->getParam('querydate', null);
        $querynbdoc = $request->getParam('querynbdoc', null);
        $querypor   = $request->getParam('querypor', null);

        $portal = Hal_Site::getCurrentPortail();
        if ($portal === null) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Ce portail n'implemente pas le patrouillage");
            $this->redirect('/');
            return;
        }
        $filterList = [];
        try {
            $this->listDocument($portal, $filterList, $filter, $queryid, $queryuid, $querytype, $querydate, $querynbdoc, $querypor);
        } catch (Zend_Paginator_Exception $e) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Erreur de pagination");
        }
        // Param de view
        $this->view->mode = $this; // Mode moderation pour les scripts de la vue
        $this->view->inProgress = Hal_Moderation::documentsInProgress(Hal_Auth::getUid());
        $this->view->filterList = $filterList;
        $this->view->pageDescription = "Merci de bien vouloir vérifier, valider, patrouiller les articles suivants";
        $this->view->addFilterMenu = false;
        $this->renderScript('moderate/documents.phtml');
    }

    public function displaydocumentsAction() {
        $idParamName = 'docid'; // A ce controller, on fourni une liste d'identifiant.  PAS de DOCIDS

        $request = $this->getRequest();
        $params = $request->getParams();
        if (!isset($params [$idParamName])) {
            $this->forward('listDocuments');
        }
        $listIds = $params [$idParamName];
        if (!is_array($listIds)) {
            $listIds = [$listIds];
        }
        // Affichage de documents
        $messages = new Hal_Evaluation_Moderation_Message();
        $this->view->mode = $this; // Mode moderation pour les scripts de la vue
        $this->view->responses = $messages->getList(Hal_Auth::getUid());
        $this->view->document = new Hal_Document();
        $this->view->docids = $listIds;
        // Indicateur de modération
        foreach ($listIds as $docid) {
            Hal_Moderation::addDocInProgress($docid, Hal_Auth::getUid(), $this->getRequest()->getClientIp());
        }
        $this->renderScript('patrol/documents-actions.phtml');
    }

    /**
     * Liste des documents à modérer
     */
    public function doactionAction() {

        $idParamName = 'docid'; // A ce controller, on fourni une liste d'identifiant.  PAS de DOCIDS
        // $moderation = new Hal_Moderation();
        $request = $this->getRequest();
        $params = $request->getParams();
        if (!isset($params [$idParamName])) {
            $this->forward('listdocuments');
        }
        $listIds = $params [$idParamName];
        if (!is_array($listIds)) {
            $listIds = [$listIds];
        }

        if (!isset($params ['useraction'])) {
            //  No action: return ti list
            $this->forward('listdocuments');
        }
        $action = $params ['useraction'];

        $this->view->forwardAction = $this->getRequest()->getActionName();
        $comment = isset($params ['comment']) ? $params ['comment'] : '';
        $res = [] ;
        $errors = [];

        switch ($action) {
            case 'back':
                Hal_Moderation::delDocInProgress($this->getRequest()->getClientIp());
                $this->forward('listDocuments');
                break;

            case 'edit':
                if (count($listIds) > 1) {
                    $this->forward('listdocuments');
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("On ne peut editer qu'un seul document a la fois");
                }
                $id = $listIds[0];  // Identifiant!
                $document = new Hal_Document($id, 0, '', true);
                $depositSite = Hal_Site::loadSiteFromId($document->getSid());
                $this->redirect($depositSite->getUrl().'/submit/update/docid/' . $document->getDocid());
                exit();
                break;

            case 'annotate':
                foreach ($listIds as $id) {
                    $document = new Hal_Document(0, $id,0, true);
                    $ok = $document->annotate(Hal_Auth::getUid(), $comment);
                    if ($ok) {
                        $res[] = $document->getId();
                    } else {
                        $errors[] = $document->getId() . " n'a pas été annoté correctement";
                    }
                    Hal_Moderation::delDocInProgress($this->getRequest()->getClientIp(), $document->getDocid());
                }
                break;
            case 'patrol':
                $site = \Hal\Patrol::getPatrolSite();
                foreach ($listIds as $id) {
                    $document = new Hal_Document($id, 0, 0, true);
                    $ident = $document -> getId();
                    $patrol = Hal\Patrol::load($ident, $site);
                    if ($patrol ===null) {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Le document $ident n'etait pas a patrouiller");
                        $this->forward('listdocuments');
                        return;
                    }
                    $patrol->markPatrol($document->getVersion());
                    try {
                        $patrol->save();
                        $res[] = $document->getId();
                    } catch (Exception $e) {
                        $errors[] = $document->getId() . "N'a pas été correctement patrouillé";
                    }
                    Hal_Moderation::delDocInProgress($this->getRequest()->getClientIp(), $document-> getDocid());
                }
                break;

            default:
                break;
        }

        if (count($res) == 1) {
            $text = $this->view->translate("patrol-action-$action");
            $text = str_replace('%%DOCUMENT%%', $res [0], $text);
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage($text);
        } else if (count($res) > 1) {
            $text = $this->view->translate("patrol-action-$action-multiple");
            $text = str_replace('%%DOCUMENTS%%', implode(', ', $res), $text);
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage($text);
        } else if (count($errors)) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage(implode(',', $errors));
        }
        $this->forward('listdocuments');
    }

    /**
     * @param $req Zend_Db_Select
     * @param Zend_Controller_Request_Http $request
     * @return array
     */
    private function addFilterToRequest($req, $filter, $queryid, $queryuid, $querytype, $querydate, $querynbdoc, $querypor) {

        $filterList = [
            'filter'     => $filter,
            'queryid'    => $queryid ,
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
     * Retourne l'Url de patrouillage d'un document
     * @param $docid
     * @return string
     */
    public function getUrlForViewingDocument ($docid) {
        return PREFIX_URL . 'patrol/displaydocuments/docid/' . $docid;
    }

    /**
     * @return string
     */
    public function getFormAction() {
        return '/patrol/displaydocuments';
    }
}
