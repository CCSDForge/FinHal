<?php

//Chargement des constantes login/pwd
define('APPLICATION_ENV', 'production');
require_once('../public/bddconst.php');

set_time_limit(0);
ini_set('memory_limit','8182M');
set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/phplib', '/sites/library'), array(get_include_path()))));

header('Content-Type: text/html; charset=UTF-8');

require 'Zend/Debug.php';

$dbHAL = new PDO('mysql:host='.HAL_HOST.';port='.HAL_PORT.';dbname='.HAL_NAME, HAL_USER, HAL_PWD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));
$dbCAS = new PDO('mysql:host='.CAS_HOST.';dbname='.CAS_NAME, CAS_USER, CAS_PWD, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));

// dépôt sans métas
$sql = $dbHAL->query('SELECT DOCID, UID FROM DOCUMENT WHERE DOCSTATUS IN (11, 111) AND FORMAT="FILE"');
$nbDOC = $nbAUTO = 0;
foreach ( $sql->fetchAll() as $row ) {
    $nbDOC++;
    $contributeur = $dbCAS->query('SELECT CONCAT(LASTNAME, SUBSTRING(FIRSTNAME, 1, 1)) FROM T_UTILISATEURS WHERE UID='.$row['UID'])->fetch(PDO::FETCH_COLUMN);
    foreach ( $dbHAL->query('SELECT CONCAT(r.LASTNAME, SUBSTRING(r.FIRSTNAME, 1, 1)) FROM REF_AUTHOR r, DOC_AUTHOR a WHERE r.AUTHORID=a.AUTHORID AND a.DOCID='.$row['DOCID'])->fetchAll(PDO::FETCH_COLUMN) as $author ) {
        if ( $contributeur == $author ) {
            $nbAUTO++;
            continue 2;
        }
    }
}
println('documents : '.$nbDOC);
println('documents où contributeur ~ auteur : '.$nbAUTO);
println(round($nbAUTO*100/$nbDOC).'%');

function println($s = '')
{
    print $s . "\n";
}
