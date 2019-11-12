<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 15/09/17
 * Time: 15:47
 */

/**
 * Class Hal_Transfert
 *
 * Class permettant le transfert d'un document vers une archive externe
 * La table correspondante corresponds au triplet (idHal, IdExterne, Url de suivit
 *     Exemple: DOCIDARXIV, DOCIDSWH
 *
 * Interface publique de la classe fille:
 *    - getMedia
 *    - getAtom
 *    - canTransfert
 *    - canShowTransfert
 */
abstract class Hal_Transfert
{
    const MAX_RECURSION = 51;

    static protected $zipExcludeRe = '/The regexp to exclude some files in sent zip/';

    /*  DB Fields */
    /** @var int $_docid */
    protected $_docid;
    /** @var string $_remoteId
     *
     *  Subclass can maybe interpret this as int? */
    protected $_remoteId;
    /** @var string $_pendingUrl */
    protected $_pendingUrl;

    /* Les noms des tags des les xml de status : defaut arxiv values*/
    public $submissionIdTag = 'submissionIdTag';
    public $remoteIdTag     = 'remoteIdTag';
    public $trackingIdTag   = 'trackingIdTag';
    public $statusTag       = 'status';
    public $commentTag      = 'comment'; // foo value. nothing for Arxiv...
    public $needReindex     = false;
    static public $IDCODE ='undefined';

    protected $user= null;
    protected $pwd = null;
    public $errorTag        = 'error';

    /* Other internal properties */
    /** @var bool : indicate that the object was previouly searched into DB and loaded if found
     * You must use isFromDb to know if the row is allready in Db */
    protected $loaded = false;
    /** @var bool */
    protected $transfertLoaded = false;
    /** @var string */
    protected $_collection = null;
    /** @var bool */
    protected $modified = true;
    /** @var bool isFromDb */
    protected $isFromDb = false;
    /** @var  Hal_Transfert[] */
    protected $oldTransferts = [];
    /** @var string $serviceUrl */
    protected $serviceUrl;
    /** @var string $method */
    protected $method;
    /** @var  Hal_Document */
    protected $document;

    protected $filenamePrefix = "transfert-";
    // To be define in subclass
    /** @var string */
    static protected $TABLE = null;
    /** @var string */
    static protected $EXTFIELDNAME = null;
    /**
     * @param Hal_Document $document
     * @param string[] $errors (for returning reasons for not being able to transfert)
     * @return bool
     */
    abstract static public function canTransfert($document, &$errors);
    /**
     * @param bool $force
     * @return mixed
     * @throws Hal_Transfert_Exception
     */
    abstract public function send($force = false);
    /**
     * @return string
     */
    abstract protected function getServiceUrl();
    /**
     * @param string $edit
     * @param string $url
     * @param int    $attente
     * @param bool   $dolog
     * @return Hal_Transfert_Response
     */
    abstract public function waitresult($edit, $url, $attente, $dolog);
    /**
     * @param array $row
     * @return Hal_Transfert
     * @throws Exception
     */
    static protected function array2obj($row)
    {
        throw new Exception('This static function must be implemented in sub class');
    }

    /**
     * Hal_Transfert constructor for initial data
     * @param int    $docid
     * @param string $extId
     * @param string $pendingUrl
     */
    public function __construct($docid = 0, $extId = '', $pendingUrl = '')
    {
        $this->setDocid($docid);
        $this->setRemoteId($extId);
        $this->setPendingUrl($pendingUrl);
        $this->modified = true;
    }

    /**
     * Set docid in object, assure this is an int
     * @param $docid
     */
    protected function setDocid($docid) {
        $this->_docid = (int) $docid;
    }
    /** setter
     * @param string $url
     */
    public function setPendingUrl($url) {
        if ($this -> _pendingUrl != $url) {
            $this -> _pendingUrl = "$url";
            $this -> modified = true;
        }
    }

    /** Getter
     * @return string
     */
    public function getPendingUrl() {
        return $this -> _pendingUrl;
    }

    /** setter
     * @param string $id
     */
    public function setRemoteId($id) {
        $this -> _remoteId = "$id";
        $this -> modified = true;
    }

    /** Getter
     * @return string
     */
    public function getRemoteId()
    {
        return $this->_remoteId;
    }

    /**
     * Get Login user name on remote server
     * @return string
     */
    public function getUser() {
        return $this -> user;
    }

    /**
     * Get user password for remote server
     * @return string
     */
    public function getPwd() {
        return $this -> pwd;
    }

    /** delete from Database */
    public function delete()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        if (is_int($this->_docid)) {
            $db->delete(static::$TABLE, 'DOCID = ' . $this->_docid);
        }
        $this -> modified = true;
        $this -> isFromDb = false;
    }

    /**
     * @return Hal_Transfert[];
     */
    protected function getOldTransferts()
    {
        // The core fields not modified: no update of modified field
        if (!$this->transfertLoaded) {
            $thedocument = $this->document;
            if ($thedocument->getDocid() != 0) {
                $identifiant = $thedocument->getId(false);
                $tableAlias = 'extern';
                $fullExternFieldname = $tableAlias . '.' . static::$EXTFIELDNAME;
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                $sql = $db->select()->distinct()->from([$tableAlias => static::$TABLE], $tableAlias . '.*');
                $sql->joinLeft(['doc' => Hal_Document::TABLE], "$tableAlias.DOCID=doc.DOCID", '');
                $sql->where('doc.IDENTIFIANT = ?', $identifiant);
                $sql->where("$fullExternFieldname IS NOT NULL");
                $rows = $db->fetchAll($sql);
                $res = [];
                foreach ($rows as $row) {
                    $res[] = static::array2obj($row);
                }
                $this->transfertLoaded = true;
                $this->oldTransferts = $res;
            }
        }
        return $this->oldTransferts;
    }

    /**
     * Load info from DB
     * @param $docid
     * @return bool
     */
    protected function load($docid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select()->distinct()->from(['extern' => static::$TABLE])->where("extern.DOCID=$docid");

        $row = $db->fetchRow($select);
        if ($row === false) {
            return false;
        }
        $this->_docid = (int) $row['DOCID'];
        $this->_remoteId = $row[static::$EXTFIELDNAME];
        $this->_pendingUrl = $row['PENDING'];
        // obj is like in DB...
        $this->modified = false;
        $this->isFromDb = true;
        $this->loaded = true;
        return true;
    }

    public function save()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        if ($this->modified) {
            if ($this->isFromDb) {
                // update
                try {
                    $db->update(static::$TABLE, [static::$EXTFIELDNAME => $this->_remoteId,
                                                 'PENDING'             => $this->_pendingUrl], 'DOCID = ' . $this->_docid);
                    $this->modified = false;
                    $this->loaded = true;
                } catch (Zend_Db_Exception $e) {

                    error_log(sprintf("Can't update %s for saving %d with (%s,%s)",
                        static::$TABLE, $this->_docid, $this->_remoteId, $this->_pendingUrl));
                }
            } else {
                // creation
                try {
                    $db->insert(static::$TABLE, ['DOCID'               => $this->_docid,
                                                 static::$EXTFIELDNAME => $this->_remoteId,
                                                 'PENDING'             => $this->_pendingUrl]);
                    $this->modified = false;
                    $this->isFromDb = true;
                    $this->loaded = true;
                } catch (Zend_Db_Exception $e) {
                    error_log(sprintf("Can't insert %s for saving %d with (%s,%s)",
                        static::$TABLE, $this->_docid, $this->_remoteId, $this->_pendingUrl));
                }
            }
        }
    }

    /**
     * Load all old version of transfered
     * Memorize the call to avoid multiple loading from DB
     */
    protected function fullLoad()
    {
        // Don't modify core field: object not modified in regard of saving
        if (!$this->transfertLoaded) {
            $this->oldTransferts = self::getOldTransferts();
            $this->transfertLoaded = true;
        }
    }

    /**
     * Return false if document not on external service
     * Return the external Id if found
     * @return false|null|int
     */
    public function isAllreadyOn()
    {
        $others = $this -> getOldTransferts();
        foreach ($others as $previous_deposit) {
            if ($previous_deposit -> _remoteId != null) {
                return $previous_deposit -> _remoteId;
            }
        }
        return false;
    }

    /**
     * @param $zipfilename String : of the zip file name to use (will be deleted if exist)
     * @param $fSrc        Hal_Document_File[]
     * @return true if ok
     * @throws Hal_Transfert_Exception, open Exception
     */
    public function create_zip($zipfilename, $fSrc)
    {
        # Creation d'un Zip pour les fichiers sources devant etre envoye a Arxiv
        # Particularite/optimisation:
        #    Si il y a un fichier zip, on le prends comme base
        #    Il semble (a confirmer) que l'on ne transferera pas 2 zip!
        // TODO on devrait verifier que c'est un path absolu
        if (is_file($zipfilename)) {
            @unlink($zipfilename);
        }
        // On ne mets pas un zip dans un zip, on utilise le zip fourni
        $zipToExclude = '';
        foreach ($fSrc as $file) {
            $name = $file->getName();
            if (preg_match('/\.zip$/', $name)) {
                @copy($file->getPath(), $zipfilename);
                $zipToExclude = $name;
                break;
            }
        }
        $zip = new ZipArchive;
        if (! $zip->open($zipfilename, ZipArchive::CREATE)) {
            throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, 'Error generated zip to submit...');
        }
        // Construction du Zip
        foreach ($fSrc as $file) {
            // Pas de transfert de bib,log,autre zip
            $name = $file->getName();
            if (( $name == $zipToExclude) || preg_match(static::$zipExcludeRe, $name)){
                // Zip deja dans l'archive, ou fichier a exclure : on ne l'ajoute pas
                continue;
            }
            //
            if (! $zip->addFile($file->getPath(), $name)) {
                error_log('Can\'t add ' . $file->getPath() . ' to zip ' . $zipfilename . ' as ' . $name);
            }
        }
        if (!$zip->close()) {
            throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, 'Error closing zip to submit...');
        }

        return true;
    }

    /** Nuitamment, un check pour mise a jour de l'external Id
     *  l'indexation peut etre faite par l'appellant en positionnant doreindex a false.
     * @param bool $doreindex
     * @param bool $dolog
     * @return Hal_Transfert_Response
     *
     *
     */
    public function check_pending_status($doreindex = true, $dolog = false) {
        $response = $this -> waitresult(null, $this -> getPendingUrl(), 1 , $dolog);
        if ($this ->getRemoteId()) {
            /** mise a jour de l'identifiant du document */
            try {
                if ($dolog) {
                    Ccsd_Log::message("Add SWH id for " . $this ->_docid . "\n", true, 'INFO');
                }
                Hal_Document_Meta_Identifier::addIdExtDb($this->_docid, static::$IDCODE, $this->getRemoteId());
            } catch (Exception $e) {
                /* If identifier allready exists, don't complain */
            }
        }
        if ($doreindex && $this -> needReindex) {
            Hal_Document::deleteCaches([ $this -> _docid ]);
            Ccsd_Search_Solr_Indexer::addToIndexQueue([ $this -> _docid ]);
        }
        return $response;
    }

    /**
     * Si changement de DociId pour un document, suivit dans les tables de trasnferts
     *
     * @param int $olddoci
     * @param int $newDocid
     */
    static public function changeDocid($olddoci, $newDocid)
    {
        $transfertObj = new static($olddoci);
        if ($transfertObj->load($olddoci)) {
            $transfertObj->delete();
            $transfertObj->setDocid($newDocid);
            $transfertObj->modified = true;
            $transfertObj->save();
        }
    }
}