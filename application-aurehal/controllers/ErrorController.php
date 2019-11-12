<?php

class ErrorController extends Zend_Controller_Action
{

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');

        if ($errors) {
	        if ($errors->exception instanceof Zend_Acl_Exception) {
	        	$this->renderScript('error/refused.phtml');
	        } else if ($errors->exception instanceof Zend_Controller_Router_Exception) {
	        	Ccsd_Tools::debug($errors->exception);
	        	$this->renderScript('error/missing.phtml');
	        } else if ($errors->exception instanceof Ccsd_Referentiels_Exception_IDHalException) {
	        	$this->view->message = $errors->exception->getMessage();
	        	
	        	foreach ($errors->exception->getCode() as $authorid) {
	        		echo (new Ccsd_Referentiels_Author($authorid));
	        	}
	        	
	        	$this->renderScript('error/alert.phtml');
	        } else if ($errors->exception instanceof Ccsd_Referentiels_Exception_ReferentStructException) {
	        	$this->view->message = $errors->exception->getMessage();
	        	
	        	foreach ($errors->exception->getCode() as $structid) {
	        		echo (new Ccsd_Referentiels_Structure($structid));
	        	}
	        	
	        	$this->renderScript('error/alert.phtml');
	        } else {
	        	if (!$errors || !$errors instanceof ArrayObject) {
	        		$this->view->message = 'You have reached the error page';
	        		return;
	        	}
	        	
	        	switch ($errors->type) {
	        		case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
	        		case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
	        		case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
	        			// 404 error -- controller or action not found
	        			$this->getResponse()->setHttpResponseCode(404);
	        			$priority = Zend_Log::NOTICE;
	        			$this->view->message = 'Page not found';
	        			break;
	        		default:
	        			// application error
	        			$this->getResponse()->setHttpResponseCode(500);
	        			$priority = Zend_Log::CRIT;
	        			$this->view->message = 'Application error';
	        			break;
	        	}
	        	
	        	// Log exception, if logger available
	        	if ($log = $this->getLog()) {
	        		$log->log($this->view->message, $priority, $errors->exception);
	        		$log->log('Request Parameters', $priority, $errors->request->getParams());
	        	}
	        	
	        	// conditionally display exceptions
	        	if ($this->getInvokeArg('displayExceptions') == true) {
	        		$this->view->exception = $errors->exception;
	        	}
	        	
	        	$this->view->request   = $errors->request;
	        }
        }
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

