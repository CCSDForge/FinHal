<?php
/**
 * Affichage du CV d'un auteur
 *
 * Class IndexController
 */
class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        echo 'error';
    }
}