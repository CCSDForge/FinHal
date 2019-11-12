<?php

/**
 * WS SOAP Controller
 *
 */
class WsController extends Zend_Controller_Action
{
    public function init()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->redirect('/docs/soap');
    }
}