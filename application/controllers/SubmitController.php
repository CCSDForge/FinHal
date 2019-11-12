<?php

/**
 * Dépôt, modif, ajout de fichier
 *
 */
class SubmitController extends Hal_Controller_Action
{
    /**
     * Session courante
     * @var Hal_Session_Namespace
     */
    protected $_session = null;

    /**
     * @var Hal_Submit_Manager
     */
    protected $_submitManager = null;

    /**
     * Répertoire de stockage des fichiers associés au dépôt
     * @var null
     */
    protected $_tmpDir = null;

    const SUBMIT_CONTROLER = 'submit';
    const SUBMIT_PREFIXURL = '/submit/';

    const ERROR_SUPPORT = "Erreur du serveur. Veuillez contacter le support : hal.support@ccsd.cnrs.fr";

    /**
     * 1- Initialisation du dépôt
     */
    public function init()
    {
        if (Hal_Settings_Features::hasDocSubmit() === false) {
            $this->redirect('/error/feature-disabled');
        }

        //Définition de la session et de l'espace temporaire de dépôt des fichiers
        $this->_session = new Hal_Session_Namespace(SESSION_NAMESPACE);
        $this->_tmpDir = PATHTEMPDOCS . Zend_Session::getId() . '/';

        $user = Hal_Auth::getUser();

        if (!isset($this->_session->document)) {
            //Nouveau document
            $this->_session->type = Hal_Settings::SUBMIT_INIT;
            $this->_session->document = new Hal_Document();

            if (!($user instanceof Hal_User)) {
                Ccsd_Tools::panicMsg(__DIR__, __LINE__, 'ATTENTION ON A UN USER VIDE POUR L\'ACTION : '.$this->getRequest()->getActionName());
            }

            $this->_session->document->setSid(SITEID)->setContributor($user);

            // On initialise le type de document selon le type par défaut du portail
            $this->_session->document->setTypDoc(Hal_Settings::getDefaultTypdoc());

            // On ajoute les préférences par défaut de l'utilisateur courant
            $this->_session->document->addUserMetas($user);

            // On initialise les métadonnées avec les données par défaut du type de document
            $submitManagerClass = new Hal_Submit_Manager($this->_session);
            $this->_session->document->addMetas($submitManagerClass->getDefaultMetas(), 0);

            // On ajoute l'utilisateur comme auteur par défaut
            $this->_session->document->addUserAuthors($user);
        } else {
            if ($this->_session->document->getContributor('uid') == 0) {
                if ($user instanceof Hal_User) {
                    Ccsd_Tools::panicMsg(__DIR__, __LINE__, 'ATTENTION ON UTILISE UN DOCUMENT AVEC UID=0 POUR L\'ACTION : ' . $this->getRequest()->getActionName() . ' POUR L\'UTILISATEUR : ' . $user->getUid());
                    $this->_session->document->setContributor($user);
                } else {
                    Ccsd_Tools::panicMsg(__DIR__, __LINE__, 'ATTENTION ON UTILISE UN DOCUMENT AVEC UID=0 POUR L\'ACTION : ' . $this->getRequest()->getActionName() . ' AVEC UN UTILISATEUR TOUJOURS NULL');
                }
            }
        }

        $this->_session->document->setTypeSubmit($this->_session->type);

        if (! isset($this->_session->submitStatus)) {
            // Le mode de dépôt du portail est prioritaire sur les préférences de dépôt de l'utilisateur
            $mode = Hal_Settings::submitMode();
            // Si le mode de dépôt n'est pas imposé par le portail, on prend celui de l'utilisateur
            if (!isset($mode)) {
                $mode = $user->getMode();
            }
            //Initialisation des étapes du dépôt
            $this->_session->submitStatus = new Hal_Submit_Status($this->_session->type, $mode);
        }

        if (! isset($this->_session->submitOptions)) {
            //Initialisation des étapes du dépôt
            $this->_session->submitOptions = new Hal_Submit_Options();
        }

        $this->_submitManager = new Hal_Submit_Manager($this->_session);
    }

    /**
     * 2- Initialisation de toutes les vues au chargement de la page
     */
    public function indexAction()
    {
        // Initialisation des étapes en vue détaillée pour autre qu'un nouveau dépôt
        if (Hal_Settings::SUBMIT_INIT != $this->_session->type && Hal_Settings::SUBMIT_REPLACE != $this->_session->type ) {
            $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_FILE)->setMode(Hal_Settings::SUBMIT_MODE_DETAILED);
            $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_META)->setMode(Hal_Settings::SUBMIT_MODE_DETAILED);
            $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_AUTHOR)->setMode(Hal_Settings::SUBMIT_MODE_DETAILED);
        }

        $this->initializeIndexView();

        //Initialisation de toutes létapes du dépôt
        foreach ($this->_session->submitStatus->getSteps() as $step) {
            $step->initView($this->view, $this->_session->document, $this->_session->type);
        }
    }

    /**
     * Modification suite à une demande de la modération
     * ==> tu peux tout faire dessus
     */
    public function modifyAction()
    {
        if ($this->getParam('docid') != 0 ) {
            $document = new Hal_Document($this->getParam('docid'));
            $document->load('DOCID',true);

            if (Hal_Document_Acl::canModify($document)) {
                $this->_session->type = Hal_Settings::SUBMIT_MODIFY;
                $this->_session->submitStatus->setSubmitType(Hal_Settings::SUBMIT_MODIFY);

                $this->_submitManager->copyFilesInTmp($document->getFiles(), $this->_tmpDir);
                $this->_session->document = $document;
                // Initialisation de la validité du dépot
                $this->_session->submitStatus->update($document, $this->_session->type, [Hal_Settings::SUBMIT_STEP_FILE, Hal_Settings::SUBMIT_STEP_META, Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]);

                $this->redirect('/submit/index');
            } else {
                $this->redirect('/');
            }
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Utiliser comme modele
     * ==> nouveau dépot "pré-remplis"
     */
    public function copyAction()
    {
        if ($this->getParam('docid') != 0 ) {
            $document = new Hal_Document($this->getParam('docid'));
            $document->load('DOCID',true);

            if ($document instanceof Hal_Document) {

                $this->_session->type = Hal_Settings::SUBMIT_INIT;

                $document->setDocid(0, false);
                $document->setId('');
                $document->setVersion(1);
                // On reset la propriété. ça posait problème notamment pour les envois de mails.
                $document->setOwner(array(Hal_Auth::getUid()));
                $document->setContributor(Hal_Auth::getUser());
                $document->setCitation('ref', null);
                $document->setCitation('full', null);
                $document->initFiles();

                $document->resetSomeMetaForTypedocWhenReplace();

                $this->_session->document = $document;
                // Initialisation de la validité du dépot
                $this->_session->submitStatus->update($document, $this->_session->type, [Hal_Settings::SUBMIT_STEP_FILE, Hal_Settings::SUBMIT_STEP_META, Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]);

                $this->redirect('/submit/index');
            } else {
                $this->redirect('/');
            }
        } else {
            $this->redirect('/');
        }
    }

    /**
     * 1b - Modification des métadonnées d'un document
     * ==> Pas pouvoir modifier ni ajouter des fichiers
     * ==> étape courante : métadonnées
     */
    public function updateAction()
    {
        if ($this->getParam('docid') != 0 ) {
            $document = Hal_Document::find($this->getParam('docid'));
            if (Hal_Document_Acl::canUpdate($document, $this->getParam('pwd', ''))) {
                $this->_session->type = Hal_Settings::SUBMIT_UPDATE;
                $this->_session->submitStatus->setSubmitType(Hal_Settings::SUBMIT_UPDATE);

                $this->_session->document = $document;
                $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_FILE)->setFilesNotDeletable(array_keys($this->_session->document->getFiles()));
                $this->_session->submitStatus->setCurrentStep(Hal_Settings::SUBMIT_STEP_META);
                // Initialisation de la validité du dépot
                $this->_session->submitStatus->update($document, $this->_session->type, [Hal_Settings::SUBMIT_STEP_FILE, Hal_Settings::SUBMIT_STEP_META, Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]);
                $this->redirect('/submit/index');
            } else {
                $this->redirect('/');
            }
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Ajout d'une nouvelle version
     */
    public function replaceAction()
    {
        if ($this->getParam('docid') != 0 ) {
            $document = Hal_Document::find($this->getParam('docid'));
            if (Hal_Document_Acl::canReplace($document, $this->getParam('pwd', ''))) {
                $this->_session->type = Hal_Settings::SUBMIT_REPLACE;
                $this->_session->submitStatus->setSubmitType(Hal_Settings::SUBMIT_REPLACE);
                $newDocument = clone($document);
                //On rajoute le déposant de la version précédente comme propriétaire
                $docOwner = array_unique(array_merge($document->getOwner(), array($document->getContributor('uid'))));
                $newDocument->setOwner($docOwner);
                $newDocument->setContributor(Hal_Auth::getUser());
                // remise a zero des fichiers pour la nouvelle version
                $newDocument->initFiles();
                // effacement des meta donnees qui ne doivent pas survivre a une nouvelle version
                // eg: identifiant software heritage
                // TODO...
                $newDocument->resetSomeMetaForTypedocWhenReplace();
                $newDocument->_oldVersion = $document;
                $this->_session->document = $newDocument;
                // Initialisation de la validité du dépot
                $this->_session->submitStatus->update($newDocument, $this->_session->type, Hal_Settings::getSubmissionsSteps());

                $this->redirect('/submit/index');
            } else {
                $this->redirect('/');
            }
        } else {
            $this->redirect('/');
        }
    }

    /**
     * Ajout d'un fichier
     * ==> Pouvoir modifier les métadonnées des fichiers
     */
    public function addfileAction()
    {
        if ($this->getParam('docid') == 0 ) {
            $this->redirect('/');
            return;
        }
        $document = Hal_Document::find($this->getParam('docid'));
        $status = $document -> getStatus();
        //if (Hal_Document_Acl::canUpdate($document, $this->getParam('pwd', ''))) {
        if ( $status !=  Hal_Document::STATUS_VISIBLE) {
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Ce document est en moderation, il ne peut etre modifie");
            $this->redirect('/');
            return;
        }

        // On différencie le type de dépôt pour un ajout de fichier principal ou un ajout d'annex
        if ($document->getFormat() == Hal_Document::FORMAT_NOTICE) {
            $this->_session->type = Hal_Settings::SUBMIT_ADDFILE;
            $this->_session->submitStatus->setSubmitType(Hal_Settings::SUBMIT_ADDFILE);
        } else {
            $this->_session->type = Hal_Settings::SUBMIT_ADDANNEX;
            $this->_session->submitStatus->setSubmitType(Hal_Settings::SUBMIT_ADDANNEX);
        }

        // On ne permet pas l'ajout d'auteur ni de compléter ses affiliations
        $this->_session->submitOptions->setCompleteauthors(false);
        $this->_session->submitOptions->setAffiliateauthors(false);

        //On rajoute l'utilisateur connecté comme propriétaire du dépôt
        $docOwner = array_unique(array_merge($document->getOwner(), array(Hal_Auth::getUid())));
        $document->setOwner($docOwner);
        $this->_submitManager->copyFilesInTmp($document->getFiles(), $this->_tmpDir);

        $this->_session->document = $document;

        $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_FILE)->setMode(Hal_Settings::SUBMIT_MODE_DETAILED);
        $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_FILE)->setFilesNotDeletable(array_keys($this->_session->document->getFiles()));
        // Initialisation de la validité du dépot
        $this->_session->submitStatus->update($document, $this->_session->type, [Hal_Settings::SUBMIT_STEP_FILE, Hal_Settings::SUBMIT_STEP_META, Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]);
        $this->redirect('/submit/index');
        //} else {
        //    $this->redirect('/');
        //}
    }

    /**
     * Modification par un modérateur
     * ==> on peut tout faire dessus
     */
    public function moderateAction()
    {
        if ($this->getParam('docid') != 0 && Hal_Auth::isModerateur()) {
            $this->_session->document->setDocid($this->getParam('docid'), true);
            $this->_session->type = Hal_Settings::SUBMIT_MODERATE;
            $this->_session->submitStatus->setSubmitType(Hal_Settings::SUBMIT_MODERATE);

            $this->_submitManager->copyFilesInTmp($this->_session->document->getFiles(), $this->_tmpDir);

            // Initialisation de la validité du dépot
            $this->_session->submitStatus->update($this->_session->document, $this->_session->type, [Hal_Settings::SUBMIT_STEP_FILE, Hal_Settings::SUBMIT_STEP_META, Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]);

            $this->redirect('/submit/index');
        } else {
            // Url forgee?
            $this->redirect('/index/index');
        }
    }


    protected function initializeIndexView()
    {
        //Initialisation des variables globales utilisées dans la vue
        $this->view->submitType = $this->_session->type;
        $this->view->typdoc = $this->_session->document->getTypdoc();
        $this->view->steps = $this->_session->submitStatus->getStepsValidity();
        $this->view->activeStep = $this->_session->submitStatus->getCurrentStepName();
        $this->view->document = $this->_session->document;

        // Typdoc filtrés par l'extension du document principal
        $defaultF = $this->_session->document->getDefaultFile();
        $extension = $defaultF ? Ccsd_File::getExtension($defaultF->getName()) : "";

        $this->view->availableTypdocs = addslashes(Zend_Json::encode($this->_session->submitStatus->getTypdocs($this->view->typdoc, $extension)));
        // La visu des droits du portail est prioritaire sur les préférences de dépôt de l'utilisateur
        $seeLegal = Hal_Settings::seeLegal();
        // Si le mode de dépôt n'est pas imposé par le portail, on prend celui de l'utilisateur
        if (!isset($seeLegal)) {
            $seeLegal = Hal_Auth::getUser()->getSeelegal();
        }
        $this->view->seeLegal = $seeLegal;

        // Dans le cas de la modification d'un document suite à une demande la modération, il faut pouvoir afficher le message
        if ($this->_session->document->getDocid() && $this->_session->document->getStatus() == Hal_Document::STATUS_MODIFICATION) {
            $this->view->moderationMsg = Hal_Document_Logger::getLastComment($this->_session->document->getDocid(), Hal_Document_Logger::ACTION_ASKMODIF);
        }
    }

    protected function getHtmlSteps($stepsName, $verifValidity = false)
    {
        $return = [];
        foreach ($this->_session->submitStatus->getSteps() as $name => $step) {
            if (in_array($name, $stepsName)) {
                $return[$name] = $step->getHtml($this->view, $this->_session->document, $this->_session->type, $verifValidity);
            }
        }

        return $return;
    }

    /**
     * Encode un array en json ou renvoie une erreur serveur en cas d'échec
     * @param $toencode
     * @return string
     */
    private function encodeJsonOrGetError($toencode)
    {
        $jsonreturn = Zend_Json::encode($toencode);

        if (!$jsonreturn) {
            $this->getResponse()->setHttpResponseCode(500);
            return self::ERROR_SUPPORT;
        }

        return $jsonreturn;
    }


    /**
     * Changement d'étape pour le dépot simplifié
     */
    public function ajaxswitchstepAction()
    {
        $this->noRender();

        if (!$this -> isAjaxPost() || !$this->getParam('newStep')) {

            //pas de requete AJAX ou pas de requete POST
            return false;
        }

        $params = $this->getParams();
        $newstep = $params["newStep"];
        unset($params["newStep"]);
        $currentStep = $this->_session->submitStatus->getCurrentStepName();

        // Si l'étape n'a pas réellement changée, on ne fait rien !
        if ($newstep == $currentStep) {
            return false;
        }

        //Passage à l'étape suivante
        $this->_session->submitStatus->setCurrentStep($newstep);

        $verifValidity = false;

        if (Hal_Settings::SUBMIT_STEP_META == $currentStep && !empty($params)) {
            $this->_session->submitStatus->getStep($currentStep)->submit($this->_session->document, null, $params);
            $verifValidity = true;
        }

        $return = $this->getajaxreturnsteps([$currentStep, Hal_Settings::SUBMIT_STEP_RECAP], $verifValidity);

        if (Hal_Settings::SUBMIT_STEP_FILE == $newstep) {
            // On renvoit la première erreur dans l'étape fichier quand il y en a une
            $errors = $this->_session->submitStatus->getStep($newstep)->getErrors();
            if (!empty($errors)) {
                $return["errorajax"]["file"] = $errors[0];
            }
        }

        echo $this->encodeJsonOrGetError($return);
    }

    protected function callMethod($method, $params = null)
    {
        if ($params == null) {
            return $this->$method();
        }
        return $this->$method($params);
    }

    /**
     * Compilation Latex
     */
    public function latexprocessAction()
    {
        $this->noRender();

        try {
            $this->_submitManager->setTypDocFromFileLimit();

            $resCompilation = Ccsd_File::compile($this->_tmpDir);

            $idx = [];

            if ($resCompilation['status'] == false) {
                //Erreur de compilation
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate('La compilation laTex a échouée.');
                echo $resCompilation['out'];
            } else {
                //Ajout des fichiers pdf créés. La méthode compile retourne un xml /files/pdf(+)
                $pdf = Ccsd_Tools::xpath((string)$resCompilation['out'], '/files/pdf');
                $idsx = [];
                if ($pdf) {
                    if (is_array($pdf)) {
                        foreach ($pdf as $v) {
                            $fileObject = $this->_submitManager->fileDataToFileObject($v, $this->_tmpDir . $v);
                            $fileObject->setSource(Hal_Document_File::SOURCE_COMPILED);
                            $idsx = array_merge($idsx, $this->_submitManager->addFileToDocument($fileObject, $this->_tmpDir));
                        }
                    } else {
                        $fileObject = $this->_submitManager->fileDataToFileObject($pdf, $this->_tmpDir . $pdf);
                        $fileObject->setSource(Hal_Document_File::SOURCE_COMPILED);
                        $idsx = array_merge($idsx, $this->_submitManager->addFileToDocument($fileObject, $this->_tmpDir));
                    }
                }

                // Ajout du(des) fichier(s) de log et bbl créé(s) par la compilation
                if (is_dir($this->_tmpDir)) {
                    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->_tmpDir)) as $file) {
                        if ($file->isFile() && preg_match('/\.(log|bbl)$/', $file->getFilename()) && !$this->_session->document->existFileName($file->getFilename())) {
                            $exist = $this->_session->document->getFileIdByName($file->getFilename());
                            if ($exist !== false) {
                                $this->_session->document->delFile($exist);
                            }
                            $f = [
                                'name' => $file->getFilename(),
                                'path' => $file->getPathname(),
                                'size' => filesize($file->getPathname()),
                                'typeMIME' => Ccsd_File::getMimeType($file->getPathname()),
                                'type' => Hal_Settings::FILE_TYPE_SOURCES
                            ];
                            $idsx[] = $this->_session->document->addFile($f);
                        }
                    }

                }

                $toreturn = $this->_submitManager->getDetailledFiles($this->view, $this->_session->type, $idsx);

                /* Ajout d'un message Succès / Echec de la récupération des métadonnées */
                $defaultFile = $this->_session->document->getDefaultFile();
                $defaultFilename =  ($defaultFile ? $defaultFile->getName() : "no default file");
                $returnCode = $this->_submitManager->prepareFileReturnedMsg($this->view, true, false, true, $defaultFilename, '');
                //$toreturn["noReturnMsg"] = true;

                // Aucun message retourné s'il ne s'agit pas du fichier principal
                if ($returnCode) {
                    $toreturn["sucessMsg"] = $this->view->render('submit/step-file/sucessmsg.phtml');
                }

                $toreturn = $toreturn + $this->getajaxreturnsteps(Hal_Settings::getSubmissionsSteps());

                echo $this->encodeJsonOrGetError($toreturn);
            }
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            echo $this->view->translate($e->getMessage());
        }
    }

    /**
     * Dépôt d'un fichier
     * Si le fichier est convertible (doc, ppt, ...) il sera automatiquement envoyé au serveur de conversion
     *
     * @return
     * En cas d'erreur 500 : string (message d'erreur)
     *
     * En cas de réussite :
     * $return["name"] : string (le nom du fichier déposé)
     * $return["main"] : bool (le fichier déposé est-il le fichier principal ?)
     * $return["convertedFile"] : string (le nom du fichier converti à partir du fichier déposé s'il existe)
     * $return["main"] : bool (le fichier converti est-il le fichier principal ?)
     *
     * $return["filerow"] : html (la vue du fichier en cas de dépot détaillée)
     * $return Hal_Submit_Status
     *
     */
    public function uploadAction()
    {
        $this->noRender();

        //To do, découper cette action en plusieurs appels au server pour ne pas avoir à mettre cette limite infinie.
        set_time_limit(0);

        $upload = new Ccsd_Upload(array('upload_dir' => $this->_tmpDir, 'param_name' => key($_FILES)), false);
        $parameters = $upload->post(false);

        $key = key($parameters);

        if ($parameters[$key] == 'file' || $parameters[$key] == 'files') {
            $parameters[$key][0] = $parameters[$key];
        }

        // TODO : METTRE CE TEST DANS LA FONCTION PROCESS NEW FILE !!
        // Test la taille des fichiers
        foreach ($parameters[$key] as $file) {
            if (isset($file->error) && $file->error != "") {
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate($file->error);
                return;
            }
        }

        $toreturn = array();

        try {
            // EN REALITE IL N'Y A JAMAIS PLUSIEURS FICHIERS EN MEME TEMPS ???
            //foreach ($parameters[$key] as $file) {
            if (isset($parameters[$key][0])) {
                $toreturn = $this->_submitManager->processNewFile(Hal_Submit_Manager::SRC_FILE, $parameters[$key][0], $this->_session->type, $this->_tmpDir, $this->view);

                // Rechargement des vues seulement dans le cas de recherche de métadonnées
                if (array_key_exists("existMain", $toreturn) && $toreturn["existMain"]) {
                    $toreturn = $toreturn + $this->getajaxreturnsteps(Hal_Settings::getSubmissionsSteps());
                    if (!$toreturn['validity']['file']) {
                        $toreturn['err'] = $this->_session->submitStatus->getStep('file')->getErrors()[0];
                    }
                }

                echo $this->encodeJsonOrGetError($toreturn);
            } else {
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate("Le fichier a été mal transmis.");
            }
        } catch (Exception $e) {
            $this->getResponse()->setHttpResponseCode(500);
            echo $this->view->translate($e->getMessage());
        }
    }

    /**
     * Ajout d'un fichier récupéré dans l'espace FTP de l'utilisateur
     */
    public function selectftpfileAction()
    {
        $this->noRender();
        if ($this->isAjaxPost() && $this->getParam('file')) {
            try {

                $toreturn = $this->_submitManager->processNewFile(Hal_Submit_Manager::SRC_FTP, $this->getParam('file'), $this->_session->type, $this->_tmpDir, $this->view);

                // Rechargement des vues seulement dans le cas de recherche de métadonnées
                if ($toreturn["existMain"]) {
                    $toreturn = $toreturn + $this->getajaxreturnsteps(Hal_Settings::getSubmissionsSteps());
                }

                echo $this->encodeJsonOrGetError($toreturn);
            } catch (Exception $e) {
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate($e->getMessage());
            }
        } else {
            $this->getResponse()->setHttpResponseCode(500);
            echo $this->view->translate("Aucun fichier transmis");
        }
    }

    /**
     * Ajout d'un fichier récupéré à partir de son url
     *
     * $return["filerow"] : html (la vue du fichier en cas de dépot détaillée)
     * $return Hal_Submit_Status
     */
    public function ajaxdownloadfileAction()
    {
        $this->noRender();
        if ($this->isAjaxPost() && $this->getParam('url')) {
            try {

                $toreturn = $this->_submitManager->processNewFile(Hal_Submit_Manager::SRC_URL, $this->getParam('url'), $this->_session->type, $this->_tmpDir, $this->view);

                // Rechargement des vues seulement dans le cas de recherche de métadonnées
                if ($toreturn["existMain"]) {
                    $toreturn = $toreturn + $this->getajaxreturnsteps(Hal_Settings::getSubmissionsSteps());
                }

                echo $this->encodeJsonOrGetError($toreturn);
            } catch (Exception $e) {
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate($e->getMessage());
            }
        } else {
            $this->getResponse()->setHttpResponseCode(500);
            echo $this->view->translate("Aucun fichier transmis");
        }
    }

    /**
     * Test la conversion arXiv
     */
    public function ajaxtestarxivAction()
    {
        $this->noRender();
        if ($this->isAjaxPost()) {
            $transfert = Hal_Transfert_Arxiv::transfert($this->_session->document);
            $error=[];
            if ($transfert -> canTransfert($this->_session->document, $error) && !empty($error)) {
                $content = '<ul>';
                $view = $this->view;
                if( in_array('resume', $error)){
                    $content .= "<li>". $view->translate("Le document n'a pas de résumé en anglais")."</li>";
                }
                if (in_array('filenosource', $error)){
                    $content .= "<li>". $view->translate("Le document contient un pdf de type laTeX sans documents source.")."</li>";
                }
                if (in_array('domain', $error)){
                    $content .= "<li>". $view->translate("Le document n'a pas d'appartenance avec un sous-domaine arXiv")."</li>";
                }
                if (in_array('filesize', $error)){
                    $content .= "<li>". $view->translate("Chaque fichier doit être inférieur à 3Mb et le tout ne doit pas dépasser les 10 Mb")."</li>";
                }
                if (in_array('nobbl', $error)){
                    $content .= "<li>". $view->translate("Vous utilisez bibtex (un fichier bib est fourni), vous devez fournir le fichier bbl pour Arxiv")."</li>";
                }
                $content .= '</ul>';

                $this->getResponse()->setHttpResponseCode(500);
                echo $view->translate("Le document ne peut être transféré sur arXiv pour les raisons suivantes :"). $content;
            } else {
                //echo Zend_Json::encode(array('success' => true, 'msg' => $this->view->translate("Le document peut-être transféré sur arXiv"), 'status' => 'success'));
            }
        }
    }

    /**
     * Décompression d'une archive zip
     */
    public function ajaxunzipAction()
    {
        $this->noRender();

        $fileId = $this->getRequest()->getParam('file', null);
        if ($this->isAjaxPost() && $fileId !== null) {

            $idsx = $this->_submitManager->addZippedFiles($this->_session->document->getFile($fileId)->getPath(), $this->_tmpDir);

            if (empty($idsx)) {
                // Echec de l'ajout du fichier - Probablement car déjà existant
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate("Echec du dézip");
            } else {
                // Suppression de l'archive zip
                $this->_session->document->delFile((int) $fileId);

                $toreturn["fullfileblock"] = $this->_submitManager->getDetailledFilesFullBlock($this->view, $this->_session->type);

                echo $this->encodeJsonOrGetError($toreturn + $this->getajaxreturnsteps(Hal_Settings::getSubmissionsSteps()));
            }
        }
    }

    /**
     * Ajax définition d'un fichier comme principal

     * $return Hal_Submit_Status
     */
    public function ajaxselectmainfileAction()
    {
        $this->noRender();

        $fileName = $this->getRequest()->getParam('file', '');

        $toreturn = [];

        if (!empty($fileName)) {

            $fileid = $this->_session->document->getFileIdByName($fileName);
            $file = $this->_session->document->getFile($fileid);

            if (false === $file) {
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate("Fichier transmis non reconnu");
            } else if (!in_array($file->getExtension(), $this->_submitManager->mainFileExtensions($this->_session->document->getTypDoc()))) {
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate("Extension non acceptée pour ce type de dépôt. Choisissez le type de document souhaité.");
            } else {

                $currentDefaultFile = $this->_session->document->getDefaultFile();

                $this->_session->document->majMainFile($fileName);

                if ($currentDefaultFile) {
                    $currentDefaultFile->setType(Hal_Settings::FILE_TYPE_ANNEX);
                }

                // Si le fichier est devenu le fichier principal, on récupère ses métadonnées associées
                if (isset($file) && $file->getDefault()) {

                    // Changement du type du fichier
                    $file->setType(Hal_Settings::FILE_TYPE);

                    // Changement de l'origine du document
                    if ($file->getOrigin() == '') {
                        $file->setOrigin(Hal_Settings::FILE_SOURCE_AUTHOR);
                    }

                    $metasArray = null;

                    try {
                        $metasArray = $this->_submitManager->createMetadatas($file->getPath(), "pdf");
                    } catch (Exception $e) {
                        //todo remonter l'info sur le pb de récupération de méta de grobid ?
                    }
                    if (!empty($metasArray)) {
                        $this->_submitManager->loadExternalMeta(["grobid" => $metasArray]);
                    }

                    // On met à jour le type de document s'il est vide
                    if ('' == $this->_session->document->getTypDoc()) {
                        $this->_submitManager->changeCurrentTypdoc($this->_submitManager->getTypdocFromMetadata($fileName, $this->_session->document->getHalMeta()));
                    }

                    /* Ajout d'un message Succès / Echec de la récupération des métadonnées */
                    $returnCode = $this->_submitManager->prepareFileReturnedMsg($this->view, true, false, false, $file->getName(), '');

                    //$toreturn["noReturnMsg"] = true;

                    // Aucun message retourné s'il ne s'agit pas du fichier principal
                    if ($returnCode) {
                        $toreturn["sucessMsg"] = $this->view->render('submit/step-file/sucessmsg.phtml');
                    }

                    $toreturn = $toreturn + $this->_submitManager->getDetailledFiles($this->view, $this->_session->type, array_keys($this->_session->document->getFiles()));
                    $toreturn = $toreturn + $this->getajaxreturnsteps(Hal_Settings::getSubmissionsSteps());

                    echo $this->encodeJsonOrGetError($toreturn);

                } else {
                    echo $this->encodeJsonOrGetError($this->_submitManager->getDetailledFiles($this->view, $this->_session->type, array_keys($this->_session->document->getFiles())));
                }
            }
        } else {
            $this->getResponse()->setHttpResponseCode(500);
            echo $this->view->translate("Aucun fichier transmis");
        }
    }

    /**
     * Ajax récupération des documents en session
     */
    public function ajaxgetsessionfilesAction()
    {
        $this->noRender();

        $files = array();

        foreach ($this->_session->document->getFiles() as $file) {
            $fileArray = $file->toArray();
            $fileArray['notDeletable'] = $this->_session->type == Hal_Settings::SUBMIT_ADDFILE || $this->_session->type == Hal_Settings::SUBMIT_ADDANNEX || $this->_session->type == Hal_Settings::SUBMIT_UPDATE ? true : false;
            $fileArray['thumb'] = $file->getTmpThumb();
            $files[] = $fileArray;
        }

        echo $this->encodeJsonOrGetError($files);
    }

    /**
     * Ajax : ajout d'un identifiant extérieur pour l'ajout de métadonnées
     *
     * $return Hal_Submit_Status
     */
    public function ajaxaddidextAction()
    {
        ini_set('max_execution_time', 0); //Supprime le timeout pour les dépôts ayant énormément d'auteurs
        $this->noRender();
        $params = $this->getRequest()->getParams();

        if (!empty($params['idext']) && !empty($params['idtype'])) {

            $idType = $params['idtype'];
            $idExt = $params['idext'];

            // Validation de la forme de l'identifiant
            $validator = "Ccsd_Form_Validate_Is" . strtolower($idType);

            if (class_exists($validator)) {
                $validator = new $validator ();

                // Identifiant non valide
                if (!$validator->isValid($idExt)) {
                    $this->getResponse()->setHttpResponseCode(500);

                    $msgs = $validator->getMessages();

                    if (is_array($msgs)) {
                        foreach ($msgs as $k => $v) {
                            echo $this->view->translate ($v);
                        }
                    } else {
                        echo $msgs;
                    }
                } else {
                    try {
                        $this->_submitManager->addIdExtToDocument($idExt, $idType);
                        $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_FILE)->setIdExt($idType, $idExt);

                        /* Ajout d'un message Succès / Échec de la récupération des métadonnées */
                        $returnCode = $this->_submitManager->prepareIdReturnedMsg($this->view, $idType, $idExt);
                        //$toreturn["noReturnMsg"] = true;

                        echo $this->encodeJsonOrGetError(["url"=>Hal_Submit_Manager::getIdUrl($idType, $idExt)] + $this->getajaxreturnsteps(Hal_Settings::getSubmissionsSteps()) + ["sucessMsg" => $this->view->render('submit/step-file/sucessmsg.phtml')]);

                    } catch (Exception $e) {
                        $this->getResponse()->setHttpResponseCode(500);
                        echo $this->view->translate($e->getMessage());
                    }
                }
            } else {
                $this->getResponse()->setHttpResponseCode(500);
                echo $this->view->translate("Type d'identifiant inconnu");
            }
        } else {
            $this->getResponse()->setHttpResponseCode(500);
            echo $this->view->translate("Aucun identifiant transmis");
        }

    }

    public function ajaxchangefilemetaAction()
    {
        $this->noRender();

        $group = $this->getParam('group', '');
        $key = $this->getParam('key', '');
        $value = $this->getParam('value', '');

        $files = $this->_session->document->getFiles();

        if (isset($files[$group])) {
            $file = $files[$group];
            if ("date" == $key || "visible" == $key) {
                if ($value != 'date') {
                    $file->setDateVisible($value);
                    if (!$file->isEmbargoValid()){
                        $file->setDateVisible($file->maxEmbargo());
                        $this->getResponse()->setHttpResponseCode(500);
                        echo Zend_Json::encode(["errorMsg" => $this->view->translate('Vous ne pouvez pas avoir un embargo supérieur à : '). $file->maxEmbargo(), "maxDate" => $file->maxEmbargo()]);
                        return;
                    }
                }

            } else if (in_array($key, ['format', 'comment', 'type', 'origin', 'defaultannex', 'licence'])) {
                $file->{'set' . ucfirst($key)}($value);
            }

            if ($file->getType() == Hal_Settings::FILE_TYPE_ANNEX) {

                $existDefaultAnnex = false;
                $idDefaultFigure = false;

                if ($file->getFormat() == '' && in_array($file->getExtension(), array('jpg', 'png'))) {
                    $file->setFormat('figure');
                }

                if ($idDefaultFigure === false && $file->getFormat() == 'figure') {
                    $idDefaultFigure = $group;
                }

                if (! $existDefaultAnnex && $file->getDefaultannex()) {
                    $existDefaultAnnex = true;
                }

                if (! $existDefaultAnnex && $this->_session->document->existFile()) {
                    if ($this->_session->document->getFile($idDefaultFigure) !== false) {
                        $this->_session->document->getFile($idDefaultFigure)->setDefaultannex(true);
                    }
                }
            }

            $idsx = [];
            
            // Lorsqu'on modifie le format, ça peut avoir une influence sur le fichier principal
            if ($key == 'type') {
                if ($file->getType() == Hal_Settings::FILE_TYPE_ANNEX || $file->getType() == Hal_Settings::FILE_TYPE_SOURCES) {
                    $file->setDefault(false);

                    $mainables = $this->_session->document->getMainableFiles();

                    // On prend un autre fichier principal
                    if (!empty($mainables)) {
                        $idsx[] = $mainables[0];
                        $this->_session->document->getFile($mainables[0])->setDefault(true);
                    }

                } else if (!$this->_session->document->getDefaultFile()) {
                    $file->setDefault(true);
                    $idsx[] = $this->_session->document->getFileIdByName($file->getName());
                }
            }

            echo $this->encodeJsonOrGetError($this->_submitManager->getDetailledFiles($this->view, $this->_session->type, $idsx));
        }
    }

    /**
     * Suppression d'un ou plusieurs fichiers
     */
    public function ajaxdeletefileAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->getRequest()->getParams();

        if ($this->isAjaxPost() && isset($params['method']) && $params['method'] == 'del' && isset($params['file'])) {
            $ret = false;
            /** @var $document Hal_Document */
            if ($params['file'] == 'all') { //Suppression de tous les fichiers
                foreach(array_keys($this->_session->document->getFiles()) as $id) {
                    $this->_session->document->delFile($id);
                }
                $this->_session->document->setFiles(array());

                $toreturn["filenb"] = 'all';
                $ret = true;
            } else {
                $fileId = $params['file'];
                // Dans le cas où on passe le nom du fichier plutôt que son identifiant
                if (!preg_match("/^-?[1-9][0-9]*$/D", $fileId)) {
                    $fileId = $this->_session->document->getFileIdByName($fileId);
                }

                $file = $this->_session->document->getFileByFileIdx($fileId);

                if ($file && $this->_session->document->delFile((int) $fileId)) {
                    $toreturn["filenb"] = '1';
                    $ret = true;

                    // Permet de savoir si le fichier que l'on supprime était le fichier principal
                    $toreturn["wasDefault"] = $file->getDefault();
                } else {
                    error_log("Ajaxdeletefile error: delfile return false for $fileId");
                    $this->getResponse()->setHttpResponseCode(500);
                    echo "error";
                }
            }

            if ($ret) {
                echo $this->encodeJsonOrGetError($toreturn + $this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_FILE, Hal_Settings::SUBMIT_STEP_RECAP]));
            }
        }
    }

    /**
     * Modification des options de complétion automatique
     */
    public function ajaxchangeoptionAction()
    {
        $this->noRender();
        $option = $this->getParam('option', '');
        $value = $this->getParam('value', '');

        if ($option === '' || $value === '') {
            $this->getResponse()->setHttpResponseCode(500);
            echo "Pas de valeur envoyée";
        } else {
            $this->_session->submitOptions->setOption($option, $value);
        }
    }

    /**
     * Changement de mode de dépôt (simple/complet)
     */
    public function ajaxswitchmodeAction()
    {
        $this->noRender();

        $step = $this->getParam('step', '');
        if (!$this -> isAjaxPost() || !in_array($step, $this->_session->submitStatus->getStepsList())) {
            //pas de requete AJAX ou pas de requete POST
            return false;
        }

        $this->_session->submitStatus->getStep($step)->setMode($this->getParam('status', Hal_Settings::SUBMIT_MODE_SIMPLE));

        if ($this->_session->submitStatus->getCurrentStepName() == Hal_Settings::SUBMIT_STEP_FILE) {
            echo $this->encodeJsonOrGetError(["fileView" => $this->_session->submitStatus->getStep($step)->getHtml($this->view, $this->_session->document, $this->_session->type)]);
        }
    }

    /**
     * Mise à jour des métadonnées
     */
    public function ajaxchangemetaAction()
    {
        $this->noRender();
        $params = $this->getParams();
        $type = $this->getParam("istypechange", 0);
        $domain = $this->getParam("isdomainchange", 0);
        unset($params["istypechange"]);
        unset($params["isdomainchange"]);

        $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_META)->submit($this->_session->document, null, $params);

        $return = [];

        if ($type) {

            $max = Hal_Settings::getFileLimit($params["type"]);

            if ($max && $this->_session->document->getFileNb() > $max) {
                $this->getResponse()->setHttpResponseCode(500);
                echo Zend_Json::encode(["errorMsg" => $this->view->translate('Vous ne pouvez pas avoir plusieurs fichiers pour ce type de dépôt. Choisissez un autre type de dépot ou supprimez les fichiers en surplus'), "type"=>$this->_session->document->getTypdoc()]);
                return;
            }

            $this->_submitManager->changeCurrentTypdoc($params["type"]);
            $return = $this->_submitManager->getDetailledFiles($this->view, $this->_session->type, array_keys($this->_session->document->getFiles()));

            if (($params["type"] == "THESE" || $params["type"] == "HDR" || $params["type"] == "ETABTHESE") && count($this->_session->document->getAuthors()) > 1) {
                $return["errorajax"]["meta"] = $this->view->translate('Attention, vous avez plusieurs auteurs ce qui est rare pour ce type de document. Veuillez vérifier les informations sur les auteurs dans la section \'Compléter les données auteur\'.');
            }

        }

        // Verifvalidity=true : on veut afficher les erreurs dans le cas où l'on a quitté l'étape méta et qu'elle n'est pas valide (mais pas dans le cas où on change le type de document)
        echo $this->encodeJsonOrGetError($return + $this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_FILE, Hal_Settings::SUBMIT_STEP_META, Hal_Settings::SUBMIT_STEP_RECAP], !($type||$domain)));

    }

    /**
     * Changement du type de dépot
     * @deprecated
     */
    public function ajaxchangetypeAction()
    {
        $this->noRender();
        $type = $this->getParam('type');
        $this->_submitManager->changeCurrentTypdoc($type);

        echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_FILE, Hal_Settings::SUBMIT_STEP_META, Hal_Settings::SUBMIT_STEP_RECAP]));
    }

    public function ajaxchangeuserlegalAction()
    {
        $this->noRender();
        Hal_Auth::getUser()->setandSaveSeelegal(false);
    }

    /****************************************************************/

    public function getajaxreturnsteps($steps, $verifValidity = false)
    {
        $this->_session->submitStatus->update($this->_session->document, $this->_session->type, $steps);
        return $this->getHtmlSteps($steps, $verifValidity) + $this->_session->submitStatus->toArray();
    }

    /**
     * Retourne vide si l'identifiant est valide
     * Sinon, un message Html est retourne
     */
    public function ajaxvalidateidAction()
    {
        $this->noRender();
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {

            $idType = $this->getParam('idtype', '');
            $id = $this->getParam('id', '');

            if (!empty($idType) && !empty($id)) {

                // On vire les potentiels retour à la ligne qu'on récupère ! 
                $validator = "Ccsd_Form_Validate_Is".strtolower(trim($idType, chr(0xC2).chr(0xA0)));
                try {
                    if (class_exists($validator)) {
                        $validator = new $validator ();

                        if (!$validator->isValid($id)) {
                            $this->getResponse()->setHttpResponseCode(500);
                            $this->view->msg = $validator->getMessages();
                            echo $this->view->render(self::SUBMIT_CONTROLER . '/step-meta/ajaxgetidentifier.phtml');
                        }
                    } else {
                        throw new Exception("Class not found");
                    }
                } catch (Exception $e) {
                    $this->getResponse()->setHttpResponseCode(500);
                    $this->view->msg = $this->view->translate("Type d'identifiant non reconnu");
                    echo $this->view->render(self::SUBMIT_CONTROLER . '/step-meta/ajaxgetidentifier.phtml');
                }
            } else {
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->msg = $this->view->translate("Aucun identifiant transmis");
                echo $this->view->render(self::SUBMIT_CONTROLER . '/step-meta/ajaxgetidentifier.phtml');
            }
        }
    }

    /**
     * Autocompletion pour les métadonnnées ou la recherche des doublons
     */
    public function ajaxautocompletemetaAction()
    {
        $params = $this->getParams();
        $field = Ccsd_Tools::ifsetor($params['field']);

        if (isset($params['doublon'])) {
            //recherche de doublon
            $q = 'q=' . $field . ':' . urlencode($params['term']) . '&fl=uri_s,citationFull_s';
            try {
                $solrResult = unserialize(Ccsd_Tools::solrCurl($q));
            } catch (Exception $exc) {
                error_log($exc->getMessage(), 0);
            }
            if (isset($solrResult['response']['docs']) && count($solrResult['response']['docs'])) {
                $res = array(array('id' => '', 'label' => '<i class="glyphicon glyphicon-warning-sign"></i>&nbsp;' .Zend_Registry::get(ZT)->translate("Document(s) déjà présent(s) dans l'archive HAL")));
                foreach($solrResult['response']['docs'] as $doc) {
                    $res[] = array(
                        'id' => $doc['uri_s'],
                        'label' =>  $doc['citationFull_s']
                    );
                }
                echo Zend_Json::encode($res);
            }
        } else if (isset($params['thesaurus'])) {
            //
            $class = 'Thesaurus_' . ucfirst($field);
            if (class_exists($class)) {
                $thesaurus = new $class();
                echo json_encode($thesaurus->autocomplete(urlencode($params['term'])));
            }
        } else {
            $term = urlencode($params['term']);
            $termCapitalized = ucfirst($term);

            $q = 'q=*:*&rows=0&wt=phps&facet=true';

            if ($termCapitalized != $term) {
                $q .= '&facet.prefix=' . $termCapitalized;
            }

            $q .= '&facet.prefix=' . $term;
            $q .= '&facet.field=' . $field . '&facet.mincount=1&facet.limit=50&facet.sort=index';

            try {
                $solrResult = unserialize(Ccsd_Tools::solrCurl($q));
            } catch (Exception $exc) {
                error_log($exc->getMessage(), 0);
            }


            if (isset($solrResult['facet_counts']['facet_fields'][$field])) {
                echo Zend_Json::encode(array_keys($solrResult['facet_counts']['facet_fields'][$field]));
            }
        }
        $this->noRender();
    }

    /**
     * Autocompletion pour l'ajout d'un auteur
     */
    public function ajaxsearchauthorAction()
    {
        $this->noRender();
        if (isset($_GET['term'])) {
            try{
                echo Hal_Document_Author::search($_GET['term']);
            } catch(Exception $e) {echo '';}
        }
    }

    /**
     * Selection d'un auteur
     */
    public function ajaxaddauthorAction()
    {
        $this->noRender();
        $authid = $this->getParam('authorid', '');
        if ($this->isAjaxPost() && !empty($authid)) {
            try {

                $docAuthor = new Hal_Document_Author($authid);

                // todo : optimiser le copier/coller
                $data = $this->_submitManager->prepareAffiliationParams($this->_session->document->getMeta(), ['authorid' => $authid], []);
                $res = Hal_Search_Solr_Search_Affiliation::rechAffiliations($data);

                $foundStruct = $this->_submitManager->createStructFromApiAffiliationResult($res);

                // On affilie l'auteur
                if (null != $foundStruct) {
                    $structidx = $this->_session->document->addStructure($foundStruct['struct']);
                    $docAuthor->addStructidx($structidx);
                }

                $this->_session->document->addAuthor($docAuthor);

                // On clean les structures dans le cas où l'auteur n'a pas été ajouté mais mergé
                $this->_session->document->cleanStructures();
            } catch(Exception $e) {}

            echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
        }
    }

    /**
     * Ajout d'une structure
     */
    public function ajaxaddstructureAction()
    {
        $this->noRender();
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset($params['authid'])) {
            if (isset($params['structureid'])) {
                //On vérifie que la structure ne soit pas déja présente pour le dépôt
                $add = true;
                $idx = false;
                foreach ($this->_session->document->getStructures() as $i => $structure) {
                    if ($structure->getStructid() != 0 && $structure->getStructid() == $params['structureid']) {
                        $add = false;
                        $idx = $i;
                    }
                }
                if ($add) {
                    $docStructure = new Hal_Document_Structure($params['structureid']);
                    $idx = $this->_session->document->addStructure($docStructure);
                }

                if ( $idx !== false ) {
                    $this->_session->document->getAuthor($params['authid'])->addStructidx($idx);
                }
            } else if (isset($params['structureidx'])) {
                //on travaille sur les indices des structures et non sur les identifiants. Ce qui permet de déplacer une structure non présente dans auréHAL
                if ($this->_session->document->getStructure($params['structureidx']) instanceof Hal_Document_Structure) {
                    $this->_session->document->getAuthor($params['authid'])->addStructidx($params['structureidx']);
                }
            }
        }
        echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
    }

    /**
     * Récupération du formulaire d'un auteur
     */
    public function ajaxgetauthorformAction()
    {
        $this->noRender();
        $params = $this->getParams();
        if ($this->isAjaxPost() && isset($params['id'])) {
            if ($params['id'] == 'new') { //Nouvel auteur
                $this->view->author = new Hal_Document_Author();
            } else { //Auteur associé au dépôt
                $this->view->author = $this->_session->document->getAuthor($params['id']);
            }
            $this->view->id = $params['id'];
            $this->view->typdoc = $this->_session->document->getTypDoc();

            $this->setRender('author-form', Hal_Settings::SUBMIT_STEP_AUTHOR);
        }
    }

    /**
     * Récupération du formulaire d'un auteur
     */
    public function ajaxgetauthorfunctionformAction()
    {
        $this->noRender();
        $id = $this->getParam('id', '');

        // Comparaison stricte pour éviter un bug avec id=0
        if ($this->isAjaxPost() && $id !== '') {
            $this->view->author = $this->_session->document->getAuthor($id);
            $this->view->id = $id;
            $this->view->typdoc = $this->_session->document->getTypDoc();

            $this->setRender('author-function-form', Hal_Settings::SUBMIT_STEP_AUTHOR);
        }
    }

    /**
     * Validation du formulaire d'un auteur
     */
    public function ajaxsubmitauthorformAction()
    {
        $this->noRender();
        $params = $this->getParams();
        if ($this->isAjaxPost()) {

            $list = $this->getParam('list', '');
            $separator = $this->getParam('separator', '');
            $form = $this->getParam('form','');
            $struct = $this->getParam('struct', false);

            if (!empty($list) && !empty($separator)) {
                //Inverser prénom/nom
                $revert = ($form == 'fl');

                //Récupération des affiliations
                $searchAffi = $struct;

                //Ajout d'une liste d'auteurs
                foreach(explode($separator, $list) as $authorText) {
                    $text = preg_replace('/\s{2,}/', ' ', trim($authorText));
                    $pos = strrpos($text, ' ');
                    if ($pos !== false) {
                        $data = array(substr($text, 0,$pos), substr($text, $pos+1));
                        if ($revert) {
                            list($firstname, $lastname) = $data;
                        } else {
                            list($lastname, $firstname) = $data;
                        }

                        $author = new Hal_Document_Author();
                        if ($searchAffi) {
                            // todo : optimiser le copier/coller
                            $data = $this->_submitManager->prepareAffiliationParams($this->_session->document->getMeta(), ['lastname'=>$lastname, 'firstname'=>$firstname], []);
                            $res = Hal_Search_Solr_Search_Affiliation::rechAffiliations($data);
                            $authorLoaded = $this->_submitManager->loadAuthorFromApiAffiliationResult($author, $res);
                            $foundStruct = $this->_submitManager->createStructFromApiAffiliationResult($res);

                            if (!$authorLoaded) {
                                $author->setLastname($lastname);
                                $author->setFirstname($firstname);
                            }

                            // On affilie l'auteur
                            if (null != $foundStruct) {
                                $structidx = $this->_session->document->addStructure($foundStruct['struct']);
                                $author->addStructidx($structidx);
                            }

                        } else {
                            $author->setLastname($lastname);
                            $author->setFirstname($firstname);
                        }
                        $this->_session->document->addAuthor($author);
                    }
                }
                //On supprime les laboratoires non associés à des auteurs
                $this->_session->document->cleanStructures();

                echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));

            } else if (isset($params['id'])) {

                if ($params['id'] == 'new') {
                    //Nouvel Auteur
                    $author = new Hal_Document_Author();
                } else {
                    $author = $this->_session->document->getAuthor($params['id']);
                }

                $form = $author->getForm($this->_session->document->getTypDoc());
                if (isset($params['quality']) && $params['quality'] == 'crp') {
                    //email obligatoire si auteur correspondant
                    $form->getElement('email')->setRequired(true);
                }

                if ($form->isValid($params)) {
                    //formulaire valide

                    $author->set($params);

                    if ($params['id'] == 'new') { //Nouvel Auteur
                        $params['id'] = $this->_session->document->addAuthor($author, false);
                    }

                    echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
                } else {
                    //erreur sur formulaire
                    $this->view->form = $form;
                    $this->view->id = $params['id'];
                    $this->view->author = $author;

                    $return['autstruct'] = $this->view->render('submit/step-author/author-form.phtml');
                    echo $this->encodeJsonOrGetError($return);
                }
            }
        }
    }

    /**
     * Validation du formulaire d'un auteur
     */
    public function ajaxsubmitauthorfunctionformAction()
    {
        $this->noRender();
        $id = $this->getParam('id', '');
        $quality = $this->getParam('quality', '');
        $email = $this->getParam('email', '');

        // Comparaison stricte pour éviter un bug avec id=0
        if ($this->isAjaxPost() && $id !== '' && !empty($quality)) {
            $typdoc = $this->_session->document->getTypDoc();

            $author = $this->_session->document->getAuthor($id);

            // Pour un auteur valide, on récupère son email existant
            if ($author->isValidForm()) {
                $email = $author->getEmail();
            }

            $form = $author->getFunctionForm($typdoc);

            if ($quality == 'crp') {
                //email obligatoire si auteur correspondant
                $form->getElement('email')->setRequired(true);
            }

            if (!$form->isValid(['email'=>$email, 'quality'=>$quality])) {
                //formulaire non valide, on le renvoit
                $this->view->author = $author;
                $this->view->id = $id;
                $this->view->typdoc = $typdoc;
                $this->view->form = $form;
                $this->getResponse()->setHttpResponseCode(500);
                $this->setRender('author-function-form', Hal_Settings::SUBMIT_STEP_AUTHOR);
            } else {
                //formulaire valide, on renvoit la nouvelle fonction

                $author->setQuality($quality);
                // On ne permet pas de modifier l'email si la forme auteur est valide
                $author->setEmail($email);

                echo Zend_Json::encode($this->view->translate('relator_'.$quality));
            }

        }
    }

    /**
     * Suppression d'un auteur
     */
    public function ajaxdeleteauthorAction()
    {
        $this->noRender();
        $params = $this->getParams();
        if ($this->isAjaxPost() && isset($params['id'])) {
            $this->_session->document->delAuthor($params['id']);
            //On supprime les laboratoires non associés à des auteurs
            $this->_session->document->cleanStructures();

            echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
        }
    }

    public function ajaxseestructdetailsAction()
    {
        $this->noRender();
        $structidx = $this->getParam('structid', '');

        if ($structidx === '') {
            return false;
        }

        $struct = $this->_session->document->getStructure($structidx);
        $struct = new Ccsd_Referentiels_Structure($struct->getStructid());

        echo $struct->toHtml(['showParents' => true]);

    }

    /**
     * Suppression de l'affiliation d'un auteur
     */
    public function ajaxremoveaffiliationAction()
    {
        $this->noRender();
        $params = $this->getParams();
        if ($this->isAjaxPost() && isset($params['authorid']) && isset($params['structid'])) {
            $authorId = $params['authorid'];
            $structId = $params['structid'];
            /** @var Hal_Document $document */
            $document = $this->_session->document;
            $author = $document->getAuthor($authorId);
            if ($author) {
                $author->delStructidx($structId);
                //On supprime les laboratoires non associés à des auteurs
                $document->cleanStructures();
            } else {
                Ccsd_Tools::panicMsg(__FILE__,__LINE__, "ajaxremoveaffiliationAction de aut=$authorId sur structIdx=$structId pour le document: " . $document->getTitle());
            }
            echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
        }
    }

    /**
     * Association de tous les auteurs à une structure
     */
    public function ajaxassociateallauthorsAction()
    {
        $this->noRender();
        $params = $this->getParams();
        if ($this->isAjaxPost() && isset($params['id'])) {
            foreach ($this->_session->document->getAuthors() as $author) {
                $author->addStructidx($params['id']);
            }

            echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
        }
    }

    /**
     * Modification de l'ordre des auteurs en session
     */
    public function ajaxsortauthorsAction()
    {
        $this->noRender();
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset($params['aut'])) {
            $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_AUTHOR)->setAuthorOrder($params['aut']);
            echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
        }
    }

    /**
     * Suppression d'une structure
     */
    public function ajaxdeletestructureAction()
    {
        $this->noRender();
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset($params['id'])) {
            $this->_session->document->delStructure((int)$params['id']);

            echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
        }
    }

    /**
     * Récupération du formulaire d'une structure
     */
    public function ajaxgetstructureformAction()
    {
        $this->noRender();
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset($params['id'])) {
            if ($params['id'] == 'new') { //Nouvelle structure
                $this->view->structure = new Hal_Document_Structure();
            } else { //Structure associée au dépôt
                $this->view->structure = $this->_session->document->getStructure($params['id']);
                $this->view->edit = true;
            }
            $this->view->id = $params['id'];
            if (isset($params['authorid'])) {
                $this->view->authorid = $params['authorid'];
            }
            $this->view->controller = self::SUBMIT_CONTROLER;

            $this->view->authormode = $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_AUTHOR)->getMode();

            $this->setRender('structure-form', Hal_Settings::SUBMIT_STEP_AUTHOR);
        }
    }

    /**
     * Validation du formulaire d'une structure
     */
    public function ajaxsubmitstructureformAction()
    {
        $this->noRender();
        $params = $this->getParams();
        if ($this->isAjaxPost()) {
            $docStructure = new Hal_Document_Structure();
            $docStructure->set($params);

            if (isset($params['idx']) && $params['idx'] != 'new') {
                //Modification d'une structure
                $this->_session->document->setStructure($params['idx'], $docStructure);
            } else if (isset($params['authorid'])) {
                //Nouvelle sructure
                $idx = $this->_session->document->addStructure($docStructure);
                if ( $idx !== false ) {
                    $this->_session->document->getAuthor($params['authorid'])->addStructidx($idx);
                }
            }

            echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
        }
    }

    /**
     * Formulaire d'ajout d'une liste d'auteurs
     */
    public function ajaxgetauthorslistformAction()
    {
        $this->noRender();
        if ($this->isAjaxPost()) {
            $this->setRender('author-add-list', Hal_Settings::SUBMIT_STEP_AUTHOR);
        }
    }

    /**
     * Formulaire d'ajout d'une liste d'auteurs
     */
    public function ajaxgetauthorsfromstructureformAction()
    {
        $this->noRender();
        if ($this->isAjaxPost()) {
            $this->setRender('author-add-from-structure', Hal_Settings::SUBMIT_STEP_AUTHOR);
        }
    }

    /**
     * Liste d'auteurs d'une structure
     */
    public function ajaxgetauthorsfromstructureAction()
    {
        $this->noRender();
        if ($this->isAjaxPost() ) {
            $params = $this->getParams();
            if (isset($params['structid'])) {
                //Récupération des auteurs d'une structure
                $this->view->authors = Hal_Document_Author::getFromStructure($params['structid']);
                $this->setRender('author-add-from-structure-list', Hal_Settings::SUBMIT_STEP_AUTHOR);
            }
        }
    }

    /**
     * Ajout s'une liste d'auteurs
     */
    public function ajaxsubmitauthorslistAction()
    {
        $this->noRender();
        $params = $this->getParams();
        if ($this->isAjaxPost() && isset($params['authorids'])) {
            foreach($params['authorids'] as $authorid) {
                $author = new Hal_Document_Author($authorid);
                $this->_session->document->addAuthorWithAffiliations($author, Ccsd_Tools::ifsetor($params['structid'], 0));
            }
        }

        echo $this->encodeJsonOrGetError($this->getajaxreturnsteps([Hal_Settings::SUBMIT_STEP_AUTHOR, Hal_Settings::SUBMIT_STEP_RECAP]));
    }

    /**
     * Formulaire d'ajout de mes auteurs
     */
    public function ajaxgetmyauthorsformAction()
    {
        $this->noRender();
        if ($this->isAjaxPost()) {
            $this->view->authors = Hal_Document_Author::getFromUid(Hal_Auth::getUid());
            $this->setRender('author-add-my-authors', Hal_Settings::SUBMIT_STEP_AUTHOR);
        }
    }

    /**
     * @return void
     */
    public function submitsteprecapAction()
    {

        if (!$this->getRequest()->isPost() || $this->_session->document->getTypDoc() == '') {
            $this->redirect('/submit/index');
            return;
        }

        $this->view->filesInTmpDir = in_array($this->_session->type, array(Hal_Settings::SUBMIT_INIT, Hal_Settings::SUBMIT_MODIFY, Hal_Settings::SUBMIT_MODERATE, Hal_Settings::SUBMIT_REPLACE, Hal_Settings::SUBMIT_ADDFILE, Hal_Settings::SUBMIT_ADDANNEX));

        $docid = $this->_session->document->getDocid();
        $type = $this->_session->type;
        $document = $this->_session->document;

        /** @var Hal_Submit_Step_Recap $step */
        $step = $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_RECAP);

        $step->submit($document, $type, $this->getAllParams());

        $docIdentifiant = $this->_session->document->getId();

        unset($this->_session->document, $this->_session->submitStatus, $this->_session->type);
        Ccsd_Tools::deletedir($this->_tmpDir);

        if ($type == Hal_Settings::SUBMIT_MODERATE) {
            $this->redirect('/moderate/documents/docid/' . $docid);
            return;
        } else if ($type == Hal_Settings::SUBMIT_MODIFY && Hal_Auth::isAdministrator()) {
            $contributor = new Hal_User ();
            $contributor->find ( $document->getContributor('uid') );
            $mail = new Hal_Mail ();
            // Tous les validateurs sont prévenus de la fin de l'expertise
            $mail->prepare($contributor, Hal_Mail::TPL_DOC_ADMINMODIFY, array(
                $document
            ));
            $mail->writeMail();
            $this->redirect('/administrate/pending-modification');
            return;
        } else if ($type == Hal_Settings::SUBMIT_UPDATE) {
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Vos modifications ont bien été prises en compte.');
            $this->redirect('/'.$docIdentifiant);
            return;
        } else {
            $this->redirect('/user/submissions');
            return;
        }
    }

    public function ajaxresetvalidityAction()
    {
        $this->noRender();

        // Mise à jour des métadonnées
        $params = $this->getParams();
        $this->_session->submitStatus->getStep(Hal_Settings::SUBMIT_STEP_META)->submit($this->_session->document, null, $params);

        $toreturn = $this->getajaxreturnsteps(Hal_Settings::getSubmissionsSteps(), true);

        if (false === $toreturn["validity"]["recap"]) {
            // impossible de sauvegarder le contenu
            $this->getResponse()->setHttpResponseCode(500);
        }

        echo $this->encodeJsonOrGetError($toreturn);
    }

    /**
     * Annulation du dépot
     */
    public function resetAction()
    {
        $type = $this->_session->type;
        $docIdentifiant = $this->_session->document->getId();
        $docid = $this->_session->document->getDocid();
        unset($this->_session->document, $this->_session->submitStatus, $this->_session->type);
        Ccsd_Tools::deletedir($this->_tmpDir);

        //todo supprimer c'est pour le test
        //return $this->redirect('/submit');
        //fin

        if ($type == Hal_Settings::SUBMIT_MODERATE) {
            // Redirection vers le document en modération
            return $this->redirect('/moderate/documents/docid/'.$docid);
        } else if ($type == Hal_Settings::SUBMIT_MODIFY && Hal_Auth::isAdministrator()) {
            return $this->redirect('/administrate/pending-modification');
        } else if ($type == Hal_Settings::SUBMIT_UPDATE) {
            return $this->redirect('/'.$docIdentifiant);
        } else   {
            return $this->redirect('/user/submissions');
        }
    }

    protected function setRender($view, $step)
    {
        $this->renderScript(self::SUBMIT_CONTROLER . '/step-' . $step . '/' . $view . '.phtml');
    }

    /**
     * Obsolete
     * Recherche  pour les relation entre documents
     * @deprecated
     */
    public function ajaxsearchdocAction()
    {
        error_log('submit/ajaxsearchdocAction ne devrait plus etre appele');

        $params = $this->getParams();

        $q = 'q=' . urlencode($params['term']) . '&fl=halId_s,citationFull_s,docid';

        if (isset($params['uid']) && $params['uid']!='') {
            $q .= '&fq=owners_i:' . urlencode($params['uid']);
        }
        try {
            $solrResult = unserialize(Ccsd_Tools::solrCurl($q));
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
        }
        if (isset($solrResult['response']['docs']) && count($solrResult['response']['docs'])) {
            $res = array();
            foreach($solrResult['response']['docs'] as $doc) {
                $res[] = array(
                    'id' => $doc['halId_s'],
                    'docid' => $doc['docid'],
                    'label' =>  $doc['citationFull_s']
                );
            }
            echo Zend_Json::encode($res);
        }
        $this->noRender();
    }
}

