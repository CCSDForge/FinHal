<?php 
class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $params = $this->getRequest()->getParams();
        if (preg_match('/^([a-z-]+)[^a-z-]+$/i', $params['controller'], $matches)) {
            $this->getRequest()->setControllerName($matches[1]);
            $this->getRequest()->setActionName($params['action']);
            $this->getRequest()->setDispatched(false);
        }

        $errors = $this->_getParam('error_handler');
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                $this->redirect('/docs/');
                exit;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $this->view->message = 'Application error';
                break;
        }

        $this->view->exception = $errors->exception;
        $this->view->request   = $errors->request;
    }

}