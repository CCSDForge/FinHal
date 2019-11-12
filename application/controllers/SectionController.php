<?php

/**
 * Controller permettant le rendu d'une page personnalisable
 *
 */
class SectionController extends Hal_Controller_Action
{

    public function init ()
    {
        // Récupération du nom de la page
        $this->_action = $this->getRequest()->getActionName();
        $this->getRequest()->setActionName('render');
    }

    public function renderAction ()
    {
        $webSiteId = Zend_Registry::get('website')->getSid();
        // Quand on tape dans la document de hal
        if ($webSiteId == 1 && $this->_action == 'documentation') {
            Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('https://doc.archives-ouvertes.fr');
        } else {
            $this->renderScript('index/submenu.phtml');
        }
    }
}