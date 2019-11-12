<?php

/**
 * Utilisateur spécifique de la plateforme HAL
 * @author Yannick Barborini
 *
 */
class Hal_User extends Ccsd_User_Models_User {

    const SCREEN_NAME_MAX_LENGTH = 50;

    /**
     *
     * @var integer
     */
    protected $_uid;
    
    /**
     * Mode de dépot de l'utilisateur
     * @var int
     */
    protected $_mode = 1;
    
    /**
     *
     * @var string[]
     */
    protected $_domain = [];
    
    /**
     *
     * @var string[]
     */
    protected $_autodepot = [];

    /**
     * L'utilisateur a-t-il déjà lu les conditions de dépôt d'un fichier ?
     * @var bool
     */
    protected $_seelegal = true;

    /**
     *
     * @var integer
     */
    protected $_aut = 1;

    /**
     * Préférence de mail Co Auteur
     * 1 envoi / 0 n'envoi pas
     * @var integer
     */
    protected $_coaut = 1;

    /**
     * Préférence de mail Référent Structure
     * 1 envoi / 0 n'envoi pas
     * @var array
     */
    protected $_refstru = [];

    /**
     * Préférence de mail Administrateur
     * 1 envoi / 0 n'envoi pas
     * @var integer
     */
    protected $_admin = 1;
    
    /**
     *
     * @var string
     */
    protected $_licence = '';
    
    /**
     *
     * @var int // 0 ou 1
     */
    protected $_default_author = 0;
    
    /**
     * Laboratoire d'appartenance de l'utilisateur
     *
     * @var array
     */
    protected $_laboratory = [];

    /**
     * Institution d'appartenance de l'utilisateur
     *
     * @var string[]
     */
    protected $_institution = [];
    
    /**
     * Role de l'auteur
     *
     * @var string
     */
    protected $_default_role = 'aut';

    /**
     *
     * @var string
     */
    protected $_langueid;

    /**
     * TRUE si existe données utilisateur HAL
     *
     * @var boolean
     */
    protected $_hasAccountData;

    /**
     * Tableau des rôles
     *
     * @var array
     */
    protected $_roles = null;
    private $_roles_loaded = false;
    /**
     * Adaptateur db par défaut
     *
     * @var Zend_Db_Adapter_Pdo_Mysql
     */
    protected $_db = null;

    /**
     * Table des informations spécifiques utilisateurs
     *
     * @var string
     */
    const TABLE_USER = 'USER';
    
    /**
     * Table des droits utilisateurs
     *
     * @var string
     */
    const TABLE_ROLE = 'USER_RIGHT';
    
    /**
     * Table des préférences de dépot
     *
     * @var string
     */
    const TABLE_PREF_DEPOT = 'USER_PREF_DEPOT';

    const TABLE_REF_STRUCT_PARENT = 'REF_STRUCT_PARENT';

    const TABLE_CONNEXION = 'USER_CONNEXION';

    /**
     * Table des préférences de mail
     *
     * @var string)->addMessage(
     */
    const TABLE_PREF_MAIL = 'USER_PREF_MAIL';
    
    const PREF_MODE = 'mode';
    const PREF_DOMAIN = 'domain';
    const PREF_AUTODEPOT = 'autodepot';
    const PREF_SEELEGAL = 'seelegal';
    const PREF_LICENCE = 'licence';
    const PREF_AUTH_DEFAULT = 'default_author';
    const PREF_ROLE = 'default_role';
    const PREF_LABO = 'laboratory';
    const PREF_INSTITUTION = 'institution';
    const PREF_AUT = 'aut';
    const PREF_COAUT = 'coaut';
    const PREF_REFSTRU = 'refstru';
    const PREF_ADMIN = 'admin';

    protected $_preferences_depot = [
        self::PREF_MODE,        self::PREF_DOMAIN, self::PREF_AUTODEPOT, self::PREF_LICENCE,
        self::PREF_AUTH_DEFAULT, self::PREF_ROLE,  self::PREF_LABO,      self::PREF_INSTITUTION,
        self::PREF_SEELEGAL];
    protected $_preferences_mail = [Hal_Acl::ROLE_AUTHOR, Hal_Acl::ROLE_MEMBER, Hal_Acl::ROLE_ADMINSTRUCT, Hal_Acl::ROLE_ADMIN];

    /**
     * Nom à afficher à l'utilisateur, eg écran et e-mail
     *
     * @var string
     */
    protected $_screen_name;

    /**
     * Identifiant du site sur lequel l'utilisateur est connecté
     *
     * @var int
     */
    protected $_sid = 0;

    /**
     * Si l'utilisateur a un CV
     * @var boolean
     */
    protected $_cv;

    /**
     * IdHal de l'utilisateur
     * @var string
     */
    protected $_idhal;

    /**
     * Nombre de documents en ligne de l'utilisateur
     * @var integer
     */
    protected $_nbdocvis = 0;

    /**
     * Nombre de documents en expertise scientifique de l'utilisateur
     * @var integer
     */
    protected $_nbdocsci = 0;

    /**
     * Nombre de documents refusés de l'utilisateur
     * @var integer
     */
    protected $_nbdocref = 0;

    /**
     * Constructeur d'un utilisateur HAL
     *
     * @param array $options
     */
    public function __construct(array $options = null) {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        parent::__construct($options);
    }

    /**
     * @param array $options
     * @return Hal_User
     */
    public function setOptionsPrefMail(array $options) {
        $methods = get_class_methods($this);

        foreach ($options as $key => $value) {
            $method = 'setPrefMail' . ucfirst($key);

            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    /**
     * Chargement des informations spécifiques d'un utilisateurs (données
     * spécifiques application + roles)
     * @deprecated
     */
    public function load() {
        $this->loadRoles();
    }

    /**
     * Si l'utilisateur a un CV
     * @return boolean
     */
    public function HasCV() {
        $res = Hal_Cv::existCVForUid($this->getUid());
        $this->setCv($res);
        return (bool) $res;
    }

    /**
     * Si l'utilisateur a un idhal
     * @return boolean
     */
    public function hasIdhal() {

        $res = Hal_Cv::existForUid($this->getUid());
        $this->setIdhal($res);
        return (bool) $res;
    }

    /**
     * Retourne les rôles d'un utilisateur en fonction du contexte
     * Pour un portail nous retournons tous les rôles de l'utilisateur
     * POur une collection nous ne retournons que le rôle tamponneur
     *
     * @param string $roleId
     * @return array
     */
    public function getRoles($roleId = null) {
        if ($this -> _roles_loaded === false) {
            $this->loadRoles();
            $this -> _roles_loaded = true;
        }
        if ($roleId == null) {
            return $this -> _roles;
        } else if (isset($this -> _roles [$roleId])) {
            return $this -> _roles [$roleId];
        }
        return array();
    }

    /**
     * Chargement des privilèges d'un utilisateur
     */
    public function loadRoles() {
        $this->_roles = [];
        $sql = $this->_db->select()->from(self::TABLE_ROLE)->where('UID = ?', $this->getUid());

        foreach ($this->_db->fetchAll($sql) as $row) {
            $right = $row ['RIGHTID'];
            $sid = $row ['SID'];
            $value = $row ['VALUE'];

            $website = Hal_Site::loadSiteFromId($sid);

            if ($right == Hal_Acl::ROLE_ADMIN) {
                if ($website === null) {
                    continue;
                }
                // Vérification de l'existance du portail
                $site = $website->getShortname();
                $this->_roles [$right] [$sid] = $site;
            } elseif ($right == Hal_Acl::ROLE_TAMPONNEUR) {
                $this->_roles [Hal_Acl::ROLE_A_TAMPONNEUR] = true;
                if ($website === null) {

                    continue;
                }
                // Vérification de l'existance du portail
                $site = $website->getShortname();
                $this->_roles [$right] [$sid] = $site;
            } else if ($right == Hal_Acl::ROLE_ADMINSTRUCT) {

                if (defined('SPACE_NAME') && SPACE_NAME == 'AUREHAL') {
                    $sql = $this->_db->select()
                            ->from(    array("t1" => self::TABLE_REF_STRUCT_PARENT), "t1.STRUCTID AS 0")
                            ->joinLeft(array("t2" => self::TABLE_REF_STRUCT_PARENT), "t2.PARENTID = t1.STRUCTID", "t2.STRUCTID AS 1")
                            ->joinLeft(array("t3" => self::TABLE_REF_STRUCT_PARENT), "t3.PARENTID = t2.STRUCTID", "t3.STRUCTID AS 2")
                            ->joinLeft(array("t4" => self::TABLE_REF_STRUCT_PARENT), "t4.PARENTID = t3.STRUCTID", "t4.STRUCTID AS 3")
                            ->joinLeft(array("t5" => self::TABLE_REF_STRUCT_PARENT), "t5.PARENTID = t4.STRUCTID", "t5.STRUCTID AS 4")
                            ->where("t1.PARENTID = ?", $this->_db->quote($value, Zend_Db::INT_TYPE));

                    $result = $this->_db->fetchAll($sql);

                    $result = new RecursiveIteratorIterator(
                            new RecursiveArrayIterator($result), RecursiveIteratorIterator::LEAVES_ONLY, false
                    );

                    $structids = array($value);
                    foreach ($result as $s) {
                        $structids[] = $s;
                    }
                    unset($result);

                    $structids = array_unique(array_filter($structids), SORT_NUMERIC);
                } else {
                    $structids = array($value);
                }

                $sql = $this->_db->select()
                        ->from("REF_STRUCTURE", array("STRUCTID", "STRUCTNAME"))
                        ->where("STRUCTID IN (" . implode(',', $structids) . ")");

                $result = $this->_db->fetchAll($sql);

                foreach ($result as $r) {
                    $this->_roles [$right] [$r["STRUCTID"]] = $r['STRUCTNAME'];
                }
            } else if ($right == Hal_Acl::ROLE_HALADMIN || $right == Hal_Acl::ROLE_MODERATEUR || $right == Hal_Acl::ROLE_VALIDATEUR || $right == Halms_Acl::ROLE_ADMINHALMS) {
                if ($sid == 0) {
                    $site = 'all';
                } else {
                    $site = $website->getShortname();
                    if ($site === false) {
                        continue;
                    }
                }
                if ($value != '') {
                    if ($value == '-') {
                        $this->_roles [$right] ['-' . $sid] ['site'] = $site;
                    } elseif ($value == 'terminated') {
                        $this->_roles [$right] [$sid] [$value] = $value;
                    } else {

                        $this->_roles [$right] [$sid] ['site'] = $site;
                        list ($category, $value) = explode(':', $value);
                        $this->_roles [$right] [$sid] [$category] [$value] = strtolower($category . '_' . $value);
                    }
                } else {
                    $this->_roles [$right] [$sid] ['site'] = $site;
                }
            }
        }
    }

    /**
     * @param array $roles
     * @return string
     */
    public function getSqlDeleteRoles($roles = [Hal_Acl::ROLE_ADMINSTRUCT, Hal_Acl::ROLE_MODERATEUR, Hal_Acl::ROLE_VALIDATEUR, Hal_Acl::ROLE_TAMPONNEUR, Hal_Acl::ROLE_ADMIN])
    {
        // ROLE_A_TAMPONNEUR is not save into DB so, no need to delete it
        $where = 'UID = ' . $this->_uid . ' AND (';
        $or = '';
        foreach ($roles as $role) {
            $where .= $or . '(RIGHTID = "' . $role . '"';
            if ($role == Hal_Acl::ROLE_ADMIN) {
                $where .= ' AND SID = ' . SITEID;
            }
            $where .= ')';
            $or = ' OR ';
        }
        return $where. ')';
    }
    /**
     * @param array $roles
     */
    public function deleteRoles($roles)    {
        $where = $this -> getSqlDeleteRoles($roles);
        $this->_db->delete(self::TABLE_ROLE, $where);
    }

    /**
     * @param $roles
     */
    public function addRoles($roles) {
        foreach ($roles as $roleid => $data) {
            if ($roleid == Hal_Acl::ROLE_ADMIN) {
                if (is_string($data) && $data == "1") {
                    $this->addRole($roleid, SITEID);
                }
            } else if ($roleid == Hal_Acl::ROLE_ADMINSTRUCT) {
                foreach ($data as $value) {
                    $this->addRole($roleid, 0, $value);
                }
            } else if ($roleid == Hal_Acl::ROLE_TAMPONNEUR) {
                foreach ($data as $value) {
                    $this->addRole($roleid, $value);
                }
            } else if ($roleid == Hal_Acl::ROLE_A_TAMPONNEUR) {
                // This is a internal role from ROLE_TAMPONEUR: we don't save it to DB
                continue;
            } else if ($roleid == Hal_Acl::ROLE_MODERATEUR || $roleid == Hal_Acl::ROLE_VALIDATEUR) {
                // _db->delete(self::TABLE_ROLE, 'UID = ' . $this->_uid . ' AND RIGHTID = "' . $roleid . '"')
                $this->deleteRoles([$roleid]);
                foreach ($data as $sid => $d) {
                    foreach ($d as $group => $values) {
                        foreach ($values as $value) {
                            if ($group != '0') {
                                $value = $group . ':' . $value;
                            }
                            $this->addRole($roleid, (int) $sid, $value);
                        }
                    }
                }
            }
        }
    }

    /**
     * Insert a new right for user
     * @param                   $roleid
     * @param int|number|string $sid
     * @param string            $value
     */
    public function addRole($roleid, $sid = SITEID, $value = '') {
        $bind = array(
            'UID' => $this->_uid,
            'SID' => $sid,
            'RIGHTID' => $roleid,
            'VALUE' => $value
        );
        try {
            $this->_db->insert(self::TABLE_ROLE, $bind);
        } catch (Exception $e) {

        }
    }

    /**
     * @return array
     */
    public function toArray() {
        $ccsdUser = parent::toArray();

        $halUser = array();

        $fields = array(
            // array key => metho name
                'screen_name' => 'screen_name',
                'mode'        => 'mode',
                'domain'      => 'domain',
                'autodepot'   => 'autodepot',
                'licence'     => 'licence',
                'laboratory'  => 'laboratory',
                'institution' => 'institution',
                'langueid'    => 'langueid',
                'default_author'=> 'default_author',
                'default_role'  => 'default_role',
                'idhal'       => 'idhal',
                'nbdocvis'    => 'nbdocvis',
                'nbdocsci'    => 'nbdocsci',
                'nbdocref'    => 'nbdocref',
                'cv'          => 'cv',
                'aut'         => 'PrefMailAuthor',
                'coaut'       => 'PrefMailMember',
                'refstru'     => 'PrefMailAdminstruct',
                'admin'       => 'PrefMailAdministrator',
            );

        foreach ($fields as $arrayKey => $methodname) {
            $method = 'get' . ucfirst($methodname);
            if (method_exists($this, $method)) {
                $halUser [$arrayKey] = $this->$method();
            }
        }

        return array_merge($ccsdUser, $halUser);
    }

    /**
     * Return all structure the user is admin
     * The return form is list of idStruct => "name of struct (idStruct)"
     * @return string[]
     */
    public function getStructAuth(){
        $liste = array();
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select()->from('USER_RIGHT','VALUE')->where('UID = ?', $this -> getUid())->where('RIGHTID = ?','adminstruct');

        foreach ($db->fetchCol($select) as $structid){
            $structure = new Ccsd_Referentiels_Structure();
            $structure->load($structid, false);  // We don't need to recurse, we only need name
            $liste[$structid] = $structure->getStructname() . " (" . $structid . ")";
        }

        return $liste;
    }

    /**
     * @param $prefids
     * @param $pref
     * @param $value
     * @throws Zend_Db_Adapter_Exception
     */
    protected function insertLine(&$prefids, $pref, $value) {

        if (!empty($prefids)) {
            $this->_db->insert(self::TABLE_PREF_DEPOT, array('PREFID' => $prefids[0], 'UID' => $this->_uid, 'PREF' => $pref, 'VALUE' => $value));
            array_shift($prefids);
        } else {
            $this->_db->insert(self::TABLE_PREF_DEPOT, array('UID' => $this->_uid, 'PREF' => $pref, 'VALUE' => $value));
        }

    }

    /**
     * Enregistre les préférences de dépot de l'utilisateur
     * @param int $uid
     * @return true
     * @throws Zend_Db_Adapter_Exception
     */
    public function savePrefDepot($uid)
    {
        $this->setUid($uid);

        // 1 - Select actuelles pref de dépot
        $sqlrequest = $this->_db->select()->from(self::TABLE_PREF_DEPOT)->where('UID=' . $this->_uid);
        $metasToDelete = $this->_db->fetchAll($sqlrequest);

        $prefids = [];

        // 3 - Suppression des anciennes metadatas
        foreach ($metasToDelete as $todel) {
            $prefids[$todel['PREF']][] = $todel['PREFID'];
        }
        self::removePrefDepot($this->getUid());

        // 2 - Ajout des nouvelles préférences (avec conservation du PREFID s'il existe)
        $this->insertLine($prefids[self::PREF_MODE], self::PREF_MODE, $this->getMode());

        foreach ($this->getDomain() as $domain) {

            if ($domain != "") {
                $this->insertLine($prefids[self::PREF_DOMAIN], self::PREF_DOMAIN, $domain);
            }
        }

        $this->insertLine($prefids[self::PREF_AUTH_DEFAULT], self::PREF_AUTH_DEFAULT, $this->getDefault_author());
        $this->insertLine($prefids[self::PREF_ROLE], self::PREF_ROLE, $this->getDefault_role());

        foreach ($this->getLaboratory() as $lab) {
            if ($lab != "") {
                $this->insertLine($prefids[self::PREF_LABO], self::PREF_LABO, $lab);
            }
        }

        foreach ($this->getInstitution() as $insti) {
            if ($insti != "") {
                $this->insertLine($prefids[self::PREF_INSTITUTION], self::PREF_INSTITUTION, $insti);
            }
        }

        $this->insertLine($prefids[self::PREF_SEELEGAL], self::PREF_SEELEGAL, $this->getSeelegal());

        return true;

    }

    /**
     * Transfere les preferences de depots d'un uid vers un autre
     * (Dans le cas d'un merge d'utilisateur sans nouveau profil, on recupere l'ancien)
     * @param int $uidTo
     * @param int $uidFrom
     * @return int
     * @throws Zend_Db_Adapter_Exception
     * static a cause de Hal_User_Merge qui n'a que des Uid...
     */
    public static function movePrefDepot($uidTo, $uidFrom) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $db->update(self::TABLE_PREF_DEPOT, array('UID' => $uidTo), 'UID = ' . $uidFrom);
    }

    /**
     * Suppression de toutes les preferences de depots de l'utilisateur
     * @param int $uid
     * @return int
     * static a cause de Ccsd_User_Merge qui n'a que des Uid...
     */
    public static function removePrefDepot($uid) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $db->delete(self::TABLE_PREF_DEPOT, "UID = $uid");
    }

    /**
     * Enregistre les préférences de mail de l'utilisateur
     * @param int $uid
     * @throws Zend_Db_Adapter_Exception
     */
    public function savePrefMail($uid)
    {
        $this->setUid($uid);

        // 1 - Suppression des anciennes préférences
        $this->_db->delete(self::TABLE_PREF_MAIL, 'UID = ' . (int) $uid);

        $refstru = $this->getStructAuth();
        $prefmail = $this->getPreferencesMail();

        // 2 - Ajout des nouvelles préférences
        foreach ($prefmail as $rightid => $value)
        {
            $bind = ['UID' => $uid, 'RIGHTID' => $rightid, 'SEND' => (int) $value];
            if ($rightid == 'adminstruct') {
                foreach ($refstru as $structid => $foovalue)
                {
                    $bind['STRUCTID'] = $structid;
                    if (in_array($structid, $prefmail['adminstruct'])){
                        $bind['SEND'] = 1;
                    } else {
                        $bind['SEND'] = 0;
                    }
                    $this->_db->insert(static::TABLE_PREF_MAIL, $bind);
                }
            } else {
                $this->_db->insert(static::TABLE_PREF_MAIL, $bind);
            }
        }

        return true;

    }

    /**
     * Enregistre les propriétés de Hal_User
     * Dans le cas d'un nouveau compte ( $isNewAccount = true ) l'utilisateur ne
     * remplit que les champs de CAS_users
     * Après validation du compte et connexion, il remplit les champs de
     * l'application
     *
     * @see Ccsd_User_Models_User::save()
     * @return int false UID de l'utilisateur modifié ou ajouté ; sinon false
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    public function save($isNewAccount = false, $forceInsert = false) {
        $uid = parent::save($forceInsert);

        if (false === $uid) {
            return false;
        }

        $this->setUid($uid);

        // on s'arrête là pour une création de compte CAS_users
        if ($isNewAccount) {
            // On crée une ligne dans les préférences de dépôt pour signifier qu'il faudra les complèter
            $this->createHasPrefDepot();
            return $this->getUid();
        }

        // création de données locales
        $data = array(
            'UID' => $this->getUid(),
            'SCREEN_NAME' => $this->getScreen_name(),
            'LANGUEID' => $this->getLangueid(),
            'NBDOCVIS' => $this->getNbdocvis(),
            'NBDOCSCI' => $this->getNbdocsci(),
            'NBDOCREF' => $this->getNbdocref()
        );


        if (false === $this->hasHalAccountData($this->getUid())) {
            // Ajout des données spécifiques

            try {
                $numOfInsertedRows = $this->_db->insert(self::TABLE_USER, $data);
            } catch (Zend_Db_Adapter_Exception $e) {
                error_log(__CLASS__ . ' ' . __FUNCTION__  . ' '. $e->getMessage());
                return false;
            }

            if ((int) $numOfInsertedRows == 1) {
                return $this->getUid();
            } else {
                return false;
            }
        } else {
            // Utilisateur existant en local
            // Modification des données spécifiques

            try {
                $this->reIndexUserDocs();
                $this->_db->update(self::TABLE_USER, $data, ['UID = ?' => $this->getUid()]);
            } catch (Zend_Db_Adapter_Exception $e) {
                error_log(__CLASS__ . ' ' . __FUNCTION__  . ' '. $e->getMessage());
                return false;
            }

            return $this->getUid();
        }
    }

    /**
     * Charge les données de l'utilisateur à partir de son $uid
     * @deprecated
     * @param int $uid
     * @return boolean
     * @throws Zend_Db_Statement_Exception
     */
    public function populateUserFromUid($uid) {
        $select = $this->_db->select()->from(['U' => self::TABLE_USER], ['UID', 'SCREEN_NAME', 'LANGUEID', 'NBDOCVIS', 'NBDOCSCI', 'NBDOCREF'])->where('U.UID = ?', $uid);
        $stmt = $select->query();
        $row = $stmt->fetch();

        // Données du compte utilisateur
        $userMapper = new Ccsd_User_Models_UserMapper ();
        $resCcsd = $userMapper->find($uid, $this);


        // Pas de compte CCSD
        if ($resCcsd == null) {
            return false;
        }

        // Première connexion, pas de données locales à HAL
        if ($row === false) {
            $this->setHasAccountData(false);
            return false;
        }

        $this->setHasAccountData(true);


        $this->hasIdhal();
        $this->HasCV();

        $this->setUid($row ['UID'])->setScreen_name($row ['SCREEN_NAME'])->setLangueid($row ['LANGUEID'])->setNbdocvis($row ['NBDOCVIS'])->setNbdocsci($row ['NBDOCSCI'])->setNbdocref($row ['NBDOCREF']);

        return true;
    }

    /**
     * Charge les données de l'utilisateur à partir du tableau de données
     *
     * @param array $data
     * @return boolean
     */
    public function populateUserFromData($data) {

        if (isset($data['UID'])
                && isset($data['SCREEN_NAME'])
                && isset($data['LANGUEID'])
                && isset($data['NBDOCVIS'])
                && isset($data['NBDOCSCI'])
                && isset($data['NBDOCREF'])) {

            $this->hasIdhal();
            $this->HasCV();
            $this->setUid($data ['UID'])->setScreen_name($data ['SCREEN_NAME'])->setLangueid($data ['LANGUEID']);
            $this->setNbdocvis($data ['NBDOCVIS']);
            $this->setNbdocsci($data ['NBDOCSCI']);
            $this->setNbdocref($data ['NBDOCREF']);

            return true;
        } else {
            return false;
        }
    }

    /**
     * Charge les préférences de dépot de l'utilisateur à partir de son $uid
     *
     * @param int $uid
     * @return boolean
     */
    public function populatePrefDepotFromUid ($uid) {
        $rows = Hal_User::fetchRows($this->getDb(), self::TABLE_PREF_DEPOT, $uid, 'PREFID');
        return $this -> populatePrefDepotFromData($rows);
    }

    /**
     * Charge les préférences de dépot de l'utilisateur à partir de son $uid
     * Return true
     *
     * @param array $data
     * @return true
     * @uses setDomain
     * @uses setMode
     * @uses setInstitution
     * @uses setAutodepot
     */
    public function populatePrefDepotFromData ($data) {

        foreach ($data as $row) {
            $method = 'set' . ucfirst($row['PREF']);

            if (!method_exists($this, $method)) {
                // On ne stoppe pas: Attention a la preference temporaire: nopref
                // Soit il faut creer un setNopref, soit ca ne derange pas...
                // TODO: Il faudrait controler les methodes appelables, a t on le droit d'appeller n'importe quel setXxxxx
                continue;
            } else {
                $this->$method($row['VALUE']);
            }
        }

        return true;
    }

    /**
     * Charge les préférences de mail de l'utilisateur à partir de son $uid
     *
     * @param array $data
     * @return boolean
     */
    public function populatePrefMailFromData ($data)
    {
        $structids = array();

        foreach ($data as $row) {
            $rightId       = $row['RIGHTID'];
            $sendField     = $row['SEND'];
            $structIdField = $row['STRUCTID'];
            if ($rightId == 'author') {
                $this->setPrefMailAuthor($sendField);
            }
            if ($rightId == 'member'){
                $this->setPrefMailMember($sendField);
            }
            if ($rightId == 'adminstruct'){
                if ($sendField == 1) {
                    $structids[] = $structIdField;
                }
            }
            if ($rightId == 'administrator'){
                /*if (!Hal_Auth::isAdministrator()){
                    $this->setPrefMailAdministrator(0);
                } else {*/
                    $this->setPrefMailAdministrator($sendField);

                //}
            }
        }
        if (is_array($structids)){
            $this->setPrefMailAdminstruct($structids);
        }
        return true;
    }
    /**
     * Récupération des données d'une table 
     *
     * @param string : table à requêter
     * @param Zend_Db_Adapter_Pdo_Mysql $db
     * @param int : identifiant de l'utilisateur dont on récupère les infos
     * @param string : ordre du select en db
     * @return array|boolean
     */
    static public function fetchRows($db, $table, $uid, $order = null)
    {
        $select = $db->select()->from(['U' => $table])->where('U.UID = ?', $uid);
        if ($order != null) {
            $select->order($order . ' ASC');
        }
        $stmt = $select->query();
        return $stmt->fetchAll();
    }


    /**
     * Creation d'un utilisateur HAL à partir d'un utilisateur CCsd
     *
     * @param Ccsd_User_Models_User
     * @param boolean $full
     * @return  Hal_User | NULL
     */

    static public function createUserFromCcsdUser($ccsdUser,$full = true)
    {

        $newUser = new Hal_User($ccsdUser->toArray());

        // Données de l'utilisateur
        $ok = true;
        $rows = Hal_User::fetchRows($newUser->getDb(), self::TABLE_USER, $newUser->getUid());
        if ((false === $rows || empty($rows)) && $full === true) {
            return null; // Première connexion, pas de données locales à HAL
        }

        // Préférences de dépôt
        if (false === ($rowsPref = Hal_User::fetchRows($newUser->getDb(), self::TABLE_PREF_DEPOT, $newUser->getUid(), 'PREFID'))) {
            //Todo: Est-ce un bon test??? Le premier ne suffit pas?
            return null; // Première connexion, pas de données locales à HAL
        }
        // Préférences des mails
        if (false === ($rowsPrefMail = Hal_User::fetchRows($newUser->getDb(), self::TABLE_PREF_MAIL, $newUser->getUid()))) {
            //Todo: Est-ce un bon test??? Le premier ne suffit pas?
            return null; // Première connexion, pas de données locales à HAL
        }

        if (empty($rowsPrefMail)){ //Si les préférences de mail sont vides on les set par défault
            $rowsPrefMail[] = ['RIGHTID' => 'member', 'STRUCTID' => null, 'SEND' => 1];
            $rowsPrefMail[] = ['RIGHTID' => 'author', 'STRUCTID' => null, 'SEND' => 1];
            $rowsPrefMail[] = ['RIGHTID' => 'administrator', 'STRUCTID' => null, 'SEND' => 1];

            foreach ($newUser->getStructAuth() as $structid => $value){
                $rowsPrefMail[] = ['RIGHTID' => 'adminstruct', 'STRUCTID' => $structid, 'SEND' => 1];
            }
        }

        // Il n'y a qu'une ligne de donnée utilisateur
        $userdata= $rows[0];
        $ok &= $newUser->populateUserFromData($userdata);
        $ok &= $newUser->populatePrefDepotFromData($rowsPref);
        $ok &= $newUser->populatePrefMailFromData($rowsPrefMail);
        if ($ok || $full === false) {
            return $newUser;
        } else {
            return null;
        }
    }


    /**
     * Création d'un utilisateur HAL par son UID
     *
     * @param int $uid
     * @param boolean $full return full user or not
     * @return Hal_User | null
     */
    static public function createUser($uid, $full = true)
    {
        $newUser = new Hal_User();

        // Données du compte utilisateur
        $userMapper = new Ccsd_User_Models_UserMapper ();
        $resCcsd = $userMapper->find($uid, $newUser);

        // Pas de compte CCSD
        if ($resCcsd == null) {
            return null;
        }

        // Données de l'utilisateur
        $ok = true;
        $rows = Hal_User::fetchRows($newUser->getDb(), self::TABLE_USER, $uid);
        if (false === $rows || empty($rows)) {
            return null; // Première connexion, pas de données locales à HAL
        }

        // Préférences de dépôt
        if (false === ($rowsPref = Hal_User::fetchRows($newUser->getDb(), self::TABLE_PREF_DEPOT, $uid, 'PREFID'))) {
            //Todo: Est-ce un bon test??? Le premier ne suffit pas?
            return null; // Première connexion, pas de données locales à HAL
        }
        // Préférences des mails
        if (false === ($rowsPrefMail = Hal_User::fetchRows($newUser->getDb(), self::TABLE_PREF_MAIL, $uid))) {
            //Todo: Est-ce un bon test??? Le premier ne suffit pas?
            return null; // Première connexion, pas de données locales à HAL
        }

        if (empty($rowsPrefMail)){ //Si les préférences de mail sont vides on les set par défault
            $rowsPrefMail[] = ['RIGHTID' => 'member', 'STRUCTID' => null, 'SEND' => 1];
            $rowsPrefMail[] = ['RIGHTID' => 'author', 'STRUCTID' => null, 'SEND' => 1];
            $rowsPrefMail[] = ['RIGHTID' => 'administrator', 'STRUCTID' => null, 'SEND' => 1];

            foreach ($newUser->getStructAuth() as $structid => $value){
                $rowsPrefMail[] = ['RIGHTID' => 'adminstruct', 'STRUCTID' => $structid, 'SEND' => 1];
            }
        }

        // Il n'y a qu'une ligne de donnée utilisateur
        $userdata= $rows[0];
        $ok &= $newUser->populateUserFromData($userdata);
        $ok &= $newUser->populatePrefDepotFromData($rowsPref);
        $ok &= $newUser->populatePrefMailFromData($rowsPrefMail);
        if ($ok || $full === false) {
            return $newUser;
        } else {
            return null;
        }
    }

    /**
     * insertion en base d'un utilisateur au niveau Cas (user CCSD)  et Hal
     *
     * @param $attributes array valeur d'initialisation de l'objet
     * @param $valid boolean valeur d'initialisation de la validité de l'utilisateur
     * @param $isNewAccount boolean
     * @param $forceInsert boolean
     * @return int un user si la sauvegarde s'est bien effectués sinon false
     */
    public static function insertUserFromIdp($attributes,$valid,$isNewAccount,$forceInsert)
    {

        $user = new self($attributes);
        $user->setValid($valid);
        $user->setTime_registered();
        $user->setScreen_name();
        $user->setLangueid(Zend_Registry::get('Zend_Locale')->getLanguage());
        $user->setPassword(Ccsd_Tools::generatePw());
        $user->setHasAccountData(true);
        $result = $user->save($isNewAccount,$forceInsert);
        if ($result === false ) return false;
        else return $user;
    }

    /**
     * Delete a user with all prefMail and PrefDepot, right...
     *
     */
     public function delete() {
         // Todo devrait etre dans un Role.php
         $this -> _db -> delete(self::TABLE_ROLE, 'UID=' . $this->getUid());
         self::removeMailPref($this->getUid());
         self::removePrefDepot($this->getUid());
         $this -> _db -> delete(self::TABLE_USER, 'UID=' . $this->getUid());
     }
    
    /** FONCTION OBSOLETE => PASSER PAR createUser
     * @see createUser
     * Recherche les propriétés d'un utilisateur HAL par son UID
     * @deprecated
     * @param integer $uid
     * @param int $uid
     * @return NULL | false |array // null si pas de compte du tout
     *                             // false si compte CAS et pas HAL
     *                             // Array correspondant aux donnees du compte HAL
     *                             // Mets a jour $this
     */
    public function find($uid) {

        // Données du compte utilisateur
        $userMapper = new Ccsd_User_Models_UserMapper ();
        $resCcsd = $userMapper->find($uid, $this);

        // Pas de compte CCSD
        if ($resCcsd == null) {
            return false;
        }

        $rows = Hal_User::fetchRows($this->getDb(), self::TABLE_USER, $uid);

        // Première connexion, pas de données locales à HAL
        if (count($rows) == 0) {
            $this->setHasAccountData(false);
            return null;
        }
        $row = $rows[0];

        // Préférences de l'utilisateur
        $this -> populatePrefDepotFromUid($this->getUid());

        // Préférences de l'utilisateur
        $rowsPrefMail = Hal_User::fetchRows($this->getDb(), self::TABLE_PREF_MAIL, $uid);
        $this->populatePrefMailFromData($rowsPrefMail);
        $this->setHasAccountData(true);
        $this->hasIdhal();
        $this->HasCV();
        $this->setUid($row ['UID'])->setScreen_name($row ['SCREEN_NAME'])->setLangueid($row ['LANGUEID']);
        $this->setNbdocvis($row ['NBDOCVIS']);
        $this->setNbdocsci($row ['NBDOCSCI']);
        $this->setNbdocref($row ['NBDOCREF']);

        return $row;
    }
        
    /**
     * Trouve la langue d'un utilisateur
     *
     * @return Hal_User
     */
    public function findUserLanguage() {
        $select = $this->_db->select()->from(self::TABLE_USER, 'LANGUEID')->where('UID = ?', $this->getUid());

        $stmt = $select->query();
        $row = $stmt->fetch();

        if ($row === false) {
            return $this->setLangueid(null);
        }
        $this->setLangueid($row ['LANGUEID']);
    }

    /**
     * Vérifie si Hal_User a des données de compte Hal
     *
     * @param int $uid
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public function hasHalAccountData($uid) {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $this->_db->select()->from(self::TABLE_USER, array(
                    'nombre' => 'COUNT(UID)'
                ))->where('UID = ?', $uid);

        $stmt = $select->query();

        $result = $stmt->fetch();

        if ($result ['nombre'] == 0) {
            $this->setHasAccountData(FALSE);
            return FALSE;
        }
        $this->setHasAccountData(TRUE);
        return TRUE;
    }

    /**
     * Vérifie si Hal_User a des données de compte Hal
     *
     * @param int $uid
     * @return boolean
     */
    public function hasPrefDepot() {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $this->_db->select()
            ->from(self::TABLE_PREF_DEPOT)
            ->where('UID = ?', $this->getUid())
            ->where('PREF = ?', 'noprefs');

        $result = $this->_db->fetchOne($select);

        if (empty($result)) {
            return true;
        }

        return false;
    }

    /**
     *
     */
    public function createHasPrefDepot()
    {
        $this -> _db -> insert(self::TABLE_PREF_DEPOT, ['UID' => $this->getUid(), 'PREF' => 'noprefs', 'VALUE' => '1']);
    }

    /**
     * @param $uid
     */
    public function deleteHasPrefDepot()
    {
        $this -> _db -> delete(self::TABLE_PREF_DEPOT, 'UID=' . $this->getUid() . ' AND PREF="noprefs"');
    }

    /**
     *
     * @return bool $_hasAccountData
     */
    public function getHasAccountData() {
        return $this->_hasAccountData;
    }

    /**
     *
     * @param boolean $_hasAccountData
     */
    public function setHasAccountData($_hasAccountData) {
        if (!is_bool($_hasAccountData)) {
            throw new InvalidArgumentException('hasAccountData : boolean attendu');
        } else {

            $this->_hasAccountData = $_hasAccountData;
            return $this;
        }
    }

    /**
     * @return Zend_Db_Adapter_Abstract|Zend_Db_Adapter_Pdo_Mysql
     */
    public function getDb() {
        return $this->_db;
    }
    
    /**
     *
     * @return array $pref_depot
     */
    public function getPreferencesDepot () {
        
        $pref_depot = array();
        foreach($this->_preferences_depot as $pref) {
            $method = 'get' . ucfirst($pref);
            $pref_depot[strtoupper($pref)] = $this->$method();
        }
        
        return $pref_depot;
    }

    public function clearPreferencesDepot()
    {
        $this->_mode = 1;
        $this->_domain = [];
        $this->_autodepot = [];
        $this->_licence = '';
        $this->_default_author = 1;
        $this->_laboratory = [];
        $this->_institution = [];
        $this->_default_role = 'aut';
        $this->_seelegal = true;
    }

    /**
     * @param int $uidTo
     * @param int $uidFrom
     * @return int
     */
    public static function moveMailPref($uidTo, $uidFrom) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $db->update(self::TABLE_PREF_MAIL, array('UID' => $uidTo), 'UID = ' . $uidFrom);
    }

    /**
     * Suppression de toutes les preferences mails de l'utilisateur
     * @param int $uid
     * @return int
     */
    public static function removeMailPref($uid) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $db->delete(self::TABLE_PREF_MAIL, "UID = $uid");
    }

    /**
     *
     * @return array $pref_mail
     */
    public function getPreferencesMail ()
    {
        $preferences = [];
        foreach($this->_preferences_mail as $pref) {
            $method = 'getPrefMail' . ucfirst($pref);
            if (method_exists($this, $method)){
                $preferences[$pref] = $this->$method();
            }
        }
        return $preferences;
    }

    /**
     *
     * @return int $_aut
     */
    public function getPrefMailAuthor() {
        return $this->_aut;
    }

    /**
     *
     * @param int $_aut
     */
    public function setPrefMailAuthor($_aut) {
        $this->_aut = $_aut;
        return $this;
    }

    /**
     *
     * @return int $_coaut
     */
    public function getPrefMailMember() {
        return $this->_coaut;
    }

    /**
     * @return Hal_User
     * @param int $_coaut
     */
    public function setPrefMailMember($_coaut) {
        $this->_coaut = $_coaut;
        return $this;
    }

    /**
     *
     * @return array $_refstru
     */
    public function getPrefMailAdminstruct() {
        return $this->_refstru;
    }

    /**
     *
     * @param array
     */
    public function setPrefMailAdminstruct($structids) {
        $this->_refstru = $structids;
        return $this;
    }

    /**
     *
     * @return int $_admin
     */
    public function getPrefMailAdministrator() {
        return $this->_admin;
    }

    /**
     *
     * @param int $_admin
     */
    public function setPrefMailAdministrator($_admin) {
        $this->_admin = $_admin;
        return $this;
    }

    /**
     *
     * @return int $_mode
     */
    public function getMode() {
        return $this->_mode;
    }

    /**
     *
     * @param int $_mode
     */
    public function setMode($_mode) {
        $this->_mode = (int) $_mode;
        return $this;
    }    
    
    /**
     *
     * @return string $_autodepot
     */
    public function getAutodepot() {
        return $this->_autodepot;
    }

    /**
     *
     * @param string $_autodepot
     */
    public function setAutodepot($_autodepot) {

        if (gettype($_autodepot) == "array") {
            $this->_autodepot = $_autodepot;
        } else {
            if (!in_array($_autodepot, $this->_autodepot)) {
                $this->_autodepot[] = $_autodepot;
            }
        }
        return $this;
    }
    
    /**
     *
     * @return string $_licence
     */
    public function getLicence() {
        return $this->_licence;
    }

    /**
     *
     * @param string $_licence
     */
    public function setLicence($_licence) {
        $this->_licence = $_licence;
        return $this;
    }
    
    /**
     *
     * @return array $_laboratory
     */
    public function getLaboratory() {
        return $this->_laboratory;
    }

    /**
     *
     * @param string | string[] $_laboratory
     */
    public function setLaboratory($_laboratory)
    {
        if (gettype($_laboratory) == "array") {
            $this->_laboratory = $_laboratory;
        } else {
            if (!in_array($_laboratory, $this->_laboratory) && $this->_laboratory != "") {
                $this->_laboratory[] = $_laboratory;
            }
        }
        return $this;
    }
        
    /**
     *
     * @return array $_institution
     */
    public function getInstitution() {
        return $this->_institution;
    }

    /**
     *
     * @param string $_institution
     */
    public function setInstitution($_institution) {
        if (gettype($_institution) == "array") {
            $this->_institution = $_institution;
        } else {
            if (!in_array($_institution, $this->_institution)) {
                $this->_institution[] = $_institution;
            }
        }
        return $this;
    }
    
    /**
     *
     * @return int $default_role
     */
    public function getDefault_role() {
        return $this->_default_role;
    }

    /**
     *
     * @param int $default_role
     */
    public function setDefault_role($default_role) {
        $this->_default_role = $default_role;
    }

    /**
     *
     * @return int $_nbdocvis
     */
    public function getNbdocvis() {
        return $this->_nbdocvis;
    }

    /**
     *
     * @param int $_nbdocvis
     * @return Hal_User
     */
    public function setNbdocvis($_nbdocvis) {
        $this->_nbdocvis = (int) $_nbdocvis;
        return $this;
    }

    /**
     *
     * @return int $_nbdocsci
     */
    public function getNbdocsci() {
        return $this->_nbdocsci;
    }

    /**
     *
     * @param int $_nbdocsci
     * @return Hal_User
     */
    public function setNbdocsci($_nbdocsci) {
        $this->_nbdocsci = (int) $_nbdocsci;
        return $this;
    }

    /**
     *
     * @return int $_nbdocref
     */
    public function getNbdocref() {
        return $this->_nbdocref;
    }

    /**
     *
     * @param int $_nbdocref
     * @return Hal_User
     */
    public function setNbdocref($_nbdocref) {
        $this->_nbdocref = (int) $_nbdocref;
        return $this;
    }

    /**
     *
     * @return int $_uid
     */
    public function getUid() {
        return $this->_uid;
    }

    /**
     *
     * @param int $_uid
     * @return Hal_User
     */
    public function setUid($_uid) {
        if ($_uid == '') {
            $this->_uid = null;
            return $this;
        }

        $this->_uid = intval(filter_var($_uid, FILTER_SANITIZE_NUMBER_INT));

        if ($this->_uid <= 0) {
            throw new InvalidArgumentException('Le UID utilisateur doit être supérieur à 0.');
        } else {
            return $this;
        }
    }

    /**
     *
     * @return string $_screen_Name
     */
    public function getScreen_name() {
        return $this->_screen_name;
    }

    /**
     *
     * @param string $_screen_name
     * @return Hal_User
     */
    public function setScreen_name($_screen_name = '') {
        if ($_screen_name == '') {
            $_screen_name = Ccsd_Tools::formatAuthor($this->getLastname(), $this->getFirstname());
        }

        $this->_screen_name = filter_var($_screen_name, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $this->_screen_name = Ccsd_Tools_String::truncate($this->_screen_name, self::SCREEN_NAME_MAX_LENGTH, null, false);

        return $this;
    }

    /**
     *
     * @return string $_langueid
     */
    public function getLangueid() {
        return $this->_langueid;
    }

    /**
     *
     * @return boolean $_default_author
     */
    public function getDefault_author() {
        return $this->_default_author;
    }

    /**
     * @return bool $_seelegal
     */
    public function getSeelegal()
    {
        return $this->_seelegal;
    }

    /**
     *
     * @param string $_langueid
     * @return Hal_User
     */
    public function setLangueid($_langueid) {
        if (!in_array($_langueid, Zend_Registry::get('languages'))) {
            try {

                $_langueid = Zend_Registry::get('Zend_Locale')->getLanguage();
            } catch (Exception $e) {
                $_langueid = null;
            }
        }
        $this->_langueid = $_langueid;

        return $this;
    }

    /**
     *
     * @param boolean $_default_author
     * @return Hal_User
     */
    public function setDefault_author($_default_author = 0) {
        if (($_default_author != 0) && ( $_default_author != 1)) {
            $_default_author = 0;
        }
        $this->_default_author = (int) $_default_author;
        return $this;
    }

    public function setSeelegal($seelegal)
    {
        $this->_seelegal = $seelegal;
        return $this;
    }

    public function setandSaveSeelegal($seelegal)
    {
        $this->_seelegal = $seelegal;
        $this->savePrefDepot($this->_uid);
    }

    /**
     *
     * @return string[] $_domain
     */
    public function getDomain() {
        return $this->_domain;
    }

    /**
     * Si param est un tableau, la liste des domaine est initialisee avec ce tableau
     * Si param est une chaine, alors on ajoute ce domaine aux domaines existants
     * @param string[]|string $_domain
     * @return Hal_User
     */
    public function setDomain($_domain) {
        
        if (gettype($_domain) == "array") {
            $this->_domain = $_domain;
        } else {
            if (!in_array($_domain, $this->_domain)) {
                $this->_domain[] = $_domain;
            }
        }
        return $this;
    }

    /**
     * Retourne la liste des collections d'un utilisateur
     * @param string $return
     * @return array
     */
    public function getCollections($return = 'collection') {
        $collections = array();
        foreach ($this->getRoles() as $roleid => $value) {
            if ($roleid == Hal_Acl::ROLE_TAMPONNEUR) {
                foreach (array_keys($value) as $sid) {
                    if ($return == 'collection') {
                        $collections [] = Hal_Site::loadSiteFromId($sid);
                    } else {
                        $collections [] = $sid;
                    }
                }
            }
        }
        return $collections;
    }


    /**
     * Reindexe les documents dont l'utilisateur est contributeur
     * @return int nombre de docs à réindexer
     */
    public function reIndexUserDocs() {
        $docs = $this->getDocuments(false, false, true);
        $arrOfDocids=[];
        foreach ($docs as $value) {
            $arrOfDocids[] = (int) $value['docid'];
        }
        Ccsd_Search_Solr_Indexer::addToIndexQueue($arrOfDocids, 'HAL: user profile updated'); //ré-indexation
        Hal_Document::deleteCaches($arrOfDocids);
        return count($arrOfDocids);
    }

    /**
     * Retourne les documents impactes par le changement sur l'utilisateur
     * Les documents indexables sont mis dans le deuxieme parametres,
     * L'ensemble des documents concernes sont retournes dans le premier parametre
     * @param array $docId2deleteCache
     * @param array $docId2reindex
     *
     */
    function getDocsToTouch(&$docId2deleteCache, &$docId2reindex) {
        $docs = $this->getDocuments(true, false, false);

        foreach ($docs as $docinfo) {
            $docid = (int) $docinfo['docid'];
            $docId2deleteCache[] = $docid;
            if (Hal_Document::isIndexable($docinfo['status'])) {
                $docId2reindex[] = $docid;
            }
        }
    }
    /**
     * Action a effectuer apres une modification d'utilisateur
     *    - effacement cache de document
     *    - reindexation de documents
     *    - ...
     */
    function postModifyUser() {
        $docId2deleteCache = [];
        $docId2reindex = [];
        $this -> getDocsToTouch($docId2deleteCache, $docId2reindex );
        if (count($docId2deleteCache)) {
            Hal_Document::deleteCaches($docId2deleteCache);
        }
        $nbDocsToBeReindex = count($docId2reindex);
        if (count($docId2reindex)) {
            Ccsd_Search_Solr_Indexer::addToIndexQueue($docId2reindex, 'HAL: user profile updated'); //ré-indexation
        }

        return $nbDocsToBeReindex;
    }

    /**
     * Retourne les documents d'un utilisateur
     * Attention: suivant group ou pas, la liste obtenue peut etre differente
     *    Group = true et visible = False
     *        Retourne les documents en status: STATUS_MYSPACE STATUS_VISIBLE STATUS_MODIFICATION STATUS_BUFFER STATUS_TRANSARXIV STATUS_VALIDATE
     *    Group = false et visible = False
     *        Retourne les documents en status: STATUS_MYSPACE STATUS_VISIBLE STATUS_MODIFICATION STATUS_BUFFER STATUS_TRANSARXIV STATUS_VALIDATE STATUS_REPLACED
     *    Group = true et visible = True
     *        Retourne les documents en status: STATUS_VISIBLE
     *    Group = false et visible = True
     *        Retourne les documents en status: STATUS_VISIBLE STATUS_REPLACED
     *
     * @param boolean $owner
     *        	dont il est le propriétaire
     * @param boolean $group
     *        	retourner les données groupées
     */
    public function getDocuments($owner = true, $group = true, $limitVisible = false) {
        $documents = array();
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->distinct()
                ->from(Hal_Document::TABLE, array('DOCID', 'IDENTIFIANT', 'VERSION', 'UID', 'DOCSTATUS', 'FORMAT', 'DATESUBMIT'))
                ->order('DATEMODIF DESC');
        if ($limitVisible) {
            $sql->where('DOCSTATUS IN (?)', array(Hal_Document::STATUS_VISIBLE, Hal_Document::STATUS_REPLACED));
        }

        if ($owner) {
            $sql2 = $db->select()->distinct()->from('DOC_OWNER', 'IDENTIFIANT')->where('UID = ?', $this->getUid());
            $docidsOwner = $db->fetchCol($sql2);
            if (count($docidsOwner)) {
                $sql->where($db->quoteInto('IDENTIFIANT IN (?)', $docidsOwner) . ' OR ' . $db->quoteInto('UID = ?', $this->getUid()));
            } else {
                $sql->where('UID = ?', $this->getUid());
            }
        } else {
            $sql->where('UID = ?', $this->getUid());
        }

        foreach ($db->fetchAll($sql) as $row) {
            $doc = array(
                'docid'       => $row ['DOCID'],
                'identifiant' => $row ['IDENTIFIANT'],
                'version'     => $row ['VERSION'],
                'uid'         => $row ['UID'],
                'date'        => $row ['DATESUBMIT'],
                'status'      => $row ['DOCSTATUS'],
            );
            if ($group) {
                if ($row['DOCSTATUS'] == Hal_Document::STATUS_MYSPACE) {
                    $g = Hal_Settings_Submissions::TYPE_MYSPACE;
                } else if ($row['DOCSTATUS'] == Hal_Document::STATUS_VISIBLE) {
                    if ($row['FORMAT'] == Hal_Document::FORMAT_FILE) {
                        // Document en ligne avec texte intégral
                        $g = Hal_Settings_Submissions::TYPE_ONLINE_FILE;
                    } else {
                        // Document en ligne sans texte intégral
                        $g = Hal_Settings_Submissions::TYPE_ONLINE_REF;
                    }
                } else if ($row['DOCSTATUS'] == Hal_Document::STATUS_MODIFICATION) {
                    if ($row['FORMAT'] == Hal_Document::FORMAT_FILE) {
                        // Document en attente de modif avec texte intégral
                        $g = Hal_Settings_Submissions::TYPE_MODIFY_FILE;
                    } else {
                        // Document en attente de modif sans texte intégral
                        $g = Hal_Settings_Submissions::TYPE_MODIFY_REF;
                    }
                } else if (in_array($row['DOCSTATUS'], [Hal_Document::STATUS_BUFFER, Hal_Document::STATUS_TRANSARXIV, Hal_Document::STATUS_VALIDATE])) {
                    if ($row['FORMAT'] == Hal_Document::FORMAT_FILE) {
                        // Document non visible avec texte intégral
                        $g = Hal_Settings_Submissions::TYPE_OFFLINE_FILE;
                    } else {
                        // Document non visible sans texte intégral
                        $g = Hal_Settings_Submissions::TYPE_OFFLINE_REF;
                    }
                } else {
                    continue;
                }
                $documents [$g] [] = $doc;
            } else {
                $documents [] = $doc;
            }
        }
        return $documents;
    }

    /**
     * Retourne le nombre de dépôt en ligne de l'utilisateur
     */
    public function getOnlineSubmissionsNb() {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        try {
            $sql = $db->select()->from(Hal_Document::TABLE, 'COUNT(*) AS NB')->where('UID = ?', $this->getUid())->where('DOCSTATUS IN (?)', array(
                Hal_Document::STATUS_VISIBLE,
                Hal_Document::STATUS_REPLACED
            ));
            return $db->fetchOne($sql);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param int $uid
     * @param int $sid
     */
    static public function logUserConnexion($uid, $sid) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        try {
            $data = array(
                'UID' => (int) $uid,
                'SID' => (int) $sid,
                'NB_CONNEXION' => 0,
                'FIRST_CONNEXION' => date('Y-m-d H:i:s')
            );
            $db->insert(self::TABLE_CONNEXION, $data);
        } catch (Exception $e) {
            // TODO traiter l'exception: log? ...
        }

        try {
            $db->update(self::TABLE_CONNEXION, array('NB_CONNEXION' => new Zend_Db_Expr('NB_CONNEXION+1')), 'UID= ' . $uid . ' AND SID = ' . $sid);
        } catch (Exception $e) {
            // TODO traiter l'exception: log? ...
        }
    }


    /**
     * Trouve un uid d'utilisateur à partir d'un identifiant externe
     * en cas de doublon : retourne le UID avec la connexion la plus récente
     * @param string $idExt
     * @param string|int identifiant d'un serveur externe par exemple 4 ou 'ORCID'
     * @return int $uid| boolean false si pas de résultat
     */
    public static function getUidFromIdExt($idExt, $serverId = null)
    {
        //  REF_IDHAL_IDEXT.ID varchar(200) CHARACTER SET utf8 NOT NULL
        if (strlen($idExt) > 200) {
            error_log(__METHOD__ . ': received REF_IDHAL_IDEXT.ID > 200 chars');
            return false;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->distinct()->from('REF_IDHAL', 'UID')
            ->joinLeft('REF_IDHAL_IDEXT', 'REF_IDHAL_IDEXT.IDHAL=REF_IDHAL.IDHAL', []);

        if ($serverId != null) {
            // recherche par ID de serveur
            if (is_int($serverId)) {
                $sql->where('SERVERID=?', $serverId);
            } elseif (is_string($serverId)) {
                // recherche par nom de serveur
                $sql->joinLeft('REF_SERVEREXT', 'REF_IDHAL_IDEXT.SERVERID=REF_SERVEREXT.SERVERID', []);
                $sql->where('NAME=?', $serverId);
            }
        }

        $sql->where('REF_IDHAL_IDEXT.ID=?', $idExt);

        $uidArr = $db->fetchAll($sql, '', Zend_Db::FETCH_COLUMN);

        $nbResults = count($uidArr);

        if ($nbResults == 1) {
            $uid = (int)$uidArr[0];
        } elseif ($nbResults == 0) {
            $uid = false;
        } elseif ($nbResults > 1) {
            if ($serverId == null) {
                // There can be only one
                $uid = self::getLatestConnectedUidFromArray($uidArr);
            } else {
                // Si le serverId est précisé on n'accepte pas les doublons
                return false;
            }
        }
        return $uid;
    }

    /**
     * Retourne les données ORCID à partir d'un token
     * @param string $token
     * @return array $data
     */
    static public function getOrcidWithToken ($token) {
        $endpoint = "https://pub.orcid.org/oauth/token";

        $params = array(
            "client_id" => "APP-O6Y5HZD2SFM7ON6Z",
            "client_secret" => "1afa525f-169d-4696-9ab2-019a0be98d22",
            "grant_type" => "authorization_code",
            "code" => $token);

        $curl = curl_init($endpoint);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER,'Content-Type: application/json');

        $postData = "";

        foreach($params as $k => $v) {
            $postData .= $k . '='.urlencode($v).'&';
        }

        $postData = rtrim($postData, '&');

        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);

        $json_response = curl_exec($curl);

        $data = json_decode($json_response, true);
        curl_close($curl);
        return $data;
    }

    /**
     * Pour un tableau de UID retourne le dernier connecté
     * Si aucun des uid ne s'est connecte, alors on rends le premier
     * @param int[] $uidArr
     * @return int UID | boolean
     */
    public static function getLatestConnectedUidFromArray(array $uidArr)
    {
        $uidArr = array_map('intval', $uidArr);
        $uidList = implode(',', $uidArr);
        $uidList = new Zend_Db_Expr('(' . $uidList . ')');

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(static::TABLE_CONNEXION, ['UID'])->where('UID IN ?', $uidList)->order('LAST_CONNEXION DESC', 'NB_CONNEXION DESC')->limit(1);
        $res = $db->fetchOne($sql);

        if (!$res) {
            return (int) $uidArr [0];
        } else {
            return (int) $res;
        }
    }

    /**
     * @param string $idhal
     * @return bool|int
     */
    static public function getUidFromIdHalUri($idhal)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        $sql = $db->select()
            ->from('REF_IDHAL', 'UID')
            ->where('URI = ?', $idhal);
        $res = $db->fetchOne($sql);
        if (!$res) {
            return false;
        }
        return (int) $res;
    }
    /**
     * @return bool
     */
    function getCv() {
        return $this->_cv;
    }
    /**
     * @return string
     */
    function getIdhal() {
        return $this->_idhal;
    }
    /**
     * @param $cv
     * @return $this
     */
    function setCv($cv) {
        $this->_cv = $cv;
        return $this;
    }
    /**
     * @param string $idhal
     * @return $this
     */
    function setIdhal($idhal) {
        $this->_idhal = $idhal;
        return $this;
    }

    /**
     * retire d'une liste d'utilisateurs ceux qui ont désactivé les alertes mails
     * @param array $uid liste des identfiiants de comptes utilisateurs
     * @param string $rightid type de rôle
     * @param int[] $structids liste des identfiants des structures pour les adminstructs
     * @return array
     */
    static public function filterUsersForAlert($uid, $rightid, $structids = [])
    {
        if (!is_array($uid)) {
            $uid = [$uid];
        }
        if (count($uid) == 0) {
            return [];
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        if ($rightid == Hal_Acl::ROLE_ADMINSTRUCT) {
            // Cas particulier pour les référents structures
            $res = [];
            if (is_array($structids) && count($structids)) {
                foreach ($uid as $id) {
                    $sql = $db->select()
                        ->from(Hal_User::TABLE_PREF_MAIL, new Zend_Db_Expr('COUNT(*)'))
                        ->where('RIGHTID = ?', $rightid)
                        ->where('UID = ?', $id)
                        ->where('STRUCTID IN (?)', $structids)
                        ->where('SEND = ?', 0);
                    if (!$db->fetchOne($sql)) {
                        $res[] = $id;
                    }
                }
            }
            return $res;
        }

        $sql = $db->select()->distinct()
            ->from(Hal_User::TABLE_PREF_MAIL, 'UID')
            ->where('RIGHTID = ?', $rightid)
            ->where('UID IN (?)', $uid)
            ->where('SEND = ?', 0);

        return array_diff($uid, $db->fetchCol($sql));
    }

    /**
     * @param Hal_Site
     * @return array
     */
    static public function getSiteTamponneurs($site)
    {
        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();

        // Chargement des tamponneurs
        $sql = $db->select()->from(self::TABLE_ROLE, 'UID')->where('SID = ?', $site->getSid())->where('RIGHTID = ?', Hal_Acl::ROLE_TAMPONNEUR);
        return $db->fetchCol($sql);
    }
}
