<?php
/**
 * Vérifie la cohérence entre la base de données et solr
 *
 */
$localopts = array(
        'test|t' => 'Lance le script en mode test',
        'add|a' => 'Ajoute les documents manquants dans solr',
        'cursoradd-i' => 'docid de départ (1 par défaut)',
        'rm|r' => 'Retire les documents en trop dans solr',
    );

require_once(__DIR__ . '/../public/bddconst.php');
if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}
/** @var Zend_Console_Getopt $opts */
$add = isset($opts->a);
if (isset($opts->cursoradd)) {
    $cursorAdd = (int)$opts->cursoradd;
} else {
    $cursorAdd = 1;
}

$del     = isset($opts->r);
//mode test
$test    = isset($opts->t);
//Débug
$debug   = isset($opts->d);
$verbose = isset($opts->v);
if ($verbose) {
    $debug = true;
}

//Variables de la bdd
$dbUser = HAL_USER;
$dbPwd  = HAL_PWD;
$dbHost = HAL_HOST;
$dbName = HAL_NAME;

$dbSolrHost = SOLR_HOST;
$dbNameSolr = SOLR_NAME;
$dbUserSolr = SOLR_USER;
$dbPwdSolr  = SOLR_PWD;

$dbHALV3 = new PDO(HAL_PDO_URL, $dbUser,     $dbPwd,     array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbSOLR = new PDO(SOLR_PDO_URL, $dbUserSolr, $dbPwdSolr, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

if ($debug) {
    $timestart = microtime(true);
    println('> Début du script: '. date("H:i:s", $timestart));
    if ($test) {
        println('> Mode TEST');
    }
}

if ($add) {
    /* 1- Vérification que les documents visibles en base sont bien indexés par solr */
    if ($debug) {
        println();
        println('# Cohérence DB > SolR');
        println('# Environnement:' . APPLICATION_ENV);
        println('# DBHAL : ' . HAL_PDO_URL);
        println('# DBSOLR: ' . SOLR_PDO_URL);
    }

    $querySolR = ['wt' => 'phps', 'q' => '*:*', 'fl' => 'docid', 'rows' => '800'];
    $page = 0;
    $docidsToIndex = array();

    //Récupération des documents qui devraient être indexés
    while (true) {
        $docidsSolR = $docidsDb = $docidsTmp = array();
        $query = $dbHALV3->query('SELECT DOCID FROM `DOCUMENT` WHERE DOCSTATUS IN (11, 111) AND DOCID > ' . $cursorAdd . ' ORDER BY DOCID ASC LIMIT 500');
        $res = $query->fetchAll();
        if (count($res) == 0) {
            break;
        }
        foreach ($res as $row) {
            $docidsDb[] = $row['DOCID'];
            $cursorAdd = $row['DOCID'];
        }

        //Récupération des documents dans solr
        $querySolR['q'] = 'docid:(' . implode('+OR+', $docidsDb) . ')';
        $result = unserialize(solr($querySolR));
        foreach ($result['response']['docs'] as $row) {
            $docidsSolR[] = $row['docid'];
        }

        $docidsTmp = array_diff($docidsDb, $docidsSolR);
        $docidsToIndex = array_merge($docidsToIndex, $docidsTmp);

        if ($verbose) {
            $text = ' - cursor: ' . $cursorAdd;
            if (count($docidsTmp)) {
                $text .= ' - ' . count($docidsTmp) . ' document(s) à ajouter - (docids: ' . implode(', ', $docidsTmp) . ')';
            }
            println($text);
        }
    }
    if ($debug) {
        $text = '> Nombre de documents à ajouter dans solR : ' . count($docidsToIndex);
        if (count($docidsToIndex)) {
            $text .= ' (docids: ' . implode(', ', $docidsToIndex) . ')';
        }
        println($text);
        println();
    }

    if (! $test) {
        foreach ($docidsToIndex as $docid) {
            $sql = "INSERT INTO `SOLR_INDEX`.`INDEX_QUEUE` (`ID`, `DOCID`, `UPDATED`, `APPLICATION`, `ORIGIN`, `CORE`, `PRIORITY`, `STATUS`) VALUES (NULL, '" . $docid . "', CURRENT_TIMESTAMP, 'hal', 'UPDATE', 'hal', '10', 'ok');";
            $dbSOLR->exec($sql);
        }
    }
}

if ($del) {
    /* 2- Vérification que les documents dans solr sont toujours en base  */
    if ($debug) {
        println();
        println('# Cohérence SolR > DB');
    }
    $querySolR = ['wt' => 'phps', 'q' => '*:*', 'fl' => 'docid', 'sort' => 'docid desc', 'rows' => '10000', 'cursorMark' => '*'];
    $page = 0;
    $docidsToUnindex = array();
    while (true) {
        $docidsSolR = $docidsDb = $docidsTmp = array();
        $result = unserialize(solr($querySolR, true));
        foreach ($result['response']['docs'] as $row) {
            $docidsSolR[] = $row['docid'];
        }
        if (count($docidsSolR)) {
            //Récupération des documents en base
            $query = $dbHALV3->query('SELECT DOCID, DOCSTATUS FROM `DOCUMENT` WHERE DOCID IN (' . implode(', ', $docidsSolR) . ')');
            foreach ($query->fetchAll() as $row) {
                $docidsDb[] = $row['DOCID'];
                if ($row['DOCSTATUS'] != 11 && $row['DOCSTATUS'] != 111) {
                    //Le document n'a rien à faire dans solr
                    $docidsTmp[] = $row['DOCID'];
                }
            }
            foreach (array_diff($docidsSolR, $docidsDb) as $docid) {
                //Documents plus dans la base
                $docidsTmp[] = $docid;
            }
        }

        $docidsToUnindex = array_merge($docidsToUnindex, $docidsTmp);

        if ($verbose) {
            $text = ' - cursor: ' . $result['nextCursorMark'];
            if (count($docidsTmp)) {
                $text .= ' - ' . count($docidsTmp) . ' document(s) à retirer - (docids: ' . implode(', ', $docidsTmp) . ')';
            }
            println($text);
        }

        if ($querySolR['cursorMark'] == $result['nextCursorMark']) {
            break;
        }
        $querySolR['cursorMark'] = $result['nextCursorMark'];
    }

    if ($debug) {
        $text = '> Nombre de documents à retirer de solR : ' . count($docidsToUnindex);
        if (count($docidsToUnindex)) {
            $text .= ' (docids: ' . implode(', ', $docidsToUnindex) . ')';
        }
        println($text);
        println();
    }

    if (! $test) {
        foreach ($docidsToUnindex as $docid) {
            $sql = "INSERT INTO `SOLR_INDEX`.`INDEX_QUEUE` (`ID`, `DOCID`, `UPDATED`, `APPLICATION`, `ORIGIN`, `CORE`, `PRIORITY`, `STATUS`) VALUES (NULL, '" . $docid . "', CURRENT_TIMESTAMP, 'hal', 'DELETE', 'hal', '10', 'ok');";
            $dbSOLR->exec($sql);
        }
    }
}

if ($debug) {
    $timeend = microtime(true);
    $time = $timeend - $timestart;
    println('> Fin du script: ' . date("H:i:s", $timeend));
    println('> Script executé en ' . number_format($time, 3) . ' sec.');
}

function solr($a, $encode = false) {
    $query = [];
    foreach ( $a as $p=>$v) {
        $query[] = $p.'='. ($encode ? rawurlencode($v) : $v);
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
