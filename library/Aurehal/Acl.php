<?php

class Aurehal_Acl extends Zend_Acl
{

	public function __construct() {
		//Ajout des rôles
		$this->addRole(new Zend_Acl_Role('guest'));
		$this->addRole(new Zend_Acl_Role('adminstruct'));
		$this->addRole(new Zend_Acl_Role('administrator'));
		$this->addRole(new Zend_Acl_Role('haladmin'));
		
		//Ajout des ressources
		$this->addResource(new Zend_Acl_Resource('anrproject'));		//Projet ANR
		$this->addResource(new Zend_Acl_Resource('journal'));			//Journaux
		$this->addResource(new Zend_Acl_Resource('europeanproject'));	//Projet Européen
		$this->addResource(new Zend_Acl_Resource('author'));			//Auteurs
		$this->addResource(new Zend_Acl_Resource('ajax'));			//Auteurs
		$this->addResource(new Zend_Acl_Resource('structure'));	    //Structure
        $this->addResource(new Zend_Acl_Resource('view'));		    	//View : Gui rdf

		$this->addGuestPrivileges()
		     ->addAdminstructPrivileges()
		     ->addAdministratorPrivileges()
		     ->addHaladminPrivileges();

		$this->addResource(new Zend_Acl_Resource('index'));
		$this->allow (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'index');
		
		$this->addResource(new Zend_Acl_Resource('zombie'));
		$this->allow (array('haladmin'), 'zombie');
		
		$this->addResource(new Zend_Acl_Resource('user'));
		$this->allow (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'user');
		
		$this->addResource(new Zend_Acl_Resource('error'));
		$this->allow (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'error');
		
		$this->allow (array('adminstruct', 'administrator', 'haladmin'), array('anrproject', 'journal', 'europeanproject', 'author', 'structure'), array('ajaxloaddocument', 'history'));
		
		$this->addResource(new Zend_Acl_Resource('domain'));			//Disciplines
		$this->allow (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'domain', array('index', 'browse', 'read'));
		$this->deny  (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'domain', array('modify'));

        $this->addResource(new Zend_Acl_Resource('typdoc'));			//Typde de document
        $this->allow (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'typdoc', array('index', 'browse', 'read'));
        $this->deny  (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'typdoc', array('modify'));

        $this->addResource(new Zend_Acl_Resource('idhal'));			//IDHAL
        $this->allow (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'idhal', array('index', 'browse', 'read'));
        $this->deny  (array('guest', 'adminstruct', 'administrator', 'haladmin'), 'idhal', array('modify'));
	}
	
	//Ajout des privilèges pour le rôle "guest"
	public function addGuestPrivileges ()
	{
		$this->deny('guest', 'anrproject', 		array('modify', 'replace', 'create'));
		$this->deny('guest', 'journal', 		array('modify', 'replace', 'create'));
		$this->deny('guest', 'europeanproject', array('modify', 'replace', 'create'));
		$this->deny('guest', 'author', 			array('modify', 'replace', 'create'));
		$this->deny('guest', 'structure', 		array('modify', 'replace', 'create'));
		
		$this->allow('guest', 'anrproject', 	 array('index', 'browse', 'read'));
		$this->allow('guest', 'journal', 		 array('index', 'browse', 'read'));
		$this->allow('guest', 'europeanproject', array('index', 'browse', 'read'));
		$this->allow('guest', 'author', 		 array('index', 'browse', 'read'));
		$this->allow('guest', 'structure', 		 array('index', 'browse', 'read'));
        $this->allow('guest', 'view',  		     array('rdf', 'browse', 'read'));

		return $this;
	}
	
	//Ajout des privilèges pour le rôle "adminstruct"
	public function addAdminstructPrivileges ()
	{
		$this->deny ('adminstruct', 'anrproject', 	   array('modify', 'replace', 'create'));
		$this->deny ('adminstruct', 'journal',  	   array('modify', 'replace', 'create'));
		$this->deny ('adminstruct', 'europeanproject', array('modify', 'replace', 'create'));
		$this->deny ('adminstruct', 'author', 	 	   array('modify', 'replace', 'create'));
		$this->allow ('adminstruct', 'structure',       array('modify', 'replace', 'create'));
	
		$this->allow('adminstruct', 'anrproject', 	   array('index', 'browse', 'read'));
		$this->allow('adminstruct', 'journal',         array('index', 'browse', 'read'));
		$this->allow('adminstruct', 'europeanproject', array('index', 'browse', 'read'));
		$this->allow('adminstruct', 'author', 	       array('index', 'browse', 'read'));
		$this->allow('adminstruct', 'structure',       array('index', 'browse', 'read'));
        $this->allow('adminstruct', 'view', 		     array('rdf', 'browse', 'read'));
		return $this;
	}
	
	//Ajout des privilèges pour le rôle "administrator"
	public function addAdministratorPrivileges ()
	{
		$this->allow('administrator', 'anrproject',      array('modify', 'replace', 'create'));
		$this->allow('administrator', 'journal', 	     array('modify', 'replace', 'create'));
		$this->allow('administrator', 'europeanproject', array('modify', 'replace', 'create'));
		$this->allow('administrator', 'author', 	     array('modify', 'replace', 'create'));
		$this->allow('administrator', 'ajax', 	        array('ajaxsearchstructure'));
		$this->allow('administrator', 'structure', 	     array('modify', 'replace', 'create'));
	
		$this->allow('administrator', 'anrproject',      array('index', 'browse', 'read'));
		$this->allow('administrator', 'journal', 	     array('index', 'browse', 'read'));
		$this->allow('administrator', 'europeanproject', array('index', 'browse', 'read'));
		$this->allow('administrator', 'author', 	     array('index', 'browse', 'read'));
		$this->allow('administrator', 'structure', 	     array('index', 'browse', 'read'));
        $this->allow('administrator', 'view', 		     array('rdf', 'browse', 'read'));
		return $this;
	}
	
	//Ajout des privilèges pour le rôle "haladmin"
	public function addHaladminPrivileges ()
	{
		$this->allow('haladmin', 'anrproject', 	    array('modify', 'replace', 'create'));
		$this->allow('haladmin', 'journal',         array('modify', 'replace', 'create'));
		$this->allow('haladmin', 'europeanproject', array('modify', 'replace', 'create'));
		$this->allow('haladmin', 'author',          array('modify', 'replace', 'create'));
        $this->allow('haladmin', 'ajax', 	        array('ajaxsearchstructure'));
		$this->allow('haladmin', 'structure',       array('modify', 'replace', 'create'));
	
		$this->allow('haladmin', 'anrproject', 	    array('index', 'browse', 'read'));
		$this->allow('haladmin', 'journal',         array('index', 'browse', 'read'));
		$this->allow('haladmin', 'europeanproject', array('index', 'browse', 'read'));
		$this->allow('haladmin', 'author',          array('index', 'browse', 'read'));	
		$this->allow('haladmin', 'structure',       array('index', 'browse', 'read'));
        $this->allow('haladmin', 'view', 		      array('rdf', 'browse', 'read'));

        return $this;
	}
		
}
