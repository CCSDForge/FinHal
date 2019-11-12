<?php


abstract class Hal_Document_Meta_Abstract
{
    // Table des métadonnées d'un document
    const TABLE_META = 'DOC_METADATA';

    protected $_key = "";
    protected $_value;

    protected $_group = null;

    // Origine de la métadonnée (form, grobid, doi, arxiv, ...)
    protected $_source = "";

    // La métadonnée a-t-elle été écrite manuellement ? On enregistre l'uid de l'utilisateur
    // Si la valeur est à 0 : la valeur est complètement récupérée automatiquement et non modifiée manuellement
    protected $_modificationUid = 0;

    // Statut de la métadonnée (enBase=1, enCours=0)
    protected $_status = 0;

    protected $isMultilingue = false;

    protected $_defautlView = 'displayMeta.phtml';

    /************* Fonctions à implémenter ****************/
    abstract public function save($docid, $sid, &$metaids = null);
    /** A fonction to merge values when it comes from more than one sources */
    abstract public function merge($newMeta);

    /**
     * Hal_Document_Meta_Abstract constructor.
     * @param     $key
     * @param     $value
     * @param     $group
     * @param     $source
     * @param     $uid
     * @param int $status
     */
    public function __construct($key, $value, $group, $source, $uid, $status = 0)
    {
        if (isset($group) && is_string($group)) {
            $group = strtolower($group);
        }

        $this->_key = $key;
        $this->_value = $value;
        $this->_group = $group;
        $this->_source = $source;
        $this->_modificationUid = $uid;
        $this->_status = $status;
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->_key;
    }

    /**
     * @param string $key
     */
    public function setKey($key)
    {
        $this->_key = $key;
    }

    /**
     * @return mixed
     */
    public function getValue($group = false)
    {
        return $this->_value;
    }

    /**
     * @param $value
     */
    public function setValue($value) {
        $this ->_value = $value;
    }
    /**
     * @deprecated : use getValue
     * @return mixed
     */
    public function getHalValue($group = false)
    {
        return $this;
    }

    /**
     * @param mixed $value
     */
    static public function getDefaultValue($filter = '')
    {
        return '';
    }

    /**
     * @return string
     */
    public function getGroup()
    {
        return $this->_group;
    }

    /**
     * @param string $group
     */
    public function setGroup($group)
    {
        $this->_group = $group;
    }

    /**
     * @return string
     */
    public function getSource()
    {
        return $this->_source;
    }

    /**
     * @param string $source
     */
    public function setSource($source)
    {
        $this->_source = $source;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->_status = $status;
    }

    /**
     * @return int
     */
    public function getModifUid()
    {
        return $this->_modificationUid;
    }

    /**
     * @param $modifUid
     */
    public function setModifUid($modifUid)
    {
        $this->_modificationUid = $modifUid;
    }

    /**
     * @return bool
     */
    public function isValid() {
        return true;
    }

    public function getRealValue()
    {
        return $this->_value;
    }

    /**
     * On trim la value pour cleaner la valeur avant de l'enregistrer en base
     * @param string $value
     */
    protected function cleanForDb($value)
    {
        return trim($value);
    }

    /**
     * MetaIds: array of Meta Ids available in DB table (after a delete
     *          It's to reuse some Ids.
     * @param int[] $metaids
     * @param $docid
     * @param $sid
     */
    protected function insertLine(&$metaids, $docid, $sid)
    {
        $bind = array(
                'DOCID' => $docid,
                'METANAME' => $this->getKey(),
                'METAVALUE' => $this->cleanForDb($this->getValue()),
                'METAGROUP' => $this->getGroup(),
                'SOURCE' => $this->getSource(),
                'UID' => $this->getModifUid(),
                'SID' => $sid);
        if (!empty($metaids)) {
            $bind['METAID'] = $metaids[0];
            array_shift($metaids);
        }

        try {
            $this->_db->insert(Hal_Document_Metadatas::TABLE_META, $bind);
        } catch(Exception $e) {
            Ccsd_Log::message('Metadata Save Error in docid ' . $docid . ' for metadata ' . $this->getKey() , false, '', PATHTEMPDOCS . 'metadatas');
            Ccsd_Log::message(serialize($bind) , false, '', PATHTEMPDOCS . 'metadatas');
        }
    }

    /**
     * @param $source
     * @return string
     */
    public function getMetasFromSource($source)
    {
        if ($this->getSource() == $source) {
            return $this->getKey();
        } else {
            return "";
        }
    }

    /**
     * @return bool
     */
    public function isMultilingue() {
        // Must use $isMultilingue but we need before implement title/abstract/subTitle as meta object...
        return Hal_Settings::isMultiLanguageMetas($this->getKey());
    }

    /**
     * @return string
     */
    public function getDisplayView() {
        return $this->_defautlView;
    }

    public function isMultiValued() {
        return false;
    }
}
