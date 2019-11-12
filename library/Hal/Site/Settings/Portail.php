<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 18/01/18
 * Time: 14:05
 */

require_once(__DIR__.'/../Settings.php');
require_once(__DIR__.'/../Collection.php');
require_once(__DIR__.'/../../Site.php');
require_once(__DIR__.'/../../Ini.php');

/**
 * Class Hal_Site_Settings_Portail
 */
class Hal_Site_Settings_Portail extends Hal_Site_Settings
{
    const TABLE = 'PORTAIL_SETTINGS';

    
    /**
     * liste des fichiers de configuration d'un portail
     * @var array
     *
     * Est-ce qu'il faudrait pas faire une correspondance
     * META => meta.ini
     * RESEARCH => solr.hal.etc
     * TRAD_META => [fr=>fr/meta.php, en=>en/meta.php]
     * etc
     *
     * avec un tableau CONFIGS_DISPO => [META, RESEARCH, TRAD_META, etc]
     *
     */
    protected $_files = [];

    static protected $_defaultFiles = [];

    /** @var string liste des types de documents principaux pour affichage dans les widgets */
    static protected $mainTypdocs = '';

    /** @var string liste sérialisée des types de documents du portail */
    static protected $typdocs = '';

    /** @var bool : indique si le site est patrouille */
    private $patrolled = false;

    /** @var string */
    private $googleToken = '';

    /**
     * Hal_Site_Settings_Portail constructor.
     * @param Hal_Site $site
     * @param array $data
     */
    public function __construct(Hal_Site $site, $data = [])
    {
        parent::__construct($site, $data);

        $this->setPiwikid(Ccsd_Tools::ifsetor($data['piwikid'], 0));

        //Initialisation des fichiers de configurtion
        $this->_files = self::$_defaultFiles = [
            $this->getRootPath() . CONFIG . INSTANCEPREFIX . 'meta.ini',
            $this->getRootPath() . CONFIG . 'solr.hal.defaultFilters.json',
            $this->getRootPath() . LANGUAGES . 'fr/meta.php',
            $this->getRootPath() . LANGUAGES . 'en/meta.php'
        ];
    }

    /**
     * @param Hal_Site
     * @return Hal_Site_Settings
     */
    static public function loadFromSite(Hal_Site $site)
    {
        $settings = new Hal_Site_Settings_Portail($site);
        $settings->setSubmitAllowed(true);

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sid = $site->getSid();
        $sql = $db->select()->from(self::TABLE, array('SETTING', 'VALUE'))->where('SID = ?', $sid);
        foreach ( $db->fetchPairs($sql) as $setting => $value ) {
            switch ($setting) {
                case 'COLLECTION' :
                    $settings->setAssociatedsiteId($value);
                    break;
                case 'VISIBILITY' :
                    $visibility = $value == 'HIDDEN' ? false : true;
                    $settings->setVisibility($visibility);
                    break;
                case 'SUBMIT' :
                    if ($value == "NO") {
                        $settings->setSubmitAllowed(false);
                    }
                    break;
                // types de documents du portail
                case 'TYPDOC' :
                    $settings->setTypdocs($value);
                    break;
                // types de documents principaux pour affichage dans les widgets Derniers documents, Dernières publication, Compteurs
                case 'MAIN_TYPDOC' :
                    $settings->setMainTypdocs($value);
                    break;
                case 'PATROL':
                    // Le site souhaite afficher les fonctionnalite de patrouillage
                    // Devrait etre remonter a Hal|Site
                    $settings->setPatrol($value);
                    break;
                case 'GOOGLE':
                    // Le site souhaite afficher les fonctionnalite de patrouillage
                    // Devrait etre remonter a Hal|Site
                    $settings->setGoogleToken($value);
                    break;
                default :
                    $portalname = $site->getShortname();
                    Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Unknow Portal setting: $setting for portal $portalname ($sid)");
                    break;
            }
        }

        $settings->load($site);
        return $settings;
    }

    /**
     * @return int
     * @throws Zend_Db_Adapter_Exception
     */
    public function save()
    {
        parent::save();

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete(self::TABLE, 'SID = ' . $this->getSid());

        // todo : sauvegarder les fichiers de configuration ??

        $res = $db->insert(self::TABLE, ['SID' => $this->getSid(), 'SETTING' => 'PIWIKID', 'VALUE' => $this->getPiwikid()]);
        $res = $res && $db->insert(self::TABLE, ['SID' => $this->getSid(), 'SETTING' => 'COLLECTION', 'VALUE' => $this->getAssociatedsiteId()]);
        return  $res && $db->insert(self::TABLE, ['SID' => $this->getSid(), 'SETTING' => 'VISIBILITY', 'VALUE' => $this->getVisibility()]);
    }

    /**
     * @param string $token
     */
    public function setGoogleToken($token) {
        $this->_googleToken = $token;
    }

    /**
     * @return string
     */
    public function getGoogleToken() {
        return $this->_googleToken;
    }
    /**
     * Retourne la liste des fichieres de configuration éditables via l'interface
     * @return array
     */
    public function getSettingFiles()
    {
        return $this->_files;
    }

    /**
     * Retourne le chemin vers un fichier
     * @param string
     * @return array | false
     */
    public function getSettingFilename($fileId)
    {
        //todo : y a une correspondance clé=>valeur dans _files ?

        if (isset($this->_files[$fileId])) {
            return $this->_files[$fileId];
        }
        return false;
    }

    /**
     * @param Hal_Site_Settings $settings
     * @throws Zend_Db_Adapter_Exception
     */
    public function duplicate(Hal_Site_Settings $settings)
    {
        parent::duplicate($settings);

        if (!($settings instanceof Hal_Site_Settings_Portail)) {
            return;
        } else {
            $settings->setParams($this->toAssocArray());
            $settings->save();
        }
    }

    /**
     * @return array
     */
    public function toAssocArray()
    {
        return ['piwikid' => $this->getPiwikid()];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'piwikid' => $this->_piwikid,
            'collection' => $this->_associatedsiteId
        ];
    }

    /**
     * Retourne le contenu d'un fichier de configuration
     * @param $fileId
     * @return string
     */
    public function getConfigFileContent($fileId)
    {
        $filepath = $this->getSettingFilename($fileId);
        if (is_file($filepath)) {
            return file_get_contents($filepath);
        }
        return '';
    }

    /**
     * Enregistrement du contenu d'un fichier de config
     * @param $fileId
     * @param $content
     * @return int|string
     * @throws Zend_Exception
     */
    public function saveConfigFile($fileId, $content)
    {
        // todo : $fileid dans CONFIGS_DISPO

        $filename = $this->getSettingFilename($fileId);
        $res = file_put_contents($filename, $content);
        if ($res) {
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $error = false;
            if ($ext == 'php') {
                $res = shell_exec('/opt/php5/bin/php -l ' . $filename);
                $error = strpos($res, 'Errors parsing') !== false;
            } else if ($ext == 'json') {
                json_decode($content);
                $error = json_last_error() != JSON_ERROR_NONE;
            } else if ($ext == 'ini') {
                $error = parse_ini_file($filename) === false ;
            }

            if ($error) {
                unlink($filename);
                throw new Zend_Exception("La syntaxe du fichier " . $ext . " n'est pas valide");
            }
        }
        return $res;
    }

    /**
     * Retourne le formulaire d'édition des fichiers de conf
     * @return Ccsd_Form
     * @throws Zend_Form_Exception
     */
    static public function getFormConfEdition()
    {
        $form = new Ccsd_Form();
        $form->setAttrib('id', 'form-files');
        $form->setAttrib('class', 'form-horizontal');

        $form->addElement('select', 'file', array(
            'label'		=> "Fichier",
            'class'		=> "form-control",
            'multioptions'	=> self::$_defaultFiles
        ));

        $form->addElement('textarea', 'content', array(
            'label'		=> "Contenu",
            'class'		=> "form-control",
            'rows'	=> 20,
        ));

        return $form;
    }

    /**
     * On récupère soit les métas données locales pour ce portail soit les metas par défaut si rien de local
     * @return array
     * @throws Zend_Config_Exception
     */
    public function getConfigMeta()
    {
        $path = SPACE_DATA . '/' . SPACE_PORTAIL . '/' . $this->getSiteShortName() . '/' . CONFIG . 'meta.ini';

        if(!is_readable($path)) {
            $path = Hal_Site_Portail::DEFAULT_CONFIG_PATH . '/meta.ini';
        }

        $ini = new Hal_Ini($path, ['section_default' => 'metas']);
        return $ini->toArray();
    }

    /**
     * @return Hal_Site_Collection
     */
    public function getAssociatedColl()
    {
        return $this->getAssociatedsite();
    }
    /**
     * @return int
     */
    public function getAssociatedCollId()
    {
        return $this->getAssociatedsiteId();
    }

    /**
     * Retourne la liste des SITEID des collections rattachées à un portail
     *
     * @param bool : config collection tamponnage auto
     * @return array
     */
    static public function getPortailsCollectionsSid($auto = true)
    {
        //todo : revoir

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(array('p' => self::TABLE), 'VALUE')
            ->from(array('s' => Hal_Site::TABLE), null)
            ->from(array('c' => Hal_Site_Collection::TABLE), null)
            ->where('s.SID = p.SID')
            ->where('s.TYPE = "PORTAIL"')
            ->where('p.SETTING = "COLLECTION"')
            ->where('p.VALUE = c.SID');
        if ($auto) {
            $sql->where('c.SETTING = ?', 'mode');
            $sql->where('c.VALUE = ?', 'auto');
        }

        return $db->fetchCol($sql);
    }

    /**
     * Indique si un portail est un portail/collection
     * @deprecated
     * @param int
     * @return string
     */
    static public function getSidCollection($sid)
    {
        // todo : est-ce que c'est pas la même chose que getAssociatedCollection ??
        // renvoyer this->_collection ?


        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, 'VALUE')
            ->where('SETTING = "COLLECTION"')
            ->where('SID = ?', $sid);
        return $db->fetchOne($sql);
    }

    /**
     * Fixe la liste des types de documents principaux pour affichage dans les widgets
     *
     * @param string $setting : liste des types de document sérialisée
     *
     * @return $this
     */
    private function setMainTypdocs($setting)
    {
        if (is_string($setting)) {
            self::$mainTypdocs = $setting;
        }
        return $this;
    }

    /**
     * Retourn la liste sérialisée des types de documents principaux pour affichage dans les widgets
     *
     * @return string
     */
    static public function getMainTypdocs()
    {
        return self::$mainTypdocs;
    }

    /**
     * Fixe la liste des types de documents du portail
     *
     * @param string $setting : liste des types de document sérialisée
     *
     * @return $this
     */
    private function setTypdocs($setting)
    {
        if (is_string($setting)) {
            self::$typdocs = $setting;
        }
        return $this;
    }

    /**
     * Retourne la liste sérialisée des types de documents principaux pour affichage dans les widgets
     *
     * @return string
     */
    static public function getTypdocs()
    {
        return self::$typdocs;
    }

    /**
     * @param bool $value
     */
    public function  setPatrol($value) {
        $this->patrolled = $value ? true : false;
    }

    /**
     * Use isPatrolled instead
     * Define to be called by getSettings('patrol')
     * @param bool $value
     */
    protected function getPatrol() {
        return $this->patrolled;
    }

    /**
     * @return bool
     */
    public function isPatrolled() {
        return $this->patrolled;
    }
}