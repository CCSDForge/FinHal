<?php
/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 22/02/18
 * Time: 10:25
 */

namespace Hal\Document;

class Lock
{
    const TABLE = 'USER_MODER_TMP';

    /**
     * Des documents sont en cours d'utilisation
     *
     * @param
     *        	int $docid
     * @param
     *        	int $uid
     * @param
     *        	string $ip
     * @param
     *          string $action
     */
    static public function addDocInProgress($docid, $uid, $ip, $action) {
        $db = \Zend_Db_Table_Abstract::getDefaultAdapter ();
        $bind = array (
            'DOCID' => $docid,
            'UID' => $uid,
            'IP' => ip2long ( $ip ),
            'ACTION' => $action
        );

        try {
            $db->insert ( self::TABLE, $bind );
        } catch ( \Exception $e ) {
            // Tant pis, c'est indicatif
        }
    }

    /**
     * Les documents ne sont plus en cours de modération
     *
     * @param string $ip
     * @param int    $docid
     */
    static public function delDocInProgress($ip, $docid = 0) {
        $db = \Zend_Db_Table_Abstract::getDefaultAdapter ();
        $where = 'IP = ' . ip2long ( $ip );
        if ($docid != 0) {
            $where .= ' AND DOCID = ' . $docid;
        }
        $db->delete ( self::TABLE, $where );
    }

    /**
     * Liste les documents en cours d'utilisation
     *
     * @param int $uid
     * @param string $action
     * @return array
     */
    static public function documentsInProgress($uid = null, $action = null) {
        $db = \Zend_Db_Table_Abstract::getDefaultAdapter ();

        // Suppression des documents de plus de 5 minutes
        $db->delete ( self::TABLE, 'TIMESTAMPDIFF(MINUTE,DATEMODER,NOW()) > 5' );

        $sql = $db->select ()->from ( array (
            'm' => self::TABLE
        ), 'DOCID' )->join ( array (
            'u' => 'USER'
        ), 'u.UID = m.UID', new \Zend_Db_Expr ( "CONCAT_WS('', SCREEN_NAME, ' (UID: ', u.UID, ')')" ) );

        if ($uid !== null) {
            $sql->where ( 'm.UID != ?', (int) $uid );
        }

        if ($action !== null) {
            $sql->where('m.ACTION = ?', $action);
        }

        return $db->fetchPairs ( $sql );
    }

    /**
     * @param $docid
     * @return bool
     */
    static public function isLocked($docid, $uid = null, $action = null)
    {
        $docs = Lock::documentsInProgress($uid, $action);

        return in_array($docid, array_keys($docs));
    }
}