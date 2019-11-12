<?php

class Hal_User_Library {
    
    const TABLE = "USER_LIBRARY_DOC";
    const TABLE_SHELF = 'USER_LIBRARY_SHELF';
    
    public $uid;
    public $documents;
    
    protected $_db;
    
    public function __construct($options = array()) {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $this->setOptions($options);
    }
    
	/**
     * Définition des options de la page
     * @param array $options
     */
    public function setOptions($options = array())
    {
        foreach ($options as $option => $value) {
            switch(strtolower($option)) {
                case 'uid' :
                    $this->uid = (int)$value;
                    break;                    
            }
        } 
    }
    
    public function getDocs () {
        if (!$this->uid) {
            return false;
        }
        
        $shelfs = $this->getShelfs();
        $docs = array();
        
        foreach($shelfs as $shelf) {
            
            $docs[$shelf['LIBSHELFID']] = array(
                "lib" => $shelf['LIB'],
                "dateCreation" => $shelf['DATE_CREATION'],
                "shelfUId" => $shelf['UID'],
                "documents" => array()
            );
            
            $sql = $this->_db->select()
                        ->from(array('uld' => self::TABLE))
                        ->joinLeft(array('d' => 'DOCUMENT'), 'uld.IDENTIFIANT = d.IDENTIFIANT', array('VERSION' => 'MAX(d.VERSION)', 'DOCID'))
                        ->where('uld.UID = ?', $this->uid)
                        ->where('uld.LIBSHELFID = ?', $shelf['LIBSHELFID'])
                        ->group('uld.LIBDOCID');
                        
            $result = $this->_db->fetchAll($sql);

            if (count($result) > 0) {
                foreach ($result as $row) {
                    $document = Hal_Document::find($row['DOCID']);
                    if ( $document instanceof Hal_Document ) {
                        $docs[$shelf['LIBSHELFID']]['documents'][$row['LIBDOCID']] = ['uid' => $row['UID'], 'document' => $document->getCitation('full'), 'identifiant' => $row['IDENTIFIANT']];
                    }
                }
            }
        }
        
        $this->documents = $docs;
        return $docs;
    }
    
    public function getShelfs () {
        if (!$this->uid) {
            return false;
        }
        
        $sql = $this->_db->select()
                       ->from(array('uls' => self::TABLE_SHELF))
                       ->where('uls.UID = ?', $this->uid)
                       ->order(array('DESC' => 'uls.UID'));
                       
        return $this->_db->fetchAll($sql);
    }
    
    public function whereAreSavedMyDocument ($docIdentifiant) {
        $sql = $this->_db->select()
                       ->from(array('uld' => self::TABLE), array('shelfId' => 'LIBSHELFID'))
                       ->where('uld.IDENTIFIANT = ?', $docIdentifiant);
        return $this->_db->fetchCol($sql);
    }

    /**
     * Retourne les étagères sur lesquelles se trouve le document $identifiant pour l'utilisateur $uid
     * @param $identifiant
     * @param $uid
     * @return array
     */
    static public function getShelfIds($identifiant, $uid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE, 'LIBSHELFID')
            ->where('IDENTIFIANT = ?', $identifiant)
            ->where('UID = ?', $uid);
        return $db->fetchCol($sql);
    }

    /**
     * @param array $options
     * @param bool $findDuplicate
     * @return bool|int
     */
    public function addShelf($options = array(), $findDuplicate = true) {
        try {
            $stmt = $this->_db->query('INSERT INTO '.self::TABLE_SHELF.' (`LIB`, `UID`) VALUES (?, ?)', array(trim($options['shelfName']), (int)$this->uid));
            if ( $stmt->rowCount() == 1 ) {
                return (int) $this->_db->lastInsertId();
            }
        } catch (Zend_Db_Statement_Exception $e ) {
            if ( $findDuplicate && $e->getCode() == 23000 ) { // doublon
                $sql = $this->_db->select()->from(self::TABLE_SHELF, 'LIBSHELFID')->where('LIB = ?', trim($options['shelfName']));
                $id = $this->_db->fetchOne($sql);
                if ($id) {
                    return (int)$id;
                }
            }
        }
        return false;
    }

    /**
     * @param array $options
     * @return int
     * @throws Zend_Db_Statement_Exception
     */
    public function addDocument($options = array()) {
        if (isset($options['docIdentifiant']) && $options['docIdentifiant']!='') {
            $shelfId = 0;
            if (isset($options['shelfName']) && trim($options['shelfName']) != '') {
                $shelfId = $this->addShelf(array('shelfName'=>$options['shelfName']));
            } else if (isset($options['shelfId']) && $options['shelfId']) {
                $shelfId = $options['shelfId'];
            }
            if ( $shelfId ) {
                $stmt = $this->_db->query('INSERT IGNORE INTO '.self::TABLE.' (`UID`, `LIBSHELFID`, `IDENTIFIANT`) VALUES (?, ?, ?)', array((int)$this->uid, (int)$shelfId, trim($options['docIdentifiant'])));
                return $stmt->rowCount();
            } else {
                return 0;
            }
        } else {
            return 0;
        }
    }

    /**
     * @param int $shelfId
     * @param string $shelfName
     * @return bool
     * @throws Zend_Db_Statement_Exception
     */
    public static function editShelfName ($shelfId, $shelfName) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $stmt = $db->query('UPDATE IGNORE '.self::TABLE_SHELF.' SET `LIB` = ? WHERE LIBSHELFID = ?', array(trim($shelfName), (int)$shelfId));
        if ( $stmt->rowCount() == 1 ) {
            return $shelfId;
        } else {
            return false;
        }
    }

    /**
     * @param int $libdocid
     * @return int
     */
    public static function delDocument($libdocid) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $db->delete(self::TABLE, "LIBDOCID = ".(int)$libdocid);
    }

    /**
     * @param int $libdocid
     * @return int
     */
    public static function delShelf($libshelfid) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete(self::TABLE, "LIBSHELFID = ".(int)$libshelfid);
        return $db->delete(self::TABLE_SHELF, "LIBSHELFID = ".(int)$libshelfid);
    }

    /**
     * @param int $libdocid
     * @return int
     */
    public static function count($libshelfid) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, new Zend_Db_Expr('count(*)'))->where("LIBSHELFID = ".(int)$libshelfid);
        return $db->fetchOne($sql);
    }

    /**
     * Modifie un identifiant pour toutes les bibliothèques d'un document en base
     * @param string $sOldIdent : ancien identifiant du document
     * @param string $sNewIdent : nouvel identifiant du document
     * @return boolean
     */
    public function updateIdentifiant($sOldIdent, $sNewIdent)
    {
        if (!isset($sOldIdent) || !isset($sNewIdent) || !is_string($sOldIdent) || !is_string($sNewIdent)) {
            return false;
        }
        $bind = [
            'IDENTIFIANT' => $sNewIdent,
        ];
        try {
            return $this->_db->update(self::TABLE, $bind, ['IDENTIFIANT = ?' => $sOldIdent]);
        } catch (Exception $e) {
            return false;
        }
    }

}