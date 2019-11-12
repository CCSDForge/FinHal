<?php

/**
 * Script qui crée les imagettes pour toutes les vidéos de HAL
 */

$timestart = microtime(true);
println('> Début du script: '. date("H:i:s", $timestart));

//DB
$dbUser = 'root';
$dbPwd = 'password';
$dbHost = 'localhost';
$dbName = 'HALV3';
$dbHostThumb = 'localhost';
$dbNameThumb = 'thumb';
$dbHostSolr = 'localhost';
$dbNameSolr = 'SOLR_INDEX';

$dbSOLR = new PDO('mysql:host='. $dbHostSolr .';dbname='. $dbNameSolr, $dbUser, $dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbHALV3 = new PDO('mysql:host='. $dbHost .';port=3306;dbname='. $dbName, $dbUser, $dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbTHUMB = new PDO('mysql:host='. $dbHostThumb .';dbname='. $dbNameThumb, $dbUser, $dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

$cursor = $counter = 0;

// On récupère tous les documents du type "vidéo"
$requete = $dbHALV3->prepare('SELECT f.FILENAME, d.DOCID, f.FILEID FROM DOCUMENT d, DOC_FILE f WHERE d.DOCID=1083116 AND d.DOCID=f.DOCID AND d.TYPDOC="VIDEO" AND d.DOCSTATUS NOT LIKE 0') ;
$requete->execute();

while ($videos = $requete->fetch()){
          
    //Insertion dans thumb
    $urlImagette = 'https://hal.archives-ouvertes.fr/file/index/docid/' . $videos['DOCID'] . '/fileid/' . $videos['FILEID'];
    $debug = $urlImagette;
    $src = '/docs/' . wordwrap(sprintf("%08d", $videos['DOCID']), 2, DIRECTORY_SEPARATOR, 1) . '/' . $videos['FILENAME'];
    $options = json_encode(['src' => $src]);
    $sql = "INSERT INTO `THUMB` (`URL`, `OPTION`, `SID`) VALUES ('" . $urlImagette . "', '".$options."', 3);";
    $dbTHUMB->exec($sql);
    $imagette = $dbTHUMB->lastInsertId();

    //Modification de l'imagette dans DOC_FILE
    $sql = "UPDATE DOC_FILE SET IMAGETTE = '" . $imagette . "' WHERE FILEID = " . $videos['FILEID'] . " LIMIT 1;" ;
    $dbHALV3->exec($sql);
        
    //Suppression du cache du document
    $path = '/docs/' . wordwrap(sprintf("%08d", $videos['DOCID']), 2, DIRECTORY_SEPARATOR, 1) . '/cache/';
    foreach (array('phps', 'tei') as $format) {
        $file = $path . $videos['DOCID'] . '.' . $format;
        if (is_file($file)) {
            @unlink($file);
        }
    }

    //Réindexation du document
    $sql = "INSERT INTO `SOLR_INDEX`.`INDEX_QUEUE` (`ID`, `DOCID`, `UPDATED`, `APPLICATION`, `ORIGIN`, `CORE`, `PRIORITY`, `STATUS`) VALUES (NULL, '" . $videos['DOCID'] . "', CURRENT_TIMESTAMP, 'hal', 'UPDATE', 'hal', '10', 'ok');";
        $dbSOLR->exec($sql);
        
    println("DOCID : " . $videos['DOCID'] . ", ajout de l'imagette " . $imagette . " associée au fichier " . $videos['FILEID']);
    $counter ++;
}

println("Nombre d'imagettes créées: " . $counter);


$timeend = microtime(true);
$time = $timeend - $timestart;
println('> Fin du script: ' . date("H:i:s", $timeend));
println('> Script executé en ' . number_format($time, 3) . ' sec.');


function println($var) {
    echo $var."\n";
}
