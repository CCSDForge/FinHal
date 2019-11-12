<?php

/**
 * Visualisation d'une ressource fiche dans des formats variés
 * rem) voir les routes définies dans @see application.ini
 *
 */
class ViewController extends Hal_Controller_Action
{
    /**
     * @throws Zend_Controller_Response_Exception
     * @throws Zend_Exception
     * @return void
     */
    public function indexAction()
    {
        $request = $this->getRequest();
        $docid = $request->getParam('docid', 0);
        $identifiant = $request->getParam('identifiant', '');
        $version = $request->getParam('version', 0);
        $format = strtolower($request->getParam('format', ''));
        $lang = strtolower($request->getParam('lang', null));

        $document = Hal_Document::find($docid, $identifiant, $version);

        // Si dépôt non trouvé: on termine sur une erreur 404
        if ((false === $document) || ($document->getDocid() == 0)) {
            $this->getResponse()->setHttpResponseCode(404);
            $this->view->documentIdentifier = $identifiant;
            $this->view->documentVersion = $version;
            $this->renderScript('error/document-not-found.phtml');
            return;
        }

        // le document n'est pas en ligne : pas status 11 OU 111
        if (!$document->isOnline()) {
            if (Hal_Document_Acl::canView($document)) {
                // mais l'utilisateur a le droit de le voir
                $this->_helper->FlashMessenger->setNamespace('info')->addMessage("Ce document n'est pas visible en ligne.");
            } else {
                // l'utilisateur n'a pas de droits pour de le voir
                $this->getResponse()->setHttpResponseCode(404);
                $this->view->documentIdentifier = $identifiant;
                $this->view->documentVersion = $version;
                $this->renderScript('error/document-not-found.phtml');
                return;
            }
        }

        $this->view->submitAllowed = Hal_Site::getCurrentPortail()->submitAllowed();

        $isDocumentVisibleWithSolr = $document->isVisibleWithSolr();

        // Si pas visible dans cet espace + l'utilisateur n'a pas de privilege particulier
        if (!$isDocumentVisibleWithSolr && !Hal_Document_Acl::canView($document)) {
            // dépôt pas visible par solr dans cet espace
            $this->getResponse()->setHttpResponseCode(404);
            $this->view->documentIdentifier = $identifiant;
            $this->view->documentVersion = $version;

            // Si pas visible dans cet espace et Si pas du tout indexé
            if (!$document->isIndexedWithSolr()) {
                $this->renderScript('error/document-not-indexed-in-solr.phtml');
                return;
            }
            // ok il est indexé mais pas visible dans cet espace
            $this->renderScript('error/document-not-visible-in-space.phtml');
            return;
        }
        // L'utilisateur peut voir le document grâce à ses droits
        // mais pour les autres utilisateurs le document n'est pas visible dans cet espace :
        // on affiche un message d'information
        if (!$isDocumentVisibleWithSolr) {

            // on vérifie qu'il est indexé peut être que c'est pour ça qu'il n'est pas  visible dans cet espace
            if ($document->isIndexedWithSolr()) {
                $this->_helper->FlashMessenger->setNamespace('info')->addMessage("Ce document est indexé, mais il n'est pas accessible dans cet espace pour les autres utilisateurs.");

            } else {
                // il n'est pas indexé de toute façon
                $this->_helper->FlashMessenger->setNamespace('info')->addMessage("Ce document n'est pas indexé, il n'est pas accessible pour les autres utilisateurs.");

                // il est en ligne mais pas indexé, on le met dans la file d'indexation au cas ou ce serait un oubli
                if ($document->isOnline()) {
                    Ccsd_Search_Solr_Indexer::addToIndexQueue(array($document->getDocid()));
                    $this->_helper->FlashMessenger->setNamespace('info')->addMessage("Le document vient d'être placé dans la file d'indexation. Merci de patienter quelques minutes.");

                }
            }
        }


        // Todo: Hum: a lot of exit! Not sure it is good to exit in place of return
        // shutdown of plugin not done.


        /**
         * After HAL ID, the Url may contain a param, it can be:
         *     - reload to force cache to be deleted
         *     - a real export format (bibtex, json, tei,...)
         *     - a language code for switching language easily
         *
         * If language code is not valid on web-site, we do a Tei export
         */

        if ($format != '') {
            if ($format == 'reload') {
                $document->deleteCache();
                if (Hal_Auth::getUid() && in_array($document->getStatus(), [Hal_Document::STATUS_VISIBLE, Hal_Document::STATUS_REPLACED])) {
                    Ccsd_Search_Solr_Indexer::addToIndexQueue(array($document->_docid));
                }
                $this->redirect(PREFIX_URL . $document->getId(true));
                exit;
            } else if (in_array($format, Zend_Registry::get('languages'))) {
                // Special case of format is a language code
                if ($lang && $lang != $format) {
                    $this->redirect(str_replace("/$format", "/$lang", $_SERVER['REQUEST_URI']));
                    return;
                }
                // We change locale in session
                Hal_Translation_Plugin::setTranslation($format);

            } else {
                if (in_array($format, ['bibtex', 'dc', 'endnote', 'sip', 'json'])) {
                    Hal_Document_Visite::add($document->getDocid(), Hal_Auth::getUID(), $format);
                }
                $this->get($document, $format);
                exit;
            }
        }
        // No format explicitely asked, search for implicit rdf acceptance
        if (Hal_Rdf_Tools::requestRdfFormat($request)) {
            $this->get($document, 'rdf');
        } else {
            Hal_Document_Visite::add($document->getDocid(), Hal_Auth::getUID());
            $this->view->document = $document;
            // References
            $this->view->references = new Hal_Document_References($document->getDocid());
        }
    }

    /**
     * Retourne l'objet document dans le format approprié
     *
     * @param Hal_Document $document
     * @param string $format
     * @return void
     */
    private function get($document, $format = 'tei')
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        switch ($format) {
            case 'bibtex' :
                header('Content-Type: text/plain; charset: utf-8');
                echo $document->get('bib');
                break;
            case 'dc' :
                header('Content-Type: text/xml; charset: utf-8');
                echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL . $document->get('dc');
                break;
            case 'dcterms' :
                header('Content-Type: text/xml; charset: utf-8');
                echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL . $document->get('dcterms');
                break;
            case 'endnote' :
                header('Content-Type: text/plain; charset: utf-8');
                header('Content-Disposition: attachment; filename="' . $document->getId() . 'v' . $document->getVersion() . '.enw"');
                echo $document->get('enw');
                break;
            case 'sip' :
                header('Content-Type: text/xml; charset: utf-8');
                echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL . $document->get('sip');
                break;
            case 'json' :
                header('Content-Type: application/json; charset: utf-8');
                echo $document->get('json');
                break;
            case 'dump' :
                header('Content-Type: text/html; charset: utf-8');
                Ccsd_Tools::debug($document->toArray(), false, 'pre', $document->getId(), false);
                break;
            case 'rdf' :
                $rdf = new Hal_Rdf_Document($document->getDocid());
                header('Content-Type: text/xml; charset: utf-8');
                echo $rdf->getRdf();
                break;
            case 'html_references' :
                echo (new Hal_Document_References($document->getDocId()))->getHTMLReferences();
                break;
            default :
                header('Content-Type: text/xml; charset: utf-8');
                echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL . $document->get('tei');
                break;
        }
    }

    public function historyAction()
    {
        $this->_helper->layout()->disableLayout();
        $request = $this->getRequest();
        $docid = $request->getParam('docid', 0);
        $identifiant = $request->getParam('id', '');
        $version = $request->getParam('version', 0);
        $limit = $request->getParam('limit', '');
        $format = $request->getParam('format', null);

        $document = Hal_Document::find($docid, $identifiant, $version);
        if ($document instanceof Hal_Document && Hal_Document_Acl::canViewHistory($document)) {
            $history = Hal_Document_Logger::get(0, $document->getId(), $limit);
            if ($format && $format == 'json') {
                echo Zend_Json::encode($history);
                $this->_helper->viewRenderer->setNoRender();
            } else {
                $this->view->history = $history;
            }
            $this->view->showAll = $limit;
        }
    }

    /**
     * Called by Ajax: not a direct Http method
     * Display a table widget for metadata
     *
     * NO RIGHT CONTROL!
     */
    public function metadataAction()
    {
        $request = $this->getRequest();
        $docid = $request->getParam('docid', 0);
        $this->_helper->layout()->disableLayout();
        if ($this->getRequest()->isXmlHttpRequest() && $docid) {
            $this->view->hideFiles = true;
            $this->view->document = Hal_Document::find($docid);
            $this->renderScript('document/admin.phtml');
        }
    }

    /**
     *
     */
    public function referencesAction()
    {
        $this->_helper->layout()->disableLayout();
        $request = $this->getRequest();
        $params = $this->getRequest()->getParams();
        $refId = $request->getParam('refId', null);
        $docId = $request->getParam('docId', null);
        $modifications = $request->getParam('modifications');
        $actionReference = $request->getParam('actionReference', null);

        $viewscriptname = 'document/references.phtml';
        $formscriptname = 'document/form-reference.phtml';

        if ($request->isXmlHttpRequest() && isset($docId) && isset($actionReference)) {
            $document = Hal_Document::find($docId);
            $this->view->document = $document;
            $references = new Hal_Document_References($document->getDocId());
            $references->load();
            $this->view->references = $references;
            if ($actionReference == 'renderReferences') {
                $this->renderScript($viewscriptname);
            } else if (isset($refId) && $actionReference == 'prepareToEditReference') {
                $this->view->form = json_encode($references->prepareToEditReference($refId), JSON_UNESCAPED_UNICODE);
                $this->renderScript($formscriptname);
            } else if (isset($refId) && isset($modifications) &&
                $actionReference == 'setReference' &&
                Hal_Document_Acl::canUpdate($document)) {
                $references->editReference($refId, $modifications);
                $this->renderScript($viewscriptname);
            } else if (isset($refId) && $actionReference == 'removeReference') {
                if (Hal_Document_Acl::canUpdate($document)) {
                    $references->removeReference($refId);
                }
                $this->renderScript($viewscriptname);
            } else if (isset($refId) && $actionReference == 'validateReference') {
                if (Hal_Document_Acl::canUpdate($document)) {
                    $references->validateReference($refId);
                }
                $this->renderScript($viewscriptname);
            } else if (isset($params['dataForm']) && $actionReference == 'checkInputData') {
                $this->view->form = json_encode($references->checkReferenceModifications($params['dataForm']), JSON_UNESCAPED_UNICODE);
                $this->renderScript($formscriptname);
            }
        }
    }

    /**
     * Cette action est-elle utilisee?
     */
    public function rdfAction()
    {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getParams();
        $this->_helper->viewRenderer->setNoRender();   // TODO ?????????????????????? et renderScript ensuite...
        if (isset($params['docid'])) {
            $document = Hal_Document::find($params['docid']);
            $this->view->document = $document;
            $this->view->addScriptPath(LIBRARYPATH . '/Ccsd/Rdf/');
            $this->renderScript('gui.phtml');
        }
    }

    /**
     * Affichage de la page concernant la conformite au RGAA
     */
    public function conformitergaaAction()
    {
        $this->renderScript('view/conformiteRgaa.phtml');
    }
    /**
     * Affichage de la page concernant l'aide accessibilite
     */
    public function accessibilityhelpAction()
    {
        $this->renderScript('view/accessibilityHelp.phtml');
    }

    /**
     * Privacy page / GPDR
     */
    public function privacyAction()
    {
        $this->renderScript('view/privacy.phtml');
    }
    /**
     * Legal notice
     */
    public function legalnoticeAction()
    {
        $this->renderScript('view/legalnotice.phtml');
    }
}
