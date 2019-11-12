<?php


namespace Hal;

/**
 * A posteriori moderation is called Patrolling
 */
class Patrol
{
    const TABLE = "DOC_PATROL";

    const IDFIELD      = "IDENTIFIANT";
    const SITEIDFIELD  = "SID";
    const STATUSFIELD  = "PSTATUS";
    const VERSIONFIELD = "PVERSION";
    const DATEFIELD    = "PDATE";
    const UIDFIELD     = "UID";

    const PATROLLED = 1;
    const NONPATROLLED = 0;
    /* Properties */

    /** @var string */
    private $identifiant = "";
    /** @var int */
    private $siteid = 0;
    /** @var bool */
    private $status = false;
    /** @var string */
    private $date = "";
    /** @var int */
    private $version = 0;
    /** @var int  */
    private $uid = 0;
    /* Model management properties */

    /** @var bool */
    private $_modified = false;
    /** @var bool */
    private $_frombase = true;

    /**
     * Private Patrol constructor.
     * @see construct for public constructor
     *     diff: this one manipulate integer id for site
     * @param string $identifiant
     * @param int $siteid
     * @param bool $status
     * @param int $uid
     * @param string $date
     * @param int $version
     */
    private function __construct($identifiant, $siteid, $status=false, $uid =0, $date="", $version=0)
    {
        /* L'identifiant et la siteid sont la clef primaire: On ne doit pas pouvoir les changer dans l'objet!
           => pas de setter!
        */
        $this->identifiant = $identifiant;
        $this->siteid =  $siteid;
        $this->setStatus($status);
        $this->setDate($date);
        $this->setVersion($version);
        $this->setUid($uid);

        $this->_frombase = false;
        $this->_modified = true;
    }
    /** Public constructor
     * @param string $identifiant
     * @param \Hal_Site $site
     * @param bool $status
     * @param string $date
     * @param int $version
     * @return Patrol
     *
     */
    public static function construct($identifiant, $site, $status=false, $uid=0, $date="", $version=0) {
        $siteid = (int) $site->getSid();
        return new self($identifiant, $siteid, $status, $uid=0, $date, $version);
    }

    /**
     * For an existing patrol onject: mark object as pattroled for the $version to now;
     * @param int $version
     */
    public function markPatrol($version)
    {
        $today = date("Y-m-d");
        $this->setDate($today);
        $this->setStatus(true);
        $this->setVersion($version);
        $this->setUid(\Hal_Auth::getUid());
    }

    /**
     * For an existing patrol onject: mark object as non pattroled
     */
    public function unmarkPatrol()
    {
        if ($this->isStatus()) {
            $this->setStatus(false);
        } else {
            $id = $this->getIdentifiant();
            $siteid = $this->getSiteid();
            \Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Patrol for $id on $siteid should be set to unmark but is already unmark!");
        }
    }

    /**
     * @param $identifiant
     * @param \Hal_Site $site
     * @return Patrol|null
     *
     */
    public static function load($identifiant, $site)
    {
        $db = \Zend_Db_Table::getDefaultAdapter();

        $select = $db->select()->from(self::TABLE);
        $select->where(self::IDFIELD . " = ?", $identifiant);
        $select->where(self::SITEIDFIELD . " = ?", $site -> getSid());
        $res = $db->fetchRow($select);
        if ($res) {
            $obj =  self::row2obj($res);
            $obj -> _frombase = true;
            return $obj;
        } else {
            return null;
        }
    }

    /**
     * @throws \Zend_Db_Adapter_Exception
     */
    public function save()
    {
        $db = \Zend_Db_Table::getDefaultAdapter();
        if ($this->_frombase) {
            /* Update */
            $where = sprintf("IDENTIFIANT = '%s' AND SID = %d", $this->getIdentifiant(), $this->getSiteid());
            $db->update(self::TABLE, $this->toRow(), $where);
        } else {
            /* Insert */
            try {
                $db->insert(self::TABLE, $this->toRow());
            } catch (\Zend_Db_Adapter_Exception $e) {
                if ($e->getCode() == 23000) { // Duplicate Key: patrol allready in db
                    return;
                }
                throw $e;
            }
        }
    }

    /**
     *
     */
    public function delete() {
        $db = \Zend_Db_Table::getDefaultAdapter();
        $id = $this->getIdentifiant();
        if (preg_match('/[a-z]*-\d+/', $id)) {
            $db->delete(self::TABLE, self::IDFIELD . "= '" . $this->getIdentifiant() . "'");
        } else {
            \Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Patrol Identitifant ($id) isn't an Hal document id");
        }
    }
    /**  Transform a row db into an object
     * @param array $row
     * @return Patrol
     */
    private static function row2obj($row)
    {
        $id = $row[self::IDFIELD];
        $siteid = $row[self::SITEIDFIELD];
        $status = $row[self::STATUSFIELD] ? true : false;;
        $date = $row[self::DATEFIELD] == null ? '' : $row[self::DATEFIELD];
        $version = $row[self::VERSIONFIELD];
        $uid = $row[self::UIDFIELD];

        if ($version == null) {
            $version = 0;
        }
        return new Patrol($id, $siteid, $status, $uid, $date, $version);
    }

    /**
     * Transform object to Array for db binding
     * @return array
     */
    private function  toRow() {
        $date = $this->getDate();
        if ($date == '') { $date = null ; }  // en Bd pas de date vide => null en obj date tjours string
        return [
            self::IDFIELD      => $this->getIdentifiant(),
            self::SITEIDFIELD  => $this->getSiteid(),
            self::STATUSFIELD  => $this->isStatus() ? self::PATROLLED : self::NONPATROLLED,
            self::UIDFIELD     => $this->getUid(),
            self::DATEFIELD    => $date,
            self::VERSIONFIELD => $this->getVersion()
            ];
    }

    /**
     * @return \Hal_Site_Collection
     */
    public static function getPatrolSite() {
        $portal = \Hal_Site::getCurrentPortail();
        $site   = \Hal_Site::getCurrent();
        if ($portal->getSetting('patrol')) {
            $coll = $site;
            if ($site->getType() == \Hal_Site::TYPE_PORTAIL) {
                // site courant: portail: on doit prendre la collection associee
                /** @var \Hal_Site_Portail $site */
                $coll = $site->getAssociatedColl();
            }
            return $coll;
        }
        return null;
    }

    /**
     * @return string
     */
    public function getIdentifiant(): string
    {
        return $this->identifiant;
    }

    /**
     * @return int
     */
    public function getSiteid(): int
    {
        return $this->siteid;
    }

    /**
     * @param $uid
     */
    public function setUid($uid) {
        $this->uid = $uid;
    }
    /**
     * @return int
     */
    public function getUid() {
        return $this->uid;
    }
    /**
     * @return bool
     */
    public function isStatus(): bool
    {
        return $this->status;
    }

    /**
     * @param bool $status
     */
    private function setStatus(bool $status)
    {
        if ($status !== $this->isStatus()) {
            $this->status = $status;
            $this->_modified = true;
        }
    }

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @param string $date
     */
    private function setDate(string $date)
    {
        if ($date != $this->getDate()) {
            $this->date = $date;
            $this->_modified = true;
        }
    }

    /**
     * @return int
     */
    public function getVersion(): int
    {
        return $this->version;
    }

    /**
     * @param int $version
     */
    private function setVersion(int $version)
    {
        if ($version != $this->getVersion()) {
            $this->version = $version;
            $this->_modified = true;
        }

    }

    /**
     * Retourne les documents a patrouiller pour le site et l'utilisateur
     * @param \Hal_Site_Portail $portal
     * @return \Zend_Db_Select
     */
    public static function getDocuments($portal)
    {
        $db =\Zend_Db_Table_Abstract::getDefaultAdapter();
        // On prends le Patrol object non patrouilles pour le site
        //    la version en ligne
		$sql = $db->select ()->distinct ()->from ( [ 'p' => self::TABLE ], null)
            ->from ( ['d' => \Hal_Document::TABLE ])
            // Pour avoir les stat user pour affichage dans moderation/patrouillage
            ->from ( array ('u' => 'USER'), array('SCREEN_NAME', 'NBDOCVIS', 'NBDOCSCI', 'NBDOCREF' ))
            ->where ( 'u.UID=d.UID' )
            // Pour avoir les noms de sites de depot
            ->from ( array ('s' => \Hal_Site::TABLE), 'SITE' )
            ->where ( 'd.SID=s.SID' )
            //
            ->where ( 'p.' . self::IDFIELD . '=d.IDENTIFIANT' )  // Jointure
            ->where ( 'p.' . self::STATUSFIELD . ' = ?', self::NONPATROLLED)
            ->where ( 'p.SID=?', $portal->getSid() )
            ->where ( 'd.DOCSTATUS = ' . \Hal_Document::STATUS_VISIBLE);

        $sql = self::addModeratorFilters ( $sql , \Hal_Document::STATUS_VISIBLE );
		return $sql;
    }

    /**
     * Ajoute des critères à une requête SQL pour limiter la requête aux documents/critères du modérateur
     *
     * @param \Zend_Db_Select $sql
     * @param int $docstatus
     * @return \Zend_Db_Select
     */
    private static function addModeratorFilters($sql, $docstatus) {
        $sqlWhere = false;
        $condOr = $condAnd = array ();
        $addFromDocMetadata = false;
        $addFromForAffiliation = false;
        foreach ( \Hal_Auth::getModerateurDetails () as $sid => $details ) {
            if (count ( $details )) {
                foreach ( $details as $metaname => $values ) {
                    if ($metaname == 'sql') {
                        $sqlWhere = true;
                        $mysql = str_replace ( ' SID', ' d.SID', $values [0] );
                        $mysql = str_replace ( 'UID', 'd.UID', $mysql );

                        $sql->where ( $mysql );
                        if (preg_match('/METANAME/', $mysql)) {
                            $addFromDocMetadata = true;
                        }
                        continue;
                    }
                    foreach ( $values as $value ) {
                        if ($metaname == 'typdoc') {
                            $condition = '(';
                            if ($sid != 0) {
                                $condition .= 'd.SID = ' . $sid . ' AND ';
                            }
                            if (substr($value, 0, 1) == '-') {
                                $value = str_replace('-', '', $value);
                                $condition .= 'TYPDOC != "' . $value . '")';
                                $condAnd [] = $condition;
                            } else {
                                $condition .= 'TYPDOC = "' . $value . '")';
                                $condOr [] = $condition;
                            }
                        } elseif ($metaname == 'structure') {
                            $addFromForAffiliation = true;
                            $sql->where('das.STRUCTID=?', $value);
                        } else {
                            $addFromDocMetadata = true;
                            $condition = '(';
                            if ($sid != 0) {
                                $condition .= 'd.SID = ' . $sid . ' AND ';
                            }
                            $condition .= 'METANAME = "' . $metaname . '" AND (';

                            if (substr ( $value, 0, 1 ) == '-') {
                                $value = str_replace ( '-', '', $value );
                                $condition .= 'METAVALUE != "' . $value . '"';
                                if ($metaname == 'domain') {
                                    $condition .= ' AND METAVALUE NOT LIKE "' . $value . '.%")';
                                    $condition .= ' AND METAGROUP = 0)';
                                } else {
                                    $condition .= '))';
                                }
                                $condAnd [] = $condition;
                            } else {
                                $condition .= 'METAVALUE = "' . $value . '"';
                                if ($metaname == 'domain') {
                                    $condition .= ' OR METAVALUE LIKE "' . $value . '.%")';
                                    $condition .= ' AND METAGROUP = 0)';
                                } else {
                                    $condition .= '))';
                                }
                                $condOr [] = $condition;
                            }
                        }
                    }
                }
            } else if ($sid != 0) {
                if (substr ( $sid, 0, 1 ) == '-') {
                    $condAnd [] = 'd.SID != ' . str_replace ( '-', '', $sid );
                } else {
                    $condOr [] = 'd.SID = ' . $sid;
                }
            }
        }

        if (! $sqlWhere) {

            $where = 'DOCSTATUS = ? ';

            if (count ( $condOr ) && count ( $condAnd )) {
                $where .= ' AND ((' . implode ( ' OR ', $condOr ) . ') OR (' . implode ( ' AND ', $condAnd ) . '))';
            } else {
                if (count ( $condOr )) {
                    $where .= ' AND (' . implode ( ' OR ', $condOr ) . ')';
                }
                if (count ( $condAnd )) {
                    $where .= ' AND (' . implode ( ' AND ', $condAnd ) . ')';
                }
            }
            if (\Hal_Auth::isHALAdministrator ()) {
                if ($where != '') {
                    $where = '(' . $where . ') OR ';
                }
                $where .= 'DOCSTATUS = ' . \Hal_Document::STATUS_TRANSARXIV;
            }

            $sql->where ( $where, $docstatus );
        }

        if ($addFromDocMetadata) {
            $sql->from ( array ('m' => \Hal_Document_Metadatas::TABLE_META), null )->where ( 'd.DOCID=m.DOCID' );
        }
        if ($addFromForAffiliation) {
            $sql -> from ( array ('da' => 'DOC_AUTHOR'), null)     -> where("da.DOCID=d.DOCID");
            $sql -> from ( array ('das' => 'DOC_AUTSTRUCT'), null) -> where("das.DOCAUTHID=da.DOCAUTHID");

        }
        return $sql;
    }

}