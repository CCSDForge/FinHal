<?php

class Hal_Mail_Template 
{
	protected $_id;
	protected $_parentId;
	protected $_sid;
	protected $_key;
	
	protected $_translations;
	protected $_body;
	protected $_name;
	protected $_subject;
	
	const T_MAIL_TEMPLATES = 'MAIL_TEMPLATE';
	
	public function __construct (array $options = null)
	{
		if (is_array($options)) {
			$this->setOptions($options);
		}
	}
	
	public function setOptions (array $options)
	{
		$methods = get_class_methods($this);
		foreach ($options as $key => $value) {
			$key = strtolower($key);
			$method = 'set' . ucfirst($key);
			if (in_array($method, $methods)) {
				$this->$method($value);
			} else {
				echo "La méthode $value n'existe pas<br/>";
			}
		}
	
		return $this;
	}
	
	// Trouve un template par son id, et charge ses données (bdd + translations)
	public function find($id)
	{
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		$select = $db->select()->from(self::T_MAIL_TEMPLATES)->where('ID = ? ', $id);
		$result = $select->query()->fetch();
		
		if ($result) {
			$this->populate($result);
			return $result;				
		} else {
			return null;
		}
	}
	
	// Trouve un template par sa clé, et charge ses données (bdd + translations)
	public function findByKey($key)
	{
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
	
		// On vérifie si il existe une version personnalisée de ce template
		$sql = $db->select()->from(self::T_MAIL_TEMPLATES)->where('`KEY` = ? ', 'custom_'.$key)->where('SID = ?', SITEID);
		$result = $db->fetchRow($sql);
		
		if ($result) {
			$this->populate($result);
			return $result;
		}
		
		// Sinon, on cherche sa version par défaut	
		$sql = $db->select()->from(self::T_MAIL_TEMPLATES)->where('`KEY` = ? ', $key);
		$result = $db->fetchRow($sql);
		
		if ($result) {
			$this->populate($result);
			return $result;
		} else {
			return null;
		}
	}
	
	private function populate($data)
	{
		if ($data) {
			$this->setId($data['ID']);
			$this->setParentId($data['PARENTID']);
			$this->setSid($data['SID']);
			$this->setKey($data['KEY']);
			// $this->loadTranslations();
		} else {
			return null;
		}
	}
	
	public function toArray()
	{
		$result = array();
		
		$fields = array(
				'id',
				'parentId',
				'sid',
				'key',
				'body'
		);
		
		foreach ($fields as $key) {
			$method = 'get' . ucfirst($key);
			if (method_exists($this, $method)) {
				$result[$key] = $this->$method();
			}
		}
		
		return $result;
	}
	
	// Enregistre un template modifié
	public function save()
	{
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		if (!$this->getParentid()) {
			// Nouveau template personnalisé
			$key = 'custom_'.$this->getKey();		
			$values = array(
					'PARENTID'	=>	$this->getId(),
					'SID'		=>	SITEID,
					'KEY'		=>	$key					
			);
			if (!$db->insert(self::T_MAIL_TEMPLATES, $values)) {
				return false;
			}
		} else {
			// Modification d'un template personnalisé
			$key = $this->getKey();			
		}
		
		// Ecriture des traductions ********************************
		
		// Récupération du fichier de traduction
		$path = SPACE . 'languages/';
		$filename = 'mails.php';
		
		$translations = self::getOtherTranslations(SPACE . 'languages/', $filename, '#^'.$key.'#'); // Vérifier chemin
			
		// Traductions du nom du template
		$name = $this->getName();
		foreach ($name as $lang=>$translation) {
			$translations[$lang][$key.'_tpl_name'] = $translation;
		}
			
		// Traductions du sujet du template
		$subject = $this->getSubject();
		foreach ($subject as $lang=>$translation) {
			$translations[$lang][$key.'_mail_subject'] = $translation;
		}
			
		// Mise à jour du fichier de traduction
		Hal_Mail_Translations::writeTranslations($translations, SPACE . 'languages/', $filename);
			
		// Création du template dans ses différentes langues
		$body = $this->getBody();
		foreach ($body as $lang=>$translation) {
			$path = SPACE . 'languages/'. $lang . '/emails/';
			if (!is_dir($path)) {
				mkdir($path);
			}
			file_put_contents ($path . $key.'.phtml', $translation);
		}
		
		return true;

	}
	
	// Suppression d'un template
	public function delete()
	{
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$id = $this->getId();
		$key = $this->getKey();
		
		// Supprimer en base
		$db->delete(self::T_MAIL_TEMPLATES, 'ID = '.$id);

		// Supprimer les fichiers de traduction
		$translations = self::getOtherTranslations(SPACE . 'languages/', 'mails.php', '#^'.$key.'#');
		Hal_Mail_Translations::writeTranslations($translations, SPACE . 'languages/', 'mails.php');
		
		// Supprimer le template
		$langFolders = scandir($path);
		foreach ($langFolders as $folder) {
			$filepath = SPACE . 'languages/'.$folder.'/emails/'.$key.'.phtml';
			if (file_exists($filepath)) {
				unlink($filepath);
			}
		}
		return true;
	}
	
	// Charge les traductions du template (body, name et subject)
	public function loadTranslations($langs = null) 
	{
		$translator = Zend_Registry::get('Zend_Translate');
		if (!$langs) {
			$langs = Zend_Registry::get('website')->getLanguages();
		}

		$path = ($this->getParentid()) ? SPACE . 'languages/' : APPLICATION_PATH.'/languages/';
		Hal_Mail_Translations::loadTranslations($path, 'mails.php');
		
		$this->loadName($langs);
		$this->loadSubject($langs);
		$this->loadBody();
	}
		
	public function getTranslations() 
	{
		return $this->_translations;
	}
	
	// Charge le corps du template dans les différentes langues trouvées
	// @return array
	public function loadBody()
	{
		$path = ($this->getParentid()) ? SPACE . 'languages/' : APPLICATION_PATH.'/languages/';
		$exclusions = array('.', '..', '.svn');
		$result = array();
	
		if (is_dir($path)) {
				
			$files = scandir($path);
			foreach ($files as $file) {
				$filepath = $path.$file.'/emails/'.$this->getKey().'.phtml';
				if (!in_array($file, $exclusions) && file_exists($filepath)) {
					$result[$file] = file_get_contents($filepath);
				}
			}
		}
	
		if (!empty($result)) {
			$this->setBody($result);
			return $result;
		} else {
			return null;
		}
	}
	
	
	// Renvoie les traductions qui ne correspondent pas au pattern passé en paramètre
	// Peut scanner tous les fichiers du répertoire, ou chercher celui passé en paramètre
	private static function getOtherTranslations($path, $file=null, $pattern)
	{
		$translations = array();
	
		if ($file && !preg_match('#(.*).php#', $file)) {
			$file .= '.php';
		}
	
		$translations = Hal_Mail_Translations::getTranslations($path, $file);
		// Filtre les traductions en fonction du pattern
		if (!empty($translations)) {
			foreach ($translations as $lang=>$tmp_translation) {
				foreach($tmp_translation as $key=>$translation) {
					if (preg_match($pattern, $key)) {
						unset($translations[$lang][$key]);
					}
				}
			}
		}
		
		return $translations;
	}
	
	// Charge le nom template dans les différentes langues trouvées
	// @return array
	public function loadName($langs)
	{
		$translator = Zend_Registry::get('Zend_Translate');
		foreach ($langs as $lang) {		
			if ($translator->isTranslated($this->getKey().'_tpl_name', false, $lang)) {
				$name[$lang] = $translator->translate($this->getKey().'_tpl_name', $lang);	
			}
		}
		if (!empty($name)) {
			$this->setName($name);
			return $name;
		} else {
			return null;
		}
	}
	
	// Charge le sujet du template dans les différentes langues trouvées
	// @return array
	public function loadSubject($langs)
	{
		$translator = Zend_Registry::get('Zend_Translate');
		foreach ($langs as $lang) {
			// Subject
			if ($translator->isTranslated($this->getKey().'_mail_subject', false, $lang)) {
				$subject[$lang] = $translator->translate($this->getKey().'_mail_subject', $lang);
			}
		}
		if (!empty($subject)) {
			$this->setSubject($subject);
			return $subject;
		} else {
			return null;
		}
	}
	
	
	// GETTERS **************************************************
	
	public function getPath($locale)
	{
		return ($this->getParentid()) ? SPACE.'languages/'.$locale.'/emails' : APPLICATION_PATH.'/languages/'.$locale.'/emails'; 
	}
	
	public function getBody($lang=null)
	{
		if ($lang) {
			if (is_array($this->_body) && array_key_exists($lang, $this->_body)) {
				return $this->_body[$lang];
			} else {
				return null;
			}			
		} else {
			return $this->_body;
		}
	}
	
	public function getName($lang=null)
	{
		if ($lang) {
			if (is_array($this->_name) && array_key_exists($lang, $this->_name)) {
				return $this->_name[$lang];
			} else {
				return null;
			}
		} else {
			return $this->_name;
		}
	}
	
	public function getSubject($lang=null)
	{
		if ($lang) {
			if (is_array($this->_subject) && array_key_exists($lang, $this->_subject)) {
				return $this->_subject[$lang];
			} else {
				return null;
			}
		} else {
			return $this->_subject;
		}
	}
	
	public function getId()
	{
		return $this->_id;
	}
	
	public function getParentid()
	{
		return $this->_parentId;
	}
	
	public function getSid()
	{
		return $this->_sid;
	}
	
	public function getKey()
	{
		return $this->_key;
	}
	
	
	// SETTERS ***************************************************
	
	public function setBody($body)
	{
		$this->_body = $body;
		return $this;
	}
	
	public function setName($name)
	{
		$this->_name = $name;
		return $this;
	}
	
	public function setSubject($subject)
	{
		$this->_subject = $subject;
		return $this;
	}
	
	public function setId($id)
	{
		$this->_id = $id;
		return $this;
	}
	
	public function setParentid($parentId)
	{
		$this->_parentId = $parentId;
		return $this;
	}

	public function setSid($sid)
	{
		$this->_sid = $sid;
		return $this;
	}
	
	public function setKey($key)
	{
		$this->_key = $key;
		return $this;
	}
	
	
}
