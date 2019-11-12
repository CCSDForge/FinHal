<?php

class Aurehal_Acl_Plugin extends Zend_Controller_Plugin_Abstract {
	
	private $_acl = null;

	public function __construct(Zend_Acl $acl) {
		$this->_acl = $acl;
	}

	public function isAllowed (Zend_Controller_Request_Abstract $request, $exception = true) {
		$roles = array_intersect(Hal_Auth::getRoles(), $this->_acl->getRoles());

		if (empty ($roles)) {
			$roles = array('guest');
		}
		
		$allowed = false;
		foreach ($roles as $role) {
			$allowed = $allowed || $this->_acl->isAllowed($role, $request->getControllerName(), $request->getActionName());
		}

		if ( Hal_Auth::isAdminStruct() ) {
			if ('structure' == $request->getControllerName()) {
				
				if (in_array($request->getActionName(), array('modify', 'replace', 'transfer'))) {
					
					if ( in_array('administrator', $roles) || in_array('haladmin', $roles) ) {
						return true;
					}
					
					$structures = array_keys(Hal_Auth::getDetailsRoles(Hal_Acl::ROLE_ADMINSTRUCT));
	
					if ( empty($structures) ) {
                        return false;
                    }
                    
					if ('modify' == $request->getActionName()) {
                        return in_array($request->getParam('id'), $structures);
                    } else if ('transfer' == $request->getActionName()){
                        return in_array($request->getParam('id'), $structures);
                    } else if ('replace' == $request->getActionName()) {
						$structs = $request->getParam('row');
						if (!is_array($structs)) $structs = array($structs);
						
						if (($id = $request->getParam('dest', false)) !== false) {
							array_push($structs, $id);
						}

						if (array_diff ($structs, $structures)) {
							if ($exception)
								throw new Ccsd_Referentiels_Exception_ReferentStructException("", array_diff ($structs, $structures));
							else return false;
						}
						
						return true;
					}
				}
			}
		}


        return $allowed;
	}

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
    	if (!$this->isAllowed($request)) {
    		throw new Zend_Acl_Exception();
    	}
    }
     
}