<?php

set_time_limit(0);
ini_set('memory_limit','8192M');
set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/phplib_test', '/sites/library_test'), array(get_include_path()))));

header('Content-Type: text/html; charset=UTF-8');

require 'Zend/Debug.php';
require 'Zend/Db.php';
require 'Zend/Db/Table/Abstract.php';

//Chargement des constantes login/pwd
define('APPLICATION_ENV', 'development');
require_once('../public/bddconst.php');

define('CACHE_ROOT', '/var/www/cache/hal');

//DB
$dbUser = HAL_USER;
$dbPwd = HAL_PWD;
$dbHost = HAL_HOST;
$dbName = HAL_NAME;
$dbPort = HAL_PORT;

$dbUserSolr = SOLR_USER;
$dbPwdSolr = SOLR_PWD;
$dbHostSolr = SOLR_HOST;
$dbPortSolr = SOLR_PORT;
$dbNameSolr = SOLR_NAME;
$pathdocs = '/docs/';

$dbHALV3 = new PDO('mysql:host='. $dbHost .';dbname='. $dbName .'', $dbUser, $dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbSOLR = new PDO('mysql:host='. $dbHostSolr .';dbname='. $dbNameSolr .'', $dbUserSolr, $dbPwdSolr, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));


$query = $dbHALV3->query('SELECT DISTINCT IDENTIFIANT FROM DOCUMENT WHERE FORMAT="file" ORDER BY DOCID ASC');
foreach ($query->fetchAll() as $row) {
    println($row['IDENTIFIANT']);
    $query2 = $dbHALV3->query('SELECT DOCID, VERSION, DOCSTATUS FROM DOCUMENT WHERE IDENTIFIANT = "'.$row['IDENTIFIANT'].'" ORDER BY VERSION ASC');
    if ( $query2->rowCount() == 1 ) {
        $resource = $query2->fetch(PDO::FETCH_ASSOC);
        if ( $resource['VERSION'] != 1 || $resource['DOCSTATUS'] == 111 ) {
            $status = '';
            if ( $resource['DOCSTATUS'] == 111 ) {
                println('Une seule version à 111 !!!');
                $status = ', DOCSTATUS = 11';
            }
            $sql = 'UPDATE DOCUMENT SET VERSION = 1'.$status.'  WHERE DOCID = ' .$resource['DOCID'];
            $dbHALV3->exec($sql);
            if ( in_array($resource['DOCSTATUS'], [11, 111]) ) {
                $baseCache = CACHE_ROOT .'/'.APPLICATION_ENV.'/'. $pathdocs . wordwrap(sprintf("%08d", $resource['DOCID']), 2, DIRECTORY_SEPARATOR, 1) . DIRECTORY_SEPARATOR . '/';
                foreach(['phps', 'tei', 'dc', 'bib', 'enw', 'json'] as $f) {
                    if ( is_file($baseCache . $resource['DOCID'] . '.' . $f) ) {
                        @unlink($baseCache . $resource['DOCID'] . '.' . $f);
                    }
                }
                $sql = "INSERT INTO INDEX_QUEUE (DOCID, APPLICATION, ORIGIN, CORE) VALUES (" . $resource['DOCID'] . ", 'hal', 'UPDATE', 'hal');";
                $dbSOLR->exec($sql);
            }
        }
    }/* else {
        $version = 1;
        foreach ($query2->fetchAll(PDO::FETCH_ASSOC) as $resource) {
            if ( $resource['VERSION'] != $version ) {
                $sql = 'UPDATE DOCUMENT SET VERSION = '.$version.'  WHERE DOCID = ' .$resource['DOCID'];
                $dbHALV3->exec($sql);
                if ( in_array($resource['DOCSTATUS'], [11, 111]) ) {
                    $baseCache = CACHE_ROOT . $pathdocs . wordwrap(sprintf("%08d", $resource['DOCID']), 2, DIRECTORY_SEPARATOR, 1) . DIRECTORY_SEPARATOR . '/';
                    foreach(['phps', 'tei', 'dc', 'bib', 'enw', 'json'] as $f) {
                        if ( is_file($baseCache . $resource['DOCID'] . '.' . $f) ) {
                            @unlink($baseCache . $resource['DOCID'] . '.' . $f);
                        }
                    }
                    $sql = "INSERT INTO INDEX_QUEUE (DOCID, APPLICATION, ORIGIN, CORE) VALUES (" . $resource['DOCID'] . ", 'hal', 'UPDATE', 'hal');";
                    $dbSOLR->exec($sql);
                }
            }
            $version++;
        }
    }*/
}

println();

function println($s = '')
{
    print $s . "\n";
}
