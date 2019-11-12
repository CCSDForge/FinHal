<?php
/**
 * Gestion des message prédefinis pour l'évaluation : moderation et + expertise - validation scientifique
 * @see Hal_Evaluation_Moderation_Message
 *  @see Hal_Evaluation_Validation_Message
 *
 */
abstract class Hal_Evaluation_Message {
	protected $_messageid = 0;
	protected $_uid = 0;
	protected $_title = '';
	protected $_message = '';
	protected $_form;
	protected $_db;
    
	public function __construct($id = false, $load = true, $dbTable = null) {
		$this->_db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		
		if ($id) {
			$this->_messageid = $id;
			if ($load) {
				$this->load ();
			}
		}
		$this->_form = new Ccsd_Form ();
		$this->_form->removeDecorator ( 'Form' );
	}
	public function getId() {
		return $this->_messageid;
	}
	//Just an alias with fieldname
	public function getMessageid() {
		return $this->_messageid;
	}
	
	public function getUid() {
        return $this->_uid;
    }

    public function setUid($uid) {
        return $this->_uid = $uid;
    }

    public function getMessage() {
        return $this->_message;
    }
    
	public function setMessage($msg) {
        return $this->_message = $msg;
    }
    
	public function getTitle() {
        return $this->_title;
    }
    
	public function setTitle($title) {
        return $this->_title = $title;
    }
    
	/**
	 * Définition des options de la page
	 * 
	 * @param array $options        	
	 */

    public function setOptions($options = array()) {
		foreach ( $options as $option => $value ) {
			$option = strtolower ( $option );
			switch ($option) {
				case 'messageid' :
					$this->_messageid = ( int ) $value;
					break;
				
				case 'uid' :
					$this->_uid = ( int ) $value;
					break;
				
				case 'title' :
					$this->_title = ( string ) $value;
					break;
				
				case 'message' :
					$this->_message = ( string ) $value;
					break;
			}
		}
	}
    
	public function load($id = false) {
		if (! $id && $this->_messageid == 0) {
			return false;
		} else if ($id) {
			$this->_messageid = $id;
		}
		
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = $db->select ()->from ( static::$dbTable )->where ( 'MESSAGEID = ?', $this->_messageid )->limit ( 1 );
		
		$m = $db->fetchRow ( $sql );
		
		$this->_uid = $m ['UID'];
		$this->_title = $m ['TITLE'];
		$this->_message = $m ['MESSAGE'];
		
		return $this;
	}
	public static function getList($uid = 0, $show_default = true) {
		$list = array ();
		
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = $db->select ()->from ( static::$dbTable )->where ( "UID = ?", $uid );
		
		if ($show_default) {
			$sql->orWhere ( "UID = ?", 0 );
		}
		$sql->order ( 'TITLE ASC' );
		
		foreach ( $db->fetchAll ( $sql ) as $row ) {
			$m = new static::$className ();
			$m->_messageid = $row ['MESSAGEID'];
			$m->_uid = $row ['UID'];
			$m->_title = $row ['TITLE'];
			$m->_message = $row ['MESSAGE'];
			
			$list [$m->_messageid] = $m;
		}
		
		return $list;
	}
	public static function getIds($uid, $isAdmin = false) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = $db->select ()->from ( static::$dbTable, array (
				"MESSAGEID" 
		) )->where ( "UID = ?", $uid );
		if ($isAdmin) {
			$sql->orWhere ( "UID = 0" );
		}
		return $db->fetchCol ( $sql );
	}
	public static function cleanTable($uid = false) {
		if (! $uid || $uid == 0) {
			return false;
		}
		
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		return $db->delete ( static::$dbTable, "`UID` = " . $uid );
	}
	public function save() {
		$options = $this->toArray ();
		unset ( $options ['messageid'] );
		
		if ($this->_messageid != 0) {
			return $this->_db->update ( static::$dbTable, array_change_key_case ( $this->toArray (), CASE_UPPER ), "MESSAGEID = " . $this->_messageid );
		} else {
			$ret = $this->_db->insert ( static::$dbTable, array_change_key_case ( $this->toArray (), CASE_UPPER ) );
            $this -> _messageid = $this->_db->lastInsertId(static::$dbTable); 
            return $ret;
		}
	}
	public function delete() {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		return $db->delete ( static::$dbTable, "`MESSAGEID` = " . $this->_messageid );
	}
	public function getForm() {
		$this->_form->addElement ( 'hidden', 'uid', array (
				'value' => $this->_uid,
				'belongsTo' => 'message_' . $this->_messageid 
		) );
		$this->_form->addElement ( 'hidden', 'status', array (
				'value' => 'nop',
				'belongsTo' => 'message_' . $this->_messageid 
		) );
		$this->_form->addElement ( 'hidden', 'messageid', array (
				'value' => $this->_messageid,
				'belongsTo' => 'message_' . $this->_messageid 
		) );
		$this->_form->addElement ( 'text', 'title', array (
				'label' => 'Titre',
				'value' => $this->_title,
				'belongsTo' => 'message_' . $this->_messageid 
		) );
		$this->_form->addElement ( 'textarea', 'message', array (
				'label' => 'Message',
				'rows' => 3,
				'value' => $this->_message,
				'belongsTo' => 'message_' . $this->_messageid 
		) );
		
		return $this->_form;
	}
	public function toArray() {
		return array (
				"messageid" => $this->_messageid,
				"uid" => $this->_uid,
				"title" => $this->_title,
				"message" => $this->_message 
		);
	}
}