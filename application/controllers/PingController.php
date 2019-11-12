<?php

/**
 * Un controleur pour vérifier si l'application répond
 */
class PingController extends Hal_Controller_Action {

    /**
     * Pong
     */
    public function indexAction() {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        echo 'pong';
        exit;
    }

}
