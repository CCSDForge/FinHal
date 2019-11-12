<?php

/**
 * Auteur d'un document
 *
 */
class Hal_Document_Author
{

    /**
     * tables
     */
    const TABLE = 'DOC_AUTHOR';
    const TABLE_REF_IDHAL = 'REF_IDHAL';
    const TABLE_DOC_ID = 'DOC_AUTHOR_IDEXT';
    const TABLE_SERVER_EXT = 'REF_SERVEREXT';
    const TABLE_IDHAL_IDEXT = 'REF_IDHAL_IDEXT';
    const TABLE_DOCAUTHSTRUCT = 'DOC_AUTSTRUCT';
    /**
     * Identifiant de la forme auteur
     *
     * @var int
     */
    protected $_authorid = 0;

    /**
     * Identifiant unique auteur
     *
     * @var int
     */
    protected $_docauthid = 0;
    /**
     * Nom
     *
     * @var string
     */
    protected $_lastname = '';
    /**
     * Nom : celui de la forme valide de l'idhal
     * @var string
     */
    protected $_lastname_valid = '';
    /**
     * Prénom
     *
     * @var String
     */
    protected $_firstname = '';
    /***
     * Prénom : celui de la forme valide de l'idhal
     * @var string
     */
    protected $_firstname_valid = '';
    /**
     * Initiale, autre prénom
     *
     * @var String
     */
    protected $_othername = '';
    /**
     * Adresse mail
     *
     * @var string
     */
    protected $_email = '';
    /**
     * URL du site perso
     *
     * @var String
     */
    protected $_url = '';
    /**
     * Etablissement employeur
     *
     * @var String
     */
    protected $_organism = '';
    /**
     * Etablissement employeur
     *
     * @var Int
     */
    protected $_organismid = 0;
    /**
     * Role de l'auteur
     *
     * @var string
     */
    protected $_quality = 'aut';
    /**
     * idHAL de l'auteur (int)
     *
     * @var int
     */
    protected $_idhal = 0;
    /**
     * IdHAL de l'auteur (URI string)
     *
     * @var string
     */
    protected $_idhalstring;
    /**
     * id du CV auteur
     * @var int
     */
    protected $_idCV;
    /**
     * Si l'auteur a un CV
     * @var boolean
     */
    protected $_hasCV;
    /**
     * identifiants externes de l'auteur (lié à son idHAL ou à la forme)
     *
     * @var array
     */
    protected $_idsAuthor = array();
    /**
     * Correspondance champs SolR > Objet
     *
     * @var array
     */
    protected $_solrCorresp = array(
        'docid' => 'authorid',
        'idHal_i' => 'idhal',
        'idHal_s' => 'idhalstring',
        'lastName_s' => 'lastname',
        'middleName_s' => 'othername',
        'firstName_s' => 'firstname',
        'email_s' => 'email',
        'url_s' => 'url'
    );

    /**
     * indice des structures de l'auteurs dans le tableaux des structures du documents.
     *
     * @var int[]
     */
    protected $_structidx = array();

    /**
     * Identifiants des structures de rattachement de l'auteur pour le dépôt
     *
     * @var int[]
     */
    protected $_structid = array();

    /**
     *
     * @var string
     */
    protected $_valid = '';

    /**
     * Hal_Document_Author constructor.
     * @param int $authorid
     * @param int $docid
     */
    public function __construct($authorid = null, $docid = 0)
    {
        if (null != $authorid) {
            $this->setAuthorid($authorid);
            $this->load($docid);
        }
    }

    /**
     * Chargement des données d'un auteur en fonction du référentiel
     * @param int $docid
     */
    public function load($docid = 0)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()
            ->from(array(
                'ra' => Ccsd_Referentiels_Author::$_table
            ),
                array(
                    'authorid' => 'ra.AUTHORID',
                    'idhal' => 'ra.IDHAL',
                    'firstname' => 'ra.FIRSTNAME',
                    'lastname' => 'ra.LASTNAME',
                    'othername' => 'ra.MIDDLENAME',
                    'email' => 'ra.EMAIL',
                    'url' => 'ra.URL',
                    'organismid' => 'ra.STRUCTID',
                    'valid' => 'ra.VALID'
                ))
            ->joinLeft(array(
                'rs' => Ccsd_Referentiels_Structure::$_table
            ), 'ra.STRUCTID = rs.STRUCTID', array(
                'organism' => 'rs.STRUCTNAME'
            ))
            ->joinLeft(array(
                'idhal' => self::TABLE_REF_IDHAL
            ), 'idhal.IDHAL = ra.IDHAL', array(
                'idhalstring' => 'idhal.URI'
            ))
            ->where('AUTHORID = ?', $this->getAuthorid());

        $row = $db->fetchRow($sql);

        if ($row) {

            if ($row['idhal'] != 0) {
                // récupère en plus les formes auteurs valides si l'auteur a un idhal et une forme valide
                $sql2 = $db->select()
                    ->from(array(
                        'autvalid' => Ccsd_Referentiels_Author::$_table
                    ), array(
                            'FIRSTNAME_VALID' => 'autvalid.FIRSTNAME',
                            'LASTNAME_VALID' => 'autvalid.LASTNAME'
                        )
                    )
                    ->where("IDHAL='" . $row['idhal'] . "' AND VALID='VALID'");

                $row2 = $db->fetchRow($sql2);
                if ($row2) {
                    $row = array_merge($row2, $row);
                }
            }

            $this->set($row);

            $this->loadIdsAuthor($docid);
            if ($this->getIdHal()) {
                $idCV = Hal_Cv::existCVForIdHal($this->getIdHal());
                $this->setIdCV($idCV);
                if ($idCV != false) {
                    $this->setHasCV(true);
                } else {
                    $this->setHasCV(false);
                }
            } else {
                $this->setIdCV(0);
                $this->setHasCV(false);
            }
        } else {
            $this->setAuthorid(0);
        }

        // On charge l'email de l'utilisateur si la forme auteur n'a pas d'email
        if ($this->getEmail() == '' && $this->getIdhalstring() != '') {
            $userUid = Hal_User::getUidFromIdHalUri($this->getIdhalstring());
            $associatedAuthor = Hal_User::createUser($userUid);
            if (isset($associatedAuthor)) {
                $this->setEmail($associatedAuthor->getEmail());
            }
        }
    }

    /**
     * Récupération de l'identifiant de l'auteur
     *
     * @return int
     */
    public function getAuthorid()
    {
        return $this->_authorid;
    }

    /**
     * initialisation de l'identfiant de l'auteur
     *
     * @param int $authorid
     */
    public function setAuthorid($authorid)
    {
        $this->_authorid = (int)$authorid;
    }

    /**
     * Récupération de l'identifiant de l'auteur
     *
     * @return int
     */
    public function getDocauthid()
    {
        return $this->_docauthid;
    }

    /**
     * initialisation de l'identfiant de l'auteur
     *
     * @param int $docauthid
     */
    public function setDocauthid($docauthid)
    {
        $this->_docauthid = (int)$docauthid;
    }

    /**
     * Initialisation de l'auteur à partir d'un tableau associatif
     *
     * @param array $data
     */
    public function set($data)
    {
        $classMethods = get_class_methods($this);

        foreach ($data as $attrib => $value) {

            $attrib = strtolower($attrib);

            $method = 'set' . ucfirst($attrib);
            if (in_array($method, $classMethods)) {
                $this->$method($value);
            } else {
                $this->{'_' . $attrib} = $value;
            }
        }
        if (!array_key_exists('organism', array_keys($data)) && $this->getOrganismId() != 0) {
            $struct = new Ccsd_Referentiels_Structure($this->getOrganismId());
            $this->setOrganism($struct->getStructname());
        }
    }

    /**
     * récupération de l'id de l'établissement d'appartenance
     * @return int
     */
    public function getOrganismId()
    {
        return $this->_organismid;
    }

    /**
     *
     * @param number $_organismid
     * @return Hal_Document_Author
     */
    public function setOrganismid($_organismid)
    {
        $this->_organismid = $_organismid;
        return $this;
    }

    /**
     * Récupération des identifiants de l'auteur
     *
     * @param int
     * @return array
     */
    public function loadIdsAuthor($docid = 0)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        if ($docid) {
            $sql = $db->select()
                ->from(self::TABLE_DOC_ID, 'ID')
                ->join(array(self::TABLE_SERVER_EXT), self::TABLE_SERVER_EXT . '.SERVERID = ' . self::TABLE_DOC_ID . '.SERVERID', array('URL', 'NAME'))
                ->where('DOCID = ?', (int)$docid)
                ->where('AUTHORID = ?', (int)$this->getAuthorid());
            foreach ($db->fetchAll($sql) as $row) {
                $this->_idsAuthor[$row['NAME']] = array('url' => $row['URL'], 'id' => $row['ID']);
            }
        }
        if ($this->getIdHal()) {
            $sql = $db->select()
                ->from(self::TABLE_IDHAL_IDEXT, 'ID')
                ->join(array(self::TABLE_SERVER_EXT), self::TABLE_SERVER_EXT . '.SERVERID = ' . self::TABLE_IDHAL_IDEXT . '.SERVERID', array('URL', 'NAME'))
                ->where('IDHAL = ?', $this->getIdHal());

            foreach ($db->fetchAll($sql) as $row) {
                $this->_idsAuthor[$row['NAME']] = array('url' => $row['URL'], 'id' => $row['ID']);
            }
        }
        return $this->_idsAuthor;
    }

    /**
     * Récupération de l'uri idHAL de l'auteur
     *
     * @return int
     */
    public function getIdHal()
    {
        return $this->_idhal;
    }

    /**
     * initialisation de l'uri idHAL de l'auteur
     *
     * @param int $idhal
     */
    public function setIdHal($idhal)
    {
        $this->_idhal = (int)$idhal;
    }

    /**
     * Récupération d'une fonction auteur
     * @param string $role
     * @return string
     */
    static public function getRole($role)
    {
        if (in_array($role, self::getRoles())) {
            return 'relator_' . $role;
        }
        return 'relator_aut';
    }

    /**
     * Récupération des fonctions auteur d'un document
     * @param string $typdoc
     * @return string[]
     */
    static public function getRoles($typdoc = 'DEFAULT')
    {
        $roles = Hal_Settings::getAuthorRoles($typdoc);

        $formatedRoles = array();

        foreach ($roles as $role)
            $formatedRoles[$role] = 'relator_' . $role;

        return $formatedRoles;
    }

    /**
     * Remplace une forme auteur par une nouvelle dans les documents
     * @param int $from
     * @param array $docids
     * @return bool|int
     */
    static public function replaceWithNew($from = 0, $docids = array())
    {
        $newAuthor = new Hal_Document_Author();
        $oldAuthor = new Hal_Document_Author($from);

        $newAuthor->setFirstname($oldAuthor->getFirstname());
        $newAuthor->setLastname($oldAuthor->getLastname());
        $newAuthId = $newAuthor->save();

        return Hal_Document_Author::replace($from, $newAuthId, $docids);
    }

    /**
     * récupération du prénom de l'auteur
     */
    public function getFirstname()
    {
        return $this->_firstname;
    }

    /**
     * initialisation du prénom de l'auteur
     *
     * @param string $firstname
     */
    public function setFirstname($firstname)
    {
        $this->_firstname = $firstname;
    }

    /**
     * Récupération du nom de l'auteur
     */
    public function getLastname()
    {
        return $this->_lastname;
    }

    /**
     * initialisation du nom de l'auteur
     *
     * @param string $lastname
     */
    public function setLastname($lastname)
    {
        $this->_lastname = $lastname;
    }

    /**
     * @return Ccsd_Referentiels_Author
     */
    public function docauthor2refauthor() {
        $authorid = $this->getAuthorid();
        $data = array(
            'AUTHORID' => $authorid,
            'IDHAL' => $this->getIdHal(),
            'FIRSTNAME' => $this->getFirstname(),
            'LASTNAME' => $this->getLastname(),
            'MIDDLENAME' => $this->getOthername(),
            'EMAIL' => $this->getEmail(),
            'URL' => $this->getUrl(),
            'STRUCTID' => $this->getOrganismId()
        );
        return new Ccsd_Referentiels_Author(0, $data);
    }
    /**
     * Enregistrement d'un auteur
     *
     * @return int
     */
    public function save()
    {
        $authorid = $this->getAuthorid();

        // On ne modifie pas le référentiel dans le cas où l'auteur existe déjà dans le référentiel avec une forme valide
        if ($authorid != 0 && $this->isValidForm()) {
            return $authorid;
        }

        $refAuthor = $this->docauthor2refauthor();
        return $refAuthor->save();
    }

    /**
     * @param $xml
     * @return DOMElement
     */
    public function getXMLNode($xml) {

        $refAuthor = $this->docauthor2refauthor();
        return $refAuthor->getXMLNode($xml, $this->getStructid(), $this->getQuality());
    }
    /**
     * récupération du complément de nom de l'auteur
     */
    public function getOthername()
    {
        return $this->_othername;
    }

    /**
     *
     * @param string $_othername
     * @return Hal_Document_Author
     */
    public function setOthername($_othername)
    {
        $this->_othername = $_othername;
        return $this;
    }

    /**
     * récupération de l'email de l'auteur
     */
    public function getEmail()
    {
        return $this->_email;
    }

    /**
     * initialisation de l'email de l'auteur
     *
     * @param
     *            string email
     */
    public function setEmail($email)
    {
        $this->_email = filter_var($email, FILTER_SANITIZE_EMAIL);
    }

    /**
     * récupération de l'url du site perso de l'auteur
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     *
     * @param string $_url
     * @return Hal_Document_Author
     */
    public function setUrl($_url)
    {
        $_url = filter_var($_url, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $this->_url = filter_var($_url, FILTER_SANITIZE_URL);
        return $this;
    }

    /**
     * @param $aut2
     * @return bool
     */
    public function isConsideredSameAuthor(Hal_Document_Author $aut2)
    {
        return Ccsd_Externdoc::isConsideredSameAuthor($this->toArray(), $aut2->toArray());
    }

    /**
     * @param Hal_Document_Author $aut2
     */
    public function mergeAuthor(Hal_Document_Author $aut2)
    {

        $array1 = $this->toArray();

        // Pour que les données se mergent correctement, il ne faut pas de valeurs vides
        foreach ($array1 as $i => $v) {
            if (empty($v)) {
                unset($array1[$i]);
            }
        }

        $array2 = $aut2->toArray();

        $result = Ccsd_Externdoc::merge2Authors($array2, $array1);
        $this->set($result);
    }

    /**
     * @param int $from
     * @param int $to
     * @param array $docids
     * @return bool|int
     */
    static public function replace($from = 0, $to = 0, $docids = array())
    {
        try {
            if ($from == 0 || $to == 0) {
                return false;
            }
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $where['AUTHORID = ?'] = (int)$from;
            if (count($docids)) {
                $where['DOCID IN (?)'] = $docids;
            }
            return $db->update(self::TABLE, array('AUTHORID' => $to), $where);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Recherche dans le référentiel auteur
     * TODO utiliser la même méthode que pour les référentiels
     *
     * @param string $q terme recherché
     * @param string $format format de retour
     * @param int $nbResultats
     * @return mixed
     * @throws Exception
     */
    public static function search($q, $format = 'json', $nbResultats = 100)
    {
        $queryString = "fl=docid,label_html&df=text_autocomplete&q=" . urlencode($q) . "&omitHeader=true&rows=" . $nbResultats . "&wt=" . $format . "&sort=" . urlencode('valid_s desc,score desc,lastName_s asc,firstName_s asc');

        return Ccsd_Tools::solrCurl($queryString, 'ref_author');
    }

    /**
     * Retourne les auteurs d'une structure triés par nom de famille
     *
     * @param int $structid identifiant de la structure
     *
     * @return array auteurs triés par nom de famille
     * @throws Exception
     */
    static public function getFromStructure($structid)
    {
        $authors = array();
        $stringToSearch = Ccsd_Search_Solr::SOLR_ALPHA_SEPARATOR . $structid . Ccsd_Search_Solr::SOLR_FACET_SEPARATOR;
        $queryString = "q=*&rows=0&wt=phps&facet=true&facet.mincount=1&facet.field=structHasAlphaAuthId_fs&facet.contains=" . urlencode($stringToSearch);
        $res = unserialize(Ccsd_Tools::solrCurl($queryString));
        // retourne par exemple : "D_AlphaSep_300046_FacetSep_IN2P3_JoinSep_5552_FacetSep_Davier M."

        if (isset($res['facet_counts']['facet_fields']['structHasAlphaAuthId_fs'])) {
            foreach (array_keys($res['facet_counts']['facet_fields']['structHasAlphaAuthId_fs']) as $item) {
                $data = explode(Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR, $item);
                if (isset($data[1])) {
                    $dataAuthor = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $data[1]);
                    if (count($dataAuthor) == 2) {
                        $authors[$dataAuthor[0]] = $dataAuthor[1];
                    }
                }
            }
        }
        uasort($authors, 'strcoll');

        return $authors;
    }

    /**
     * Retourne les auteurs d'un contributeur
     *
     * @param int  $uid
     * @return array
     * @throws Exception
     */
    static public function getFromUid($uid)
    {
        $authors = array();
        $queryString = "q=*&start=0&rows=0&fq=contributorId_i:" . $uid . "&wt=phps&facet=true&facet.field=authIdFullName_fs&facet.mincount=1";
        $res = unserialize(Ccsd_Tools::solrCurl($queryString));
        if (isset($res['facet_counts']['facet_fields']['authIdFullName_fs'])) {
            foreach (array_keys($res['facet_counts']['facet_fields']['authIdFullName_fs']) as $item) {
                $data = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $item);
                if (count($data) == 2) {
                    $authors[$data[0]] = $data[1];
                }
            }
        }
        asort($authors);
        return $authors;
    }

    /**
     * @param array $author
     * @return bool|int
     * @throws Exception
     */
    static public function findByAuthor($author)
    {
        $lastname = Ccsd_Tools::ifsetor($author['lastname'], '');
        $firstname = Ccsd_Tools::ifsetor($author['firstname'], '');
        $email = Ccsd_Tools::ifsetor($author['email'], '');

        if ($lastname != '' && $firstname != '') {
            return self::find($lastname, $firstname, $email);
        }
        return false;
    }

    /**
     * Search for an Author
     * @param string $lastname
     * @param string $firstname
     * @param string $email
     * @return int docid|bool
     * @throws Exception
     */
    static public function find($lastname, $firstname, $email = '')
    {
        $lastname = trim($lastname);
        $firstname = trim($firstname);
        $email = trim($email);

        $queryString = 'q=*:*&fq=NOT(idHal_i:0)';
        $queryString .= '&fq=lastName_sci:' . urlencode($lastname);
        if ($firstname != '') {
            $queryString .= '&fq=firstName_sci:' . urlencode($firstname);
            //On cherche sur l'initiale du prénom dans le cas où on a un email
            if (strlen($firstname) == 1 && !empty($email)) {
                $queryString .= '*';
            }
        }
        if ($email != '') {
            $queryString .= '&fq=email_s:' . urlencode(self::getEmailHashed($email));
        }
        $queryString .= '&fl=docid';
        $queryString .= '&rows=1';
        $queryString .= '&omitHeader=true';
        $queryString .= '&sort=' . urlencode('valid_s desc');
        $queryString .= '&wt=phps';

        $res = unserialize(Ccsd_Tools::solrCurl($queryString, 'ref_author'));
        if (isset($res['response']['docs'][0]['docid'])) {
            return (int)$res['response']['docs'][0]['docid'];
        }
        return false;
    }

    /**
     * Hash of author's email
     * @param string $email
     * @param string $hashType hash type [md5 (default) | sha1]
     * @return null|string sha1 hash of email
     */
    static public function getEmailHashed($email = null, $hashType = 'md5')
    {
        if ($email == null) {
            return null;
        }

        $email = strtolower($email);

        switch ($hashType) {
            case 'sha1':
                return sha1($email);
                break;
            case 'md5':
                return md5($email);
                break;
            default:
                return md5($email);
                break;
        }

    }

    /**
     * @param int $docauthid
     * @return array
     */
    static public function getInfoAuthor($docauthid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, '*')
            ->where('DOCAUTHID = ?', $docauthid);
        return $db->fetchRow($sql);
    }

    /**
     * @param int $docauthid
     */
    static public function deleteAuthor($docauthid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete(self::TABLE, 'DOCAUTHID = ' . $docauthid);
    }

    /**
     * @param int $docauthid
     * @param int $docid
     * @param int $authorid
     * @param string $quality
     * @throws Zend_Db_Adapter_Exception
     */
    static public function insertAuthor($docauthid, $docid, $authorid, $quality)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $bind = array(
            'DOCAUTHID' => $docauthid,
            'DOCID' => $docid,
            'AUTHORID' => $authorid,
            'QUALITY' => $quality
        );
        $db->insert(self::TABLE, $bind);
        Hal_Document::deleteCaches($docid);
    }

    /**
     * @param int $docauthid
     * @return array
     */
    static public function getInfoAuthorStruct($docauthid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_DOCAUTHSTRUCT, '*')
            ->where('DOCAUTHID = ?', $docauthid);
        return $db->fetchRow($sql);
    }

    /**
     * @param int $docauthid
     */
    static public function deleteAuthorStruct($docauthid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete(self::TABLE_DOCAUTHSTRUCT, 'DOCAUTHID = ' . $docauthid);
    }

    /**
     * @param int $autstrucid
     * @param int $docauthid
     * @param int $structid
     * @throws Zend_Db_Adapter_Exception
     */
    static public function insertAuthorStruct($autstrucid, $docauthid, $structid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $bind = array(
            'AUTSTRUCTID' => $autstrucid,
            'DOCAUTHID' => $docauthid,
            'STRUCTID' => $structid
        );
        $db->insert(self::TABLE_DOCAUTHSTRUCT, $bind);
    }

    /**
     * Get domain from author's email
     * @param string $email
     * @return null|string
     */
    static public function getDomainFromEmail($email = null)
    {
        if ($email == null) {
            return null;
        }
        return Ccsd_Tools::getEmailDomain($email);

    }

    /**
     * Récupération de l'authorid d'un utilisateur à partir du docid et de l'uid de l'utilisateur
     *
     * @param $docid
     * @param $uid
     * @return int
     */
    static public function findAuthidFromDocid($docid, $uid)
    {

        $auth = new Hal_Document_Author();

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()
            ->from(self::TABLE, 'AUTHORID')
            ->where('DOCID = ?', (int)$docid);

        $authids = $db->fetchAll($sql);

        $user = Hal_User::createUser($uid);

        if (isset($user)) {

            foreach ($authids as $id) {
                $author = new Hal_Document_Author($id['AUTHORID']);

                if (strtolower($author->getLastname()) == strtolower($user->getLastname()) && ucfirst($author->getFirstname()) == ucfirst($user->getFirstname())) {
                    return $id['AUTHORID'];
                }
            }
        }
        return 0;
    }

    /*
     * Remplace une forme auteur par une autre dans les documents
     */

    /**
     * Initialisation de l'auteur à partir d'un retour solR
     *
     * @param int
     * @return bool
     * @throws Exception
     */
    public function loadFromSolr($authorid)
    {
        $solrResponse = unserialize(Ccsd_Tools::solrCurl('q=docid:' . $authorid . '&wt=phps&omitHeader=true', 'ref_author'));
        $dataAuthor = Ccsd_Tools::ifsetor($solrResponse['response']['docs'][0], false);
        if ($dataAuthor === false) {
            return false;
        }
        foreach ($this->_solrCorresp as $fieldSolr => $attrib) {
            if (isset($dataAuthor[$fieldSolr])) {
                $this->{'_' . $attrib} = $dataAuthor[$fieldSolr];
            }
        }
        return true;
    }

    /**
     * Récupération de l'auteur suivant %n et %p
     * @param string $pattern
     * @return string
     */
    public function getEncodedName($pattern = '%n, %p')
    {
        $firstname = $this->getFirstname();
        $lastname = $this->getLastname();
        return trim(str_replace('%p', Ccsd_Tools::upperWord($firstname), str_replace('%n', Ccsd_Tools::upperWord($lastname), $pattern)), ' ,');
    }

    /**
     * Retourne la signature MD5 d'un auteur
     *
     * @return string
     */
    public function createMd5()
    {
        return md5(strtolower('lastname' . $this->getLastname() . 'firstname' . $this->getFirstname() . 'middlename' . $this->getOthername() . 'email' . $this->getEmail() . 'url' . $this->getUrl() . 'structid' . $this->getOrganismId()));
    }

    /**
     * Indique si l'auteur est affilié
     *
     * @return bool
     */
    public function isAffiliated()
    {
        return count($this->_structidx) > 0;
    }

    /**
     * Ajout d'une affiliation (pointeur interne)
     *
     * @param int $structidx
     */
    public function addStructidx($structidx)
    {
        if ($structidx !== false && !in_array($structidx, $this->_structidx)) {
            $this->_structidx[] = $structidx;
        }
    }

    /**
     * Suppression d'une affiliation
     *
     * @param int $structidx
     */
    public function delStructidx($structidx)
    {
        $key = array_search($structidx, $this->_structidx);
        if ($key !== false) {
            unset($this->_structidx[$key]);
        }
    }

    /**
     * Mise à jour des structures (lors de suppression)
     *
     * @param array $corresp
     */
    public function updateStructidx($corresp)
    {
        $structidx = array();
        foreach ($this->_structidx as $idx) {
            if (array_key_exists($idx, $corresp)) {
                $structidx[] = $corresp[$idx];
            }
        }
        $this->_structidx = $structidx;
    }

    /**
     * Ajout d'une structure
     *
     * @param int $structid
     */
    public function addStructid($structid)
    {
        if (!in_array($structid, $this->_structid)) {
            $this->_structid[] = $structid;
        }
    }

    /**
     * Indique si l'auteur est correspondant
     *
     * @return bool
     */
    public function isCorresponding()
    {
        return $this->getQuality() == 'crp';
    }

    /**
     * Fonction de l'auteur
     */
    public function getQuality()
    {
        return $this->_quality;
    }

    /**
     *
     * @param string $_quality
     * @return Hal_Document_Author
     */
    public function setQuality($_quality)
    {
        $this->_quality = (strlen(trim($_quality))) ? trim($_quality) : 'aut';
        return $this;
    }

    /**
     * Retourne l'objet auteur sous forme de tableau
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'authorid' => $this->getAuthorid(),
            'idhal' => $this->getIdHal(),
            'idhalstring' => $this->getIdhalstring(),
            'idsauthor' => $this->getIdsAuthor(),
            'lastname' => $this->getLastname(),
            'firstname' => $this->getFirstname(),
            'lastname_valid' => $this->getLastname_valid(),
            'firstname_valid' => $this->getFirstname_valid(),
            'othername' => $this->getOthername(),
            'fullname' => $this->getFullname(),
            'email' => $this->getEmail(),
            'url' => $this->getUrl(),
            'organism' => $this->getOrganism(),
            'organismid' => $this->getOrganismId(),
            'quality' => $this->getQuality(),
            'structidx' => $this->getStructidx(),
            'structid' => $this->getStructid(),
            'idcv' => $this->getIdCV(),
            'hascv' => $this->getHasCV(),
            'valid' => $this->getValid()

        );
    }

    /**
     *
     * @return string $_idhalstring
     */
    public function getIdhalstring()
    {
        return $this->_idhalstring;
    }

    /**
     *
     * @param string $_idhalstring
     * @return Hal_Document_Author
     */
    public function setIdhalstring($_idhalstring)
    {
        $this->_idhalstring = $_idhalstring;
        return $this;
    }

    /**
     * Récupération des identifiants ext de l'auteur
     *
     * @return array
     */
    public function getIdsAuthor()
    {
        $out = array();
        foreach ($this->_idsAuthor as $n => $v) {
            if (isset($v['url']) && isset($v['id'])) {
                if (preg_match('$^http$', $v['id'])) {
                    $out[$n] = $v['id'];
                } else {
                    $out[$n] = $v['url'] . $v['id'];
                }
            }
        }
        return $out;
    }


    /**
     * Return One Author external ID
     * @param string $extIdName ORCID ; arXiv ; IdRef ; ...
     * @return string
     */
    public function getAuthorExtId(string $extIdName = '') :string
    {
        $authorExternalId = '';

        if ($extIdName == '') {
            $authorExternalId = '';
        }

        $authorIdExts = $this->getIdsAuthor();

        if ( (isset($authorIdExts[$extIdName])) && ($authorIdExts[$extIdName] != '') )  {
            $authorExternalId = $authorIdExts[$extIdName];
        }

        return $authorExternalId;

    }


    /**
     * @return string
     */
    public function getLastname_valid()
    {
        return $this->_lastname_valid;
    }

    /**
     * @param string $lastname_valid
     * @return Hal_Document_Author
     */
    public function setLastname_valid($lastname_valid)
    {
        $this->_lastname_valid = trim($lastname_valid);
        return $this;
    }

    /**
     * @return string
     * @return Hal_Document_Author
     */
    public function getFirstname_valid()
    {
        return $this->_firstname_valid;
    }

    /**
     * @param string $firstname_valid
     * @return Hal_Document_Author
     */
    public function setFirstname_valid($firstname_valid)
    {
        $this->_firstname_valid = trim($firstname_valid);
        return $this;
    }

    /**
     * Récupération du nom complet de l'auteur
     * @param bool $middlename
     * @return string
     */
    public function getFullname($middlename = false)
    {
        $firstname = $this->getFirstname();
        if ($middlename && $this->getOthername()) {
            $firstname .= ' ' . $this->getOthername();
        }
        return Ccsd_Tools::formatAuthor($firstname, $this->getLastname());
    }

    /**
     * récupération de l'établissement d'appartenance de l'auteur
     */
    public function getOrganism()
    {
        return $this->_organism;
    }

    /**
     *
     * @param string $_organism
     * @return Hal_Document_Author
     */
    public function setOrganism($_organism)
    {
        $this->_organism = $_organism;
        return $this;
    }

    /**
     * récupération des affiliations de l'auteur (pointeur interne)
     */
    public function getStructidx()
    {
        return $this->_structidx;
    }

    /**
     * initialisation des affiliations de l'auteur (pointeur interne)
     *
     * @param array $structidx
     */
    public function setStructidx($structidx)
    {
        $this->_structidx = $structidx;
    }

    /**
     * récupération des affiliations de l'auteur
     */
    public function getStructid()
    {
        return $this->_structid;
    }

    /**
     * initialisation des affiliations de l'auteur
     *
     * @param array $structid
     */
    public function setStructid($structid)
    {
        $this->_structid = $structid;
    }

    /**
     *
     * @return int
     */
    public function getIdCV()
    {
        return $this->_idCV;
    }

    /**
     *
     * @param int $_idCV
     * @return Hal_Document_Author
     */
    public function setIdCV($_idCV = 0)
    {
        $this -> _idCV = (int)$_idCV;
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function getHasCV()
    {
        return $this->_hasCV;
    }

    /**
     *
     * @param boolean $_hasCV
     * @return Hal_Document_Author
     */
    public function setHasCV($_hasCV)
    {
        $this->_hasCV = $_hasCV;
        return $this;
    }

    /**
     *
     * @return  string $_valid
     */
    public function getValid()
    {
        return $this->_valid;
    }

    /**
     * Les formes auteurs VALID ou INCOMING sont des formes valides
     *
     * @return bool
     */
    public function isValidForm()
    {
        return !empty($this->_valid) && $this->_valid != Ccsd_Referentiels_Abstract::STATE_INCOMING;
    }

    /**
     *
     * @param string $_valid
     * @return Hal_Document_Author
     */
    public function setValid($_valid)
    {
        $this->_valid = $_valid;
        return $this;
    }

    /**
     * Retourne le formulaire d'édition d'un auteur
     *
     * @return Ccsd_Form
     * @throws Zend_Form_Exception
     */
    public function getForm($typdoc)
    {
        $form = new Ccsd_Form();
        // Prénom
        $form->addElement('text', 'firstname', array(
            'label' => 'Prénom',
            'required' => true
        ));
        // Nom
        $form->addElement('text', 'lastname', array(
            'label' => 'Nom',
            'required' => true
        ));
        // Autre prénom
        $form->addElement('text', 'othername', array(
            'label' => 'Autre(s) prénom(s), Initiales'
        ));
        // Email
        $form->addElement('text', 'email', array(
            'label' => 'Email',
            'validators' => array(
                array(
                    'validator' => 'EmailAddress'
                )
            )
        ));
        // URL site perso
        $form->addElement('text', 'url', array(
            'label' => 'URL page perso'
        ));
        // Organisme d'appartenance
        $form->addElement('text', 'organism', array(
            'label' => "Etablissement employeur"
        ));
        // Authorid
        $form->addElement('hidden', 'authorid');
        $form->addElement('hidden', 'organismid');

        return $form;
    }


    /**
     * Retourne le formulaire d'édition d'un auteur
     *
     * @param string $typdoc
     * @return Ccsd_Form
     * @throws Zend_Form_Exception
     */
    public function getFunctionForm($typdoc)
    {
        $form = new Ccsd_Form();

        // Qualité
        $form->addElement('select', 'quality', array(
            'label' => 'Fonction',
            'multiOptions' => self::getRoles($typdoc)
        ));


        $attribs = [];
        $description = '';
        $email = $this->getEmail();

        // On ne permet pas de modifier l'email si l'auteur a un idHAl
        if ($this->isValidForm()) {
            $attribs = array('disabled' => 'disabled');
            $description = 'Les données de cet auteur sont valides dans le référentiel, vous ne pouvez pas les modifier lors du dépôt.';
        }

        // Email
        $form->addElement('text', 'email', array(
            'label' => 'Email',
            'validators' => array(
                array(
                    'validator' => 'EmailAddress'
                )
            ),
            'attribs' => $attribs,
            'description' => $description,
            'value' => $email
        ));

        return $form;
    }

    /**
     * Essaie de récupérer la dernière affiliations de l'auteur
     *
     * @return array
     */
    public function getLastStructures()
    {
        if ($this->getLastname() == '' && $this->getFirstname() == '') {
            return array();
        }


        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        // SQL permettant de récupérer le DOCAUTHID du dernier dépôt pour cet
        // auteur
        $sql1 = $db->select()
            ->from(array(
                'd1' => 'DOCUMENT'
            ), null)
            ->from(array(
                'a1' => self::TABLE
            ), 'DOCAUTHID')
            ->where('d1.DOCID = a1.DOCID')
            ->order('DATESUBMIT DESC')
            ->limit(1);
        if ($this->getAuthorid() != 0) {
            $sql1->where('a1.AUTHORID = ?', $this->getAuthorid());
        } else {
            $sql1->from(array(
                'ra1' => 'REF_AUTHOR'
            ), null)
                ->where('ra1.AUTHORID = a1.AUTHORID')
                ->where('LASTNAME = ?', $this->getLastname());

            if ($this->getFirstname()) {
                $sql1->where('LEFT(FIRSTNAME, 1) = ?', $this->getFirstname()[0]);
            }
        }
        $sql = $db->select()
            ->distinct()
            ->from(array(
                'as' => self::TABLE_DOCAUTHSTRUCT
            ), null)
            ->from(array(
                's' => Ccsd_Referentiels_Structure::$_table
            ), array(
                'STRUCTID',
                'VALID'
            ))
            ->where('as.DOCAUTHID = (' . $sql1 . ')')
            ->where('as.STRUCTID = s.STRUCTID');

        $structids = array(
            Hal_Document_Structure::STATE_VALID => array(),
            Hal_Document_Structure::STATE_OLD => array(),
            Hal_Document_Structure::STATE_INCOMING => array()
        );

        foreach ($db->fetchAll($sql) as $row) {
            $structids[$row['VALID']][] = $row['STRUCTID'];
        }

        if (count($structids[Hal_Document_Structure::STATE_VALID])) {
            $out = $structids[Hal_Document_Structure::STATE_VALID];
        } else {
            $out = array_merge($structids[Hal_Document_Structure::STATE_OLD], $structids[Hal_Document_Structure::STATE_INCOMING]);
        }
        return $out;
    }

    /*
     * Récupération des informations d'un auteur
     */

    public function searchStructure()
    {

    }

    /**
     * Supprime un auteur de la table DOC_AUTHOR
     * @param int $docid
     * @return int
     * @throws Zend_Db_Adapter_Exception
     */

    public function saveDocAuthor($docid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $authorid = $this->save();
        if ($authorid === false) {
            return false;
        }
        // Enregistrement des infos spécifiques au document
        if ($this->getIdHal() == 0 && is_array($this->_idsAuthor) && count($this->_idsAuthor)) {
            foreach ($this->_idsAuthor as $val) {
                if (isset($val['url'])) {
                    $sql = $db->select()->from(self::TABLE_SERVER_EXT, 'SERVERID')->where('URL = ?', $val['url']);
                    $res = $db->fetchOne($sql);
                    if ($res !== false) {
                        $db->insert(self::TABLE_DOC_ID, array('AUTHORID' => $authorid, 'DOCID' => $docid, 'SERVERID' => $res, 'ID' => $val['id']));
                    }
                }
            }
        }
        $db->insert(self::TABLE, array('DOCID' => $docid, 'AUTHORID' => $authorid, 'QUALITY' => $this->getQuality()));

        return $db->lastInsertId(self::TABLE);
    }

    /**
     * Ajoute un auteur dans la table DOC_AUTHOR
     * @param string $server
     * @param int $id
     * @return Hal_Document_Author
     */

    public function addIdAuthor($server, $id)
    {
        $this->_idsAuthor[] = array('url' => $server, 'id' => $id);
        return $this;
    }

    /**
     * Récupération des informations d'un auteur lié aux structures
     * @param string $_othername
     * @return Hal_Document_Author
     */

    public function setMiddlename($_othername)
    {
        return $this->setOthername($_othername);
    }

    /**
     * Supprime un auteur de la table DOC_AUTSTRUCT
     */
    public function __toString()
    {
        return $this->getFullname();
    }

    /*
     * Ajoute un auteur dans la table DOC_AUTSTRUCT
     */

    /**
     * Indique si un auteur est bien formé :
     * @return bool
     */
    public function isWellFormed()
    {
        if (trim($this->getLastname()) == '' || trim($this->getFirstname()) == '') {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function getURI()
    {
        return self::createURI($this->getAuthorid());
    }

    /**
     * @param int $authorid
     * @return string
     */
    static public function createURI($authorid)
    {
        return AUREHAL_URL . "/author/{$authorid}";
    }

    /**
     * @param int $idhal
     * @return int
     */
    public function getUidFromIdHal($idhal)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()
            ->from(self::TABLE_REF_IDHAL, 'UID')
            ->where('IDHAL = ?', $idhal);
        return $db->fetchOne($sql);
    }


}
