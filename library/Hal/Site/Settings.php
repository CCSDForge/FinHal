<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 18/01/18
 * Time: 09:24
 */

require_once(__DIR__.'/Settings.php');

/**
 * Class Hal_Site_Settings
 */
class Hal_Site_Settings
{

    /* @const string WebSite Settings table name */
    const TABLE = 'WEBSITE_SETTINGS';

    /**
     * @var Hal_Site
     */
    protected $_site = null;

    /** @var array */
    protected $_languages = [];
    /** @var string */
    protected $_piwikid = '';

    /** @var bool */
    protected $_visibility = true;

    /** @var int */
    protected $_associatedsiteId = 0;
    /** @var Hal_Site_Collection */
    protected $_associatedsite = null;
    /** @var bool */
    protected $_associatedsiteLoaded = false;

    /**
     * @var string[]  ; Langues par défaut pour un site
     */
    private $_defaultLangs = ['fr', 'en'];

    /** @var bool : indique si les dépôts sont autorisés pour le portail */
    protected $_submitallowed = true;

    /**
     * Hal_Site_Settings constructor.
     * @param Hal_Site $site
     * @param array $data
     */
    public function __construct(Hal_Site $site, $data = [])
    {
        $this->setSite($site);

        $this->setVisibility(Ccsd_Tools::ifsetor($data['visibility'], true));
        $this->setLanguages(Ccsd_Tools::ifsetor($data['languages'], $this->_defaultLangs));
        $this->setAssociatedsiteId(Ccsd_Tools::ifsetor($data['associatedsite'], 0));
    }

    /**
     * @param Hal_Site $site
     * @return Hal_Site_Settings|null
     */
    static public function loadFromSite(Hal_Site $site)
    {
        // Sous-classer !
        Ccsd_Tools::panicMsg(__FILE__, __LINE__, "loadFromSite must be subclassed!!! caslled with " . $site->getShortname());
        return null;

    }

    /**
     * @param $params
     */
    public function setParams($params)
    {
        foreach ($params as $key => $value) {

            $methodName = 'set'.ucfirst($key);

            if (method_exists ( get_class($this) ,  $methodName)) {
                $this->{$methodName}($value);
            }
        }
    }

    /**
     * @param Hal_Site $site
     * @return Hal_Site_Settings
     */
    public function load(Hal_Site $site)
    {
        $this->setSite($site);

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, array('SETTING', 'VALUE'))->where('SID = ?', $site->getSid());
        foreach ( $db->fetchPairs($sql) as $setting => $value ) {
            switch ($setting) {
                case 'languages' :
                    $this->setLanguages(unserialize($value));
                    break;
                case 'PIWIKID':
                    $this->setPiwikid($value);
                    break;
                default :
                    break;
            }
        }
        return $this;
    }

    /**
     * @return int
     * @throws Zend_Db_Adapter_Exception
     */
    public function save()
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();

        if ($this->getSid() != 0) {
            $db->delete(self::TABLE, 'SID = ' . $this->getSid());
        }

        $bind = ['SID' => $this->getSid(), 'SETTING' => 'languages', 'VALUE' => serialize($this->getLanguages())];

        return $db->insert(self::TABLE, $bind);
    }


    /**
     * Création du dossier data lié au site
     */
    public function createFilePaths(){

        $dir = $this->getRootPath();
        if (! is_dir ( $dir )) {
            mkdir ( $dir , 0777, true );
            mkdir ( $dir.CONFIG , 0777, true );
            mkdir ( $dir.LANGUAGES , 0777, true );
            mkdir ( $dir.LAYOUT , 0777, true );
            mkdir ( $dir.PAGES , 0777, true );
            mkdir ( $dir.PUBLIC_DEF , 0777, true );
        }
    }

    /** Return the path of Collection on disk
     * @return string
     */
    public function getRootPath() {
        return SPACE_DATA . '/' . strtolower($this->getSite()->getType()) . '/' . $this->getSiteShortName() . '/';
    }

    /**
     * @param int
     * @return $this
     */
    public function setPiwikid($id)
    {
        $this->_piwikid = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getPiwikid()
    {
        return $this->_piwikid;
    }

    
    /**
     * @param string[] $languages
     * @return $this
     */
    public function setLanguages($languages)
    {
        if (empty($languages)) {
            $languages = $this->_defaultLangs;
        }

        if (is_string($languages)) {
            $languages = [$languages];
        }

        $this->_languages = $languages;
        return $this;
    }

    /**
     * @return array
     */
    public function getLanguages()
    {
        return $this->_languages;
    }

    /**
     * @param $setting
     * @return mixed
     */
    public function getSetting($setting)
    {
        $methodName = 'get' . ucfirst($setting);

        if (method_exists ( get_class($this) ,  $methodName)) {
            return $this->{$methodName}();
        }
        return null;
    }

    /**
     * @param Hal_Site $site
     * @return Hal_Site_Settings $this
     */
    public function setSite(Hal_Site $site)
    {
        $this->_site = $site;
        return $this;
    }

    /**
     * @return Hal_Site
     */
    public function getSite()
    {
        return $this->_site;
    }

    /**
     * @param $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->_visibility = $visibility;
        return $this;
    }

    /**
     * @return bool
     */
    public function getVisibility()
    {
        return $this->_visibility;
    }

    /**
     * @param int $siteid
     * @return $this
     */
    public function setAssociatedsiteId($siteid)
    {
        if ($this->_associatedsiteId != $siteid) {
            $this->_associatedsiteId = $siteid;
            $this->_associatedsiteLoaded = false;
        }
        return $this;
    }

    /**
     * @param Hal_Site $site
     * @return $this
     */
    public function setAssociatedsite($site)
    {
        $this-> setAssociatedsiteId($site->getSid());
        $this->_associatedsite = $site;
        $this->_associatedsiteLoaded = true;
        return $this;
    }


    /**
     * @return int
     */
    public function getAssociatedsiteId()
    {
        return $this->_associatedsiteId;
    }
   /**
     * @return Hal_Site_Collection
     */
    public function getAssociatedsite()
    {
        $id = $this->getAssociatedsiteId();
        if ($id && ! $this->_associatedsiteLoaded) {
            /** @var Hal_Site_Collection $site */
            $site = Hal_Site::loadSiteFromId($this->getAssociatedsiteId() );
            $this->setAssociatedsite($site);
        }
        return $this->_associatedsite;
    }

    /**
     * @return string
     */
    public function getSid()
    {
        return $this->getSite()->getSid();
    }

    /**
     * @deprecated : utiliser getSiteShortname()
     * @return string
     */
    public function getName()
    {
        return $this->getSiteShortName();
    }

    /**
     * @return string
     */
    public function getSiteShortName()
    {
        return $this->getSite()->getShortname();
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'languages' => $this->_languages,
            'visibility' => $this->_visibility
        ];
    }

    /**
     * @param Hal_Site_Settings $settings
     * @throws Zend_Db_Adapter_Exception
     */
    public function duplicate(Hal_Site_Settings $settings)
    {
        $settings->setLanguages($this->getLanguages());
        $settings->setVisibility($this->getVisibility());

        $settings->save();
    }

    /**
     *
     */
    public function delete()
    {
        // Rien par défaut... voir collection
    }

    /**
     * @return array
     */
    public function toAssocArray()
    {
        // voir les sous-classes
        return [];
    }

    /**
     * Positionne l'indicateur de dépôt autorisé dans le portail
     * @param bool $submit : la soumission est autorisée ou pas (oui par défaut)
     * @return Hal_Site_Settings
     */
    public function setSubmitAllowed($submit)
    {
        if (isset($submit) && is_bool($submit)) {
            $this->_submitallowed = $submit;
        }
        return $this;
    }

    /**
     * Lit l'indicateur de dépôt autorisé dans le portail
     * @return bool
     */
    public function getSubmitAllowed()
    {
        return $this->_submitallowed;
    }

}
