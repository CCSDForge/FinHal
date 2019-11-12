<?php
/**
 * Created by JetBrains PhpStorm.
 * User: yannick
 * Date: 20/09/13
 * Time: 10:28
 * CV d'un auteur
 */
class Hal_Cv
{
    /**
     * Widgets dispo pour un CV
     */
    const CV_WIDGET_PHOTO = 'photo';
    const CV_WIDGET_DOMAIN = 'domain';
    const CV_WIDGET_KEYWORDS = 'keywords';
    const CV_WIDGET_COAUTHORS = 'coauthors';
    const CV_WIDGET_STRUCTURES = 'structures';
    const CV_WIDGET_REVUES = 'revues';
    const CV_WIDGET_YEAR = 'years';
    const CV_WIDGET_ANR = 'anr';
    const CV_WIDGET_EUROP = 'europ';
    const CV_WIDGET_MESH = 'mesh';
    const CV_WIDGET_IDEXT = 'idext';
    const CV_WIDGET_SOCIALURL = 'socialurl';
    const CV_WIDGET_EXPORT = 'export';
    const CV_WIDGET_EXT = 'ext';
    const CV_WIDGET_METRICS = 'metrics';

    /**
     * Table de l'identité du chercheur
     */
    const TABLE_IDHAL = 'REF_IDHAL';
    /**
     * Table des identifiants exterieurs du chercheur
     */
    const TABLE_IDHAL_IDEXT = 'REF_IDHAL_IDEXT';
    /**
     * Table des serveurs exterieurs
     */
    const TABLE_SERVEREXT = 'REF_SERVEREXT';
    /**
     * Table des serveurs exterieurs
     */
    const TABLE_CV = 'REF_IDHAL_CV';
    /**
     * Table des formes auteurs (référentiel auteur)
     */
    const TABLE_AUTHOR = 'REF_AUTHOR';
    /**
     * Table du référentiel des structures
     */
    const TABLE_STRUCTURE = 'REF_STRUCTURE';

    /**
     * Maximum character champs TEXT Mysql
     */
    const MAX_TEXT = 65535;

    /**
     * Identifiant du CV
     * @var int
     */
    protected $_idHal = 0;

    /**
     * Identifiant de l'utilisateur propriétaire du CV
     * @var int
     */
    protected $_uid = 0;

    /**
     * Nom du CV
     * @var string
     */
    protected $_uri = '';

    /**
     * Titre de la page chercheur
     * @var string
     */
    protected $_cvTitle = '';
    /**
     * Descriptif de la page chercheur
     * @var string
     */
    protected $_cvContent = '';

    /**
     * Liste des widgets affichés dans la page chercheur
     * @var array
     */
    protected $_widgets = array();

    /**
     * Widget exterieurs (twitter, facebook, ...)
     * @var string
     */
    protected $_widgetExt = '';

    /**
     * CSS du CV
     * @var string
     */
    protected $_css = '';

    /**
     * URL vers CSS du CV
     * @var string
     */
    protected $_theme = '';

    /**
     * Types de dépôt a afficher
     * @var array
     */
    protected $_typdocs = array();

    /**
     * Liste des formes auteurs associées au chercheur
     * @var array
     */
    protected $_authors = array();

    /**
     * Identifiants exterieurs du chercheur (orcid, researcherid, ...)
     * @var array
     */
    protected $_idExt = array();

    /**
     * URL sociales du chercheur (academia, google schoolar, ...)
     * @var array
     */
    protected $_socialUrlExt = array();

    /**
     * Id de la forme auteur par défaut du chercheur
     * @var int
     */
    protected $_current = 0;

    /**
     * Retour de solR pour la liste des documents
     * @var null
     */
    protected $_solrResult = null;

    /**
     * Nom du fichier de cache
     * @var string
     */
    protected $_cacheFilename = '';

    protected $_cachePath = '';

    /**
     * Filtres à appliquer pour la requete solR
     * @var array
     */
    protected $_filters = array();

    /**
     * Liste des serveurs identifiants chercheurs exterieurs
     * @var array
     */
    protected $_serversExt = array();

    /**
     * Liste des serveurs réseaux sociaux exterieurs
     * @var array
     */
    protected $_socialServers = array();

    /**
     * Liste des URLS des serveurs ext
     * @var array
     */
    protected $_serversUrl = array();

    /**
     * Correspondance des champs de l'url et de solr
     * @var array
     */
    protected $_solRFilters = array('authIdHal_s', 'authFullName_t', 'journalId_i', 'producedDateY_i', 'primaryDomain_s', 'keyword_s', 'anrProjectId_i', 'europeanProjectId_i', 'mesh_s', 'structId_i');
    protected $_solRFacets = array('keyword_s', 'primaryDomain_s', 'authIdHalFullName_fs', 'journalTitleId_fs', 'producedDateY_i', 'anrProjectTitleId_fs', 'europeanProjectTitleId_fs', 'mesh_s', 'authIdHasPrimaryStructure_fs');

    /**
     * Initialisation du CV chercheur
     * @param int $idHal
     * @param string $uri
     * @param int $uid
     */
    public function __construct($idHal = 0, $uri = '', $uid = 0)
    {
        $this->_idHal = $idHal;
        $this->_uri = $uri;
        $this->_uid = $uid;
        $this->_cachePath = CACHE_CV;
    }

    /**
     * Chargement du CV du chercheur
     * @param bool $loadDocuments
     * @return Hal_Cv
     */
    public function load($loadDocuments = true)
    {
        if ($this->_idHal != 0 || $this->_uri != '' || $this->_uid != '') {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $sql = $db->select()
                ->from(array('idhal' => self::TABLE_IDHAL), array('IDHAL AS ID', '*'))
                ->joinLeft(array('cv' => self::TABLE_CV), 'idhal.IDHAL=cv.IDHAL');
            if ($this->_idHal != 0) {
                $sql->where('idhal.IDHAL = ?', $this->_idHal);
            } else if ($this->_uri != '') {
                $sql->where('idhal.URI = ?', $this->_uri);
            } else if ($this->_uid != '') {
                $sql->where('idhal.UID = ?', $this->_uid);
            }
            $row = $db->fetchRow($sql);

            if (isset($row['ID'])) {
                $this->_idHal = $row['ID'];
                $this->_uri = $row['URI'];
                $this->_uid = $row['UID'];

                //Récupération du CV
                $dbfield2objfield = array(
                    'TITLE'   =>  'cvTitle',
                    'CONTENT' =>  'cvContent',
                    'TYPDOC'  =>  'typdocs',
                    'WIDGET'  =>  'widgets',
                );
                foreach($dbfield2objfield as $fieldName => $attrName) {
                    if (isset($row[$fieldName])) {
                        try {
                            $str = $row[$fieldName];
                            $this->{'_' . $attrName} = @unserialize($str);
                            if (! $this->{'_' . $attrName}) {
                                $str = str_replace(["\n", "\r"], "", $str);
                                $str = preg_replace_callback('!s:(\d+):"(.*?)";!', function($m) { return 's:'.strlen($m[2]).':"'.$m[2].'";'; }, $str);
                                $this->{'_' . $attrName} = unserialize($str);
                            }
                        } catch (Exception $e ) {
                            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Exception in load..." . $e ->getMessage());
                        }
                    }
                }
                $this->_widgetExt = $row['WIDGET_EXT'];
                $this->_css = htmlentities($row['CSS']);
                $this->_theme = $row['THEME'];

                //Récupération des formes auteurs associées
                $sql = $db->select()
                    ->from(array('a' => self::TABLE_AUTHOR))
                    ->joinLeft(array('s' => self::TABLE_STRUCTURE), 'a.STRUCTID = s.STRUCTID', 'STRUCTNAME')
                    ->where('IDHAL = ?', $this->_idHal);

                foreach($db->fetchAll($sql) as $row) {


                    if (!empty($row['EMAIL'])) {
                        $row['EMAIL_HASH'] = Hal_Document_Author::getEmailHashed($row['EMAIL']);
                        $row['EMAIL_DOMAIN'] = Hal_Document_Author::getDomainFromEmail($row['EMAIL']);
                    }

                    $this->_authors[$row['AUTHORID']] = $row;

                    if ($row['VALID'] == 'VALID') {
                        $this->_current = $row['AUTHORID'];
                    }
                }
                // TODO: Ne faire qu'une seule requete et partager ensuite suivant (A,AS) et (U)
                // Question: Le SORT est-il vraiment utile?: Pour avoir les serveur toujours dans le meme ordre pour la presentation???
                //Récupération des identifiants exterieurs
                $sql = $db->select()
                    ->from(['i'=>self::TABLE_IDHAL_IDEXT], array('i.SERVERID', 'i.ID'))
                    ->joinLeft(['s' => self::TABLE_SERVEREXT], 's.SERVERID = i.SERVERID', '')
                    ->where('i.IDHAL = ?', $this->_idHal)
                    ->where('s.TYPE IN ("A", "AS")')
                    ->order('s.ORDER ASC');
                $this->_idExt = $db->fetchPairs($sql);
                //Récupération des url sociales exterieurs
                $sql = $db->select()
                    ->from(['i'=>self::TABLE_IDHAL_IDEXT], array('i.SERVERID', 'i.ID'))
                    ->joinLeft(['s' => self::TABLE_SERVEREXT], 's.SERVERID = i.SERVERID', '')
                    ->where('i.IDHAL = ?', $this->_idHal)
                    ->where('s.TYPE = "U"')
                    ->order('s.ORDER ASC');
                $this->_socialUrlExt = $db->fetchPairs($sql);

                //Récupération des données (publis, ...) de l'auteur
                if ($loadDocuments) {
                    $request = $this->createSolrRequest();
                    $this->_solrResult = $this->solrRequest($request);
                    /** Attention: @see widget-export.phtml utilise _createSolr */
                    $this->_createSolr = $request;
                }
            } else {
                $this->_idHal = 0;
            }
        }
        return $this;
    }

    /**
     * Récupération des données solR
     * @param string $request
     * @return mixed
     * @throws Exception
     */
    private function solrRequest($request)
    {
        $existFilters = count($this->getFilters())>0;

        if (!$existFilters && Hal_Cache::exist($this->getCacheFilename(), 3600*6, $this->_cachePath)) {
            $res = unserialize(Hal_Cache::get($this->getCacheFilename(), $this->_cachePath));
        } else {
            $res = Hal_Tools::solrCurl($request, 'hal', 'select', true);
            if ($res) {
                if (!$existFilters) {
                    Hal_Cache::save($this->getCacheFilename(), $res, $this->_cachePath);
                }
                $res = unserialize($res);
            }
        }
        return $res;
    }

    /**
     * @return string
     */
    public function createSolrRequest()
    {
        $select = 'q=authId_i:(' . implode('+OR+', $this->getFormAuthorids()) . ')&start=0&rows=1000';
        $select .= '&fl=docid,citationFull_s,thumbId_i,uri_s';
        if ( count($this->_solRFacets) ) {
            $select .= '&facet=true';
            foreach ( $this->_solRFacets as $f ) {
                if ( $f == 'authIdHasPrimaryStructure_fs' ) {
                    // prefix la facette sur les authorid de cet idhal
                    foreach ( $this->getFormAuthorids() as $id ) {
                        $select .= '&facet.field={!key=authIdHasPrimaryStructure_fs_'.$id.'+facet.prefix='.$id.Ccsd_Search_Solr::SOLR_FACET_SEPARATOR.'}' . $f;
                    }
                } else {
                    $select .= '&facet.field=' . $f;
                }
            }
        }
        if (is_array($this->getTypdocs()) && count($this->getTypdocs()) > 0) {
            $select .= '&fq=docType_s:(' . implode('+OR+', $this->getTypdocs()) . ')';
        }
        if (count($this->getFilters())>0) {
            foreach($this->getFilters() as $filters) {
                foreach($filters as $filter => $data) {
                    $select .= '&fq=' . $data['solr'] . ':' . ($filter);
                }
            }
        }
        $select .= '&facet.mincount=1&facet.limit=2000&group=true&group.field=docType_s&group.limit=1000';
        $select .= '&group.sort=producedDateY_i+desc';
        $select .= '&wt=phps&indent=false&omitHeader=true';

        return $select;
    }

    /**
     * Retourne le nom du fichier de cache pour le CV
     * @return string
     */
    public function getCacheFilename()
    {
        if ($this->_cacheFilename == '') {
            $this->_cacheFilename = 'cv.' . $this->getIdHal() . '.phps';
        }
        return $this->_cacheFilename;
    }

    /**
     * Retourne l'IDHAL de l'auteur
     * @return int
     */
    public function getIdHal()
    {
        return $this->_idHal;
    }

    /**
     * Retourne l'URI du chercheur (identifiant dans l'URL du chercheur)
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * Retourne l'UID du compte associé au CV
     * @return int
     */
    public function getUid()
    {
        return $this->_uid;
    }

    /**
     * Retourne le titre du CV
     * @return mixed
     */
    public function getCVTitle()
    {
        return $this->_cvTitle;
    }

    /**
     * Retourne le contenu du CV
     * @return mixed
     */
    public function getCVContent()
    {
        return $this->_cvContent;
    }


    /**
     * Retourne le bloc widget exterieur (widget twitter, facebook, ...)
     * @return string
     */
    public function getWidgetExt()
    {
        return $this->_widgetExt;
    }

    /**
     * Retourne les styles CSS du site
     * @return string
     */
    public function getCss()
    {
        return $this->_css;
    }

    /**
     * Retourne le theme CSS
     * @return string
     */
    public function getTheme()
    {
        return $this->_theme;
    }

    /**
     * Retourne les identités exterieurs du chercheur
     * @param null $server
     * @return array|mixed
     */
    public function getIdExt($server = null)
    {
        if (null != $server) {
            return Ccsd_Tools::ifsetor($this->_idExt[$server], '');
        }
        return $this->_idExt;
    }

    /**
     * Retourne les url sociales exterieurs du chercheur
     * @param null $server
     * @return array|mixed
     */
    public function getSocialUrlExt($server = null)
    {
        if (null != $server) {
            return Ccsd_Tools::ifsetor($this->_socialUrlExt[$server], '');
        }
        return $this->_socialUrlExt;
    }

    /**
     * Indique si une fichier existe après avoir été chargé
     * @return bool
     */
    public function exist()
    {
        return $this->_idHal != 0;
    }

    /**
     * Indique si une URI existe pour un CV
     * @param $uri
     * @return bool
     */
    public static function existUri($uri)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE_IDHAL, 'count(*)')
            ->where('URI = ?', $uri);
        return $db->fetchOne($sql) == 1;
    }

    /**
     * Indique si un idHAL est défini pour un utilisateur
     * @param $uid
     * @return string
     */
    static public function existForUid($uid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE_IDHAL, 'URI')
            ->where('UID = ?', $uid);
        return $db->fetchOne($sql);
    }

    /**
     * Indique si un CV est défini pour un utilisateur
     * @param int $uid
     * @return string
     */
    static public function existCVForUid($uid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(array('idhal' => self::TABLE_IDHAL), '')
            ->joinLeft(array('cv' => self::TABLE_CV), 'idhal.IDHAL=cv.IDHAL', 'cv.CVID')
            ->where('UID = ?', $uid);

        return $db->fetchOne($sql);
    }



    /**
     * Si un CV existe pour
     * @param int $idHal
     * @return string
     */
    static public function existCVForIdHal($idHal) {
    	$db = Zend_Db_Table_Abstract::getDefaultAdapter();
    	$sql = $db->select()
    	->from(array('idhal' => self::TABLE_CV), 'IDHAL')
    	->where('IDHAL = ?', $idHal);
    	return $db->fetchOne($sql);
    }

    /**
     * IdHAL existe pour un IdExt compatible HAL
     *
     * @param string
     * @param string
     * @return bool
     */
    static public function existFromIdext($url, $identifier) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(array('idhal' => self::TABLE_IDHAL), 'count(*)')
            ->joinLeft(array('idext' => self::TABLE_IDHAL_IDEXT), 'idhal.IDHAL=idext.IDHAL', '')
            ->joinLeft(array('servext' => self::TABLE_SERVEREXT), 'idext.SERVERID=servext.SERVERID', '')
            ->where('servext.URL = ?', $url)
            ->where('idext.ID = ?', $identifier);
        return $db->fetchOne($sql) >= 1;
    }

    /**
     * IdHAL existe pour forme auteur (prenom, nom, mail)
     *
     * @param string
     * @param string
     * @param string
     * @return bool
     */
    static public function existFromAuthorInfo($firstname='', $lastname='', $email='') {
        if ( $firstname == '' || $lastname == '' || $email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
            return false;
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from('REF_AUTHOR', 'count(*)')
            ->where('FIRSTNAME = ?', $firstname)
            ->where('LASTNAME = ?', $lastname)
            ->where('EMAIL = ?', $email)
            ->where('IDHAL != 0')
            ->where('VALID = "VALID"');
        return $db->fetchOne($sql) >= 1;
    }

    /**
     * IdHAL pour un IdExt compatible HAL
     *
     * @param string
     * @param string
     * @return int
     */
    static public function getFromIdext($url, $identifier) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(array('idhal' => self::TABLE_IDHAL), '')
            ->joinLeft(array('idext' => self::TABLE_IDHAL_IDEXT), 'idhal.IDHAL=idext.IDHAL', '')
            ->joinLeft(array('servext' => self::TABLE_SERVEREXT), 'idext.SERVERID=servext.SERVERID', '')
            ->joinLeft(array('author' => self::TABLE_AUTHOR), 'idhal.IDHAL=author.IDHAL', 'author.AUTHORID')
            ->where('author.VALID = ?', 'VALID')
            ->where('servext.URL = ?', $url)
            ->where('idext.ID = ?', $identifier);
        return $db->fetchOne($sql);
    }

    /**
     * IdHAL pour une forme auteur (prenom, nom, mail)
     *
     * @param string
     * @param string
     * @param string
     * @return int
     */
    static public function getFromAuthorInfo($firstname='', $lastname='', $email='') {
        if ( $firstname == '' || $lastname == '' || $email == '' || !filter_var($email, FILTER_VALIDATE_EMAIL) ) {
            return 0;
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from('REF_AUTHOR', 'AUTHORID')
            ->where('FIRSTNAME = ?', $firstname)
            ->where('LASTNAME = ?', $lastname)
            ->where('EMAIL = ?', $email)
            ->where('IDHAL != 0')
            ->where('VALID = "VALID"');
        return $db->fetchOne($sql);
    }

    /**
     * Indique si on affiche la colonne avec les widgets dans la page du CV
     * @return bool
     */
    public function showColumn()
    {
        return count($this->_widgets) > 0 || $this->_widgetExt != '';
    }

    /**
     * Retourne la liste des widgets proposés pour le CV
     * @return array
     */
    public function getListWidgets()
    {
        $res = array();
        try {
            $reflect = new ReflectionClass(get_class($this));
        } catch (ReflectionException $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, $e -> getMessage());
        }
        foreach ($reflect->getConstants() as $const => $value) {
            if (substr($const, 0, 10) === 'CV_WIDGET_') {
                $res[] = $value;
            }
        }
        return $res;
    }

    /**
     * Retourne la liste des widgets à afficher
     * @return array
     */
    public function getWidgets()
    {
        return $this->_widgets;
    }

    /**
     * Retourne la liste des types de dépôts à afficher
     * @return array
     */
    public function getTypdocs()
    {
        return $this->_typdocs;
    }

    /**
     * Retourne la forme auteur par défaut
     * @return mixed
     */
    public function getDefaultFormAuthor()
    {
        return Ccsd_Tools::ifsetor($this->_authors[$this->_current], false);
    }

    /**
     * @return int
     */
    public function getCurrentFormAuthorId()
    {
        return $this->_current;
    }

    /**
     * Retourne les formes auteurs du chercheur
     * @return array
     */
    public function getFormAuthors()
    {
        return $this->_authors;
    }

    /**
     * Retourne les identifiants des formes auteurs
     * @return array
     */
    public function getFormAuthorids()
    {
        return array_keys($this->_authors);
    }

    /**
     * Ajoute un filtre à la requete solr
     * @param $field
     * @param $values
     */
    public function addFilter($field, $values)
    {
        if (in_array($field, $this->_solRFilters)) {
            if (! is_array($values)) {
                $values = array($values);
            }
            foreach ($values as $value) {
                $this->_filters[$field][$value] = array('solr' => $field, 'value' => $value);
            }
        }
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->_filters;
    }

    /**
     * @param string $excludeField
     * @param string $excludeId
     * @return string
     */
    public function getUrl($excludeField = '', $excludeId = '')
    {
        $url = '/' .$this->getUri();
        foreach($this->getFilters() as $field => $filter) {
            foreach(array_keys($filter) as $id) {
                if ($excludeField == $field && $excludeId == $id) {
                    continue;
                }
                $url .= '/' . $field . '/' . $id;
            }
        }
        return $url;
    }

    /**
     * Retourne la facette des mots clés
     * @return mixed
     */
    public function getFacetKeywords()
    {
        return $this->getFacet('keyword_s');
    }

    /**
     * Retourne la facette des mots clés MESH
     * @return mixed
     */
    public function getFacetMesh()
    {
        return $this->getFacet('mesh_s');
    }

    /**
     * Retourne la facette des domaines
     * @return mixed
     */
    public function getFacetDomains()
    {
        return $this->getFacet('primaryDomain_s');
    }

    /**
     * Retourne la facette des revues
     * @return array
     */
    public function getFacetRevues()
    {
        $revues = array();
        foreach($this->getFacet('journalTitleId_fs') as $revue => $nbdoc) {
            list($name, $journalid) = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $revue);
            $revues[] = array('journalid' => $journalid, 'name' => $name, 'nbdoc' => $nbdoc);
        }
        return $revues;
    }

    /**
     * Retourne la facette des projet ANR
     * @return array
     */
    public function getFacetAnr()
    {
        $anrs = array();
        foreach($this->getFacet('anrProjectTitleId_fs') as $anr => $nbdoc) {
            list($name, $anrid) = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $anr);
            $anrs[] = array('anrid' => $anrid, 'name' => $name, 'nbdoc' => $nbdoc);
        }
        return $anrs;
    }

    /**
     * Retourne la facette des projet Européen
     * @return array
     */
    public function getFacetEurop()
    {
        $europs = array();
        foreach($this->getFacet('europeanProjectTitleId_fs') as $europ => $nbdoc) {
            list($name, $europid) = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $europ);
            $europs[] = array('europid' => $europid, 'name' => $name, 'nbdoc' => $nbdoc);
        }
        return $europs;
    }

    /**
     * Retourne la facette des années de production
     * @return mixed
     */
    public function getFacetProducedYear()
    {
        return $this->getFacet('producedDateY_i');
    }

    /**
     * Retourne la facette des auteurs
     * @return array
     */
    public function getFacetAuthors()
    {
        $authors = array();
        foreach($this->getFacet('authIdHalFullName_fs') as $author => $nbdoc) {
            list($authidhal, $fullname) = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $author);

            if ($authidhal == $this->getUri()) continue;
            $authors[] = array('authIdHal_s' => $authidhal, 'authFullName_t' => $fullname, 'nbdoc' => $nbdoc);
        }
        return $authors;
    }

    /**
     * Retourne la facette des affiliations
     * @return array
     */
    public function getFacetStructures()
    {
        $aff = array();
        foreach($this->getFacet('authIdHasPrimaryStructure_fs') as $struct => $nbdoc) {
            list($structid, $name) = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $struct);
            if ( array_key_exists($name, $aff) ) {
                $aff[$name] = array('structId_i' => $aff[$name]['structId_i'].' OR '.$structid, 'name' => $name, 'nbdoc' => $aff[$name]['nbdoc']+$nbdoc);
            } else {
                $aff[$name] = array('structId_i' => $structid, 'name' => $name, 'nbdoc' => $nbdoc);
            }
        }
        return $aff;
    }

    /**
     * Retourne les stats de consulation
     * @return string
     */
    public function getFacetMetrics()
    {
        $metrics =  Hal_Cv_Visite::get($this->getIdHal());
        return $metrics > 0 ? $metrics : 0;
    }

    /**
     * Retourne une facette
     * @param $facet
     * @return array
     */
    private function getFacet($facet)
    {
        if ( $facet == 'authIdHasPrimaryStructure_fs' ) {
            $out = [];
            foreach ( $this->getFormAuthorids() as $id ) {
                if ( isset($this->_solrResult['facet_counts']['facet_fields'][$facet.'_'.$id]) && is_array($this->_solrResult['facet_counts']['facet_fields'][$facet.'_'.$id]) && count($this->_solrResult['facet_counts']['facet_fields'][$facet.'_'.$id]) ) {
                    foreach ( $this->_solrResult['facet_counts']['facet_fields'][$facet.'_'.$id] as $v=>$n ) {
                        $v = str_replace(Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR, '', strstr($v, Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR));
                        if ( isset($out[$v]) ) {
                            $out[$v] += $n;
                        } else {
                            $out[$v] = $n;
                        }
                    }
                }
            }
            return $out;
        } else {
            return Ccsd_Tools::ifsetor($this->_solrResult['facet_counts']['facet_fields'][$facet], []);
        }
    }

    /**
     * Retourne les documents groupés par type de dépôt
     * @return mixed
     */
    public function getDocuments()
    {
        $documents = array();
        $resSolR = Ccsd_Tools::ifsetor($this->_solrResult['grouped']['docType_s']['groups']);
        if(empty($this->getTypdocs())){
            foreach ($resSolR as $group){
                $typdocs[] = $group['groupValue'];
            }
        } else {
            $typdocs = $this->getTypdocs();
        }

        foreach($typdocs as $typdoc) {
            foreach($resSolR as $group) {
                if ((string)$group['groupValue'] == $typdoc) {
                    $documents[$typdoc] = $group['doclist'];
                    break;
                }
            }
        }
        return $documents;
    }

    /**
     * Retourne le nombre de documents pour la requete solr
     * @return mixed
     */
    public function getDocumentsNb()
    {
        return Ccsd_Tools::ifsetor($this->_solrResult['grouped']['docType_s']['matches'], 0);
    }

    /**
     * export en tableau
     * @return array
     */
    public function toArray()
    {
        $data = array(
            'idhal'   =>  $this->getIdHal(),
            'uri'     =>  $this->getUri(),
            'url'     =>  (count($this->getCVTitle()) > 0) ? CV_URL . '/' . $this->getUri() : '',
            'title'   =>  $this->getCVTitle(),
            'content' =>  $this->getCVContent(),
            'widget_ext' =>  $this->_widgetExt,
            'idext' => $this->getIdExt(),
            'socialurl' => $this->getSocialUrlExt(),
            'css' => $this->getCss(),
            'theme' => $this->getTheme()
        );

        return $data;
    }

    /**
     * Liste des serveurs pour identifiants exterieurs de type author
     * @return array
     */
    public function getServerExt()
    {
        if (count($this->_serversExt) == 0) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $sql = $db->select()->from(self::TABLE_SERVEREXT)->where('TYPE IN ("A", "AS")')->order('ORDER ASC');
            $this->_serversExt = $this->_serversUrl = array();
            foreach($db->fetchAll($sql) as $row) {
                $this->_serversExt[$row['SERVERID']] = $row['NAME'];
                $this->_serversUrl[$row['SERVERID']] = $row['URL'];
            }
        }
        return $this->_serversExt;
    }

    /**
     * Liste des serveurs sociaux
     * @return array
     */
    public function getSocialServerExt()
    {
        if (count($this->_socialServers) == 0) {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $sql = $db->select()->from(self::TABLE_SERVEREXT)->where('TYPE = "U"')->order('ORDER ASC');
            $this->_socialServers = array();
            foreach($db->fetchAll($sql) as $row) {
                $this->_socialServers[$row['SERVERID']] = $row['NAME'];
            }
        }
        return $this->_socialServers;
    }

    /**
     * @return array
     */
    public function getServerUrl()
    {
        if (count($this->_serversUrl) == 0) {
            $this->getServerExt();
        }
        return $this->_serversUrl;
    }

    /**
     * Enregistrement du CV
     * @param $data
     */
    public function save($data)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        //Enregistrement de l'IDHAL
        if (! isset($data['idhal']) || $data['idhal']==0) {
            $bind = array(
                'UID' =>  $this->getUid(),
                'URI' =>  Ccsd_Tools::ifsetor($data['uri']),
                'UID' =>  $this->getUid(),
            );
            $db->insert(self::TABLE_IDHAL, $bind);
            $this->_idHal = $db->lastInsertId(self::TABLE_IDHAL);
        } else {
            $this->_idHal = $data['idhal'];
        }

        $bind = array(
            'TEL' =>  Ccsd_Tools::ifsetor($data['tel']),
            'CV' =>  Ccsd_Tools::ifsetor($data['cv']),
            'BLOG' =>  Ccsd_Tools::ifsetor($data['blog']),
            'WIDGET_EXT' =>  Ccsd_Tools::ifsetor($data['widget_ext']),
            'SOCIAL' =>  serialize(Ccsd_Tools::ifsetor($data['social'], array())),
            'WIDGET' =>  serialize(Ccsd_Tools::ifsetor($data['widget'], array())),
            'TYPDOC' =>  serialize(Ccsd_Tools::ifsetor($data['typdoc'], array())),
            'CSS' =>  Ccsd_Tools::ifsetor($data['css']),
            'THEME' =>  Ccsd_Tools::ifsetor($data['theme'])
        );
        $db->update(self::TABLE_IDHAL, $bind, 'IDHAL = ' . $this->getIdHal());

        //Zend_Debug::dump($data);

        // Récupération des anciennes formes auteurs associées à l'IDHAL
        $sql = $db->select()->from(self::TABLE_AUTHOR, 'AUTHORID')->where('IDHAL = ?', $this->getIdHal());
        $oldAuthorids = $db->fetchCol($sql);
        $newAuthorids = array();

        //Mise à jour des formes auteur
        if (isset($data['authorid']) && is_array($data['authorid'])) {
            if (!isset($data['default'])) {
                $data['default'] = $data['authorid'][0];
            }

            foreach ($data['authorid'] as $authorid) {

                if (isset($data['docs'][$authorid]) && is_array($data['docs'][$authorid])) {
                    //Zend_Debug::dump($data['docs'][$authorid]);
                    //L'utilisateur a limité à certains documents
                    //La forme auteur est-elle associée à d'autres dépôts ?
                    $sql = $db->select()
                              ->from(Hal_Document_Author::TABLE, 'DOCID')
                              ->where('AUTHORID = ?', $authorid)
                              ->where('DOCID NOT IN (?)', $data['docs'][$authorid]);
                    $res = $db->fetchCol($sql);
                    //Zend_Debug::dump($res);

                    if ($res && count($res) > 0) {
                        //La forme auteur est bien associée à d'autres dépôts, on va la dupliquer
                        $refAuthor = new Ccsd_Referentiels_Author($authorid);

                        //Fait-elle partie du CV
                        if ($refAuthor->IDHAL == $this->_idHal) {
                            $refAuthor->setData('AUTHORID', 0);
                            $refAuthor->setData('IDHAL', 0);
                            $refAuthor->setData('VALID', 'INCOMING');
                            $id = $refAuthor->save(true);
                            if ($id !== false) {
                                //On modifie l'association pour les dépôts selectionnés
                                /** TODO: Utiliser Hal_Document_Author::replace
                                 Normalement: Hal_Document_Author::replace($authorid, $id , $res);
                                 */
                                $db->update(Hal_Document_Author::TABLE, array('AUTHORID' => $id),
                                    'AUTHORID = ' . $authorid . ' AND DOCID IN (' . implode(', ', $res) . ')');
                                //Réindexation des documents
                                /** Todo: devrait etre dans Hal_Document_Author::replace  */
                                Hal_Document::deleteCaches($res);
                                Ccsd_Search_Solr_Indexer::addToIndexQueue($res);
                            }
                        } else {
                            //Elle ne fait pas partie du CV on en créé une nouvelle
                            $oldAuthorid = $authorid;
                            $refAuthor->setData('AUTHORID', 0);
                            $refAuthor->setData('IDHAL', $this->_idHal);
                            $refAuthor->setData('VALID', Ccsd_Tools::ifsetor($data['default'], 0) == $oldAuthorid ? 'VALID' : 'OLD');
                            $authorid = $refAuthor->save(true);
                            if ($authorid != false) {
                                //On modifie l'association pour les dépôts selectionnés
                                /** TODO: Utiliser Hal_Document_Author::replace
                                 Normalement: Hal_Document_Author::replace($oldAuthorid, $authorid, $data['docs'][$oldAuthorid])
                                 */
                                $db->update(Hal_Document_Author::TABLE, array('AUTHORID' => $authorid),
                                    'AUTHORID = ' . $oldAuthorid . ' AND DOCID IN (' . implode(', ', $data['docs'][$oldAuthorid]) . ')');
                                //Réindexation des documents
                                /** Todo: devrait etre dans Hal_Document_Author::replace  */
                                Hal_Document::deleteCaches($data['docs'][$oldAuthorid]);
                                Ccsd_Search_Solr_Indexer::addToIndexQueue($data['docs'][$oldAuthorid]);
                            }
                        }
                        $newAuthorids[] = $authorid;
                        continue;
                    }
                }

                //On modifie l'IDHAL de l'auteur
                $refAuthor = new Ccsd_Referentiels_Author($authorid);
                $refAuthor->setData('IDHAL', $this->getIdHal());
                $refAuthor->setData('VALID', Ccsd_Tools::ifsetor($data['default'], 0) == $authorid ? 'VALID' : 'OLD');
                $authorid = $refAuthor->save(true);
                if ($authorid !== false ) {
                    $newAuthorids[] = $authorid;
                }
            }
        }

        //Formes auteurs ne faisant plus partie du CV
        $authorids = array_diff($oldAuthorids, $newAuthorids);

        if (count($authorids)) {
            foreach($authorids as $authorid) {
                $oldAuthorid = $authorid;
                $refAuthor = new Ccsd_Referentiels_Author($authorid);
                $refAuthor->setData('IDHAL', 0);
                $authorid = $refAuthor->save(true);
                if ($authorid !== false && $authorid !== $oldAuthorid) {
                    //On supprime l'ancienne forme auteur
                    //$refAuthor->delete();

                    //On modifie l'association pour les dépôts selectionnés
                    $sql = $db->select()->from(Hal_Document_Author::TABLE, 'DOCID')
                        ->where('AUTHORID = ' . $oldAuthorid);
                    $res = $db->fetchCol($sql);
                    if (count($res)) {
                        $db->update(Hal_Document_Author::TABLE, array('AUTHORID' => $authorid),
                            'AUTHORID = ' . $oldAuthorid . ' AND DOCID IN (' . implode(', ', $res ) . ')');
                        //Réindexation des documents
                        Ccsd_Search_Solr_Indexer::addToIndexQueue($res);
                    }
                }
            }
        }

        //Enregistrement des identifiants externes
        $db->delete(self::TABLE_IDHAL_IDEXT, 'IDHAL = ' . $this->getIdHal());
        $servers = $this->getServerExt();
        foreach($data as $key => $val) {
            if (substr($key, 0, 6) == 'idExt_' && $val != '') {
                $id =  str_replace('idExt_', '', $key);
                if (isset($servers[$id])) {
                    $bind = array(
                        'IDHAL'   =>  $this->getIdHal(),
                        'SAMEAS'   =>  $servers[$id],
                        'ID'   =>  $val
                    );
                    $db->insert(self::TABLE_IDHAL_IDEXT, $bind);
                }
            }
        }
        //Suppression du cache s'il a été fait
        Hal_Cache::delete($this->getCacheFilename(), $this->_cachePath);
    }

    /**
     * Récupération du formulaire de création de l'IdHAL
     * @return Ccsd_Form
     */
    public function getFormIdHAL()
    {
        $servExt = $this->getServerExt();
        unset($servExt['4']); //Supprimme l'ORCID

        $form = new Ccsd_Form();
        $form->setAttrib('class', 'form');

        try {
            //IdHAL
            $form->addElement('hidden', 'idhal');
            //Identifiant chercheur
            $form->addElement('text', 'uri', array('label' => 'IdHAL', 'description' => 'Attention, cet identifiant ne pourra plus être modifié par la suite', 'required' => true));
            //Identifiant ORCID
            $form->addElement('text', 'orcid', array('label' => 'Identifiant ORCID', 'readonly' => 'readonly'));
            //Identités exterieures
            $form->addElement('multiTextSimpleLang', 'idext', array(
                'label' => 'Autres identifiants chercheur',
                'description' => "Alignez votre IdHAL avec vos autres identifiants chercheurs",
                'pluriValues' => 0,
                'populate' => $servExt
            ));
            //Plateformes réseaux sociaux
            $form->addElement('multiTextSimpleLang', 'socialurl', array(
                'label' => 'Urls de réseaux sociaux',
                'description' => "Ajoutez vos liens vers les réseaux sociaux",
                'pluriValues' => 0,
                'populate' => $this->getSocialServerExt()
            ));
        } catch (Zend_Form_Exception $e) {

        }
        return $form;
    }

    /**
     * Enregistrement du formulaire de création de l'IdHAL du chercheur
     * @param array
     * @param bool : permet de savoir si on force la fusion en cas de doublon dans les formes auteur
     */
    public function saveIdHAL($data, $acceptDedoublonnage = false)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        //Enregistrement de l'IDHAL
        if (! isset($data['idhal']) || $data['idhal']=='' || $data['idhal']==0) {
            $bind = array(
                'UID' =>  $this->getUid(),
                'URI' =>  Ccsd_Tools::ifsetor($data['uri'])
            );
            $db->insert(self::TABLE_IDHAL, $bind);
            $this->_idHal = $db->lastInsertId(self::TABLE_IDHAL);
        } else {
            $this->_idHal = $data['idhal'];
        }

        if (isset($data['orcid'])){
            $sql = $db->select()->from(self::TABLE_SERVEREXT, 'SERVERID')->where('NAME = "ORCID"');
            $res = $db->fetchOne($sql);
            if  (intval($res) > 0) {
                $data['idext'][$res] = $data['orcid'];
            }
            unset($data['orcid']);
        }

        //Enregistrement des identités exterieures et url sociales exterieures
        if ( ( isset($data['idext']) && is_array($data['idext']) && count($data['idext']) ) || ( isset($data['socialurl']) && is_array($data['socialurl']) && count($data['socialurl']) ) ) {
            $db->delete(self::TABLE_IDHAL_IDEXT, 'IDHAL = ' . $this->_idHal);
            $ext =@ $data['idext'] + $data['socialurl'];
            foreach($ext as $serverid => $id) {
                if ($id == '') continue;
                $sql = $db->select()->from(self::TABLE_SERVEREXT, 'URL')->where('SERVERID = ?', $serverid);
                $res = $db->fetchOne($sql);
                if ($res != ''){
                    $clean = array($res => "");
                    $id = strtr($id, $clean);
                }
                $bind = ['IDHAL' => $this->_idHal, 'SERVERID' => $serverid, 'ID' => $id];
                $db->insert(self::TABLE_IDHAL_IDEXT, $bind);
            }
        }

        //Enregistrement des formes auteurs
        // Récupération des anciennes formes auteurs associées à l'IDHAL
        $sql = $db->select()->from(self::TABLE_AUTHOR, 'AUTHORID')->where('IDHAL = ?', $this->getIdHal());
        $oldAuthorids = $db->fetchCol($sql);
        $newAuthorids = array();

        //Mise à jour des formes auteur
        if (isset($data['authorid']) && is_array($data['authorid'])) {
            if (!isset($data['default'])) {
                $data['default'] = $data['authorid'][0];
            }

            foreach ($data['authorid'] as $authorid) {

                if (isset($data['docs'][$authorid]) && is_array($data['docs'][$authorid])) {
                    //Zend_Debug::dump($data['docs'][$authorid]);
                    //L'utilisateur a limité à certains documents
                    //La forme auteur est-elle associée à d'autres dépôts ?
                    $sql = $db->select()
                        ->from(Hal_Document_Author::TABLE, 'DOCID')
                        ->where('AUTHORID = ?', $authorid)
                        ->where('DOCID NOT IN (?)', $data['docs'][$authorid]);
                    $res = $db->fetchCol($sql);

                    if ($res && count($res) > 0) {
                        //La forme auteur est bien associée à d'autres dépôts, on va la dupliquer
                        $refAuthor = new Ccsd_Referentiels_Author($authorid);

                        //Fait-elle partie du CV
                        if ($refAuthor->IDHAL == $this->_idHal) {
                            $refAuthor->setData('AUTHORID', 0);
                            $refAuthor->setData('IDHAL', 0);
                            $refAuthor->setData('VALID', 'OLD');
                            $id = $refAuthor->save(true, $acceptDedoublonnage);
                            if ($id !== false) {
                                //On modifie l'association pour les dépôts selectionnés
                                $db->update(Hal_Document_Author::TABLE, array('AUTHORID' => $id),
                                    'AUTHORID = ' . $authorid . ' AND DOCID IN (' . implode(', ', $res) . ')');
                                //Réindexation des documents
                                Hal_Document::deleteCaches($res);
                                Ccsd_Search_Solr_Indexer::addToIndexQueue($res);
                            }
                        } else {
                            //Elle ne fait pas partie du CV on en créé une nouvelle
                            $oldAuthorid = $authorid;
                            $refAuthor->setData('AUTHORID', 0);
                            $refAuthor->setData('IDHAL', $this->_idHal);
                            $refAuthor->setData('VALID', Ccsd_Tools::ifsetor($data['default'], 0) == $oldAuthorid ? 'VALID' : 'OLD');
                            $authorid = $refAuthor->save(true, $acceptDedoublonnage);
                            if ($authorid != false) {
                                //On modifie l'association pour les dépôts selectionnés
                                $db->update(Hal_Document_Author::TABLE, array('AUTHORID' => $authorid),
                                    'AUTHORID = ' . $oldAuthorid . ' AND DOCID IN (' . implode(', ', $data['docs'][$oldAuthorid]) . ')');
                                //Réindexation des documents
                                Hal_Document::deleteCaches($data['docs'][$oldAuthorid]);
                                Ccsd_Search_Solr_Indexer::addToIndexQueue($data['docs'][$oldAuthorid]);
                            }
                        }
                        $newAuthorids[] = $authorid;
                        continue;
                    }
                }

                //On modifie l'IDHAL de l'auteur
                $refAuthor = new Ccsd_Referentiels_Author($authorid);
                $refAuthor->setData('IDHAL', $this->getIdHal());
                $refAuthor->setData('VALID', Ccsd_Tools::ifsetor($data['default'], 0) == $authorid ? 'VALID' : 'OLD');
                $authorid = $refAuthor->save(true, $acceptDedoublonnage);
                if ($authorid !== false ) {
                    $newAuthorids[] = $authorid;
                }
            }
        }

        //Formes auteurs ne faisant plus partie du CV
        $authorids = array_diff($oldAuthorids, $newAuthorids);

        if (count($authorids)) {
            foreach($authorids as $authorid) {
                $oldAuthorid = $authorid;
                $refAuthor = new Ccsd_Referentiels_Author($authorid);
                $refAuthor->setData('IDHAL', 0);
                $refAuthor->setData('VALID', 'INCOMING');
                $authorid = $refAuthor->save(true, $acceptDedoublonnage);
                if ($authorid !== false && $authorid !== $oldAuthorid) {
                    //On supprime l'ancienne forme auteur
                    //$refAuthor->delete();

                    //On modifie l'association pour les dépôts selectionnés
                    $sql = $db->select()->from(Hal_Document_Author::TABLE, 'DOCID')
                        ->where('AUTHORID = ' . $oldAuthorid);
                    $res = $db->fetchCol($sql);
                    if (count($res)) {
                        $db->update(Hal_Document_Author::TABLE, array('AUTHORID' => $authorid),
                            'AUTHORID = ' . $oldAuthorid . ' AND DOCID IN (' . implode(', ', $res ) . ')');
                        //Réindexation des documents
                        Hal_Document::deleteCaches($res);
                        Ccsd_Search_Solr_Indexer::addToIndexQueue($res);
                    }
                }
            }
        }

        //Suppression du cache s'il a été fait
        $this -> delete_cache();
    }

    /**
     * Récupération du formulaire de création du CV
     * @return Ccsd_Form
     */
    public function getFormCV()
    {
        $form = new Ccsd_Form();
        $form->setAttrib('class', 'form');

        //Titre du CV
        try {
            $form->addElement('MultiTextSimpleLang', 'title', array(
                'label' => 'Titre de la page',
                'populate' => Hal_Tools::getLangWebsite(),
                'required' => true
            ));

            //Contenu
            $form->addElement('MultiTextAreaLang', 'content', array(
                'label' => 'Contenu',
                'lang' => Zend_Registry::get('languages'),
                'populate' => Hal_Tools::getLangWebsite(),
                'tiny' => true,
                'validators' => array(
                    array('StringLength', false, array('max' => self::MAX_TEXT, 'messages' => 'Attention : la limite maximale de caractères autorisés est de 65 000'))
                ),
                'display' => Ccsd_Form_Element_MultiText::DISPLAY_ADVANCED
            ));

            //Thème
            $form->addElement('select', 'theme', array(
                'label' => 'Thème CSS',
                'populate' => array(0 => 'Thème HAL par défaut', 'cv2.css' => 'Taupe', 'cv3.css' => 'Galaxie')
            ));

            //CSS
            $form->addElement('textarea', 'css', array(
                'label' => 'Feuille de styles',
                'rows' => '12'
            ));
        } catch (Zend_Form_Exception $e) {

        }
        return $form;
    }

    /**
     *
     */
    public function delete_cache()
    {
        Hal_Cache::delete($this->getCacheFilename(), $this->_cachePath);
    }

    /**
     * Save User CV using UID from session
     * @param $data
     * @return bool|int
     */
    public function saveCV($data)
    {
        $data['idhal'] = false;
        $cv = new Hal_Cv(null,null,Hal_Auth::getUid());
        $cv->load(false);
        $data['idhal'] = $cv->_idHal;

        if(!$data['idhal']) {
            return false;
        }

        // get idhal from uid

        $this->_idHal = $data['idhal'];
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete(self::TABLE_CV, 'IDHAL =' . $this->getIdHal());

        $bind = array(
            'IDHAL' =>  $this->getIdHal(),
            'TITLE' =>  serialize(Ccsd_Tools::ifsetor($data['title'], array())),
            'CONTENT' =>  serialize(Ccsd_Tools::ifsetor($data['content'], array())),
            'WIDGET' =>  serialize(Ccsd_Tools::ifsetor($data['widget'], array())),
            'TYPDOC' =>  serialize(Ccsd_Tools::ifsetor($data['typdoc'], array())),
            'WIDGET_EXT' =>  Ccsd_Tools::ifsetor($data['widget_ext']),
            'CSS' =>  Ccsd_Tools::ifsetor($data['css']),
            'THEME' =>  Ccsd_Tools::ifsetor($data['theme'])
        );
        $this -> delete_cache();
        return $db->insert(self::TABLE_CV, $bind);
    }

    /**
     * Retourne la liste des CV existants
     * @return array
     */
    static public function liste()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_IDHAL, 'URI')->order('DATEMODIF DESC');
        return $db->fetchAll($sql);
    }

    /**
     * Retourne les identifiants des CV (utilisé pour le RDF)
     * todo à fusionner avec methode liste
     * @return array
     */
    static public function getIds()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_IDHAL, 'URI')->order('DATEMODIF DESC');
        return $db->fetchCol($sql);
    }
}