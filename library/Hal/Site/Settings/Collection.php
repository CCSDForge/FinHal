<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 18/01/18
 * Time: 14:05
 */

require_once(__DIR__.'/../../Site.php');
require_once(__DIR__.'/../../Cache.php');
require_once(__DIR__.'/../Settings.php');

/**
 * Class Hal_Site_Settings_Collection
 */
class Hal_Site_Settings_Collection extends Hal_Site_Settings
{

    const TABLE = 'COLLECTION_SETTINGS';
    // TODO: Mettre une fonction getAllSettings() qui concatene les settings de la classe avec les parents
    static public $SETTINGS_LIST = [ 'asker', 'comment', 'critere', 'languages', 'mode', 'visible' , 'patrolled'];

    const MODE_AUTO =   "auto";
    const MODE_MAN =   "manuel";

    /** @var string  */
    protected $_critere = '';
    /** @var int  */
    protected $_visible = 1;
    /** @var string  */
    protected $_asker = '';
    /** @var string  */
    protected $_comment = '';
    /** @var string  */
    protected $_mode = '';
    /** @var bool  */
    protected $_patrolled = false;
    /**
     * Hal_Site_Settings_Collection constructor.
     * @param $site
     * @param array $data
     */
    public function __construct($site, $data)
    {
        parent::__construct($site, $data);

        //todo : faire un truc plus smart qui prend la settings_list et essaie de remplir les params
        $this->setCritere(Ccsd_Tools::ifsetor($data['critere'], ''));
        $this->setMode(Ccsd_Tools::ifsetor($data['mode'], ''));
        $this->setAsker(Ccsd_Tools::ifsetor($data['asker'], ''));
        $this->setComment(Ccsd_Tools::ifsetor($data['comment'], ''));
        $this->setVisible(Ccsd_Tools::ifsetor($data['visible'], 1));
        $this->setPatrolled(Ccsd_Tools::ifsetor($data['patrolled'], 0));
        // Créer les fichiers de config ?
    }

    /**
     * @param Hal_Site
     * @return Hal_Site_Settings
     */
    static public function loadFromSite(Hal_Site $site)
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, array('SETTING', 'VALUE'))->where('SID = ?', $site->getSid());
        $data = $db->fetchPairs($sql);

        $settings = new Hal_Site_Settings_Collection($site, $data);

        $settings->load($site);
        return $settings;

    }

    /**
     *
     */
    public function delete()
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete(self::TABLE, 'SID = ' . (int)$this->getSid());

        $this->deleteAssociatedFiles();
    }

    /**
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     */
    public function save()
    {
        parent::save();

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();

        if ($this->getSid() != 0) {
            $db->delete(self::TABLE, 'SID = ' . $this->getSid());
        }

        // todo : pourquoi on enregistre pas SETTINGS_LIST ?? Pourquoi pas languages ?
        // BM: Parce que languages est sense etre fait par le parent (Site_Settings: ce n'est pas particulier aux Collections
        // En fait, la list des settings devrait juste etre augmente des settings "locaux"
        $res = true;
        foreach(array('asker', 'comment', 'critere', 'mode', 'visible') as $setting) {
            $bind = array(
                'SID'      =>  $this->getSid(),
                'SETTING'  =>  $setting,
                'VALUE'    =>  $this->{'_' . $setting}
            );
            $res = $res && $db->insert(self::TABLE, $bind);
        }

        return $res;
    }

    /**
     * @param Hal_Site_Settings $settings
     * @throws Zend_Db_Adapter_Exception
     */
    public function duplicate(Hal_Site_Settings $settings)
    {
        parent::duplicate($settings);

        if (!($settings instanceof Hal_Site_Settings_Collection)) {
            return;
        } else {
            $settings->setParams($this->toAssocArray());
            $settings->save();
        }
    }

    /**
     * Suppression des fichiers de configuration
     */
    public function deleteAssociatedFiles() {
        $code = $this -> getSiteShortName();
        if (isset($code) && ($code != null) && !in_array($code, ['','.','..'])) {
            // Attention de ne pas effacer SPACE_COLLECTION!!!
            // Donc getCode doit contenir qq chose!
            // code vide est sans doute une erreur, mais elle conduirait a une catastrophe
            // Cela arrive si l'admin fait deux fois le bouton delete!!!
            $dir = SPACE_DATA . '/'. SPACE_COLLECTION . '/' . $code;
            if (is_dir($dir)) {
                Ccsd_Tools::rrmdir($dir);
            }
        } else {
            $sid = $this -> getSid();
            error_log("Code vide trouve pour la collection $sid");
        }
    }

    /**
     * @return array
     */
    public function getAsDbArray()
    {
        $settings=[];
        foreach (self::$SETTINGS_LIST as $setting_label) {
            if (isset($this -> {'_' . $setting_label})) {
                $settings[] = [ 'SETTING' => $setting_label, 'VALUE' => $this -> {'_' . $setting_label} ];
            }
        }
        return $settings;
    }

    /**
     * @return array
     */
    public function toAssocArray()
    {
        $data = [];
        foreach(self::$SETTINGS_LIST as $setting) {
            $data[$setting] = $this->{'_' . $setting};
        }
        return $data;
    }


    /**
     * @return array
     */
    public function toArray()
    {
        return $this->toAssocArray();
    }

    /**
     * @param $mode
     * @return $this
     */
    public function setMode($mode)
    {
        $this->_mode = $mode;
        return $this;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->_mode;
    }

    /**
     * @param $critere
     * @return $this
     */
    public function setCritere($critere)
    {
        $this->_critere = $critere;
        return $this;
    }

    /**
     * @return string
     */
    public function getCritere()
    {
        return $this->_critere;
    }

    /**
     * @param $visibility
     * @return $this
     */
    public function setVisibility($visibility)
    {
        $this->_visible = $visibility;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVisibility()
    {
        return $this->_visible;
    }

    /**
     * @param $visibility
     * @return $this
     */
    public function setVisible($visibility)
    {
        $this->_visible = $visibility;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getVisible()
    {
        return $this->_visible;
    }

    /**
     * @param $asker
     * @return $this
     */
    public function setAsker($asker)
    {
        $this->_asker = $asker;
        return $this;
    }

    /**
     * @return string
     */
    public function getAsker()
    {
        return $this->_asker;
    }

    /**
     * @param $comment
     * @return $this
     */
    public function setComment($comment)
    {
        $this->_comment = $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * @param $bool
     */
    public function setPatrolled($bool) {
        $this-> _patrolled = $bool;
    }

    /**
     * @return bool
     */
    public function getPatrolled() {
        return $this-> _patrolled;
    }


    /** Retourne la liste des collections non visibles
     * @return array
     */
    static public function getNonVisibleCollections()
    {
        $cacheName = 'collection.nonvisible.phps';
        $cachePath = DEFAULT_CACHE_PATH;
        if (Hal_Cache::exist ( $cacheName, 60, $cachePath )) {
            return unserialize(Hal_Cache::get($cacheName, $cachePath));
        }

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(array('s' => Hal_Site::TABLE), 'SITE')
            ->from(array('c' => self::TABLE), null)
            ->where('s.SID = c.SID')
            ->where('SETTING = "visible"')
            ->where('VALUE = "0"');
        $res = $db->fetchCol($sql);
        Hal_Cache::save($cacheName, serialize($res), $cachePath);
        return $res;
    }
}