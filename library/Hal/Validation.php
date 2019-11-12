<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 10/01/2014
 * Time: 10:25
 */
class Hal_Validation {
	const TABLE = 'USER_VALIDATE_TMP';
	public function __construct() {
	}
	
	/**
	 * Sauvegarde expert
	 * 
	 * @param Zend_Controller_Request_Abstract $request        	
	 */
	public static function saveExpert($request) {
		$flashmessenger = Zend_Controller_Action_HelperBroker::getStaticHelper ( 'FlashMessenger' );
		$url = Zend_Controller_Action_HelperBroker::getStaticHelper ( 'Url' );
		
		$docid = $request->getParam ( 'docid' );
		$expertUid = $request->getParam ( 'experts' );
		$comment = $request->getParam ( 'comment-expert' );
		
		$doc = new Hal_Document ( $docid, '', '', true );
		
		// pas d'expert défini
		if ($expertUid == null) {
			
			// si doc pas déjà en validation
			if ($doc->getStatus () != Hal_Document::STATUS_VALIDATE) {
				$valMessage = "Aucun expert défini : le document reste en modération. Aucun changement.";
			} else {
				
				foreach ( Hal_Validation::getDocValidators ( $docid ) as $uid => $fullname ) {
					$validator = new Hal_User ();
					$validator->find ( $uid );
					
					$mail = new Hal_Mail ();
					// Tous les validateurs sont prévenus de la fin de l'expertise
					$mail->prepare ( $validator, Hal_Mail::TPL_ALERT_VALIDATOR_END_VALIDATION, array (
							$doc 
					) );
					$mail->writeMail ();
				}
				
				// reset suppression des validateurs
				Hal_Validation::delDocInValidation ( $docid );
				$valMessage = "Aucun expert défini : les experts ont été supprimés. Les experts ont été informés par e-mail. Le document a été remis en modération.";
				$doc->putOnModeration ();
				Hal_Document_Logger::log ( $docid, Hal_Auth::getUid (), Hal_Document_Logger::ACTION_MODERATE, $valMessage );
			}
			$flashmessenger->setNamespace ( 'success' )->addMessage ( "Document " . $doc->getId ( true ) . ' ' . $valMessage );
			return true;
		}
		
		// 1. change status
		
		if ($doc->validate ( Hal_Auth::getUid (), $comment )) {
			$flashmessenger->setNamespace ( 'success' )->addMessage ( "Document " . $doc->getId ( true ) . " est désormais en validation scientifique." );
		}
		
		// reset
		Hal_Validation::delDocInValidation ( $docid );
		
		foreach ( $expertUid as $uid ) {
			
			// 2 ajoute les experts
			$res = Hal_Validation::addDocInValidation ( $docid, $uid );
			
			$expert = new Hal_User ();
			$expert->find ( $uid );
			
			// 3. envoi mail experts
			$mail = new Hal_Mail ();
			$mail->prepare ( $expert, Hal_Mail::TPL_ALERT_VALIDATOR, array (
					$doc,
					$expert 
			) );
			$mail->writeMail ();
			
			$flashmessenger->setNamespace ( 'success' )->addMessage ( "Document " . $doc->getId ( true ) . ' ' . "L'expert " . $expert->getFullName () . " a été averti par e-mail." );
		}
		
		return true;
	}
	
	/**
	 * Retourne la liste des documents à valider pour l'utilisateur connecté
	 */
	public function getDocuments($filterByUid = true) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		
		$sql = $db->select ()->distinct ()->from ( array (
				'd' => Hal_Document::TABLE 
		), array (
				'd.DOCID',
				'd.TYPDOC',
				'd.DOCSTATUS',
				'd.DATESUBMIT',
				'd.UID' 
		) );
		
		$sql->where ( 'd.DOCSTATUS = ?', array (
				( int ) Hal_Document::STATUS_VALIDATE 
		) );
		

		
		
		$sql->from ( array ('s' => Hal_Site::TABLE), 'SITE' )->where ( 'd.SID=s.SID' );
		
	
		
		if ($filterByUid == true) {
			$sql->where ( 'hvt.UID  = ?', array (
					( int ) Hal_Auth::getUid () 
			) );
			
			$sql->from ( array (
					'hvt' => Hal_Validation::TABLE
			), null )->where ( 'hvt.DOCID=d.DOCID' );
			
		}
		
		$sql->order ( 'DATESUBMIT DESC' );
		return $db->fetchAll ( $sql );
	}
	
	/**
	 * Retourne la liste des experts disponibles
	 *
	 * @param string $docid        	
	 * @param int $sid        	
	 * @param array $rightCriteria        	
	 * @return array|null:
	 */
	public static function getAvailableExperts($sid = 0, $rightCriteria = null) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		
		$sql = 'SELECT * FROM ' . Hal_User::TABLE_ROLE . ' AS acl WHERE acl.RIGHTID = :rightName AND ( acl.SID = :sid OR  acl.SID = 0 ';
		
		if ($rightCriteria != null) {
			foreach ( $rightCriteria as $val ) {
				$sql .= " OR acl.VALUE LIKE '$val'";
			}
		}
		
		$sql .= ')';
		
		$sql .= 'GROUP BY acl.UID ORDER BY acl.UID ASC';
		
		$stmt = new Zend_Db_Statement_Pdo ( $db, $sql );
		
		$stmt->execute ( array (
				':rightName' => Hal_Acl::ROLE_VALIDATEUR,
				':sid' => $sid 
		) );
		
		return $stmt->fetchAll ();
	}
	
	/**
	 * Si un expert est disponible ou pas
	 *
	 * @param int $uid        	
	 * @return boolean
	 */
	public static function isAvailable($uid) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		
		$sql = "SELECT DISTINCT(acl.UID) FROM " . Hal_User::TABLE_ROLE . " AS acl WHERE acl.RIGHTID = :rightName AND acl.UID = :uid AND acl.SID = 0 AND VALUE = 'terminated'";
		
		$stmt = new Zend_Db_Statement_Pdo ( $db, $sql );
		
		$stmt->execute ( array (
				':rightName' => Hal_Acl::ROLE_VALIDATEUR,
				':uid' => ( int ) $uid 
		) );
		
		if (count ( $stmt->fetchAll () ) == 0) {
			return true;
		} else {
			return false;
		}
	}
	public static function setExpertNotAvailable($uid) {
		if (self::isAvailable ( $uid ) == false) {
			return true; // déja fait
		}
		
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$bind = array (
				'VALUE' => 'terminated',
				'UID' => ( int ) $uid,
				'SID' => 0,
				'RIGHTID' => Hal_Acl::ROLE_VALIDATEUR 
		);
		
		try {
			$db->insert ( Hal_User::TABLE_ROLE, $bind );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}
	public static function setExpertAvailable($uid) {
		if (self::isAvailable ( $uid ) == true) {
			return true; // déja fait
		}
		
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		
		$where = "UID = " . ( int ) $uid . " AND RIGHTID = " . $db->quote ( Hal_Acl::ROLE_VALIDATEUR ) . " AND VALUE = 'terminated' AND SID = 0";
		
		try {
			$db->delete ( Hal_User::TABLE_ROLE, $where );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Des documents sont en cours de Validation
	 *
	 * @param
	 *        	$docid
	 * @param
	 *        	$uid
	 * @param
	 *        	$ip
	 */
	static public function addDocInValidation($docid, $uid) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$bind = array (
				'DOCID' => ( int ) $docid,
				'UID' => ( int ) $uid 
		);
		
		try {
			$db->insert ( self::TABLE, $bind );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Les documents ne sont plus en cours de Validation
	 *
	 * @param
	 *        	$ip
	 * @param int $docid        	
	 */
	static public function delDocInValidation($docid) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		
		$where = 'DOCID = ' . ( int ) $docid;
		
		try {
			$db->delete ( self::TABLE, $where );
		} catch ( Exception $e ) {
			return false;
		}
	}
	
	/**
	 * Retourne les données pour le fomulaire de selection des experts
	 *
	 * @param Zend_Controller_Request_Abstract $request        	
	 */
	static public function getSelectExpertData(Zend_Controller_Request_Abstract $request) {
		$sid = $request->getParam ( 'sid', 0 );
		$domains = $request->getParam ( 'domains' );
		$typdoc = $request->getParam ( 'typdoc' );
		
		if (is_array ( $domains )) {
			foreach ( $domains as $dom ) {
				$domArr = Ccsd_Tools_String::getHalDomainPaths ( $dom );
				foreach ( $domArr as $dList ) {
					$crit [] = 'domain:' . $dList . '%';
				}
			}
		}
		
		if ($typdoc != null) {
			$crit [] = 'typdoc:' . $typdoc;
		}
		
		$allResults = Hal_Validation::getAvailableExperts ( $sid, $crit );
		
		if ($allResults == null) {
			$cssClass = 'disabled';
			$listArr [] = '{ value : "", label : "Aucun Résultat", cssClass: "' . $cssClass . '" }';
		}
		
		$u = new Hal_User ();
		
		foreach ( $allResults as $k => $user ) {
			
			$u->find ( $user ['UID'] );
			
			if ($user ['VALUE'] == 'terminated') {
				$cssClass = 'disabled';
			} else {
				$cssClass = '';
			}
			$listArr [] = '{ value : ' . $user ['UID'] . ', label : "' . $u->getFullName () . '", cssClass: "' . $cssClass . '" }';
		}
		
		return $listArr;
	}
	
	/**
	 * Les experts d'un doc en cours de validation
	 *
	 * @param int $docid        	
	 * @return multitype:|boolean
	 */
	static public function getDocValidators($docid) {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$bind = array (
				'DOCID' => $docid 
		);
		
		try {
			$sql = $db->select ()->from ( array (
					self::TABLE 
			), array (
					'UID' 
			) )->where ( 'DOCID = ?', array (
					( int ) $docid 
			) );
			
			$uids = $db->fetchAll ( $sql );
			
			if (($uids === false) or (count ( $uids ) == 0)) {
				return false;
			}
			
			$expert = new Hal_User ();
			foreach ( $uids as $uid ) {
				$expert->find ( $uid );
				$list [$expert->getUid ()] = $expert->getFullName ();
			}
			return $list;
		} catch ( Exception $e ) {
			return false;
		}
	}
} //end class