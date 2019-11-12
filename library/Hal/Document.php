<?php

/**
 *
 * Objet document de l'archive ouverte
 *
 */
class Hal_Document
{

    /**
     * Format des dépôts
     */
    const FORMAT_FILE = 'file'; // Document avec texte intégral
    const FORMAT_NOTICE = 'notice'; // Notice biblio
    const FORMAT_ANNEX = 'annex'; // Notice avec un fichier annexe

    /**
     * Statuts d'un papiers
     */
    const STATUS_VISIBLE = 11; // article visible
    const STATUS_REPLACED = 111; // ancienne(s) version(s) d'article
    const STATUS_BUFFER = 0; // en attente de validation technique
    const STATUS_TRANSARXIV = 10; // en attente de transfert vers arXiv
    const STATUS_VALIDATE = 9; // en attente de validation scientifique
    const STATUS_MODIFICATION = 1; // article en attente de modification
    const STATUS_DELETED = 99; // article refusé
    const STATUS_MERGED = 88; // article fusionné
    const STATUS_MYSPACE = 2; // article dans l'espace du déposant

    const CACHE_MAX_TIME = 900000; // 2500 heures
    const PHPS_CACHE_MAX_TIME = 86400; // 3600 * 24 = 24 heures
    /**
     * Tables
     */
    const TABLE = 'DOCUMENT'; // Table principale d'un article
    const TABLE_RELATED = 'DOC_RELATED'; // Table des ressources liées de l'article
    const TABLE_DELETED = 'DOC_SAMEAS'; // Table des correspondances id deleted et id online
    const TABLE_OWNER = 'DOC_OWNER'; // Table des propriétaires d'un doc
    const TABLE_PMC = 'DOC_HALMS'; // Table temporaire pour HALMS à terme à supprimer
    /**
     * Url
     */
    const URL_DOI = 'https://dx.doi.org/';
    const URL_THESES = 'https://www.theses.fr/';

    public static $_serverCopy = array('doi', 'arxiv', 'pubmed', 'bibcode', 'ird', 'ineris', 'pubmedcentral', 'irstea', 'sciencespo', 'oatao', 'ensam', 'prodinra', 'okina', 'cern', 'inspire', 'swh', 'biorxiv', 'chemrxiv','wos');

    public static $_serverCopyUrl = array(
        'doi' => 'https://dx.doi.org/',
        'arxiv' => 'https://arxiv.org/abs/',
        'pubmed' => 'https://www.ncbi.nlm.nih.gov/entrez/query.fcgi?cmd=Retrieve&db=pubmed&dopt=Abstract&list_uids=',
        'bibcode' => 'http://adsabs.harvard.edu/cgi-bin/nph-bib_query?bibcode=',
        'pubmedcentral' => 'https://www.pubmedcentral.nih.gov/articlerender.fcgi?tool=pmcentrez&rendertype=abstract&artid=',
        'irstea' => 'https://cemadoc.irstea.fr/cemoa/',
        'sciencespo' => 'https://spire.sciencespo.fr/hdl:/',
        'oatao' => 'https://oatao.univ-toulouse.fr/',
        'ird' => 'http://www.documentation.ird.fr/fdi/notice.php?ninv=',
        'okina' => 'http://okina.univ-angers.fr/publications/',
        'ensam' => '',
        'prodinra' => 'https://prodinra.inra.fr/record/',
        'ineris' => '',
        'cern' => 'https://cds.cern.ch/record/',
        'inspire' => 'https://inspirehep.net/record/',
        'swh' => 'https://archive.softwareheritage.org/',
        'biorxiv' => 'https://www.biorxiv.org/cgi/content/short/',
        'chemrxiv' => 'https://dx.doi.org/10.26434/chemrxiv.',
        'wos'=>''
    );

    /**
     * Identifiant interne d'un document
     *
     * @var int
     */
    public $_docid = 0;

    /**
     * Identifiant public d'un document
     *
     * @var String
     */
    public $_identifiant = '';

    /**
     * @var array
     */
    public $_sameas_ids = [];

    /**
     * @var bool
     */
    public $_sameas_loaded = false;
    
    /**
     * Password d'un document
     *
     * @var String
     */
    protected $_pwd = '';

    /**
     * Version du document
     *
     * @var int
     */
    protected $_version = 0;

    /**
     * Indique si le dépôt est avec ou sans le texte intégral
     *
     * @var string
     */
    protected $_format = '';

    /**
     * Statut du document
     *
     * @var int
     */
    protected $_status = 0;

    /**
     * Type du document (UNDEFINED, REPORT, ART, ...)
     *
     * @var string
     */
    protected $_typdoc = '';

    /**
     * Citation de la ressource : full -> titre+auteur+refbib ou refbib
     *
     * @var array
     */
    protected $_citation = array('full' => null, 'ref' => null);

    /**
     * COinS
     *
     * @var string
     */
    protected $_coins = '';

    /**
     * Objet contenant les métadonnées du document
     *
     * @var Hal_Document_Metadatas
     */
    protected $_metas = null;

    /**
     * Tableau des fichiers associés au document
     *
     * @var Hal_Document_File[]
     */
    protected $_files = array();

    /**
     * Tableau des auteurs du document
     *
     * @var Hal_Document_Author[]
     */
    protected $_authors = array();

    /**
     * Tableau des structures de recherche liés aux auteurs du document
     *
     * @var Hal_Document_Structure[]
     */
    protected $_structures = array();

    /**
     * Tableau des ressources liées
     *
     * @var array
     */
    protected $_related = array();

    /**
     * Tableau des collections du document
     *
     * @var Hal_Site_Collection[]
     */
    protected $_collections = array();

    /**
     * Tableau des versions du document
     *
     * @var array
     */
    protected $_versions = array();

    /**
     * UID du déposant
     *
     * @var array
     */
    protected $_contributor = array('uid' => 0);

    /**
     * SITEID du portail où le doc a été déposé
     *
     * @var int
     */
    protected $_sid = 0;

    /**
     * Nom du portail où le doc a été déposé
     *
     * @var string
     */
    protected $_instance = '';

    /**
     * Tableau des owners du document
     *
     * @var array
     */
    protected $_owners = array();

    /**
     * Date de soumission              'yyyy-MM-dd HH:mm:ss'
     */
    protected $_submittedDate = null;

    /**
     * Date de mise en ligne           'yyyy-MM-dd HH:mm:ss'
     */
    protected $_releasedDate = null;

    /**
     * Date de dernière modification   'yyyy-MM-dd HH:mm:ss'
     */
    protected $_modifiedDate = null;

    /**
     * Date de production              'yyyy-MM-dd'
     */
    protected $_producedDate = null;

    /**
     * The Publication Date
     * 'yyyy-MM-dd' eg: 1970-12-31
     * @var string
     */
    protected $_publicationDate = null;

    /**
     * Date d'archivage                'yyyy-MM-dd HH:mm:ss'
     */
    protected $_archivedDate = null;

    private $_loaded = false;

    /**
     * Le déposant a demandé le transfert sur arXiv
     * @var bool
     */
    protected $_isArxiv = false;

    /**
     * Le déposant a demandé le transfert sur Pubmed Central
     * @var bool
     */
    private $_isPmc = false;

    /**
     * Le déposant a demandé le transfert sur Software Heritage
     * @var bool
     */
    private $_isSwh = false;

    /**
     * Lien extérieur URL Value / Lien Arxiv,PMC,Istex
     * @var string
     */
    protected $_linkexturl = null;

    /**
     * Lien extérieur ID Value / Lien Arxiv,PMC,Istex
     * @var string
     */
    protected $_linkextid = null;

    /**
     * Le déposant a demandé de cacher son dépôt de OAI
     * @var bool
     */
    private $_hideOAI = false;

    /**
     * Le déposant a demandé de cacher son dépôt de RePEc
     * @var bool
     */
    private $_hideRePEc = false;

    /**
     * Type de dépôt (sert pour l'enregistrement)
     * @var string
     */
    private $_typeSubmit = '';

    /**
     * Origine du dépôt (web, ws, sword)
     * @var string
     */
    protected $_inputType = Hal_Settings::SUBMIT_ORIGIN_WEB;

    /**
     * Id de l'imagette de document
     * @var int
     */
    protected $_thumbid = 0;

    /**
     * Définit si un dépôt est un auto-archivage
     * @var bool
     */
    private $_selfArchiving = false;

    /**
     * @var string
     */
    private $_moderationMsg = "";

    /** this function must not be used outside this file
     *  It is created for test purpose
      */
    public function set_submittedDate($date = null) {
        if ($date == null) {
            $date = date('Y-m-d H:i:s');
        }
        $this ->_submittedDate = $date;
    }

    /**
     * Constructeur
     *
     * @param int $docid docid
     * @param string $identifiant HAL identifiant
     * @param int $version document version
     * @param boolean $populate chargement de l'objet
     * @param bool $loadFromBase utilisation forcée de la bdd
     */
    public function __construct($docid = 0, $identifiant = '', $version = 0, $populate = false, $loadFromBase = false)
    {
        $this->_docid = 0;
        $this->_identifiant = '';
        $this->_version = 0;
        $docid = abs((int)$docid);
        $this->_metas = new Hal_Document_Metadatas();

        if ($docid > 0) {
            $this->_docid = $docid;
            if ($populate) {
                $this->load('DOCID', $loadFromBase);
            }
        } else {
            $this->_identifiant = trim((string)$identifiant);
            $this->_version = (int)$version;
            if ($populate) {
                $this->load('ID', $loadFromBase);
            }
        }
    }

    /**
     * Vérifie l'existence d'un document et le retourne
     *
     * @param int $docid
     * @param string $identifiant
     * @param int $version
     * @param bool $loadFromBase utilisation forcée de la bdd
     * @return Hal_Document|bool
     */
    static public function find($docid = 0, $identifiant = '', $version = 0, $loadFromBase = false)
    {
        if ($docid == 0) {
            $document = new self(0, $identifiant, $version, true, $loadFromBase);
        } else {
            $document = new self($docid, '', 0, true, $loadFromBase);
        }
        return ($document->getDocid() != 0) ? $document : false;
    }

    /**
     * Réinitialisation de l'objet
     */
    private function clear()
    {
        $blankInstance = new static;
        try {
            $reflBlankInstance = new ReflectionClass($blankInstance);
        } catch (ReflectionException $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "this can't arise. class always exists");
        }
        foreach ($reflBlankInstance->getProperties() as $prop) {
            $prop->setAccessible(true);
            if (!$prop->isStatic()) {
                $this->{$prop->name} = $prop->getValue($blankInstance);
            }
        }
    }

    /**
     * Récupération du docid d'un document à partir de son identifiant
     * @param string $identifiant
     * @return int
     */
    public function getDocidFromId($identifiant = null)
    {
        if ($identifiant) {
            $matches=[];
            if (preg_match("/(.*)v(\d+)/", $identifiant, $matches)) {
                # identifiant avec version
                $version = $matches[2];
                $identifiant = $matches[1];
            } else {
                $version = 0; // We take the visible one
            }
        } else {
            $version     = $this->_version;
            $identifiant = $this->_identifiant;
        }

        # NOTE: Ne pas utiliser $this, si identifiant est donnee!!!
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        # Ancienne forme utilisees en externe...
        $identifiant = str_replace('democrite-', 'in2p3-', str_replace('ccsd-', 'hal-', $identifiant));

        // Le cas habituel...
        $sql = $db->select()->from(self::TABLE, 'DOCID')
            ->where('IDENTIFIANT = ?', $identifiant);
        if ($version > 0) {
            $sql->where('VERSION = ?', $version);
            $sql->where('DOCSTATUS != ?', self::STATUS_MERGED);
        } else {
            $sql->where('DOCSTATUS = ?', self::STATUS_VISIBLE);
        }

        $docid = $db->fetchOne($sql);

        if (!$docid) {
            // Peut etre un identifiant supprime ?
            // TODO: Un petit join... non? au lieu de deux requete?
            $sql = $db->select()
                ->from(['del'=> self::TABLE_DELETED], 'doc.DOCID')
                ->join(['doc'=>self::TABLE], "del.CURRENTID=doc.IDENTIFIANT")
                ->where('del.DELETEDID = ?', $identifiant)
                ->where('doc.DOCSTATUS = ?', self::STATUS_VISIBLE);

            $docid = $db->fetchOne($sql);
        }
        // Toujours pas de resultat: on prends qq soit le status
        if (!$docid && $this->_version == 0) {
            $sql = $db->select()->from(self::TABLE, 'DOCID')
                ->where('IDENTIFIANT = ?', $identifiant);
            $res = $db->fetchCol($sql);
            if (count($res) == 1) {
                $docid = $res[0];
            }
        }

        return $docid;
    }


    /**
     * Récupération de l'identifiant d'un document à partir de son docid
     * @param int $docid
     * @return string
     */
    static public function getIdFromDocid($docid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, 'IDENTIFIANT')
            ->where('DOCID = ?', $docid);
        return $db->fetchOne($sql);
    }

    /**
     * Récupération du docid
     * @return int
     */
    public function getDocid()
    {
        return (int)$this->_docid;
    }

    /**
     * Récupération identifiant
     * @return string
     */
    public function getId($version = false)
    {
        return $this->_identifiant . (($version && count($this->_versions) > 1) ? 'v' . $this->_version : '');
    }

    /**
     * Récuparation version
     * @return int
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Récupération de toutes les versions d'un document
     * @return int[]
     */
    public function getVersionsFromId($identifiant)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, 'VERSION')
            ->where('IDENTIFIANT = ?', $identifiant);
        return $db->fetchCol($sql);
    }

    /**
     * Initialisation de la version
     * @param int $version
     */
    public function setVersion($version)
    {
        $this->_version = (int)$version;
    }

    /**
     * Initialisation de la liste des versions
     * @param array $versions
     */
    public function setVersions($arrayVersions)
    {
        $this->_versions = $arrayVersions;
    }

    /**
     * Constructeur via docid
     * @param int $docid
     * @param bool $populate
     */
    public function setDocid($docid = 0, $populate = true)
    {
        $this->_docid = abs((int)$docid);
        if ($populate) {
            $this->load();
        }
    }


    /**
     * Constructeur via l'identifiant
     * @param string $identifiant
     * @param int $version
     * @param bool $populate
     */
    public function setID($identifiant = '', $version = 0, $populate = true)
    {
        $this->_identifiant = trim((string)$identifiant);
        $this->_version = (int)$version;
        if ($populate) {
            $this->load('ID');
        }
    }

    /**
     * Récupération des docid du dépôt à partir de son identifiant
     * @return int[]
     */
    public function getDocids()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->distinct()
            ->from(self::TABLE, 'DOCID')
            ->where('IDENTIFIANT = ?', str_replace('democrite-', 'in2p3-', str_replace('ccsd-', 'hal-', $this->_identifiant)));
        return $db->fetchCol($sql);
    }

    /**
     *  Renvoie la liste des
     */
    public function getSameasIds()
    {
        if (!$this->_sameas_loaded) {

            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $sql = $db->select()
                ->from(self::TABLE_DELETED, 'DELETEDID')
                ->where('CURRENTID = ?', $this->_identifiant);
            $this->_sameas_ids = $db->fetchCol($sql);
            $this->_sameas_loaded = true;
        }

        return $this->_sameas_ids;
    }

    /**
     * Récupération du format du dépôt
     * @return string
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * Récupération du format du dépôt
     * @return string
     */
    public function isNotice()
    {
        return $this->_format == self::FORMAT_NOTICE;
    }

    /**
     * Initialisation du format du dépôt en fonction des fichiers du document
     */
    public function initFormat()
    {
        if ($this->existFile()) {
            $mainFile = $this->getDefaultFile();
            if ($mainFile instanceof Hal_Document_File) {
                $this->setFormat(self::FORMAT_FILE);
            } else {
                $this->setFormat(self::FORMAT_ANNEX);
            }
        } else {
            $this->setFormat(self::FORMAT_NOTICE);
        }
    }

    /**
     * Indique si le dépôt a un fichier principal visible
     * @return bool
     */
    public function mainFileVisible()
    {
        $this->initFormat();
        return $this->getFormat() == self::FORMAT_FILE;
    }

    /**
     * @return bool
     */
    private function isLoaded()
    {
        return $this->_loaded;
    }

    /**
     * Retourne le SITEID d'un document selon son Docid
     *
     * @param int $docid
     * @return int
     */
    static function getDocumentSID($docid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, ['SID'])->where('DOCID = ?', (int)$docid);
        $row = $db->fetchRow($sql);
        return $row['SID'];
    }


    /**
     * Chargement de l'objet document
     *
     * @param string $method méthode de chargement
     * @param bool $loadFromBase force le chargement depuis la base
     */
    public function load($method = 'DOCID', $loadFromBase = false)
    {
        $this->_loaded = false;
        if ($method == 'ID') {
            //Chargement à partir de l'identifiant du document
            $this->_docid = $this->getDocidFromId();
        }

        if ($loadFromBase == false && $this->cacheExist('phps')) {
            // Chargement à partir du cache phps
            $cache = $this->get('phps');
            if ($cache instanceof Hal_Document) {
                foreach (array_keys(get_object_vars($this)) as $attr) {
                    $this->$attr = $cache->$attr;
                }
                $this->_loaded = true;
            }
        }
        if (!$this->_loaded) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $sql = $db->select()->from(self::TABLE)->where('DOCID = ?', (int)$this->_docid);
            $row = $db->fetchRow($sql);
            if ($row) {
                $this->clear();
                // Infos générales
                $this->_docid = $row['DOCID'];
                $this->_identifiant = $row['IDENTIFIANT'];
                $this->_version = $row['VERSION'];
                $this->_pwd = $row['PWD'];
                $this->_status = $row['DOCSTATUS'];
                $this->_typdoc = $row['TYPDOC'];

                $user = new Ccsd_User_Models_DbTable_User();
                $contribInfo = $user->search($row['UID']);
                if ((is_array($contribInfo)) && (isset($contribInfo[0]))) {
                    $this->_contributor = array(
                        'lastname' => $contribInfo[0]['LASTNAME'],
                        'firstname' => $contribInfo[0]['FIRSTNAME'],
                        'fullname' => Ccsd_Tools::formatUser($contribInfo[0]['FIRSTNAME'], $contribInfo[0]['LASTNAME']),
                        'email' => $contribInfo[0]['EMAIL'],
                        'uid' => $row['UID']
                    );
                } else {
                    $this->_contributor = array('uid' => $row['UID']);
                }
                unset($user, $contribInfo);

                $this->setSid($row['SID']);
                $site = Hal_Site::loadSiteFromId($row['SID']);
                $this->_instance = $site->getSiteName();
                $this->_submittedDate = $row['DATESUBMIT'];
                $this->setReleasedDate($row['DATEMODER']);
                if ($this->_releasedDate == null) {
                    $this->_releasedDate = $this->_submittedDate;
                }
                $this->_modifiedDate = $row['DATEMODIF'];
                if ($this->_modifiedDate == null) {
                    $this->_modifiedDate = $this->_submittedDate;
                }
                $this->_format = $row['FORMAT'];

                // TODO: transformer cela en objet: class RelatedDocument
                //       et transferer le load dans cette nouvelle classe.
                // TODO: Stop aux tableaux
                // ressources liées
                /** TODO: A mettre dans Hal/Document/Relation */
                $sql = $db->select()
                    ->from(self::TABLE_RELATED)
                    ->where('DOCID = ?', (int)$this->_docid);
                foreach ($db->fetchAll($sql) as $row) {

                    $relatedDocument = new self (0, $row['IDENTIFIANT'], 0, false, false);
                    $relatedDocid = $relatedDocument->getDocidFromId();

                    if ($relatedDocid != '') {
                        $relatedDocument->setDocid($relatedDocid, false);
                        $relatedDocument->setSid(self::getDocumentSID($relatedDocument->getDocid()));

                        if ($relatedDocument) {
                            $this->_related[] = array(
                                'IDENTIFIANT' => $row['IDENTIFIANT'],
                                'URI' => $relatedDocument->getUri(),
                                'RELATION' => $row['RELATION'],
                                'INFO' => $row['INFO'],
                                'DATE' => $row['DATEMODIF']
                            );
                        }
                    }
                }

                // Utilisateurs propriétaires
                $sql = $db->select()
                    ->from(Hal_Document_Owner::TABLE, array('UID'))
                    ->where('IDENTIFIANT = ?', $this->_identifiant);
                $this->setOwner($db->fetchCol($sql));

                // Autres versions
                $sql = $db->select()
                    ->from(self::TABLE)
                    ->where('IDENTIFIANT = ?', $this->_identifiant)
                    ->order('DATESUBMIT ASC');
                foreach ($db->fetchAll($sql) as $row) {
                    $this->_versions[$row['VERSION']] = $row;
                }

                // Métadonnées
                $this->_metas->load($this->_docid);

                // Auteurs
                $sql = $db->select()
                    ->from(array(
                        'doc' => Hal_Document_Author::TABLE
                    ), array(
                        'DOCAUTHID',
                        'AUTHORID',
                        'QUALITY'
                    ))
                    ->where('doc.DOCID = ?', (int)$this->_docid)
                    ->order('doc.DOCAUTHID ASC');

                foreach ($db->fetchAll($sql) as $row) {

                    // Création de l'auteur, ajout de sa fonction et ajout au document
                    $author = new Hal_Document_Author($row['AUTHORID'], $this->_docid);
                    $author->setQuality($row['QUALITY']);
                    $author->setDocauthid($row['DOCAUTHID']);
                    $this->addAuthor($author, true);

                    // Liens Auteurs-Structures
                    $sql2 = $db->select()
                        ->from(Hal_Document_Author::TABLE_DOCAUTHSTRUCT , 'STRUCTID')
                        ->where('DOCAUTHID = ?', (int)$row['DOCAUTHID'])
                        ->order('AUTSTRUCTID ASC');
                    foreach ($db->fetchAll($sql2) as $row2) {
                        $author->addStructid((int)$row2['STRUCTID']);
                        $author->addStructidx($this->addStructure($row2['STRUCTID']));
                    }
                }

                // Fichiers
                if ($this->_format != self::FORMAT_NOTICE) {
                    $sql = $db->select()
                        ->from(Hal_Document_File::TABLE)
                        ->where('DOCID = ?', (int)$this->_docid)
                        ->order('MAIN DESC')
                        ->order('FILENAME ASC');
                    $racineDoc = $this->getRacineDoc();
                    foreach ($db->fetchAll($sql) as $row) {
                        $file = new Hal_Document_File();
                        $file->load($row, $racineDoc);
                        $this->addFile($file);
                    }

                    //Récupération de l'imagette du dépôt
                    $this->setThumbid($this->getThumb());
                }

                // Collections
                $this->_collections = Hal_Document_Collection::getCollections($this->_docid);

                // Date de production de la ressource : date ou conferenceStartDate ou writingDate sinon submittedDate
                $this->_producedDate = $this->createProducedDate();

                // Publication Date @see Hal_Document_Settings_Dates for configuration
                $this->_publicationDate = $this->createPublicationDate();

                // Date d'archivage
                $sql = $db->select()->from(Ccsd_Archive::TABLE_DONNEES, 'DATE_ACTION')
                    ->where('DOCID = ?', (int)$this->_docid)
                    ->where('ACTION = ?', "ARCHIVAGE")
                    ->order('DATE_ACTION ASC');
                $this->_archivedDate = $db->fetchOne($sql);

                //Citation
                $this->getCitation('ref');
                $this->getCitation('full');
                $this->getCoins();

                //auto-archivage -> un des propriétaires est auteur
                // TODO: C'est faux, cela...  Seulement si le deposant est un auteur.
                $contributeurs = [];
                $user = new Ccsd_User_Models_UserMapper();
                foreach ($this->getOwner() as $uid) {
                    $ownerInfo = [];
                    $ownerInfo = $user->find($uid);
                    if (!is_null($ownerInfo)) {
                        $contributeurs[] = strtolower($ownerInfo['LASTNAME'] . substr($ownerInfo['FIRSTNAME'], 0, 1));
                    }
                }
                foreach ($this->_authors as $author) {
                    if ($author instanceof Hal_Document_Author && in_array(strtolower($author->getLastname() . substr($author->getFirstname(), 0, 1)), $contributeurs)) {
                        $this->setSelfArchiving(true);
                        break;
                    }
                }

                $this->_loaded = true;

                // Mise en cache de l'objet
                $this->createCache('phps');
            } else {
                $this->_docid = 0;
                $this->_identifiant = '';
                $this->_version = 0;
            }
        }
    }


    /**
     * Temp kludge
     * True if typdoc == COMM + SubmitDate > '2006-01-01' + 'date' > '2017-01-01'
     * @param $dateFormatted
     * @return bool
     */
    private function isCracKludgeCondition($dateFormatted)
    {

        if (('COMM' == $this->getTypDoc())
            && (strtotime($this->getSubmittedDate()) > strtotime('2016-01-01'))
            && (strtotime($dateFormatted) > strtotime('2017-01-01'))) {
            return true;
        }

        return false;
    }

    /**
     * 2019-10 temp kludge for CNRS CRAC Application
     * TODO remove at end of CRAC +/- 2020-01-01
     * @param string $confStartDateFormatted
     * @param string $dateFormatted
     * @return string
     */
    private function cracKludge($confStartDateFormatted, $dateFormatted) {

            if ($this->isCracKludgeCondition($dateFormatted)) {
                return $dateFormatted;
            } else {
               return $confStartDateFormatted;
            }
    }


    /**
     * @return string
     */
    public function createProducedDate()
    {
        $producedDate = $this->_submittedDate == null ? date('Y-m-d') : substr($this->_submittedDate, 0, 10);
        // Date de production de la ressource : date ou conferenceStartDate ou writingDate sinon submittedDate

        $dateFormatted = Ccsd_Tools::str2date($this->getMeta('date'));
        if (!in_array($this->_typdoc, ['COMM', 'POSTER', 'PRESCONF', 'UNDEFINED']) && $dateFormatted != '') {
            $producedDate = $dateFormatted;
        } else {
            $confStartDateFormatted = Ccsd_Tools::str2date($this->getMeta('conferenceStartDate'));
            if (in_array($this->_typdoc, ['COMM', 'POSTER', 'PRESCONF']) && $confStartDateFormatted != '') {
                $producedDate = $this->cracKludge($confStartDateFormatted, $dateFormatted);
            } else {
                $writingDateFormatted = Ccsd_Tools::str2date($this->getMeta('writingDate'));

                if ($writingDateFormatted != '') {
                    $producedDate = $writingDateFormatted;
                }
            }
        }

        return $producedDate;
    }

    /**
     * Initialisation du format de dépôt (fichier ou non)
     *
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->_format = $format;
    }

    /**
     * Initialisation du type de document
     *
     * @param string $typdoc
     */
    public function setTypdoc($typdoc)
    {
        $this->addMeta('typdoc', $typdoc);
        $this->_typdoc = $typdoc;
    }

    /**
     * @param $related
     */
    public function setRelated($related)
    {
        $this->_related = $related;
    }

    /**
     * @return bool
     */
    public function getSelfArchiving()
    {
        return $this->_selfArchiving;
    }

    /**
     * @param bool $selfArchiving
     */
    public function setSelfArchiving($selfArchiving)
    {
        $this->_selfArchiving = $selfArchiving;
    }

    /**
     * @return array
     */
    public function getRelated()
    {
        return $this->_related;
    }

    /**
     * Récupération du type de document du dépôt
     *
     * @return string
     */
    public function getTypDoc()
    {
        //TODO : C'est ça qu'on devrait faire, non ?
        //return $this->_metas->getMeta('typdoc');
        return $this->_typdoc;
    }

    /**
     * Récupération de la licence du dépôt
     * @return string
     */
    public function getLicence()
    {
        return  $this->_metas->getMeta('licence');
    }

    /**
     * Définition de la licence sur les fichiers
     * @param $licence
     */
    public function setLicence($licence)
    {
        $this->_metas->setMeta('licence', $licence, "web", 0);
    }

    /**
     * Récupération de la date de soumission
     *
     * @param string $format format
     * @param string $local langue
     * @return string
     */
    public function getSubmittedDate($format = null, $local = null)
    {
        if ($format != null) {
            $date = new Ccsd_Date();
            $date->set($this->_submittedDate);
            return $date->get($format, $local);
        }
        return $this->_submittedDate;
    }

    /**
     * Récupération de la date d'archivage
     *
     * @param string $format format
     * @param string $local langue
     * @return string|bool
     */
    public function getArchivedDate($format = null, $local = null)
    {
        if ($this->_archivedDate) {
            if ($format != null) {
                $date = new Ccsd_Date();
                $date->set($this->_archivedDate);
                return $date->get($format, $local);
            }
            return $this->_archivedDate;
        }
        return false;
    }

    /**
     * Récupération de la date de dernière modification
     *
     * @param string $format format
     * @param string $local langue
     * @return string
     */
    public function getLastModifiedDate($format = null, $local = null)
    {
        if ($format != null) {
            $date = new Ccsd_Date();
            $date->set($this->_modifiedDate);
            return $date->get($format, $local);
        }
        return $this->_modifiedDate;
    }

    /**
     * Récupération de la date de production
     *
     * @param string $format format
     * @param string $local langue
     * @return string
     */
    public function getProducedDate($format = null, $local = null)
    {
        if (empty($this->_producedDate)) {
            $this->_producedDate = $this->createProducedDate();
        }

        if ($format != null) {
            $date = new Ccsd_Date();
            $date->set($this->_producedDate);
            return $date->get($format, $local);
        }

        return $this->_producedDate;
    }

    /**
     * Return the publication date
     * @param null $format
     * @param null $locale
     * @return bool|false|string
     * @throws Zend_Date_Exception
     */
    public function getPublicationDate($format = null, $locale = null)
    {
        if (empty($this->_publicationDate)) {
            $this->_publicationDate = $this->createPublicationDate();
        }

        if ($format != null) {
            $date = new Ccsd_Date();
            $date->set($this->_publicationDate);
            $this->_publicationDate = $date->get($format, $locale);
        }
        return $this->_publicationDate;
    }


    /**
     * Create a publication Date
     * Use a config array from Hal_Document_Settings_Dates
     * @see Hal_Document_Settings_Dates
     * @return bool|false|string
     */
    public function createPublicationDate()
    {
        $publicationDate = '';

        foreach (Hal_Document_Settings_Dates::getPublicationDateMethods($this->getTypDoc()) as $dateMethod) {

            if ($dateMethod == Hal_Document_Settings_Dates::TYPE_SUBMITTED_DATE) {
                $publicationDate = $this->getSubmittedDate("YYYY-MM-dd");
            } else {
                $publicationDate = Ccsd_Tools::str2date($this->getHalMeta()->getMeta($dateMethod));
            }

            // Stop ASA we get a string date
            if (!empty($publicationDate)) {
                break;
            }
        }

        // Eventually we must have a date : Hal_Document_Settings_Dates::TYPE_SUBMITTED_DATE
        if (empty($publicationDate)) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, 'Eventually, publication date for '. $this->getId(true) . ' is empty: it is a bug');
        }

        return $publicationDate;
    }

    /**
     * Initialisation des fichiers d"un article
     *
     * @param array $files
     */
    public function setFiles($files)
    {
        $this->_files = array();
        $this->addFiles($files);
    }

    /**
     * Ajout de fichiers au document
     *
     * @param array $files
     */
    public function addFiles($files)
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }
    }

    /**
     * Ajout d'un fichier au document
     *
     * @param Hal_Document_File|array $file
     * @param bool
     * @return int
     */
    public function addFile($file)
    {
        if ((is_array($file) && isset($file['name']))) {
            $name = $file['name'];
            $size = $file['size'];
        } else if ($file instanceof Hal_Document_File) {
            $name = $file->getName();
            $size = $file->getSize(true);
        }

        if ($this->existFileName($name)) {

            $idx = $this->getFileIdByName($name);
            $fileObj = $this->getFileByFileIdx($idx);
            $fileObj->setSize($size);

            return $idx;
        }

        if ($file instanceof Hal_Document_File) {
            $halFile = $file;
        } else {
            $halFile = new Hal_Document_File(count($this->_files));
            $halFile->set($file);
        }

        $this->_files[] = $halFile;
        return max(array_keys($this->_files));
    }

    /**
     * Mise à jour du fichier principal et ses métadonnées associés
     * Si on avait un fichier principal (FP) et un fichier à mettre à jour =>
     *
     * @param $filename : nom du fichier à mettre à jour
     */
    public function majMainFile($filename)
    {
        foreach ($this->_files as $file) {
            if ($file->getName() == $filename) {
                $file->setDefault(!$file->getDefault());
            } else if ($file->getDefault()) {
                $file->setDefault(false);
            }
        }
    }

    /**
     * Renvoie la liste des fichiers qui peuvent être principaux
     *
     * @return array
     */
    public function getMainableFiles ()
    {
        $mainables = [];

        foreach ($this->_files as $idx => $file) {
            if ($file->getType() == Hal_Settings::FILE_TYPE) {
                $mainables[] = $idx;
            }
        }
        return $mainables;
    }

    /**
     * @return Hal_Document_File|bool
     */

    public function getDefaultFile()
    {
        foreach ($this->_files as $file) {
            if ($file->getDefault() && $file->getType() == Hal_Settings::FILE_TYPE) {
                return $file;
            }
        }
        return false;
    }

    /**
     * @return string|null
     */
    public function getDefaultFileThumb()
    {
        foreach ($this->_files as $file) {
            if ($file->getDefault() && $file->getType() == Hal_Settings::FILE_TYPE) {
                return $file->getImagette();
            }
        }
        return null;
    }

    /**
     * @return string|null
     */
    public function getDateVisibleMainFile()
    {
        foreach ($this->_files as $file) {
            if ($file->getDefault() && $file->getType() == Hal_Settings::FILE_TYPE) {
                return $file->getDateVisible();
            }
        }
        return null;
    }

    /**
     * Calcule la première date de visibilité des fichiers de type file
     * @return string
     */
    public function getFirstDateVisibleFile()
    {
        $date = $this->getDateVisibleMainFile();
        foreach ($this->_files as $file) {
            if ($file->getType() == Hal_Settings::FILE_TYPE) {
                if (strtotime($file->getDateVisible()) < strtotime($date)) {
                    $date = $file->getDateVisible();
                }
            }
        }
        return $date;
    }

    /**
     * @return string
     */
    public function getUrlMainFile()
    {
        if ($this->_typdoc == 'IMG') {
            return '/image';
        }
        return '/document';
    }

    /**
     * Retourne les fichiers associés au dépôt
     *
     * @return Hal_Document_File[]
     */
    public function getFiles()
    {
        return $this->_files;
    }

    /**
     * @param string $type
     * @param bool $excludeDefault
     * @return Hal_Document_File[]
     */
    public function getFilesByType($type, $excludeDefault = true)
    {
        $files = array();
        foreach ($this->_files as $f) {
            if ($f->getType() == $type) {
                if ($type == Hal_Settings::FILE_TYPE && $excludeDefault && $f->getDefault()) continue;
                $files[] = $f;
            }
        }
        return $files;
    }

    /**
     * @param bool $gallery
     * @return Hal_Document_File[]
     */
    public function getFilesAnnex($gallery = false)
    {
        // En cas de gallery, on veut les images dont on sait faire les vignettes, sans embargo

        $filesGallery = array();
        $filesFichiers = array();
        foreach ($this->_files as $f) {
            if ($f->getType() == Hal_Settings::FILE_TYPE_ANNEX) {
                $visible = $f->canRead();
                if ($visible
                    && ($f->getFormat() == 'figure')
                    && (strstr($f->getTypeMime(), 'image'))) {
                    $filesGallery[] = $f;
                } else {
                    $filesFichiers[] = $f;
                }
            }
        }
        if ($gallery){
            return $filesGallery;
        } else {
            return $filesFichiers;
        }
    }

    /**
     * @param $id
     * @return Hal_Document_File|null
     */
    public function getFileByFileId($id)
    {
        foreach ($this->_files as $f) {
            if ($f->getFileid() == (int)$id) {
                return $f;
            }
        }
        return null;
    }

    /**
     * @param $id
     * @return Hal_Document_File|null
     */
    public function getFileByFileIdx($id)
    {
        foreach ($this->_files as $i => $f) {
            if ($i == (int)$id) {
                return $f;
            }
        }
        return null;
    }

    /**
     * @param $name
     * @return null|string
     */
    public function getFileThumbByFilename($name)
    {
        foreach ($this->getFiles() as $f) {
            if ($f->getName() == urldecode($name)) {
                return $f->getImagette();
            }
        }
        return null;
    }

    /**
     * Retourne un identifiant de miniature
     * @return null|string
     */
    public function getThumb()
    {
        // imagette annexe principale
        foreach ($this->getFiles() as $f) {
            if ($f->getType() == Hal_Settings::FILE_TYPE_ANNEX && $f->getDefaultannex() && $f->getFormat() == 'figure') {
                return $f->getImagette();
            }
        }
        // ou imagette fichier principal
        return $this->getDefaultFileThumb();
    }

    /**
     * Retourne un fichier associé à un dépôt
     *
     * @param int|string $id
     * @return Hal_Document_File|bool
     */
    public function getFile($id)
    {
        if (is_numeric($id) && array_key_exists($id, $this->getFiles())) {
            return $this->_files[$id];
        } else {
            foreach ($this->getFiles() as $file) {
                if ($file->getName() == rawurldecode($id) || $file->getName() == $id) { //Pourquoi rawurldecode ?
                    return $file;
                }
            }
        }
        return false;
    }

    /**
     * @return Hal_Document_File[]
     */
    public function loadFiles()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        if ($this->getFormat() != self::FORMAT_NOTICE) {
            $sql = $db->select()
                ->from(Hal_Document_File::TABLE)
                ->where('DOCID = ?', (int)$this->getDocid())
                ->order('MAIN DESC')
                ->order('FILENAME ASC');
            $racineDoc = $this->getRacineDoc();
            foreach ($db->fetchAll($sql) as $row) {
                $file = new Hal_Document_File();
                $file->load($row, $racineDoc);
                $this->addFile($file);
            }
        }
        return $this->getFiles();
    }


    /**
     * Retourne le nombre de fichiers
     * @return int
     */
    public function getFileNb()
    {
        return count($this->_files);
    }

    /**
     * Indique si un fichier existe
     *
     * @param int $id
     * @return bool
     */
    public function existFile($id = null)
    {
        if ($id == null) {
            return count($this->_files);
        }
        return isset($this->_files[$id]);
    }

    /**
     * Indique si un fichier existe
     *
     * @param string $name
     * @return bool
     */
    public function existFileName($name = '')
    {
        if ($name == '') {
            return false;
        }
        $decodedName = urldecode($name);
        foreach ($this->_files as $file) {
            if ($file->getName() == $decodedName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retourne id fichier
     *
     * @param string $name
     * @return bool|int
     */
    public function getFileIdByName($name = '')
    {
        if ($name == '') {
            return false;
        }
        $decodedName = urldecode($name);
        foreach ($this->_files as $id => $file) {
            if ($file->getName() == $decodedName) {
                return $id;
            }
        }
        return false;
    }

    /**
     * Suppression d'un fichier
     *
     * @param int $id
     * @return boolean
     */
    public function delFile($id)
    {
        if ($this->existFile($id)) {
            if (is_file($this->getFile($id)->getPath())) {
                unlink($this->getFile($id)->getPath());
            }
            unset($this->_files[$id]);

            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function delFiles()
    {
        $res = true;
        foreach ($this->_files as $id => $file) {
            $res = $res && $this->delFile($id);
        }
        return $res;
    }

    /**
     *
     */
    public function initFiles()
    {
        $this->_files = array();
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Initialisation des métadonnées d'un article
     *
     * @param array $metas
     */
    public function setMetas($metas, $uid = 0, $source = "web")
    {
        $this->_metas->clearMetas();
        $this->addMetas($metas, $uid, $source);
    }

    /**
     * @param array  $metas
     * @param int    $uid
     * @param string $source
     */
    public function addMetas($metas, $uid = 0, $source = "web")
    {
        $this->_metas->addMetasFromArray($metas, $source, $uid);
    }

    /**
     * @param Hal_User $user
     */
    public function addUserMetas($user)
    {
        $this->_metas->addMetasFromUser($user);
    }

    /** On ajoute les auteurs depuis les préférences de dépot d'un utilisateur
     *
     */
    public function addUserAuthors(Hal_User $user)
    {
        if ($user->getDefault_author()) {
            //L'utilisateur est auteur par défaut
            $docAuthor = new Hal_Document_Author();
            $new = false;

            //On regarde s'il a un CV
            $cv = new Hal_Cv(0, '', $user->getUid());
            $cv->load(false);
            if ($cv->exist() && $cv->getCurrentFormAuthorId() != 0) {
                //on récupère la forme auteur par défaut du CV
                $docAuthor->setAuthorid($cv->getCurrentFormAuthorId());
            } else {
                //On rajoute un nouvel auteur au référentiel
                $new = true;
                $docAuthor->setLastname($user->getLastname());
                $docAuthor->setFirstName($user->getFirstname());
                $docAuthor->setOthername($user->getMiddlename());
                $docAuthor->setEmail($user->getEmail());
            }

            $docAuthor->load();

            // On ajoute les préférences de l'utilisateur : role de l'auteur, etablissement employeur, labos
            $docAuthor->setQuality($user->getDefault_Role());

            if (!empty($user->getInstitution())) {
                $struct = new Ccsd_Referentiels_Structure($user->getInstitution()[0]);
                $docAuthor->setOrganism($struct->getStructname());
            }

            // Ajout du document au dépot
            if ($new || $docAuthor->getAuthorid() != 0 ) {
                $this->addAuthorWithAffiliations($docAuthor, $user->getLaboratory());
            }
        }
    }

    /**
     * Efface toutes les métadonnées
     * @return Hal_Document
     */
    public function clearMetas()
    {
        //Attention il faut garder la licence => pourquoi ??
        $this->_metas->clearMetas('licence');

        return $this;
    }

    /**
     * Initialisation d'une métadonnée d'un article
     *
     * @param string $meta
     * @param string|array $value
     */
    public function addMeta($key, $value, $uid = 0)
    {
        // Est ce que c'est forcément manuel ?
        $this->_metas->addMeta($key, $value, "web", $uid);
    }

    /**
     * @param Hal_Document_Metadatas $newMetas
     */
    public function mergeHalMeta(Hal_Document_Metadatas $newMetas)
    {
        $this->_metas->merge($newMetas);
    }

    /**
     * @param Hal_Document_Metadatas $newMetas
     */
    public function setHalMeta(Hal_Document_Metadatas $newMetas)
    {
        $this->_metas = $newMetas;
    }

    /**
     * Suppression d'une métadonnée
     *
     * @param string $meta
     */
    public function delMeta($key)
    {
        $this->_metas->delMeta($key);
    }

    /**
     * Récupération des métadonnées du document
     * @deprecated Use indirection by getHalMeta -> getMeta()
     * @param string $meta
     * @return array|string
     */
    public function getMeta($meta = null)
    {
        // On veut acceder au identifiant comme si c'etait des noms de meta...
        //
        if (in_array($meta, self::$_serverCopy)) {
            return $this->_metas->getMeta('identifier', $meta);
        } else {
            return $this->_metas->getMeta($meta);
        }
    }

    /**
     * @param $meta
     * @return Hal_Document_Meta_Abstract
     */
    public function getMetaObj($meta) {
        return $this->_metas->getHalMeta($meta);
    }
    /**
     * @return Hal_Document_Metadatas
     */
    public function getHalMeta()
    {
        return $this->_metas;
    }

    /**
     *
     * @param $source
     */
    public function getMetasFromSource($source)
    {
        return $this->_metas->getMetaKeysFromSource($source);
    }

    /**
     * Récupération de la signature COinS du document
     *
     * @param bool
     * @return string encoded url parameters
     */
    public function getCoins($reload = false)
    {
        if ($reload || $this->_coins == '') {
            $typeDC = array('UNDEFINED' => 'document', 'ART' => 'journal', 'OUV' => 'book', 'COUV' => 'incollection', 'COMM' => 'proceedings', 'POSTER' => 'poster', 'REPORT' => 'report', 'THESE' => 'phdthesis', 'HDR' => 'thesis', 'ETABTHESE' => 'thesis', 'LECTURE' => 'lecture', 'IMG' => 'image', 'VIDEO' => 'video', 'PATENT' => 'patent', 'DOUV' => 'book', 'SON' => 'sound');
            $param = array();
            $param[] = 'ctx_ver=Z39.88-2004';
            $param[] = 'rft_val_fmt=' . rawurlencode('info:ofi/fmt:kev:mtx:dc');
            $param[] = 'rft.type=' . (array_key_exists($this->_typdoc, $typeDC) ? $typeDC[$this->_typdoc] : 'other');
            $param[] = 'rft.identifier=' . rawurlencode($this->getUri());
            $param[] = 'rft.identifier=' . rawurlencode($this->getId());
            foreach ($this->_metas->getMeta('identifier') as $code => $id) {
                $param[] = 'rft.identifier=' . rawurlencode($code . ':' . $id);
            }
            $param[] = 'rft.title=' . rawurlencode($this->getMainTitle());
            foreach ($this->getAuthors() as $author) {
                $param[] = 'rft.creator=' . rawurlencode($author->getEncodedName());
            }
            if (is_array($this->getKeywords())) {
                foreach ($this->getKeywords() as $kws) {
                    if (is_array($kws)) {
                        $param[] = 'rft.subject=' . implode('&amp;rft.subject=', array_map('rawurlencode', $kws));
                    } else {
                        $param[] = 'rft.subject=' . rawurlencode($kws);
                    }
                }
            }

            $param[] = 'rft.language=' . $this->getMeta(Ccsd_Externdoc::META_LANG);
            $param[] = 'rft.date=' . $this->getProducedDate();
            /** @var Ccsd_Referentiels_Journal  $oJ */
            if (($oJ = $this->getMeta('journal')) instanceof Ccsd_Referentiels_Journal && $oJ->JNAME) {
                $param[] = 'rft.source=' . rawurlencode($oJ->JNAME);
            }
            if ($this->getMeta('bookTitle') != '') {
                $param[] = 'rft.source=' . rawurlencode($this->getMeta('bookTitle'));
            }
            if ($this->getMeta('conferenceTitle') != '') {
                $param[] = 'rft.source=' . rawurlencode($this->getMeta('conferenceTitle'));
            }
            if ($this->getMeta('city') != '') {
                $param[] = 'rft.coverage=' . rawurlencode($this->getMeta('city'));
            }
            if ($this->getMeta('country') != '') {
                $param[] = 'rft.coverage=' . rawurlencode(Zend_Locale::getTranslation(strtoupper($this->getMeta('country')), 'country', 'en'));
            }
            $this->_coins = '<span class="Z3988" title="' . implode('&amp;', $param) . '"></span>';
        }
        return $this->_coins;
    }


    /**
     * Page de garde du PDF principal
     * @param string[] $needsFiles
     * @return bool
     */
    public function makeTexCoverPage( &$needsFiles )
    {
        $lang =''; //to get the title language

        /**  TODO: Revoir la transformation... Il faut pouvoir dire si une chaine est ascii, html ou latex et faire les transformation en connaissance de cause.
         *   */
        $bruteTitle = strip_tags($this->getMainTitle(false, $lang));
        // Latex treat greek characters so if greek, it's not math, so don't translate greek into math symbol
        $doGreek = ($lang != 'el');
        $title    = Ccsd_Tools::htmlToTex($bruteTitle, $doGreek);
        $authors  = Ccsd_Tools::htmlToTex(strip_tags($this->getListAuthors(10)), $lang=!'el') ;
        $citation = Ccsd_Tools::htmlToTex(strip_tags($this->getCitation('full')), $doGreek) ;
        // Hum apres cela, il reste des caractere Latex indesirable...
        // Exemple: %
        // Il ne devrait pas y avoir de commentaire dans les meta, donc on escape
        // Idem pour &
        $title    = Ccsd_Tools::protectCoverpage($title);
        $authors  = Ccsd_Tools::protectCoverpage($authors);  // ok with array
        $citation = Ccsd_Tools::protectCoverpage($citation);

        $unicode = new Ccsd_Tex_Unicode($lang);
        $MultiLangTitle = $unicode ->parseXelatexLingualsCommand($title);
        $MultiAuthors   = $unicode ->parseXelatexLingualsCommand($authors);
        $MultiCitation  = $unicode ->parseXelatexLingualsCommand($citation);

        $headers = $unicode->headers();
        $tex = '\documentclass[11pt,a4paper]{article}' . PHP_EOL;
        $tex .= '%% -*- latex-command: xelatex -*-' . PHP_EOL;
        // $tex .= '\pdfoutput=1' . PHP_EOL;
        $tex .= '\usepackage{xunicode}' . PHP_EOL;
        $tex .= '\usepackage{xltxtra}' . PHP_EOL;

        if (($MultiLangTitle != $title) || ($headers != [])) {
            $tex .= '\usepackage{polyglossia}' . PHP_EOL;
            $tex .= '\setmainfont{Linux Libertine O}' . PHP_EOL;
            $tex .= '\setmainlanguage{english}' . PHP_EOL;
            foreach ($headers as $h) {
                $tex .= $h . PHP_EOL;
            }
        }
        $tex .= '\usepackage[absolute]{textpos}' . PHP_EOL;
        $tex .= '\usepackage{setspace}' . PHP_EOL;
        $tex .= '\usepackage{color}' . PHP_EOL;
        $tex .= '\usepackage{array}' . PHP_EOL;
        $tex .= '\usepackage{graphicx}' . PHP_EOL;
        $tex .= '\usepackage{multicol}' . PHP_EOL;
        // *** you should *not* be loading the inputenc package
        // *** XeTeX expects the source to be in UTF8 encoding
        // *** some features of other encodings may conflict, resulting in poor output.
        // $tex .= '\usepackage[utf8]{inputenc}' . PHP_EOL;
        $tex .= '\usepackage[xetex,unicode=true,hyperfootnotes=false,colorlinks=true,citecolor=black,filecolor=black,linkcolor=black,urlcolor=black,pdfborder={0 0 0}]{hyperref}' . PHP_EOL;
        $tex .= '\hypersetup{%' . PHP_EOL;
        $tex .= 'pdfstartview={Fit},%' . PHP_EOL;
        $tex .= 'pdftitle={' . $title. "},%" . PHP_EOL;
        $table = array();
        foreach ($this->getAuthors() as $author) {
            $table[] = Ccsd_Tools::htmlToTex($author->getFullname(), $doGreek);
        }
        $tex .= 'pdfauthor={' . implode(', ', $table) . '},%' . PHP_EOL;
        unset($table);
        $tex .= 'pdfkeywords={';
        foreach ($this->getKeywords() as $kws) {
            if (is_array($kws)) {
                $tex .= Ccsd_Tools::htmlToTex(implode(', ', $kws), $doGreek);
            } else {
                $tex .= Ccsd_Tools::htmlToTex($kws, $doGreek);
            }
        }
        $tex .= '},%' . PHP_EOL;
        $tex .= 'pdfcreator={HAL},%' . PHP_EOL;
        $tex .= 'pdfproducer={PDFLaTeX},%' . PHP_EOL;
        $table = array();
        foreach ($this->getHalMeta()->getMeta('domain') as $domain) {
            $table[] = Ccsd_Tools_String::getHalDomainTranslated($domain, 'en', '/', false);
        }
        $tex .= 'pdfsubject={' . implode(', ', array_unique($table)) . '}}' . PHP_EOL;
        unset($table);
        $tex .= '\urlstyle{same}' . PHP_EOL;
        $tex .= '\pagestyle{empty}' . PHP_EOL;
        // On remonte sur la place de la page d'entete.
        $tex .= '\setlength{\topmargin}{-2cm}' . PHP_EOL;
        $tex .= '\setlength{\headheight}{0cm}' . PHP_EOL;
        $tex .= '\setlength{\headsep}{0cm}' . PHP_EOL;
        // Textheight page - la taille des block fixes
        $tex .= '\setlength{\textheight}{18.7cm}' . PHP_EOL;
        $tex .= '\setlength{\textwidth}{17cm}' . PHP_EOL;
        $tex .= '\setlength{\oddsidemargin}{0cm}' . PHP_EOL;
        $tex .= '\setlength{\evensidemargin}{0cm}' . PHP_EOL;
        $tex .= '\setlength{\parindent}{0.25in}' . PHP_EOL;
        $tex .= '\setlength{\parskip}{0.25in}' . PHP_EOL;

        $tex .= '\newlength{\posXlogo}' . PHP_EOL;
        $tex .= '\newlength{\posYlogo}' . PHP_EOL;
        $tex .= '\setlength{\posXlogo}{2cm}' . PHP_EOL;
        $tex .= '\setlength{\posYlogo}{23.5cm}' . PHP_EOL;
        $tex .= '\newlength{\posXident}' . PHP_EOL;
        $tex .= '\newlength{\posYident}' . PHP_EOL;
        $tex .= '\setlength{\posXident}{2cm}' . PHP_EOL;
        $tex .= '\setlength{\posYident}{19cm}' . PHP_EOL;
        $tex .= '\newlength{\posXhal}' . PHP_EOL;
        $tex .= '\newlength{\posYhal}' . PHP_EOL;
        $tex .= '\setlength{\posXhal}{2cm}' . PHP_EOL;
        $tex .= '\setlength{\posYhal}{23.5cm}' . PHP_EOL;
        $tex .= '\newlength{\posXcc}' . PHP_EOL;
        $tex .= '\newlength{\posYcc}' . PHP_EOL;
        $tex .= '\setlength{\posXcc}{2cm}' . PHP_EOL;
        $tex .= '\setlength{\posYcc}{\dimexpr\paperheight-4cm\relax}' . PHP_EOL;

        $tex .= '%\usepackage{pdfpages}' . PHP_EOL;
        // $tex .= '\pdfoptionpdfminorversion 6' . PHP_EOL . PHP_EOL; // for pdflatex, not xelatex
        $tex .= '\begin{document}' . PHP_EOL;
        $tex .= '\topskip0pt\vspace*{0cm}' . PHP_EOL;
        // ---------- LOGO ----------------
        $tex .= '\begin{center}' . PHP_EOL;
        $tex .= '\href{https://hal.archives-ouvertes.fr}{\includegraphics[width=0.25\textwidth]{hal-ao.jpg}} \\\\' . PHP_EOL;
        $needsFiles['hal-ao.jpg'] = APPLICATION_PATH . '/../public/img/hal-ao.jpg';
        $tex .= '\vfill' . PHP_EOL;
        // -------- TITRE / AUTEURS --------

        $tex .= '\begin{doublespace}' . PHP_EOL;
        $tex .= '{\LARGE \textbf{' . $MultiLangTitle . '}} \\\\' . PHP_EOL;
        $tex .= '{\Large ' . $MultiAuthors . '}' . PHP_EOL;
        $tex .= '\end{doublespace}' . PHP_EOL;
        $tex .= '\end{center}' . PHP_EOL;
        // ---------- CITATION -------------
        $tex .= '{\includegraphics[width=8pt]{triangle.png}}{\Large \textbf{~To cite this version:}} \\\\\\\\' . PHP_EOL;
        $needsFiles['triangle.png'] = APPLICATION_PATH . '/../public/img/triangle.png';
        $tex .= '\begin{tabular}{|p{\textwidth}}' . PHP_EOL;
        $tex .= '{' . $MultiCitation . '} \\\\' . PHP_EOL;
        $tex .= '\end{tabular}' . PHP_EOL;
        $tex .= '\vfill' . PHP_EOL;
        // ---------- ID ? URL -------------
        $tex .= '\begin{textblock*}{\textwidth}(\posXident , \posYident)' . PHP_EOL;
        $tex .= '\begin{center}' . PHP_EOL;
        $tex .= '\begin{doublespace}' . PHP_EOL;
        $tex .= '{\Large \textbf{HAL Id: ' . Ccsd_Tools::htmlToTex($this->getId()) . '}} \\\\' . PHP_EOL;
        $tex .= '{\Large \textbf{\url{' . Ccsd_Tools::htmlToTex($this->getUri(true)) . '}}} \\\\' . PHP_EOL;
        // --------- DATE et VERSION --------
        $dateFormat=Zend_Date::DAY_SHORT . ' ' . Zend_Date::MONTH_NAME_SHORT . ' ' . Zend_Date::YEAR;
        $tex .= 'Submitted on ' . $this->getSubmittedDate($dateFormat, 'en');
        // There is some documents with only a version 5!!!  Taking count give 1 not 5
        // Plusieurs versions et ce n'est pas la derniere
        if (count($this->getDocVersions()) >=1) {
            /** @var int $lastVersion */
            $lastVersion = max(array_keys($this->getDocVersions()));
            $lastVersionDate = Hal_Document::getDateVersionFromDocRow($this->getDocVersions()[$lastVersion]);
            if ($this->_version < $lastVersion) {
                $date = new Zend_Date();
                $date->set($lastVersionDate, 'Y-MM-d H:i:s');
                $tex .= ' (v' . $this->_version . '), last revised ' . $date->get($dateFormat, 'en') . ' (v' . $lastVersion . ')';
            }
        }
        $tex .= PHP_EOL;
        $tex .= '\end{doublespace}' . PHP_EOL;
        $tex .= '\end{center}' . PHP_EOL;
        $tex .= '\end{textblock*}' . PHP_EOL;
        // ----------  Pres HAL ----------------
        $tex .= '\begin{textblock*}{\textwidth}(\posXhal , \posYhal)' . PHP_EOL;
        $tex .= '\begin{multicols}{2}' . PHP_EOL;
        $tex .= '\textbf{HAL} is a multi-disciplinary open access archive for the deposit and dissemination of scientific research documents, whether they are published or not. The documents may come from teaching and research institutions in France or abroad, or from public or private research centers.' . PHP_EOL;
        $tex .= '\vfill' . PHP_EOL;
        $tex .= '\columnbreak' . PHP_EOL;
        $tex .= 'L\'archive ouverte pluridisciplinaire \textbf{HAL}, est destinée au dépôt et à la diffusion de documents scientifiques de niveau recherche, publiés ou non, émanant des établissements d\'enseignement et de recherche français ou étrangers, des laboratoires publics ou privés.' . PHP_EOL;
        $tex .= '\end{multicols}' . PHP_EOL;
        $tex .= '\end{textblock*}' . PHP_EOL;
        // -----------  LICENCE ----------------
        if ($this->getLicence() != '' && in_array($this->getLicence(), Hal_Settings::getKnownLicences())) {
            $tex .= '\begin{textblock*}{\textwidth}(\posXcc , \posYcc)' . PHP_EOL;
            $tex .= '\begin{center}' . PHP_EOL;

            if (array_key_exists('url', Hal_Settings::getLicenceInfos($this->getLicence())) && array_key_exists('icon', Hal_Settings::getLicenceInfos($this->getLicence())) && is_array(Hal_Settings::getLicenceInfos($this->getLicence())['icon'])) {
                $licence = Hal_Settings::getLicenceInfos($this->getLicence());
                $tex .= '\href{' . $licence['url'] . '}{';
                foreach ($licence['icon'] as $icon) {
                    $tex .= '{\includegraphics[width=24pt]{' . $icon . '.png}}';
                    $needsFiles[$icon . '.png'] = APPLICATION_PATH . '/../public/img/licences/' . $icon . '.png';
                }
                if (strpos($this->getLicence(), 'creativecommons') !== false) {
                    $tex .= '\\\\ Distributed under a Creative Commons \\verb|' . Ccsd_Tools::translate(Hal_Referentiels_Metadata::getLabel('licence', $this->getLicence()), 'en') . '| 4.0 International License' . PHP_EOL;
                } else {
                    $tex .= '\\\\ \\verb|' . Ccsd_Tools::translate(Hal_Referentiels_Metadata::getLabel('licence', $this->getLicence()), 'en') . '|'. PHP_EOL;
                }
                $tex .= "}"; // Fermeture \href
            } else {
                $tex .= '\\verb|' . Ccsd_Tools::translate(Hal_Referentiels_Metadata::getLabel('licence', $this->getLicence()), 'en')  . '|' . PHP_EOL;
            }
            $tex .= '\end{center}'          . PHP_EOL;
            $tex .= '\end{textblock*}'      . PHP_EOL;

        }
        return $tex . '\end{document}'  . PHP_EOL;
    }

    /**
     * Concat 2 pdf file with the first library pdfbox which give a good result
     *        Try to give the best result, so try some pdfbox version or copy the second file  without merging
     * Return true if destination file is Ok
     *        False if dest can't be created
     * @param $resultat
     * @param $first
     * @param $second
     * @return bool
     */
    public function mergePDF($resultat, $first, $second) {
        setlocale(LC_CTYPE, "fr_FR.UTF-8"); // escapeshellarg strip les lettres accentuees si on n'est pas dans une locale Utf8
        if (!file_exists($second)) {
            // Le fichier principal n'existe pas, la concatenation va echouer...
            return false;
        }
        $shellFirst  = escapeshellarg($first) ;
        $shellSecond = escapeshellarg($second);
        $shellResult = escapeshellarg($resultat);
        $pdfboxVerions = explode(',' , PDFDOCAPP);
        foreach ($pdfboxVerions as $jarfile) {
            shell_exec("java -jar $jarfile PDFMerger $shellFirst $shellSecond $shellResult");
            if (file_exists($resultat) && (filesize($resultat) > (filesize($second) / 2))) {
                return true;
            }
        }
        return copy($second, $resultat);
    }

    /**
     * Page de garde du PDF principal
     *
     * @return bool   (true if the dest file exist, else false)
     * ture don't means that merging was done, just that a file was created (meerging or by copy original file
     */
    public function makeCoverPage($destdir = null) {
        if ($destdir === null) {
            $destdir = $this->getRacineCache();
        }
        if ($this->getFormat() == self::FORMAT_FILE) {
            $needsFiles = [];
            $tex = $this -> makeTexCoverPage($needsFiles);

            // Creation du Zip pour la compilation de la page de garde
            $zip = new ZipArchive;
            $uniqid = uniqid($this->_docid);
            $zipfile = PATHTEMPDOCS . 'tmp' . $uniqid . '.zip';

            if ($zip->open($zipfile, ZipArchive::CREATE)) {
                $zip->addFromString('paper.tex', $tex);
                foreach ($needsFiles as $name => $file) {
                    $zip->addFile($file, $name);
                }
                $zip->close();
                // Compilation Latex
                $generatedPdf = Ccsd_File::compile(PATHTEMPDOCS, 'tmp' . $uniqid . '.zip', 'paper' . $uniqid . '.pdf', false, false);
                @unlink($zipfile);
                $generatedfile = PATHTEMPDOCS . 'paper' . $uniqid . '.pdf';
                if ($generatedPdf['status'] && is_file($generatedfile)) {
                    // PDFMerger with pdfbox
                    $pdffile = $destdir . '/' . $this->_docid . '.pdf';
                    // $pdffile    = PATHTEMPDOCS . 'merge' . $uniqid . '.pdf';  // ficher resultat
                    $coverPage  = PATHTEMPDOCS . 'paper' . $uniqid . '.pdf';  // cover page
                    $documentFile = $this->getDefaultFile()->getPath();       // document
                    $mergeResult = $this->mergePDF($pdffile, $coverPage, $documentFile);
                    @unlink($generatedfile);

                    return $mergeResult;
                }
                // Pas meme pu faire la compil, on copie le fichier original
                file_put_contents(PATHTEMPDOCS . 'coverpage.log', 'Docid : ' . $this->getDocid() . '; ' . $generatedPdf['out'] . PHP_EOL, FILE_APPEND);
                $filetocopy = $this->getDefaultFile()->getPath();
                $rescopy = @copy($filetocopy, $this->getRacineCache() . '/' . $this->_docid . '.pdf');
                if (!$rescopy) {
                    Ccsd_Tools::panicMsg(__FILE__,__LINE__, "File $filetocopy n'existe pas!");
                }
                return $rescopy;
            }
        }
        return false;
    }

    /**
     * Ajout Watermark + Métas IPTC+XMP
     */
    public function makeWatermark()
    {
        if ($this->getFormat() == self::FORMAT_FILE) {
            $jpeg = $this->getDefaultFile();
            if ($jpeg->getTypeMIME() == 'image/jpeg') {
                // watermark
                if (($this->getHalMeta() -> getMeta('watermark') == '1') &&
                    (($size = getimagesize($jpeg->getPath())) !== false)
                ) {
                    $width = $size[0];
                    $height = $size[1];
                    if ($width > 280 && $height > 100) {
                        $image = imagecreatefromjpeg($jpeg->getPath());
                    } else {
                        return false;
                    }
                    $black    = imagecolorallocatealpha($image, 0, 0, 0, 0);
                    $black100 = imagecolorallocatealpha($image, 0, 0, 0, 100);
                    $white =    imagecolorallocatealpha($image, 255, 255, 255, 0);
                    $white80  = imagecolorallocatealpha($image, 255, 255, 255, 80);
                    $red = imagecolorallocatealpha($image, 235, 0, 0, 0);
                    imagefilledrectangle($image, $width - intval($width / 4), $height - intval($height / 6), $width, $height, $white80);
                    imagefilledrectangle($image, $width - intval($width / 4) - 3, $height - intval($height / 6) - 3, $width - 3, $height - 3, $black100);
                    $tb = imagettfbbox(intval($width / 40), 0, APPLICATION_PATH . '/../' . PUBLIC_DEF . 'fonts/arialnb.ttf', 'Médi');
                    imagettftext($image, intval($width / 40), 0, $width - intval($width / 8) - abs($tb[2] - $tb[0]), $height - intval($height / 12) - abs($tb[5] - $tb[3]) / 2, $white, APPLICATION_PATH . '/../' . PUBLIC_DEF . 'fonts/arialnb.ttf', 'Médi');
                    imagettftext($image, intval($width / 40), 0, $width - intval($width / 8), $height - intval($height / 12) - abs($tb[5] - $tb[3]) / 2, $red, APPLICATION_PATH . '/../' . PUBLIC_DEF . 'fonts/ariblk.ttf', 'HAL');

                    $texte = array();
                    $texte[] = $this->getId();
                    $firstAuthor = $this->getAuthor(0);
                    if ($firstAuthor instanceof Hal_Document_Author && !in_array($firstAuthor->getFullname(), array('', '.', '. .'))) {
                        $texte[] = $firstAuthor->getFullname();
                    }
                    $texte[] = $this->getUri();

                    $tb = imagettfbbox(intval($width / 140), 0, APPLICATION_PATH . '/../' . PUBLIC_DEF . 'fonts/arialnb.ttf', implode("\n", $texte));
                    imagettftext($image, intval($width / 140), 0, $width - intval($width / 4) + intval($width / 140), $height - intval($height / 24) - abs($tb[5] - $tb[3]) / 2, $black, APPLICATION_PATH . '/../' . PUBLIC_DEF . 'fonts/arialnb.ttf', implode("\n", $texte));
                    if ($this->getLicence() != '' &&
                        in_array($this->getLicence(), Hal_Settings::getKnownLicences()) &&
                        array_key_exists('icon', Hal_Settings::getLicenceInfos($this->getLicence())) &&
                        is_array(Hal_Settings::getLicenceInfos($this->getLicence())['icon'])) {
                        $licence = Hal_Settings::getLicenceInfos($this->getLicence());
                        $i = 0;
                        foreach ($licence['icon'] as $icon) {
                            $src = imagecreatefrompng(APPLICATION_PATH . '/../public/img/licences/' . $icon . '.png');
                            imagecopy($image, $src, $width - intval($width / 4) + 4 + 13 * $i++, 4, 0, 0, 13, 13);
                            imagedestroy($src);
                        }
                    }
                    imagejpeg($image, $this->getRacineCache() . '/' . $this->_docid . '.jpeg');
                    imagedestroy($image);
                }
                // métas iptc+xmp
                $country = Zend_Locale::getTranslation(strtoupper($this->getMeta('country')), 'country', 'en');
                $copyright = Zend_Registry::get('Zend_Translate')->translate(Hal_Referentiels_Metadata::getLabel('licence', $this->getLicence()), 'en');
                $authors = array();
                foreach ($this->getAuthors() as $o) {
                    if ($o instanceof Hal_Document_Author && $o->getFullname()) {
                        $authors[] = $o->getFullname();
                    }
                }
                $metasIPTC = array('005' => $this->getMainTitle(), '105' => $this->getMainTitle(), '022' => $this->getId(), '025' => iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($this->getKeywords())), 0), '055' => $this->getProducedDate(), '080' => $authors, '090' => $this->getMeta('city'), '100' => strtoupper($this->getMeta('country')), '101' => $country, '110' => $this->getMeta('credit'), '115' => $this->getMeta('source'), '116' => $copyright, '120' => iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($this->getAbstract())), 0));
                $metasXMP = array('title' => $this->getTitle(), 'id' => $this->getId(), 'keyword' => $this->getKeywords(), 'date' => $this->getProducedDate(), 'creator' => $authors, 'city' => $this->getMeta('city'), 'country' => $country, 'right' => $this->getMeta('credit'), 'source' => $this->getMeta('source'), 'copyright' => $copyright, 'description' => $this->getAbstract());
                $withmeta = $this->getRacineCache() . $this->_docid . '.jpeg';
                if (is_file($withmeta)) {
                    $img = imagecreatefromjpeg($withmeta);
                } else {
                    $img = imagecreatefromjpeg($jpeg->getPath());
                }
                imagejpeg($img, $withmeta, 75);
                imagedestroy($img);
                $xmp = Ccsd_Externdoc_Image_Xmp::add($withmeta, $metasXMP);
                $iptc = Ccsd_Externdoc_Image_Iptc::add($withmeta, $metasIPTC);
                if (is_file($withmeta)) {
                    return ($xmp && $iptc);
                } else {
                    return copy($jpeg->getPath(), $withmeta);
                }
            }
        }
        return false;
    }

    /**
     * @param string $format
     * @param string $citation
     */
    public function setCitation($format = 'ref', $citation = null)
    {
        $this->_citation[$format] = $citation;
    }


    /**
     * Récupération de la citation (format=full) ou de la ref bib (format=ref)
     *
     * @param string
     * @param bool
     * @return string
     */
    public function getCitation($format = 'ref', $reload = false)
    {
        if ($reload || $this->_citation[$format] === null) {
            $metaList = Hal_Referentiels_Metadata::metaList();
            $citation = Hal_Settings::getCitationStructure($this->_typdoc);
            $docLang = strtolower($this->getMeta(Ccsd_Externdoc::META_LANG));
            if (!in_array($docLang, Hal_Settings::getLanguages())) {
                $docLang = 'en';
            }
            while (preg_match('/{([^}]+)}/', $citation, $meta) && isset($meta[1])) {
                $value = $this->getMeta($meta[1]);
                if ($meta[1] == 'date') {
                    if ($this->getMeta('inPress')) {
                        $value = Zend_Registry::get('Zend_Translate')->translate('inPress', $docLang); // Traduction A paraitre
                    } else {
                        $value = substr($this->createProducedDate(), 0, 4);
                    }
                }
                if ($meta[1] == 'datePublication') {
                    $value = substr($this->getMeta('date'), 0, 4);
                }
                if ((is_string($value) && $value == '') || (is_array($value) && count($value) == 0)) {
                    $citation = str_replace('{' . $meta[1] . '}', '', $citation);
                    $citation = preg_replace('#<([a-z]+)></\1>#i', '', $citation);
                    continue;
                }
                if ($meta[1] == 'domain' && is_array($value)) {
                    $value = $value[0];
                }

                if (($meta[1] == 'hceres_etabassoc_local') || ($meta[1] == 'hceres_etabsupport_local')) {
                    if (! is_array($value)) {
                        $value = [$value];
                    }
                    $tmp = [];
                    foreach ($value as $v) {
                        if ($v instanceof Ccsd_Referentiels_Hceres) {
                            $tmp[] = $v->NOM;
                        }
                    }
                    $value = ', ' . implode(', ', $tmp);
                }

                if (is_array($value)) {
                    $value = implode('; ', $value);
                }
                if ($meta[1] == 'page') {
                    $value = str_replace('–', '-', $value);
                    if (preg_match('/^(\?+|-+|\.+|to appear|[a-z]+)$/', $value)) {
                        $value = '';
                    }
                    if ($value != '' && !preg_match('/(^pp)|p|(page$)/', $value)) {
                        $value = 'pp.' . $value;
                    }
                    $value = str_replace('pages', 'p.', $value);
                }
                if ($meta[1] == 'volume' && $value) {
                    if ($this->getMeta('issue')) {
                        $value = $value . ' (' . $this->getMeta('issue') . ')';
                    }
                }
                if ($meta[1] == 'publisher') {
                    if (preg_match('/^(\?+|-+|\.+|none|undef)$/', $value)) {
                        // Champs mal remplis... on annule la valeur
                        $value = '';
                    } else {
                        // Valeur de publisher Ok
                        // On regarde si publisherLink existe pour mettre un lien
                        $linkvalue = $this->getMeta('publisherLink');
                        if ($linkvalue != '') {
                            $value = '<a target="_blank" href="' . ((!preg_match('@^https?://@', $linkvalue)) ? 'http://' . $linkvalue : $linkvalue) . '">' . $value . '</a>';
                        }
                    }
                }
                if ($meta[1] == 'doi') {
                    $value = '<a target="_blank" href="'. self::URL_DOI . $value . '">&#x27E8;' . $value . '&#x27E9;</a>';
                }
                if ($meta[1] == 'nnt') {
                    $value = '<a target="_blank" href="'. self::URL_THESES . $value . '">&#x27E8;NNT : ' . $value . '&#x27E9;</a>';
                }
                if ($meta[1] == 'swh') {
                    $value = '<a target="_blank" href="https://archive.softwareheritage.org/browse/' . $value . '">&#x27E8;' . $value . '&#x27E9;</a>';
                }
                if ($meta[1] == 'publisherLink') {
                    // TODO : A supprimer dans les definitions de citation.  Mettre un lien en clair dans la citation ne semble pas pertinent.
                    $value = '';
                }
                if ($meta[1] == 'journal' && $value instanceof Ccsd_Referentiels_Journal) {
                    $citation = str_replace('{journalPublisher}', $value->PUBLISHER, $citation);
                    $value = $value->JNAME;
                }
                if ($meta[1] == 'number' && $this->_typdoc == 'PATENT') {
                    $value = Zend_Registry::get('Zend_Translate')->translate('N° de brevet', $docLang) . ': ' . $value;
                }
                if ($meta[1] == 'conferenceStartDate') {
                    if (strlen($value) > 4) {
                        $value = date("M Y", mktime(0, 0, 0, substr($value, 5, 2), 1, substr($value, 0, 4)));
                    }
                }
		
                if (in_array($meta[1], $metaList)) {
                    if ($meta[1] == 'country') {
                        $value = ucfirst(Zend_Locale::getTranslation(strtoupper($value), 'country', $docLang));
                    } else if ($meta[1] == Ccsd_Externdoc::META_LANG) {
                        $value = ucfirst(Zend_Locale::getTranslation($value, Ccsd_Externdoc::META_LANG, $docLang));
                    } else {
                        $value = Zend_Registry::get('Zend_Translate')->translate(Hal_Referentiels_Metadata::getLabel($meta[1], $value), $docLang);
                    }
                }

                $citation = str_replace('{' . $meta[1] . '}', $value, $citation);
            }
            do {
                $citation = preg_replace('/\s+([,.])\s+/', ' ', $citation, -1, $count);
            } while ($count > 0);
            $refCitation = trim(str_replace('..', '.', str_replace(array('()', '[]'), '', preg_replace('/\s+/', ' ', $citation))), " -,.\t\n\r\0\x0B");
            $this->setCitation('ref', $refCitation);

            // Compute FullCitation

            if ($this->getInstance() != 'hceres' && count($this->getAuthors()) > 0) {
                $authorArr = array();
                $i = 0;
                foreach ($this->getAuthors() as $author) {
                    if ($i >= 5) break;
                    $authorArr[] = $author->getFullname();
                    $i++;
                }
                if (count($this->getAuthors()) > 5) {
                    $authorArr[] = 'et al.';
                }
                if (is_array($authorArr)) {
                    $author = implode(', ', $authorArr);
                }
            } else {
                $author = '';
            }
            if ($this->getInstance() == 'hceres') {
                $author = $author . ($author == '' ? '' : '. ') . Zend_Registry::get('Zend_Translate')->translate('typdoc_' . $this->getTypdoc()) ;
            }
            $fullCitation = $author . ($author == '' ? '' : '. ') . $this->getMainTitle(true) . '. ' . $refCitation;

            if ($this->getId() != '') {
                $fullCitation = $fullCitation . '. <a target="_blank" href="' . $this->getUri(true) . '">&#x27E8;' . $this->getId(true) . '&#x27E9;</a>';
            }

            $this->setCitation('full', $fullCitation);
        }
        return $this->_citation[$format];
    }

    /**
     * Récupération des conditions de réutilisation du document pour SPM
     * pour affichage dans le bloc 'Pour citer ce document'
     *
     * @return array
     */
    public function getReuse()
    {
        $aData = array();

        // ajout de l'auteur si ce n'est pas un auteur "collectif"
        $aAuthors = $this->getAuthors();
        if (count($aAuthors)) {
            if (preg_match('/^collectif/i', $aAuthors[0]->getFirstname())) {
                $aData['auteur'] = '';
            } else {
                $aData['auteur'] = Ccsd_Tools::formatAuthor($aAuthors[0]->getLastname(),
                        $aAuthors[0]->getFirstname()) . ' - ';
            }
        }

        // date de publication
        setlocale(LC_TIME, "fr_FR.utf8");
        $d = strtotime($this->getReleasedDate());
        $aData['datePubli'] = strftime ("%e %B %Y", $d);

        // liste des entites
        $aStructs = array();
        foreach ($this->getStructures() as $structure) {
            $aValues[] = $structure->getStructname();
            $aStructs = array_merge($aStructs, $structure->getAllParents());
        }

        foreach ($aStructs as $structure) {
           $aValues[] =  $structure['struct']->getStructname();
        }

        if (isset($aValues)) {
            $aValues = array_unique($aValues);
            // on enlève la structure de niveau le plus élevé
            array_pop($aValues);
            // on commence par la structure de plus haut niveau
            $aValues = array_reverse($aValues);

            $aData['arboEntites'] = implode(' / ', $aValues);
        }

        return $aData;
    }

    /**
     * Récupération du titre du document
     *
     * @param string $lang
     * @param bool $subTitle ajout sous titre
     * @return string|array
     */
    public function getTitle($lang = null, $subTitle = false)
    {
        $title  = $this->_metas->getMeta(Ccsd_Externdoc::META_TITLE, $lang);
        $stitle = $this->_metas->getMeta('subTitle', $lang);

        // Modif : on vérifie si le subtitle est vide
        if ($subTitle && !empty($title) && !empty($stitle)) {
            if ($lang && 'fr' == $lang) {
                // Titre français = Titre : SousTitre
                return $title . ' : ' . $stitle;
            } else {
                // Titre autre langue = Titre: SousTitre
                if ($lang) {
                    return $title . ': ' . $stitle;
                    // Titre dans toutes les langues : array
                } else {
                    // Retourne l'ensemble de tous les titres construits avec les sous titres
                    $titles = array();
                    foreach ($title as $language => $t) {
                        $titles[$language] = $this->getTitle($language, $subTitle);
                    }

                    return $titles;
                }
            }
        } else {
            return $title;
        }
    }

    /**
     * Récupération du titre principal du document
     *
     * @param bool    $subTitle : Retrieve subTitle and add it to title
     * @param string  $lang     : The title language is returned in this param or '' if unknown
     *                  @todo: maybe we must return 'en' in place of ''
     * @return string
     */

    public function getMainTitle($subTitle = false, &$lang = null)
    {
        $lang = $this->getHalMeta()->getMeta(Ccsd_Externdoc::META_LANG);
        if ($lang == '') {
            $lang = 'en';
        }
        $title = $this->getTitle($lang, $subTitle);

        if ($title == '') {
            $lang = 'en';
            $title = $this->getTitle('en', $subTitle);
        }
        if ($title == '') {
            /* On prend le premier titre  */
            foreach ($this->getTitle(null, $subTitle) as $k => $t) {
                if (is_array($t)) {
                    $title = $t[0];
                    $lang = $k;
                } else {
                    $title = $t;
                    $lang =''; // Unknown...
                }
                break;
            }
        }
        if ($title == '') {
            $lang ='en';
            $title = 'No title' ;
        }
        return $title;
    }

    /**
     * Récupération du sous-titre du document
     *
     * @param string $lang
     * @return string|array
     */
    public function getSubTitle($lang = null)
    {
        return $this->_metas->getMeta("subTitle", $lang);
    }

    /**
     * Récupération du résumé du document
     *
     * @param string $lang
     * @return string|array
     */
    public function getAbstract($lang = null)
    {
        return $this->_metas->getMeta("abstract", $lang);
    }

    /**
     * @return array|string
     */
    public function getDomains()
    {
        return $this->_metas->getMeta("domain");
    }

    /**
     * Retourne la liste de tous les domaines d'un document
     * Si le document a comme domaine shs.eco, la fonction retournera un tableau contenant shs et shs.eco
     *
     */
    public function getAllDomains()
    {
        $domains = $this->getDomains();
        $result = [];
        if (!$domains) {
            return $result;
        } else if (!is_array($domains)) {
            $domains = [$domains];
        }
        foreach ($domains as $domain) {
            $result = array_merge($result, Ccsd_Tools_String::getHalDomainPaths($domain));
        }
        return array_unique($result);
    }

    /**
     * @return mixed|string
     */
    public function getMainDomain()
    {
        $domain = $this->getDomains();
        // TO DO : Comment gérer le current ?
        return (isset($domain) && is_array($domain)) ? current($domain) : '';
    }

    /**
     * Retourne true si un fulltext est en attente de validation pour le document a l'origine de type Notice
     * @param string $identifiant à vérifier
     * @return boolean true si fulltext en attente de validation false sinon
     * @todo: renommer cette fonction: valide seulement pour un (docid) identifiant pointant pour une notice
     */
    static public function VerifyFulltextWaiting($identifiant)
    {
        /* Une notice ne peut avoir qu'une seule ligne dans document
           Sauf si on a ajoute un document et que le dit document est en moderation
           Apres lamoderation, il n'y aura plus que un document de format "file"

            Pour le cas qui nous interesse, il suffit de voir si l'identifiant a plusieurs ligne/docid dans DOCUMENT
        */
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE)->where('IDENTIFIANT = ?', $identifiant);

        return  (count($db->fetchAll($sql)) > 1);
    }


    /**
     * Retourne la liste des identifiants d'articles associés à un identifiant exterieur (ex DOI-
     * @param $server String nom du serveur ext (doi, arxiv, ...)
     * @param $id
     * @param $excludeCurrentId // indique s'il faut exclure l'identifiant du document courant
     * @return array
     */
    public function getDocsByIdExt($server, $id, $excludeCurrentId  = true)
    {
        $res = [];
        foreach (Hal_Document_Doublonresearcher::getDoublonsOnIds([$server => $id], []) as $row) {
            if (isset($row['IDENTIFIANT']) && (!$excludeCurrentId || $row['IDENTIFIANT'] != $this->getId())) {
                $res[] = $row['IDENTIFIANT'];
            }
        }
        return $res;
    }

    /**
     * Retourne l'(les) identfiant(s) du dépôt dans une autre base
     * @param null $server
     * @return mixed
     */
    public function getIdsCopy($server = null)
    {
        return $this->_metas->getMeta("identifier", $server);
    }

    /**
     * @return array
     */
    public function getIdsCopyUrl()
    {
        $res = array();
        foreach ($this->getIdsCopy() as $server => $value) {
            if ($value == '') {
                continue;
            }
            if (array_key_exists($server, self::$_serverCopyUrl)) {
                $res[$server] = array('id' => $value, 'link' => self::$_serverCopyUrl[$server] . $value);
            }
        }
        return $res;
    }

    /**
     * @param string $lang
     * @return array|string
     */
    public function getKeywords($lang = null)
    {
        return $this->_metas->getMeta(Ccsd_Externdoc::META_KEYWORD, $lang);
    }

    /**
     * Retourne la clé BibTeX de la ressource
     * @return string
     */
    public function getKeyBibtex()
    {
        if ($this->getAuthor(0) instanceof Hal_Document_Author) {
            return preg_replace("/[^a-zA-Z0-9]/", "", strtolower(Ccsd_Tools::stripAccents($this->getAuthor(0)->getLastname()))) . ':' . $this->_identifiant;
        } else {
            return 'NONE' . ':' . $this->_identifiant;
        }
    }

    /**
     * Retourne les auteurs du document
     *
     * @param int $max nbre max d'auteur, 0 pas de limite
     * @return Hal_Document_Author[]
     */
    public function getAuthors($max = 0)
    {
        if ($max == 0) {
            return $this->_authors;
        } else {
            return array_slice($this->_authors, 0, $max);
        }
    }

    /**
     * Enregistre les auteurs
     *
     * @param Hal_Document_Author[]
     */
    public function setAuthors($authors)
    {
        $this->_authors = $authors;
    }

    /**
     * retourne un auteur
     *
     * @param int $id
     * @return Hal_Document_Author
     */
    public function getAuthor($id)
    {
        if (array_key_exists($id, $this->_authors)) {
            $author = $this->_authors[$id];
            if ($author instanceof Hal_Document_Author) {
                return $author;
            }
        }
        // Hum pas d'auteur... c'est plutot mal
        return null;
    }

    /**
     * @return int
     */
    public function getAuthorsNb()
    {
        return count($this->_authors);
    }

    /**
     * Retourne la liste des auteurs
     * @param int $max // nbre max d'auteurs, 0 pas de limite
     * @return string
     */
    public function getListAuthors($max = 0)
    {
        $auteur = implode(', ', $this->getAuthors($max));
        return ($this->getAuthorsNb() > $max) ? $auteur . ', et al.' : $auteur;
    }

    /**
     * Retourne la liste des structures
     * @return string
     */
    public function getListStructures()
    {
        return implode(', ', $this->getStructures());
    }

    /**
     * @return string
     */
    public function getListDomains()
    {
        $res = array();
        foreach ($this->getDomains() as $domain) {
            $res[] = Ccsd_Tools_String::getHalDomainTranslated($domain);
        }
        return implode(', ', $res);
    }

    /**
     * Modifie l'ordre des auteurs
     *
     * @param array $order
     */
    public function changeAuthorsOrder($order)
    {
        $tmp = array();
        $i =0;
        foreach ($order as $old) {
            if (isset($this->_authors[$old])){
                $tmp[$i] = $this->_authors[$old];
                $i++;
            }
        }
        $this->_authors = $tmp;
    }

    /**
     * Ajout d'un nouvel auteur
     * les indices des elements ne sont pas touches lors de l'ajout d'un element
     *
     * @param mixed (array | Hal_Document_Author) $data
     * @param boolean $loadFromBase indique si l'ajout de l'auteur se fait au moment du chargement de l'article de la base
     * @return int L'indice de l'element ajoute
     */
    public function addAuthor($data, $loadFromBase = false)
    {
        if ($data instanceof Hal_Document_Author) {
            $author = $data;
        } else {
            $author = new Hal_Document_Author();
            $author->set($data);
            if ($loadFromBase) {
                $author->loadIdsAuthor($this->_docid);
            }
        }

        if (!$loadFromBase) {
            // On vérifie que l'auteur n'est pas déjà associé au dépôt - On considère que 2 auteurs n'ont pas le même nom-prénom
            foreach ($this->getAuthors() as $idx => $docAuthor) {
                if ($docAuthor->isConsideredSameAuthor($author)) {

                    $docAuthor->mergeAuthor($author);

                    // On récupère l'affiliation s'il n'y en a pas
                    if (!$docAuthor->isAffiliated() && $author->isAffiliated()) {
                        foreach ($author->getStructidx() as $structidx) {
                            $docAuthor->addStructidx($structidx);
                        }
                    }

                    $this->_authors[] = $docAuthor;
                    unset($this->_authors[$idx]);
                    return max(array_keys($this->_authors));
                }
            }
        }
        $this->_authors[] = $author;
        return max(array_keys($this->_authors));
    }

    /**
     * @param Hal_Document_Author $docAuthor
     * @param int | array $structid
     */
    public function addAuthorWithAffiliations($docAuthor, $structid = 0)
    {
        // ToDo : faire un truc moins sale ! => trouver pourquoi on  se retrouve avec structid = [0=>""]
        if (0 == $structid || (is_array($structid) && empty($structid)) || (count($structid) == 1 && empty($structid[0])))
            $structid = $docAuthor->getLastStructures();
        else if (!is_array($structid))
            $structid = [$structid];

        foreach ($structid as $id) {
            $idx = $this->addStructure($id);
            $docAuthor->addStructidx($idx);

        }
        $this->addAuthor($docAuthor);
    }

    /**
     * Suppression des auteurs et structures du dépôt
     */
    public function delAutStruct()
    {
        $this->_authors = array();
        $this->_structures = array();
    }

    /**
     * Suppression d'un auteur
     *
     * @param int $idDel
     */
    public function delAuthor($idDel)
    {
        $newAuthors = array();
        foreach ($this->_authors as $id => $author) {
            if ($id != $idDel) {
                $newAuthors[$id] = $author;
            }
        }
        $this->_authors = $newAuthors;
    }

    /**
     * Retourne les structures de recherche d'un document
     *
     * @return Hal_Document_Structure[]
     */
    public function getStructures()
    {
        return $this->_structures;
    }

    /**
     * On supprime les laboratoires non associés à des auteurs
     */
    public function cleanStructures()
    {
        $this->delStructures($this->getStructuresNotUsed());
    }

    /**
     * Retourne les structures de recherche d'un des auteurs d'un document
     *
     * @param int $authorid id d'un des auteurs du document
     *
     * @return array structures
     */
    public function getStructuresAuthor($authorid)
    {
        $resultat = [];
        $structures = $this->getStructures();
        foreach ($structures as $idx => $docStructure) {
            foreach ($this->getAuthors() as $author) {
                if (($author->getAuthorid() == $authorid) && (in_array($idx, $author->getStructidx()))) {
                    $resultat[] = $docStructure;
                }
            }
        }

        return $resultat;
    }

    /**
     * Retourne les codes CNRS d'un document
     *
     * @return array
     */
    public function getCodeCNRSStructures()
    {
        $cnrs = [];
        foreach ($this->_structures as $structure) {
            foreach ($structure->getAllParents() as $parent) {
                if ($parent['struct']->getSigle() == 'CNRS' && $parent['code'] != '') {
                    $cnrs[] = str_replace(' ', '', $parent['code']);
                }
            }
        }

        return array_unique($cnrs);
    }

    /**
     * retourne une structure de recherche
     *
     * @param int $id
     * @return Hal_Document_Structure
     */
    public function getStructure($id)
    {
        return $this->_structures[$id];
    }

    /**
     * Ajout d'une nouvelle structure au document
     *
     * @param
     *            mixed (int, array, Hal_Document_Structure) $data
     * @return int
     */
    public function addStructure($structure)
    {
        if (!$structure instanceof Hal_Document_Structure) {
            if (is_array($structure)) {
                $structure = new Hal_Document_Structure(0, $structure);
            } else {
                $structure = new Hal_Document_Structure((int)$structure);
                if ($structure->getStructid() == 0) {
                    return false;
                }
            }
        }

        $structid = $structure->getStructid();
        // On vérifie que la structure n'est pas déjà associée au dépôt
        foreach ($this->getStructures() as $idx => $docStruct) {
            if ($docStruct->getStructid() != 0 && $structid == $docStruct->getStructid()) {
                return $idx;
            }
        }

        //on vérifie que la structure soit valide
        if (! $structure->isWellFormed()) {
            return false;
        }

        $this->_structures[] = $structure;
        return max(array_keys($this->_structures));
    }

    /**
     * @param int $id
     * @param Hal_Document_Structure $structure
     * @return bool
     */
    public function setStructure($id, $structure)
    {
        if (isset($this->_structures[$id])) {
            $this->_structures[$id] = $structure;
            return true;
        }
        return false;
    }

    /**
     * Suppression d'une structure de recherche
     *
     * @param int $idDel
     */
    public function delStructure($idDel)
    {
        $this->delStructures([$idDel]);
    }

    /**
     * Suppression d'affiliations
     *
     * @param array $idsToDel
     */
    public function delStructures($idsToDel)
    {
        $structures = array();
        $corresp = array();
        $i = 0;
        foreach ($this->_structures as $id => $structure) {
            if (!in_array($id,$idsToDel)) {
                $structures[$i] = $structure;
                $corresp[$id] = $i;
                $i++;
            }
        }
        $this->_structures = $structures;
        foreach ($this->_authors as $author) {
            $author->updateStructidx($corresp);
        }
    }

    /**
     * Retourne un tableau des index des structures non associées aux auteurs
     *
     * @return array
     */
    public function getStructuresNotUsed()
    {
        $structidx = array_keys($this->getStructures());
        foreach ($structidx as $idx) {
            foreach ($this->getAuthors() as $author) {
                if (in_array($idx, $author->getStructidx())) {
                    unset($structidx[$idx]);
                }
            }
        }
        return $structidx;
    }

    /**
     * Retourne l'URL du portail de dépôt
     * @return string
     */
    public function getPortailUrl()
    {
        $portail = Hal_Site::loadSiteFromId($this->getSid());
        return $portail->getUrl();
    }

    /**
     * URI d'un dépôt
     *
     * @param bool URI avec le numero de version (si V>1)
     * @return string
     */
    public function getUri($version = false)
    {
        if ($this->_docid == 0) {
            return '';
        }

        $portail = Hal_Site::loadSiteFromId($this->getSid());
        return $portail->getUrl() . '/' . $this->_identifiant . (($version && count($this->getDocVersions()) > 1) ? 'v' . $this->_version : '');
    }

    /**
     * Repertoire physique des fichiers d'un docid
     */
    public function getRacineDoc()
    {
        return self::getRacineDoc_s($this->_docid);
    }

    /**
     * Static method :
     * Repertoire physique des fichiers d'un docid
     */
    public static function getRacineDoc_s($docid)
    {
        if ($docid == 0) {
            return '';
        }
        return PATHDOCS . wordwrap(sprintf("%08d", $docid), 2, DIRECTORY_SEPARATOR, 1) . DIRECTORY_SEPARATOR;
    }

    /**
     * Retourne le chemin vers le répertoire de cache d'un dépôt
     * @return string
     */
    public function getRacineCache()
    {
        return self::getRacineCache_s($this->_docid);
    }

    /**
     * Static method :
     * Retourne le chemin vers le répertoire de cache d'un dépôt
     * @return string
     */
    public static function getRacineCache_s($docid)
    {
        if ($docid == 0) {
            return '';
        }
        return DOCS_CACHE_PATH . wordwrap(sprintf("%08d", $docid), 2, DIRECTORY_SEPARATOR, 1) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param $inputType
     */
    public function setInputType($inputType)
    {
        $this->_inputType = $inputType;
    }

    /**
     * @return string
     */
    public function getInputType()
    {
        return $this->_inputType;
    }

    /**
     * Le texte complet est disponible sur HAL ou Arxiv ou Pubmedcentral, etc
     * todo à supprimer si ne sert plus
     * @return int
     */
    public function isTextAvailable()
    {
        if (($this->_format == self::FORMAT_FILE) || ($this->getIdsCopy('arxiv') != '') || ($this->getIdsCopy('pubmedcentral') != '')) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Indique si le texte intégral est accessible pour un document
     * @return bool
     */
    public function isOpenAccess()
    {
        if ($this->getFormat() == static::FORMAT_FILE) {
            //Regarde embargo
            $mainFile = $this->getDefaultFile();
            if ($mainFile instanceof Hal_Document_File) {
                return $mainFile->canRead();
            }
        }
        /** @var Hal_LinkExt $linkedExt */
        $linkedExt = $this->getMetaObj('LINKEXT');
        if ($linkedExt != null) {
            return in_array($linkedExt->getIdSite(), ['arxiv', 'pubmedcentral', 'openaccess']);
        } else {
            return false;
        }
    }

    /**
     * TODO Ne pas utiliser file_exists: cache exist doit rendre le contenu du fichier directment!
     *      et non pas si exist alors lire car le parallelisme rends le truc caduque.
     * Return document in the selected format
     * @param string $format
     * @param bool $content
     * @return bool|mixed|string
     */
    public function get($format = null, $content = true)
    {
        if ($this->_docid == 0) {
            return false;
        }
        if ($format != null) {
            if (($this->cacheExist($format) == false) && (!$this->createCache($format))) {
                return false;
            }
            $file = $this->getRacineCache() . $this->_docid . '.' . $format;
            if (is_file($file)) {
                if ($content) {
                    $content = file_get_contents($file);
                    // Voir Benoit
                    if ($format == 'tei' && !simplexml_load_string($content)) { // Vérifie que le XML est valide
                        return false;
                    }
                    return ($format == 'phps') ? unserialize($content) : $content;
                } else {
                    return $file;
                }
            }
        }
        return false;
    }

    /**
     * @param string $format
     * @return bool
     */
    public function createCache($format = null)
    {
        // les fichiers de caches sont conservés dans le repertoire "cache" sous doc/id/
        if ($this->_docid == 0) {
            return false;
        }
        if ($format == null) {
            return false;
        }
        if (! is_dir($this->getRacineCache()) && (! @mkdir($this->getRacineCache(), 0777, true))) {
            return false;
        }

        $basename = $this->getRacineCache() . '/' . $this->_docid;
        switch ($format) {
            case 'phps':
                return file_put_contents($basename . '.phps', serialize($this));
            case 'tei':
                return file_put_contents($basename . '.tei', $this->createTEI());
            case 'bib' :
                return file_put_contents($basename . '.bib', Ccsd_Tools::xslt($this->get('tei'), __DIR__.'/Document/xsl/bibtex.xsl'));
            case 'dc' :
                return file_put_contents($basename . '.dc', Ccsd_Tools::xslt($this->get('tei'), __DIR__.'/Document/xsl/dc.xsl', ['currentDate' => date('Y-m-d')]));
            case 'enw' :
                return file_put_contents($basename . '.enw', Ccsd_Tools::xslt($this->get('tei'), __DIR__.'/Document/xsl/endnote.xsl'));
            case 'json' :
                return file_put_contents($basename . '.json', $this->createJSON());
            case 'pdf' :
                return $this->makeCoverPage();
            case 'jpeg' :
                return $this->makeWatermark();
            case 'dcterms' :
                return file_put_contents($basename . '.dcterms', Ccsd_Tools::xslt($this->get('tei'), __DIR__.'/Document/xsl/dcterms.xsl'));
            default:
                Ccsd_Tools::panicMsg(__FILE__, __LINE__ , "Unknown format:  $format");
        }
        return false;
    }

    /**
     * @param string $format
     * @return bool
     */
    public function cacheExist($format = null)
    {
        if ($this->_docid == 0 || ($format == null)) {
            return false;
        }
        switch ($format) {
            case 'phps':
                $filename = $this->getRacineCache() . '/' . $this->_docid . '.' . $format;
                return is_file($filename) && (filesize($filename)>0) && (time() - filemtime($filename) < self::PHPS_CACHE_MAX_TIME);
            default:
                $filename = $this->getRacineCache() . '/' . $this->_docid . '.' . $format;
                return is_file($filename) && (filesize($filename)>0) && (time() - filemtime($filename) < self::CACHE_MAX_TIME);
        }
    }

    /**
     * @param string $format
     * @return bool
     */
    public function deleteCache($format = null)
    {
        return self::deleteCaches(array($this->_docid), $format);
    }

    /**
     * @param int|int[]    $docids
     * @param string   $format
     * @return bool
     */
    static public function deleteCaches($docids, $format = null)
    {
        if (!is_array($docids)) {
            $docids = array((int)$docids);
        }
        if (count($docids) > 1000) {
            // Hum: si le file system est charge, on peut depasse le Maximum execution time de Php
            ini_set('max_execution_time', 0);
        }
        if ($format != null && !is_array($format)) {
            $format = array($format);
        }
        try {
            $res = true;
            foreach ($docids as $docid) {
                if ($format != null) {
                    $baseCache = DOCS_CACHE_PATH . wordwrap(sprintf("%08d", $docid), 2, DIRECTORY_SEPARATOR, 1) . '/';
                    foreach ($format as $f) {
                        if (is_file($baseCache . $docid . '.' . $f)) {
                            $res = $res && @unlink($baseCache . $docid . '.' . $f);
                        }
                    }
                } else {
                    $res = Ccsd_Tools::rrmdir(DOCS_CACHE_PATH . wordwrap(sprintf("%08d", $docid), 2, DIRECTORY_SEPARATOR, 1) . '/');
                }
            }
            return $res;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Création du JSON de l'article
     * Sortie standard de Solr en json
     * @return string json
     */
    public function createJSON()
    {
        $res = Ccsd_Tools::solrCurl('q=docid:' . $this->_docid . '&rows=1&fl=*&omitHeader=true&wt=json');
        return $res;
    }

    /**
     * Création de la TEI de l'article
     * @return string xml
     */
    public function createTEI()
    {
        if (!$this->isLoaded()) {
            $this->load();
        }
        $tei = new Hal_Document_Tei($this);
        return $tei->create();
    }

    /**
     * Chargement du document via la TEI sous forme de DOM object
     * @param DOMDocument
     * @param string
     * @param string
     * @return Hal_Document
     */
    public function loadFromTEI(DOMDocument $xml, $pathImport = null, $instance = 'hal', $teiLoadOptions=[])
    {
        $tei = new Hal_Document_Tei($this);
        $tei->setOptions($teiLoadOptions);
        $tei->load($xml, $pathImport, $instance);

        return $this;
    }
    /**
     * Chargement du document via la TEI sous forme de fichier
     * @param string $filename
     * @param string
     * @param string
     * @return Hal_Document
     */
    public static function loadFromTEIFile($filename, $pathImport = null, $instance = 'hal')
    {
        $contentFile = file_get_contents($filename);
        $content = new DOMDocument();
        $content->loadXML($contentFile);
        $content->schemaValidate(__DIR__ . '/Sword/xsd/inner-aofr.xsd');

        $document = new Hal_Document();
        $document->loadFromTEI($content, $pathImport, $instance);

        return $document;
    }

    /**
     * Retourne l'identifiant OAI du document
     * @param $oaiVersion string
     */
    public function getOaiIdentifier($oaiVersion)
    {
        switch ($oaiVersion) {
            case "v1": $oaiRepoIdent = "HAL"; break;
            case "v2": $oaiRepoIdent = "hal.archives-ouvertes.fr"; break;
            default :
                Ccsd_Tools::panicMsg(__FILE__,__LINE__, "getOaiIdentifier called with bad oaiversion = $oaiVersion, must be v1 or v2");
                $oaiRepoIdent = "HAL";
        }
        return  'oai:'. $oaiRepoIdent. ':' . $this->_identifiant . 'v' . $this->_version;
    }

    /**
     * OAI header de l'article
     * @var $oaiVersion string
     * @return string xml
     */
    public function getOaiHeader($oaiVersion)
    {
        if (!$this->isLoaded()) {
            $this->load();
        }
        $xml = new Ccsd_DOMDocument('1.0', 'utf-8');
        $root = $xml->createElement('header');
        $root->appendChild($xml->createElement('identifier', $this->getOaiIdentifier($oaiVersion)));
        $root->appendChild($xml->createElement('datestamp', substr($this->_modifiedDate, 0, 10)));

        foreach ($this->getOaiSet() as $set) {
            $root->appendChild($xml->createElement('setSpec', $set));
        }

        $xml->appendChild($root);
        $xml->formatOutput = true;
        $xml->substituteEntities = true;
        $xml->preserveWhiteSpace = false;
        return $xml->saveXML($xml->documentElement);
    }
    /**
     * @return string[]
     */
    public function getOaiSet()
    {
        $sets = array('type:' . $this->_typdoc);
        foreach ($this->getDomains() as $domain) {
            if ($domain != '') {
                $domaines = Ccsd_Tools_String::getHalDomainPaths($domain);
                if (!in_array('subject:' . $domaines[0], $sets)) {
                    $sets[] = 'subject:' . $domaines[0];
                }
            }
        }
        foreach ($this->_collections as $collection) {
            if ($collection instanceof Hal_Site_Collection) {
                $sets[] = $collection->getShortname() == 'OPENAIRE' ? 'openaire' : 'collection:' . $collection->getShortname();
            }
        }
        return $sets;
    }


    /**
     * Compute a "name" <email> that Arxiv accept...
     * Some form for the first part are not accepted by Arxiv
     * @return string
     */
    public function getFromNormalized()
    {
        $fullname = $this->getContributor('fullname');
        $email    = $this->getContributor('email');

        return Ccsd_Tools::getFromNormalized($fullname, $email);
    }

    /**
     * 
     */
    public function onlineVersion() {

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()->from(self::TABLE, 'DOCID')
            ->where('IDENTIFIANT = ?', $this->getId())
            ->where('VERSION = ?', (int)$this->getVersion())
            ->where('DOCSTATUS = ?', self::STATUS_VISIBLE)
            ->where('DOCID != ?', (int)$this->getDocid());
        $result = $db->fetchCol($sql);

        if (count($result) >= 1) {
            return $result[0];
        }

        return 0;
    }

    /**
     * Enregistrement de l'article
     * @param int $uid déposant
     * @param bool $sendMail envoi de mail
     * @return int
     */
    public function save($uid = 0, $sendMail = true)
    {

        if (is_array($this->getMeta()) && count($this->getMeta()) == 0) {
            return false;
        }

        $mailContrib = $mailModerator = null;
        $index = false;

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $this->initFormat();

        if ($this->getTypeSubmit() == Hal_Settings::SUBMIT_INIT) {
            // Nouveau dépôt
            $this->_version = 1;
            $this->_pwd = $this->generatePw();
            $this->_submittedDate = date('Y-m-d H:i:s');

            // LOG DANS LE CAS Où UID=0 !! ça ne devrait pas arriver !!
            if ($this->getContributor('uid') == 0) {
                Ccsd_Tools::panicMsg(__FILE__, __LINE__, 'ATTENTION UID=0 POUR LE DOCUMENT : '.serialize($this->toArray()));
            }

            $bind = array(
                'TYPDOC' => $this->_typdoc,
                'VERSION' => $this->getVersion(),
                'DATESUBMIT' => $this->getSubmittedDate(),
                'DATEMODIF' => date('Y-m-d H:i:s'),
                'UID' => $this->getContributor('uid'),
                'SID' => $this->getSid(),
                'INPUTTYPE' => $this->_inputType,
                'TEXTAVAILABLE' => $this->isTextAvailable(),
                'PWD' => $this->_pwd,
                'EXPORTREPEC' => (int)!$this->_hideRePEc,
                'EXPORTOAI' => (int)!$this->_hideOAI);

            $bind['FORMAT'] = $this->getFormat();

            //Statut du dépôt
            if ($this->getFormat() == self::FORMAT_NOTICE) { //Notice
                $bind['DOCSTATUS'] = Hal_Settings::validNotice() ? self::STATUS_BUFFER : self::STATUS_VISIBLE;
                //Suppression du cache CV de chaque Auteur ayant un idhal
                foreach ($this->getAuthorwithIdhal($this->_docid) as $k => $idhal) {
                    Hal_Cache::delete('cv.' . $idhal . '.phps', CACHE_CV );
                }
            } else { //Au moins 1 fichier est associé au dépôt
                $bind['DOCSTATUS'] = $this->_isArxiv ? self::STATUS_TRANSARXIV : self::STATUS_BUFFER;
            }

            if ($bind['DOCSTATUS'] == self::STATUS_VISIBLE) {
                $bind['DATEMODER'] = date('Y-m-d H:i:s');
                $index = true;
            }

            $db->insert(self::TABLE, $bind);
            $this->_docid = $db->lastInsertId(self::TABLE);

            // Génération de l'identifiant du dépôt
            $this->_identifiant = $this->generateId($this->_docid, Hal_Settings::getDocumentPrefix($this->getSid(), $this->_typdoc));
            $db->update(self::TABLE, array('IDENTIFIANT' => $this->_identifiant), 'DOCID = ' . $this->_docid);
            Hal_Document_Logger::log($this->getDocid(), $this->getContributor('uid'), Hal_Document_Logger::ACTION_CREATE);

            //Définition du modèle de mail envoyé au déposant et au modérateur
            $mailContrib = ($bind['DOCSTATUS'] == self::STATUS_VISIBLE) ? Hal_Mail::TPL_DOC_SUBMITTED_ONLINE : Hal_Mail::TPL_DOC_SUBMITTED;
            if ($this->getFormat() != self::FORMAT_NOTICE || Hal_Settings::validNotice()) {
                $mailModerator = Hal_Mail::TPL_ALERT_MODERATOR;
            }

            // PROPRIETAIRES
            $this->addProprio($this->getContributor('uid'));
//            // a- Suppression des anciens auteurs du document
//            $db->delete(self::TABLE_OWNER, array('IDENTIFIANT = ?' => $this->getId(), 'UID = ?' => $this->getContributor('uid')));
//            // b- Enregistrement du contributeur comme propriétaire
//            $bind = array(
//                'IDENTIFIANT' => $this->getId(),
//                'UID' => $this->getContributor('uid'),
//            );
//            $db->insert(self::TABLE_OWNER, $bind);
        } else if ($this->getTypeSubmit() == Hal_Settings::SUBMIT_MODIFY) {
            // Modification suite à une demande du modérateur
            $bind = array('DATESUBMIT' => date('Y-m-d H:i:s'));

            if ($this->getFormat() == self::FORMAT_NOTICE) { //Notice
                $bind['DOCSTATUS'] = Hal_Settings::validNotice() ? self::STATUS_BUFFER : self::STATUS_VISIBLE;
            } else { //Au moins 1 fichier est associé au dépôt
                $bind['DOCSTATUS'] = $this->_isArxiv ? self::STATUS_TRANSARXIV : self::STATUS_BUFFER;
            }

            if ($bind['DOCSTATUS'] == self::STATUS_VISIBLE) {
                //On vérifie qu'il n'existe pas en base une notice avec le même identifiant même version
                $db = Zend_Db_Table_Abstract::getDefaultAdapter();

                $sql = $db->select()->from(self::TABLE, 'DOCID')
                    ->where('IDENTIFIANT = ?', $this->getId())
                    ->where('VERSION = ?', (int)$this->getVersion())
                    ->where('DOCID != ?', (int)$this->getDocid());
                $docid = $db->fetchOne($sql);
                if ($docid) {
                    //On supprime l'ancienne version
                    $old = Hal_Document::find($docid);
                    if ($old) {
                        //Copie des stats de la version supprimée sur la nouvelle
                        Hal_Document_Visite::transferStat($old->getDocid(), $this->getDocid());
                        $old->delete($this->getContributor('uid'), '', false);
                        Hal_Document_Logger::log($docid, $this->getContributor('uid'), Hal_Document_Logger::ACTION_ADDFILE);
                        // Suppression de la notice de l'index
                        Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid), 'hal', 'DELETE', 'hal', 10);
                    }
                }
                $bind['DATEMODER'] = date('Y-m-d H:i:s');
            }
            $bind['EXPORTREPEC'] = (int)!$this->_hideRePEc;
            $bind['EXPORTOAI'] = (int)!$this->_hideOAI;
            $bind['TYPDOC'] = $this->_typdoc;
            $bind['FORMAT'] = $this->getFormat();
            $db->update(self::TABLE, $bind, 'DOCID = ' . $this->_docid);

            $moderationMsg = $this->_moderationMsg !== "" ? $this->_moderationMsg : 'corrections du dépôt';

            if (Hal_Auth::isAdministrator()) {
                Hal_Document_Logger::log($this->getDocid(), Hal_Auth::getUid(), Hal_Document_Logger::ACTION_MODIF, $moderationMsg);
            } else {
                Hal_Document_Logger::log($this->getDocid(), $this->getContributor('uid'), Hal_Document_Logger::ACTION_MODIF, $moderationMsg);
            }

        } else if ($this->getTypeSubmit() == Hal_Settings::SUBMIT_UPDATE) {
            // Modification des métadonnées du dépôt
            $bind = array();
            $bind['TYPDOC'] = $this->_typdoc;
            $bind['DATEMODIF'] = date('Y-m-d H:i:s');
            $bind['EXPORTREPEC'] = (int)!$this->_hideRePEc;
            $bind['EXPORTOAI'] = (int)!$this->_hideOAI;
            $db->update(self::TABLE, $bind, 'DOCID = ' . $this->_docid);
            $logUid = $uid != 0 ? $uid : $this->getContributor('uid');
            Hal_Document_Logger::log($this->getDocid(), $logUid, Hal_Document_Logger::ACTION_UPDATE);
            $index = true;
        } else if ($this->getTypeSubmit() == Hal_Settings::SUBMIT_MODERATE) {
            // Modification du dépôt par les modérateurs
            $bind = array();
            $bind['TYPDOC'] = $this->_typdoc;
            $bind['FORMAT'] = $this->getFormat();
            $db->update(self::TABLE, $bind, 'DOCID = ' . $this->_docid);
            $logUid = $uid != 0 ? $uid : $this->getContributor('uid');
            Hal_Document_Logger::log($this->getDocid(), $logUid, Hal_Document_Logger::ACTION_EDITMODERATION, 'edition par le modérateur');

        } else if ($this->getTypeSubmit() == Hal_Settings::SUBMIT_REPLACE) {
            // Nouvelle version
            $this->_version = $this->getVersion() + 1;
            $bind = array(
                'TYPDOC' => $this->_typdoc,
                'IDENTIFIANT' => $this->getId(),
                'VERSION' => $this->getVersion(),
                'DATESUBMIT' => date('Y-m-d H:i:s'),
                'DATEMODIF' => date('Y-m-d H:i:s'),
                'UID' => $this->getContributor('uid'),
                'INPUTTYPE' => $this->_inputType,
                'TEXTAVAILABLE' => $this->isTextAvailable(),
                'SID' => $this->getSid(),
                'PWD' => $this->_pwd,
                'EXPORTREPEC' => (int)!$this->_hideRePEc,
                'EXPORTOAI' => (int)!$this->_hideOAI,
                'FORMAT' => $this->getFormat()
            );
            //Statut du dépôt
            if ($this->getFormat() == self::FORMAT_NOTICE) { //Référence biblio
                $bind['DOCSTATUS'] = Hal_Settings::validNotice() ? self::STATUS_BUFFER : self::STATUS_VISIBLE;
            } else { //Au moins 1 fichier est associé au dépôt
                $bind['DOCSTATUS'] = $this->_isArxiv ? self::STATUS_TRANSARXIV : self::STATUS_BUFFER;
            }
            if ($bind['DOCSTATUS'] == self::STATUS_VISIBLE) {
                $index = true;
            }
            $db->insert(self::TABLE, $bind);
            $this->_docid = $db->lastInsertId(self::TABLE);

            // PROPRIETAIRES ajout du nouveau contributeur si différent de version précdente
            $this->addProprio($this->getContributor('uid'));
            // Delete meta a ne pas copier:
            // +++ SWHID
            Hal_Document_Logger::log($this->getDocid(), $this->getContributor('uid'), Hal_Document_Logger::ACTION_VERSION);

            //Définition du modèle de mail envoyé au déposant et au modérateur
            $mailContrib = ($bind['DOCSTATUS'] == self::STATUS_VISIBLE) ? Hal_Mail::TPL_DOC_SUBMITTED_ONLINE : Hal_Mail::TPL_DOC_SUBMITTED;
            if ($this->getFormat() != self::FORMAT_NOTICE || Hal_Settings::validNotice()) {
                $mailModerator = Hal_Mail::TPL_ALERT_MODERATOR;
            }
        } else if ($this->getTypeSubmit() == Hal_Settings::SUBMIT_ADDFILE
            ||     $this->getTypeSubmit() == Hal_Settings::SUBMIT_ADDANNEX) {

            //Ajout d'un fichier
            $bind = array(
                'TYPDOC' => $this->_typdoc,
                'IDENTIFIANT' => $this->getId(),
                'VERSION' => $this->getVersion(),
                'DATEMODIF' => date('Y-m-d H:i:s'),
                'UID' => $this->getContributor('uid'),
                'INPUTTYPE' => $this->_inputType,
                'TEXTAVAILABLE' => $this->isTextAvailable(),
                'SID' => $this->getSid(),
                'PWD' => $this->_pwd,
                'EXPORTREPEC' => (int)!$this->_hideRePEc,
                'EXPORTOAI' => (int)!$this->_hideOAI,
                'FORMAT' => $this->getFormat()
            );

            /*
            * Dans le cas de l'ajout d'un fichier à un notice, la date de soumission est la date du jour
            * Dans le cas de l'ajout d'une annexe, on ne modifie pas la date de soumission
            */
            if ($this->getTypeSubmit() == Hal_Settings::SUBMIT_ADDFILE) {
                $bind['DATESUBMIT'] = date('Y-m-d H:i:s');
            } else {
                $bind['DATESUBMIT'] = $this->getSubmittedDate();
            }

            //Statut du dépôt
            if ($this->getFormat() == self::FORMAT_NOTICE) { //Référence biblio
                $bind['DOCSTATUS'] = Hal_Settings::validNotice() ? self::STATUS_BUFFER : self::STATUS_VISIBLE;
            } else { //Au moins 1 fichier est associé au dépôt
                $bind['DOCSTATUS'] = $this->_isArxiv ? self::STATUS_TRANSARXIV : self::STATUS_BUFFER;
            }
            $db->insert(self::TABLE, $bind);
            $this->_docid = $db->lastInsertId(self::TABLE);

            // PROPRIETAIRES ajout du nouveau contributeur si différent de version précédente
            // boucle pour recréer tous les propriétaire
            $array_owner = $this->getOwner();
            foreach( $array_owner as $owner){
                $this->addProprio($owner);
            }
            // JB 18/06/2019 correction ci dessus. Boucle d'ajout de propriété faite uniquement sur contributeur en lieu et place de tous les owners
            // ticket #61 github
            //$this->addProprio($this->getContributor('uid'));

            $logUid = ($uid != 0) ? $uid : $this->getContributor('uid');
            Hal_Document_Logger::log($this->getDocid(), $logUid, Hal_Document_Logger::ACTION_ADDFILE);

            //Définition du modèle de mail envoyé au déposant et au modérateur
            $mailContrib = ($bind['DOCSTATUS'] == self::STATUS_VISIBLE) ? Hal_Mail::TPL_DOC_SUBMITTED_ONLINE : Hal_Mail::TPL_DOC_SUBMITTED;
            if ($this->getFormat() != self::FORMAT_NOTICE || Hal_Settings::validNotice()) {
                $mailModerator = Hal_Mail::TPL_ALERT_MODERATOR;
            }
        }

        if (($this->getFormat() == self::FORMAT_FILE) && ($this->getTypeSubmit() != Hal_Settings::SUBMIT_UPDATE)) {
            // Si on a decoche ou recocher la case transfert Arxiv
            // On doit supprimer le document de la table de transfert ET faire que le document soit mis en moderation standard
            // Le test sur Hal_Settings::SUBMIT_UPDATE car les status Arxiv ne doit pas changer si modif de la notice
            //-- arXiv

            $docstatus =  self::STATUS_BUFFER;
            $transfertArxiv = Hal_Transfert_Arxiv::init_transfert($this);
            if ($this->_isArxiv || ($transfertArxiv -> getRemoteId() != null)) {
                // On n'efface pas si un Id est deja present! ou si le transfert est demande
                $transfertArxiv -> save();
                $docstatus =  self::STATUS_TRANSARXIV;
            } else {
                // Arxiv non coche: on efface le transfert (si demande auparavant)
                $transfertArxiv -> delete();
            }

            $db->update(self::TABLE, array('DOCSTATUS' => $docstatus), 'DOCID =' . $this->_docid);
            //-- PubMed Central // TODO utiliser un WS HALMS
            if (!$this->gotoPMC()) {
                $db->delete(self::TABLE_PMC, 'DOCID = ' . $this->_docid);
            }
            if ($this->_isPmc) {
                $db->insert(self::TABLE_PMC, array('DOCID' => $this->_docid));
            }
        }

        //Software Heritage
        if ($this->_isSwh) {
            try {
                $db->insert(Hal_Transfert_SoftwareHeritage::$TABLE, ['DOCID' => $this->_docid]);
            } catch (Exception $e) {
                //duplicate entry
            }
        }


        //-- METADONNEES
        $this->_metas->save($this->_docid, $this->getSid());

        //RELATIONS
        $this->saveRelated(true, Hal_Auth::getUid());

        // -- STRUCTURE
        // a- Suppression des anciennes structures du document
        $db->query('DELETE FROM autlab USING ' . Hal_Document_Author::TABLE_DOCAUTHSTRUCT . ' autlab, ' . Hal_Document_Author::TABLE . ' aut WHERE autlab.DOCAUTHID = aut.DOCAUTHID AND aut.DOCID = ' . $this->_docid);

        // b- Enregistrement des nouvelles structures
        $structs = array();
        foreach ($this->getStructures() as $structidx => $structure) {
            try {
                if (!$structure->getStructid())
                    $structure->save($this->_docid);
            } catch (Exception $e) {
                // TODO BM: Est-ce bien raisonable de ne pas traiter cette exception?
            }
            $structs[$structidx] = $structure->getStructid();
        }

        // -- AUTEURS
        // a- Suppression des anciens auteurs du document
        $db->delete(Hal_Document_Author::TABLE, 'DOCID = ' . $this->_docid);
        $db->delete(Hal_Document_Author::TABLE_DOC_ID, 'DOCID = ' . $this->_docid);
        // b- Enregistrement des nouveaux auteurs
        foreach ($this->getAuthors() as $author) {

            $docauthid = $author->saveDocAuthor($this->_docid);
            if ($docauthid !== false) {
                foreach ($author->getStructidx() as $structidx) {
                    // Enregistrement dans DOC_AUTLAB
                    $bind = array(
                        'DOCAUTHID' => $docauthid,
                        'STRUCTID' => $structs[$structidx]
                    );
                    $db->insert(Hal_Document_Author::TABLE_DOCAUTHSTRUCT, $bind);
                }
            }

        }

        // Enregistrement des fichiers
        $erreur = false;
        if ($this->getTypeSubmit() != Hal_Settings::SUBMIT_UPDATE && $this->existFile()) {
            $db->delete(Hal_Document_File::TABLE, 'DOCID = ' . $this->_docid);
            $dest = $this->getRacineDoc();
            if ($this->getTypeSubmit() != Hal_Settings::SUBMIT_ADDFILE && $this->getTypeSubmit() != Hal_Settings::SUBMIT_ADDANNEX) {
                //Suppression du repertoire
                Ccsd_Tools::deletedir($dest);
            }
            // modif des métadonnées (pas des fichiers)
            foreach ($this->getFiles() as $file) {
                if (!$file->save($this->_docid, $dest)) {
                    Ccsd_Log::message('Error in docid ' . $this->_docid . ' for file ' . $file->getPath() . ' to ' . $dest, false, '', PATHTEMPDOCS . 'file');
                    $erreur = true;
                }
            }
        }

        //Envoi des mails
        if ($sendMail && $mailContrib != null) {
            foreach (array_unique(array_merge($this->getOwner(), array($this->getContributor('uid')), array(strval(Hal_Auth::getUid())))) as $uidmail) {
                $users = new Hal_User();
                $users->find($uidmail);
                if ($users && $users->getPrefMailAuthor()) {
                    $mail = new Hal_Mail();
                    $mail->prepare($users, $mailContrib, array($this));
                    $mail->writeMail();
                }
            }
        }
        if ($sendMail && $mailModerator != null) {
            foreach ($this->getModerators() as $uid) {
                $moderator = new Hal_User();
                $moderator->find($uid);
                $mail = new Hal_Mail();
                $mail->prepare($moderator, $mailModerator, array($this));
                $mail->writeMail();
            }
        }

        //Tamponnage pour les portails/collections
        $site = Hal_Site::getCurrentPortail();
        /** @var Hal_Site_Settings_Portail $settings */
        $settings = $site->getSettingsObj();
        $collectionSid = $settings ->getAssociatedCollId();
        // Todo replace $id pas obj
        if ($collectionSid !== 0) {
            $collection = $settings ->getAssociatedColl();
            Hal_Document_Collection::add($this->_docid, $collection);
        }

        if ($index) {
            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->_docid));

            // Puisqu'on ne passe pas par putOnline dans le cas d'une notice mise en ligne directement, on envoie les alertes mails depuis ici
            if ($this->getFormat() == self::FORMAT_NOTICE && !Hal_Settings::validNotice() && $this->getTypeSubmit() == Hal_Settings::SUBMIT_INIT && $sendMail) {
                $this->alertAndShareOwnershipToContributors();
            }
        }

        if (!($erreur)) {
            //Suppression du cache
            $this->deleteCache();
        }

        return $this->_docid;
    }

    /**
     * Enregistrement d'un identifiant extérieur (version du dépôt sur arxiv, pubmed, ...)
     * @param int    $docid identifiant du dépôt hal
     * @param string $code serveur extérieur
     * @param string $localid identifiant sur serveur extérieur
     * @throws Zend_Db_Adapter_Exception
     *
     * ATTENTION: l'objet document n'est pas modifie!!, la meta identifiant de l'objet n'est pas mis a jour
     */
    public function addIdExt($docid, $code, $localid)
    {
        Hal_Document_Meta_Identifier::addIdExtDb($docid, $code, $localid);
    }


    /**
     * Modification de la date de modification du document
     * False in case of Db failure
     * @param int $docid
     * @return bool
     */
    static public function changeDateModif($docid)
    {
        try {
            Zend_Db_Table_Abstract::getDefaultAdapter()->update(self::TABLE, array('DATEMODIF' => date('Y-m-d H:i:s')), 'DOCID =' . (int)$docid);
        } catch (Zend_Db_Adapter_Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Retourne le document sous forme d'un tableau
     * @return array
     */
    public function toArray()
    {
        $array = array(
            'docid' => $this->getDocid(),
            'identifiant' => $this->getId(),
            'uri' => $this->getUri(true),
            'version' => $this->getVersion(),
            'versions' => $this->getDocVersions(),
            'status' => $this->getStatus(),
            'format' => $this->getFormat(),
            'typdoc' => $this->getTypDoc(),
            'sid' => $this->getSid(),
            'related' => $this->getRelated(),
            'instance' => $this->getInstance(),
            'submittedDate' => $this->getSubmittedDate(),
            'releasedDate' => $this->getReleasedDate(),
            'producedDate' => $this->getProducedDate(),
            'modifiedDate' => $this->getLastModifiedDate(),
            'archivedDate' => $this->getArchivedDate(),
            'contributor' => $this->getContributor(),
            'citationRef' => $this->getCitation('ref'),
            'citationFull' => $this->getCitation('full'),
            'thumbid' => $this->getThumbid(),
            'uid' => $this->getContributor('uid'),
            'owners' => $this->getOwner(),
            'selfArchiving' => $this->getSelfArchiving(),
            'metas' => array()
        );

        $array['metas'] = $this->getHalMeta()->toArray();

        foreach ($this->getAuthors() as $author) {
            $array['authors'][] = $author->toArray();
        }
        foreach ($this->getStructures() as $structure) {
            $array['structures'][] = $structure->toArray();
        }
        foreach ($this->getFiles() as $file) {
            $array['files'][] = $file->toArray();
        }
        foreach ($this->getCollections() as $collection) {
            $array['collections'][] = $collection->toArray();
        }
        return $array;
    }

    /** SAuvegarde les lien de relation entre document
     *  ToDO  : A mettre dans un Hal/Document/Relation
     *  TODO: Passer en objet (Relation)  et $this->_related est un Relation[]
     * @param bool $reindex
     * @param int $uid
     * @return int   Nombre d'erreur en insertion.
     */
    public function saveRelated($reindex = false, $uid = null)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $docid = $this->_docid;
        $identifiantCible = self::getIdFromDocid($docid);

        $db->delete(self::TABLE_RELATED, 'DOCID = ' . $this->_docid);
        // On enleve ce qui pointe vers le document
        // Les relations etant symetriques, on va les remettre si necessaire
        $db->delete(self::TABLE_RELATED, 'IDENTIFIANT = "' . $this->_identifiant . '"');

        $corresp = [
            // hasVersion: pour a une version en francais par exemple, sinon on est dans le v1..vn de HAL
            'isRequiredBy'   => 'requires',        'requires'        => 'isRequiredBy',
            'isPartOf'       => 'hasPart',         'hasPart'         => 'isPartOf',
            'isReferencedBy' => 'references',      'references'      => 'isReferencedBy',
            'isFormatOf'     => 'hasFormat',       'hasFormat'       => 'isFormatOf',
            'illustrate'     => 'isIllustratedBy', 'isIllustratedBy' => 'illustrate',
            'conformsTo'     => 'conformsTo',      'hasVersion'      => 'isVersionOf'];
        $return = 0;
        foreach ($this->_related as $related) {
            $identifiantCible2 = trim($related['IDENTIFIANT']);
            if ($identifiantCible2  == '') {
                continue;
            }
            $docid2 = self::getDocidFromId($identifiantCible2 );
            $bind1 = array(
                'DOCID' => $docid,
                'IDENTIFIANT' => $identifiantCible2,
                'RELATION'    => $related['RELATION'],
                'INFO'        => $related['INFO']
            );

            $bind2 = array(
                'DOCID' => $docid2,
                'IDENTIFIANT' => $identifiantCible,
                'RELATION'    => $corresp[$related['RELATION']],
                'INFO'        => ''
            );

            try {
                $db->insert(self::TABLE_RELATED, $bind1);
                $db->insert(self::TABLE_RELATED, $bind2);
            } catch(Exception $e) {
                Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't insert $docid " . $related['RELATION'] . " $identifiantCible (or reverse) in " . self::TABLE_RELATED);
                $return ++;
            }
            if ($reindex && !is_null($docid) && !is_null($docid2)) {
                $docids = array($docid, $docid2);
                Hal_Document_Logger::log($docid,  $uid, Hal_Document_Logger::ACTION_RELATED);
                Hal_Document_Logger::log($docid2, $uid, Hal_Document_Logger::ACTION_RELATED);
                self::deleteCaches($docids);
                Ccsd_Search_Solr_Indexer::addToIndexQueue($docids);
            }
        }
        return $return;
    }

    /**
     * Génération de l'identifiant d'un article
     *
     * @param int $docid
     * @param string $base
     * @return string
     */
    public function generateId($docid, $base)
    {
        return $base . str_pad($docid, 8, "0", STR_PAD_LEFT);
    }

    /**
     * Génération du mot de passe d'un article
     *
     * @param int $min
     *            nombre min de caractères
     * @param int $max
     *            nombre max de caractères
     * @return string
     */
    public function generatePw($min = 6, $max = 8)
    {
        $pass = "";
        $nbchar = rand($min, $max);
        $chars = array("#", "&", "@", "?", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", 0, 1, 2, 3, 4, 5, 6, 7, 8, 9);
        for ($i = 0; $i < $nbchar; $i++) {
            $pass .= $chars[rand(0, count($chars) - 1)];
        }
        return $pass;
    }

    /**
     * Retourne le mot de passe de l'article
     *
     * @return String
     */
    public function getPwd()
    {
        return $this->_pwd;
    }

    /**
     * Indique si un document est en ligne
     * @return boolean
     */
    public function isOnline()
    {
        return $this->isVisible() || $this->_status == self::STATUS_REPLACED;
    }

    /**
     * Indique si un document est a indexer
     * @param int $status
     * @return bool
     */
    public static function isIndexable($status){
        // Yet, it's equivalent to isOnline but this can change...
        return $status == self::STATUS_REPLACED || $status == self::STATUS_VISIBLE;
    }

    /**
     * Indique si le document est visible
     * @return boolean
     */
    public function isVisible()
    {
        return $this->_status == self::STATUS_VISIBLE;
    }

    /**
     * Indique si l'utilisateur est propriétaire du document
     * @param int $uid
     * @return boolean
     */
    public function isOwner($uid = 0)
    {
        return in_array($uid, $this->getOwner());
    }

    /**
     * Indique si un document est en attente de modifications
     * @param int $status
     * @return boolean
     */
    public function isWaitingModifications($status = null)
    {
        if ($status == null) {
            $status = $this->_status;
        }
        return $status == self::STATUS_MODIFICATION;
    }

    /**
     * @param int $status
     * @return bool
     */
    public function isMySpace($status = null)
    {
        if ($status == null) {
            $status = $this->_status;
        }
        return $status == self::STATUS_MYSPACE;
    }

    /**
     * Indique si un document est un dépôt fulltext ou une notice
     * @param int $file
     * @return boolean
     */
    public function isFulltext($file = null)
    {
        if ($file == null) {
            $file = $this->_format;
        }
        return $file == 'file';
    }

    /**
     * indique si le document sera transféré sur arXiv
     * @param bool $transfert
     */
    public function setTransfertArxiv($transfert)
    {
        $this->_isArxiv = $transfert;
    }

    /**
     * @todo: A deplacer dans Hal_Transfert...
     * indique si le document doit être transféré sur arXiv
     * @return bool
     */
    public function gotoSWH()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(Hal_Transfert_SoftwareHeritage::$TABLE, 'COUNT(*)')
            ->where('DOCID = ?', $this->_docid);
        return (bool)$db->fetchOne($sql);
    }

    /**
     * @todo: A deplacer dans Hal_Transfert...
     * indique si le document doit être transféré sur arXiv
     * @return bool
     */
    public function gotoArxiv()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(Hal_Transfert_Arxiv::$TABLE, 'COUNT(*)')
            ->where('DOCID = ?', $this->_docid);
        return (bool)$db->fetchOne($sql);
    }

    /**
     * Indique si le document sera transféré sur PMC
     * @param $transfert
     */
    public function setTransfertPMC($transfert)
    {
        $this->_isPmc = $transfert;
    }

    /**
     * Indique si le dépôt doit être transféré sur Software Heritage
     * @param boolean $transfert
     */
    public function setTransfertSWH($transfert)
    {
        $this->_isSwh = $transfert;
    }

    /**
     * @return bool
     */
    public function gotoPMC()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE_PMC, 'COUNT(*)')
            ->where('DOCID = ?', $this->_docid);
        return (bool)$db->fetchOne($sql);
    }

    /**
     * Indique si le document sera visible sur OAI
     * @param $hide
     */
    public function setHideOAI($hide)
    {
        $this->_hideOAI = (bool)$hide;
    }

    /**
     * Indique si le document sera visible pour RePEc
     * @param $hide
     */
    public function setHideRePEc($hide)
    {
        $this->_hideRePEc = (bool)$hide;
    }

    /**
     * Indique si un document est indexé par solr (donc visible)
     * @return bool
     */
    public function isIndexed()
    {
        try {
            $res = unserialize(Hal_Tools::solrCurl('q=docid:' . $this->_docid . '&rows=0&omitHeader=true&wt=phps', 'hal', 'select', true, true));
        } catch (Exception $e) {
            // 2eme tentative
            try {
                $res = unserialize(Hal_Tools::solrCurl('q=docid:' . $this->_docid . '&rows=0&omitHeader=true&wt=phps', 'hal', 'select', true, true));
            } catch (Exception $e) {
                return false;
            }
        }

        return $res['response']['numFound'] == 1;
    }

    /**
     * Récupération du contributeur d'un papier
     * @param string
     * @return mixed
     */
    public function getContributor($option = null)
    {
        if ($option != null) {
            return (isset($this->_contributor[$option])) ? $this->_contributor[$option] : '';
        }
        return $this->_contributor;
    }

    /**
     * indique le contributeur
     *
     * @param int
     */
    public function setContributorId($uid)
    {
        $this->_contributor['uid'] = $uid;
    }

    /**
     * /**
     * indique le contributeur
     *
     * @param Hal_User
     */
    public function setContributor(Hal_User $user)
    {
        $this->_contributor['uid'] = $user->getUid();
        $this->_contributor['email'] = $user->getEmail();
        $this->_contributor['lastname'] = $user->getLastname();
        $this->_contributor['firstname'] = $user->getFirstname();
        $this->_contributor['fullname'] = Ccsd_Tools::formatUser($user->getFirstname(), $user->getLastname());
    }

    /**
     * Retourne les proprietaires d'un papier
     *
     * @return array
     */
    public function getOwner()
    {
        return array_unique(array_merge($this->_owners, array($this->getContributor('uid'))));
    }

    /**
     * Affecte les proprietaires d'un papier
     *
     * @param array
     */
    public function setOwner(array $owners)
    {
        $this->_owners = $owners;
    }

    /**
     * Récupération des identifiants de structures d'un papier
     *
     * @return array
     */
    public function getStructids()
    {
        $res = array();
        foreach ($this->getStructures() as $structure) {
            $res[] = $structure->getStructid();
            $res = array_merge($res, $structure->getParentsStructids());
        }
        return array_unique($res);
    }

    /**
     * Indique si le dépôt possède une nouvelle version en cours
     *
     * @return bool
     */
    public function isVersionsNonDispos()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE, 'COUNT(*) AS RES')
            ->where('IDENTIFIANT = ?', $this->getId())
            ->where('DOCSTATUS NOT IN (?)', array(
                self::STATUS_VISIBLE,
                self::STATUS_REPLACED
            ));
        return $db->fetchOne($sql);
    }

    /**
     * @deprecated : utiliser replaceMeta de Meta_Simple ou adapter pour un autre type de métadonnée
     * @param int $docid
     * @param string $metaname
     * @param null $metavalue
     * @param null $oldvalue
     * @param string $metagroup
     * @return bool|int
     */
    static public function updateMeta($docid = 0, $metaname = '', $metavalue = null, $oldvalue = null, $metagroup = '')
    {
        $halMeta = new Hal_Document_Meta_Simple($metaname, $metavalue, $metagroup, '', 0);
        return $halMeta->replaceMeta($docid, $oldvalue);
    }

    /**
     * @param int    $docid
     * @param string $metaname
     * @param mixed  $metavalue
     * @param string $metagroup
     * @param string $oldlang
     * @return bool|int
     */
    static public function updateMetaGroup($docid = 0, $metaname = '', $metavalue = null, $metagroup = '', $oldlang = null)
    {
        try {
            if ($metaname == '') {
                return false;
            }

            $db = Zend_Db_Table_Abstract::getDefaultAdapter();

            $where['METANAME = ?'] = $metaname;
            if ($metagroup != '') {
                $where['METAGROUP = ?'] = $metagroup;
            }
            if ($oldlang != null) {
                $where['METAGROUP = ?'] = $oldlang;
            }
            if (is_array($docid)) {
                $where['DOCID IN (?)'] = $docid;
            } else {
                $where['DOCID = ?'] = (int)$docid;
            }
            $sql = $db->update(Hal_Document_Metadatas::TABLE_META, array('METAGROUP' => $metavalue), $where);
            self::deleteCaches($docid);
            return $sql;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param int    $docid
     * @param string $metaname
     * @param mixed  $metavalue
     * @param string $metagroup
     * @return bool
     */
    static public function ajoutMeta($docid = 0, $metaname = '', $metavalue = null, $metagroup = null)
    {
        try {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();

            if (is_numeric($metagroup)) {
                $sql = $db->select()
                    ->from(Hal_Document_Metadatas::TABLE_META, new Zend_Db_Expr("COUNT(*)"))
                    ->where('METANAME = ?', $metaname)
                    ->where('DOCID = ?', $docid);

                $count = $db->fetchOne($sql);
                $metagroup = $count;
            }
            if (preg_match('/înter_/', $metavalue)) {
                $metavalue = preg_replace('/înter_/', '', $metavalue);
            }
            $bind = array(
                'DOCID' => $docid,
                'METANAME' => $metaname,
                'METAVALUE' => $metavalue,
                'METAGROUP' => $metagroup
            );
            $db->insert(Hal_Document_Metadatas::TABLE_META, $bind);
            self::deleteCaches($docid);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $metaname
     * @param mixed $metavalue
     * @return bool
     */
    static public function existMetaValueFor($metaname, $metavalue)
    {
        if ($metaname == '') {
            return false;
        }

        if (preg_match('/inter_/', $metavalue)) {
            $metavalue = preg_replace('/inter_/', '', $metavalue);
        }

        $select = Zend_Db_Table_Abstract::getDefaultAdapter()->select()
            ->from(Hal_Document_Metadatas::TABLE_META, new Zend_Db_Expr("COUNT(*)"))
            ->where("METANAME = ?", $metaname)
            ->where("METAVALUE = ?", $metavalue);

        return (bool)Zend_Db_Table_Abstract::getDefaultAdapter()->fetchOne($select);
    }

    /**
     * @param int $uid
     * @param string $type
     * @param int $structid
     * @throws Zend_Exception
     */
    protected function writeOwnership($uid, $type, $structid = 0)
    {

        if ($type == 'AUT') {

            $tokenData = array(
                'Uid' => $uid,
                'Docid' => $this->_docid
            );

            $token = new Hal_Document_Tokens_Ownership($tokenData);
            $token->generateUserToken();
            $token->setUsage('UNSHARE'); // token pour le retrait de la propriété

            $tokenMapper = new Hal_Document_Tokens_OwnershipMapper($token);
            $tokenSaveResult = $tokenMapper->save($token);
        }

        try {
            $webSiteUrl = Zend_Registry::get('website')->getUrl();
        } catch (Exception $e) {
            $webSiteUrl = 'https://' . $_SERVER ['SERVER_NAME'];
        }

        $user = new Hal_User();
        $user->find($uid);

        if ($structid) {
            $struct = new Hal_Document_Structure($structid);
            $structname = empty($struct->getSigle()) ? $struct->getStructname() : $struct->getSigle();
        }

        $mail = new Hal_Mail();

        $portail = Hal_Site::loadSiteFromId($this->getSid());

        switch ($type) {
            case 'AUT' :
                $mail->prepare($user, Hal_Mail::TPL_ALERT_OWNERSHIP, array(
                $this,
                'MAIL_TO_FULLNAME' => $user->getFirstname() .' '.$user->getLastname(),
                'DOCUMENT_URL' => $portail->getUrl() . '/' .$this->_identifiant,
                'DOC_FORMAT' => Zend_Registry::get('Zend_Translate')->translate('format_'.$this->_format),
                'OWNERSHIP_OK_URL'  => $webSiteUrl .'/user/acceptownership/id/'  .$this->_identifiant,
                'OWNERSHIP_KO_URL'  => $webSiteUrl .'/user/removeownership/uid/'.$uid.'/docid/'.$this->_docid.'/token/'.$token->getToken(),
                'MODIFY_PROFIL_URL' => $webSiteUrl .'/user/editprefmail',
                'MAIL_SIGNATURE' => 'CCSD'));
                break;
            case 'STRUCT' :
                $mail->prepare($user, Hal_Mail::TPL_ALERT_REFSTRUCT, array(
                    $this,
                    'STRUCTNAME' => $structname,
                    'MAIL_TO_FULLNAME' => $user->getFirstname() .' '.$user->getLastname(),
                    'DOCUMENT_URL' => $portail->getUrl() . '/' .$this->_identifiant,
                    'DOC_FORMAT' => Zend_Registry::get('Zend_Translate')->translate('format_'.$this->_format),
                    'AUTHOR_NAME' => '',
                    'STRUCT_NAME' => '',
                    'MODIFY_PROFIL_URL' => $webSiteUrl . '/user/editprefmail',
                    'MAIL_SIGNATURE' => 'CCSD'));
                break;
            case 'ADMIN' :
                $mail->prepare($user, Hal_Mail::TPL_ALERT_ADMIN, array(
                    $this,
                    'MAIL_TO_FULLNAME' => $user->getFirstname() .' '.$user->getLastname(),
                    'DOCUMENT_URL' => $portail->getUrl() . '/' .$this->_identifiant,
                    'DOC_FORMAT' => Zend_Registry::get('Zend_Translate')->translate('format_'.$this->_format),
                    'MODIFY_PROFIL_URL' => $webSiteUrl . '/user/editprefmail',
                    'MAIL_SIGNATURE' => 'CCSD'));
                break;
        }

        $mail->writeMail();
    }

    /**
     *
     */
    protected function alertAndShareOwnershipToContributors()
    {
        //8- Partage de propriété aux co-auteurs
        $coAuthUID = $this->getAuthorsUID(true);
        $doc_owner = new Hal_Document_Owner();
        $doc_owner->shareOwnership($this, $coAuthUID);

        //9- Envoi du mail aux co-auteurs / référents structures / administrateurs portail
        $mailUID['AUT'] = $this->getAuthorsUID(true);
        $mailUID['ADMIN'] = $this->getAdminUID(true);

        // On s'assure qu'une personne ne reçoit qu'un seul mail pour un dépôt même s'il est référent de 4 structures et admin du portail
        $alreadyNotified = [];

        // Cas simple pour les auteurs et admin portail
        foreach ($mailUID as $type => $uidmails) {
            foreach ($uidmails as $uidmail) {
                if ($uidmail != $this->getContributor('uid') && !in_array($uidmail, $alreadyNotified)) {
                    $this->writeOwnership($uidmail, $type);
                    $alreadyNotified[] = $uidmail;
                }
            }
        }

        // Cas plus complexe pour les référents structure
        $uidmails = $this->getAdminStructUID(true);

        foreach ($uidmails as $uidmail => $structid) {
            if ($uidmail != $this->getContributor('uid') && !in_array($uidmail, $alreadyNotified)) {
                $this->writeOwnership($uidmail, 'STRUCT', $structid);
                $alreadyNotified[] = $uidmail;
            }
        }
    }

    public function reIndexDirect ($update) {
        $this->reIndexStatic($this->getDocid(),$update);
    }

    public static function reIndexStatic ($docid, $update) {
        $options['env'] = APPLICATION_ENV;
        $indexer = new Ccsd_Search_Solr_Indexer_Halv3($options);
        $indexer->setOrigin($update);
        $indexer->processDocid($docid);
    }

    /**
     * Mise en ligne d'un document
     * @param int $uid id moderateur
     * @param string $comment commentaire modérateur
     * @param bool
     * @return bool
     */
    public function putOnline($uid, $comment = '', $sendMail = true)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()->from(self::TABLE, 'DOCID')
            ->where('IDENTIFIANT = ?', $this->getId())
            ->where('VERSION = ?', (int)$this->getVersion())
            ->where('DOCID != ?', (int)$this->getDocid());
        $result = $db->fetchCol($sql);
        if (count($result) >= 1) {
            //Cas de l'ajout d'un fichier au dépôt
            //On supprime les anciennes versions (nettoyage au cas où)
            try {
                $newDocid = $this->getDocid();
                foreach ($result as $docid) {
                    $old = Hal_Document::find($docid);
                    if ($old) {
                        $oldDocid = $old->getDocid();
                        //Copie des stats de la version supprimée sur la nouvelle
                        Hal_Document_Visite::transferStat($oldDocid, $newDocid);

                        // Copie des logs de la version supprimée
                        Hal_Document_Logger::copyLogs($oldDocid, $newDocid);

                        // Copie des métrics
                        Hal_Stats::moveStats($oldDocid, $newDocid);

                        //Copie les tampons de la version supprimée sur la nouvelle
                        Hal_Document_Collection::transferColl($oldDocid, $newDocid);

                        // Recuperation des transferts Arxiv/SWH...
                        Hal_Transfert_Arxiv::changeDocid($oldDocid, $newDocid);
                        Hal_Transfert_SoftwareHeritage::changeDocid($oldDocid, $newDocid);

                    //Suppression de la notice
                    $old->delete($uid, '', false, true);
                    Hal_Document_Logger::log($docid, $uid, Hal_Document_Logger::ACTION_ADDFILE);
                }
                }
            } catch (Exception $e) {
                Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Lors de la mise en ligne: " .  $e->getMessage());
            }
        }
        if ($this->getVersion() > 1) {
            // Cas des nouvelles versions, changer le statut des versions précédentes
            $sql = $db->select()->from(self::TABLE, 'DOCID')
                ->where('IDENTIFIANT = ?', $this->getId())
                ->where('VERSION < ?', $this->getVersion())
                ->where('DOCID != ?', $this->getDocid())
                ->where('DOCSTATUS != ?', self::STATUS_REPLACED); // pour eviter de reindexer les anciennes versions deja ok
            foreach ($db->fetchCol($sql) as $docid) {
                $bind = array('DOCSTATUS' => self::STATUS_REPLACED);
                $db->update(self::TABLE, $bind, 'DOCID = ' . $docid);
                self::deleteCaches($docid);
                $this->reIndexStatic($docid,Ccsd_Search_Solr_Indexer::O_UPDATE);
            }
        }
        $datemoder = $this->getReleasedDate();
        // 1- Changement du statut du papier
        $bind = array(
            'DOCSTATUS' => self::STATUS_VISIBLE,
            'DATEMODER' => date('Y-m-d H:i:s')
        );
        if ($db->update(self::TABLE, $bind, 'DOCID = ' . $this->getDocid())) {
            // 2- création des imagettes
            if (defined('APPLICATION_ENV') && APPLICATION_ENV == ENV_PROD) {
                //Uniquement en production
                try {
                    $this->createImagettes();
                } catch (Exception $e) {
                    Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Exception in createImagettes for docid: " . $this->getDocid());
                    // On echoue pas si les imagettes echouent
                }
            }

            // 3- Incrémente le nombre de doc visible
            $sql = $db->select()->from(self::TABLE, 'UID')
                ->where('DOCID = ?', $this->getDocid());
            $uiddoc = $db->fetchOne($sql);
            $db->update("USER", array('NBDOCVIS' => new Zend_Db_Expr('NBDOCVIS + 1')), 'UID = ' . $uiddoc);

            // 4- Log de l'action
            Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_MODERATE, $comment);

            // 5- Suppression des fichiers de cache
            $this->deleteCache();

            // 6- Réindexation
            $this->reIndexDirect(Ccsd_Search_Solr_Indexer::O_UPDATE);

            // 7- Envoi du mail de confirmation au déposant
            if ($sendMail) {
                foreach ($this->getOwner() as $uidmail) {
                    $users = new Hal_User();
                    $users->find($uidmail);
                    $mail = new Hal_Mail();
                    $mail->prepare($users, Hal_Mail::TPL_DOC_ACCEPTED, array('MSG_MODERATEUR' => $comment, 'document' => $this));
                    $mail->writeMail();
                }
            }

            //8- Partage de propriété aux co-auteurs
            // V1 + Datemoder Null
            if ($this->getVersion() == 1 && $datemoder == null) {
                $this->alertAndShareOwnershipToContributors();
            }

            //HALMS - Si une demande a été faite, on le rend visible dans HALMS
            if ($this->_isPmc) {
                $res = $db->fetchOne($db->select()->from(self::TABLE_PMC, 'DOCID')->where('DOCID = ?', $this->_docid)->where('DOCSTATUS IS NULL'));
                if ($res == $this->_docid) {
                    $db->update(self::TABLE_PMC, ['DOCSTATUS' => 0], 'DOCID = ' . $this->_docid);
                }
            }

            //Suppression du cache CV de chaque Auteur ayant un idhal
            foreach ($this->getAuthorwithIdhal($this->_docid) as $k => $idhal) {
                Hal_Cache::delete('cv.' . $idhal . '.phps', CACHE_CV);
            }
            return true;
        }
        return false;
    }

    /**
     * Remise en modération d'un document
     * @return bool
     */
    public function putOnModeration()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        if ($db->update(self::TABLE, array('DOCSTATUS' => self::STATUS_BUFFER), 'DOCID = ' . $this->getDocid())) {
            // Suppression des fichiers de cache
            $this->deleteCache();
            return true;
        }
        return false;
    }


    /**
     * Document à modifier par le déposant
     * @param int $uid
     * @param string $comment
     * @param bool $sendMail
     * @param bool $reminder
     * @return boolean
     */
    public function toUpdate($uid, $comment, $sendMail = true, $reminder = false)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // Changement de statut du document

        if ($reminder == true) {
            // pour une relance récupère aussi le commentaire précédent
            $comment .= PHP_EOL . Hal_Document_Logger::getLastComment($this->getDocid(), Hal_Document_Logger::ACTION_ASKMODIF);
        } else {
            try {
                $db->update(self::TABLE, array(
                    'DOCSTATUS' => self::STATUS_MODIFICATION
                ), 'DOCID = ' . $this->getDocid());
            } catch (Exception $e) {
                return false;
            }
        }

        // 2- Log de l'action
        Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_ASKMODIF, $comment);

        // 3- Suppression des fichiers de cache
        $this->deleteCache();

        // 4- Envoi du mail au déposant
        if ($sendMail) {
            foreach ($this->getOwner() as $uidmail) {
                $users = new Hal_User();
                $users->find($uidmail);
                $mail = new Hal_Mail();
                if ($reminder == true) {
                    $mail->prepare($users, Hal_Mail::TPL_DOC_TOUPDATE_REMINDER, array(
                        'MSG_MODERATEUR' => $comment,
                        'document' => $this
                    ));
                } else {
                    $mail->prepare($users, Hal_Mail::TPL_DOC_TOUPDATE, array(
                        'MSG_MODERATEUR' => $comment,
                        'document' => $this
                    ));
                }
                $mail->writeMail();
            }
            return true;
        }
    }

    /**
     * Annotation du document
     * @param $uid
     * @param $comment
     * @return bool
     */
    public function annotate($uid, $comment)
    {
        return Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_ANNOTATE, $comment);
    }


    /**
     * Réponse du déposant à une demande des modérateurs
     * @param int $uid
     * @param string $comment
     * @return bool
     */
    public function reply($uid, $comment)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        //Changement de statut du document

        if ($db->update(self::TABLE, array('DOCSTATUS' => self::STATUS_BUFFER), 'DOCID = ' . $this->getDocid())) {
            // 2- Log de l'action
            Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_MODIF, $comment);

            // 3- Suppression des fichiers de cache
            $this->deleteCache();
            return true;
        }
        return false;
    }

    /**
     * Le document doit-il avoir un fichier ?
     * @return bool
     */
    public function isFileRequired()
    {
        return in_array($this->getTypDoc(), Hal_Settings::getTypdocFulltext());
    }

    /**
     * @return bool
     */
    public function uniqueFileLimited()
    {
        return Hal_Settings::getFileLimit($this->getTypDoc()) == 1;
    }

    /**
     * Vérifie qu'un dépôt est transformable en notice
     * cad il n'existe pas déjà un dépôt en ligne avec le même identifiant
     * @see https://wiki.ccsd.cnrs.fr/wikis/ccsd/index.php/Versionnement_des_documents
     * @return bool
     */
    public function isNoticeable()
    {
        // Le document ne peut pas être tranformé en notice si le fichier est nécessaire
        if ($this->isFileRequired()) {
            return false;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // recherche des dépôts en ligne avec le même identifiant
        $sql = $db->select()->from(self::TABLE, 'DOCID')
            ->where('IDENTIFIANT = ?', $this->getId())
            ->where('DOCSTATUS = ?', self::STATUS_VISIBLE)
            ->where('DOCID != ?', $this->getDocid());
        if (count($db->fetchCol($sql))) return false;
        else return true;
    }

    /**
     * Transformation en notice
     * @param $uid
     * @param $comment
     * @param bool $sendMail
     * @return bool
     */
    public function notice($uid, $comment, $sendMail = true)
    {
        if (!$this->isNoticeable()) {
            return false;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // 1- Changement du statut du papier
        $bind = array(
            'DOCSTATUS' => self::STATUS_VISIBLE,
            'FORMAT' => self::FORMAT_NOTICE,
            'DATEMODER' => date('Y-m-d H:i:s')
        );
        if ($db->update(self::TABLE, $bind, 'DOCID = ' . $this->getDocid())) {
            //2- suppression des fichiers
            foreach ($this->getFiles() as $file) {
                $file->deleteFile();
            }
            $db->delete(Hal_Document_File::TABLE, 'DOCID = ' . $this->getDocid());

            // 3- Log de l'action
            Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_NOTICE, $comment);
            // 4- Suppression des fichiers de cache
            $this->deleteCache();
            // 5- Indexation
            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->getDocid()));
            // 5-Envoi du mail au déposant
            if ($sendMail) {
                foreach ($this->getOwner() as $uidmail) {
                    $users = new Hal_User();
                    $users->find($uidmail);
                    $mail = new Hal_Mail();
                    $mail->prepare($users, Hal_Mail::TPL_DOC_ACCEPTED, array('MSG_MODERATEUR' => $comment, 'document' => $this));
                    $mail->writeMail();
                }
            }
            //Suppression du cache CV de chaque Auteur ayant un idhal
            foreach ($this->getAuthorwithIdhal($this->_docid) as $k => $idhal) {
                Hal_Cache::delete('cv.' . $idhal . '.phps', CACHE_CV);
            }

            // Suppression de la littérature citée

            //Remplacement par méthode dans classe dédiée aux références bibliographiques
            //$db->delete(Hal_Document_References::DOC_REFERENCES, 'DOCID = ' . $this->getDocid());
            Hal_Document_References::deleteById($this->getDocid());

            return true;
        }
        return false;
    }

    /**
     * Détermine si le document peut être une notice
     * Cas où ça n'est pas possible : 2e version sans fichier alors que la version 1 a un fichier
     * @see https://wiki.ccsd.cnrs.fr/wikis/ccsd/index.php/Versionnement_des_documents
     * @return bool
     */
    public function canBeNotice()
    {
        if ($this->getVersion() > 1) {
            $v1 = Hal_Document::find(0, $this->getId(), 1, true);
            if ($v1 !== false && $v1->getDefaultFile() !== false && !$this->isNotice()) {
                // Dans le cas d'une notice qui a une version 1 avec fichier
                return false;
            }
        }

        return true;
    }

    /**
     * Refus d'un document
     * @param $uid
     * @param $comment
     * @param bool $sendMail
     * @return bool
     */
    public function refused($uid, $comment, $sendMail = true)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // 1- Changement du statut du papier
        $bind = array(
            'DOCSTATUS' => self::STATUS_DELETED
        );
        if ($db->update(self::TABLE, $bind, 'DOCID = ' . $this->getDocid())) {
            // 2- Log de l'action
            Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_HIDE, $comment);
            // 3- Incrémente le nombre de doc visible
            $sql = $db->select()->from(self::TABLE, 'UID')
                ->where('DOCID = ?', $this->getDocid());
            $uiddoc = $db->fetchOne($sql);
            $db->update("USER", array('NBDOCREF' => new Zend_Db_Expr('NBDOCREF + 1')), 'UID = ' . $uiddoc);
            // 4- Suppression des fichiers de cache
            $this->deleteCache();
            // 5- Suppression de l'index (s'il existe)
            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->getDocid()), 'hal', 'DELETE');
            // 6-Envoi du mail au déposant
            if ($sendMail) {
                foreach ($this->getOwner() as $uidmail) {
                    $users = new Hal_User();
                    $users->find($uidmail);
                    $mail = new Hal_Mail('UTF-8');
                    $mail->prepare($users, Hal_Mail::TPL_DOC_REFUSED, array('MSG_MODERATEUR' => $comment, 'document' => $this));
                    $mail->writeMail();
                }
            }

            return true;
        }
        return false;
    }

    /**
     * Fusion d'un document
     * @param $uid
     * @param $comment
     * @param Hal_Document $replacedoc
     * @param bool $sendMail
     * @return bool
     */
    public function fusion($uid, $comment, $replacedoc, $sendMail = true)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // 1- Changement du statut du papier
        $bind = array(
            'DOCSTATUS' => self::STATUS_MERGED
        );
        if ($db->update(self::TABLE, $bind, 'DOCID = ' . $this->getDocid())) {
            /* Il faut effacer le NNT, sinon cela cree une impossibilite d'ajouter sur un autre DOCID cette identifiant */
            $db->delete(Hal_Document_Meta_Abstract::TABLE_META, 'DOCID = ' . $this->getDocid() . " AND METANAME='nnt'");
            // 2- Log de l'action
            Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_MERGED, "Fusion avec " . $replacedoc -> getId() . ": " . $comment);
            // 3- Suppression des fichiers de cache
            $this->deleteCache();
            // 4- Mise à jour de l'index (s'il existe)
            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->getDocid()), 'hal', 'UPDATE');
            // 5-Envoi du mail au déposant
            if ($sendMail) {
                foreach ($this->getOwner() as $uidmail) {
                    $users = new Hal_User();
                    $users->find($uidmail);
                    $mail = new Hal_Mail('UTF-8');
                    $mail->prepare($users, Hal_Mail::TPL_DOC_FUSION, array('MSG_MODERATEUR' => $comment, 'document' => $this, 'REPLACEID' => $replacedoc->getId(), 'DOC_TITLE'=>$this->getMainTitle(), 'REPLACE_TITLE'=>$replacedoc->getMainTitle()));
                    $mail->writeMail();
                }
            }

            return true;
        }
        return false;
    }

    /**
     * Mise en Status replace de la Version precedente d'un document
     * @param $uid
     * @param $comment
     * @param bool $sendMail
     * @return bool
     */
    public function verspre($uid, $comment, $sendMail = false)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // 1- Changement du statut du papier
        $bind = array(
            'DOCSTATUS' => self::STATUS_REPLACED
        );
        if ($db->update(self::TABLE, $bind, 'DOCID = ' . $this->getDocid())) {
            // 2- Log de l'action
            Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_MODIF, $comment);
            // 3- Suppression des fichiers de cache
            $this->deleteCache();
            // 4- Mise à jour de l'index (s'il existe)
            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->getDocid()), 'hal', 'UPDATE');
            // 5-Envoi du mail au déposant
            if ($sendMail) {
                foreach ($this->getOwner() as $uidmail) {
                    $users = new Hal_User();
                    $users->find($uidmail);
                    $mail = new Hal_Mail('UTF-8');
                    $mail->prepare($users, Hal_Mail::TPL_DOC_REFUSED, array('MSG_MODERATEUR' => $comment, 'document' => $this));
                    $mail->writeMail();
                }
            }

            return true;
        }
        return false;
    }

    /**
     * Mise en modification de la version d'un document:
     *     Chgment de la date + effacement cache + indexation demandee
     * @param $uid
     * @param $comment
     * @param bool $sendMail
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     */
    public function versnew($uid, $comment, $sendMail = false)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // 1- Changement de la datemodif du papier
        $bind = array(
            'DATEMODIF' => date('Y-m-d H:i:s')
        );
        if ($db->update(self::TABLE, $bind, 'DOCID = ' . $this->getDocid())) {
            // 2- Log de l'action
            Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_VERSION, $comment);
            // 3- Suppression des fichiers de cache
            $this->deleteCache();
            // 4- Mise à jour de l'index (s'il existe)
            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->getDocid()), 'hal', 'UPDATE');
            // 5-Envoi du mail au déposant
            if ($sendMail) {
                foreach ($this->getOwner() as $uidmail) {
                    $users = new Hal_User();
                    $users->find($uidmail);
                    $mail = new Hal_Mail('UTF-8');
                    $mail->prepare($users, Hal_Mail::TPL_DOC_REFUSED, array('MSG_MODERATEUR' => $comment, 'document' => $this));
                    $mail->writeMail();
                }
            }

            return true;
        }
        return false;
    }


    /**
     * Suppression d'un document
     * @param $uid
     * @param $comment
     * @param bool $sendMail
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     */
    public function delete($uid = null, $comment = '', $sendMail = true, $reindexdirect = false)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        //1- Suppression des données en base
        if (count($this->getVersionsFromId($this->_identifiant)) == 1) {
            $db->delete(Hal_Document_Owner::TABLE, 'IDENTIFIANT = "' . $this->_identifiant . '"');
            $db->delete(Hal_Document_Owner::TABLE_CLAIM, 'IDENTIFIANT = "' . $this->_identifiant . '"');
        }
        $db->delete(self::TABLE, 'DOCID = ' . $this->_docid);
        $db->delete(Hal_Document_Metadatas::TABLE_META, 'DOCID = ' . $this->_docid);
        $db->delete('DOC_ARCHIVE', 'DOCID = ' . $this->_docid);
        $db->query('DELETE FROM autlab USING ' . Hal_Document_Author::TABLE_DOCAUTHSTRUCT . ' autlab, ' . Hal_Document_Author::TABLE . ' aut WHERE autlab.DOCAUTHID = aut.DOCAUTHID AND aut.DOCID = ' . $this->_docid);
        $db->delete(Hal_Document_Author::TABLE, 'DOCID = ' . $this->_docid);
        $db->delete(Hal_Document_Author::TABLE_DOC_ID, 'DOCID = ' . $this->_docid);
        $db->delete('DOC_COMMENT', 'DOCID = ' . $this->_docid);
        $db->delete(Hal_Document_File::TABLE, 'DOCID = ' . $this->_docid);
        $db->delete(Hal_Document_Meta_Identifier::TABLE_COPY, 'DOCID = ' . $this->_docid);

        $db->delete(self::TABLE_RELATED, 'DOCID = ' . $this->_docid);
        //$db->delete('DOC_STAT_COUNTER', 'DOCID = ' . $this->_docid);
        Hal_Stats::delete($this->_docid);
        $db->delete(Hal_Document_Collection::TABLE, 'DOCID = ' . $this->_docid);
        $db->delete('DOC_DELETED', 'DOCID = ' . $this->_docid);
        //2- Ajout dans DOC_DELETED
        $bind = array(
            'DOCID' => $this->_docid,
            'IDENTIFIANT' => $this->getId(),
            'OAISET' => serialize($this->getOaiSet()),
            'DATEDELETED' => date('Y-m-d H:i:s')
        );
        $db->insert('DOC_DELETED', $bind);
        //3- Log
        Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_DELETE, $comment);
        // 3- Suppression des fichiers
        Ccsd_Tools::rrmdir($this->getRacineDoc());
        $this->deleteCache();
        // 4- Suppression de l'index (s'il existe)
        if ($reindexdirect) {
            $this->reIndexDirect(Ccsd_Search_Solr_Indexer::O_DELETE);
        } else {
            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->getDocid()), 'hal', 'DELETE');
        }
        // 5-Envoi du mail au déposant
        //if ($sendMail) {
        //    foreach($this->getOwner() as $uidmail){
        //        $users = new Hal_User();
        //        $users->find($uidmail);
        //        $mail = new Hal_Mail();
        //        $mail->prepare($users, Hal_Mail::TPL_DOC_DELETED, array('MSG_MODERATEUR' => $comment, 'document' => $this));
        //        $mail->writeMail();
        //    }
        //}

        return true;
    }

    /**
     * Mise en validation scientifique
     */
    public function validate($uid, $comment)
    {


        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // 1- Changement du statut du papier
        $bind = array(
            'DOCSTATUS' => self::STATUS_VALIDATE
        );

        $res = $db->update(self::TABLE, $bind, 'DOCID = ' . $this->getDocid());

        // 3- Incrémente le nombre de doc visible
        $sql = $db->select()->from(self::TABLE, 'UID')
            ->where('DOCID = ?', $this->getDocid());
        $uiddoc = $db->fetchOne($sql);
        $db->update("USER", array('NBDOCSCI' => new Zend_Db_Expr('NBDOCSCI + 1')), 'UID = ' . $uiddoc);

        // 2- Log de l'action
        Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_VALIDATE, htmlspecialchars($comment));

        // 3- Suppression des fichiers de cache
        $this->deleteCache();

        return $res;

    }

    /**
     * Fonction permettant de changer l'instance de dépôt d'un document
     * Permet de remettre dans hal un document n'ayant rien a faire dans un portail institutionnel
     * @param int    $uid     : identifiant de l'utilisateur qui fait la demande de modification
     * @param string $comment : commmentaire pour le log
     * @param int    $sid     : identifant du portail
     * @return int
     * @throws Zend_Db_Adapter_Exception
     */
    public function changeInstance($uid, $comment, $sid = 1)
    {
        if (! Hal_Moderation::canTransfertHAL($this)) {
            return 0;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // Recherche de l'ancien identifiant
        $sOldIdent = $this->getId();
        $iVersion = $this->getVersion();

        // Constitution prefixe du nouvel identifiant
        $sNewPrefix = Hal_Settings::getDocumentPrefix($sid, $this->getTypDoc());

        //$aVersions = $this->getDocVersions();

        //1- Si même identifiant, changement d'instance
        if (strncmp($sNewPrefix, $sOldIdent, strlen($sNewPrefix)) == 0) {
            $bind = [
                'SID' => $sid
            ];
            $res = $db->update(self::TABLE, $bind, 'IDENTIFIANT = "' . $sOldIdent . '"');
            // sinon changement d'identifiant du document + instance
        } else {
            // Constitution du nouvel identifiant
            $sNewIdent = $this->generateId($this->getDocid(), $sNewPrefix);
            $bind = [
                'IDENTIFIANT' => $sNewIdent,
                'SID' => $sid
            ];
            $res = $db->update(self::TABLE, $bind, 'IDENTIFIANT = "' . $sOldIdent . '"');
            $this->setID($sNewIdent, $iVersion, false);

            // ajout du lien ancien vers nouvel identifiant
            $this->addSameAs($sOldIdent);

            // modification de l'identifiant du document dans la table DOC_OWNER
            $oDocOwner = new Hal_Document_Owner();
            $oDocOwner->updateIdentifiant($sOldIdent, $sNewIdent);

            // modification de l'identifiant du document dans la table DOC_OWNER_CLAIM
            $oDocOwner->updateClaimIdentifiant($sOldIdent, $sNewIdent);

            // modification de l'identifiant du document dans la table DOC_RELATED
            $this->updateRelatedIdentifiant($sOldIdent, $sNewIdent);

            // modification de l'identifiant du document dans la table DOC_SAMEAS
            $this->updateSameAsIdentifiant($sOldIdent, $sNewIdent);

            // modification de l'identifiant du document dans la table USER_LIBRARY_DOC
            $oUserLibrary = new Hal_User_Library();
            $oUserLibrary->updateIdentifiant($sOldIdent, $sNewIdent);
        }

        // 2- Log de l'action
        Hal_Document_Logger::log($this->getDocid(), $uid, Hal_Document_Logger::ACTION_MOVED, htmlspecialchars($comment));

        // 3- Suppression des fichiers de cache
        $this->deleteCache();

        // 4- Mise à jour de l'index (s'il existe)
        Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->getDocid()), 'hal', 'UPDATE');

        return $res;
    }

    /**
     * Création des imagettes du dépôt
     * imagette pour le fichier principal et pour les annexes de type image
     * @throws Zend_Db_Adapter_Exception
     *
     * THUMB_KEY est definit dans le pwd.json
     */
    public function createImagettes()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(Hal_Document_File::TABLE, ['FILEID', 'FILENAME'])
            ->where('DOCID = ?', $this->getDocid())
            ->where('(FILETYPE = "file" AND MAIN = "1") OR (FILETYPE = "annex" AND TYPEANNEX = "figure")')
            ->where('(IMAGETTE = 0 OR IMAGETTE IS NULL)');

        foreach($db->fetchAll($sql) as $row) {
            $src = $this->getRacineDoc() . $row['FILENAME'];

            $thumbid = Ccsd_Thumb::add(THUMB_KEY, HAL_URL . '/file/index/docid/' . $this->getDocid() . '/fileid/' . $row['FILEID'], json_encode(['src' => $src]));
            if ($thumbid !== false) {
                $db->update(Hal_Document_File::TABLE, array('IMAGETTE' => $thumbid), 'FILEID = ' . $row['FILEID']);
            }
        }
    }

    /**
     *
     * @return int $_sid
     */
    public function getSid()
    {
        if ($this->_sid == 0) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Récupération du SID null !");
            $this->_sid = 1;
        }

        return $this->_sid;
    }

    /**
     *
     * @return string $_instance
     */
    public function getInstance()
    {
        return $this->_instance;
    }

    /**
     *
     * @param number $_sid
     * @return Hal_Document
     */
    public function setSid($_sid)
    {
        if ($_sid == 0) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Ecriture d'un SID null !");
            $_sid = 1;
        }

        $this->_sid = $_sid;
        return $this;
    }

    /**
     * Initialise le type de dépôt
     * @param $typeSubmit
     * @return Hal_Document
     */
    public function setTypeSubmit($typeSubmit)
    {
        $this->_typeSubmit = $typeSubmit;
        return $this;
    }

    /**
     * Retourne le type de dépôt (pour l'enregsitrement)
     * @return string
     */
    public function getTypeSubmit()
    {
        return $this->_typeSubmit;
    }

    /**
     * Retourne la liste des versions du document
     * @return array
     */
    public function getDocVersions()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        foreach ($this->_versions as $version) {
            if (!is_array($version)) { // Ancien cache
                $sql = $db->select()
                    ->from(self::TABLE)
                    ->where('IDENTIFIANT = ?', $this->_identifiant)
                    ->order('DATESUBMIT ASC');
                foreach ($db->fetchAll($sql) as $row) {
                    $this->_versions[$row['VERSION']] = $row;
                }
            }
            break; // on traite juste sur un seul element de tableau!!!
        }
        return $this->_versions;
    }


    /**
     * Retourne la liste des tampons du document
     * @return Hal_Site_Collection[]
     */
    public function getCollections()
    {
        return $this->_collections;
    }

    /**
     * Ecriture des collections
     * @param Hal_Site_Collection[]
     */
    public function setCollections($collections)
    {
        $this->_collections = $collections;
    }

    /**
     * Retourne les identifiants des collections de l'article
     * @return int[]
     */
    public function getCollectionIds()
    {
        $res = array();
        foreach ($this->getCollections() as $collection) {
            $res[] = $collection->getSid();
        }
        return $res;
    }

    /**
     *
     * @param string $_instance
     * @return Hal_Document
     */
    public function setInstance($_instance)
    {
        $this->_instance = $_instance;
        return $this;
    }

    /**
     * @return string $_releasedDate
     */
    public function getReleasedDate()
    {
        return $this->_releasedDate;
    }

    /**
     * @param string $_releasedDate
     * @return Hal_Document
     */
    public function setReleasedDate($_releasedDate)
    {
        $this->_releasedDate = $_releasedDate;
        return $this;
    }

    /**
     * @param $msg
     * @return $this
     */
    public function setModerationMsg($msg)
    {
        // On filtre le message pour qu'il n'y ait pas de contenu HTML.
        $this->_moderationMsg = $msg;
        return $this;
    }

    /**
     * Retourne la liste des modérateurs devant relire ce document
     * Pour le moment la méthode ne se base que sur le critère DOMAIN
     * A modifier si le critère du modérateur est différent !!
     */
    public function getModerators()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()
            ->distinct()
            ->from(Hal_User::TABLE_ROLE, 'UID')
            ->where('RIGHTID = ?', Hal_Acl::ROLE_MODERATEUR)
            ->where('SID IN (?)', [0, (int)$this->getSid()])
            ->where('VALUE IN (?)', ['', 'typdoc:' . $this->_typdoc, 'domain:' . $this->getMainDomain()]);

        return $db->fetchCol($sql);
    }

    /**
     * @return array
     */
    public function getNbConsult()
    {
        $cacheFile = $this->_docid . '.metrics';
        $cachePath = $this->getRacineCache();
        if (Hal_Cache::exist($cacheFile, 86400, $cachePath)) {
            $res = unserialize(Hal_Cache::get($cacheFile, $cachePath));
        } else {
            //$db = Zend_Db_Table_Abstract::getDefaultAdapter();

            // TODO : migrer vers Hal_Stats
            $db = Hal_Db_Adapter_Stats::getAdapter(APPLICATION_ENV);
            $sql = $db->select()
                ->from(['c' => 'DOC_STAT_COUNTER'], ['CONSULT', 'SUM(COUNTER)'])
                ->where('c.DOCID = ?', $this->getDocid())
                ->group('c.CONSULT');
            $res = $db->fetchPairs($sql);
            if ($res) {
                Hal_Cache::save($cacheFile, serialize($res), $cachePath);
            }
        }
        return $res;
    }

    /**
     * Ajout d'une entrée dans DOC_SAMEAS
     * @param $docDeletedId
     */
    public function addSameAs($docDeletedId)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        try {
            $bind = array(
                'DELETEDID' => $docDeletedId,
                'CURRENTID' => $this->getId(),
            );
            $db->insert(self::TABLE_DELETED, $bind);
        } catch (Exception $e) {
            // Todo: Must do something???
        }
    }

    /**
     * Change les tampons des documents fusionnés
     * @param Hal_Document $docDeleted
     */
    public function changeTampon($docDeleted)
    {
        try {
            foreach ($docDeleted->getCollectionIds() as $sid){
                $site = Hal_Site::loadSiteFromId($sid);
                Hal_Document_Collection::add($this->getDocid(),$site);
                Hal_Document_Collection::del($docDeleted->getDocid(),$site);
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Transfert d'un propriétaire
     * @param $docPrecUID
     */
    public function addProprio($docPrecUID)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        try {
            // a- Suppression des anciens auteurs du document
            $db->delete(self::TABLE_OWNER, array('IDENTIFIANT = ?' => $this->getId(), 'UID = ?' => $docPrecUID));

            $bind = array(
                'IDENTIFIANT' => $this->getId(),
                'UID' => $docPrecUID,
            );
            $db->insert(self::TABLE_OWNER, $bind);
        } catch (Exception $e) {
        }
    }

    /**
     * Change l'identifiant d'un document
     * @param int $docNewId
     * @param Hal_Document $docPrecId
     * @param int $v version
     */
    public function changeId($docPrecId, $docNewId, $v)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        try {
            $bind = array(
                'IDENTIFIANT' => $docPrecId->getId(),
                'VERSION' => $v
            );
            $db->update(self::TABLE, $bind, 'DOCID = ' . $docNewId);
        } catch (Exception $e) {
        }
    }

    /**
     * Récupère les metasources d'une métadonnée
     * @param $meta
     * @param $metaclass
     * @param $metamethod
     * @param $metalabel
     * @param $metasource
     * @return string
     */
    public function getMetasource($meta, $metaclass, $metamethod, $metalabel)
    {
        if ($meta != Ccsd_Externdoc::META_LANG && $meta != 'country') {
            $getmeta = $metaclass::$metamethod($meta);
        } else {
            $getmeta = $metaclass::$metamethod();
        }

        $array = [];

        if ($meta == 'type'){
            foreach ($getmeta as $typdoc) {
                foreach ($typdoc as $i => $val){
                $array[] = array('value' => $i, 'text' => Ccsd_Tools::translate($val));
                }
            }
        } else {
            foreach ($getmeta as $i => $val) {
                    $array[] = array('value' => $i, 'text' => Ccsd_Tools::translate($val));
            }
        }
        return Zend_Json::encode($array);
    }

    /**
     * Ajout d'une métadonnée
     * @param $docid
     * @param $meta
     * @param $value
     */
    public function insertMeta($docid, $meta, $value)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        if (preg_match('/înter_/', $value)) {
            $value = preg_replace('/înter_/', '', $value);
        }
        try {
            $bind = array(
                'DOCID' => $docid,
                'METANAME' => $meta,
                'METAVALUE' => $value
            );
            $db->insert(Hal_Document_Metadatas::TABLE_META, $bind);
        } catch (Exception $e) {
        }
    }

    /**
     * Remettre un document en modération
     * @param int $docid
     * @param int $uid
     * @throws Zend_Db_Adapter_Exception
     * @return void
     */
    static public function moderate($docid, $uid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $document = new Hal_Document($docid,'',0,true);

        if ($document->getStatus() == Hal_Document::STATUS_VISIBLE) {
            $db->update(self::TABLE, array('DOCSTATUS' => self::STATUS_BUFFER), 'DOCID = ' . $docid);

            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid), 'hal', 'DELETE', 'hal', 0);
            self::deleteCaches($docid);

            Hal_Document_Logger::log($docid, $uid, Hal_Document_Logger::ACTION_REMODERATE);

            //Suppression du cache CV de chaque Auteur ayant un idhal
            foreach (Hal_Document::getAuthorwithIdhal($docid) as $k => $idhal) {
                Hal_Cache::delete('cv.' . $idhal . '.phps', CACHE_CV);
            }

        } else {
            throw new Exception( "Erreur: ce n'est pas la derniere version");
        }
    }

    /**
     * Récupération des informations d'un auteur lié aux structures
     * @param int $docid
     * @return array
     */
    static public function getAuthorwithIdhal($docid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $query = 'SELECT DISTINCT refauth.IDHAL FROM `' . Hal_Document_Author::TABLE . '` AS `docauth` INNER JOIN `REF_AUTHOR` AS `refauth` ON refauth.AUTHORID=docauth.AUTHORID WHERE (refauth.IDHAL > 0) AND docauth.DOCID = ' . $docid . ' AND refauth.IDHAL IN (SELECT IDHAL FROM REF_IDHAL_CV)';

        return $db->fetchCol($query);
    }

    /**
     * Retourne la liste des
     * @return array
     */
    public function getUsersToAlert()
    {
        $result = [];

        return $result;
    }

    /**
     * Return le libelle d'un status
     * @param $status : int
     * @param $lang
     * @return string
     */
    public static function statusToString ($status, $lang = null)
    {
        //Formate la chaîne pour la traduction
        $status = 'status_'. $status; //exemple : status_111 => Ancienne version
        return Ccsd_Tools::translate($status, $lang);
    }

    /**
     * Return visible documents ids
     * @param $dateModeration : date
     * @return array of docids
     */
    public static function getVisibleDocIds ($dateModeration)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, 'DOCID')
            ->where('DOCSTATUS = ?', self::STATUS_VISIBLE)
            ->where('DATEMODER >= ?', date('Y-m-d H:i:s', strtotime($dateModeration)));
        return $db->fetchCol($sql);
    }

    /**
     * retourne les identifiants des comptes des auteurs
     * @param bool $forAlert indique si on créé la liste pour un envoi de mail
     * (dans ce cas on vérifie les préférences de dépôt)
     * @return array
     */
    public function getAuthorsUID($forAlert = false)
    {
        $uid = [];

        foreach ($this->getAuthors() as $author){
            /* @var Hal_Document_Author $author */
            if ($author->getIdHal() != 0){
                //Recherche d'un utilisateur à partir de l'idHAL
                $uidTmp = $author->getUidFromIdHal($author->getIdHal());
                if ($uidTmp) {
                    $uid[] = $uidTmp;
                }
            } else if ($author->getEmail() != '') {
                //Recherche d'un utilisateur à partir de son adresse mail
                $refUsers = new Ccsd_User_Models_DbTable_User();
                $res = $refUsers->getUidByEmail($author->getEmail());
                if (!$res) {
                    continue;
                }
                if (count($res) == 1) {
                    $uid[] = $res[0];
                } else {
                    $uidTmp = Hal_User::getLatestConnectedUidFromArray($res);
                    if ($uidTmp) {
                        $uid[] = $uidTmp;
                    }
                }
            }
        }

        if ($forAlert) {
            $uid = Hal_User::filterUsersForAlert($uid, Hal_Acl::ROLE_MEMBER);
        }

        return $uid;
    }

    /**
     * Retourne la liste des Référents Structure
     * @param bool $forAlert
     * @return array uid => structid
     */
    public function getAdminStructUID($forAlert = false)
    {
        $structIds = $this->getStructids();
        if ( count($structIds) == 0) {
            return [];
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql =  $db->select()->distinct()
            ->from(Hal_User::TABLE_ROLE, ['UID', 'VALUE'])
            ->where('RIGHTID = ?', Hal_Acl::ROLE_ADMINSTRUCT)
            ->where('VALUE IN (?)', $structIds);
        $res = $db->fetchAssoc($sql);

        $uids = array_keys($res);

        if ($forAlert) {
            $uids = Hal_User::filterUsersForAlert(array_keys($res), Hal_Acl::ROLE_ADMINSTRUCT, $structIds);
        }

        $toreturn = [];

        // On formate le résultat de sortie uid => structid
        foreach ($uids as $uid) {
            $toreturn[$uid] = $res[$uid]['VALUE'];
        }

        return $toreturn;
    }

    /**
     * Retourne la liste des administrateurs du portail de dépôt
     * @param bool $forAlert indique si on créé la liste pour un envoi de mail
     * (dans ce cas on vérifie les préférences de dépôt)
     * @return array liste d'identifiant de compte utilisateur
     */
    public function getAdminUID($forAlert = false)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql =  $db->select()->distinct()
            ->from(Hal_User::TABLE_ROLE, 'UID')
            ->where('RIGHTID = ?', Hal_Acl::ROLE_ADMIN)
            ->where('SID = ?', $this->getSid());
        $uid = $db->fetchCol($sql);

        if ($forAlert) {
            $uid = Hal_User::filterUsersForAlert($uid, Hal_Acl::ROLE_ADMIN);
        }
        return $uid;
    }


    /**
     * Retourne les Ids Extérieurs
     * @return array assoc CODE => ID
     * TODO: A transferer dans Hal_Document_Meta_Identifier ou un Identifier.php
     */
    public function getHasCopy()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql =  $db->select()
            ->from(Hal_Document_Meta_Identifier::TABLE_COPY, array('CODE','LOCALID'))
            ->where("DOCID = ?", $this->_docid);
        $row = $db->fetchAll($sql);
        $array =[];
        foreach ($row as $i => $assoc) {
            $array[$assoc['CODE']] = $assoc['LOCALID'];
        }
        return $array;
    }

    /**
     * Le document est-il visible avec Solr
     * La réponse dépend des filtres du portail/collection
     * @return bool
     */
    public function isVisibleWithSolr()
    {
        try {
            $res = unserialize(Hal_Tools::solrCurl('q=halId_s:' . $this->getId(false) . '&rows=0&omitHeader=true&wt=phps', 'hal', 'select', true, true));
        } catch (Exception $e) {
            return false;
        }

        return $res['response']['numFound'] > 0;
    }

    /**
     * Le document est-il indexé avec Solr
     * La réponse NE dépend PAS des filtres du portail/collection
     * Permet de savoir si le document est indexé avec Solr
     * @return bool
     */
    public function isIndexedWithSolr()
    {
        try {
            $res = unserialize(Ccsd_Tools::solrCurl('q=docid:' . $this->getDocid() . '&rows=0&omitHeader=true&wt=phps', 'hal', 'select'));
        } catch (Exception $e) {
            return false;
        }

        return $res['response']['numFound'] == 1;
    }

    /**
     * Identifiant imagette
     * @return int
     */
    public function getThumbid()
    {
        return $this->_thumbid;
    }

    /**
     * Identifiant imagette
     * @param int $thumbid
     */
    public function setThumbid($thumbid)
    {
        $this->_thumbid = (int) $thumbid;
    }

    /**
     * Modifie un identifiant pour toutes les ressources liées de l'article en base
     * @param string $sOldIdent : ancien identifiant du document
     * @param string $sNewIdent : nouvel identifiant du document
     * @return boolean
     * TODO: A mettre dans hal/Document/Relation
     */
    public function updateRelatedIdentifiant($sOldIdent, $sNewIdent)
    {
        if (!isset($sOldIdent) || !isset($sNewIdent) || !is_string($sOldIdent) || !is_string($sNewIdent)) {
            return false;
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $bind = [
            'IDENTIFIANT' => $sNewIdent,
        ];
        try {
            return $db->update(self::TABLE_RELATED, $bind, ['IDENTIFIANT = ?' => $sOldIdent]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Modifie un identifiant pour toutes les correspondances id deleted et id online en base
     * @param string $sOldIdent : ancien identifiant du document
     * @param string $sNewIdent : nouvel identifiant du document
     * @return boolean
     */
    public function updateSameAsIdentifiant($sOldIdent, $sNewIdent)
    {
        if (!isset($sOldIdent) || !isset($sNewIdent) || !is_string($sOldIdent) || !is_string($sNewIdent)) {
            return false;
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $bind = [
            'CURRENTID' => $sNewIdent,
        ];
        try {
            return $db->update(self::TABLE_DELETED, $bind, ['CURRENTID = ?' => $sOldIdent]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @param string $lang
     * @return string
     */
    public function getDoctypeIntro($lang = null) {

        $typeDoc = $this->getTypDoc();
        $intro = 'intro' . $typeDoc;

        $trad = Ccsd_Tools::translate($intro, $lang);
        if ($trad != $intro) {
            /** une traduction existe, on l'utilise, sinon, c'est que ce n'est pas défini, on rends vide */
            return $trad;
        }
        return "";
    }

    /**
     * Tant qu'on a des row de table document, passer par ces fonctions pour obtenir les valeurs
     * @param $row
     * @return string
     * @deprecated
     */
    static public function getDateVersionFromDocRow($row) {
        return $row['DATESUBMIT'];
    }


    /**
     * When replace, we meus suppress somr meat
     * Eg: SWH external Id is just for one version, so, don't copy from one version to another
     * TODO: PUt this into a SOFTWARE.php
     */
    public function resetSomeMetaForTypedocWhenReplace() {
        /** @var Hal_Document_Meta_Identifier $meta */
        $meta = $this->getMetaObj('identifier');
        if ($meta) {
            $meta->removeGroup(Hal_Transfert_SoftwareHeritage::$IDCODE);

        }
    }
}
