<?php

set_time_limit(0);
ini_set('memory_limit','2048M');
set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/phplib_test', '/sites/library_test'), array(get_include_path()))));

header('Content-Type: text/html; charset=UTF-8');

require 'Zend/Debug.php';
require 'Zend/Db.php';
require 'Zend/Db/Table/Abstract.php';

//Chargement des constantes login/pwd
define('APPLICATION_ENV', 'production');
require_once('../public/bddconst.php');

//DB
$dbUser = HAL_USER;
$dbPwd = HAL_PWD;
$dbHost = HAL_HOST;
$dbName = HAL_NAME;

$dbUserSolr = SOLR_USER;
$dbPwdSolr = SOLR_PWD;
$dbHostSolr = SOLR_HOST;
$dbNameSolr = SOLR_NAME;

$dbHALV3 = new PDO('mysql:host='. $dbHost .';dbname='. $dbName .'', $dbUser, $dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbSOLR = new PDO('mysql:host='. $dbHostSolr .';dbname='. $dbNameSolr .'', $dbUserSolr, $dbPwdSolr, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

//Parcours des documents
$query = $dbHALV3->query('SELECT CONCAT_WS("", IDENTIFIANT, "v", VERSION) AS ID, DOCID, FORMAT FROM DOCUMENT  WHERE DATESUBMIT > "2014-10-10" ORDER BY DOCID ASC LIMIT 10000');
foreach ($query->fetchAll() as $row) {
    //println($row['DOCID']);
    //Présence de fichiers
    $query2 = $dbHALV3->query('SELECT * FROM DOC_FILE  WHERE DOCID = ' . $row['DOCID']);
    $res2 = $query2->fetchAll();
    if (count($res2) == 0) {
        //Notice
        if ($row['FORMAT'] != 'notice') {
            println($row['ID'] . " - " . $row['DOCID'] . " - format '" . $row['FORMAT'] . "' | format devrait être 'notice' (fichier manquant)");
        }
    } else {
        $main = false;
        foreach ($res2 as $row2) {
            if ($row2['MAIN'] == '1') {
                $main = true;
            }
        }
        $format = $main ? 'file' : 'annex';
        if ($row['FORMAT'] != $format) {
            println($row['ID'] . " - " . $row['DOCID'] . " - format '" . $row['FORMAT'] . "' | format devrait être '" . $format. "' (" . count($res2) . " fichier(s)" . ($main ? ', fichier principal présent' : '') . ")");
        }
    }
}
println();

function println($s = '')
{
    print $s . "\n";
}
