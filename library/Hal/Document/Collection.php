<?php
/**
 * Gestion des collections d'un article
 * User: yannick
 * Date: 09/01/2014
 * Time: 11:20
 */

class Hal_Document_Collection
{
    const TABLE= 'DOC_TAMPON'; // Table des collections du document

    /**
     * Retourne toutes les collections d'un document
     * @param int $docid
     * @return Hal_Site_Collection[]
     */
    static public function getCollections($docid)
    {
        $res = array();
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, 'SID')
            ->where('DOCID = ?', (int) $docid)
            ->order('DATESTAMP ASC');
        foreach($db->fetchCol($sql) as $sid) {
            $collection = Hal_Site::loadSiteFromId($sid);
            if ($collection->getSid() != 0) {
                $res[] = $collection;
            }
        }
        return $res;
    }

    /**
     * Ajoute une collection à un article
     * @param int $docid identifiant de l'article
     * @param int $sid identifiant de la collection
     * @param int $uid identifiant de l'utilisateur
     * @return bool
     */
    static public function add($docid, $sid, $uid = 100000, $update=true)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        //Test de la présence de la collection pour le document
        $sql = $db->select()->from(self::TABLE, 'COUNT(*) AS NB')
            ->where('DOCID = ?', (int) $docid)
            ->where('SID = ?', (int) $sid);
        if ($db->fetchOne($sql) == 0) {
            $bind = array(
                'DOCID' =>  $docid,
                'SID'   =>  $sid,
                'UID'   =>  $uid,
            );
            $db->insert(self::TABLE, $bind);

            //Détamponnage des autres versions du documents
            $sqlId = $db->select()->from(Hal_Document::TABLE, 'IDENTIFIANT')
                ->where('DOCID = ?', (int) $docid);
            $sql = $db->select()->from(Hal_Document::TABLE, 'DOCID')
                ->where('DOCID != ?', (int) $docid)
                ->where('IDENTIFIANT = ?', $db->fetchOne($sqlId));
            foreach($db->fetchCol($sql) as $doc) {
                self::del($doc, $sid, $uid);
            }

            if ($uid != 100000) {
                //Cas du tamponnage manuel, on tamponne les collections supérieures automatiques
                foreach(Hal_Site_Collection::getCollectionsSup($sid) as $parentsid) {
                    self::add($docid,$parentsid, $uid);
                }
            }

            //Log
            Hal_Document_Logger::log($docid, $uid, Hal_Document_Logger::ACTION_ADDTAMPON, $sid);
            if ( $update ) {
                //Changement de la date de modif du dépôt
                Hal_Document::changeDateModif($docid);
                //Suppression des caches
                Hal_Document::deleteCaches($docid, array('phps', 'tei', 'json'));
                //Réindexation
                Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid));
            }
            return true;
        } else {
            //Le document est déjà tamponné
            return false;
        }
    }

    /**
     * Retire une collection à un article
     * @param int $docid  // identifiant de l'article
     * @param int $sid    // identifiant de la collection
     * @param int $uid    // identifiant de l'utilisateur
     */
    static public function del($docid, $sid, $uid = 100000)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        //Test de la présence de la collection pour le document
        $sql = $db->select()->from(self::TABLE, 'COUNT(*) AS NB')
            ->where('DOCID = ?', (int) $docid)
            ->where('SID = ?', (int) $sid);
        if ($db->fetchOne($sql) == 1) {
            $db->delete(self::TABLE, 'DOCID = ' . (int) $docid . ' AND SID = ' . (int) $sid);
            //Log
            Hal_Document_Logger::log($docid, $uid, Hal_Document_Logger::ACTION_DELTAMPON, $sid);
            //Changement de la date de modif du document
            Hal_Document::changeDateModif($docid);
            Hal_Document::deleteCaches($docid, array('phps', 'tei', 'json'));
            Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid));

            return true;
        }
        //le document n'est pas tamponné
        return true;
    }

    /**
     * Indique si un document a été détamponné par gestionnaire de collection
     * @param int $docid // identifiant du document
     * @param int $sid   // identifiant de la collection
     * @return bool
     */
    static public function isDeleted($docid, $sid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(Hal_Document_Logger::TABLE, 'COUNT(*) AS NB')
            ->where('DOCID = ?', (int) $docid)
            ->where('UID != 100000')
            ->where('LOGACTION = ?', Hal_Document_Logger::ACTION_DELTAMPON)
            ->where('MESG = ?', $sid);
        return $db->fetchOne($sql);
    }

    /**
     * On masque le document de la lsite des documents à tamponner de l'utilisateur
     * @param $docid
     * @param $sid
     * @return string
     */
    static public function hide($docid, $sid, $uid)
    {
        $res = false;
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        try {
            $bind = array(
                'DOCID' => $docid,
                'SID' => $sid,
                'UID' => $uid
            );
            $res = $db->insert(Hal_Site_Collection::TABLE_HIDDEN_DOC, $bind);
        } catch(Exception $e) {}

        return $res;
    }

    /**
     * Transfert des collections d'un document sur un autre
     * @param $deletedDocid
     * @param $docid
     */
    public static function transferColl ($deletedDocid, $docid)
    {
        try {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $db->update(self::TABLE, array('DOCID' => $docid) , 'DOCID = ' . $deletedDocid);
        } catch (Exception $e) {
            return false;
        }
    }
}