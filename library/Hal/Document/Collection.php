<?php
/**
 * Gestion des TAMPONS d'un article
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
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE)
            ->where('DOCID = ?', (int) $docid)
            ->join(Hal_Site::TABLE, self::TABLE . ".SID = " . Hal_Site::TABLE . ".SID")
            ->order('DATESTAMP ASC');

        $collections = [];
        foreach($db->fetchAll($sql) as $row) {
            $site = Hal_Site::rowdb2Site($row);
            $collections[$site->getSid()]  = $site;
        }
        return $collections;
    }

    /**
     * Ajoute une collection à un article
     * @param int $docid identifiant de l'article
     * @param Hal_Site_Collection $site identifiant de la collection
     * @param int $uid identifiant de l'utilisateur
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     * @throws Zend_Db_Statement_Exception
     */
    static public function add($docid, $site, $uid = 100000, $update=true)
    {
        $sid = $site->getSid();
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        //Test de la présence de la collection pour le document
        try {
            $bind = array(
                'DOCID' =>  $docid,
                'SID'   =>  $sid,
                'UID'   =>  $uid,
            );
            try {
                $db->insert(self::TABLE, $bind);
            } catch (Zend_Db_Statement_Exception $e) {
                if ($e->getCode() == 23000) { // Duplicate Key: stamp already put but maybe not indexed
                    Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid));
                    return False;
                }
                throw $e;
            }

            //Détamponnage des autres versions du documents
            $sqlId = $db->select()->from(Hal_Document::TABLE, 'IDENTIFIANT')
                ->where('DOCID = ?', (int) $docid);
            $sql = $db->select()->from(Hal_Document::TABLE, 'DOCID')
                ->where('DOCID != ?', (int) $docid)
                ->where('IDENTIFIANT = ?', $db->fetchOne($sqlId));
            foreach($db->fetchCol($sql) as $doc) {
                self::del($doc, $site, $uid);
            }

            if ($uid != 100000) {
                //Cas du tamponnage manuel, on tamponne les collections supérieures automatiques
                foreach($site->getAncestors() as $collParent) {
                    self::add($docid,$collParent, $uid);
                }
            }
            // Mise a jour du patrouillage si necessaire.
            // TODO : devrait avoir l'identifiant, pas le docID
            $site->patrolMaybe($docid);
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

        } catch (Zend_Db_Adapter_Exception $e) {
             if ($e->getCode() == 23000) { // Duplicate Key: collection allready in db
                return False;
            }
            //Le document est déjà tamponné
            throw $e;
        }
    }

    /**
     * Retire une collection à un article
     * @param int $docid  // identifiant de l'article
     * @param Hal_Site $site    // identifiant de la collection
     * @param int $uid    // identifiant de l'utilisateur
     * @return bool  (toujours true???)
     */
    static public function del($docid, $site, $uid = 100000)
    {
        $sid = $site->getSid();
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