<?php

/**
 * Pour les référentiels en base de données + solr
 * Retourne les différences entre solr et la base de données
 * Supprime les entrées en trop ou ajoute les entrées manquantes
 *
 */
ini_set("memory_limit", '4096M');
ini_set("display_errors", '1');
$timestart = microtime(true);


$localopts = [
    'ref|r=s' => ' Referential (author | domain | journal | projanr | projeurop | structure)',
    'method|m=s' => 'Method (add | delete)',
    'tryme|t' => 'Try',
    'progress|p' => 'Show progression'
];



if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

define('DEFAULT_ENV', 'development');

if ($opts->help != false || ($opts->application_env != false && !in_array($opts->application_env, [
            'development',
            'testing',
            'preprod',
            'production'
        ]))
) {
    die('*** Wrong environment.' . PHP_EOL . $opts->getUsageMessage() . PHP_EOL);
}


if ($opts->method == false) {
    die('method is mandatory' . PHP_EOL . $opts->getUsageMessage() . PHP_EOL);
}

if (($opts->method != 'add') && ($opts->method != 'delete')) {
    die('Wrong method' . PHP_EOL . $opts->getUsageMessage() . PHP_EOL);
}


if ($opts->ref == false) {
    die('ref is mandatory.' . PHP_EOL . $opts->getUsageMessage() . PHP_EOL);
}

switch ($opts->ref) {
    case 'author':
        $ref = new Ccsd_Referentiels_Author();
        break;
    case 'domain':
        $ref = new Ccsd_Referentiels_Domain();
        break;
    case 'journal':
        $ref = new Ccsd_Referentiels_Journal();
        break;
    case 'projanr':
        $ref = new Ccsd_Referentiels_Anrproject();
        break;
    case 'projeurop':
        $ref = new Ccsd_Referentiels_Europeanproject();
        break;
    case 'structure':
        $ref = new Ccsd_Referentiels_Structure();
        break;
    default:
        die('Invalid referential.' . PHP_EOL . $opts->getUsageMessage() . PHP_EOL);
}


if ($opts->verbose == true) {
    $verbose = true;
} else {
    $verbose = false;
}


if ($opts->progress == true) {
    $progress = true;
} else {
    $progress = false;
}

$db = Zend_Db_Table_Abstract::getDefaultAdapter();
Ccsd_Log::message('DB Host: ' . $db->getConfig()['host']  , $verbose, '', '');
Ccsd_Log::message('DB Name: ' . $db->getConfig()['dbname'], $verbose, '', '');



if ($opts->method == 'add') {
    /*
     * 1- Vérification que les documents visibles en base sont bien indexés par solr
     */

    $docidsToIndex = [];
    $offset = 0;
    $totalDocids = 0;
    //Récupération des documents qui devraient être indexés

    $nbDbEntries = $ref->countDbEntries();
    $checkProcessed = 0;
    $count = 500;

    while (true) {
        $docidArr_Solr = [];
        $docidArr_Db = [];
        $docidArr_Db = $ref->getDocidsByDb($count, $offset);

        // abort mission if no more results
        if (count($docidArr_Db) == 0) {
            Ccsd_Log::message("No more data to process.", $progress);
            break;
        }

        $checkProcessed = $checkProcessed + count($docidArr_Db);
        Ccsd_Log::message('Checked ' . $checkProcessed . '/' . $nbDbEntries, $progress, '', '');


        $docidArr_Db = array_column($docidArr_Db, 'docid');
        $offset = end($docidArr_Db);

        $result = $ref->checkIfDocidsExistInSolr($docidArr_Db, $count);
        $docidArr_Solr = $result['response']['docs'];

        $docidArr_Solr = array_column($docidArr_Solr, 'docid');
        $docidArr_Solr = array_map('strval', $docidArr_Solr);

        $docidsToIndex = array_diff($docidArr_Db, $docidArr_Solr);


        $totalDocids = $totalDocids + count($docidsToIndex);

        if ($opts->tryme == false) {
            Ccsd_Search_Solr_Indexer::addToIndexQueue($docidsToIndex, basename(__FILE__), 'UPDATE', $ref->getCore());
        }

        if (count($docidsToIndex) > 0) {
            $text = 'Found ' . count($docidsToIndex) . ' document(s) to be indexed in Solr - (docids: [' . implode('] [', $docidsToIndex) . ')]';
            Ccsd_Log::message($text, $verbose);
        }
    } //end while true

    if ($opts->tryme == false) {
        $text = $totalDocids . ' document(s) sent to Solr';
        Ccsd_Log::message($text, $verbose);
    }
}


if ($opts->method == 'delete') {
    /*
     * 1- Vérification que les documents visibles en base sont bien indexés par solr
     */

    $docidsToIndex = [];
    $offset = '*';
    $totalDocids = 0;
    //Récupération des documents qui devraient être indexés

    $nbSolrEntries = $ref->countSolrEntries();

    $checkProcessed = 0;
    $count = 500;

    while (true) {
        $docidArr_Db = [];
        $docidArr_solr = [];

        $solrResponse = $ref->getDocidsBySolrCursorMark($count, $offset);


        $totalResponseDocid = count($solrResponse['response']['docs']);

        // abort mission if no more results
        if ($totalResponseDocid == 0) {
            Ccsd_Log::message("No more data to process.", $progress);
            break;
        }

        $checkProcessed = $checkProcessed + $totalResponseDocid;
        Ccsd_Log::message('Checked ' . $checkProcessed . '/' . $nbSolrEntries, $progress, '', '');

        $offset = $solrResponse['nextCursorMark'];

        $docidArr_solr = array_column($solrResponse['response']['docs'], 'docid');

        $resultDb = $ref->checkIfDocidsExistInDb($docidArr_solr);


        $docidArr_Db = array_column($resultDb, 'docid');
        $docidArr_Db = array_map('strval', $docidArr_Db);

        $docidsToIndex = array_diff($docidArr_solr, $docidArr_Db);

        $totalDocids = $totalDocids + count($docidsToIndex);

        if ($opts->tryme == false) {
            Ccsd_Search_Solr_Indexer::addToIndexQueue($docidsToIndex, basename(__FILE__), 'DELETE', $ref->getCore());
        }

        if (count($docidsToIndex) > 0) {
            $text = 'Found ' . count($docidsToIndex) . ' document(s) to be removed from Solr - (docids: [' . implode('] [', $docidsToIndex) . ')]';
            Ccsd_Log::message($text, $verbose);
        }
    } //end while true

    if ($opts->tryme == false) {
        $text = $totalDocids . ' document(s) removed from Solr';
        Ccsd_Log::message($text, $verbose);
    }
}


$timeend = microtime(true);
$time = $timeend - $timestart;

Ccsd_Log::message('Job started at: ' . date("H:i:s", $timestart) . '/ end of job: ' . date("H:i:s", $timeend), $verbose);
Ccsd_Log::message('Time spent: ' . number_format($time, 3) . ' sec.', $verbose);
exit();



