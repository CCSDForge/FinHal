<?php
class Hal_Typdoc {
    
    /**
     * id du typdoc
     * @var string
     */
    protected $_id;
    
    /**
     * id du portail auquel est attaché le typdoc
     * @var string
     */
    protected $_sid;
    
    /**
     * type de l'élément (typdoc ou category)
     * @var string
     */
    protected $_type = 'typdoc';
    
    /**
     * Tableau des langues dispo du typdoc
     * @var array
     */
    protected $_languages  =   array();
    
    /**
     * Tableau des labels du typdoc
     * @var array
     */
    protected $_labels  =   array();
    
    /**
     * Formulaire de paramétrage du typdoc
     * @var Zend_Form
     */
    protected $_form = null;

    /**
     * Dans le cas d'une catégorie, les typdocs associés
     * @var array
     */
    public $children;
    
    /**
     * Initialisation du typdoc
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->setOptions($options);        
        $this->_form = new Ccsd_Form();
    }

    /**
     * récupération de l'id
     * @return string
     */
    public function getId()
    {
    	return $this->_id;
    }
    
    /**
     * Initialisation de l'id
     * @param string $id
     */
    public function setId($id)
    {
    	$this->_id = $id;
    }
    
    /**
     * récupération du sid
     * @return int
     */
    public function getSid()
    {
    	return $this->_sid;
    }
    
    /**
     * Initialisation du sid
     * @param int $sid
     */
    public function setSid($sid)
    {
    	$this->_sid = $sid;
    }
    
    /**
     * récupération du type
     * @return string
     */
    public function getType()
    {
    	return $this->_type;
    }
    
    /**
     * Initialisation du type
     * @param string $type
     */
    public function setType($type)
    {
    	$this->_type = $type;
    }
    
    /**
     * Définition des options de la page
     * @param array $options
     */
    public function setOptions($options = array())
    {        
        foreach ($options as $option => $value) {
            $option = strtolower($option);
            switch($option) {
                case 'languages':
                    $this->_languages = $value;
                    $this->initLabels();
                    break;
                case 'id'      :
                    $this->_id = (string) $value;
                    $this->initLabels();
                    break;
                case 'sid'      :
                    $this->_sid = (string) $value;
                    break;
                case 'type'      :
                    $this->_type = (string) $value;
                    break;
                case 'labels'   :
                    $this->setLabels($value);
                    break;                                         
            }
        } 
    }
    
	/**
     * Récupération de la liste des langues de la page
     * @return array
     */
    public function getLanguages()
    {
    	return $this->_languages;
    }
    
    public function initLabels ($prefix = 'typdoc_')
    {
        if (isset($this->_languages) && $this->_languages && isset($this->_id) && $this->_id) {
            foreach($this->_languages as $lang) {
                $this->_labels[$lang] = Zend_Registry::get('Zend_Translate')->translate($prefix.$this->_id, $lang);       
            }
        }
        
    }
    
    /**
     * Retourne le label dans la langue demandée
     * @param string $lang
     * @return string
     */
    public function getLabel($lang)
    {
        return isset($this->_labels[$lang]) ? $this->_labels[$lang] : '';
    }
    
	/**
     * Récupération des labels de la page
     * @return array
     */
    public function getLabels()
    {
        return $this->_labels;
    }
    
	/**
     * Initialisation du label de la page
     * @param string $label
     * @param string $lang
     */
    public function setLabel($label, $lang)
    {
        $this->_labels[$lang]   =   $label;
    }
    
    /**
     * Initialisation des labels
     * @param array $labels
     */
    public function setLabels ($labels)
    {
        if (is_string($labels)) {
        	foreach ($this->getLanguages() as $lang) {
        		$this->setLabel($labels, $lang);
        	}
        } else {
        	//Réinitialisation
        	$this->_labels = array();
        	foreach ($labels as $lang => $label) {
	            if ($label != '') {
	            	$this->setLabel($label, $lang);
	            }
	        }
        }
    }
    
    /**
     * Récupération des typdoc par défaut
     */
    private function getDefaultTypdoc()
    {
        $d_typdoc = Hal_Settings::getTypdoc();
        $arr = array();
        $i = 0;
        foreach($d_typdoc as $typdoc) {
            $arr[] = array(
            			'id' => $typdoc,
                        'type' => 'typdoc'
            );
            $i++;
        }
        
        return $arr;
    }
    
    /**
     * Liste des clés pour chaque typdoc enregistré
     */
    public function getUsingKeys()
    {
        
        if (file_exists(SPACE.CONFIG.'typdoc.json')) {
            $file = SPACE.CONFIG.'typdoc.json';
        } else if (file_exists(APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'typdoc.json')) {
            $file = APPLICATION_PATH . "/../" . LIBRARY . THESAURUS .'typdoc.json';
        } else {
            return false;   
        }
                
        $json = file_get_contents($file);
        $typdocs = Zend_Json::decode($json);
        
        $keys = array();
        
        foreach ($typdocs as $key => $typdoc) {
            $keys[] = $key;
            
            if (isset($typdoc['children']) && count($typdoc['children']) > 0) {
                foreach ($typdoc['children'] as $key2 => $child) {
                    $keys[] = $key2;
                }
            }
        }
        
        return $keys;
    }
    
    /**
     * Liste des typdoc
     */
    public function getTypdoc() {
                
        if ($this->_sid) {

            if (file_exists(SPACE.CONFIG.'typdoc.json')) {

                $json = file_get_contents(SPACE.CONFIG.'typdoc.json');
                $typdocs = Zend_Json::decode($json);

            } else if ($serialTypdoc = Hal_Site_Settings_Portail::getTypdocs() != '') {
                 $typdocs = unserialize($serialTypdoc);

            } else {
                $json = file_get_contents(APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'typdoc.json');
                $typdocs = Zend_Json::decode($json);
            }

            $list = array();
            foreach ($typdocs as $typdoc) {
                $td = new self(array('id' => $typdoc['id'], 'sid' => $this->_sid, 'languages' => $this->getLanguages()));
                
                if ($typdoc['type'] == 'category') {
                    $td->setOptions(array('type' => 'category'));
                }

                if (isset($typdoc['children']) && is_array($typdoc['children'])) {
                    foreach ($typdoc['children'] as $child) {
                        $td->children[] = new self(array('id' => $child['id'], 'sid' => $this->_sid, 'languages' => $this->getLanguages()));
                    }
                }

                $list[] = $td;
            }

            return $list;

        }

        return array();
    }
    
    public function haveChildren ()
    {
        return is_array($this->children) && count($this->children) > 0;
    }
    
    public function toArray()
    {
        return array(
            'sid' => $this->getSid(),
            'languages' => $this->getLanguages(),
            'labels' => $this->getLabels()
        );
    }
    
    public static function save($sid, $typdocs) 
    {
        // Préparation et enregistrement du fichier de langues.
        $lang = array();
        $json = array();
        $usingKeys = array();

        foreach ($typdocs as $key => $typdoc) {
            if (!isset($typdoc['id']) || !$typdoc['id'] || in_array($typdoc['id'], $usingKeys)) {
                continue;
            }
            $usingKeys[] = $typdoc['id'];
            $lang['typdoc_'.$typdoc['id']] = $typdoc['labels'];
            $json[$typdoc['id']] = array('id' => $typdoc['id'], 'type' => $typdoc['type'], 'label' => 'typdoc_'.$typdoc['id']);
            if (isset($typdoc['children']) && is_array($typdoc['children'])) {
                foreach ($typdoc['children'] as $child) {
                    $lang['typdoc_'.$child['id']] = $child['labels'];
                    $json[$typdoc['id']]['children'][$child['id']] = array('id' => $child['id'], 'type' => $child['type'], 'label' => 'typdoc_'.$child['id']);
                }
            }
        }

        $writer = new Ccsd_Lang_Writer($lang);
		$writer->write(SPACE . LANGUAGES, 'typdoc'); 
        
        // Enregistrement en base de tous les typdoc pour le portail en cours.
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete('PORTAIL_SETTINGS', "SID = ".$sid." AND SETTING LIKE 'TYPDOC'");
        $db->insert('PORTAIL_SETTINGS', array(
        									'SID' => $sid,
        									'SETTING' => 'TYPDOC',
        									'VALUE' => serialize($json)
                                        ));
        
        // Enregistrement du json.
		$filename = SPACE.CONFIG.'typdoc.json';
		$dir = substr($filename, 0, strrpos($filename, '/'));
		if (! is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		file_put_contents($filename, Zend_Json::encode($json));
    		
    }
    
    public function initForm()
    {
    	unset($this->_form);
    	$this->_form = new Ccsd_Form();
    }
    
    public function getFormElement ($no_labels = false) {
        
        if (!$this->getId()) {
            return false;
        }
        $form = new Ccsd_Form();
        
        $languages = $this->getLanguages();
        $languages = array_intersect_key(Zend_Locale::getTranslationList('language'), array_flip($languages));

        $elem = new Ccsd_Form_Element_MultiTextSimpleLang($this->getId(), array(
			'required' => true, 
            'class' => 'required',
            'label' => 'Nom du typdoc',
			'value' => $no_labels ? array(): $this->getLabels(), 
			'populate'	=> $languages, 
			'belongsTo' => 'typdoc',
			'validators' => array(new Ccsd_Form_Validate_RequiredLang(array ('langs' => $this->getLanguages())))));
        
        $form->addElement($elem);
        
        $form->removeDecorator('Form');
        
        $this->_form = $form;

		return $this->_form;
    }

    public static function init($sid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete('PORTAIL_SETTINGS', "SID = ".$sid." AND SETTING = 'TYPDOC'");
        @unlink(SPACE . CONFIG . 'typdoc.json');
        foreach(Zend_Registry::get('languages') as $lang) {
            @unlink(SPACE . LANGUAGES . $lang . '/typdoc.php');
        }

    }

}