<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 15/01/2014
 * Time: 14:50
 */
class  Hal_Document_Logger
{
    const TABLE = 'DOC_LOG';

    const ACTION_CREATE     = 'create'; //Dépôt d'un document
    const ACTION_ANNOTATE   = 'annotate'; //Annotation du modérateur sur le dépôt
    const ACTION_DISCUSSION = 'discussion'; //Discussion entre les valideurs scientifiques
    const ACTION_ASKMODIF   = 'askmodif'; //Demande de modification
    const ACTION_MODIF      = 'modif'; //correction
    const ACTION_VALIDATE   = 'validate'; //Validation scientifique
    const ACTION_MODERATE   = 'moderate'; //Modération d'un document
    const ACTION_UPDATE     = 'update'; //Modification des métadonnées
    const ACTION_ONLINE     = 'online'; //Mise en ligne d'un document sous embargo
    const ACTION_VERSION    = 'version'; //Dépôt d'une nouvelle version
    const ACTION_ADDFILE    = 'addfile'; //Ajout d'un fichier à une notice
    const ACTION_COPY       = 'copy'; //Se servir d'un dépôt comme modèle
    const ACTION_JREF       = 'jref'; // Modification des références de publication
    const ACTION_DOMAIN     = 'domain'; //Modification des domaines scientifiques
    const ACTION_ADDTAMPON  = 'addtampon'; //Tamponnage
    const ACTION_DELTAMPON  = 'deltampon'; //Détamponnage
    const ACTION_MERGED     = 'merged';  // Le document a ete fusionne avec un autre
    const ACTION_HIDE       = 'hide'; //Refus d'un article
    const ACTION_DELETE     = 'delete'; //Suppression d'un article
    const ACTION_RELATED    = 'related'; //Liaison de la ressource
    const ACTION_NOTICE     = 'notice'; //Transformation en notice
    const ACTION_SHARE      = 'share'; //Partage de document
    const ACTION_REMODERATE = 'remod'; //Remettre en modération
    const ACTION_REQUESTFIE = 'request'; //Demande de lecture fichier sous embargo
    const ACTION_MOVED      = 'moved'; //Changement de portail
    const ACTION_EDITMODERATION = 'editModeration'; //Modification d'un document par un modérateur


    /**
     * Log des actions effectuées sur un papier
     * return true if logging is done, false if it fail
     * @param int $docid identifiant interne du papier
     * @param int $uid identifiant du compte
     * @param string $action action à logger
     * @param string $comment commentaire optionnel
     * @return bool
     */
    public static function log($docid, $uid, $action, $comment = '')
    {
        try {
            if (is_array($docid)) {
                for ($i = 0; $i < count($docid); $i++) {
                    Zend_Db_Table_Abstract::getDefaultAdapter()->insert(self::TABLE, array('DOCID' => $docid[$i], 'UID' => $uid, 'LOGACTION' => $action, 'MESG' => $comment));
                }
            } else {
                Zend_Db_Table_Abstract::getDefaultAdapter()->insert(self::TABLE, array('DOCID' => $docid, 'UID' => $uid, 'LOGACTION' => $action, 'MESG' => $comment));
            }
            return true;
        } catch (Zend_Db_Adapter_Exception $e) {
            return false;
        }
    }

    /**
     * Retourne les logs d'un papier
     * @param mixed int|array
     * @param string
     * @param string
     * @return array
     */
    public static function get($docid = 0, $identifiant = '', $limit = '')
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(array('log' => self::TABLE));

        if ( is_array($docid) ) {
            $sql->where('DOCID IN (?)', $docid);
        } else if ($docid != 0) {
            $sql->where('DOCID = ?', (int)$docid);
        } else if ($identifiant != '') {
            $sql->join(array('doc' => Hal_Document::TABLE), 'doc.DOCID = log.DOCID', null);
            $sql->where('doc.IDENTIFIANT = ?', $identifiant);
        }

        //on masque les editions par les modérateurs
        $sql->where('LOGACTION NOT IN (?)', array(self::ACTION_EDITMODERATION));

        if ($limit == 'moderate') { //on masque historique tamponnage automatique
            $sql->where('LOGACTION NOT IN (?)', array(self::ACTION_ADDTAMPON, self::ACTION_DELTAMPON, 'tampon'));
            $sql->where('log.UID != 100000');
        } else if ($limit == 'view') { //on masque historique annotation
            $sql->where('LOGACTION NOT IN (?)', array(self::ACTION_ANNOTATE));
        }

        $sql->order('DATELOG DESC');

        $data = array();
        $infoUsers = array();
        foreach($db->fetchAll($sql) as $row) {
            $log = array();
            if (!array_key_exists($row['UID'], $infoUsers)) {
                $user = Hal_User::createUser($row['UID']);
                // suite à fusion l'utilisateur peut ne plus exister
                if ($user instanceof Hal_User) {
                    $infoUsers[$row['UID']] = [
                        'FULLNAME' => $user->getFullName(),
                        'EMAIL' => $user->getEmail(),
                        'USERNAME' => $user->getUsername(),
                        'UID' => $row['UID'],
                    ];
                }
            }
            $log['USER'] = $infoUsers[$row['UID']];
            $log['DATE'] = $row['DATELOG'];

            $log['ACTION'] = $row['LOGACTION'];
            if ($log['ACTION'] == self::ACTION_ADDTAMPON || $log['ACTION'] == self::ACTION_DELTAMPON) {
                $sql = $db->select()->from(Hal_Site::TABLE, 'CONCAT_WS("", SITE, " - ", NAME)')->where('SID = ?', (int) $row['MESG'])->where('TYPE = ?', Hal_Site::TYPE_COLLECTION);
                $res = $db->fetchOne($sql);
                if ($res != '') {
                    $log['COMMENT'] = $res;
                } else {
                    $log['COMMENT'] = $row['MESG'];
                }
            } else {
                $log['COMMENT'] = $row['MESG'];
            }
            $data[] = $log;
        }
        return $data;
    }

    /**
     * Copie de logs d'un docid vers un autre
     * @param int $fromid
     * @param int $toid
     * @throws Zend_Db_Adapter_Exception
     */
    public static function copyLogs($fromid, $toid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->update(self::TABLE, ['DOCID' => $toid], ['DOCID = ? '=>$fromid]);
    }

    /**
     * Retourne le dernier commentaire laissé sur un dépôt
     * @param int $docid
     * @param string $action
     * @return string
     */
    public static function getLastComment($docid, $action = '')
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, 'MESG')->where('DOCID = ?', (int) $docid)->order('DATELOG DESC')->limit(1);
        if ($action != '') {
            $sql->where('LOGACTION = ?', $action);
        }
        return $db->fetchOne($sql);
    }

    /**
     * Retourne true si le document a déjà eu cette action, false sinon
     * @param int $docid
     * @param string $action
     * @return boolean
     */
    public static function hasAction($docid, $action = '')
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE)->where('DOCID = ?', (int) $docid);
        if ($action != '') {
            $sql->where('LOGACTION = ?', $action);
        } else {
            return false;
        }

        if ($db->fetchOne($sql)){
            return true;
        }

        return false;
    }
}