<?php

/**
 * Class Hal_Acl
 */
class Hal_Acl extends Ccsd_Acl
{
    /**
     * Liste des droits de l'application
     * @var string
     */
    const ROLE_AUTHOR        =   'author'; // Pas réellement un rôle mais nécessaire au bon fonctionnement des pref mail
	const ROLE_GUEST        =    'guest';
    const ROLE_MEMBER       =    'member';
    const ROLE_ADMIN        =    'administrator';
    const ROLE_A_TAMPONNEUR =    'atamponneur'; // n'est pas le tamponneur de cette collection mais peut tamponner pour une collection
    const ROLE_TAMPONNEUR   =    'tamponneur';  // Est le tamponneur de cette collection
    const ROLE_ADMINSTRUCT  =    'adminstruct';
    const ROLE_VALIDATEUR   =    'validateur';
    const ROLE_MODERATEUR   =    'moderateur';
    const ROLE_ADMINHALMS   =    'halmsadmin';
    const ROLE_HALADMIN     =    'haladmin';

    /**
     * Hal_Acl constructor.
     * @throws Zend_Config_Exception
     */
    public function __construct()
    {
        /**
		 * Héritage entre les rôles
         */
    	$this->_roles = array(
            self::ROLE_GUEST       =>    null,
            self::ROLE_MEMBER      =>    self::ROLE_GUEST,
            self::ROLE_ADMIN       =>    self::ROLE_MEMBER,
            self::ROLE_A_TAMPONNEUR=>    self::ROLE_MEMBER,
            self::ROLE_TAMPONNEUR  =>    self::ROLE_A_TAMPONNEUR,
            self::ROLE_ADMINSTRUCT =>    self::ROLE_MEMBER,
            self::ROLE_VALIDATEUR  =>    self::ROLE_MEMBER,
            self::ROLE_MODERATEUR  =>    self::ROLE_MEMBER,
            self::ROLE_ADMINHALMS  =>    self::ROLE_MEMBER,
            self::ROLE_HALADMIN    =>    self::ROLE_ADMIN
        );
        parent::__construct();
        
        //Ressources à rajouter dans les ACL
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/acl.ini');
        $this->_defaultAcl = $config->toArray();
    }
    
}