<?php

require_once(__DIR__.'/../Acl.php');
require_once(__DIR__.'/../User.php');
require_once(__DIR__.'/../Site.php');
require_once(__DIR__.'/../Document/Collection.php');
require_once(__DIR__.'/Settings/Collection.php');
require_once(__DIR__.'/../Website/Header.php');

/**
 * Class Hal_Site_Collection
 */
class Hal_Site_Collection extends Hal_Site
{
    const TABLE_PARENT = 'SITE_PARENT';
    const MODULE       = 'collection';
    const DEFAULT_CONFIG_PATH = DEFAULT_CONFIG_ROOT . self::MODULE;
    const CACHE_MODULE_PATH   = CACHE_ROOT . '/'. APPLICATION_ENV . '/' . self::MODULE;
    const DEFAULT_CACHE_PATH  = self::CACHE_MODULE_PATH . '/' . SPACE_DEFAULT;
    const MODULE_PATH         = SPACE_DATA . '/' . self::MODULE;

    const TABLE_HIDDEN_DOC = 'COLLECTION_DOC_HIDDEN';

    /**
     * @var string
     */
    protected $_type = self::TYPE_COLLECTION;

    /**
     * @var Hal_Site_Settings_Collection
     */
    protected $_settings = null;

    /**
     * Parents de la collection
     * @var array
     */
    protected $_parents = array();

    /**
     * Permet un fonctionnement lazy de chargement des parents
     * @var bool
     */
    protected $_parentsLoaded = false;

    /**
     * UID des utilisateurs tamponneurs de la collection
     * @var array
     */
    protected $_tamponneurs = array();

    /**
     * Permet un fonctionnement lazy de chargement des tamponneurs
     * @var bool
     */
    protected $_tamponneursLoaded = false;

    /**
     * @var Hal_Site_portail
     */
    protected $_associatedPortail = null;
    protected $_associatedPortailLoaded = false;

    /**
     * Hal_Site_Collection constructor.
     * @param $infos
     * @param bool $full
     */
    public function __construct($infos, $full = false)
    {
        // todo : tester la valider des infos (genre type=COLLECTION) sinon renvoyer une exception
        $this->setParams($infos, $full);
        $this -> _prefixUrl = '/' . $this -> _name;
        $this -> _spaceUrl  = $this -> _prefixUrl . "/public/";
        parent::__construct($infos, $full);
    }

    /**
     * @param array $params
     * @param bool $full
     * @uses set

     */
    public function setParams($params, $full = false)
    {
        parent::setParams($params, $full);

        if ($full) {

            // Chargement des tamponneurs
            $this->setTamponneurs(Ccsd_Tools::ifsetor($params['tamponneurs'], array()));

            // Chargement des parents
            $this->setParentsIds(Ccsd_Tools::ifsetor($params['parentids'], array()));

        }
    }

    /**
     * @deprecated
     * Initialisation de l'object avec un tableau associatif
     */
    public function set()
    {
        Ccsd_Tools::panicMsg(__FILE__, __LINE__, "ATTENTION CETTE FONCTION EST OBSOLETE ET NE DEVRAIT PAR CONSEQUENT PAS ETRE APPELE !!");
    }

    /**
     * Création du dossier data lié à la collection s'il n'existe pas déjà
     */
    public function createFilePaths()
    {
        $this->getSettingsObj()->createFilePaths();
    }

    /**
     * Chargement des parents depuis la BDD
     * Le tableau resultant a des clefs numerique correspondant aux SID
     */
    public function loadParents()
    {
        if (!$this->_parentsLoaded) {

            // Reset parents
            $this->_parents = [];

            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $sql = $db->select()->from(array("tparent" => self::TABLE_PARENT), 'SPARENT')->join(array("tsite" => HAL_SITE::TABLE), "tparent.SPARENT=tsite.SID")->where('tparent.SID = ?', $this->getSid());

            foreach ($db->fetchAll($sql) as $siteinfo) {

                if ($siteinfo["TYPE"] != self::TYPE_COLLECTION) {
                    Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Try to load a non-collection-typed site as collection parent");
                } else {
                    $coll = new Hal_Site_Collection($siteinfo);
                    $this->_parents[$coll -> getSid()] = $coll;
                }
            }

            $this->_parentsLoaded = true;
        }
    }

    /**
     * Chargement des settings depuis la BDD
     */
    public function loadSettings()
    {
        if (!$this->_settingsLoaded) {
            $this->_settings = Hal_Site_Settings_Collection::loadFromSite($this);
            $this->_settingsLoaded = true;
        }
    }

    /**
     * Chargement des tamponneurs depuis la BDD
     */
    public function loadTamponneurs()
    {
        if (!$this->_tamponneursLoaded) {
            $this->_tamponneurs = Hal_User::getSiteTamponneurs($this);
            $this->_tamponneursLoaded = true;
        }
    }

    /**
     * Tamponnage des documents répondants au critère de tamponnage
     * @throws Exception
     */
    public function tamponnate()
    {
        if ($this->getCritere() == '') {
            return;
        }

        $query = "q=*&fq=status_i:11&fq=" . urlencode(self::getFullCritere($this->getSid()));
        $query .= "&fq=NOT(collId_i:" . $this->getSid() . ")";
        $query .= "&rows=1000000&wt=phps&fl=docid&omitHeader=true";
        $res = unserialize(Ccsd_Tools::solrCurl($query));
        if (isset($res['response']['numFound']) && isset($res['response']['docs'])) {
            // BM un echo ici??? On est dans une vue? dans un script oblogatoirement???
            echo count($res['response']['docs']);
            foreach ($res['response']['docs'] as $d) {
                Hal_Document_Collection::add($d['docid'], $this);
            }
        }
    }

    /**
     * @return bool|mixed
     * @throws Zend_Db_Adapter_Exception
     */
    public function save() {

        // Sauvegarde du site
        $res = parent::save();

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        //Collections supérieures
        if ($this->getSid() != 0) {
            $db->delete(self::TABLE_PARENT, self::SID.' = ' . $this->getSid());
        }

        foreach($this->getParents() as $parent) {
            if (Hal_Site_Collection::isCreatingCycle($this->getSid(), $parent->getSid())) {
                $res = false;
            } else {
                $bind = array(
                    self::SID     =>  $this->getSid(),
                    'SPARENT' =>  $parent->getSid(),
                );
                try{
                    $res = $res && $db->insert(self::TABLE_PARENT, $bind);
                } catch(Exception $e) {
                //on fait rien : cas de l'ajout de 2X la meme collection sup
                }
            }
        }

        //Sauvegarde des settings
        $res = $res && $this->getSettingsObj()->save();

        // todo : ne faudrait-il pas sauvegarder ce qui est lié aux WEBSITE ?


        $halUser = new Hal_User;
        //Suppression des anciens rôles tamponneur
        // todo : virer cette suppression directe dans la table USER !!
        $db->delete(Hal_User::TABLE_ROLE, 'RIGHTID = "' . Hal_Acl::ROLE_TAMPONNEUR . '" AND '.self::SID.' =' . $this->getSid());
        //Tamponneurs
        foreach($this->getTamponneurs() as $uid)
        {
            $halUser->setUid($uid);
            $halUser->addRole(Hal_Acl::ROLE_TAMPONNEUR, $this->getSid());
        }

        return $res;
    }

    /**
     * Avec un identifiant de l'objet ou avec un objet lui meme, retourne l'objet
     * Permet de s'affranchir au niveau de l'appellant de transformer en objet quand on a un id.
     * Le but est d'appele systematiquement avec un objet.
     * @param int|string
     * @return Hal_Site
     **/
    function id2obj($ident) {
        if (is_numeric($ident)) {
            return Hal_Site::loadSiteFromId($ident);
        } elseif (is_string($ident)) {
            $coll = Hal_Site::exist($ident, self::TYPE_COLLECTION, true);
            $coll -> setShortname($ident);
            return $coll;
        } else {
            return $ident;
        }
    }

    /** 
     * Transforme un objet en tableau de bind pour Mysql 
     **/
    function objAsDbArray() {
        return [self::SID => $this -> getSid(),
                self::TYPE => $this -> getType(),
                self::SHORTNAME => $this -> getShortname(),
                self::URL => $this -> getUrl(),
                self::FULLNAME => $this -> getFullname(),
                self::CATEGORY => $this -> getCategory(),
                self::DATE_CREATION => $this -> getCreationDate(),
                self::CONTACT => $this -> getContact(),
                self::IMAGETTE => $this -> getImagette()
        ];
    }

    /**
     * @return array
     */
    function obj2settings_row() {
        return $this->_settings->getAsDbArray();
    }

    /**
     * @return int
     */
    public function getSid()
    {
        return $this->_sid;
    }

    /**
     * @deprecated : utiliser getShortname
     * @return string
     */
    public function getCode()
    {
    	return $this->getShortname();
    }

    /**
     * @return string
     */
    public function getShortname()
    {
        return Ccsd_Tools_String::stripCtrlChars($this->_site);
    }

    /**
     * @param $name
     */
    public function setShortname($name)
    {
        $this->_site = $name;
    }

    /**
     * @deprecated : utiliser getShortname()
     * @return string
     */
    public function getSite()
    {
        return $this->getShortname();
    }

    /**
     * @deprecated : utiliser setSHortname
     * @param $code
     */
    public function setCode($code)
    {
        $this->setShortname($code);
    }

    /**
     * @deprecated : ATTENTION getName du parent renvoit le fullname !!!! Ici il faut utiliser getShortname
     * @return string
     */
    public function getName()
    {
    	return $this->getShortname() ;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
    	return $this->_url;
    }
    /**
     * @param string $site
     * @return Hal_Site
     */
    public function setSite($site)
    {
        $site = strtoupper($site);
        return parent::setSite($site);
    }

    /**
     * @return string
     */
    public function getPrivilegeUserRight()
    {
        return Hal_Acl::ROLE_TAMPONNEUR;
    }
    /**
     * @param Hal_Site_Collection[]|Hal_Site_Collection $siteArray
     * @return $this
     */
    public function addParents($siteArray)
    {
        if ($siteArray instanceof Hal_Site_Collection) {
            $siteArray = [ $siteArray ];
        }

        if (!is_array($siteArray)) {
            Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Site_Collection.setParentsIds called with non array: $siteArray");
            return $this;
        }

        foreach ( $siteArray as $parent ) {
            $this->_parents[] = $parent;
        }

        $this->_parentsLoaded = true;
        return $this;
    }

    /**
     * @param Hal_Site_Collection[] $collArray
     * @return $this
     */
    public function setParents($collArray)
    {
        $this->_parents = $collArray;
        $this->_parentsLoaded = true;
        return $this;
    }

    /**
     *
     * @see addParentsId
     * @param int[] $sidArray
     * @return Hal_Site_Collection
     */
    public function setParentsIds($sidArray)
    {
        // Reset parents
        $this->_parents = [];
        $this->_parentsLoaded = true;
        $this->addParentsId($sidArray);

        return $this;
    }
     /**
     * @param $sidArray
     * @return Hal_Site_Collection
     */
    public function addParentsId($sidArray)
    {
        $this -> loadParents(); // Before adding parents, assume that parents are loaded
        if (!is_array($sidArray)) {
            Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Site_Collection.setParentsIds called with non array");
            return $this;
        }

        foreach ( $sidArray as $parentid ) {
            $parent = self::loadSiteFromId($parentid);
            if ($parent === null) {
                Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Collection " . $this->getSid() . "refer to non existant $parentid");
                continue;
            }

            // BM: Doit on appeler addParent qui controle les boucles 
            $this->_parents[] = $parent;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getParents()
    {
        $this->loadParents();
    	return $this->_parents;
    }

    /**
     * @param $uidArray
     * @return $this
     */
    public function setTamponneurs($uidArray)
    {
        $this->_tamponneurs = $uidArray;
        $this->_tamponneursLoaded = true;
        return $this;
    }

    /**
     * @return array
     */
    public function getTamponneurs()
    {
        $this->loadTamponneurs();
        return $this->_tamponneurs;
    }

    /**
     * @param $settings
     */
    public function setSettings($settings)
    {
        if ($settings instanceof Hal_Site_Settings_Collection) {
            $this->_settings = $settings;
        } else {
            $this->_settings = new Hal_Site_Settings_Collection($this, $settings);
        }

        $this->_settingsLoaded = true;
    }

    /**
     * @param bool $populate
     * @return Ccsd_Form|null
     * @throws Zend_Config_Exception
     */
    public function getForm($populate = false)
    {
    	$form = self::getDefaultForm();
    	if ($populate) {
            $data = $this->toArray();

            $data = array_merge($data, $this->getSettingsObj()->toAssocArray());

            // On vire les langues => todo : comprendre pourquoi. Est-ce que languages est vraiment sensé être dans les settings_collection ?
            unset($data['languages']);

            $tmp = array();
            foreach($this->getParents() as $parent) {
                if ($parent == null) {
                    // TODO: pas normal mais ca arrive
                    continue;
                }
                $tmp[] = $parent->getSid();
            }
            $data['parents'] = implode(',', $tmp);
            $data['tamponneur'] = implode(',', $this->getTamponneurs());
            $form->populate($data);
        }

        return $form;
    }

    /**
     * @return Ccsd_Form
     * @throws Zend_Config_Exception
     */
    static public function getDefaultForm()
    {
        $config = new Zend_Config_Ini(__DIR__ . '/Form/config.ini');
        $form = new Ccsd_Form();
        $form->setConfig($config);
        return $form;
    }

    /**
     * @param int $uid
     * @return bool
     */
    public function isTamponneur($uid)
    {
        return in_array($uid, $this->getTamponneurs());
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $res = array(
            'sid'   =>  $this->getSid(),
            'name'   =>  $this->getFullname(),
            'code'   =>  $this->getShortname(),
            'category'   =>  $this->_category
        );
        foreach($this->getParents() as $parent) {
            if ($parent == null) {
                // TODO: Pas normal mais ca arrive
                continue;
            }
            $res['parents'][] = $parent->toArray();
        }
        return $res;
    }


    /**
     * Retourne la miniature de la collection
     * @param string $size
     * @return string
     */
    public function getThumb($size='small')
    {
    	return Ccsd_Thumb::THUMB_URL.$this->_imagette."/".$size;
    }

    /**
     * @return string
     */
    public function getMode()
    {
        return $this->getSetting('mode');
    }

    /**
     * @return string
     */
    public function getCritere()
    {
    	return $this->getSetting('critere');
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getNbDocTamponned()
    {
        $data=[];
        $query = "q=collId_i:" . $this->getSid() . "&start=0&rows=0&wt=phps&facet=true&facet.field=submitType_s&omitHeader=true";
        $res = Ccsd_Tools::solrCurl($query);
        $res = unserialize($res);
        if (isset($res['facet_counts']['facet_fields']['submitType_s'])) {
            $data = $res['facet_counts']['facet_fields']['submitType_s'];
        }
        return $data;
    }

    /**
     * @param int $uid
     * @return array
     * @throws Exception
     */
    public function getDocumentsToTamponnate($uid = 0)
    {
        $solrFilter = self::getFullCritere($this->getSid()). " AND -collId_i:" . $this->getSid();
        if ($uid != 0) {
            //On retire des résultats solr les documents masqués par les gestionnaires
            $docids = $this->getDocumentToHide();
            if (count($docids)) {
                if (count($docids) < 1000) {
                    //todo limit solr
                    $solrFilter .= ' AND -docid:(' . implode(' OR ', $docids) . ')';
                }
            }
        }
        $query = "q=*:*&fq=" . urlencode($solrFilter) . "&fq=status_i:11&rows=100&fl=citationFull_s,submitType_s,docid&wt=phps&omitHeader=true";
        $res = Ccsd_Tools::solrCurl($query);
        $res = unserialize($res);
        if (isset($res['response']['docs'])) {
            return $res['response']['docs'];
        }
        return array();
    }


    /**
     * @return bool
     */
    public function isInstitutionnel()
    {
        return in_array($this->getCategory(), array(self::CAT_INSTITUTION, self::CAT_LABO, self::CAT_PRES, self::CAT_UNIV));
    }

    /**
     * Suppression de la collection
     */
    public function delete()
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();

        //Suppression des infos sur la collection
        $db->delete(self::TABLE, 'TYPE = "COLLECTION" AND '.self::SID.' = ' . (int)$this->getSid());
        $db->delete(self::TABLE_PARENT, self::SID.' = ' . (int)$this->getSid());

        //Suppression des infos sur le site
        //todo : déporter les WEBSITE_SETTINGS
        $db->delete(Hal_Website_Header::TABLE, self::SID.' = ' . (int)$this->getSid());
        $db->delete('WEBSITE_NAVIGATION', self::SID.' = ' . (int)$this->getSid());
        $db->delete(Hal_Site_Settings_Collection::TABLE, self::SID.' = ' . (int)$this->getSid());
        $db->delete('WEBSITE_STYLES', self::SID.' = ' . (int)$this->getSid());

        // Effacement de la configuration
        $this ->getSettingsObj()->delete();

        //todo : déporter les WEBSITE_SETTINGS
        $db->delete(Hal_User::TABLE_ROLE, 'SID =' . $this->getSid());

        // On récupère tous les docids tamponnés
        $docids = $this->getDocids();

        if (is_array($docids) && count($docids)) {
            // Suppression des tampons des documents
            $db->delete(Hal_Document_Collection::TABLE, self::SID.' = ' . (int)$this->getSid());

            // Réindexation des documents
            Hal_Document::deleteCaches($docids);
            Ccsd_Search_Solr_Indexer::addToIndexQueue($docids);
        }
    }


    /**
     * Retourne la liste des docids d'une collection
     * @return array
     */
    public function getDocids()
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(Hal_Document_Collection::TABLE, 'DOCID')
            ->where(self::SID.' = ?', (int) $this->getSid());
        return $db->fetchCol($sql);
    }

    /**
     * Retourne les documents à masquer pour le tamponneur d'une collection
     *     l'ensemble de tous les documents deja gere manuellement par LES gestionnaires de la collection
     * @return array
     */
    public function getDocumentToHide()
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_HIDDEN_DOC, 'DOCID')
            ->distinct()
            // BM: suppress limit to a user, if two coll manager, they must see the same things
            //->where('UID = ?', (int) $uid)
            ->where(self::SID.' = ?', (int) $this->getSid());
        return $db->fetchCol($sql);
    }

    /**
     * Vérification si l'select ajout du lien parent/enfant va créer un cycle
     * @param int $sid      identifiant collection fille
     * @param int $sparent  identifiant collection parente
     * @return bool : 0/1 cycle créé
     */
    static public function isCreatingCycle($sid, $sparent)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(array('a' => self::TABLE_PARENT), self::SID)
            ->where('a.SID =  ?', $sparent)
            ->where('a.SPARENT = ?', $sid);
        if ($db->fetchAll($sql)) {
            return true;
        }
        $sql = $db->select()->from(array('a' => self::TABLE_PARENT), self::SID)
            ->joinLeft(array('b' => self::TABLE_PARENT), 'a.SPARENT=b.SID', '')
            ->where('a.SID =  ?', $sparent)
            ->where('b.SPARENT = ?', $sid);
        if ($db->fetchAll($sql)) {
            return true;
        }
        $sql = $db->select()->from(array('a' => self::TABLE_PARENT), self::SID)
            ->joinLeft(array('b' => self::TABLE_PARENT), 'a.SPARENT=b.SID', '')
            ->joinLeft(array('c' => self::TABLE_PARENT), 'b.SPARENT=c.SID', '')
            ->where('a.SID =  ?', $sparent)
            ->where('c.SPARENT = ?', $sid);
        if ($db->fetchAll($sql)) {
            return true;
        }

        return false;
    }


    /**
     * Vérifie si un cycle parent/enfant existe
     * @param Hal_Site $coll collection parente
     * @return bool : 0/1 cycle
     */
    public function createCycle (Hal_Site $coll) {
        $cycle = false;
        $parents = $this->getParents();

        if ($cycle = $cycle || $coll->getSid() == $this->getSid()){
            return $cycle;
        }

        foreach ($parents as $parent) {
            $cycle = $cycle || $parent->createCycle($coll);
        }

        return $cycle;
    }

    /**
     * Ajout du parent seulement si il n'y a pas de lien parent/enfant
     * @param Hal_Site_Collection $coll collection parente
     * @return bool 0/1 ajoute un parent à la collection si 1 / rien si 0
     */
    public function addParent(Hal_Site_Collection $coll)
    {
        $this->loadParents(); // assume that foreign key loaded
        if ($this->createCycle($coll)) {
            return false;
        } else {
            $this->_parents[] = $coll;
            return true;
        }
    }

    /** Duplication de collection
     * @param string $request
     * @param array $bind
     * @return array
     **/

    // Todo BM Fonction utilitaire a deplacer pour une utilisation plus large???
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
     * @throws Zend_Db_Adapter_Exception
     */
    function do_insert($table, $bind ) {
        /** @var Zend_Db_Adapter_Pdo_Mysql $db */
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->insert($table,  $bind);
        return $db->lastInsertId($table);
    }

    /**
     * @param string $newcode
     * @return bool|string
     */
    public function duplicateValidateParam($newcode) {
        if ($this -> getSid() == 0) {
            // on ne duplique pas une collection pas encore dans la base!
            return "Collection " . $this -> getShortname() . " inexistante";
        }

        $newObj = Hal_Site::exist($newcode, self::TYPE_COLLECTION, false);

        if ($newObj == true) {
            return "Collection $newcode existe deja";
        }

        return true;
    }

    /**
     * @param Hal_Site_Collection
     * @throws Zend_Db_Adapter_Exception
     */
    public function duplicateParent(Hal_Site_Collection $receiver) {
        $oldId = $this -> getSid();
        # Mise a jour Table SITE_PARENT
        $site_parents = $this -> execandfetch("select * from SITE_PARENT where SID=$oldId");
        foreach ($site_parents as $parent) {
            $parent[self::SID] = $receiver->getSid();
            $this -> do_insert('SITE_PARENT', $parent);
        }
    }

    /**
     * @param $newcode
     * @param $name
     * @param $url
     * @return bool|string
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Exception
     */
    public function createNewFromDuplicate($newcode, $name, $url)
    {
        // Validite des parametres: existance, ... La syntaxe est controle par le controleur
        $ret = $this->duplicateValidateParam($newcode);
        if ( $ret !== true) {
            // Paramètre invalide, on retourne l'erreur de validation
            return $ret;
        }

        // On crée la nouvelle collection
        $newCollection = new Hal_Site_Collection(["site"=>strtoupper($newcode), "name"=>$name, "url"=>$url]);

        $this->duplicateSite($newCollection);
        $this->duplicateSettings($newCollection);
        $this->duplicateUserRight($newCollection);
        $this->duplicateWebSite($newCollection);
        $this->duplicateParent($newCollection);
        return true;
    }

    /**
     * Réindexation des documents présents dans la collection
     * sert si on change les collections supérieurs (pb d'affichage par sous collection)
     * pas besoin de supprimer le cache la réindexation s'en charge
     */
    public function reindexDocuments()
    {
        $docids = $this->getDocids();
        if (is_array($docids) && count($docids)) {
            Ccsd_Search_Solr_Indexer::addToIndexQueue($docids);
        }
    }

    /**
     * Retourne la liste des sous collection d'une collection
     * @return array
     */
    public function getCollectionsInf()
    {
        $collections = array();
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(array('s' => self::TABLE), [self::SID, self::SHORTNAME, self::FULLNAME])
            ->from(array('p' => self::TABLE_PARENT), null)
            ->where('s.SID = p.SID')
            ->where('p.SPARENT = ?', $this->getSid())
            ->order('p.SID ASC');
        foreach ( $db->fetchAll($sql) as $row ) {
            $collections[] = $row;
        }
        return $collections;
    }

    /**
     * @param int $sid
     * @return string
     */
    static public function getFullCritere($sid)
    {
        // Todo : cette fonction doit-elle être static ?

        $criteres = array();
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(Hal_Site_Settings_Collection::TABLE, 'VALUE')->where(self::SID.' = ?', $sid)->where('SETTING = "critere"');
        $critere = $db->fetchOne($sql);
        if ($critere) {
            $criteres[] = '(' . $critere . ')';
        }
        $sql = $db->select()->from(self::TABLE_PARENT, 'SPARENT')->where(self::SID.' = ?', $sid);
        foreach ( $db->fetchCol($sql) as $parentSid ) {
            $res = self::getFullCritere($parentSid);
            if ($res != '') {
                $criteres[] = $res ;
            }
        }
        $criteres = array_filter($criteres);
        if (count($criteres)) {
            return '(' . implode(' AND ', $criteres) . ')';
        } else {
            return '';
        }
    }

    /**
     * @param int  $sid
     * @param bool $onlyAuto
     * @return array
     * @deprecated
     *     use @see getAncestors() instead
     */
    static public function getCollectionsSup($sid, $onlyAuto = true)
    {
        $collections = array();
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(array('p' => self::TABLE_PARENT), 'SPARENT')->where('p.SID = ?', $sid);
        if ($onlyAuto) {
            $sql->joinLeft(array('s' => Hal_Site_Settings_Collection::TABLE), 'p.SPARENT=s.SID', '')
                ->where('s.SETTING = "mode"')
                ->where('s.VALUE = "auto"');
        }
        $sql->order('p.SID ASC');
        foreach ( $db->fetchCol($sql) as $parentSid ) {
            $collections[] = $parentSid;
            $collections = array_merge($collections, self::getCollectionsSup($parentSid, $onlyAuto));
        }
        return array_unique($collections);
    }
   /**
     * @param bool $onlyAuto
     * @return Hal_Site_Collection[]
     */
    public function getAncestors($onlyAuto = true)
    {
        $ancestors = $this->getParentCollections($onlyAuto);
        /** @var Hal_Site_Collection $parent */
        foreach ($ancestors as $parent) {
            // recursion
            $pancestors = $parent->getAncestors($onlyAuto);
            foreach ($pancestors as $coll) {
                $sid = $coll->getSid();
                if (!array_key_exists($sid, $ancestors)) {
                    // Assure unicity
                    $ancestors[$sid] = $coll;
                }
            }
        }
        return $ancestors;
    }

    /**
     * @param bool $onlyAuto
     * @return Hal_Site_Collection[]
     */
    public function getParentCollections($onlyAuto = true)
    {
        $this->loadParents();
        if ($onlyAuto) {
            $parents = $this->getParents();
            $autoParents = array_filter($parents, function ($coll) {
                /** @var Hal_Site_Collection $coll */
                return $coll -> isAuto();
                });
            return $autoParents;
        } else {
            return $this->getParents();
        }
    }

    /**
     * Récupération des catégories d'une collection
     */
    static public function getCategories() {
        $roles = array();
        $oClass = new ReflectionClass('Hal_Site_Collection');
        foreach ( $oClass->getConstants() as $k=>$v ) {
            if ( substr($k, 0, 4) == 'CAT_' ) {
                $roles[$v] = strtolower(substr($k, 4));
            }
        }
        return $roles;
    }

    /**
     * @param $sid
     * @return array
     */
    static public function getSubCollections($sid)
    {
        //todo : est-ce qu'il faudrait pas qu'elle ne soit pas static ? Et utiliser le $this->_sid ??
        // Hum et utiliser un lazy load...
        $res = array();
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(array('p' => self::TABLE_PARENT), null)
            ->join(array('s' => self::TABLE), 'p.SID=s.SID')
            ->where('SPARENT = ?', $sid);
        foreach($db->fetchAll($sql) as $row) {
            if (! isset($res[$row['CATEGORY']])) {
                $res[$row['CATEGORY']] = array();
            }
            $res[$row['CATEGORY']][$row['SITE']] = $row['NAME'];
        }
        return $res;
    }

    /**
     * @deprecated Use getPortal instead
     * Return the Id the portal (or false) if a portal is associated to this collection
     * @param $sid
     * @return int
     */
    static public function getAssociatedPortail($sid)
    {
        // todo : Passer par un Hal_Site_Settigns_Portail ?
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(Hal_Site_Settings_Portail::TABLE, self::SID)
            ->where('SETTING = "COLLECTION"')
            ->where('VALUE = ?', $sid);
        return (int) $db->fetchOne($sql);
    }

    /**
     * A web site can propose access to deposit system: a portal can, a collection cannot
     * Pas de soumission dans une collection
     * @return bool
     */
    public function submitAllowed()
    {
        return false;
    }

    /**
     * A collection can automaticaly  or manualy tamponate its documents
     * So return true if automatic, false if manual
     * @return bool
     */
    public function isAuto() {
        return ($this->getSetting('mode') == 'auto');
    }

    /**
     * Return Portal object (or null) for which this collection is associated
     *     null si pas associee
     * @return Hal_Site_Portail
     */
    public function getPortail()
    {
        if (!$this->_associatedPortailLoaded) {
            $site = Hal_Site_Portail::findByAssociatedCollection($this);
            if ($site) {
                $this->_associatedPortail = $site;
            }
        }
        return $this->_associatedPortail;
    }
    /**
     * If the collection is Patrolled, return the associated portal
     * !!!!  ONLY Associated Collection can be patrolled because of this
     * We need to find a portal for the collection
     * At the moment, collection cannot be associated to a portal
     * When that become possible, we will be able to patrol thos type of collection.
     * @return Hal_site
     */
    public function isPatrolled() {
        if ($this->getSetting('patrolled')) {
            return $this->getPortail();
        }
        return null;
    }
}
