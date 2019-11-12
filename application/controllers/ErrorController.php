<?php

class ErrorController extends Hal_Controller_Action
{

    public function errorAction()
    {
        $params = $this->getRequest()->getParams();

        $controller = $params['controller'];
        if (preg_match('/^([a-z-]+)[^a-z-]+$/i', $controller, $matches)) {
            $this->getRequest()->setControllerName($matches[1]);
            $this->getRequest()->setActionName($params['action']);
            $this->getRequest()->setDispatched(false);
        }

        $errors = $this->_getParam('error_handler');
        if ($errors != null) {


            switch ($errors->type) {
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:

                    // 404 error -- controller or action not found
                    $this->redirect('/error/pagenotfound');
                    $this->getResponse()->setHttpResponseCode(404);
                    $this->view->message = 'Page not found';
                    break;
                default:
                    // application error
                    $this->getResponse()->setHttpResponseCode(500);

                    // On log la première exception de la stack
                    $exceptions = $this->getResponse()->getException();
                    if (isset($exceptions[0])) {
                        error_log($exceptions[0]->getMessage());
                    }

                    $this->view->message = 'Application error';
                    break;
            }
            $this->view->exception = $errors->exception;
            $this->view->request = $errors->request;
        } else {
            $this->view->message = $this->_getParam('error_message');
            $this->view->description = $this->_getParam('error_description');
        }
    }


    public function featureDisabledAction()
    {
        $this->getResponse()->setHttpResponseCode(401);
        $this->render('feature-disabled');
    }


    public function pagenotfoundAction()
    {
        $this->getResponse()->setHttpResponseCode(404);
    }

    public function unauthajaxAction()
    {
        $this->noRender();
        $this->getResponse()->setHttpResponseCode(401);
    }

    public function collectionnotfoundAction()
    {

    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }


}

