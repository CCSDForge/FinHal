<?php

class Hal_Cv_Visite
{
    const COUNTER='CV_STAT_COUNTER';
    const PREFIX='cv-';
    
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

    public static function add($idHal, $uid = 0)
    {
        $data = array(
            'IDHAL'		=> (int)$idHal,
            'IP'		=> (int)ip2long(Zend_Controller_Front::getInstance()->getRequest()->getClientIp()),
            'AGENT'     => (string)isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 2000) : '',
            'UID'		=> (int)$uid,
            'DHIT'		=> (string)date('Y-m-d')
        );
        
        $fp = self::getfilehandler();
        if ( $fp !== false ) {
            fputcsv($fp, $data);
            fclose($fp);
        }
        return true;
    }

    /**
     * @param $idHal
     * @return string
     */
    public static function get($idHal)
    {
        $db = Hal_Db_Adapter_Stats::getAdapter(APPLICATION_ENV);
        $sql = $db->select()
            ->from(self::COUNTER, 'SUM(COUNTER)')
            ->where('IDHAL = ?', (int)$idHal);
        return $db->fetchOne($sql);
    }

}