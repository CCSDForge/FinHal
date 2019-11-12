<?php

class AdministrateController extends Hal_Controller_Action {

    /**
     *
     * @var Zend_Controller_Request_Abstract
     */
    protected $_request = null;

    /**
     *
     * @var array
     */
    protected $_params = null;

    public function init() {
        if (!(Hal_Auth::isAdministrator() || ($this->getRequest()->getActionName() == 'ajaxsearchuser'))) {
            $this->view->message = "Accès non autorisé";
            $this->view->description = "";
            $this->renderScript('error/error.phtml');
        }
        $this->_request = $this->getRequest();
        $this->_params = $this->_request->getParams();
    }

    /**
     * Documents en attente de validation
     */
    public function pendingValidationAction() {
        $params = $this->getRequest()->getParams();

        $val = new Hal_Validation();
        $mod = new Hal_Moderation();
        $this->view->forwardAction = $this->getRequest()->getActionName();

        if ((!isset($params ['docid'])) || ( $this->getRequest()->getParam('evaluate-action') == 'back')) {
            if (Hal_Auth::isHALAdministrator()) {
                $this->view->documents = $val->getDocuments(false);
            } else {

                $this->view->documents = $mod->getAdministratorDocuments(array(
                    Hal_Document::STATUS_VALIDATE
                ));
            }

            $this->view->layout()->pageDescription = '<span class="label label-orange">' . count($this->view->documents) . "</span> documents en attente d'expertise";

            $this->formAction = '/administrate/pending-validation';
            $this->render('documents');
            return;
        }

        if (isset($params ['docid'])) {
            if (!is_array($params ['docid'])) {
                $params ['docid'] = [
                    $params ['docid']
                ];
            }
            if (isset($params ['evaluate-action'])) {
                $comment = isset($params ['comment']) ? $params ['comment'] : '';

                // Action sur des documents
                if ($params ['evaluate-action'] != 'back') {
                    $document = new Hal_Document();
                    $tags = array(
                        'MSG_MODERATEUR' => $comment
                    );
                    $res = $error = array();
                    foreach ($params ['docid'] as $docid) {
                        $document->setDocid($docid, true);
                        $tags ['document'] = $document;

                        if ($params ['evaluate-action'] == 'delete') {
                            // Suppression du document
                            if ($document->delete(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            }
                        } else if ($params ['evaluate-action'] == 'validate') {
                            // demande d'expertise : relance
                            if ($document->validate(Hal_Auth::getUid(), $comment)) {
                                $res [] = $document->getId() . ', v' . $document->getVersion();
                            }
                        } else if ($params ['evaluate-action'] == 'moderation') {

                            $validators = Hal_Validation::getDocValidators($docid);
                            if (is_array($validators)) {
                                foreach ($validators as $uid => $fullname) {
                                    $validator = new Hal_User();
                                    $validator->find($uid);
                                    $mail = new Hal_Mail();
                                    // Tous les validateurs sont prévenus de la fin de l'expertise
                                    $mail->prepare($validator, Hal_Mail::TPL_ALERT_VALIDATOR_END_VALIDATION, array(
                                        $document
                                    ));
                                    $mail->writeMail();
                                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("La fin de l'expertise a été notifiée par e-mail à l'expert : " . $fullname);
                                }
                            }

                            Hal_Validation::delDocInValidation($docid);

                            $document->putOnModeration();
                            Hal_Document_Logger::log($docid, Hal_Auth::getUid(), Hal_Document_Logger::ACTION_MODERATE);

                            foreach ($document->getModerators() as $uid) {
                                $moderator = new Hal_User();
                                $moderator->find($uid);

                                $mail = new Hal_Mail();
                                $mail->prepare(
                                    $moderator, 
                                    Hal_Mail::TPL_ALERT_MODERATOR, 
                                    array($document)
                                );
                                $mail->writeMail();
                            }

                            $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Le document a été remis en modération. Les modérateurs ont été informés par e-mail.");

                            $this->redirect('/administrate/pending-validation');
                        } elseif ($params ['evaluate-action'] == 'validate-reminder') {

                            $validators = Hal_Validation::getDocValidators($docid);

                            if (is_array($validators)) {
                                foreach ($validators as $uid => $fullname) {
                                    $validator = new Hal_User();
                                    $validator->find($uid);

                                    $mail = new Hal_Mail();
                                    // Tous les validateurs sont prévenus de la fin de l'expertise
                                    $mail->prepare(
                                        $validator, 
                                        Hal_Mail::TPL_ALERT_VALIDATOR_REMINDER, 
                                        array($document)
                                    );
                                    $mail->writeMail();
                                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Document " . $document->getId(true) . ' ' . "L'expert " . $fullname . " a été relancé par e-mail.");
                                }
                            } else {
                                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Document " . $document->getId(true) . ' ' . "en expertise scientifique, mais aucun expert défini !");
                            }

                            $this->redirect('/administrate/pending-validation');
                        }
                    }

                    if (count($res) == 1) {
                        $text = $this->view->translate('administrate-action-' . $params ['evaluate-action']);
                        $text = str_replace('%%DOCUMENT%%', $res [0], $text);
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage($text);
                    } else if (count($res) > 1) {
                        $text = $this->view->translate('administrate-action-' . $params ['evaluate-action'] . '-multiple');
                        $text = str_replace('%%DOCUMENTS%%', implode(', ', $res), $text);
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage($text);
                    } else if (count($error)) {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage(implode(',', $error));
                    }
                }

                $this->view->layout()->pageDescription = '<span class="label label-primary">' . count($this->view->documents) . "</span> documents en attente d'expertise";

                $this->formAction = '/administrate/pending-validation';

                $this->render('documents');
            } else {
                // Affichage de documents

                $messages = new Hal_Evaluation_Validation_Message();
                $this->view->responses = $messages->getList(Hal_Auth::getUid());
                $this->view->document = new Hal_Document();
                $this->view->docids = $params ['docid'];
                $this->render('documents-actions');
            }
        }
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
     * Sauvegarde expert
     * + document status passe en validation
     * + envoi mail experts
     * @return void
     */
    public function saveexpertAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        Hal_Validation::saveExpert($this->getRequest());

        $forwardAction = htmlspecialchars($this->getRequest()->getParam('forwardAction'));

        $this->redirect($this->view->url(
            array(
                'controller' => 'administrate',
                'action' => $forwardAction,
                'docid' => (int) $this->getRequest()->getParam('docid')
        )));
        return;
    }

    /**
     * Documents en demande de modification
     * @return void
     */
    public function pendingModificationAction() {
        $params = $this->getRequest()->getParams();
        if ($this->getRequest()->isPost() && isset($params ['method'])) {
            $document = Hal_Document::find(Ccsd_Tools::ifsetor($params ['docid'], 0), Ccsd_Tools::ifsetor($params ['identifiant'], ''), Ccsd_Tools::ifsetor($params ['version'], 0));
            if ($document instanceof Hal_Document) {
                switch ($params ['method']) {
                    // Demande de modifications de la moderation
                    case Hal_Settings_Submissions::ACTION_MODIFY :
                        if (Hal_Document_Acl::canModify($document)) {
                            $this->redirect('/submit/modify/docid/' . $document->getDocid());
                            return;
                        } else {
                            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne pouvez pas modifier le document");
                        }
                        break;

                    // Réponse du déposant aux modérateurs
                    case Hal_Settings_Submissions::ACTION_REPLY :
                        if (Hal_Document_Acl::canModify($document)) {
                            $this->redirect('/administrate/reply/docid/' . $document->getDocid());
                            return;
                        }
                        break;
                }
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Le document demandé n'existe pas !");
            }
        }
        $this->view->forwardAction = $this->getRequest()->getActionName();

        $moderation = new Hal_Moderation();
        $this->view->sites = $moderation->getSites();
        $order = 'DATESUBMIT DESC';
        if (isset($_GET['order'])) {
            switch ($_GET['order']) {
                case 'docdesc':
                    $order = 'IDENTIFIANT DESC';
                    break;
                case 'docasc':
                    $order = 'IDENTIFIANT ASC';
                    break;
                case 'contdesc':
                    $order = 'SCREEN_NAME DESC';
                    break;
                case 'contasc':
                    $order = 'SCREEN_NAME ASC';
                    break;
                case 'datedesc':
                    $order = 'DATESUBMIT DESC';
                    break;
                case 'dateasc':
                    $order = 'DATESUBMIT ASC';
                    break;
                case 'pordesc':
                    $order = 'SITE DESC';
                    break;
                case 'porasc':
                    $order = 'SITE ASC';
                    break;
                default:
                    Ccsd_Tools::panicMsg(__FILE__, __LINE__, 'Unknow SwitchCase value: ' . $order);
            }
        }

        $req = $moderation->getModifDocuments();
        if (isset($_GET['queryid'])) {
            $query = 'd.IDENTIFIANT LIKE ?';
            $value = '%' . $_GET['queryid'] . '%';
            $req->where($query, $value);
        };
        if (isset($_GET['queryuid'])) {
            $query = 'u.SCREEN_NAME LIKE ?';
            $value = '%' . $_GET['queryuid'] . '%';
            $req->where($query, $value);
        };
        if (isset($_GET['querydate'])) {
            $query = 'd.DATESUBMIT LIKE ?';
            $value = '%' . $_GET['querydate'] . '%';
            $req->where($query, $value);
        };
        if (isset($_GET['querypor'])) {
            $query = 's.SITE LIKE ?';
            $value = $_GET['querypor'];
            $req->where($query, $value);
        }
        $this->getDocumentsPagination($req->order($order));
    }

    /**
     * @param  Zend_Db_Select $request
     */
    public function getDocumentsPagination($request) {
        $currentPage = $this->_getParam('page', 1);
        $currentRows = $this->_getParam('rows', 50);

        $adapter = new Zend_Paginator_Adapter_DbSelect($request);
        $documents = new Zend_Paginator($adapter);
        $documents->setItemCountPerPage($currentRows);
        $documents->setCurrentPageNumber($currentPage);

        $this->view->documents = $documents;
        Zend_Paginator::setDefaultScrollingStyle(Hal_Settings_Search::PAGINATOR_STYLE);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('partial/pagination.phtml');
    }

    /**
     * @return void
     */
    public function replyAction() {
        $form = new Ccsd_Form();
        $form->setAttrib('class', 'form-horizontal');
        $form->addElement('textarea', 'reply', array(
            'label' => 'Réponse',
            'required' => true,
            'rows' => 4
        ));
        $form->setActions(true)->createCancelButton($this->view->translate('Annuler'), array(
            'onclick' => 'link("/administrate/pending-modification");',
            'class' => 'btn btn-sm btn-default'
        ))->createSubmitButton($this->view->translate('Répondre'), array(
            'class' => 'btn btn-primary',
            'style' => 'margin-top:0px;'
        ));

        if ($this->getRequest()->isPost()) {
            // Soumission du formulaire
            $params = $this->getRequest()->getPost();
            if (isset($params ['docid']) && isset($params ['reply'])) {
                // Réponse au modérateurs
                $document = Hal_Document::find((int) $params ['docid']);
                $document->reply(Hal_Auth::getUid(), $params ['reply']);
                $contributor = new Hal_User();
                $contributor->find($document->getContributor('uid'));
                $mail = new Hal_Mail();
                // Tous les validateurs sont prévenus de la fin de l'expertise
                $mail->prepare($contributor, Hal_Mail::TPL_DOC_ADMINMODIFY, array(
                    $document
                ));
                $mail->writeMail();
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Votre réponse a été envoyée aux modérateurs");
            } else {
                // Erreur
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Erreur lors de l'envoi de la réponse");
            }
            $this->redirect('/administrate/pending-modification');
            return;
        } else {
            $params = $this->getRequest()->getParams();
            $document = Hal_Document::find((int) $params ['docid']);
            if ($document instanceof Hal_Document && Hal_Document_Acl::canModify($document)) {
                $this->view->comment = Hal_Document_Logger::getLastComment($document->getDocid(), Hal_Document_Logger::ACTION_ASKMODIF);
                $form->addElement("hidden", "docid", array(
                    'value' => $params ['docid']
                ));
                $this->view->form = $form;
            } else {
                $this->redirect('/administrate/pending-modification');
                return;
            }
        }
    }

    /**
     *
     */
    public function indexAction() {
        $this->view->title = "Administrer";
        $this->renderScript('index/submenu.phtml');
    }

    /**
     * @param Hal_User_Merge $um
     * @param Hal_User $toUidUser
     * @param Hal_User $fromUidUser
     * @return Ccsd_FlashMessenger
     */
    public function replaceUserProfile($um, $toUidUser, $fromUidUser) {
        $fromUidhasHalAccountData = $fromUidUser->hasHalAccountData($fromUidUser->getUid());
        $toUidhasHalAccountData   = $toUidUser->hasHalAccountData($toUidUser->getUid());

        if ((! $toUidhasHalAccountData) && $fromUidhasHalAccountData) {
            $overwriteProfileResult = $um->moveUserProfile();
            if ($overwriteProfileResult >= 1) {
                return new Ccsd_FlashMessenger('success', 'Profil utilisateur source déplacé vers le profil cible');
            } else {
                return new Ccsd_FlashMessenger('danger', 'Échec du déplacement du profil utilisateur source vers le profil cible');
            }
        }

        // si chaque profil existe
        if ($toUidhasHalAccountData && $fromUidhasHalAccountData) {
            // l'utilisateur cible a déjà un profil
            $deleteProfileResult = $um->removeUserProfile();
            if ($deleteProfileResult >= 1) {
                return new Ccsd_FlashMessenger('success', 'Profil utilisateur supprimé');
            } else {
                return new Ccsd_FlashMessenger('danger', 'Échec de la suppression du profil utilisateur');
            }
        }

        // si pas de profil pour l'utilisateur source ni pour l'utilisateur de destination
        if ((! $toUidhasHalAccountData) && (! $fromUidhasHalAccountData)) {
            $msg = new Ccsd_FlashMessenger('info', "Les données liées de l'application ont été migrées mais l'utilisateur de destination n'a pas de profil HAL.");
            $msg -> addMessage('info', "Il doit se connecter pour se crééer un profil HAL.") ;
            return $msg;
        }

        // si pas de profil HAL pour l'utilisateur source et si il y a un profil de destination on ne fait rien
        if ($toUidhasHalAccountData && ( ! $fromUidhasHalAccountData)) {
            return new Ccsd_FlashMessenger('info', "Les données liées de l'application ont été migrées mais l'utilisateur source n'a pas de profil HAL.");
        }

        Ccsd_Tools::panicMsg(__FILE__,__LINE__, 'Tests not exhautive! ...');
        return '';
    }

    /**
     * Fusion des profils HAL
     */
    public function mergeUsersAction() {

        Zend_Session::regenerateId();

        $uidFrom = $this->getRequest()->getParam('uidFrom');
        $uidTo   = $this->getRequest()->getParam('uidTo');
        $tables  = $this->getRequest()->getParam('tables');

        $um = new Hal_User_Merge();
        $um->setUidFrom($uidFrom);
        $um->setUidTo($uidTo);

        if (is_array($tables)) {
            // On transmets a $uidTo l'ensemble des proprietes de $uidFrom
            // TODO: Attention, si des proprietes sont non multiples, cela risque de ne pas marcher.
            //       Pour USER_RIGHT, cela risque de dupliquer des droits ?
            $this->view->mergeResults = $um->mergeUsers($tables);
            $logResult = $um->logUserMerge(Hal_Auth::getUid());
            if ($logResult == 1) {
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage('La fusion a été journalisée');
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("La fusion n'a pas pu être journalisée");
            }
        } else {
            $this->view->mergeResults = [];
        }

        $toUidUser   = new Hal_User(['UID' => $um->getUidTo()]);
        $fromUidUser = new Hal_User(['UID' => $um->getUidFrom()]);

        $toUidUser->postModifyUser(); // effacement cache de document + reindexation de documents, ...

        $flashMessenger = $this -> replaceUserProfile($um, $toUidUser, $fromUidUser);
        $flashMessenger -> toSessionFlashMessenger($this->_helper->FlashMessenger);

        $this->render('user-merge-results');
    }

    /**
     * Administration des utilisateurs
     */
    public function usersAction() {
        $params = $this->getRequest()->getParams();
        $this->view->q = trim(Ccsd_Tools::ifsetor($params ['q'], ''));
        if (isset($params ['uid']) && isset($params ['method'])) {

            Zend_Session::regenerateId();

            $this->view->method = $params ['method'];
            if ($params ['method'] == 'account') {
                $form = new Hal_User_Form_Edit(null, "HAL");
                $form->setAction($this->view->url());
                $form->setActions(true)->createSubmitButton("Enregistrer");
                if (isset($params ['save']) && $form->isValid($this->getRequest()->getPost())) {
                    $values = $form->getValues();
                    $halUser = new Hal_User(array_merge($values ["ccsd"], $values ["hal"]));
                    if ($form->getSubForm("ccsd")->PHOTO->isUploaded()) {
                        $photoFileName = $form->ccsd->PHOTO->getFileName();
                        try {
                            $halUser->savePhoto($photoFileName);
                        } catch (Exception $e) {
                            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage($e->getMessage());
                        }
                    }
                    $halUser->save();
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Données utilisateur modifiées');
                    $this->redirect('/administrate/users/' . ($this->view->q != '' ? 'q/' . $this->view->q : ''));
                    return;
                }

                $ccsdUserMapper = new Ccsd_User_Models_UserMapper();
                $halUser = new Hal_User();
                $data = $ccsdUserMapper->find($params ['uid'], $halUser)->toArray();
                $halData = $halUser->find($params ['uid']);
                if (is_array($halData)) {
                    $data = array_merge($data, $halData);
                }



                $form->setDefaults($data);
                $form->addElement('hidden', 'uid', array(
                    'value' => $params ['uid']
                ));
                $form->addElement('hidden', 'method', array(
                    'value' => $params ['method']
                ));
                $form->addElement('hidden', 'save', array(
                    'value' => '1'
                ));
                $form->addElement('hidden', 'q', array(
                    'value' => $this->view->q
                ));
                $form->ccsd->PHOTO->getDecorator('Picture')->setUID($params ['uid']);

                $this->view->form = $form;
                $this->view->user = $halUser;
                $this->render('users-account');
            } else if ($params ['method'] == 'rights') {
                $halUser = new Hal_User();
                $halUser->find($params ['uid']);
                $isHalAdmin = Hal_Auth::isHALAdministrator();

                if (isset($params ['save'])) {
                    $roles = [Hal_Acl::ROLE_ADMINSTRUCT, Hal_Acl::ROLE_TAMPONNEUR, Hal_Acl::ROLE_ADMIN];
                    if ($isHalAdmin) {
                        $roles[] = Hal_Acl::ROLE_MODERATEUR;
                        $roles[] = Hal_Acl::ROLE_VALIDATEUR;
                    }
                    $halUser->deleteRoles($roles);
                    $halUser->addRoles($params['roles']);
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les privilèges de l'utilisateur sont sauvegardés.");
                    $this->redirect('/administrate/users/' . ($this->view->q != '' ? 'q/' . $this->view->q : ''));
                    return;
                }
                $this->view->user = $halUser;
                $this->view->isHalAdmin = $isHalAdmin;

                $instances = array();
                foreach (Hal_Site_Portail::getInstances() as $site) {
                    $instances [$site ['SID']] = $site ['NAME'];
                }
                natcasesort($instances);

                $this->view->instances = $instances;

                $this->render('users-rights');
            }
        }
        if ($this->view->q != '') {
            // Recherche d'utilisateurs
            $refUsers = new Hal_Users();
            $this->view->users = $refUsers->search($this->view->q);



            /**
             * Préparation Fusion des profils HAL
             */
            $this->view->mergeAllowed = false;

            if ($this->getRequest()->getParam('mergeFromUid')) {
                $halUser = new Hal_User();
                $halUser->find(intval($params ['mergeFromUid']));
                $this->view->mergeFromUid = $this->getRequest()->getParam('mergeFromUid');
                $this->view->fromUidHasIdhal = Hal_Cv::existForUid($this->getRequest()->getParam('mergeFromUid'));
                $this->view->fromUidHasCv = Hal_Cv::existCVForUid($this->getRequest()->getParam('mergeFromUid'));
                $this->view->mergeFromUser = $halUser;


                $um = new Hal_User_Merge();

                $this->view->usersTable = $um->getApplicationUsersTable();
                $this->view->tablesWithUserUID = $um->getValueOccurr('UID', $this->getRequest()->getParam('mergeFromUid'));
            }

            if ($this->getRequest()->getParam('mergeToUid')) {
                $halUser = new Hal_User();
                $halUser->find(intval($params ['mergeToUid']));
                $this->view->toUidHasIdhal = Hal_Cv::existForUid($this->getRequest()->getParam('mergeToUid'));
                $this->view->toUidHasCv = Hal_Cv::existCVForUid($this->getRequest()->getParam('mergeToUid'));
                $this->view->mergeToUid = $this->getRequest()->getParam('mergeToUid');
                $this->view->mergeToUser = $halUser;
                $this->view->mergeAllowed = true;
            }


            if (($this->view->toUidHasCv !== null) and ( ($this->view->fromUidHasCv !== false) and ( $this->view->toUidHasCv !== false))) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Impossible de fusionner 2 profils utilisateurs qui ont chancun un idhal.');
                $this->view->mergeAllowed = false;
            }


            if (($this->getRequest()->getParam('mergeFromUid') == $this->getRequest()->getParam('mergeToUid')) && ($this->getRequest()->getParam('mergeToUid') != null)) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Non : impossible de fusionner un utilisateur avec lui même.');
                $this->view->mergeAllowed = false;
            }

            /**
             * END Préparation Fusion des profils HAL
             */
        }
    }

    /**
     * Valide un compte utilisateur
     */
    public function ajaxvalidateuserAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $params = $this->getRequest()->getPost();
        if ($this->getRequest()->isXmlHttpRequest() && isset($params ['uid'])) {
            try {
                $userMapper = new Ccsd_User_Models_UserMapper();
                $userMapper->activateAccountByUid($params ['uid']);
            } catch (Zend_Db_Adapter_Exception $e) {
                return $e->getMessage();
            }
        }
    }

    /**
     * Suspend un compte utilisateur
     */
    public function ajaxTerminateUserAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $params = $this->getRequest()->getPost();

        if ($this->getRequest()->isXmlHttpRequest() && isset($params ['uid'])) {

            if ($params ['uid'] == Hal_Auth::getUid()) {
                echo json_encode(array(
                    "error" => "I Cannot Self-Terminate"
                ));
                return false;
            }

            try {
                $userMapper = new Ccsd_User_Models_UserMapper();
                $userMapper->terminateAccountByUid($params ['uid']);
                echo json_encode(array(
                    "success" => "Compte désactivé"
                ));
            } catch (Zend_Db_Adapter_Exception $e) {
                echo json_encode(array(
                    "error" => $e->getMessage()
                ));
                return false;
            }
        }
    }

    public function ajaxsearchcollectionAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        if (isset($_GET ['term'])) {
            echo Zend_Json::encode(Hal_Site::autocomplete($_GET ['term'], Hal_Site::TYPE_COLLECTION));
        }
    }

    /**
     * Cherche un utilisateur
     */
    public function ajaxsearchuserAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $res = array();
        $valid = false;
        if (isset($_GET['valid']) && ($_GET['valid'] == 1)) {
            $valid = true;
        }
        if (isset($_GET ['term'])) {
            $refUsers = new Ccsd_User_Models_DbTable_User();
            foreach ($refUsers->search($_GET ['term'], 100, $valid) as $user) {
                $label = Ccsd_Tools::formatAuthor($user ['LASTNAME'], $user ['FIRSTNAME']);
                $label .= ' (' . $user ['UID'] . ') - ' . $user ['EMAIL'];
                $res [] = array(
                    'id' => $user ['UID'],
                    'label' => $label
                );
            }
        }
        echo Zend_Json::encode($res);
    }

    public function ajaxsearchdocumentAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        if (isset($_GET ['term'])) {
            echo Zend_Json::encode(Hal_Site::autocomplete($_GET ['term'], Hal_Site::TYPE_COLLECTION));
        }
    }

    public function ajaxmodifmetaAction() {
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();
            $docid = $params ['docid'];
            $metaname = $params ['meta'];
            $indexer = $params['indexer'];
            if (isset($params ['old'])) {
                $oldvalue = $params ['old'];
            } else {
                $oldvalue = '';
            }

            $halMeta = new Hal_Document_Meta_Simple($metaname, $params ['value'], '', '', Hal_Auth::getUid());
            $halMeta->replaceMeta($docid, $oldvalue);

            if ($indexer == 'doublon') {
                // Indexation
                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid));
            }
        }
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function ajaxmodifmetagroupAction() {
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();
            $docid = $params ['docid'];
            $metaname = $params ['meta'];
            $oldlang = $params ['oldlang'];
            $indexer = $params ['indexer'];
            Hal_Document::updateMetaGroup($docid, $metaname, $params ['value'], $oldlang);

            if ($indexer == "doublon") {
                // Indexation
                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid));
            }
        }
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function ajaxajoutmetaAction() {
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();
            $docid = $params ['docid'];
            $metaname = $params ['meta'];
            $indexer = $params ['indexer'];
            foreach (Zend_Json::decode($params['value']) as $value => $group) {
                Hal_Document::ajoutMeta($docid, $metaname, $value, $group);
            }

            if ($indexer == "doublon") {
                // Indexation
                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid));
            }
        }
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /** 
     * gestion des doublons dans les dépôts
     * 
     * @return void
     */
    public function doublonsAction() {
        $request = $this->getRequest();
        if (! ($request->isPost())) {
            return;
        }
        $params = $request->getPost();
        $id1 = $params ['id1'];
        $id2 = $params ['id2'];
        if (isset($id1) &&
            isset($id2) &&
            $id1 != $id2)
        {
            if (is_numeric($id1) && is_numeric($id2)) {
                // Docid
                $doc1 = Hal_Document::find($id1);
                $doc2 = Hal_Document::find($id2);
            }
            else {
                // Identifiant
                $doc1 = Hal_Document::find(0, $id1);
                $doc2 = Hal_Document::find(0, $id2);
            }
            // doc can have same id if aliased previously
            if ($doc1 instanceof Hal_Document && $doc2 instanceof Hal_Document && $doc1->getId() != $doc2->getId()) {
                if (isset($params ['method']) && $params ['method'] == 'hierarchiser' && isset($params ['save'])) {
                    // Hierarchiser des documents
                    if ($doc1->getDocid() == $params ['save']) {
                        $docVersNew = $doc1;
                        $docVersPre = $doc2;
                    } else {
                        $docVersNew = $doc2;
                        $docVersPre = $doc1;
                    }

                    if ($docVersNew->getFormat() == 'notice' && $docVersPre->getFormat() == 'file') {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('La notice ' . $docVersNew->getId() . ' ne peut pas être la nouvelle version du fichier ' . $docVersPre->getId() . '.');
                    } else {

                        // Enregistrement du lien entre docu hierarchisé et docu new
                        $docVersPre->addSameAs($docVersNew->getId());

                        //Transfert les tampons
                        $docVersNew->changeTampon($docVersPre);

                        // Transfert de propriétaire
                        foreach ($docVersPre->getOwner() as $uidprec) {
                            foreach ($docVersNew->getOwner() as $uidnew) {
                                if ($uidnew != $uidprec) {
                                    $docVersNew->addProprio($uidprec);
                                }
                            }
                        }

                        // Nouvelle définition du document hierarchisé
                        $docVersNew->versnew(Hal_Auth::getUid(), 'Nouvelle version de ' . $docVersPre->getId(), false);

                        // Changement de statut du document précédent
                        $docVersPre->verspre(Hal_Auth::getUid(), 'Ancienne version de ' . $docVersNew->getId(), false);
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage($docVersNew->getId() . ' est la nouvelle version de ' . $docVersPre->getId() . '.');

                        //Mise à jour des versions
                        $lastVersion = max(array_keys($docVersPre->getDocVersions()));

                        foreach ($docVersNew->getDocVersions() as $version) {
                            $docversion = Hal_Document::find($version['DOCID']);
                            if ($docversion) {
                                $v = $lastVersion + $docversion->getVersion();
                                // Défini le nouvel identifiant
                                $docVersNew->changeId($docVersPre, $docversion->getDocid(), $v);

                                $docversion->deleteCache();

                                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($version['DOCID']), 'hal', 'UPDATE');
                            }
                        }

                        foreach ($docVersPre->getDocVersions() as $version) {
                            $docversion = Hal_Document::find($version['DOCID']);
                            if ($docversion) {
                                $docversion->deleteCache();

                                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($version['DOCID']), 'hal', 'UPDATE');
                            }
                        }
                    }
                } elseif (isset($params ['method']) && $params ['method'] == 'fusion' && isset($params ['save'])) {
                    // Fusion des documents
                    if ($doc1->getDocid() == $params ['save']) {
                        $docToSave = $doc1;
                        $docToDelete = $doc2;
                    } else {
                        $docToSave = $doc2;
                        $docToDelete = $doc1;
                    }

                    if ($docToSave->getFormat() == 'notice' && $docToDelete->getFormat() == 'file') {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('La notice ' . $docToSave->getId() . ' ne peut pas être la nouvelle version du fichier ' . $docToDelete->getId() . '.');
                    } else {
                        // Enregistrement du lien entre docu supprimé et doc restant
                        $docToSave->addSameAs($docToDelete->getId());

                        //Transfert les tampons
                        $docToSave->changeTampon($docToDelete);

                        // Nouvelle définition du document fusionné
                        $docToDelete->fusion(Hal_Auth::getUid(), '', $docToSave, true);
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage($docToSave->getId() . ' conservé, ' . $docToDelete->getId() . ' fusionné');

                        // Mise à jour de l'Indexation
                        Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docToSave->getDocid()));

                        // Suppression de l'index, DOCSTATUS 88 n'est plus visible
                        Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docToDelete->getDocid()), 'HAL dédoublonnage', 'DELETE');
                    }
                } else {
                    $this->view->documents = array(
                        $doc1,
                        $doc2
                    );
                }
            } else {
                // Cas d'erreur: pas de doc ou doc identiques
                $error = "Les docId sont les memes... Les documents ont sans doute deja ete fusionnes";
                if (!$doc1 instanceof Hal_Document) {
                    $error = "Erreur dans la saisie de l'identifiant du premier document : ". $id1;
                } else if (!$doc2 instanceof Hal_Document ){
                    $error = "Erreur dans la saisie de l'identifiant du deuxième document : ". $id2;
                }
                // Meme docid...
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage($error);
            }
        } else {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Erreur dans la saisie des identifiants (ou identifiants identiques)");
        }
    }

    /**
     * Remet un document en modération
     */
    public function ajaxmoderateAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        if (!$this->getRequest()->isXmlHttpRequest()) {
            echo "Error...";
            return false;
        }
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();
            try {
                Hal_Document::moderate($params ['docid'], $params ['uid']);
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }
    }
    
    /**
     * Gestion des doublons dans le cas de la saisie des docid au lieu des identifiants
     *
     * Fonction obsolète
     * @param $doc1 Hal_Document premier document à dédoublonner
     * @param $doc2 Hal_Document deuxième document à dédoublonner
     * @param $params array résultat de getParams()
     * 
     * @return bool : traitement terminé ou pas
     */
    protected function doublonsDocid($doc1, $doc2, $params) {


        if ($doc1 instanceof Hal_Document && $doc2 instanceof Hal_Document) {
            if ($doc1->getId() != $doc2->getId()) {
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Identifiants différents : cas standard.");
                    
                return false;
            }
            // on ne peut pas garder le document qui n'est pas en ligne
            if (!($doc1->isVisible() && $doc2->isVisible())) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne pouvez fusionner que la dernière version des documents.");
                return true;
            }

            if (isset($params ['method']) && $params ['method'] == 'hierarchiser' && isset($params ['save'])) {
                if ($doc1->getVersion() != $doc2->getVersion()) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne pouvez pas hiérarchiser des documents de même identifiant et de versions différentes.");
                    return true;
                }
                    
                // Hierarchiser des documents
                if ($doc1->getId() == $params ['save']) {
                    $docVersNew = $doc1;
                    $docVersPre = $doc2;
                } else {
                    $docVersNew = $doc2;
                    $docVersPre = $doc1;
                }
                // Défini la bonne version
                $v = 1;
                foreach ($docVersPre->getVersionsFromId($docVersPre->getId()) as $version) {
                    $v ++;
                }
                // Définir le nouvel identifiant
                $docVersNew->changeId($docVersPre, $docVersNew, $v);

                // Nouvelle définition du document hierarchisé
                $docVersNew->versnew(Hal_Auth::getUid(), 'Nouvelle version de ' . $docVersPre->getId(), false);

                // Changement de statut du document précédent
                $docVersPre->verspre(Hal_Auth::getUid(), 'Ancienne version de ' . $docVersNew->getId(), false);
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage($docVersNew->getId() . ' est la nouvelle version de ' . $docVersPre->getId() . '.');

                // Indexation
                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docVersNew->getDocid()));

                // Indexation
                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docVersPre->getDocid()));
            } elseif (isset($params ['method']) && $params ['method'] == 'fusion' && isset($params ['save'])) {
                // Fusion des documents
                if ($doc1->getId() == $params ['save']) {
                    $docToSave = $doc1;
                    $docToDelete = $doc2;
                } else {
                    $docToSave = $doc2;
                    $docToDelete = $doc1;
                }
                if (($docToDelete->getFormat() == Hal_Document::FORMAT_FILE) 
                    && ($docToSave->getFormat() != Hal_Document::FORMAT_FILE)) {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous devez conserver le dépôt avec fichier");
                        return true;
                }

                // Nouvelle définition du document fusionné
                $docToDelete->fusion(Hal_Auth::getUid(), '', $docToSave, true);
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage($docToSave->getId() . ' conservé, ' . $docToDelete->getId() . ' fusionné');

                // Mise à jour de l'Indexation
                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docToSave->getDocid()));

                // Suppression de l'index, DOCSTATUS 88 n'est plus visible
                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docToDelete->getDocid()), 'HAL dédoublonnage', 'DELETE');
            } else {
                $this->view->documents = array(
                    $doc1,
                    $doc2
                );
            }

        } else {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Erreur dans la saisie des identifiants");
        }
        return true;
    }


    /**
     * Traitement par lot
     */
    public function batchAction()
    {
        $this->view->render = 'index';
        if ($this->getRequest()->isPost()) {
            $query = $this->getRequest()->getParam('query', null);
            if (null !== $query) {
                $res = Hal_Stats::getDocids($query, '', 100);
                $this->view->docids = $res['docids'];
                $this->view->fields = Hal_Stats::getStatFields();
                $this->view->render = 'field';
            }

            $field = $this->getRequest()->getParam('field', null);
            if (null !== $field) {
                //var_dump($this->getRequest()->getParams());exit;

                $query = 'q=docid:(' . urlencode(implode(' OR ', $this->getRequest()->getParam('docids'))). ')';
                $query .= "&start=0&rows=0&wt=phps&omitHeader=true&facet=true&facet.mincount=1&facet.limit=10000&facet.field=" . $this->getRequest()->getParam('field');
                $solrResult = Ccsd_Tools::solrCurl($query, 'hal', 'select', 29);
                $solrResult = unserialize($solrResult);
                $this->view->data = $solrResult['facet_counts']['facet_fields'][$this->getRequest()->getParam('field')];
                $this->view->render = 'data';
            }
        }


    }
}
