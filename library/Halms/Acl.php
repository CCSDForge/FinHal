<?php

class Halms_Acl extends Ccsd_Acl
{
    /**
     * Liste des droits de l'application
     * @var string
     */
	const ROLE_GUEST        =    'guest';
    const ROLE_MEMBER       =    'member';
    const ROLE_ADMINHALMS  =    'halmsadmin';

    public function __construct($file = null)
    {
        /**
		 * Héritage entre les rôles
         */
    	$this->_roles = array(
            self::ROLE_GUEST       =>    null,   
            self::ROLE_MEMBER      =>    self::ROLE_GUEST,   
            self::ROLE_ADMINHALMS  =>    self::ROLE_MEMBER
        );
        parent::__construct($file);

    }
    
}