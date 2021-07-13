<?php

/**
 * Class UserController
 */

use Hal\Document\Lock;

class UserController extends Hal_Controller_Action {

    public function indexAction() {
      if (!Hal_Auth::isLogged()) {
            $this->_helper->FlashMessenger->setNamespace('warning')->addMessage("Vous devez vous authentifier pour accéder à cette page.");
            $this->redirect(PREFIX_URL);
        }

        $halUser = Hal_User::createUser(Hal_Auth::getUid());

        $this->view->halUser = $halUser->toArray();

        $ccsdUser = new Ccsd_User_Models_User ();
        $ccsdUserMapper = new Ccsd_User_Models_UserMapper ();

        $ccsdUserMapper->find(Hal_Auth::getUid(), $ccsdUser);
        $this->view->ccsdUser = $ccsdUser->toArray();

        $this->view->roles = Hal_Auth::getDetailsRoles();
    }

    /**
     * Change d'utilisateur
     *
     * @param integer $uidToSu
     */
    public function suAction() {

        Zend_Session::regenerateId();

        /**
         * Pour changer d'utilisateur
         */
        $uidToSu = $this->getRequest()->getParam('uid');
        if ($uidToSu == null) {
            $this->_helper->FlashMessenger->setNamespace('warning')->addMessage("Pas de paramètre utile.");
            $this->redirect(PREFIX_URL);
        }

        if (!Hal_Auth::isLogged()) {
            $this->_helper->FlashMessenger->setNamespace('warning')->addMessage("Vous devez vous authentifier pour accéder à cette page.");
            $this->redirect(PREFIX_URL);
        }

        // Qui a le droit de changer d'utilisateur
        if (Hal_Auth::isHALAdministrator() !== true) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous n'avez pas les privilèges requis pour accéder à cette page.");
            $this->redirect($this->view->url(array(
                                'controller' => 'user',
                                'action' => 'index',
                                'lang' => Hal_Auth::getLangueid()
                                    ), null, true));
            return;
        }

        // check uid
        if (filter_var($uidToSu, FILTER_VALIDATE_INT) == false) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Ce UID n'est pas valide.");
            $this->redirect(PREFIX_URL);
        }

        $user = Hal_User::createUser($uidToSu);

        if ($user === null) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Ce UID n'est pas valide, il n'existe pas.");
            $this->redirect(PREFIX_URL);
        }

        $fromUid = Hal_Auth::getUid();
        Hal_Auth::getInstance()->clearIdentity();
        Hal_Auth::setIdentity($user);
        $user->setScreen_name();

        Ccsd_User_Models_UserMapper::suLog($fromUid, $uidToSu, 'halv' . Hal_Settings::VERSION, 'GRANTED');

        $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Vous avez changé pour le compte de " . $user->getScreen_name());

        $this->redirect($this->view->url(array(
                            'controller' => 'user',
                            'action' => 'index',
                            'lang' => Hal_Auth::getLangueid()
                                ), null, true));
        return;
    }

    /**
     * Connecte with ORCID
     *
     */
    public function coextAction() {
        $token = $this->getRequest()->getParam('code');
        $url = $this->getRequest()->getParam('url');

        $localuri = $this->getRequest()->getHttpHost(); //Portail Local
        $urlscheme = parse_url($url, PHP_URL_SCHEME); //Protocol de redirection
        $urlp = parse_url($url, PHP_URL_HOST); //Portail de redirection

        if ($localuri == $urlp) { //Si les deux portails concordent alors l'authentification est possible
            if ($url != null){
                if (Hal_Site_Portail::existFromUrl($urlp) == false){
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Cette URL n'est pas valide.");
                    $this->redirect(PREFIX_URL);
                }
            }

            $data = Hal_User::getOrcidWithToken($token);
            $uid = Hal_User::getUidFromIdExt($data['orcid'], 'ORCID');

            if ($uid === false) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Cet ORCID n'est pas lié à un compte HAL.");
                $this->redirect(PREFIX_URL);
            }

            $user = Hal_User::createUser($uid);

            if ($user === null) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Ce UID n'est pas valide, il n'existe pas.");
                $this->redirect(PREFIX_URL);
            }

            Hal_Auth::getInstance()->clearIdentity();
            Hal_Auth::setIdentity($user);
            $user->setScreen_name();

            $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Authentification réussie.");

            $this->redirect($url);
            return;
        } else { //Sinon redirection sur le bon portail avant connexion
            $this->redirect($urlscheme. '://'. $urlp. '/user/coext?url='.$url.'&code='. $token);
        }

    }

    /**
     * Login utilisateur
     * Après login redirige :
     * - sur la page de modification de compte si pas de champs Application HAL
     * - sur la page de forward-controller + forward-action envoyé en paramètre
     * à CAS
     * - sur le compte utilisateur si pas de page de destination envoyé en
     * paramètre à CAS
     */
    public function loginAction() {
        // CAS si non definit ou definit a CAS.
        $params = $this->_request->getParams();
        $halUser = new Hal_User ();

        switch (AUTH_TYPE) {
            case 'DBTABLE':

                if (!isset($params['username'])) {
                    # Il faut renvoyer vers le formulaire de login
                    $form = new Ccsd_User_Form_Accountlogin();
                    $form->setAction($this->view->url());
                    $form->setActions(true)->createSubmitButton("Connexion");
                    $this->view->form = $form;
                    $this->renderScript('user/login.phtml');
                    return;
                } else {
                    # on vient du formulaire de login
                    $db = Ccsd_Db_Adapter_Cas::getAdapter();  # la db est celle specifier dans la "service CAS non utilise... On devrait plutot utiliser le nom AUTH plutot que CAS
                    $authAdapter = new Hal_Auth_Adapter_DbTable($db, 'T_UTILISATEURS', 'USERNAME', 'PASSWORD', 'SHA2(?, 512) AND VALID=1');
                    $login    = $params['username'];
                    $password = $params['password'];
                    $authAdapter->setIdentity($login)->setCredential($password);
                }
                break;
            default:
                # l'identite sera pris en charge par CAS
                $authAdapter = new Ccsd_Auth_Adapter_Cas ();
                $authAdapter->setIdentityStructure($halUser);
                $authAdapter->setServiceURL($this->_request->getParams());
                break;
        }

        try {

            $result = Hal_Auth::getInstance()->authenticate($authAdapter);
        } catch (CAS_AuthenticationException $e) {
            $this->view->message = 'Échec authentification CAS';
            $this->view->exception = $e;
            $this->view->description = "Échec de l'authentification avec le serveur CAS";
            $this->renderScript('error/error.phtml');
            return;
        } catch (Zend_Auth_Adapter_Exception $e) {
            $this->view->message = 'Échec authentification';
            $this->view->exception = $e;
            $this->view->description = "Échec de l'authentification";
            $this->renderScript('error/error.phtml');
            return;
        }

        switch ($result->getCode()) {
            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
            case Zend_Auth_Result::FAILURE :
                // auth CAS: on ne devrait jamais arriver là : c'est géré par CAS
                // Auth Dbtable: on peut y venir.
                $this->view->message = 'Échec authentification';
                $this->view->description = implode(' ; ', $result->getMessages());
                $this->renderScript('error/error.phtml');
                break;

            case Zend_Auth_Result::SUCCESS :
                Zend_Session::regenerateId();
                // $authAdapter->setIdentityStructure($halUser);
                $identity = Hal_Auth::getInstance()->getIdentity();
                $identity->load();
                // Info sur la connexion
                $halUser->setUid(Hal_Auth::getUid());
                Hal_User::logUserConnexion($halUser->getUid(), SITEID);

                $halUser->hasHalAccountData($halUser->getUid());

                if ($halUser->getHasAccountData() === TRUE) {
                    $halUser->find($halUser->getUid());
                    $halUser->populatePrefDepotFromUid($halUser->getUid());
                    $localeSession = new Zend_Session_Namespace('Zend_Translate');
                    $localeSession->lang = Hal_Auth::getLangueid();
                } else {
                    $halUser->setScreen_name();
                }

                // $authAdapter->setIdentityStructure($halUser);

                // Reset de la session au moment du login
                $session = new Hal_Session_Namespace(SESSION_NAMESPACE);
                $session->resetSingleInstance(SESSION_NAMESPACE);

                $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Authentification réussie.');

                // pas de données dans la table de HaL, formulaire pour
                // compléter données utilisateur
                if ($halUser->getHasAccountData() === FALSE) {
                    $this->redirect($this->view->url(array(
                                        'controller' => 'user',
                                        'action' => 'edit'
                    )));
                    return;
                }

                // Pas de controller
                if (null == $this->_request->getParam('forward-controller')) {

                    $this->redirect($this->view->url(array(
                                        'controller' => 'user',
                                        'action' => 'index',
                                        'lang' => Hal_Auth::getLangueid()
                                            ), null, true));
                    return;
                }

                // Pas d'action
                if (null == $this->_request->getParam('forward-action')) {
                    $uri = $this->view->url(array(
                        'controller' => $this->_request->getParam('forward-controller'),
                        'lang' => Hal_Auth::getLangueid()
                            ), null, true);
                }

                // Récupération des paramètres supplémentaires (et
                // suppression de ceux dont on a plus besoin)


                unset($params ['forward-controller'], $params ['forward-action'], $params ['module']);

                $params ['controller'] = $this->_request->getParam('forward-controller');
                $params ['action'] = $this->_request->getParam('forward-action');
                $params ['lang'] = Hal_Auth::getLangueid();

                $uri = $this->view->url($params, null, true);

                // Si uri de redrection on ne garde que controller + action
                if (null != $this->_request->getParam('forward-uri')) {
                    $params = array();
                    $params ['controller'] = $this->_request->getParam('forward-controller');
                    $params ['action'] = $this->_request->getParam('forward-action');
                    $params ['lang'] = Hal_Auth::getLangueid();

                    $uri = $this->view->url($params, null, true);
                    $uri .= '?' . $this->_request->getParam('forward-uri');
                }

                $this->redirect($uri);
                break;
            default:
                throw new Exception("Panic: Unexpected value (" . $result->getCode() . ") for code.");
        }
    }

    /**
     * Logout du client
     */
    public function logoutAction() {
        switch (AUTH_TYPE) {
            case 'DBTABLE':
                $auth = new Hal_Auth_Adapter_DbTable();
                break;
            default:
                $auth = new Ccsd_Auth_Adapter_Cas ();
        }

        $hostname = false;
        $hostname = Ccsd_Auth_Adapter_Cas::getCurrentHostname();
        $hostname = rtrim($hostname, '/');
        $redirectionUrl = $hostname . '/user/logoutfromcas';
        $auth->logout($redirectionUrl);
        // Never reach in case of CAS use
        $this->redirect($redirectionUrl);
    }

    /**
     * Atterrissage après logout du client qui a été redirigé par le
     * serveur CAS
     * Verifie que la déconnexion est effective
     * Redirige vers la page d'accueil
     */
    public function logoutfromcasAction() {
        $this->_helper->layout()->disableLayout();

        if (Hal_Auth::isLogged()) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage(Ccsd_User_Models_User::LOGOUT_FAILURE);
        } else {
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage(Ccsd_User_Models_User::LOGOUT_SUCCESS);
        }

        Zend_Session::regenerateId();

        $hostname = Ccsd_Auth_Adapter_Cas::getCurrentHostname();
        if ($hostname) {
            $this->redirect($hostname);
            return;
        } else {
            $this->redirect($this->view->url(array(
                                'controller' => 'index'
                                    ), null, true));
            return;
        }
    }

    /**
     * @param Hal_User $user
     * @return Ccsd_User_Models_UserTokens|null
     */
    public function initUserToken($user) {
        $userTokenData = array(
            'UID' => $user->getUid(),
            'EMAIL' => $user->getEmail()
        );
        $userToken = new Ccsd_User_Models_UserTokens($userTokenData);
        $userToken->generateUserToken();
        $userToken->setUsage('VALID'); // token pour validation de compte

        $userTokenMapper = new Ccsd_User_Models_UserTokensMapper($userToken);
        $userTokenSaveResult = $userTokenMapper->save($userToken);
        if (false === $userTokenSaveResult ) {
            return null;
        } else {
            return $userToken;
        }
    }

    /**
     * @param Hal_User $user
     * @param string $template
     * @return bool
     */
    public function  sendValidationMail($user, $template, $action) {
        $userToken = $this->initUserToken($user);
        if (null == $userToken) {
            // can't init token
            return false;
        }

        $this->view->userEmail = $user->getEmail();
        $this->view->fullUserName = $user->getScreen_name();
        $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_CREATE_SUCCESS;
        /**
         * écriture email
         */
        $site = Hal_Site::getCurrentPortail();
        $webSiteUrl = $site->getUrl();
        $url = $webSiteUrl . $this->view->url(array(
                'controller' => 'user',
                'action' => $action,
                'uid' => $user->getUid(),
                'token' => $userToken->getToken()
            ), null, true);

        $mail = new Hal_Mail ();
        $mail->prepare($user, $template, array(
            'TOKEN_VALIDATION_LINK' => $url
        ));

        $mailStatus = $mail->writeMail();
        if ($mailStatus !== true) {
            $this->view->mailResultMessage = "La préparation du message a échoué.";
        }
        return true;
    }
    /**
     * Création d'un compte utilisateur
     */
    public function createAction() {

        switch (AUTH_TYPE) {
            case 'DBTABLE':
                $localAuthAdapter = new Hal_Auth_Adapter_DbTable();
                break;
            default:
                $localAuthAdapter = new Ccsd_Auth_Adapter_Cas ();
        }
        $selfCreate = true;
        if (defined('SELFCREATEACCOUNT') &&  (!SELFCREATEACCOUNT)) {
            // Pas de create par utilisateur
            $selfCreate = false;
            if (!Hal_Auth::isHALAdministrator()) {
                // Pas le droit de creer un compte
                $this->forward('error','error', null, ['error_message' => "Vous devez etre administrateur pour creer un compte"]);
                return;
            }
        } else {
            // Create par utilisateur, mais deja connecter, pas d'autre creation
            if (Hal_Auth::isLogged()) {
                $this->_helper->redirector('index');
            }
        }

        $form = $localAuthAdapter -> getUserCreateForm();
        $form->setAction($this->view->url());
        $form->setActions(true)->createSubmitButton("Créer un compte");
        $this->view->form = $form;

        if ($this->getRequest()->isPost()) {
            // retour des informations
            if ($form->isValid($this->getRequest()->getPost())) {
                $user = new Hal_User($form->getValues());
                $user->setValid(!$selfCreate); // compte non valide par défaut
                $user->setTime_registered();
                $user->setScreen_name(Ccsd_Tools::formatUser($user->getLastname(), $user->getFirstname()));
                $user->setLangueid(Zend_Registry::get('Zend_Locale')->getLanguage());
                // le mot de passe doit peut etre etre genere
                $localAuthAdapter->completeUserInfoIfNeeded($user);
                $userSaveResult = $user->save(true);

                if (false == $userSaveResult) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Échec de la création du compte.");
                    return;
                }
                $user->setUid($userSaveResult);
                if ($selfCreate) {
                    // Compte auto cree: envois du mail de validation
                    $userTokenSaveResult = $this->sendValidationMail($user, Hal_Mail::TPL_ACCOUNT_CREATE,'activate' );
                    if ($userTokenSaveResult === false) {
                        // Echec, on retourne sur le formulaire
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Échec de la création du compte.");
                        return;
                    }
                } else {
                    // Compte cree par admin
                    $userTokenSaveResult = $this->sendValidationMail($user, Hal_Mail::TPL_ACCOUNT_INIT,'resetpassword' );;
                    if ($userTokenSaveResult === false) {
                        // Echec, on retourne sur le formulaire
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Échec envois du compte a l'utilisateur");
                        return;
                    }
                }
            }
        }
    }

    /**
     * Modification des infos personnelles de l'utilisateur
     */
    public function editAction() {
        $ccsdUserMapper = new Ccsd_User_Models_UserMapper ();
        $halUser = new Hal_User ();

        if (!Hal_Auth::isLogged()) {
            $this->_helper->redirector('create');
        }

        Zend_Session::regenerateId();

        $ccsdUserDefaults = $ccsdUserMapper->find(Hal_Auth::getUid(), $halUser);
        $halUserDefaults = $halUser->find(Hal_Auth::getUid());
        $this->view->user = $halUser;

        $arrayUserDefaults = $ccsdUserDefaults->toArray();

        // Si existe données dans la table de l'appli
        if (!is_array($halUserDefaults)) {
            $halUserDefaults = array(
                'SCREEN_NAME' => Hal_Auth::getFullName()
            );
        }

        $arrayUserDefaults = array_merge($arrayUserDefaults, $halUserDefaults);

        $form = new Hal_User_Form_Edit(null, "HAL");
        $form->setAction($this->view->url());

        $form->setActions(true)->createSubmitButton("Enregistrer les modifications");

        $form->setDefaults($arrayUserDefaults);

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {

                $values = $form->getValues();

                $halUser = new Hal_User(array_merge($values ["ccsd"], $values ["hal"]));

                $halUser->setUid(Hal_Auth::getUid());

                if ($form->getSubForm("ccsd")->PHOTO->isUploaded()) {

                    $photoFileName = $form->getSubForm("ccsd")->PHOTO->getFileName();

                    try {
                        $halUser->savePhoto($photoFileName);
                    } catch (Exception $e) {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage($e->getMessage());
                    }
                }

                $halUserSaveResult = $halUser->save();

                if (!$halUserSaveResult) {

                    $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_EDIT_FAILURE;
                    $this->view->form = $form;
                    $this->render('edit');
                    return;
                }

                // sinon le username est supprimé de l'identité : en modification il n'est pas utilisé dans la méthode save()
                $halUser->setUsername(Hal_Auth::getUsername());

                Hal_Auth::setIdentity($halUser);

                $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Les modifications sont sauvegardées.');
                $this->redirect(PREFIX_URL . 'user');
                return;
            }
        }

        $this->view->form = $form;
    }

    /**
     * Modification des préférences de dépot d'un utilisateur
     */
    public function editprefdepotAction()
    {
        $ccsdUserMapper = new Ccsd_User_Models_UserMapper ();

        if (!Hal_Auth::isLogged()) {
            $this->_helper->redirector('create');
        }

        Zend_Session::regenerateId();

        $halUser = Hal_User::createUser(Hal_Auth::getUid());

        $this->view->user = $halUser;

        $form = new Hal_User_Form_EditPrefDepot(null, "HAL");
        $form->setAction($this->view->url());

        $form->setActions(true)->createSubmitButton("Enregistrer les modifications");

        $prefs = $halUser->getPreferencesDepot();

        // FILTRAGE DE LABORATORY et INSTITUTION qui sont ajoutés par le javascript
        $prefs['LABORATORY'] = "";
        $prefs['INSTITUTION'] = "";

        $form->setDefaults($prefs);

        if ($this->getRequest()->isPost() && ($form->isValid($this->getRequest()->getPost()))) {

            $values = $form->getValues();

            // LABO & INSTITUTION & Autodepot & Domain => On transforme le type en array pour qu'il soit correctement enregistré
            if(isset($values["hal"]["LABORATORY"]) && $values["hal"]["LABORATORY"] == "") {
                $values["hal"]["LABORATORY"] = array();
            }
            if(isset($values["hal"]["INSTITUTION"]) && $values["hal"]["INSTITUTION"] == "") {
                $values["hal"]["INSTITUTION"] = array();
            }
            if(!isset($values["hal"]["AUTODEPOT"])) {
                $values["hal"]["AUTODEPOT"] = array();
            }
            if(isset($values["hal"]["DOMAIN"]) && empty($values["hal"]["DOMAIN"])) {
                $values["hal"]["DOMAIN"] = array();
            }
            $halUser->setOptions($values["hal"]);

            $halUserSaveResult = $halUser->savePrefDepot(Hal_Auth::getUid());

            if (!$halUserSaveResult) {
                $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_EDIT_FAILURE;
                $this->view->form = $form;
                $this->render('editprefdepot');
                return;
            }

            // Pour une bonne prise en compte des préférences de l'utilisateur
            Hal_Auth::setIdentity($halUser);
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Les modifications sont sauvegardées.');
            $this->redirect(PREFIX_URL . 'user/index');
            return;
        }

        $this->view->form = $form;
    }

    /**
     * Modification des préférences de mail de l'utilisateur
     */
    public function editprefmailAction()
    {
        $ccsdUserMapper = new Ccsd_User_Models_UserMapper ();

        if (!Hal_Auth::isLogged()) {
            $this->_helper->redirector('create');
        }

        Zend_Session::regenerateId();

        $halUser = Hal_User::createUser(Hal_Auth::getUid());

        $this->view->user = $halUser;

        $form = new Hal_User_Form_EditPrefMail(null, "HAL");
        $form->setAction($this->view->url());

        $form->setActions(true)->createSubmitButton("Enregistrer les modifications");


        $prefs = $halUser->getPreferencesMail();

        $form->setDefaults($prefs);

        if ($this->getRequest()->isPost() && ($form->isValid($this->getRequest()->getPost()))) {

            $values = $form->getValues();

            $halUser->setOptions($values["hal"]);
            $halUser->setOptionsPrefMail($values["hal"]);

            $halUserSaveResult = $halUser->savePrefMail(Hal_Auth::getUid());

            if (!$halUserSaveResult) {
                $this->view->resultMessage = Ccsd_User_Models_User::ACCOUNT_EDIT_FAILURE;
                $this->view->form = $form;
                $this->render('editprefmail');
                return;
            }

            $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Les modifications sont sauvegardées.');
            $this->redirect(PREFIX_URL . 'user/index');
            return;
        }

        $this->view->form = $form;
    }

    public function ajaxdeletephotoAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $params = $this->getRequest()->getPost();

        $res = false;
        if ($this->getRequest()->isXmlHttpRequest() && isset($params ['uid'])) {
            if (Hal_Auth::getUid() == $params ['uid'] || Hal_Auth::isAdministrator()) {
                // Suppression de l'image de l'utilisateur connecté ou par un
                // administrateur
                $user = new Ccsd_User_Models_User(array(
                    'uid' => $params ['uid']
                ));
                $user->deletePhoto();
                if (Hal_Auth::getUid() == $params ['uid']) {
                    $res = '1';
                } else {
                    $res = '2';
                }
            }
        }
        if ($res === false) {
            header("HTTP/1.0 404 Not Found");
            exit();
        }
        echo $res;
    }

    /**
     * Change User password
     */
    public function changepasswordAction() {

        Zend_Session::regenerateId();

        // Retour de l'activation OK
        if ($this->getRequest()->getParam('change') == 'done') {
            $this->render('changepassword');
            return;
        }

        $form = new Ccsd_User_Form_Accountchangepassword ();
        $form->setAction($this->view->url());
        $form->setActions(true)->createSubmitButton("Changer le mot de passe");

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {

                $user = new Ccsd_User_Models_User ();
                $userMapper = new Ccsd_User_Models_UserMapper ();

                $testPreviousPassword = $userMapper->findByUsernamePassword(Hal_Auth::getInstance()->getIdentity()->getUsername(), $form->getValue('PREVIOUS_PASSWORD'));

                if ($testPreviousPassword == null) {

                    $this->view->resultMessage = $this->view->message("Votre ancien mot de passe n'est pas correct.", 'danger');
                    $this->view->form = $form;
                    $this->render('changepassword');
                    return;
                }

                try {

                    $user->setUid(Hal_Auth::getUid());
                    $user->setPassword($form->getValue('PASSWORD'));
                    $user->setTime_modified();
                    $affectedRows = $userMapper->savePassword($user);

                    if ($affectedRows == 1) {
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Votre mot de passe a été changé.');
                        $this->redirect(PREFIX_URL . 'user');
                        return;
                    } else {
                        $this->view->resultMessage = $this->view->message("Échec de la modification. Votre mot de passe n'a pas été changé.", 'danger');
                        $this->render('changepassword');
                        return;
                    }
                } catch (Exception $e) {
                    $this->view->resultMessage = $this->view->message("Échec de la modification. Votre mot de passe n'a pas été changé.", 'danger');
                    $this->render('changepassword');
                    return;
                }
            }
        }

        $this->view->form = $form;
    }

    /**
     * Activation du compte suite au clic sur le lien dans l'email
     * @return void
     */
    public function activateAction() {
        if (Hal_Auth::isLogged()) {
            $this->_helper->redirector('index');
        }

        // Retour de l'activation OK
        if ($this->getRequest()->getParam('activation') == 'done') {
            $this->render('activate');
            return;
        }

        $token = $this->getRequest()->getParam('token');

        if (null == $token) {
            $this->view->errorMessage = "Erreur lors de l'activation du compte. Pas de jeton d'activation.";
            $this->render('activate');
            return;
        }

        try {
            $userTokens = new Ccsd_User_Models_UserTokens(array(
                'TOKEN' => $token
            ));
        } catch (Exception $e) {
            $this->view->errorMessage = "Erreur lors de l'activation du compte. Le jeton d'activation de ce compte n'est pas valable. ErrorCode UA00";
            $this->render('activate');
            return;
        }

        $userTokensMapper = new Ccsd_User_Models_UserTokensMapper ();
        $userMapper = new Ccsd_User_Models_UserMapper ();

        $tokenData = $userTokensMapper->findByToken($token, $userTokens);

        // le client essaie d'utiliser un jeton prévu pour autre chose que la
        // validation de compte
        if ('VALID' != $userTokens->getUsage()) {
            if ($uid = $this->getRequest()->getParam('uid', false)) {
                //L'uid est présetn dans l'URL, on vérifie si le compte est valide
                if ($userMapper->accountValidity($uid)) {
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Votre compte est désormais actif. Vous pouvez dès maintenant vous connecter.');
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Vous pouvez dès maintenant vous authentifier sur le service central d'authentification du CCSD.");
                    $this->redirect(PREFIX_URL . 'user/activate/activation/done');
                    return;
                }
            }
            $this->view->errorMessage = "Erreur lors de l'activation du compte. Le jeton d'activation de ce compte n'est pas valide";
            $this->render('activate');
            return;
        }

        // pas de jeton trouvé
        if (null == count($tokenData)) {
            $this->view->errorMessage = "Erreur lors de l'activation du compte. Le jeton d'activation de ce compte n'existe pas.";
            $this->render('activate');
            return;
        }

        try {
            $userMapper->activateAccountByUid($userTokens->getUid());
        } catch (Exception $e) {
            $this->view->errorMessage = $e->getMessage();
            $this->render('activate');
            return;
        }

        $userTokensMapper->delete($token, $userTokens);

        $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Votre compte est désormais actif. Vous pouvez dès maintenant vous connecter.');
        $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Vous pouvez dès maintenant vous authentifier sur le service central d'authentification du CCSD.");
        $this->redirect(PREFIX_URL . 'user/activate/activation/done');
        return;
    }

    /**
     * Procédure pour changer un mot de passe perdu
     */
    public function lostpasswordAction() {
        if (Hal_Auth::isLogged()) {
            $this->_helper->redirector('index');
        }
        $form = new Ccsd_User_Form_Accountlostpassword ();

        $form->setAction($this->view->url());
        $form->setActions(true)->createSubmitButton("Demander un nouveau mot de passe");

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {

                $userMapper = new Ccsd_User_Models_UserMapper ();

                $userInfo = $userMapper->findByUsername($form->getValue('USERNAME'));

                if ($userInfo ->count() === 0 ) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Désolé, nous n'avons pas trouvé de compte valide avec ce nom d'utilisateur.");
                    $this->view->form = $form;
                    $this->render('lostpassword');
                    return;
                }

                $user = new Ccsd_User_Models_User($userInfo->current()->toArray());

                $userTokenInfo = $userInfo->current()->toArray();
                $userTokenInfo ['USAGE'] = $form->getValue('USAGE');

                $userToken = new Ccsd_User_Models_UserTokens($userTokenInfo);
                $userToken->generateUserToken();

                $userTokenMapper = new Ccsd_User_Models_UserTokensMapper($userToken);
                $userTokenMapper->save($userToken);

                $this->view->userEmail = $user->getEmail();
                $this->view->fullUserName = $user->getFirstname() . ' ' . $user->getLastname();

                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les instructions de changement de mot de passe ont été envoyées à l'adresse de courriel associée à ce compte.");

                /**
                 * écriture email
                 */
                try {
                    $webSiteUrl = Zend_Registry::get('website')->getUrl();
                } catch (Exception $e) {
                    $webSiteUrl = 'https://' . $_SERVER ['SERVER_NAME'];
                }

                $url = $webSiteUrl . $this->view->url(array(
                            'controller' => 'user',
                            'action' => 'resetpassword',
                            'token' => $userToken->getToken()
                                ), null, true);

                $mail = new Hal_Mail ();
                $mail->prepare($user, Hal_Mail::TPL_ACCOUNT_LOST_PWD, array(
                    'TOKEN_VALIDATION_LINK' => $url
                ));
                $mail->writeMail();

                $this->render('lostpassword');
                return;
            }
        }

        $this->view->form = $form;
    }

    /**
     * Formulaire de renvoi de login si l'utilisateur l'a oublié
     */
    public function lostloginAction() {
        if (Hal_Auth::isLogged()) {
            $this->_helper->redirector('index');
        }

        $form = new Ccsd_User_Form_Accountlostlogin ();
        $form->setAction($this->view->url());
        $form->setActions(true)->createSubmitButton("Recevoir mon login");

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {

                $user = new Ccsd_User_Models_User($form->getValues());

                $userMapper = new Ccsd_User_Models_UserMapper ();

                $userLogins = $userMapper->findLoginByEmail($form->getValue('EMAIL'));

                // login non trouvé
                if (null === $userLogins) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Désolé, nous n'avons pas trouvé de compte avec cette adresse de courriel.");
                    $this->view->form = $form;
                    $this->render('lostlogin');
                    return;
                }

                // liste des logins trouvés + mention compte validé ou non
                $listeUserLogins = '';
                foreach ($userLogins as $login) {
                    $datecrea = date("Y-m-d", strtotime($login['TIME_REGISTERED']));
                    $listeUserLogins .= '- ' . $login ['USERNAME'] . '   (Créé le ' . $datecrea . ')';
                    if ($login ['VALID'] == 0) {
                        $listeUserLogins .= $this->view->translate(" (Vous n'avez pas validé ce compte)");
                    }
                    $listeUserLogins .= "\n";
                }

                $this->view->userEmail = $form->getValue('EMAIL');

                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Votre nom d'utilisateur vous a été envoyé par courriel.");

                /**
                 * écriture email
                 */
                $mail = new Hal_Mail ();
                $mail->prepare($user, Hal_Mail::TPL_ACCOUNT_LOST_LOGIN, array(
                    'MAIL_ACCOUNT_USERNAME_LIST' => $listeUserLogins
                ));
                $mail->writeMail();

                $this->render('lostlogin');
                return;
            }
        }

        $this->view->form = $form;
    }

    /**
     * Procédure de RAZ du mot de passe
     */
    public function resetpasswordAction() {
        if (Hal_Auth::isLogged()) {
            $this->_helper->redirector('index');
        }
        // Retour OK
        if ($this->getRequest()->getParam('reset') == 'done') {
            return;
        }
        $token = $this->getRequest()->getParam('token');
        try {
            $userTokens = new Ccsd_User_Models_UserTokens(array(
                'TOKEN' => $token
            ));
        } catch (Exception $e) {
            $this->view->resultMessage = $this->view->message("Erreur : le jeton n'est pas valide.", 'danger');
            return;
        }

        $userTokensMapper = new Ccsd_User_Models_UserTokensMapper ();

        $tokenData = $userTokensMapper->findByToken($token, $userTokens);

        // le client essaie d'utiliser un jeton prévu pour autre chose que les
        // mots de passe
        if (null === $tokenData) {
            $this->view->resultMessage = $this->view->message("Erreur : le jeton n'est pas valide.", 'danger');
            return;
        }

        $form = new Ccsd_User_Form_Accountresetpassword ();
        $form->setAction($this->view->url());
        $form->setActions(true)->createSubmitButton("Changer le mot de passe");

        $form->setDefault('token', $token);

        if ($this->getRequest()->isPost() && ($form->isValid($this->getRequest()->getPost()))) {

            $formToken = $form->getValue('token');
            $userTokens = new Ccsd_User_Models_UserTokens(array(
                'TOKEN' => $token
            ));
            $userTokensMapper = new Ccsd_User_Models_UserTokensMapper ();

            $tokenData = $userTokensMapper->findByToken($formToken, $userTokens);

            if (null != $tokenData) {
                $user = new Ccsd_User_Models_User ();
                $userMapper = new Ccsd_User_Models_UserMapper ();
                try {
                    $user->setUid($tokenData->getUid());
                    $user->setPassword($form->getValue('PASSWORD'));
                    $user->setTime_modified();
                    $user->setValid(1);
                    /** En cas de compte creer par un admin, le changement de mot de passe provoque un compte valid
                     * L'utilisateur a repondu au mail avec le token, c'est bon!
                     * Note: un compte invalide est dans ce cas un compte dont le mot de passe n'a pas encore
                     * ete re-initialise */
                } catch (Exception $e) {
                    // Todo: trapper proprement ceci!
                }

                $userMapper->savePassword($user);
                $userTokensMapper->delete($formToken, $userTokens);

                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Votre mot de passe a été changé.");
                $this->redirect(PREFIX_URL . 'user/resetpassword/reset/done');
                return;
            }
        }

        $this->view->form = $form;
    }

    /**
     * Liste des collections de l'utilisteur
     */
    public function collectionsAction() {
        $code = $this->getRequest()->getParam('tampid', false);
        if (!$code) {
            $sid = $this->getRequest()->getParam('sid', false);
            if ($sid) {
                $coll = Hal_Site::loadSiteFromId((int) $sid);
                $code = $coll->getSite();
            }
        }
        if ($code != '') {
            $userCollections = Hal_Auth::getTampon(false);
            if (array_key_exists(0, $userCollections) || in_array($code, $userCollections) || Hal_Auth::isHALAdministrator()) {
                $this->view->collection = Hal_Site::exist($code, Hal_Site::TYPE_COLLECTION, true);
                $this->render('collection');
                return;
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne disposez pas des droits pour gérer cette collection");
            }
        }
        if (Hal_Auth::isTamponneur() || Hal_Auth::isHALAdministrator()) {
            $sids = Hal_Auth::getUser()->getCollections('sid');

            if (Hal_Auth::isHALAdministrator() || (in_array(0, $sids)) || count($sids) > 50) {
                $q = $this->getRequest()->getParam('q', false);
                if ($q) {
                    $collections = array();
                    if (Hal_Auth::isHALAdministrator() || (in_array(0, $sids))) {
                        $search = Hal_Site::search($q, Hal_Site::TYPE_COLLECTION, 50);
                    } else {
                        $search = Hal_Site::search($q, Hal_Site::TYPE_COLLECTION, 50, $sids);
                    }

                    foreach ($search as $collection) {
                        $collections [] = Hal_Site::loadSiteFromId($collection ['SID']);
                    }
                    $this->view->collections = $collections;
                }
                $this->view->search = true;
            } else {
                $collections = array();
                foreach ($sids as $sid) {
                    $collections [] = Hal_Site::loadSiteFromId($sid);
                }
                $this->view->collections = $collections;
            }
        }
    }

    public function ajaxsearchcollectionAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        if (isset($_GET ['term'])) {
            $sids = Hal_Auth::getUser()->getCollections('sid');
            $sid = (!Hal_Auth::isHALAdministrator() && !in_array(0, $sids)) ? $sids : null;
            echo Zend_Json::encode(Hal_Site::autocomplete($_GET ['term'], Hal_Site::TYPE_COLLECTION, 50, $sid));
        }
    }

    public function documentsAction() {
        $this->renderScript('index/submenu.phtml');
    }

    /**
     * Dépôts de l'utilisateur
     */
    public function submissionsAction() {
        $params = $this->getRequest()->getParams();
        if ($this->getRequest()->isPost() && isset($params ['method'])) {
            if ($params ['method'] == Hal_Settings_Submissions::ACTION_DELETE) {
                // traitement par lots pour les suppressions
                if ((isset($params['docid']) || (isset($params['identifiant'])))) {
                    $suppression = [];
                    $inexistant = [];
                    $interdit = [];
                    if (isset($params['docid'])) {
                        if (!is_array($params['docid'])) {
                            $params['docid'] = array($params['docid']);
                        }
                        foreach ($params['docid'] as $docid) {
                            if ($docid == 0)
                                continue;
                            $document = Hal_Document::find($docid);
                            if ($document === false) {
                                $inexistant[] = $docid;
                            } else if (Hal_Document_Acl::canDelete($document)) {
                                $document->delete(Hal_Auth::getUid(), '', false);
                                $suppression[] = $docid;
                            } else {
                                $interdit[] = $docid;
                            }
                        }
                    }
                    if (isset($params['identifiant']) && $params['identifiant'] != '') {
                        $document = Hal_Document::find(0, $params['identifiant']);
                        if ($document === false) {
                            $inexistant[] = $params['identifiant'];
                        } else if (Hal_Document_Acl::canDelete($document)) {
                            $document->delete(Hal_Auth::getUid(), '', false);
                            $suppression[] = $params['identifiant'];
                        } else {
                            $interdit[] = $params['identifiant'];
                        }
                    }
                    $this->view->suppression = $suppression;
                    $this->view->interdit = $interdit;
                    $this->view->inexistant = $inexistant;
                }
            } else {
                $document = Hal_Document::find(Ccsd_Tools::ifsetor($params ['docid'], 0), Ccsd_Tools::ifsetor($params ['identifiant'], ''), Ccsd_Tools::ifsetor($params ['version'], 0));
                if ($document instanceof Hal_Document) {
                    switch ($params ['method']) {
                        // Consultation d'un document
                        case Hal_Settings_Submissions::ACTION_SEE :
                            if ($document->isVisible()) {
                                $this->redirect('/' . $document->getId() . 'v' . $document->getVersion());
                                return;
                            }
                            // Si non visible, le numero de version peut etre en double
                            // On ne peut pas passer par (identifiant, version) on passe donc par (docid)
                            $this->redirect('/view/index/docid/' . $params ['docid']);
                            return;

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
                                $this->redirect('/user/reply/docid/' . $document->getDocid());
                                return;
                            }
                            break;

                        // Modification des métadonnées
                        case Hal_Settings_Submissions::ACTION_METADATA :
                            if (Hal_Document_Acl::canUpdate($document, Ccsd_Tools::ifsetor($params ['pwd'], ''))) {
                                $data = array(
                                    'docid' => $document->getDocid()
                                );
                                if (isset($params ['pwd'])) {
                                    $data ['pwd'] = $params ['pwd'];
                                }
                                $this->forward('update', 'submit', null, $data);
                                return;
                            } else {
                                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne pouvez pas modifier le document");
                            }
                            break;

                        // Nouvelle version
                        case Hal_Settings_Submissions::ACTION_VERSION :
                            if (Hal_Document_Acl::canReplace($document, Ccsd_Tools::ifsetor($params ['pwd'], ''))) {
                                $data = array(
                                    'docid' => $document->getDocid()
                                );
                                if (isset($params ['pwd'])) {
                                    $data ['pwd'] = $params ['pwd'];
                                }
                                $this->forward('replace', 'submit', null, $data);
                                return;
                            } else {
                                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne pouvez pas déposer de nouvelle version");
                            }
                            break;

                        // Ajout d'un fichier
                        case Hal_Settings_Submissions::ACTION_ADDFILE :
                        case Hal_Settings_Submissions::ACTION_FILE :
                            if (Hal_Document_Acl::canUpdate($document, Ccsd_Tools::ifsetor($params ['pwd'], ''))) {
                                $data = array(
                                    'docid' => $document->getDocid()
                                );
                                if (isset($params ['pwd'])) {
                                    $data ['pwd'] = $params ['pwd'];
                                }
                                $this->forward('addfile', 'submit', null, $data);
                                return;
                            } else {
                                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne pouvez pas ajouter de fichier");
                            }
                            break;

                        // Lier la ressource
                        case Hal_Settings_Submissions::ACTION_RELATED :
                            if (Hal_Document_Acl::canUpdate($document, Ccsd_Tools::ifsetor($params ['pwd'], ''))) {
                                $this->redirect('/user/related/docid/' . $document->getDocid());
                                return;
                            }
                            break;

                        case Hal_Settings_Submissions::ACTION_TRANSFERT :
                            // Transfert de propriété
                            if (Hal_Document_Acl::canUpdate($document, Ccsd_Tools::ifsetor($params ['pwd'], ''))) {
                                $this->redirect('/user/docowner/docid/' . $document->getDocid());
                                return;
                            } else {
                                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne pouvez pas partager la propriété de ce document");
                            }
                            break;

                        case Hal_Settings_Submissions::ACTION_UNSHARE :
                            // Transfert de propriété
                            if (Hal_Document_Acl::canUnshare($document)) {
                                $doc_owner = new Hal_Document_Owner();
                                $doc_owner->removeOwnership($document, Hal_Auth::getUid());
                                $authid = Hal_Document_Author::findAuthidFromDocid($document->getDocid(), Hal_Auth::getUid());
                                if (!$authid) {
                                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Votre compte utilisateur n'a pas été trouvé.");
                                    break;
                                }
                                Hal_Document_Author::replaceWithNew($authid, [$document->getDocid()]);
                                $this->redirect('/user/submissions');
                                return;
                            } else {
                                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne pouvez pas refuser la propriété de ce document");
                            }
                            break;

                        case Hal_Settings_Submissions::ACTION_COPY :
                            // Utiliser comme modèle
                            $this->redirect('/submit/copy/docid/' . $document->getDocid());
                            return;
                    }
                } else {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Le document demandé n'existe pas !");
                }
            }
        }
        $this->view->settings = Hal_Settings_Submissions::getSettings();
        $this->view->documents = Hal_Auth::getInstance()->getIdentity()->getDocuments();
    }

    public function ajaxsubmissionsAction() {
        $this->_helper->layout()->disableLayout();

        $params = $this->getRequest()->getPost();
        if (isset($params ['docids'])) {
            if (!is_array($params['docids'])) {
                $params['docids'] = array($params['docids']);
            }

            $docs = array();
            foreach ($params ['docids'] as $docid) {
                $document = Hal_Document::find($docid);
                if ($document instanceof Hal_Document) {
                    $docs [] = array(
                        'docid' => $docid,
                        'identifiant' => $document->getId(),
                        'version' => $document->getVersion(),
                        'ref' => $document->getCitation('full'),
                        'date' => $document->getSubmittedDate()
                    );
                }
            }
            $this->view->documents = $docs;
            $this->view->group = Ccsd_Tools::ifsetor($params ['group'], '');
        }
    }

    public function ajaxdocownerAction() {
        $this->_helper->layout()->disableLayout();

        $params = $this->getRequest()->getPost();
        if (isset($params ['docids'])) {
            if (!is_array($params['docids'])) {
                $params['docids'] = array($params['docids']);
            }

            $docs = array();
            foreach ($params['docids'] as $docid) {
                $document = Hal_Document::find($docid);
                if ($document instanceof Hal_Document) {
                    $docs [] = array(
                        'docid' => $docid,
                        'identifiant' => $document->getId(),
                        'version' => $document->getVersion(),
                        'ref' => $document->getCitation('full'),
                        'date' => $document->getSubmittedDate()
                    );
                }
            }
            $this->view->userDocids = $docs;
        }
    }

    public function replyAction() {
        $form = new Ccsd_Form ();
        $form->setAttrib('class', 'form-horizontal');
        $form->addElement('textarea', 'reply', array(
            'label' => 'Réponse',
            'required' => true,
            'rows' => 4
        ));
        $form->setActions(true)->createCancelButton($this->view->translate('Annuler'), array(
            'onclick' => 'link("/user/submissions");',
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
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Votre réponse a été envoyée aux modérateurs");
            } else {
                // Erreur
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Erreur lors de l'envoi de la réponse");
            }
            $this->redirect('/user/submissions');
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
                $this->redirect('/user/submissions');
                return;
            }
        }
    }

    public function relatedAction() {
        $params = $this->getRequest()->getParams();
        if (isset($params ['docid'])) {
            $document = Hal_Document::find((int) $params ['docid']);
            if ($document instanceof Hal_Document && Hal_Document_Acl::canUpdate($document)) {
                if ($this->getRequest()->isPost()) {
                    if (isset($params ['related'] ['IDENTIFIANT']) && is_array($params ['related'] ['IDENTIFIANT'])) {
                        $related = array();
                        foreach ($params ['related'] ['IDENTIFIANT'] as $i => $identifiant) {
                            if (trim($identifiant) == '')
                                continue;
                            $related [] = array(
                                'IDENTIFIANT' => $identifiant,
                                'RELATION' => $params ['related'] ['RELATION'] [$i],
                                'INFO' => $params ['related'] ['INFO'] [$i]
                            );
                        }
                        $document->setRelated($related);
                        $document->saveRelated(true, Hal_Auth::getUid());
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Modifications enregistrées !");
                    }
                }
                $this->view->docid = $document->getDocid();
                $this->view->reference = $document->getCitation('full');
                $this->view->related = $document->getRelated();
                $this->view->dcRelation = Hal_Settings::getDcRelation();
            } else {
                // Erreur
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Le document sélectionné n'existe pas ou ne pouvez pas le modifier !");
                $this->redirect('/user/submissions');
                return;
            }
        }
    }

    /**
     * Recherche  pour les relation entre documents
     */
    public function ajaxsearchdocAction()
    {
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
                    'label' =>  html_entity_decode(strip_tags($doc['citationFull_s']))
                );
            }
            echo Zend_Json::encode($res);
        }
        $this->noRender();
    }


    /**
     * Gestion des propriétés des documents
     */
    public function docownerAction() {
        $docOwner = new Hal_Document_Owner ();
        $docid = 0;
        $render = 0;
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getPost();
            if (isset($params['share'])){
                $mail = Hal_Mail::TPL_DOC_CLAIMOWNERSHIP_DIRECT;
            } else {
                $mail = null;
            }

            $message = $this->getRequest()->getParam("message", "");
            // Sécurité : on échappe le html qui serait mis dans le message
            $message = htmlspecialchars($message);

            // Acceptation ou refus d'une demande de propriété
            if (isset($params['accept'])) {
                if (!empty($params['uid']) && (isset($params['docid']) || (isset($params['identifiant'])))) {
                    $partage = [];
                    $inexistant = [];
                    $interdit = [];
                    $echec = [];
                    if (isset($params['docid'])) {
                        if (!is_array($params['docid'])) {
                            $params['docid'] = array($params['docid']);
                        }
                        foreach ($params['docid'] as $docid) {
                            if ($docid == 0) {
                                continue;
                            }
                            $document = Hal_Document::find($docid);
                            if ($document === false) {
                                $inexistant[] = $docid;
                            } else if (Hal_Document_Acl::canUpdate($document)) {
                                if (($params['accept'] == '1') || ($params['accept'] == 'accept')) {
                                    if ($docOwner->acceptClaimOwnership($document, $params ['uid'], $mail)) {
                                        $partage[] = $document->getId() . 'v' . $document->getVersion();
                                    } else {
                                        $echec[] = $document->getId() . 'v' . $document->getVersion();
                                    }
                                } else {
                                    $docOwner->refusedClaimOwnership($document, $params ['uid']);
                                }
                            } else {
                                $interdit[] = $document->getId() . 'v' . $document->getVersion();
                            }
                        }
                    }
                    if (isset($params['identifiant']) && $params['identifiant'] != '') {
                        $document = Hal_Document::find(0, $params['identifiant']);
                        if ($document === false) {
                            $inexistant[] = $params['identifiant'];
                        } else if (Hal_Document_Acl::canUpdate($document)) {
                            $docid = $document->getDocid();
                            if ($params['accept'] == 'accept') {
                                if (Hal_Document_Acl::canUpdate($document)) {
                                    if ($docOwner->acceptClaimOwnership($document, $params['uid'], $mail)) {
                                        $partage[] = $params['identifiant'];
                                    } else {
                                        $echec[] = $params['identifiant'];
                                    }
                                } else {
                                    $interdit[] = $params['identifiant'];
                                }
                            } else {
                                $docOwner->refusedClaimOwnership($document, $params ['uid']);
                            }
                        } else {
                            $interdit[] = $params['identifiant'];
                        }
                    }
                    $this->view->partage = $partage;
                    $this->view->echec = $echec;
                    $this->view->interdit = $interdit;
                    $this->view->inexistant = $inexistant;
                } else {
                    if (isset($params['docid'])){
                        $ownershipState = null;
                        foreach ($params['docid'] as $docid){
                            $ownershipState[$docid] = 'checked';
                        }
                        $this->view->ownershipState = $ownershipState;
                    }
                    $this->view->erreur = $this->view->translate("Paramètres incomplets pour le partage de propriété");
                }
            }

            if (isset($params ['method']) && $params ['method'] == 'request' && isset($params ['identifiant'])) {

                // Envoi d'une demande de propriété
                $document = Hal_Document::find(0, Ccsd_Tools::ifsetor($params ['identifiant'], ''));
                if ($document === false) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Le document n'existe pas !");
                    $res = 4;
                } else if ($message === '') {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Impossible de demander la propriété sans message à son propriétaire.");
                    $res = 4;
                } else if ($document->getContributor('uid') == 131274) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Impossible de demander la propriété des documents déposés par STAR");
                    $res = 2;
                } else if (Hal_Document_Acl::isContributor($document) || Hal_Document_Acl::isOwner($document)) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous êtes déjà propriétaire du document !");
                    $res = 3;
                } else if ($docOwner->hasRequestedOwnership(Hal_Auth::getUid(), $document->getId())) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("La propriété du document a déjà été demandée !");
                    $res = 5;
                } else if ($docOwner->addClaimOwnership($document, Hal_Auth::getUid(), $message)) {
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("La demande de partage a été envoyée au contributeur et à tous les propriétaires par e-mail.");
                    $res = 1;
                } else {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Problème technique, merci de renouveler la demande");
                    $res = 0;
                }
                if ((!isset($params ['origin'])) || ($params ['origin'] != 'form')) {
                    echo Zend_Json::encode(array('res' => $res));
                    exit();
                }
            } else if (isset($params ['method']) && $params ['method'] == 'request' && isset($params ['docid'])) {
                if (!is_array($params['docid'])) {
                    $params['docid'] = array($params['docid']);
                }

                $inexistant = $nomsg = $star = $proprio = $dde = $envoi = 0;
                foreach ($params ['docid'] as $docid) {
                    $document = Hal_Document::find($docid);
                    if ($document === false) {
                        $inexistant = 1;
                    } else if ($document->getContributor('uid') == 131274) {
                        $star = 1;
                    } else if (Hal_Document_Acl::isContributor($document) || Hal_Document_Acl::isOwner($document)) {
                        $proprio = 1;
                    } else if ($docOwner->hasRequestedOwnership(Hal_Auth::getUid(), $document->getId())) {
                        $dde = 1;
                    } else if($message == '') {
                        $nomsg = 1;
                    } else if ($docOwner->addClaimOwnership($document, Hal_Auth::getUid(), $message)) {
                        $envoi = 1;
                    }
                }
                if ($envoi) {
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("La demande a été envoyée !");
                    $res = 1;
                } else if ($star) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Impossible de demander la propriété des documents déposés par STAR");
                    $res = 2;
                } else if ($inexistant) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Le document n'existe pas !");
                    $res = 4;
                } else if ($nomsg) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Impossible de demander la propriété sans message à son propriétaire.");
                    $res = 4;
                } else if ($proprio) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous êtes déjà propriétaire du document !");
                    $res = 3;
                } else if ($dde) {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("La propriété du document a déjà été demandée !");
                    $res = 5;
                } else {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Problème technique, merci de renouveler la demande");
                    $res = 0;
                }
                if ((!isset($params ['origin'])) || ($params ['origin'] != 'form')) {
                    echo Zend_Json::encode(array('res' => $res));
                    exit();
                }
            } else if (isset($params ['method']) && $params ['method'] == 'share' && isset($params ['docid'])) {
                // ajout boucle sur les documents cochés
                if (preg_match('/,/', $params['docid'][0])) {
                    $params['docid'] = explode(",", $params['docid'][0], 'array');
                }
                if (!is_array($params['docid'])) {
                    $params['docid'] = array($params['docid']);
                }
                $ownershipState = null;
                foreach ($params['docid'] as $docid) {
                    $document = Hal_Document::find($docid);
                    if ($document === false) {
                        $ownershipState[$docid] = 'inexistant';
                    } else if (Hal_Document_Acl::canUpdate($document)) {
                        $ownershipState[$docid] = 'checked';
                    } else {
                        $ownershipState[$docid] = 'impossible';
                    }
                }
                $this->view->ownershipState = $ownershipState;
            }

            $docid = 0;
        } else {
            $docid = $this->getRequest()->getParam('docid', false);
            $ownershipState[$docid] = 'checked';
            $this->view->ownershipState = $ownershipState;
        }
        $this->view->ownershipClaim = $docOwner->getClaimOwnership(Hal_Auth::getUid());

        if ($docid) {
            $this->view->docid = $docid;
        } else {
            $this->view->userDocids = Hal_Auth::getInstance()->getIdentity()->getDocuments(true, false, true);
        }
    }

    public function libraryAction() {
        $myLibrary = new Hal_User_Library(array(
            'uid' => Hal_Auth::getUid()
        ));
        $myLibrary->getDocs();
        $this->view->myLibrary = $myLibrary->documents;
    }

    public function ajaxeditshelfAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $params = $request->getParams();

        if ($request->isXmlHttpRequest() && isset($params ['pk']) && isset($params ['value']) && $params ['value'] != '') {
            echo Hal_User_Library::editShelfName((int) $params ['pk'], htmlspecialchars($params ['value']));
        }
    }

    public function ajaxaddshelfAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $params = $request->getParams();

        if ($request->isXmlHttpRequest() && isset($params ['name']) && $params ['name'] != '') {
            $myLibrary = new Hal_User_Library(array(
                'uid' => Hal_Auth::getUid()
            ));
            $options = array(
                'shelfName' => $params ['name']
            );

            echo $myLibrary->addShelf($options, false);
        }
    }

    /**
     *
     */
    public function ajaxdeletelibraryelementAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $params = $request->getParams();

        if ($request->isXmlHttpRequest()) {
            if (isset($params ['id'])) {
                $tmp = explode('-', $params ['id']);

                if (count($tmp) == 1) { // suppression de l'étagère complète
                    $libShelfId = $tmp [0];
                    $result = Hal_User_Library::delShelf($libShelfId);
                    if ($result) {
                        echo $params ['id'];
                    } else {
                        echo "deleteShelfProblem";
                    }
                } else { // suppression d'un doc dans une étagère
                    $libDocId = $tmp [1];
                    $result = Hal_User_Library::delDocument($libDocId);
                    if ($result) {
                        echo Hal_User_Library::count($tmp [0]);
                    } else {
                        echo "deleteDocumentProblem";
                    }
                }
            } else {
                echo "noId";
            }
        } else {
            echo "noAjax";
        }
    }

    /**
     * Suppression du cache
     */
    public function ajaxdeletecacheAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();
            $cv = new Hal_Cv($params['idhal'], '', Hal_Auth::getUid());

            $cv->delete_cache();
            echo "true";
            exit;
        }
        echo "false";
    }

    /**
     * Création de l'IdHAL de l'utilisateur
     */
    public function idhalAction() {
        $cv = new Hal_Cv(0, '', Hal_Auth::getUid());

        $forceFusion = true;
        if ($this->getRequest()->isPost()) {
            if ($this->getRequest()->getParam('authorid', null)) {
                // On enregistre l'IdHAL en forcant la suppression de doublon car l'utilisateur doit pouvoir le faire depuis son espace.
                // TODO: controler le retour de saveIdHAL
                $cv->saveIdHAL($this->getRequest()->getPost(), $forceFusion);
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage($this->view->translate("L'IdHAL a été enregistré. Cette modification sera traitée dans les plus bref délais."));
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage($this->view->translate("Aucune forme auteur n'a été séléctionnée"));
            }
        }
        $cv->load(false);
        $this->view->cv = $cv;
        if (!$cv->exist()) { // Récupération des formes auteur du compte
            // utilisateur
            $this->view->q = Hal_Auth::getUser()->getFullname();
            $this->view->fullname = Ccsd_Tools::formatAuthor(Hal_Auth::getUser()->getFirstname(), Hal_Auth::getUser()->getLastname());
        }
    }

    /**
     * Demande au déposant d'accéder au pdf principal sous embargo
     */
    public function ajaxfileaccessAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        if ($this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()) {
            $params = $this->getRequest()->getParams();
            $document = Hal_Document::find(Ccsd_Tools::ifsetor($params ['docid'], 0));
            if (Hal_Auth::isLogged() && $document instanceof Hal_Document) {
                $request = new Hal_Document_Filerequest();
                if ($request->addRequest($document, Hal_Auth::getUid())) {
                    echo "true";
                    exit;
                }
            }
        }
        echo "false";
    }

    /**
     *
     */
    public function filerequestAction() {
        $requestFile = new Hal_Document_Filerequest();
        if ($this->getRequest()->isGet() || $this->getRequest()->isPost() && $this->getRequest()->isXmlHttpRequest()) {
            $params = ( $this->getRequest()->isPost() ) ? $this->getRequest()->getPost() : $this->getRequest()->getParams();
            if (isset($params['docid']) && isset($params['uid']) && isset($params['accept'])) {
                $document = Hal_Document::find($params['docid']);
                if ($document instanceof Hal_Document && $document->isOwner(Hal_Auth::getUid())) {
                    if ($params['accept']) {
                        $res = $requestFile->acceptRequest($document, (int) $params['uid']);
                    } else {
                        $res = $requestFile->refusedRequest($document, (int) $params['uid']);
                    }
                    if ($this->getRequest()->isPost()) {
                        $this->_helper->layout()->disableLayout();
                        $this->_helper->viewRenderer->setNoRender();
                        if ($res) {
                            echo 'true';
                        }
                        exit;
                    }
                    if ($res) {
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Accessibilité du fichier mise à jour");
                    }
                }
            }
            if ($this->getRequest()->isPost()) {
                $this->_helper->layout()->disableLayout();
                $this->_helper->viewRenderer->setNoRender();
                echo 'false';
                exit;
            }
        }
        $this->view->docidsIHaveRequested = $requestFile->getRequest(Hal_Auth::getUid());
        $this->view->docidsRequestToMe = $requestFile->getDocidsWhereRequest(Hal_Auth::getUid());
        $this->view->docidsHistory = $requestFile->getRequestHistory(Hal_Auth::getUid());
    }

    /**
     *
     */
    public function ajaxgetdocrequestAction() {
        $this->_helper->layout()->disableLayout();

        if ($this->getRequest()->isXmlHttpRequest()) {
            $docOwner = new Hal_Document_Owner ();
            $documents = array();
            foreach ($docOwner->getRequestOwnership(Hal_Auth::getUid()) as $tabres) {
                $document = Hal_Document::find(0, $tabres['IDENTIFIANT']);
                $documents[] = array($tabres['DATECRE'], $document->getCitation('full'));
            }
            $this->view->documents = $documents;
        }
    }

    public function statAction() {

    }

    /**
     * Recherche de formes auteurs
     */
    public function ajaxidhalsearchAction() {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getParams();
        $query = 'q=' . urlencode($this->getRequest()->getParam('q'));
        if (isset($params ['authorids'])) {
            $query .= '+AND+NOT+(docid:(' . implode('+OR+', $params ['authorids']) . '))';
        }
        $query .= '+AND+(idHal_i:0)';
        try {
            $res = Hal_Tools::solrCurl($query . '&start=0&rows=100&wt=phps&indent=false&omitHeader=true', 'ref_author');
            $res = unserialize($res);
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
        }

        if (isset($res ['response'] ['docs']))
            $this->view->authors = $res ['response'] ['docs'];
        $this->render('idhal-search');
    }

    /**
     * Récupération des documents associés à un idhal
     */
    public function ajaxidhaldocsAction() {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getParams();

        if (isset($params ['authorid'])) {
            try {
                $res = Ccsd_Tools::solrCurl('q=*&fq=authId_i:' . $params ['authorid'] . '&fl=citationFull_s,docid&start=0&rows=1000&wt=phps&indent=false&omitHeader=true');
                $res = unserialize($res);
            } catch (Exception $exc) {
                error_log($exc->getMessage(), 0);
            }

            if (isset($res ['response'] ['docs'])) {
                $this->view->authorid = $params ['authorid'];
                $this->view->docs = $res ['response'] ['docs'];
            }
        }
        $this->render('idhal-docs');
    }

    /**
     * Vérifie la disponibilité d'un identifiant chercheur
     */
    public function ajaxexisturiAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->getRequest()->getParams();
        if (isset($params ['uri'])) {
            echo Hal_Cv::existUri($params ['uri']);
        }
        echo false;
    }

    /**
     * Gestion du CV de l'utilisateur
     */
    public function cvAction() {
        Zend_Session::regenerateId();

        $cv = new Hal_Cv(0, '', Hal_Auth::getUid());
        $form = $cv->getFormCV();
        if ($this->getRequest()->isPost()) {
            $formdata = $this->getRequest()->getPost();
            if ($form->isValid($formdata)) {
                $cv->saveCV($formdata);
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Le CV a été enregistré");
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Remplissez les champs obligatoires");
            }
        }
        $this->view->form = $form;
        $cv->load(false);
        $this->view->cv = $cv;
    }

    public function spaceAction() {
        $this->renderScript('index/submenu.phtml');
    }

    public function ftpAction() {
        $files = [];
        try {
            $files = Ccsd_User_Models_UserMapper::getUserHomeFtpFiles(Hal_Auth::getUid());
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        $this->view->fileList = $files;
    }

    /**
     *
     */
    public function searchAction() {
        $request = $this->getRequest();
        $params = $request->getParams();

        $this->view->search_url = PREFIX_URL . $params ['action'];
        $this->view->my_search = new Hal_User_Search(array(
            "uid" => Hal_Auth::getUid()
        ));
    }

    /**
     *
     */
    public function ajaxdelsearchAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $request->getPost();
            if (isset($params ['searchid']) && $params ['searchid']) {
                $user_search = new Hal_User_Search(array(
                    'searchid' => $params ['searchid'],
                    'uid' => Hal_Auth::getUid()
                ));
                if ($user_search->delete()) {
                    echo 'ok';
                    return;
                }
            }
        }
        echo 'ko';
        return;
    }

    /**
     *
     */
    public function ajaxupdatefreqAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $request->getPost();
            if (isset($params ['searchid']) && $params ['searchid']) {
                $user_search = new Hal_User_Search(array(
                    'searchid' => $params ['searchid'],
                    'freq' => $params ['freq'],
                    'uid' => Hal_Auth::getUid()
                ));
                if ($user_search->updateFreq()) {
                    echo 'ok';
                    return;
                }
            }
        }
        echo 'ko';
        return;
    }

    /**
     * Ajout document dans ma bibliothèque
     */
    public function addinlibraryAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->getRequest()->getParams();

        if (isset($params ['shelfid'])) {
            $myLibrary = new Hal_User_Library(array(
                'uid' => Hal_Auth::getUid()
            ));

            if ($params ['shelfid'] == 0 && isset($params ['name'])) {
                $params ['shelfid'] = $myLibrary->addShelf(array(
                    'shelfName' => $params ['name']
                ));
            }
            if ($params ['shelfid']) {
                if (isset($params ['identifiant'])) {
                    $nbDocAdded = $myLibrary->addDocument(array(
                        'docIdentifiant' => $params ['identifiant'],
                        'shelfId' => $params ['shelfid']
                    ));
                    echo Zend_Json::encode(array(
                        'shelfid' => $params ['shelfid'],
                        'doc' => $nbDocAdded
                    ));
                    exit();
                } else {
                    if (isset($params ['docid'])) {
                        if (!is_array($params ['docid'])) {
                            $params ['docid'] = array(
                                $params ['docid']
                            );
                        }
                    } else if (isset($params ['query'])) {
                        try {
                            $result = unserialize(Ccsd_Tools::solrCurl($params ['query'], 'hal', 'apiselect'));
                        } catch (Exception $exc) {
                            error_log($exc->getMessage(), 0);
                        }
                        $params ['docid'] = array();
                        if (isset($result ['response'] ['docs']) && is_array($result ['response'] ['docs']) && count($result ['response'] ['docs'])) {
                            foreach ($result ['response'] ['docs'] as $docid) {
                                $params ['docid'] [] = $docid ['docid'];
                            }
                        }
                    } else {
                        $params ['docid'] = array();
                    }
                    $res = 0;
                    foreach ($params ['docid'] as $docid) {
                        $document = Hal_Document::find($docid);
                        $res += $myLibrary->addDocument(array(
                            'docIdentifiant' => $document->getId(),
                            'shelfId' => $params ['shelfid']
                        ));
                    }
                    echo Zend_Json::encode(array(
                        'shelfid' => $params ['shelfid'],
                        'doc' => $res
                    ));
                    exit();
                }
            }
        }
    }

    /**
     * Suppression d'un document
     */
    public function deletedocumentAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $docid = $this->getRequest()->getParam('docid', 0);

        // On empêche la suppression du fichier lorsqu'il est en cours de modération
        if (Lock::isLocked($docid, Hal_Auth::getUid(), Hal_Moderation::MODERATION_ACTION)) {
            $this->getResponse()->setHttpResponseCode(500);
            echo 'Attention, ce document est en cours de modification par un autre utilisateur. Vous ne pouvez pas supprimer votre dépôt car il est en train d\'être modéré.';
            return;
        }

        $document = Hal_Document::find($docid);
        if ($document instanceof Hal_Document && Hal_Document_Acl::canDelete($document)) {
            echo $document->delete(Hal_Auth::getUid(), '', false);
        }
    }

    /**
     * Vérifie la conformité de l'url
     */
    public function orcidAction() {
        $url = $this->getRequest()->getParam('url');
        $code = $this->getRequest()->getParam('code');

        $localuri = $this->getRequest()->getHttpHost(); //Portail Local
        $urlp = parse_url($url, PHP_URL_HOST); //Hostname du Portail de redirection
        $urls = parse_url($url, PHP_URL_SCHEME); //Protocol du Portail de redirection

        if ($localuri == $urlp){

            if (Hal_Site_Portail::existFromUrl($urlp) == false){
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Cette URL n'est pas valide.");
                $this->redirect(PREFIX_URL);
            }

            $this->redirect($this->getRequest()->getScheme(). '://'. $urlp. '/user/idhal?code='. $code);
        } else {
            $this->redirect($urls. '://'. $urlp. '/user/orcid?url='.$url.'&code='. $code);
        }
    }

    /**
     *
     */
    public function removeownershipAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();


        $token = $this->getRequest()->getParam('token');

        if (null == $token) {
            $this->view->errorMessage = "Erreur lors de l'activation du compte. Pas de jeton d'activation.";
            $this->redirect($this->view->url(array('controller' => 'index'), null, true));
            return;
        }

        try {
            $ownershipToken = new Hal_Document_Tokens_Ownership(array(
                'TOKEN' => $token
            ));
        } catch (Exception $e) {
            $this->_helper->FlashMessenger->setNamespace('error')->addMessage("Le jeton d'activation n'est pas valide.");
            $this->redirect($this->view->url(array('controller' => 'index'), null, true));
            return;
        }

        $ownershipMapper = new Hal_Document_Tokens_OwnershipMapper ();

        $ownershipToken = $ownershipMapper->findByToken($token, $ownershipToken);

        if ('UNSHARE' != $ownershipToken->getUsage()) {
            $this->_helper->FlashMessenger->setNamespace('error')->addMessage("Le jeton d'activation n'est pas valide.");
            $this->redirect($this->view->url(array('controller' => 'index'), null, true));
            return;
        }

        $uid = $this->getRequest()->getParam('uid', false);
        $docid = $this->getRequest()->getParam('docid', false);

        if ($uid != $ownershipToken->getUid() || $docid != $ownershipToken->getDocid()) {
            $this->_helper->FlashMessenger->setNamespace('error')->addMessage("Le jeton d'activation n'est pas valide.");
            $this->redirect($this->view->url(array('controller' => 'index'), null, true));
            return;
        }

        try {
            $document = Hal_Document::find($docid);

            if (!$document) {
                $this->_helper->FlashMessenger->setNamespace('error')->addMessage('Impossible de retirer la propriété : le document n\'a pas été trouvé .');
                $this->redirect($this->view->url(array('controller' => 'index'), null, true));
                return;
            }

            $doc_owner = new Hal_Document_Owner();
            $doc_owner->removeOwnership($document, $uid);
        } catch (Exception $e) {
            $this->_helper->FlashMessenger->setNamespace('error')->addMessage($e->getMessage());
            $this->redirect($this->view->url(array('controller' => 'index'), null, true));
            return;
        }

        $ownershipMapper->delete($token, $ownershipToken);

        // Création d'une nouvelle forme auteur pour que la personne ne soit plus l'auteur du document !
        $authid = Hal_Document_Author::findAuthidFromDocid($docid, $uid);

        if (!$authid) {
            $this->_helper->FlashMessenger->setNamespace('error')->addMessage("Le compte de l'utilisateur n'a pas été trouvé.");
            $this->redirect($this->view->url(array('controller' => 'index'), null, true));
            return;
        }

        Hal_Document_Author::replaceWithNew($authid, [$docid]);


        $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Merci. Vous ne partagez plus la propriété de ce document");
        $this->redirect($this->view->url(array('controller' => 'index'), null, true));
        return;
    }

    public function acceptownershipAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        // todo : remove token "removeownership" ??

        $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Merci d'avoir validé votre propriété.");
        $this->redirect($this->view->url(array(
            'controller' => '',
            'action' => $this->getParam('id', '')
        ), null, true));
        return;
    }
}
