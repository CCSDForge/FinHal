<?php

/**
 * Class Hal_Document_Visite
 */
class Hal_Document_Visite
{
    const COUNTER='DOC_STAT_COUNTER';
    const PREFIX='document-';

    /**
     * @return bool|resource
     */
    public static function getfilehandler() {
        // Fonction strictement identique dans Hal/Document/Visite.php Hal/Cv/Visite.php
        
        // écriture dans un fichier temporaire
        $rep = PATHTEMPDOCS . 'visite/';
        if ( !is_dir($rep) && !mkdir($rep) ) {
            return false;
        }
        $fullhost=php_uname('n');
        $host=strstr(php_uname('n'), '.', true);
        if ($host == false) {
            # hostname ne contient pas le domaine (pas de .)
            $host=$fullhost;
        };
        $file = $rep . self::PREFIX . date('Ymd') . '-' . $host . '.log';
        $fp = fopen($file, 'a');
        return $fp;
    }

    /**
     * @param int $docId
     * @param int $uid
     * @param string $what
     * @param int $fileid
     * @return bool
     */
    public static function add($docId, $uid = 0, $what = 'notice', $fileid=0)
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $ip = (int)ip2long($request->getClientIp());
        $agent = (string)isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 2000) : '';
        $v = new Ccsd_Visiteurs($ip, $agent);
        $isRobot = $v->isRobot();
        if ($isRobot == true) {
            # Si robot, pas la peine de logguer
            return true;
        };
        $data = array(
            'DOCID'		=> (int)$docId,
            'IP'		=> $ip,
            'AGENT'     => $agent ,
            'UID'		=> (int)$uid,
            'DHIT'		=> (string)date('Y-m-d'),
            'CONSULT'	=> (string)$what,
            'FILEID'	=> (int)$fileid,
        );

        $fp = self:: getfilehandler();
        if ( $fp !== false ) {
            fputcsv($fp, $data);
            fclose($fp);
        }
        return true;
    }

    /**
     * Transfert des statistiques de consultation d'un document sur un autre
     * @param int $deletedDocid
     * @param int $docid
     * @return true;
     */
    public static function transferStat ($deletedDocid, $docid)
    {
        //$db = Zend_Db_Table_Abstract::getDefaultAdapter();
        // TODO : migrer vers Hal_Stats
        $db = Hal_Db_Adapter_Stats::getAdapter(APPLICATION_ENV);
        $sql = $db->select()->from(self::COUNTER)->where('DOCID = ?', $deletedDocid);
        foreach($db->fetchAll($sql) as $row) {
            $sql = $db->select()->from(self::COUNTER, 'STATID')
                ->where('DOCID = ?', $docid)
                ->where('UID = ?', $row['UID'])
                ->where('CONSULT = ?', $row['CONSULT'])
                ->where('FILEID = ?', $row['FILEID'])
                ->where('VID = ?', $row['VID'])
                ->where('DHIT = ?', $row['DHIT']);
            $statid = $db->fetchOne($sql);
            try {
                if ($statid) {
                    $db->update(self::COUNTER, array('COUNTER' => new Zend_Db_Expr('COUNTER + ' . $row['COUNTER'])), 'STATID = ' . $statid);
                } else {
                    $bind = $row;
                    unset($bind['STATID']);
                    $bind['DOCID'] = $docid;
                    $db->insert(self::COUNTER, $bind);
                }
            } catch (Zend_Db_Adapter_Exception $e) {
                return false;
            }
        }
        return true;
    }

}