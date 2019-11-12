<?php

/**
 * Fusion des profils HAL
 */
Class Hal_User_Merge extends Ccsd_User_Merge {

    /**
     * Table des profils
     */
    const PROFILE_TABLE = 'USER';

    /**
     * Table des token de fusion de compte
     */
    const USER_MERGE_TOKEN = 'USER_MERGE_TOKEN';
    const UIDFROM = 'UIDFROM';
    const UIDTO = 'UIDTO';
    const TOKEN = 'TOKEN';
    const DATE_CREATION = 'DATE_CREATION';


    /**
     * Table où loguer les fusions
     */
    const USER_MERGE_LOG_TABLE = 'USER_MERGE_LOG';

    /**
     * Table à ne pas évaluer ni modifier pour les fusions
     * @var array
     */
    protected $_tablesBlacklist = ['USER', 'USER_PREF_DEPOT', 'USER_PREF_MAIL', 'USER_CONNEXION', 'DOC_STAT_COUNTER'];

    /**
     * Constructeur
     */
    public function __construct() {
        parent::__construct();
        $this->setApplicationUsersTable(self::PROFILE_TABLE);
        $this->setUserMergeLogTable(self::USER_MERGE_LOG_TABLE);

        return $this;
    }

    /**
     * @param int $uidToMerge
     * @param int $uidToKeep
     * @return Hal_User_Merge
     */
    static public function createFromUids($uidToMerge, $uidToKeep)
    {
        $um = new Hal_User_Merge();

        $um->setUidFrom($uidToMerge);
        $um->setUidTo($uidToKeep);

        return $um;
    }

    /**
     * @param string $token
     * @return Hal_User_Merge|null
     */
    static public function createFromToken($token)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::USER_MERGE_TOKEN)->where(self::TOKEN.' = ?', $token);
        $res = $db->fetchAll($sql);

        if (empty($res)) {
            return null;
        }

        return Hal_User_Merge::createFromUids($res[0][self::UIDFROM], $res[0][self::UIDTO]);
    }

    /**
     * Replace le profil de l'utilisateur cible par celui de l'utilisateur source
     * Pour le cas ou l'utilisateur cible n'a pas de profil dans l'application
     * Attention: l'existence du profil destination n'est pas verifiee
     * @return int nombre de lignes modifiées sur l'ensemble des tables...
     * @throw Zend_Db_Exception $e
     */
    public function moveUserProfile() {
        $nb = 0;
        $nb += Hal_User::moveMailPref ($this->getUidTo(), $this->getUidFrom());
        $nb += Hal_User::movePrefDepot($this->getUidTo(), $this->getUidFrom());
        $nb += parent::moveUserProfile();
        return (int) $nb;
    }

    /**
     * Delete le profil de l'utilisateur cible par celui de l'utilisateur source
     * Pour le cas ou l'utilisateur cible n'a pas de profil dans l'application
     * Attention: l'existence du profil destination n'est pas verifiee
     * @return int nombre de lignes modifiées sur l'ensemble des tables...
     * @throw Zend_Db_Exception $e
     */
    public function removeUserProfile() {
        $nb = 0;
        $nb += Hal_User::removeMailPref ($this->getUidTo());
        $nb += Hal_User::removePrefDepot($this->getUidTo());
        $nb += parent::removeUserProfile();
        return (int) $nb;
    }

    /**
     * Copy de administrate controller....
     * @throws Zend_Db_Statement_Exception
     * @return Ccsd_FlashMessenger
     */
    public function replaceUserProfile() {

        $toUser   = new Hal_User(['UID' => $this->getUidTo()]);
        $fromUser = new Hal_User(['UID' => $this->getUidFrom()]);

        $fromUidhasHalAccountData = $fromUser->hasHalAccountData($fromUser->getUid());
        $toUidhasHalAccountData   = $toUser->hasHalAccountData($toUser->getUid());

        if ((! $toUidhasHalAccountData) && $fromUidhasHalAccountData) {
            $overwriteProfileResult = $this->moveUserProfile();
            if ($overwriteProfileResult >= 1) {
                return new Ccsd_FlashMessenger('success', 'Profil utilisateur source déplacé vers le profil cible');
            } else {
                return new Ccsd_FlashMessenger('danger', 'Échec du déplacement du profil utilisateur source vers le profil cible');
            }
        }

        // si chaque profil existe
        if ($toUidhasHalAccountData && $fromUidhasHalAccountData) {
            // l'utilisateur cible a déjà un profil
            $deleteProfileResult = $this->removeUserProfile();
            if ($deleteProfileResult >= 1) {
                return new Ccsd_FlashMessenger('success', 'Profil utilisateur supprimé');
            } else {
                return new Ccsd_FlashMessenger('danger', 'Échec de la suppression du profil utilisateur');
            }
        }

        // si pas de profil pour l'utilisateur source ni pour l'utilisateur de destination
        if ((! $toUidhasHalAccountData) && (! $fromUidhasHalAccountData)) {
            $msg = new Ccsd_FlashMessenger('info', "Les données liées de l'application ont été migrées mais l'utilisateur de destination n'a pas de profil HAL.");
            $msg -> addMessage('info', "Il doit se connecter pour se crééer un profil HAL.") ;
            return $msg;
        }

        // si pas de profil HAL pour l'utilisateur source et si il y a un profil de destination on ne fait rien
        if ($toUidhasHalAccountData && ( ! $fromUidhasHalAccountData)) {
            return new Ccsd_FlashMessenger('info', "Les données liées de l'application ont été migrées mais l'utilisateur source n'a pas de profil HAL.");
        }

        Ccsd_Tools::panicMsg(__FILE__,__LINE__, 'Tests not exhautive! ...');
        return null;
    }

    /**
     * Création d'un token pour le couple UIDTO/UIDFROM
     */
    public function createToken()
    {
        $token = sha1(time() . uniqid(rand(), true));

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->insert(self::USER_MERGE_TOKEN, array(self::UIDFROM => $this->getUidFrom(), self::UIDTO => $this->getUidTo(), self::TOKEN => $token, self::DATE_CREATION => date('Y-m-d H:i:s')));

        return $token;
    }

    /**
     * On supprime tous les tokens plus vieux que la date passée en paramètre
     * @param $date
     * @return int
     */
    static public function cleanTokens($date = null)
    {
        // Par défaut on supprime ce qui a plus de 24h
        if ($date == null) {
            $date = date("Y-m-d", strtotime("-1 day", strtotime(date('Y-m-d'))));
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $db->delete(self::USER_MERGE_TOKEN, self::DATE_CREATION.' < CAST("'.$date.'" as datetime)');
    }
}
