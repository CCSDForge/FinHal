<?php

class CollectionController extends Hal_Controller_Action {
    /**
     * Session courante
     * @var Hal_Session_Namespace
     */
    protected $_session = null;

    public function indexAction() {
        $this->view->title = "Collections";
        $this->renderScript('index/submenu.phtml');
    }

    /**
     * Initialisation de la Collection
     */
    public function init()
    {
        //Définition de la session et de l'espace temporaire de dépôt des fichiers
        $this->_session = new Hal_Session_Namespace(SESSION_NAMESPACE);

        //TODO: Pour l'instant pas d'unset car pas d'endroit pour le faire
        if ($this->getRequest()->getActionName() == 'create') {
            // TODO: A mettre dans l'action create
            // On prends la collection specifiee en parametre  ou bien on garde celle en session
            $sid = 0;
            $code = '';

            if ($this->getRequest()->isPost()) {
                $sid = $this->getParam('sid', 0);
                $code = $this->getParam('code', '');
            } else {
                // Si GET alors soit le id est precise dans l'url, soit nous sommes reellement dans un create
                $this->_session->collection = null;
            }
            if ($code == '') {
                $code = $this->getRequest()->getParam('tampid', '');
            }

            //Nouvelle collection
            if ($sid != 0) {
                $this->_session->collection = Hal_Site::loadSiteFromId($sid);
            } else {
                if ($code != '') {
                    $this->_session->collection = Hal_Site::exist($code, Hal_Site::TYPE_COLLECTION, true);
                }
            }
        }
    }

    /**
     * Liste les collections de l'archive
     */
    public function listAction() {
        $params = $this->getRequest()->getPost();
        $this->view->isHalAdmin = Hal_Auth::isHALAdministrator();
        if ($this->getRequest()->isPost() && isset($params['q'])) {
            $this->view->collections = Hal_Site::search($params['q'], Hal_Site::TYPE_COLLECTION);
            if (count($this->view->collections) == 1) {
                $this->view->q = $this->view->collections[0]['SITE'];
            } else {
                $this->view->q = $params['q'];
            }
        }
    }

    public function ajaxexistAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        echo Hal_Site::exist($this->getParam('site', ''), Hal_Site_Collection::TYPE_COLLECTION, false);
    }

    public function ajaxgetfullcritereAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->getRequest()->getPost();
        $res = array();
        if (isset($params['parentids']) && is_array($params['parentids'])) {
            foreach ($params['parentids'] as $sid) {
                $critere = Hal_Site_Collection::getFullCritere($sid);
                if ($critere != '') {
                    $res[] = $critere;
                }
            }
        }
        echo implode(' AND ', $res);
    }

    public function ajaxnbdocAction() {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getPost();
        if (isset($params['critere'])) {
            try {
                $res = Ccsd_Tools::solrCurl("q=" . urlencode($params['critere']) . "&start=0&rows=0&wt=phps&facet=true&facet.field=submitType_s&omitHeader=true");
                $res = unserialize($res);
            } catch (Exception $exc) {
                error_log($exc->getMessage(), 0);
            }

            if (isset($res['facet_counts']['facet_fields']['submitType_s'])) {
                $this->view->data = $res['facet_counts']['facet_fields']['submitType_s'];
            }
        }
    }

    public function ajaxgetdoctotamponnateAction() {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getPost();
        if (isset($params['sid'])) {
            $collection = Hal_Site::loadSiteFromId($params['sid']);
            $this->view->sid = $params['sid'];
            $this->view->documents = $collection->getDocumentsToTamponnate(Hal_Auth::getUid());
        }
    }

    public function ajaxgetdocAction() {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getPost();
        if (isset($params['doc']) && isset($params['sid'])) {
            if (preg_match('/^([a-z0-9]+(_|-)[0-9]+)v([0-9]+)$/i', $params['doc'], $match)) {
                $q = 'halId_s:' . $match[1] . ' AND version_i:' . (int) $match[3];
            } else {
                $q = 'halId_s:' . $params['doc'];
            }
            try {
                $res = Ccsd_Tools::solrCurl("q=" . urlencode($q) . "&start=0&rows=1&fl=docid,citationFull_s,collId_i&wt=phps&omitHeader=true");
                $res = unserialize($res);
            } catch (Exception $exc) {
                error_log($exc->getMessage(), 0);
            }
            if (isset($res['response']['docs'][0])) {
                $this->view->document = $res['response']['docs'][0];
                $this->view->sid = $params['sid'];
            }
        }
    }

    /**
     * Création / Modification de collection
     */
    public function createAction() {
        $populate = true;
        if ($this->getRequest()->isPost()) {

            $populate = false;
        }
        /** @var Hal_Site_Collection $collection */
        $collection = $this->_session->collection;

        if ($collection) {
            $sid = $collection->getSid();
            $code = $collection->getShortname();
            $form = $collection->getForm($populate);
            $newCollection = false;
        } else {
            $form = Hal_Site_Collection::getDefaultForm();
            $newCollection = true;
        }

        if (!$newCollection) {
            $form->getElement('code')->setRequired(false);
            $collectionsSupBefore = Hal_Site_Collection::getCollectionsSup($sid,false);
            $collectionNameBefore = $collection->getShortName();
            $collectionCategoryBefore = $collection->getCategory();
        }


        if ($this->getRequest()->isPost()) {
            $postParams = $this->getRequest()->getPost();
            if ($form->isValid($postParams)) {

                if ($collection == null) {
                    // Création d'une nouvelle collection
                    $collection = new Hal_Site_Collection($postParams, true);
                } else {
                    // Modification d'une collection existante
                    $collection->setParams($postParams, true);
                }

                if ($collection->save()) {
                    $tamponate = isset($postParams['tamponnate']) && $postParams['tamponnate'];
                    if ($tamponate) {
                        $collection->tamponnate();
                    }
                    $collection->createFilePaths();

                    if ($collection->getShortname() != '') {
                        $code = $collection->getShortname();
                    }
                    unset($this->_session->collection); //On reset la session

                    if ($newCollection) {
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Création de la collection ' . '<strong>' . $code . '</strong>');
                    } else {
                        $collectionsSupAfter = Hal_Site_Collection::getCollectionsSup($sid,false);
                        $collectionNameAfter = $collection->getShortName();
                        $collectionCategoryAfter = $collection->getCategory();
                        if ($collectionsSupBefore != $collectionsSupAfter || $collectionNameBefore != $collectionNameAfter || $collectionCategoryBefore != $collectionCategoryAfter) {
                            //Modification sur les collections supérieures, on réindexe tous les docs de la collection
                            $collection->reindexDocuments();
                        }

                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Les modifications ont été prises en compte pour la collection ' . '<strong>' . $code . '</strong>');
                        return $this->redirect('/collection/list');
                    }

                    $form = $collection->getForm(true);
                } else {
                    $this->_helper->FlashMessenger->setNamespace('info')->addMessage("L'enregistrement de la collection est incomplet. Toutes les informations n'ont pas été prises en compte");
                }
            } else {
                // On conserve simplement le formulaire invalide tel quel et on affiche les erreurs
                $this->_helper->FlashMessenger->setNamespace('info')->addMessage("Les donnees ne sont pas valides.");
            }
        }


        if (!$newCollection) {
            $form->getElement('code')->setAttrib('disabled', 'disabled')->setValue($code);
        }

        $this->view->fields = Hal_Settings::getConfigFile('solr.hal.AdvSearchFields.json');

        $this->view->form = $form;
    }

    public function deleteAction() {
        if (!Hal_Auth::isHALAdministrator()) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous n'avez pas le droit de suppression de la collection.\nLes demandes de supression doivent etre faites aupres de hal-support@ccsd.cnrs.fr");
            $this->redirect('/collection/list');
        } else {
            $sid = 0;
            $params = $this->getRequest()->getPost();
            if ($this->getRequest()->isPost() && isset($params['sid'])) {
                // Confirmation d'effacement: on a un sid
                $sid = $params['sid'];
                $collection = Hal_Site::loadSiteFromId($sid);
                try {
                    $collection->delete();
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("La collection a été supprimée");
                } catch (Exception $exc) {
                    error_log("effacement de la collection: $sid; Error: " . $exc->getMessage(), 0);
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("La collection n'a pas été supprimée completement\n");
                }
                $this->redirect('/collection/list');
            }
            // Seul un Nom de collection: Affichage de la collection pour confirmation d'effacement
            $code = $this->getRequest()->getParam('tampid', '');
            $collection = Hal_Site::loadSiteFromId($sid);

            if ($collection == null) {
                $collection = Hal_Site::exist($code, Hal_Site::TYPE_COLLECTION, true);
            }
            $sid = 0;
            if ($collection != null) {
                $sid = $collection->getSid();
            }
            if ($sid == 0) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("La collection n'existe pas");
            } else {
                $this->view->collection = $collection;
            }
        }
    }

    /**
     * Methode permettant de tamponner un document
     */
    public function addAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->getRequest()->getPost();
        if (isset($params['sid']) && (Hal_Auth::isTamponneur($params['sid']) || Hal_Auth::isHALAdministrator())) {
            $sid = $params['sid'];
            $site = Hal_Site::loadSiteFromId($sid);
            $docids = [];
            if (isset($params['docid'])) {
                //Tamponnage d'un ou plusieurs document
                if (is_array($params['docid'])) {
                    $docids = $params['docid'];
                } else {
                    $docids = array($params['docid']);
                }
            } else if (isset($params['query'])) {
                try {
                    $result = unserialize(Ccsd_Tools::solrCurl($params['query'], 'hal', 'apiselect'));
                } catch (Exception $exc) {
                    error_log($exc->getMessage(), 0);
                }
                if (isset($result['response']['docs']) && is_array($result['response']['docs']) && count($result['response']['docs'])) {
                    foreach ($result['response']['docs'] as $docid) {
                        $docids[] = $docid['docid'];
                    }
                }
            }
            $res = count($docids) > 0;
            foreach ($docids as $docid) {
                $tmp = Hal_Document_Collection::add($docid, $site, Hal_Auth::getUid());
                $res = $res || $tmp;
            }
            echo $res;
        }
    }

    /**
     * Methode permettant de détamponner un document
     */
    public function delAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->getRequest()->getPost();
        if (isset($params['sid']) && (Hal_Auth::isTamponneur($params['sid']) || Hal_Auth::isHALAdministrator())) {
            $sid = $params['sid'];
            $site = Hal_Site::loadSiteFromId($sid);
            $docids = [];
            if (isset($params['docid'])) {
                //Tamponnage d'un ou plusieurs document
                if (is_array($params['docid'])) {
                    $docids = $params['docid'];
                } else {
                    $docids = array($params['docid']);
                }
            } else if (isset($params['query'])) {
                try {
                    $result = unserialize(Ccsd_Tools::solrCurl($params['query'], 'hal', 'apiselect'));
                } catch (Exception $exc) {
                    error_log($exc->getMessage(), 0);
                }
                if (isset($result['response']['docs']) && is_array($result['response']['docs']) && count($result['response']['docs'])) {
                    foreach ($result['response']['docs'] as $docid) {
                        $docids[] = $docid['docid'];
                    }
                }
            }
            $res = count($docids) > 0;
            foreach ($docids as $docid) {
                $tmp = Hal_Document_Collection::del($docid, $site, Hal_Auth::getUid());
                $res = $res || $tmp;
            }
            echo $res;
        }
    }

    /**
     * Methode permettant de masquer un document de la liste des tamponneurs
     */
    public function hideAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->getRequest()->getPost();
        if (isset($params['docid']) && isset($params['sid']) && (Hal_Auth::isTamponneur($params['sid']) || Hal_Auth::isHALAdministrator())) {
            if (!is_array($params['docid'])) {
                $params['docid'] = array($params['docid']);
            }
            $res = true;
            foreach ($params['docid'] as $docid) {
                $tmp = Hal_Document_Collection::hide($docid, $params['sid'], Hal_Auth::getUid());
                $res = $res && $tmp;
            }
            echo $res;
        }
    }

    private function duplicateForm() {
        $form =  new Ccsd_Form();
        $form->setAttrib('class', 'form');
        $form -> addElement('text', 'oldcode' , [ 'label' =>	'Acronyme de la collection a dupliquer', 'description'=>'Identifiant de la collection a dupliquer', 'required' => true]);
        $form -> addElement('text', 'newcode', [ 'label' => 'Nouvel acronyme', 'description' => 'Acronyme de la nouvelle collection']);
        $form -> addElement('text', 'name'   , [ 'label' => 'Intitule de la nouvelle collection', 'description' => 'Intitule complet de la nouvelle collection']);
        $form -> addElement('text', 'url'    , [ 'label' => 'Url de la nouvelle collection',  'description' => 'Peut etre vide, par defaut: ....']);
        return $form;
    }

    public function duplicateAction() {
        $oldcode = '';
        $newcode = '';
        $name    = '';
        $url     = '';
        $do      = false;

        if  (!Hal_Auth::isAdministrator()) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous n'avez pas le droit de creer une collection par duplication");
            $this->redirect('/collection/list');
        }
        $form = $this -> duplicateForm();
        $this -> view -> form = $form;
        if ($this->getRequest()->isPost()) {
            
            $oldcode = strtoupper($this->getParam('oldcode', ''));
            $newcode = strtoupper($this->getParam('newcode', 0));
            $name    = $this->getParam('name'   , '');
            $url     = $this->getParam('url'    , '');

            # La validation des parametres ne valide pas l'existence ou non des codes, seulement leur syntaxe.
            if (! $form->isValid($this->getRequest()->getPost())) {
                // Message erreur
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Operation Invalide");
                $this->redirect('/collection/duplicate');
            }
            /** @var Hal_Site_Collection $collection */
            $collection = Hal_Site::exist($oldcode, Hal_Site::TYPE_COLLECTION, true);
            if ($collection -> getSid() === 0) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Erreur rencontree: Collection $oldcode inexistante");
                return;
            }
            // On duplique maintenant
            if (($ret = $collection ->createNewFromDuplicate($newcode, $name, $url)) !== true) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Erreur rencontree: $ret");
                return;
            }
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage("La collection a été dupliquee");
        }
    }

    /**
     * Annulation de la création/modification d'une collection
     */
    public function resetAction()
    {
        unset($this->_session->collection);

        return $this->redirect('/collection/list');
    }

    /**
     * Vérifie une collection
     * La collection ne doit pas avoir de lien avec la collection selectionnée :
     * Sid différent de la collection selectionnée + Sid différent des parents de la collection selectionnée
     */
    public function ajaxverifcollAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $sid = $this->getRequest()->getParam('sid', 0);
        if ($sid == 0) {
            $this->getResponse()->setHttpResponseCode(500);
            return;
        }

        $coll = Hal_Site::loadSiteFromId($sid);
        if ($coll == null) {
            $this->getResponse()->setHttpResponseCode(500);
        }

        if ($this->_session->collection == null) {
            // En train de creer une collection, le parent ne peux pas faire de boucle
            // OK
            return;
        }

        if (!$this->_session->collection->addParent($coll)){ //Si on a pas réussi a ajouté le parent
            $this->getResponse()->setHttpResponseCode(500);
        }
        // OK
        return;
    }
}
