<?php

/**
 * Vérifications des droits d'accès à une ressource
 *
 */
class Hal_Auth_Plugin extends Ccsd_Auth_Plugin
{

    const UNAUTHAJAX_ACTION = 'unauthajax';

    /**
     * @return Hal_Acl
     * @throws Zend_Exception
     */
	public function getAcl()
	{
		return Zend_Registry::get('acl');
	}

    /**
     * @param Zend_Acl_Resource $resource
     * @return bool
     */
	public function isAllowed($resource)
    {
        $allow = false;
        if ($this->_acl->has($resource)) {
            foreach (Hal_Auth::getRoles() as $role) {
                if ($this->_acl->isAllowed($role, $resource)) {
                    $allow = true;
                    break;
                }
            }
        } else {
            $allow = true;
        }
        return $allow;
    }

    /**
     * @param Zend_Controller_Request_Http $request
     */
    public function preDispatch (Zend_Controller_Request_Abstract $request)
    {
    	// Récupération des règles d'accès
    	$this->_acl = $this->getAcl();
    	// Récupération de l'id de la ressource (à modifier)
    	$resource = $request->getControllerName() . '-' . $request->getActionName();
    	//Cas particulier pour les CV...
        if ($request->getControllerName() == 'cv') {
            $resource = 'cv-index';
        }
    	if ($this->_acl->has($resource)) {
    		//La ressource demandée existe
    		if (! $this->isAllowed($resource)) {
    			//L'utilisateur ne peut pas accéder à la page
    			if (! Hal_Auth::isLogged()) {
    				//L'utilisateur n'est pas connecté
                    $request->setParam('forward-action', $request->getActionName());
                    $request->setParam('forward-controller', $request->getControllerName());
                    $request->setControllerName('user');
    				$request->setActionName('login');
    			} else {
    				$request->setControllerName(self::FAIL_AUTH_CONTROLLER);
    				$request->setActionName(self::FAIL_AUTH_ACTION);
    				$request->setParam('error_message', "Erreur d'authentification");
    				$request->setParam('error_description', "L'accès à la ressource vous est interdit");
    			}
    		} else if (! Hal_Auth::isLogged() && ($resource == 'submit-index')){
    		    //L'utilisateur n'est pas connecté et veut déposer
                $request->setParam('forward-action', $request->getActionName());
                $request->setParam('forward-controller', $request->getControllerName());
                $request->setControllerName('user');
    			$request->setActionName('login');

    		} else if (Hal_Auth::isLogged() && $resource != 'user-edit' && $resource != 'user-logout'){

    			//L'utilisateur a le droit d'accéder à la ressource, on vérifie si sont compte est complété sur HAL
    			$halUser = new Hal_User(['UID' => Hal_Auth::getUid()]);
    			//TODO : ne pas faire ça en cas d'accès à la page d'erreur, sinon redirection infinie p. erreur <=> p. accountedit si l'erreur est sur accountedit
    			if (! $halUser->hasHalAccountData($halUser->getUid())) {
    				//Le compte doit être complété

    			    Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace('info')->addMessage('<span class="glyphicon glyphicon-warning-sign"></span>&nbsp;<strong>Important</strong> : merci de compléter <a href="#profil_hal" class="alert-link">votre profil HAL</a> pour continuer.');

    				$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
    				$redirector->gotoUrl('user/edit');
    			}

    			if (! $halUser->hasPrefDepot()) {
                    //On supprime la ligne car on ne le redirige que la première fois
                    $halUser->deleteHasPrefDepot();

                    //Les préférences de dépôt ont été complétés

                    Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->setNamespace('info')->addMessage('<span class="glyphicon glyphicon-warning-sign"></span>&nbsp; Merci de compléter <a href="#profil_hal" class="alert-link">vos préférences de dépôt dans HAL</a> pour continuer.');

                    $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                    $redirector->gotoUrl('user/editprefdepot');
                }
    		}
    	} else if (! $request->isXmlHttpRequest()) {
    		//La ressource demandée n'existe pas (pas définie dans les ACL)
    		$request->setControllerName(self::FAIL_AUTH_CONTROLLER);
    		$request->setActionName(self::PAGENOTFOUND_ACTION);
    	} else if ($request->getControllerName() == 'submit' && !Hal_Auth::isLogged()) {
            // Dans le cas où l'on essaie d'accéder à une action/ajax du Submit. Si l'utilisateur n'est pas loggué, on lui envoie une erreur 401
            $request->setControllerName(self::FAIL_AUTH_CONTROLLER);
            $request->setActionName(self::UNAUTHAJAX_ACTION);
        }
    }
}