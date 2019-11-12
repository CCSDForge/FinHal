<?php

require_once(__DIR__.'/Settings/Portail.php');
require_once(__DIR__.'/../Site.php');

/**
 * Class Hal_Site_Portail
 * @method Hal_Site_Settings_Portail getSettingsObj
 */
class Hal_Site_Portail extends Hal_Site
{

    const TABLE_DOMAIN = 'PORTAIL_DOMAIN';
    const MODULE       = 'portail';  // caracterisation du module: peut etre utiliser dans les PATHs
    const DEFAULT_CONFIG_PATH = DEFAULT_CONFIG_ROOT . self::MODULE;
    const CACHE_MODULE_PATH   = CACHE_ROOT . '/'. APPLICATION_ENV . '/' . self::MODULE;
    const DEFAULT_CACHE_PATH  = self::CACHE_MODULE_PATH . '/' . SPACE_DEFAULT;
    const MODULE_PATH         = SPACE_DATA . '/' . self::MODULE;

    const SITE_INSERM  = 11;

    /**
     * Type de site (collection / portail)
     * @var string
     */
    protected $_type = self::TYPE_PORTAIL;
    /**
     * @var Hal_Site_Settings_Portail
     */
    protected $_settings = null;

    /**
     * Hal_Site_Portail constructor.
     * @param $infos
     * @param bool $full
     */
    public function __construct($infos, $full = false)
    {
        // todo : tester la valider des infos (genre type=PORTAIL) sinon renvoyer une exception
        $this->setParams($infos, $full);
        $this -> _prefixUrl = '/';
        $this -> _spaceUrl = $this -> _prefixUrl . "/public/";
        parent::__construct($infos);
    }

    /**
     * Hal_Site_Portail constructor.
     * @param Hal_Site_Collection $coll
     * @return Hal_Site_Portail
     */
    public static function findByAssociatedCollection($coll)
    {
        $colSid = $coll->getSid();
        $portailId = Hal_Site_Collection::getAssociatedPortail($colSid);
        if ($portailId) {
            return Hal_Site::loadSiteFromId($portailId);
        } else {
            return null;
        }
    }


    /**
     * @return string
     */
    public function getPrivilegeUserRight()
    {
        return Hal_Acl::ROLE_ADMIN;
    }

    /**
     * @param $settings
     */
    public function setSettings($settings)
    {
        if ($settings instanceof Hal_Site_Settings_Portail) {
            $this->_settings = $settings;
        } else {
            $this->_settings = new Hal_Site_Settings_Portail($this, $settings);
        }

        $this->_settingsLoaded = true;
    }

    /**
     * Chargement des settings depuis la BDD
     */
    public function loadSettings()
    {
        if (!$this->_settingsLoaded) {
            $this->_settings = Hal_Site_Settings_Portail::loadFromSite($this);
            $this->_settingsLoaded = true;
        }
    }

    /**
     * Retourne la liste des fichieres de configuration éditables via l'interface
     * @return array
     */
    public function getSettingFiles()
    {
        return $this->getSettingsObj()->getSettingFiles();
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function getSettingFilename($fileId)
    {
        return $this->getSettingsObj()->getSettingFilename($fileId);
    }

    /**
     * @param $fileId
     * @return mixed
     */
    public function getConfigFileContent($fileId)
    {
        return $this->getSettingsObj()->getConfigFileContent($fileId);
    }

    /**
     * @param $fileId
     * @param $content
     * @return mixed
     * @throws Zend_Exception
     */
    public function saveConfigFile($fileId, $content)
    {
        return $this->getSettingsObj()->saveConfigFile($fileId, $content);
    }

    /**
     * On récupère les métas données locales pour ce portail
     * @return array
     * @throws Zend_Config_Exception
     */
    public function getConfigMeta()
    {
        return $this->getSettingsObj()->getConfigMeta();
    }

    /**
     * @return array
     */
    public function getDomainsFromDb()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_DOMAIN, 'ID')
            ->where('SID = ?', $this->getSid());

        return $db->fetchCol($sql);
    }

    /**
     * @param array
     * @throws Zend_Db_Adapter_Exception
     */
    public function setDomainsInDb($domains)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        foreach ($domains as $domain) {
            $db->insert(self::TABLE_DOMAIN, ['SID' => $this->getSid(), 'ID' => $domain]);
        }
    }

    /**
     * Copie des settings des fichiers de configurations concernant le dépôt
     * @param Hal_Site $receiver
     * @throws Zend_Db_Adapter_Exception
     */
    public function copySubmitSettings(Hal_Site $receiver)
    {
       /**
         * @var Hal_Site_Portail $receiver
         * On copie les settings d'un portail vers un autre portail
         */
        if (! $receiver instanceof Hal_Site_Portail) {
            // Pas de sens de copier les settings portail vers une collection
            return;
        }

        $source = $this->getRootPath() . CONFIG . 'meta.ini';
        $dest = $receiver->getRootPath() . CONFIG . 'meta.ini';
        copy($source, $dest);

        $source = $this->getRootPath() . CONFIG . 'typdoc.json';
        $dest = $receiver->getRootPath() . CONFIG . 'typdoc.json';
        copy($source, $dest);

        $source = $this->getRootPath() . CONFIG . 'submit.ini';
        $dest = $receiver->getRootPath() . CONFIG . 'submit.ini';
        copy($source, $dest);

        $receiver->setDomainsInDb($this->getDomainsFromDb());
    }

    /**
     * @param Hal_Site_Portail[] $array
     * @return Hal_Site_Portail[]
     */
    static function array2indexedArray($array) {
        $res = [];
        foreach ($array as $portail) {
            $res[$portail -> getShortname()] = $portail;
        }
        return $res;
    }
    /**
     * liste des instances de la plateforme
     *
     * @return array
     * @deprecated  preferer
     *      @see getInstancesObj    prefered form
     */
    static public function getInstances()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, ['SID', 'SITE', 'NAME', 'URL', 'CONTACT'])->where("TYPE = '" . self::TYPE_PORTAIL . "'")->order("DATE_CREATION ASC");
        return $db->fetchAll($sql);
    }

    /**
     * liste des instances de la plateforme
     *
     * @return Hal_Site_Portail []
     */
    static public function getInstancesObj()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, ['SID', 'SITE', 'NAME', 'URL', 'CONTACT'])->where("TYPE = '" . self::TYPE_PORTAIL . "'")->order("DATE_CREATION ASC");
        $liste=$db->fetchAll($sql);
        $t_obj =  array_map(function($array) { return new Hal_Site_Portail($array); }, $liste);
        return static::array2indexedArray($t_obj);
    }

    /**
     * Renvoi tous les portails visibles
     * @return array
     */
    static public function getVisibleInstances()
    {
        // todo : faire une jointure !
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql2 = $db->select()->from(Hal_Site_Settings_Portail::TABLE, 'SID')->where('SETTING = "VISIBILITY"')->where('VALUE = "HIDDEN"');
        $sql = $db->select()->from(self::TABLE)->where('TYPE="PORTAIL"')->where('SID NOT IN ?', $sql2);

        return $db->fetchAll($sql);
    }

    /**
     * Retourne la liste des SITEID des collections rattachées à un portail
     * @param bool $auto
     * @return array
     */
    static public function getPortailsCollectionsSid($auto = true)
    {
        return Hal_Site_Settings_Portail::getPortailsCollectionsSid($auto);
    }

    /**
     * Indique si un portail est un portail/collection
     * @param $sid
     * @return array
     */
    static public function getSidCollection($sid)
    {
        return Hal_Site_Settings_Portail::getPortailsCollectionsSid($sid);
    }

    /**
     * Vérifie l'existance d'un portail à partir de son URL
     * @param $url
     * @return bool
     */
    static public function existFromUrl($url)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, 'URL')->where('TYPE="PORTAIL"')->where('URL = ?', 'http://'.$url)->orWhere('URL = ?', 'https://'.$url);
        $result = $db->fetchRow($sql);

        return $result != null;
    }

    /**
     * @return bool
     */
    public function submitAllowed()
    {
        //pas de droit pour les portails sans dépôt
        $oSettings = $this->getSettingsObj();
        return $oSettings->getSubmitAllowed();
    }
    /**
     * @return Hal_Site
     */
    public function isPatrolled() {
        if ($this->getSetting('patrol')) {
            /** @var Hal_Site_Settings_Portail $settings */
            $settings = $this->getSettingsObj();
            $collection = $settings->getAssociatedColl();
            return $collection;
        } else {
            return null;
        }
    }

    /**
     * @return Hal_Site_Collection
     */
    public function getAssociatedColl() {
        $settings = $this->getSettingsObj();
        $collection = $settings->getAssociatedColl();
        return $collection;
    }
    /**
     * @param string|Hal_Document $doc
     *
     * La mise a jour du patrouillage ne depends pas du Portail
     * Seulement de la collection du document
     *
     * @throws Zend_Db_Adapter_Exception
     */
    public function patrolMaybe($doc) {
        if ($this->getSetting('patrol')) {
            /** @var Hal_Site_Settings_Portail $settings */
            $collection = $this->getAssociatedColl();
            if ($collection) {
                // Il y a une collection associee... c'est bon
                // La collection associee au portail implemente le patrouillage
                if (is_int($doc)) {          // un docid
                    $identifiant = Hal_Document::getIdFromDocid($doc);
                } elseif (is_string($doc)) { // un vrai identifiant
                    $identifiant = $doc;
                } elseif (is_object($doc) && (get_class($doc) == "Hal_Document")) {
                    $identifiant = $doc->getId();
                } else {
                    Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Param must be an document  identifier or a Document object");
                    return;
                }
                $patrol = Hal\Patrol::construct($identifiant, $collection);
                $patrol->save();
            }
        }
    }
    /** @var array
     * TODO : to put elsewhere ( in config ? maybe not...)
     */
    static $embargo2dateargs = [
        '15-d'=> '+15 days',
        '1-M' => '+1 months',
        '3-M' => '3 months',
        '6-M' => '+3 months',
        '1-Y' => '+1 years',
        '2-Y' => '+2 years',
        '4-Y' => '+4 years',
        '5-Y' => '+5 years',
        '6-Y' => '+6 years',
        '10-Y'=>'+10 years',
    ];
    /**
     * Return the max date allowed for this Portal
     * Return format = %Y-%m-%d
     * @param string $typdoc
     * @return string
     */

    public function getMaxEmbargo($typdoc = 'DEFAULT') {
        $embargoSpec = Hal_Settings::getMaxEmbargo($typdoc);
        $now = strtotime('today UTC');
        if (array_key_exists($embargoSpec, self::$embargo2dateargs)) {
            return date('Y-m-d', strtotime(self::$embargo2dateargs[$embargoSpec], $now));
        }
        switch ($embargoSpec) {
            case 'none':  // no embargo allowed on this portal
                return date('Y-m-d', $now);
                break;
            case 'never': // infinite embargo allowed on this portal
            case '100-Y': // synonyme
                return '9999-12-31';
                break;
            default:
                return date('Y-m-d', strtotime('+2 years', $now));
        }
    }

}

