<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 17/01/18
 * Time: 10:23
 */

require_once(__DIR__.'/Site/Settings.php');
require_once(__DIR__.'/Site/Collection.php');
require_once(__DIR__.'/Site/Portail.php');
require_once(__DIR__.'/Acl.php');
require_once(__DIR__.'/Website/Header.php');
require_once(__DIR__.'/Website/Navigation.php');
require_once(__DIR__.'/Website/Footer.php');
require_once(__DIR__.'/Website/Search.php');
require_once(__DIR__.'/Website/Style.php');

/**'
 * Class Hal_Site
 */
abstract class Hal_Site
{

    const TABLE = 'SITE';

    const DEFAULT_SITE = 1;
    /**
     * Champs de la table
     */
    const SID	    = 'SID';
    const TYPE	    = 'TYPE';
    const SHORTNAME = 'SITE';
    const PREFIX    = 'ID';
    const URL       = 'URL';
    const FULLNAME  = 'NAME';
    const CATEGORY	= 'CATEGORY';
    const DATE_CREATION	='DATE_CREATION';
    const CONTACT   = 'CONTACT';
    const IMAGETTE  = 'IMAGETTE';

    /**
     * Types de sites dispo
     */
    const TYPE_PORTAIL = 'PORTAIL';
    const TYPE_COLLECTION = 'COLLECTION';
    const TYPE_UNDEFINED = 'UNDEFINED';
    /**
     * @var Hal_Site $current
     */
    static private $_current = null;
    /**
     * NOT IMPLEMENTED: NOT INITIATED YET: Is there a need for that?
     * Contain the entry portail
     * Do not confuse with current Website which can be portal or collection
     *    current wibsite is in $current
     * Value is different of _current in case of a collection context
     *    In portail context, both value $_current and $_currentPortail are the same
     * @see Hal_Site::$_current
     */
    static private $_currentPortail = null;

    /**
     * Identifiant du site
     * @var int
     */
    protected $_sid = 0;

    /**
     * Type de site (collection / portail)
     * @var string
     */
    protected $_type = self::TYPE_UNDEFINED;

    /**
     * Nom court du site
     * @var string
     */
    protected $_site = "";

    /**
     * Préfix des dépôts dans un portail
     * @var string
     */
    protected $_id = '';

    /**
     * Url du site
     * @var string
     */
    protected $_url = "";

    /**
     * Nom complet du site
     * @var string
     */
    protected $_name = "";

    /**
     * Catégories de sites dispo
     * TODO: Hum, cela devrait etre de la config, pas du code
     */
    const CAT_INSTITUTION = 'INSTITUTION';
    const CAT_THEME = 'THEME';
    const CAT_PRES = 'PRES';
    const CAT_UNIV = 'UNIV';
    const CAT_ECOLE = 'ECOLE';
    const CAT_LABO = 'LABO';
    const CAT_COLLOQUE = 'COLLOQUE';
    const CAT_REVUE = 'REVUE';
    const CAT_AUTRE = 'AUTRE';
    const CAT_SET = 'SET';
    const CAT_COMUE = 'COMUE';

    public $ListCategories = [
        self::CAT_INSTITUTION,
        self::CAT_THEME,
        self::CAT_PRES,
        self::CAT_UNIV,
        self::CAT_ECOLE,
        self::CAT_LABO,
        self::CAT_COLLOQUE,
        self::CAT_REVUE,
        self::CAT_AUTRE,
        self::CAT_SET,
        self::CAT_COMUE
    ];

    /**
     * Catégorie de la structure créatrice du site
     * @var string
     * TODO: La definition de la categorie par defaut devrait etre de la config
     */
    protected $_category = self::CAT_INSTITUTION;

    /**
     * Date de création du site
     * @var string
     */
    protected $_creationDate = "";

    /**
     * Adresse mail de contact du gestionnaire du site
     * @var string
     */
    protected $_contact = "";

    /**
     * Imagette associée au site
     * @var null
     */
    protected $_imagette = null;

    /**
     * @var Hal_Site_Settings
     */
    protected $_settings = null;

    /**
     * Permet un fonctionnement lazy de chargement des parents
     * @var bool
     */
    protected $_settingsLoaded = false;

    const DUPLICATE_SITE = 'dp_site';
    const DUPLICATE_SETTINGS = 'dp_settings';
    const DUPLICATE_FILES = 'dp_files';
    const DUPLICATE_SUBMIT = 'dp_submit';
    const DUPLICATE_NAVIGATION = 'dp_navigation';
    const DUPLICATE_FOOTER = 'dp_footer';
    const DUPLICATE_HEADER = 'dp_header';
    const DUPLICATE_STYLE = 'dp_style';
    const DUPLICATE_SEARCH = 'dp_search';
    const DUPLICATE_RIGHTS = 'dp_rights';

    private $_availableDpSettings = [self::DUPLICATE_SITE, self::DUPLICATE_SETTINGS, self::DUPLICATE_FILES, self::DUPLICATE_SUBMIT, self::DUPLICATE_NAVIGATION, self::DUPLICATE_FOOTER, self::DUPLICATE_HEADER, self::DUPLICATE_STYLE, self::DUPLICATE_SEARCH, self::DUPLICATE_RIGHTS];

    /** Remplacement des constantes */
    const MODULE          = 'NOT_DEFINED';   # portail ou collection
    const DEFAULT_CONFIG_PATH = 'NOT_DEFINED'; #
    const CACHE_MODULE_PATH   = 'NOT_DEFINED'; #
    const DEFAULT_CACHE_PATH  = 'NOT_DEFINED'; #
    const SPACE_DEFAULT = 'default';

    protected $_siteUrl   = 'NOT_DEFINED';  #  TODO: PAS ENCORE UTILISE MAIS CE SERAIT BIEN
    // Init dans constructeur
    protected $_spaceName = 'NOT_DEFINED'; # Composante du portail/Collection a utiliser SEULEMENT dans les Path (fichier ou Url)
    protected $_sitename  = 'NOT_DEFINED'; # Composante du portail/Collection a utiliser en dehors des Path
    protected $_space     = 'NOT_DEFINED'; # Path complet pour le root du site
    protected $_pathPages = 'NOT_DEFINED'; #
    protected $_cachePath = 'NOT_DEFINED';        #
    // Init dans constructeur sous class
    protected $_prefixUrl = 'NOT_DEFINED';
    protected $_spaceUrl  = 'NOT_DEFINED'; #
    /**
     * Hal_Site constructor.
     * @param $infos
     * @param bool $full
     */
    public function __construct($infos, $full = false)
    {
        // Il faut, prendre les params et faire les calculs des path
        // Mais il faut que les calculs soit fait d'abord dans les sous classe...
        $this->setParams($infos, $full);

        $this->setCreationDate(Ccsd_Tools::ifsetor($infos[self::DATE_CREATION], ''));
        //  Les path suivant se terminent par /
        $this -> _spaceName = $this -> _site;
        $this -> _sitename  = $this -> _name;
        $this -> _space     = SPACE_DATA . '/'. static::MODULE . '/' . $this->_spaceName . '/' ;
        $this -> _pathPages = $this -> _space . '/' . PAGES;
        $this -> _cachePath = static::CACHE_MODULE_PATH . '/' .$this->_site;
    }

    /**
     * @param $params
     * @param bool $full
     * @uses setSid(), settype() , setUrl(), setShortname(), setFullname(), setImagette()
     *
     *
     */
    public function setParams($params, $full = false)
    {
        foreach ($params as $key => $value) {

            $methodName = 'set'.ucfirst(strtolower($key));

            if (method_exists ( get_class($this) ,  $methodName)) {
                $this->{$methodName}($value);
            }
            // else {
            // todo : faut-il renvoyer une exception ?
            // BM: non, et meme logger semble difficile car cela arrive souvent.
            // }
        }

        if ($full) {
            // Chargement des settings spécifique à la sous-classe (Portail ou Collection)
            $this->setSettings($params);
        }
    }

    /**
     * @param $sid
     * @return Hal_Site_Collection|Hal_Site_Portail|null
     */
    static public function loadSiteFromId($sid)
    {
        if ( ! $sid) {
            return null;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE)
            ->where(self::SID.' = ?', $sid);

        $infos = $db->fetchRow($sql);
        if ($infos) {
            return self::rowdb2Site($infos);
        }
        return null;
    }
    /**
     * @param string $name
     * @return Hal_Site_Collection|Hal_Site_Portail|null
     */
    static public function loadSiteFromName($name)
    {
        if ( ! $name) {
            return null;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE)
            ->where(self::SHORTNAME.' = ?', $name);
        $infos = $db->fetchRow($sql);
        if ($infos) {
            return self::rowdb2Site($infos);
        }
        return null;
    }
    /**
     * Transform Raw from DB select to Object
     * @param $infos
     * @return Hal_Site_Collection|Hal_Site_Portail|null
     */
    public static function rowdb2Site($infos) {
        switch ($infos[self::TYPE]) {
            case self::TYPE_PORTAIL :
                return new Hal_Site_Portail($infos);
            case self::TYPE_COLLECTION :
                return new Hal_Site_Collection($infos);
            default :
                return null;
        }
    }

    /**
     * Définition des constantes spécifiques à l'environnement
     */
    public function registerSiteConstants()
    {
        defined('SPACE_NAME') || define('SPACE_NAME', $this->getShortname());
        defined('SITENAME') || define('SITENAME', SPACE_NAME);
        defined('SPACE') || define('SPACE', SPACE_DATA . '/' . MODULE . '/' . $this->getShortname() . '/');
        defined('SITEID') || define('SITEID', $this->getSid());
    }
    
    /**
     * Attention tamponnate est spécifique aux collections... il faudrait penser à le sortir du save
     * @param bool
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     */
    public function save()
    {
        if ($this->getType() == self::TYPE_UNDEFINED) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "On essaie d'enregistrer un site de type UNDEFINED !!");
            return false;
        }

        $res = true;
        $bind = array(
            self::TYPE          =>  $this->getType(),
            self::SHORTNAME     =>  $this->getShortname(),
            self::PREFIX        =>  $this->getId(),
            self::URL           =>  $this->getUrl(),
            self::FULLNAME      =>  $this->getFullname(),
            self::CATEGORY      =>  $this->getCategory(),
            self::CONTACT       =>  $this->getContact(),
            self::IMAGETTE      =>  $this->getImagette()
        );

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        if ($this->getSid() == 0) {
            // Nouvelle collection
            $bind[self::DATE_CREATION] = date('Y-m-d');
            $res = $db->insert(self::TABLE, $bind);

            $this->_sid = $db->lastInsertId(self::TABLE);
        } else {
            // Modification
            $db->update(self::TABLE, $bind, self::SID.' = ' . $this->getSid());
        }

        return $res;
    }

    /**
     * Population de l'objet
     *
     * @param bool
     */
    public function load($register = false)
    {
        Ccsd_Tools::panicMsg(__FILE__, __LINE__, "ATTENTION CETTE FONCTION EST OBSOLETE ET NE DEVRAIT PAR CONSEQUENT PAS ETRE APPELE !!");
    }

    /**
     * Chargement des settings depuis la BDD
     */
    public function loadSettings()
    {
        // Sous-classer !!
    }
    /**
     * @return int
     */
    public function getSid()
    {
        return $this->_sid;
    }

   /**
     * @param  int $sid
     */
    protected function setSid($sid)
    {
        $this->_sid = (int) $sid;
    }

    /**
     * @deprecated : utiliser getFullname()
     * @return string
     */
    public function getName()
    {
        return $this->getFullname();
    }

    /**
     * @deprecated : utiliser setFullname()
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @deprecated
     * TODO : Comprendre pourquoi il faut nettoyer ici alors qu'on ne le fait pas dans getSiteName
     * @return string
     */
    public function getCode()
    {
        return Ccsd_Tools_String::stripCtrlChars($this->_site);
    }

    /**
     * @deprecated
     * @param $code
     */
    public function setCode($code)
    {
        $this->_site = $code;
    }

    /**
     * Retourne le nom court du site
     * @deprecated : utiliser getShortname()
     * @param int
     * @return string
     */
    public function getSiteName()
    {
        return $this->getShortname();
    }

    /**
     * Retourne le nom court du site
     *
     * @return string
     */
    public function getShortname()
    {
        return $this->_site;
    }
    /**
     * Set le nom court du site
     *
     * @param string $name
     */
    public function setShortname($name)
    {
        $this->_name = $name;
    }

    /**
     * Retourne le nom complet du site
     *
     * @param int
     * @return string
     */
    public function getFullname()
    {
        return $this->_name;
    }
    /**
     * Retourne le nom complet du site
     *
     * @param string
     */
    public function setFullname($fullname)
    {
        $this->_name = $fullname;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        // todo : Mettre un contrôle pour que ce soit une forme d'url

        $this->_url = $url;
        return $this;
    }

    /**
     * Retourne l'url du site
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        // todo : Mettre un contrôle pour que ce soit en minuscule avec un tiret au bout

        $this->_id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return mixed
     */
    public function getLanguages()
    {
        return $this->getSetting('languages');
    }

    /**
     * @param $setting
     * @return mixed
     */
    public function getSetting($setting)
    {
        $this->loadSettings();
        return $this->_settings->getSetting($setting);
    }

    /**
     * @return bool
     */
    public function areSettingsLoaded()
    {
        return $this->_settingsLoaded;
    }

    /**
     * @param $settings
     */
    public function setSettings($settings)
    {
        // Pour l'instant n'est pas sensé être appelé, on passe par les sous-classes Portail/Collection

        if ($settings instanceof Hal_Site_Settings) {
            $this->_settings = $settings;
        } else {
            $this->_settings = new Hal_Site_Settings($settings);
        }
        $this->_settingsLoaded = true;
    }

    /**
     * @return Hal_Site_Settings
     */
    public function getSettingsObj()
    {
        $this->loadSettings(); # Lasy load
        return $this->_settings;
    }

    /**
     * @return array
     */
    public function getSettingsArray()
    {
        $this->loadSettings(); # Lasy load
        return $this->getSettingsObj()->toArray();
    }

    /**
     * type du site
     *
     * @return string
     */
    public function getType()
    {
        return $this->_type;
    }

    /**
     * affectation du type du site
     *
     * @param string $type
     * @return Hal_Site
     */
    public function setType($type)
    {
        $this->_type = $type;
        return $this;

    }

    /**
     * @param $cat
     * @return $this
     */
    public function setCategory($cat)
    {
        // todo : Mettre un contrôle pour que n'accepter que des catégories connues

        $this->_category = $cat;
        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->_category;
    }

    /**
     * @param $creationDate
     * @return $this
     */
    public function setCreationDate($creationDate)
    {
        $this->_creationDate = $creationDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreationDate()
    {
        return $this->_creationDate;
    }

    /**
     * @param $contact
     * @return $this
     */
    public function setContact($contact)
    {
        // todo : Mettre un contrôle pour n'accepter que des adresses mails

        $this->_contact = $contact;
        return $this;
    }

    /**
     * @return string
     */
    public function getContact()
    {
        return $this->_contact;
    }

    /**
     * @param $imagette
     * @return $this
     */
    public function setImagette($imagette)
    {
        // todo : Mettre un contrôle pour n'accepter que des int

        $this->_imagette = $imagette;
        return $this;
    }

    /**
     * @return null
     */
    public function getImagette()
    {
        return $this->_imagette;
    }

    /**
     * @param string $site
     * @return Hal_Site
     */
    public function setSite($site)
    {
        $this->_site = $site;
        return $this;
    }

    /**
     * @deprecated : utiliser getShortname()
     * @return string
     */
    public function getSite()
    {
        return $this->getShortname();
    }


    /** Return the path of Collection on disk
     * @return string
     */
    public function getRootPath() {
        return $this->getSettingsObj()->getRootPath();
    }

    /**
     * @param $request
     * @param null $bind
     * @return array
     */
    function execandfetch($request, $bind=null) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $query = $db->prepare($request);
        $query->execute($bind);
        return $query->fetchAll();
    }

    /**
     * @param string $table
     * @param array $bind
     * @return string
     * @throws Zend_Db_Exception
     */
    function do_insert($table, $bind ) {
        /** @var Zend_Db_Adapter_Pdo_Mysql $db */
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->insert($table,  $bind);
        return $db->lastInsertId($table);
    }

    /**
     * @param Hal_Site $receiver
     * @throws Zend_Db_Adapter_Exception
     */
    public function duplicateSite(Hal_Site $receiver)
    {
        $oldId = $this -> getSid();
        $siterow = $this -> execandfetch("select * from ".self::TABLE." where SID=$oldId")[0];
        # Mise a jour Table SITE
        unset($siterow[self::SID]);
        unset($siterow[self::SHORTNAME]);
        unset($siterow[self::URL]);
        unset($siterow[self::FULLNAME]);
        $siterow [self::DATE_CREATION] = date("Y-m-d");

        $receiver->setParams($siterow);
        $receiver->save();
    }

    /**
     * @param Hal_Site
     * @throws Zend_Db_Adapter_Exception
     */
    public function duplicateSettings(Hal_Site $receiver) {

        $this->getSettingsObj()->duplicate($receiver->getSettingsObj());
    }

    /**
     * @return string
     */
    public function getPrivilegeUserRight()
    {
        return '';
    }

    /**
     *
     * Duplique les droits des utilisateurs particulier d'un site vers un autre sans supprimer les droits déjà existants
     * @param Hal_Site
     * @throws Zend_Db_Exception
     */
    public function duplicateUserRight(Hal_Site $receiver) {

        $fromRight = $this->getPrivilegeUserRight();
        $toRight = $receiver->getPrivilegeUserRight();

        if (empty($fromRight) || empty($toRight)) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, 'Copie de user rights sur un site de type inconnu !');
        } else {

            $oldId = $this->getSid();
            # Mise a jour Table USER_RIGHT
            $site_right = $this->execandfetch("select * from USER_RIGHT where SID=$oldId and RIGHTID='" . $fromRight . "'");

            foreach ($site_right as $right) {
                $receiver->do_insert('USER_RIGHT', [self::SID => $receiver->getSid(), "UID" => $right['UID'], "RIGHTID" => $toRight, "VALUE" => ""]);
            }
        }
    }

    /**
     * @param Hal_Site $receiver
     * @return bool|string
     */
    public function duplicateFiles(Hal_Site $receiver) {
        $source  = $this->getRootPath() . PUBLIC_DEF;
        $dest    = $receiver->getRootPath() . PUBLIC_DEF;
        if (file_exists($source)) {
            // TODO: pourquoi ne pas duplique le style ???
            $ok = Ccsd_Tools::copy_tree($source , $dest, 0644, 0755, [ ]);
            // $ok = Ccsd_Tools::copy_tree($source , $dest, 0644, 0755, [ '|/style.css|' ]);
        } else {
            return true;
        }
        // Pas de chown a faire dans le cas du Web
        // Je laisse cela si qq utilise la fonction dans un script, il faudra faire le chown...
        // system("/bin/chown -R nobody:nobody $dest");
        return $ok;
    }

    /**
     * @param Hal_Site
     * @return bool
     */
    public function duplicateWebSite(Hal_Site $receiver) {

        Hal_Website_Footer::duplicate($this, $receiver);
        Hal_Website_Header::duplicate($this, $receiver);
        Hal_Website_Navigation::duplicate($this, $receiver);
        Hal_Website_Search::duplicate($this, $receiver);
        Hal_Website_Style::duplicate($this, $receiver);
        $this -> duplicateFiles($receiver);
        return true;
    }

    /**
     * @param Hal_Site $receiver
     */
    public function copySubmitSettings(Hal_Site $receiver)
    {
        // Par défaut cette fonction ne fait rien. Elle fait des trucs dans le portail
    }

    /**
     * @param Hal_Site $receiver
     * @param $options
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Exception
     */
    public function duplicate(Hal_Site $receiver, $options = [])
    {
        if (empty($options)) {
            // S'il n'y a pas d'option, on duplique tout
            $options = $this->_availableDpSettings;
        }

        foreach ($this->_availableDpSettings as $setting) {
            if (!in_array($setting, $options)) {
                continue;
            }

            switch ($setting) {
                case self::DUPLICATE_SITE :
                    $this->duplicateSite($receiver);
                    break;
                case self::DUPLICATE_SETTINGS :
                    $this->duplicateSettings($receiver);
                    break;
                case self::DUPLICATE_FILES :
                    $this -> duplicateFiles($receiver);
                    break;
                case self::DUPLICATE_FOOTER :
                    Hal_Website_Footer::duplicate($this, $receiver);
                    break;
                case self::DUPLICATE_HEADER :
                    Hal_Website_Header::duplicate($this, $receiver);
                    break;
                case self::DUPLICATE_NAVIGATION :
                    Hal_Website_Navigation::duplicate($this, $receiver);
                    break;
                case self::DUPLICATE_SEARCH :
                    Hal_Website_Search::duplicate($this, $receiver);
                    break;
                case self::DUPLICATE_STYLE :
                    Hal_Website_Style::duplicate($this, $receiver);
                    break;
                case self::DUPLICATE_RIGHTS :
                    $this->duplicateUserRight($receiver);
                    break;
                case self::DUPLICATE_SUBMIT :
                    if ($this->getType() == self::TYPE_PORTAIL){
                        $this->copySubmitSettings($receiver);
                    }
                    break;
                default :
                    break;
            }
        }
    }

    /**
     * Retourne un type en fonction du nom $name
     * @param  string $name
     * @return string self::TYPE_COLLECTION | self::TYPE_PORTAIL
     */
    static function getTypeFromShortName($name)
    {
        return (preg_match('/^[A-Z0-9_-]+$/', $name)) ? self::TYPE_COLLECTION : self::TYPE_PORTAIL;
    }

    /**
     * Vérifie l'existence d'un site (en se basant sur son nom court)
     *
     * @param string $shortname
     * @param string $type
     * @param bool $load
     * @return bool|Hal_Site
     */
    static public function exist($shortname = '', $type = self::TYPE_PORTAIL, $load = true)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE)
            ->where('SITE = ?', $shortname)
            ->where('TYPE = ?', $type);
        $res = $db->fetchRow($sql);

        if (!$res[self::SID]) {
            return false;
        }

        if (!$load) {
            return true;
        }

        switch ($res[self::TYPE]) {
            case self::TYPE_COLLECTION :
                return new Hal_Site_Collection($res);
            case self::TYPE_PORTAIL :
                return new Hal_Site_Portail($res);
            default :
                // todo : est-ce qu'il faut renvoyer null ?
                return new Hal_Site_Portail($res);
        }
    }

    /**
     * @param string $q
     * @param string
     * @param int  $limit
     * @param null $sid
     * @return array
     */
    static public function autocomplete($q, $type = self::TYPE_COLLECTION, $limit = 50, $sid = null)
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, array('SID AS id', 'CONCAT_WS("", SITE, " - ", NAME) AS label', 'SITE AS code'))
            ->where('TYPE = "'.$type.'"')
            ->limit($limit);
        if (is_numeric($q)) {
            $sql->where(self::SID.' = ?', $q);
        } else {
            $sql->where('SITE LIKE ? OR NAME LIKE ?', $q . '%');
        }
        if ($sid != null && is_array($sid) && count($sid)) {
            $sql->where(self::SID.' IN (?)', $sid );
        }
        return $db->fetchAll($sql);
    }

    /**
     * @param string $q
     * @param string
     * @param int  $limit
     * @param int[] $sitesIds
     * @return array
     * @deprecated use searchObj
     */
    static public function search($q = '%', $type = self::TYPE_COLLECTION, $limit = 100, $sitesIds = [])
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(array('t' => self::TABLE))
            ->where('t.TYPE = "'.$type.'"');
         if ($limit) {
            $sql->limit($limit);
        };
        if ($q != '%') {
            if (is_numeric($q)) {
                $sql->where('t.SID = ?', $q );
            } else {
                $sql->where('t.SITE LIKE ? OR t.NAME LIKE ?', $q . '%');
            }
        }
        if (is_array($sitesIds) && count($sitesIds)) {
            $sql->where('t.SID IN (?)', $sitesIds );
        }

        return $db->fetchAll($sql);
    }

    /**
     * @param string $q
     * @param string
     * @param int  $limit
     * @param null $sid
     * @return Hal_Site[]
     */
    static public function searchObj($q, $type = self::TYPE_COLLECTION, $limit = 100, $sid = null)
    {
        if ($sid === null) {
            $sids = []; // compat fonction search....
        }
        $rows = self::search($q, $type, $limit, $sid);

        $list =[];
        foreach ($rows as $row) {
            if ($type == self::TYPE_COLLECTION) {
                $site = new Hal_Site_Collection($row);
            } else {
                $site = new Hal_Site_Portail($row);
            }
            $list[$site->getSid()] = $site;
        }
        return $list;
    }
    /**
     * @param string $mode
     */
    public function verifyConfig($mode = 'DIR') {
        switch ($mode) {
            case 'DIR':
                $dirList = [
                    SPACE_DATA,
                    SPACE_DATA . '/' . static::MODULE,
                    static::SPACE_DEFAULT,
                    static::CACHE_MODULE_PATH,
                    $this ->_cachePath,
                    $this -> _space,
                    $this -> _pathPages,
                    static::DEFAULT_CONFIG_PATH,

                ];
                foreach ($dirList as $dir) {
                    if (!is_dir($dir)) {
                        mkdir($dir, 0777, true) ||
                        error_log(__FILE__ . "- verifyConfig for " . $this -> _name . ": Can t mk $dir");
                    }
                }
        }
    }

    /**
     * @return string
     */
    public function getPrefixUrl()
    {
        return $this->_prefixUrl;
    }

    /**
     * @return string
     */
    public function getSpaceUrl()
    {
        return $this->_spaceUrl;
    }

    /**
     * @return string
     */
    public function getSiteUrl()
    {
        return $this->_siteUrl;
    }

    /**
     * @return string
     */
    public function getSpaceName()
    {
        return $this->_spaceName;
    }

    /**
     * @return string
     */
    public function getSpace()
    {
        return $this->_space;
    }
    /**
     * @return string
     */
    public function getConfigDir()
    {
        return $this->getSpace() . '/' . CONFIG;
    }

    /**
     * @return string
     */
    public function getPathPages()
    {
        return $this->_pathPages;
    }

    /**
     * @return Hal_Site
     */
    static public function getCurrent() {
        return self::$_current;
    }
    /**
     * @param Hal_Site $portail  : Portail ou Collection  (en fait website)
     */
    static public function setCurrent($portail) {
        self::$_current = $portail;
    }

    /**
     * @return Hal_Site_Portail
     */
    static public function getCurrentPortail() {
        return self::$_currentPortail;
    }
    /**
     * @param Hal_Site $portail
     */
    static public function setCurrentPortail($portail) {
        self::$_currentPortail = $portail;
    }

    /**
     * @return bool
     */
    static public function isOnDefaultSite() {
        return static::$_current == self::DEFAULT_SITE;
    }

    /**
     * @return bool
     */
    static public function isOnWebsite() {
        return static::$_current !== null;
    }

    /**
     * Retourne false pour les collections, pour les portails fonction défini dans Hal_Site_Portail
     * @return bool
     */
    public function submitAllowed()
    {
        return false;
    }
    /** R end la collection de patrouillage
     *   Si portail patrouille, site de la collection associee
     *   Si collection patrouille: la collection elle meme
     *
     *   Si non patrouille: null
     * @param Hal_site
     */
    abstract public function isPatrolled ();
    /**
     * @param string|Hal_Document $doc
     *
     * La mise a jour du patrouillage ne depends pas du Portail
     * Seulement de la collection du document
     *
     * @throws Zend_Db_Adapter_Exception
     */
    public function patrolMaybe($doc) {
        if ($site = $this->isPatrolled()) {
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
            $patrol = Hal\Patrol::construct($identifiant, $site);
            $patrol->save();
        }
    }
}