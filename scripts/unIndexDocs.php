<?php

//Chargement des constantes login/pwd
define('APPLICATION_ENV', 'production');
require_once('../public/bddconst.php');

$timestart = microtime(true);
set_include_path(implode(PATH_SEPARATOR, array_merge(array('./../library', '/sites/phplib', '/sites/library'), array(get_include_path()))));
//set_include_path(implode(PATH_SEPARATOR, array_merge(array( './../library', '/Users/laurent/Zend/library', '/Users/laurent/PhpstormProjects/library'), array(get_include_path()))));

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

$dbHALV3 = new PDO('mysql:host='. $dbHost .';dbname='. $dbName .'', $dbUser, $dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbSOLR = new PDO('mysql:host='. $dbHostSolr .';dbname='. $dbNameSolr .'', $dbUserSolr, $dbPwdSolr, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));


// Autoloader
require_once ('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

$querySolR = ['wt'=>'phps', 'q'=>'*:*', 'fl'=>'docid', 'sort'=>'docid desc', 'rows'=>'10000', 'cursorMark'=>'*'];
$page = 0;
println('> Début du script: '. date("H:i:s", $timestart));

$count = 0;
while (true) {
    $docidsSolR = $docidsDb = $docidsToUnIndex = array();
    //Zend_Debug::dump($query, 'Doing search:');
    $result = unserialize(solr($querySolR));
    //Zend_Debug::dump($result, 'Solr result:');
    foreach ($result['response']['docs'] as $row ) {
        $docidsSolR[] = $row['docid'];
    }
    //println('docids :' . implode(', ', $docidsSolR));
    if (count($docidsSolR)) {
        //Récupération des documents en base
        $query = $dbHALV3->query('SELECT DOCID, DOCSTATUS FROM `DOCUMENT` WHERE DOCID IN (' . implode(', ', $docidsSolR) . ')');
        foreach ($query->fetchAll() as $row) {
            $docidsDb[] = $row['DOCID'];
            if ($row['DOCSTATUS'] != 11 && $row['DOCSTATUS'] != 111) {
                //Le document n'a rien à faire dans solr
                println('document à retirer de solR :' . $row['DOCID'] . ' (statut :  ' . $row['DOCSTATUS'] . ')');
                $docidsToUnIndex[] = $row['DOCID'];
            }
        }

        foreach(array_diff($docidsSolR, $docidsDb) as $docid) {
            //Documents plus dans la base
            println('document à retirer de solR :' . $row['DOCID'] . ' (plus en base)');
            $docidsToUnIndex[] = $row['DOCID'];
        }

        $count += count($docidsToUnIndex);

        foreach($docidsToUnIndex as $docid) {
            $sql = "INSERT INTO `SOLR_INDEX`.`INDEX_QUEUE` (`ID`, `DOCID`, `UPDATED`, `APPLICATION`, `ORIGIN`, `CORE`, `PRIORITY`, `STATUS`) VALUES (NULL, '" . $docid . "', CURRENT_TIMESTAMP, 'hal', 'DELETE', 'hal', '10', 'ok');";
            $dbSOLR->exec($sql);
        }
    }

    if ( $querySolR['cursorMark'] == $result['nextCursorMark'] ) {
        break;
    }
    $querySolR['cursorMark'] = $result['nextCursorMark'];
}
println('> Nombre de documents à retirer de SolR : ' . $count);
$timeend = microtime(true);
$time = $timeend - $timestart;
println('> Fin du script: ' . date("H:i:s", $timeend));
println('> Script executé en ' . number_format($time, 3) . ' sec.');


function println($var) {
    echo $var."\n";
}

function solr($a) {
    $query = [];
    foreach ( $a as $p=>$v) {
        $query[] = $p.'='.rawurlencode($v);
    }
    $tuCurl = curl_init();
    curl_setopt ( $tuCurl, CURLOPT_USERAGENT, 'CcsdToolsCurl' );
    curl_setopt ( $tuCurl, CURLOPT_URL, 'http://ccsdsolrvip.in2p3.fr:8080/solr/hal/select?'.implode('&', $query) );
    curl_setopt ( $tuCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $tuCurl, CURLOPT_CONNECTTIMEOUT, 10 );
    curl_setopt ( $tuCurl, CURLOPT_TIMEOUT, 300 ); // timeout in seconds
    curl_setopt ( $tuCurl, CURLOPT_USERPWD, 'ccsd:ccsd12solr41' );
    $info = curl_exec ( $tuCurl );
    if (curl_errno ( $tuCurl ) == CURLE_OK) {
        return $info;
    } else {
        exit(curl_errno( $tuCurl ));
    }
}