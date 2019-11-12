<?php
/**
 * Authentification sur HAL
 * @author Yannick Barborini
 *
 */
class Hal_Auth extends Ccsd_Auth
{

    /**
     * Retourne le nom de l'utilisateur à afficher
     * @return mixed
     */
    public static function getScreenName()
    {
        return self::getInstance()->getIdentity()->getScreen_name();
    }

    /**
     * Retourne la langue de l'utilisateur
     */
    public static function getLangueid ()
    {
        return self::getInstance()->getIdentity()->getLangueid();
    }


    /* ------------------------------------------------------------------------------------------------------------- */
    /* -- PRIVILEGES -- */
    /* ------------------------------------------------------------------------------------------------------------- */

    /**
     * Retourne les privilèges d'un utilisateur pour le site courant
     * @return array|void
     */
    public static function getRoles()
    {
        $roles = array();
        if (self::isLogged()) {
            /**
             * @var \Ccsd\Auth\Adapter\AdapterInterface $adapter
             */
            $adapter = self::getInstance();
            /** @var Hal_User $identity */
            $identity = $adapter->getIdentity();
            $userRoles = $identity->getRoles();
            if (is_array($userRoles)) {
                foreach ($userRoles as $roleId => $data) {
                    if (in_array($roleId, array(Hal_Acl::ROLE_ADMIN, Hal_Acl::ROLE_MODERATEUR, Hal_Acl::ROLE_VALIDATEUR)) && is_array($data)) {
                        foreach($data as $sid => $val) {
                            if (0 == SITEID || $sid == SITEID || $sid == 'all') {
                                $roles[] = $roleId;
                            }
                        }
                    } else if ($roleId == Hal_Acl::ROLE_TAMPONNEUR) {
                        // Droit sur le site de collection actuel
                        // Si pas un site de collection, alors pas de droit...
                        if (defined('MODULE') && defined('SPACE_COLLECTION') && MODULE == SPACE_COLLECTION) {
                            $site = Hal_Site_Collection::getCurrent();
                            $currentSid = $site->getSid();
                            foreach ($data as $sid => $val) {
                                if ($sid == $currentSid || $sid == 'all') {
                                    $roles[] = $roleId;
                                }
                            }
                        }
                    } else {
                        $roles[] = $roleId;
                    }
                }
            }
            if (count($roles) == 0) {
                $roles[] = Hal_Acl::ROLE_MEMBER;

            }
        } else {
            $roles[] = Hal_Acl::ROLE_GUEST;
        }
        return array_unique($roles);
    }

    /**
     * Retourne l'ensemble des privilèges d'un utilisateur (avec les détails des rôles)
     * @return array
     */
    public static function getDetailsRoles($roleId = null)
    {
        $roles = array();
        if (self::isLogged()) {
            $roles = self::getInstance()->getIdentity()->getRoles($roleId);
            if (is_array($roles)) {
            	asort($roles);
            }
        }
        return $roles;
    }

    /**
     * Vérifie les privilèges d'un utilisateur
     * @param $role
     * @return bool
     */
    static public function is($role)
    {
        return in_array($role, self::getRoles());
    }

    /**
     * Indique si l'utilisateur connecté est administrateur de la plateforme
     * @return bool
     */
    static public function isHALAdministrator()
    {
        return self::is(Hal_Acl::ROLE_HALADMIN);
    }

    /**
     * Indique si l'utilisateur connecté est administrateur du portail courant
     * @return bool
     */
    static public function isAdministrator()
    {
        return array_key_exists(SITEID, self::getDetailsRoles(Hal_Acl::ROLE_ADMIN)) || array_key_exists(0, self::getDetailsRoles(Hal_Acl::ROLE_ADMIN)) || self::isHALAdministrator();
    }

    /**
     * Indique si l'utilisateur connecté a le role d'administrateur d'au moins un portail
     * @return bool
     */
    static public function hasAdministratorRole()
    {
        return (self::is(Hal_Acl::ROLE_ADMIN) || self::isHALAdministrator());
    }

    /**
     * Indique si l'utilisateur connecté est référent pour une structure (laboratoire)
     * @param int[] $structid
     * @return bool
     */
    static public function isAdminStruct($structid = null)
    {
        if (self::is(Hal_Acl::ROLE_ADMINSTRUCT)) {
            if ($structid != null) {
                if (is_array($structid)) {
                    return count(array_intersect($structid, array_keys(self::getDetailsRoles(Hal_Acl::ROLE_ADMINSTRUCT)))) > 0;
                } else {
                    return array_key_exists($structid, self::getDetailsRoles(Hal_Acl::ROLE_ADMINSTRUCT));
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Retourne les id des structures gérées par l'utilisateur connecté
     * @param bool $returnId
     * @return array
     */
    static public function getStructId($returnId = true)
    {
        return $returnId ? array_keys(self::getDetailsRoles(Hal_Acl::ROLE_ADMINSTRUCT)) : self::getDetailsRoles(Hal_Acl::ROLE_ADMINSTRUCT);
    }

    /**
     * Indique si l'utilisateur connecté a le role de tamponneur
     * Si l'identifiant d'une collection est donnee, indique si l'utilisateur connecte est tamponneur de cette collection
     * @param int|string  $sid
     * @return bool
     */
    static public function isTamponneur($sid = 0)
    {
        if (self::isHALAdministrator()) {
            return true;
        }

        //pas de droit pour les portails sans dépôt
        $oSite = Hal_Site::getCurrent();
        $oSettings = Hal_Site_Settings_Portail::loadFromSite($oSite);
        if (!$oSettings->getSubmitAllowed()) {
            return false;
        }

        $whereTamponneur = self::getDetailsRoles(Hal_Acl::ROLE_TAMPONNEUR);

        if ($whereTamponneur) {
            // A des droit de tamponnage
            if ($sid !== 0) {
                if (is_numeric($sid)) {
                    return array_key_exists($sid, $whereTamponneur);
                } else {
                    return in_array($sid, $whereTamponneur);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Retourne les tampons gérés par l'utilisateur
     * @param bool $returnId
     * @return array
     */
    static public function getTampon($returnSid = true)
    {
        return $returnSid ? array_keys(self::getDetailsRoles(Hal_Acl::ROLE_TAMPONNEUR)) : self::getDetailsRoles(Hal_Acl::ROLE_TAMPONNEUR);
    }

    /**
     * Indique si l'utilisateur connecté est valideur scientifique
     * @param int $sid
     * @return bool
     */
    static public function isValidateur($sid = 0)
    {
        if (self::is(Hal_Acl::ROLE_VALIDATEUR)) {
            if ($sid !== 0) {
                return array_key_exists($sid, self::getDetailsRoles(Hal_Acl::ROLE_VALIDATEUR)) || array_key_exists(0, self::getDetailsRoles(Hal_Acl::ROLE_VALIDATEUR));
            }
            return true;
        }
        return false;
    }

    /**
     * Indique si l'utilisateur connecté est valideur technique
     * @param int $sid
     * @return bool
     */
    static public function isModerateur($sid = 0)
    {
        if (self::is(Hal_Acl::ROLE_MODERATEUR)) {
            if ($sid !== 0) {
                return array_key_exists($sid, self::getDetailsRoles(Hal_Acl::ROLE_MODERATEUR)) || array_key_exists(0, self::getDetailsRoles(Hal_Acl::ROLE_MODERATEUR));
            }
            return true;
        }
        return false;
    }

    /**
     * Indique si l'utilisateur est un patrouilleur pour la collection
     * @param int $sid
     * @return bool
     */
    static public function isPatroller($sid = 0)
    {
        return self::isModerateur($sid);
    }

    /** Renvoie les informations sur le patrouillage: globalement les filtres sur les documents a patrouiller
     * @return array
     */
    static public function getPatrollerDetails()
    {
        return self::getModerateurDetails();
    }

    /** Renvoie les informations sur la moderation: globalement les filtres sur les documents a moderer
     * @return array
     */
    static public function getModerateurDetails()
    {
        $res = array();
        foreach (self::getDetailsRoles(Hal_Acl::ROLE_MODERATEUR) as $sid => $details) {
            unset($details['site']);
            $infos = array();
            if (count($details) > 0) {
                foreach($details as $metaname => $metavalue) {
                    $infos[$metaname] = array_keys($metavalue);
                }
            }
            $res[$sid] = $infos;
        }
        return $res;
    }

    /**
     * Retourne les étagères de la bibliotèque de l'utilisateur
     */
    static public function getShelves()
    {
        $library = new Hal_User_Library(array('uid' => Hal_Auth::getUid()));
        return $library->getShelfs();
    }

    /**
     * Retourne l'idhal d'un utilisateur
     *
     * @return int
     */
    static public function getIdHAL()
    {
        $cv = new Hal_Cv(0, '', Hal_Auth::getUid());
        $cv->load(false);
        return (int)$cv->getIdHal();
    }

    /**
     * Indique si l'utilisateur peut modifier une structure lock
     * @param int[] $struct
     * @return bool
     */
    static public function canModifyStructLock($structid)
    {
        if (self::isHALAdministrator()) {
            return true;
        } else if (self::isAdminStruct($structid)){
            return true;
        } else {
            return false;
        }
    }
}