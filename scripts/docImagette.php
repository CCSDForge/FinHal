<?php

/**
 * Script qui vérifie la création des imagettes
 */
$localopts = array(
    'test|t' => 'Test mode',
    // 'convertOnly' => "Reconverti le fichier, sans changer la base de donnee (hormi la duree et filezise)"
);

require_once __DIR__ . "/loadHalHeader.php";

$test = isset($opts->test);
println('> Début du script: '. date("H:i:s", $timestart));

//Chargement des constantes login/pwd
require_once(__DIR__ . '/../public/bddconst.php');

$dbUser = HAL_USER;
$dbPwd = HAL_PWD;
$dbHost = HAL_HOST;
$dbName = HAL_NAME;

$dbHostThumb = THUMB_HOST;
$dbNameThumb = THUMB_NAME;
$dbUserThumb = THUMB_USER;
$dbPwdThumb  = THUMB_PWD;

$dbSolrHost = SOLR_HOST;
$dbNameSolr = SOLR_NAME;
$dbUserSolr = SOLR_USER;
$dbPwdSolr  = SOLR_PWD;

$dbHALV3 = new PDO(HAL_PDO_URL,   $dbUser,      $dbPwd,      array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbTHUMB = new PDO(THUMB_PDO_URL, $dbUserThumb, $dbPwdThumb, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbSOLR  = new PDO(SOLR_PDO_URL,  $dbUserSolr,  $dbPwdSolr,  array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));


// 1 - On s'assure que les nouveaux dépôts en ligne ont bien une imagette
$cursor = $counter = 0;
while (true) {
    $query = $dbHALV3->query('SELECT f.FILEID, f.DOCID FROM DOCUMENT d, DOC_FILE f WHERE d.DOCID=f.DOCID AND DOCSTATUS IN (11, 111) AND ((FILETYPE = "file" AND MAIN = "1") OR (FILETYPE = "annex" AND TYPEANNEX = "figure")) AND (IMAGETTE = 0 OR IMAGETTE IS NULL) AND FILEID > ' . $cursor . ' ORDER BY FILEID ASC LIMIT 10000') ;
    $res = $query->fetchAll();
    $nbtodo = count($res);
    if ($nbtodo == 0) {
        break;
    }
    verbose("Il y a $nbtodo imagettes a creer\n");
    foreach($res as $row) {
        //Insertion dans thumb
        $urlImagette = 'https://hal.archives-ouvertes.fr/file/index/docid/' . $row['DOCID'] . '/fileid/' . $row['FILEID'];
        $debug = $urlImagette;
        $sql = "INSERT INTO `THUMB` (`URL`, `SID`) VALUES ('" . $urlImagette . "', 3);";
        if ($test) {
            print "Exec DB Thumb: $sql\n";
            $imagette = 'XXXXX';
        } else {
            $dbTHUMB->exec($sql);
            $imagette = $dbTHUMB->lastInsertId();
        }

        //Modification de l'imagette dans DOC_FILE
        $sql = "UPDATE DOC_FILE SET IMAGETTE = '" . $imagette . "' WHERE FILEID = " . $row['FILEID'] . " LIMIT 1;" ;
        if ($test) {
            print "Exec DB Hal: $sql\n";
        } else {
            $dbHALV3->exec($sql);
        }
        //Suppression du cache du document
        $path = CACHE_ROOT . '/' . APPLICATION_ENV . '/docs/';
        foreach (array('phps', 'tei') as $format) {
            $file = $path . $row['DOCID'] . '.' . $format;
            if (is_file($file)) {
                if ($test) {
                    print "Effacement cache\n";
                } else {
                    @unlink($file);
                }
            }
        }
        if (!$test) {
            //Réindexation du document
            $sql = "INSERT INTO `SOLR_INDEX`.`INDEX_QUEUE` (`ID`, `DOCID`, `UPDATED`, `APPLICATION`, `ORIGIN`, `CORE`, `PRIORITY`, `STATUS`) VALUES (NULL, '" . $row['DOCID'] . "', CURRENT_TIMESTAMP, 'hal', 'UPDATE', 'hal', '10', 'ok');";
            $dbSOLR->exec($sql);

            println("DOCID : " . $row['DOCID'] . ", ajout de l'imagette " . $imagette . " associée au fichier " . $row['FILEID']);
            $cursor = $row['FILEID'];
        }
        $counter ++;
    }
    if ($test) {
        // En cas de test, on sort du while true...
        break;
    }
}
println("Nombre d'imagettes créées: " . $counter);


// 2 - On supprime les imagettes des dépôts non visibles dans HAL
$cursor = $counter = 0;
while (true) {
    $query = $dbHALV3->query('SELECT f.FILEID, f.DOCID, f.IMAGETTE FROM DOCUMENT d, DOC_FILE f WHERE d.DOCID=f.DOCID AND DOCSTATUS NOT IN (11, 111) AND ((FILETYPE = "file" AND MAIN = "1") OR (FILETYPE = "annex" AND TYPEANNEX = "figure")) AND (IMAGETTE != 0 AND IMAGETTE IS NOT NULL) AND FILEID > ' . $cursor . ' ORDER BY FILEID ASC LIMIT 10000') ;
    $res = $query->fetchAll();
    if (count($res) == 0) {
        break;
    }
    foreach($res as $row) {
        //Suppression de l'imagette dans thumb
        $sql = "DELETE FROM THUMB WHERE IMGID = " . $row['IMAGETTE'];
        //Modification de l'imagette dans DOC_FILE
        $sql2 = "UPDATE DOC_FILE SET IMAGETTE = '0' WHERE FILEID = " . $row['FILEID'] . " LIMIT 1;" ;
        if ($test) {
            print "Exec DB Thumb: $sql\n";
            print "Exec DB Hal: $sql2\n";
        } else {
            $dbTHUMB->exec($sql);
            $dbHALV3->exec($sql2);
        }

        //Suppression du cache du document
        $path = CACHE_ROOT . '/' . APPLICATION_ENV . '/docs/';
        foreach (array('phps', 'tei') as $format) {
            $file = $path . $row['DOCID'] . '.' . $format;
            if (is_file($file)) {
                if ($test) {
                    print "Effacement cache\n";
                } else {
                    @unlink($file);
                }
            }
        }

        println("DOCID : " . $row['DOCID'] . ", suppression de l'imagette " . $row['IMAGETTE'] . " associée au fichier " . $row['FILEID']);
        $cursor = $row['FILEID'];
        $counter ++;
    }
    if ($test) {
        // En cas de test, on sort du while true...
        break;
    }
}
println("Nombre d'imagettes supprimée: " . $counter);

/// 3 - On supprime les fichiers de cache des derniers dépôts des portails
sleep(300);
$query = $dbHALV3->query('SELECT SITE FROM SITE WHERE TYPE = "PORTAIL"') ;
foreach($query->fetchAll() as $row) {
    $site = $row['SITE'];
    $file = CACHE_ROOT . "/portail/$site/home.last.html";
    if (is_file($file)) {
        if ($test) {
            print "Effacement cache portail $site\n";
        } else {
            @unlink($file);
            println("Ficher supprimé : $file");

        }
    }
}

$timeend = microtime(true);
$time = $timeend - $timestart;
println('> Fin du script: ' . date("H:i:s", $timeend));
println('> Script executé en ' . number_format($time, 3) . ' sec.');
