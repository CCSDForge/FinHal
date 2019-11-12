<?php
class Hal_User_Search {
	const TABLE = "USER_SEARCH";
	public $searchid;
	public $uid = 0;
	public $lib;
	public $url;
	public $url_api;
	public $type;
	public $args = array ();
	private $_sidName;
	private $_sidUrl;
	
	/**
	 * @var Hal_User
	 */
	private $_user;
	/**
	 * @var string fréquence des alertes
	 */
	private $_freq;
	/**
	 * @var array fréquences valides des alertes
	 */
	static $validFreq = array (
			'none',
			'day',
			'week',
			'month',
			'push' 
	);
	/**
	 * @var int
	 */
	protected $_sid;
	/**
	 * @var string timestamp
	 */
	public $update_date;
    /**
     * @var Zend_Db_Adapter_Abstract
     */
	protected $_db;
	/**
	 * @param array $options        	
	 */
	public function __construct($options = array()) {
		$this->_db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$this->setOptions ( $options );
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
				case 'searchid' :
					$this->searchid = ( int ) $value;
					break;
				case 'uid' :
					$this->uid = ( int ) $value;
					break;
				case 'lib' :
					$this->lib = ( string ) $value;
					break;
				case 'url' :
					$this->url = ( string ) $value;
					break;
				case 'url_api' :
					$this->url_api = ( string ) $value;
					break;
				case 'freq' :
					$this->setFreq ( $value );
					break;
				case 'sid' :
					$this->setSid ( $value );
					break;
				case 'sidname' :
					$this->setSidName ( $value );
					break;
				case 'sidurl' :
					$this->setSidUrl ( $value );
					break;
				case 'update_date' :
					$this->update_date = $value;
					break;
				case 'user' :
					$this->setUser ( $value );
					break;
                default:
                    error_log("Bad field ($option) name in " . __FILE__ );
			}
		}
	}
    /**
     * @return Hal_User_Search[]
     */
	public function getMysearch() {
		$sql = $this->_db->select ()->from ( array (
				't' => self::TABLE 
		), array (
				'SEARCHID',
				'UID',
				'LIB',
				'URL',
				'URL_API',
				'FREQ',
				'SID',
				'UPDATE_DATE' 
		) )->joinLeft ( array (
				's' => 'SITE' 
		), 't.SID = s.SID', array (
				'SIDNAME' => 's.NAME',
				'SIDURL' => 's.URL' 
		) )->where ( 'UID = ?', $this->uid )->order ( 'UPDATE_DATE DESC' );
		
		$arr = array ();
		
		foreach ( $this->_db->fetchAll ( $sql ) as $row ) {
			$arr [] = new self ( $row );
		}
		
		return $arr;
	}
	
	/**
	 * Retourne la liste des alertes
	 *
	 * @param int $uid        	
	 * @return Hal_User_Search[]
	 */
	public function getSearchAlerts($uid = null) {
		$sql = $this->_db->select ()->from ( array (
				't' => $this->getHalDbName () . '.' . self::TABLE 
		), array (
				't.UID',
				't.LIB',
				't.URL',
				't.SID',
				't.FREQ',
				't.URL_API',
				't.UPDATE_DATE' 
		) )->join ( array (
				'u' => $this->getCasDbName () . '.' . Ccsd_Db_Adapter_Cas::USER_TABLE 
		), 't.UID = u.UID', array (
				'u.UID',
				'u.EMAIL',
				'u.CIV',
				'u.FIRSTNAME',
				'u.LASTNAME' 
		) )->where ( 'FREQ = ?', $this->getFreq () )->order ( 'UPDATE_DATE DESC' );
		
		if ($uid != null) {
			$sql->where ( 'u.UID = ?', $uid );
		}
		
		$arr = array ();
		
		foreach ( $this->_db->fetchAll ( $sql ) as $row ) {
			$user = array (
					'UID' => $row ['UID'],
					'CIV' => $row ['CIV'],
					'FIRSTNAME' => $row ['FIRSTNAME'],
					'LASTNAME' => $row ['LASTNAME'],
					'EMAIL' => $row ['EMAIL'] 
			);
			$row ['user'] = $user;
			$arr [] = new self ( $row );
		}
		
		return $arr;
	}
	
	/**
	 * Retourne l'URL prêt à envoyer à l'API
	 *
	 * @param string $url        	
	 * @return string
	 */
	public function prepareApiUrl($url) {
		$url = parse_url ( $url, PHP_URL_QUERY );
		$url = html_entity_decode ( $url );
		$url_arr = explode ( '&', $url );
		$urlArgs =[];
		foreach ( $url_arr as $valueParam ) {
			
			$valueParam_arr = explode ( '=', $valueParam );
			
			list ( $name, $value ) = $valueParam_arr;
			
			$value = urldecode ( $value ); // pour éviter double encodage
			
			$urlArgs [] = http_build_query ( array (
					$name => $value 
			), null, '&' );
		}
		
		$url = implode ( '&', $urlArgs );
		
		return SOLR_API . '/search/?' . $url;
	}
	
	/**
	 * Mise à jour fréquence
	 *
	 * @return number|boolean
	 */
	public function updateFreq() {
		$freq = $this->getFreq ();
		
		if (isset ( $this->searchid ) && $this->searchid && isset ( $freq ) && $freq) {
			return $this->_db->update ( self::TABLE, array (
					'FREQ' => $this->getFreq () 
			), "SEARCHID = " . $this->searchid . " AND UID = " . $this->uid );
		}
		return false;
	}
	/**
	 * Suppression de l'alerte
	 *
	 * @return number|boolean
	 */
	public function delete() {
		if (isset ( $this->searchid ) && $this->searchid) {
			return $this->_db->delete ( self::TABLE, "SEARCHID = " . $this->searchid . " AND UID = " . $this->uid );
		}
		return false;
	}
	
	/**
	 *
	 * @return string $url_api
	 */
	public function getUrl_api() {
		return $this->url_api;
	}
	
	/**
     * @param string $url_api
     * @return Hal_User_Search
     */
	public function setUrl_api($url_api) {
		$this->url_api = $url_api;
		return $this;
	}
	
	/**
	 * @return int $_sid
	 */
	public function getSid() {
		return $this->_sid;
	}
	
	/**
     * @param int $_sid
     * @return Hal_User_Search
     */
	public function setSid($_sid) {
		$this->_sid = ( int ) $_sid;
		return $this;
	}
	/**
	 *
	 * @return string $_sidName
	 */
	public function getSidName() {
		return $this->_sidName;
	}
	/**
	 *
	 * @return string $_sidUrl
	 */
	public function getSidUrl() {
		return $this->_sidUrl;
	}
	/**
	 * @param string $_sidName
     * @return Hal_User_Search
	 */
	public function setSidName($_sidName) {
		$this->_sidName = $_sidName;
		return $this;
	}
	/**
	 * @param string $_sidUrl
     * @return Hal_User_Search
	 */
	public function setSidUrl($_sidUrl) {
		$this->_sidUrl = $_sidUrl;
		return $this;
	}
    /**
     * @param array (option pour Hal_User)
     * @return Hal_User_Search
     */
    public function setUser($user) {
        $this->_user = new Hal_User ( $user );
        $this->_user->findUserLanguage ();
        return $this;
    }
    /**
	 * @param string $_freq        	
	 */
	public function setFreq($_freq) {
		if (in_array ( $_freq, self::$validFreq )) {
			$this->_freq = $_freq;
		} else {
			$this->_freq = 'none';
		}
	}
	/**
	 * @return string
	 */
	public function getFreq() {
		return $this->_freq;
	}
    /**
     * @return Hal_User
     */
	public function getUser() {
		return $this->_user;
	}
    /**
     * @return string
     */
	private function getHalDbName() {
		return $this->_db->getConfig () ['dbname'];
	}
    /**
     * @return string
     */
	private function getCasDbName() {
		return Ccsd_Db_Adapter_Cas::getAdapter ( APPLICATION_ENV )->getConfig () ['dbname'];
	}
}
