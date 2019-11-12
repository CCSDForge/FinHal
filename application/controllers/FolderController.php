<?php

/**
 * Controller permettant le rendu d'une page personnalisable
 *
 */
class FolderController extends Hal_Controller_Action
{

    public function init ()
    {
        // Récupération du nom de la page
        $this->_action = $this->getRequest()->getActionName();
        $this->getRequest()->setActionName('render');
    }

    public function renderAction ()
    {
    	$this->renderScript('index/submenu.phtml');
    }
}