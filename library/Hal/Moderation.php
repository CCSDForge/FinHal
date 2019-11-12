<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 10/01/2014
 * Time: 10:25
 */

use Hal\Document\Lock;

class Hal_Moderation {

	const MODERATION_ACTION = 'MODERATION';

    /**
     * Hal_Moderation constructor.
     */
	public function __construct() {
	}

	/**
	 * Retourne la liste des documents à modérer pour l'utilisateur connecté
	 *
	 * @param string $filterHalAuth
	 *        	si on filtre les résultats avec les données de l'utilisateur courant
	 * @param string $privilege
	 * @return Zend_Db_Select
	 */
	public function getDocuments($filterHalAuth = true, $privilege = Hal_Acl::ROLE_MODERATEUR) {
		Hal_Auth::getUser ()->loadRoles ();
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = $db->select ()->distinct ()->from ( array (
				'd' => Hal_Document::TABLE
		), array (
				'DOCID',
				'TYPDOC',
				'DOCSTATUS',
				'DATESUBMIT',
                'UID'
		))  ->from ( array ('s' => Hal_Site::TABLE), 'SITE' )
            ->from ( array ('u' => 'USER'), array('SCREEN_NAME', 'NBDOCVIS', 'NBDOCSCI', 'NBDOCREF' ))
            ->joinLeft(array ('l' => Hal_Document_Logger::TABLE), "d.DOCID=l.DOCID AND LOGACTION='askmodif'",'LOGACTION' )
            ->where ( 'u.UID=d.UID' )
            ->where ( 'd.SID=s.SID' )
            ->group( 'd.DOCID'); // Ne pas dupliquer les documents si plusieurs Log, ou auteurs sont affiliees a la structures!;

        if ($filterHalAuth) {
			if ($privilege == Hal_Acl::ROLE_MODERATEUR) {
				$sql = $this->addModeratorFilters ( $sql, Hal_Document::STATUS_BUFFER );
			}
		} else {
			$sql->where ( 'DOCSTATUS IN (?)', array (
					Hal_Document::STATUS_BUFFER,
					Hal_Document::STATUS_TRANSARXIV
			) );
		}
		return $sql;
	}


    /**
     * Indique si un doucment peut être transféré dans le portail générique HAL
     * permet de rediriger les dépôts qui ne devraient pas être rattachés à un portail dans HAL
     * @param Hal_Document $document
     * @return bool
     */
	static public function canTransfertHAL(Hal_Document $document)
    {
         if ($document->getInstance() == 'hal' || $document->getTypDoc() == 'MEM') {
            return false;
         }

         return true;
    }


    /**
     * Return document(s) id(s) awaiting moderation
     * @return array
     */
    public static function getDocIds() {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();
        $sql = $db->select()
            ->from(Hal_Document::TABLE, 'DOCID')
            ->where('DOCSTATUS IN (?)', [Hal_Document::STATUS_BUFFER, Hal_Document::STATUS_TRANSARXIV]);
        return $db->fetchCol($sql);
    }

    /**
     * @param int[] $docstatus
     * @return array
     */
	public function getAdministratorDocuments(array $docstatus) {
		Hal_Auth::getUser ()->loadRoles ();
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = $db->select ()->distinct ()->from ( array (
				'd' => Hal_Document::TABLE
		), array (
				'DOCID',
				'TYPDOC',

				'DOCSTATUS',
				'DATESUBMIT',
				'UID'
		) )->from ( array (
				's' => Hal_Site::TABLE
		), 'SITE' )->from ( array (
				'm' => Hal_Document_Metadatas::TABLE_META
		), null )->where ( 'd.SID=s.SID' )->where ( 'd.DOCID=m.DOCID' );


		$portals = Hal_Auth::getDetailsRoles ( Hal_Acl::ROLE_ADMIN );

		$sql->where ( 'DOCSTATUS IN (?)', $docstatus );

		if (!array_key_exists ( 0, $portals ) && !empty($portals)) {
			// Si SID == 0 c'est pour tous les portails
			$sql->where ( 'd.SID IN (?)', array_keys ( $portals ) );
		}


		$sql->order ( [
				'DATEMODIF DESC',
				'DATESUBMIT DESC'
		] );


		return $db->fetchAll ( $sql );
	}

	/**
	 * Ajoute des critères à une requête SQL pour limiter la requête aux documents/critères du modérateur
	 *
	 * @param Zend_Db_Select $sql
     * @param int $docstatus
	 */
	private function addModeratorFilters($sql, $docstatus) {
		$sqlWhere = false;
		$condOr = $condAnd = array ();
        $addFromDocMetadata = false;
        $addFromForAffiliation = false;
		foreach ( Hal_Auth::getModerateurDetails () as $sid => $details ) {
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
						    $sql->where('das.STRUCTID IN (?)', $value);
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
			if (Hal_Auth::isHALAdministrator ()) {
				if ($where != '') {
					$where = '(' . $where . ') OR ';
				}
				$where .= 'DOCSTATUS = ' . Hal_Document::STATUS_TRANSARXIV;
			}

			$sql->where ( $where, $docstatus );
		}

        if ($addFromDocMetadata) {
            $sql->from ( array ('m' => Hal_Document_Metadatas::TABLE_META), null )->where ( 'd.DOCID=m.DOCID' );
        }
        if ($addFromForAffiliation) {
            $sql -> from ( array ('da' => 'DOC_AUTHOR'), null)     -> where("da.DOCID=d.DOCID");
            $sql -> from ( array ('das' => 'DOC_AUTSTRUCT'), null) -> where("das.DOCAUTHID=da.DOCAUTHID");

        }
		return $sql;
	}

	/**
	 * Retourne la liste de tous les doucments en attente de modification
     * @return Zend_Db_Select
	 */
	public function getModifDocuments() {
		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();

        $sql = $db->select ()
            ->distinct ()
            ->from ( array (
                'd' => Hal_Document::TABLE), array (
                    'DOCID',
                    'DATESUBMIT',
                    'UID',
                    'TYPDOC'))
            ->from ( array ('s' => Hal_Site::TABLE), 'SITE' )
            ->from ( array ('u' => 'USER'), 'SCREEN_NAME' )
            ->where ( 'd.SID=s.SID' )
            ->where ( 'd.UID=u.UID' );

        $portals = Hal_Auth::getDetailsRoles ( Hal_Acl::ROLE_ADMIN );

        $sql->where ( 'DOCSTATUS IN (?)', Hal_Document::STATUS_MODIFICATION );

        if (!array_key_exists ( 0, $portals ) && !empty($portals)) {
            // Si SID == 0 c'est pour tous les portails
            $sql->where ( 'd.SID IN (?)', array_keys ( $portals ));
        }

		return $sql;
	}

    /**
     * Retourne la liste de tous les documents sous embargo
     * @return Zend_Db_Select
     */
    public function getEmbargoDocuments() {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();

        $sql = $db->select()
            ->from(array (
                'f' => 'DOC_FILE'
            ), new Zend_Db_Expr('STRAIGHT_JOIN `f`.*'))
            ->from(array (
                'd' => Hal_Document::TABLE
            ), array (
                'DOCID',
                'DATESUBMIT',
                'UID',
                'TYPDOC'))
            ->where('d.DOCID=f.DOCID')
            ->where('DATEVISIBLE > ?', new Zend_Db_Expr('NOW()'))
            ->where('FORMAT = ? ', 'file')
            ->from ( array ('s' => Hal_Site::TABLE), 'SITE' )
            ->from ( array ('u' => 'USER'), 'SCREEN_NAME' )
            ->where('d.UID=u.UID' )
            ->where('d.SID=s.SID')
            ->group('d.DOCID');

        $portals = Hal_Auth::getDetailsRoles ( Hal_Acl::ROLE_ADMIN );

        $sql->where ( 'DOCSTATUS IN (?)', array(Hal_Document::STATUS_VISIBLE, Hal_Document::STATUS_REPLACED) );

        if (!array_key_exists ( 0, $portals ) && !empty($portals)) {
            // Si SID == 0 c'est pour tous les portails
            $sql->where ( 'd.SID IN (?)', array_keys ( $portals ));
        }

        return $sql;
    }

    /**
     * Retourne la liste de tous les sites
     * @return array
     */
    public function getSites() {
        return Hal_Site::search();
        $db = Zend_Db_Table_Abstract::getDefaultAdapter ();

        $sql = $db->select()
            ->from(array (
                's' => Hal_Site::TABLE
            ), array (
                'SID',
                'SITE'));
        return $db->fetchAll($sql);
    }

	/**
	 * Des documents sont en cours de modération
	 *
	 * @param
	 *        	int $docid
	 * @param
	 *        	int $uid
	 * @param
	 *        	string $ip
	 */
	static public function addDocInProgress($docid, $uid, $ip) {
		Lock::addDocInProgress($docid, $uid, $ip, self::MODERATION_ACTION);
	}

	/**
	 * Les documents ne sont plus en cours de modération
	 *
	 * @param string $ip
	 * @param int    $docid
	 */
	static public function delDocInProgress($ip, $docid = 0) {
        Lock::delDocInProgress($ip, $docid);
	}

	/**
	 * Liste les documents en cours de modération
	 *
	 * @param null $uid
	 * @return array
	 */
	static public function documentsInProgress($uid = null) {
		return Lock::documentsInProgress($uid, self::MODERATION_ACTION);
	}

    /**
     * Mettre un fichier visible (sans embargo)
     * @param int $id
     * @param int $uid
     * @param string $date
     */
    static public function putfileembargo($id,$uid, $date = null) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        if ($date == null){
            $dateemb =  new Zend_Db_Expr('NOW()');
        } else {
            $dateemb = $date;
        }
        $db->update('DOC_FILE', array('DATEVISIBLE' => $dateemb), 'FILEID = ' .$id);

        $sql = $db->select()->from('DOC_FILE', 'DOCID')->where('FILEID = ?', $id);
        $docid = $db->fetchOne($sql);
        Ccsd_Search_Solr_Indexer::addToIndexQueue(array($docid), 'hal', 'DELETE', 'hal', 0);
        Hal_Document::deleteCaches($docid);

        Hal_Document_Logger::log($docid, $uid, Hal_Document_Logger::ACTION_MODERATE);
    }
}
