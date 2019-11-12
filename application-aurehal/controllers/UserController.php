<?php

class UserController extends Zend_Controller_Action
{

    public function init()
    {
        $action = $this->getRequest()->getActionName();
        if (! in_array($action, array('login', 'logout'))) {
            return $this->redirect(HALURL . '/user/' . $action);
        }
    }


    /**
     * Login utilisateur
     * Après login redirige :
     * - sur la page de modification de compte si pas de champs Application HAL
     * - sur la page de destination envoyé en paramètre à CAS
     * - sur le compte utilisateur si pas de page de destination envoyé en
     * paramètre à CAS
     */
    public function loginAction ()
    {
        $params = $this->_request->getParams();
        $halUser = new Hal_User();
        // Todo: Duplicate de UserController de HAL... A factoriser
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
                $authAdapter->setServiceURL($this->_request->getParams());
                $authAdapter->setIdentityStructure($halUser);
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

        switch ( $result->getCode() ) {
            case Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID:
            case Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND:
            case Zend_Auth_Result::FAILURE:
                // on ne devrait jamais arriver là : c'est géré par CAS
                // Auth Dbtable: on peut y venir.
                $this->view->message = "Erreur d'authentification";
                $this->view->description = "L'authentification a échoué";
                $this->renderScript('error/error.phtml');
                break;

            case Zend_Auth_Result::SUCCESS:
                //Je reviens de l'authentification
                Hal_Auth::getInstance()->getIdentity()->load();
                $halUser->setUid(Hal_Auth::getUid());
                $halUser->find(Hal_Auth::getUid());

                $params = $this->_request->getParams();
                $params ['controller'] = $params['forward-controller'];
                $params ['action'] = $params['forward-action'];
                unset($params ['forward-action']);
                unset($params ['forward-controller']);
                $redirectUrl = $this->view->url($params, null, true);

                $this->redirect($redirectUrl);
        }
    }
    
    public function logoutAction ()
    {
        $casAuthAdapter = new Ccsd_Auth_Adapter_Cas();
        $casAuthAdapter->logout(URL."/index");
    }

}

