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
        $casAuthAdapter = new Ccsd_Auth_Adapter_Cas();
        $halUser = new Hal_User();
        $casAuthAdapter->setIdentityStructure($halUser);
        $auth_result = Hal_Auth::getInstance()->authenticate($casAuthAdapter);
        $session = new Zend_Session_Namespace(SESSION_NAMESPACE);
        $pageDestination = "/" . ( isset($session->idhal) ? $session->idhal : '' );

        switch ( $auth_result->getCode() ) {
            case Zend_Auth_Result::FAILURE:
                // on ne devrait jamais arriver là : c'est géré par CAS
                $this->view->message = "Erreur d'authentification";
                $this->view->description = "L'authentification a échoué";
                $this->renderScript('error/error.phtml');
                break;

            case Zend_Auth_Result::SUCCESS:
                //Je reviens de l'authentification
                Hal_Auth::getInstance()->getIdentity()->load();
                $halUser->find(Hal_Auth::getUid());
                $this->redirect(CV_URL . $pageDestination);
                break;
        }
    }
    
    public function logoutAction ()
    {
        $casAuthAdapter = new Ccsd_Auth_Adapter_Cas();
        $session = new Zend_Session_Namespace(SESSION_NAMESPACE);
        $pageDestination = "/" . ( isset($session->idhal) ? $session->idhal : '' );
        $casAuthAdapter->logout(CV_URL . $pageDestination);
    }

}