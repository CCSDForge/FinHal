<?php
$localopts = array(
    'test|t'   => 'Lance le script en mode test (sans tamponnage/détamponnage)',
);

require_once(__DIR__ . '/loadHalHeader.php');

//mode test
$test = isset($opts->t);

/** @var  Zend_Db_Adapter_Pdo_Mysql $dbHALV3 */
$dbHALV3 = Zend_Db_Table_Abstract::getDefaultAdapter(['maxDocsInBuffer' => 10 ] );
$dbSOLR = new PDO(SOLR_PDO_URL, SOLR_USER, SOLR_PWD, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

$LabelSolr='cleanMeta';
$HAL_CORE="hal";

verbose('Environnement: ' . APPLICATION_ENV);
verbose('Database     : ' . HAL_NAME);
verbose('Database Solr: ' . SOLR_PDO_URL);

// dépôt sans métas
$sql = $dbHALV3->query('SELECT DOCID FROM `DOCUMENT` WHERE DATESUBMIT < ADDDATE(NOW(), INTERVAL -26 HOUR) AND DOCID NOT IN (select DISTINCT DOCID FROM DOC_METADATA)');
$nb = 0;

$docpath = PATHDOCS;
$cachepath = DOCS_CACHE_PATH;
foreach ( $sql->fetchAll() as $row ) {
    $nb++;
    debug("Delete docid: "  . $row['DOCID']);
    $dir = $docpath.wordwrap(sprintf("%08d", $row['DOCID']), 2, DIRECTORY_SEPARATOR, 1);
    $cachedir = $cachepath.wordwrap(sprintf("%08d", $row['DOCID']), 2, DIRECTORY_SEPARATOR, 1);
    debug("rm -rf $dir $cachedir");
    if (!$test) {
        $dbHALV3->exec('DELETE from DOCUMENT where DOCID='.$row['DOCID']);
        $dbHALV3->exec('INSERT INTO DOC_LOG (DOCID,UID,LOGACTION,MESG) VALUES('.$row['DOCID'].',1,"delete","cleanMeta")');
        
        $dbSOLR->exec('INSERT INTO INDEX_QUEUE (DOCID,APPLICATION,ORIGIN,CORE) VALUES ('.$row['DOCID']. ', "' .$LabelSolr. '", "DELETE","hal")');
        Ccsd_Search_Solr_Indexer::addToIndexQueue( array( $row['DOCID'] ) , $LabelSolr, "DELETE", $HAL_CORE);
        
        Ccsd_Tools::rrmdir( $dir );
        Ccsd_Tools::rrmdir( $cachedir );
    }
}
println($nb.' document(s) sans métadonnées');

if ($test) {
    $sql = $dbHALV3->query('SELECT count(*) FROM `DOC_METADATA` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
    $nb = count($sql->fetchAll());
} else {
    $nb = $dbHALV3->exec('DELETE FROM `DOC_METADATA` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
}
println($nb.' DOC_METADATA sans lien vers DOCUMENT');

if ($test) {
    $sql = $dbHALV3->query('SELECT count(*) FROM `DOC_AUTHOR` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
    $nb = count($sql->fetchAll());
} else {
    $nb = $dbHALV3->exec('DELETE FROM `DOC_AUTHOR` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
}
println($nb.' DOC_AUTHOR sans lien vers DOCUMENT');

if ($test) {
    $sql =$dbHALV3->query('SELECT count(*) FROM `DOC_AUTHOR_IDEXT` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
} else {
    $nb = $dbHALV3->exec('DELETE FROM `DOC_AUTHOR_IDEXT` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
}
println($nb.' DOC_AUTHOR_IDEXT sans lien vers DOCUMENT');

// Suppression des mots clefs vides
if ($test) {
    $sql = $dbHALV3->query('SELECT * from DOC_METADATA where METANAME = "keyword" and METAVALUE = ""');
    $nb = count($sql->fetchAll());
}
    $nb = $dbHALV3->exec('delete from DOC_METADATA where METANAME = "keyword" and METAVALUE = ""');
println($nb . ' keywords vides supprimes');


if ($test) {
    println('Arret du script en mode test: le reste est destructif');
    exit (0);
}

$nb = $dbHALV3->exec('DELETE FROM `DOC_AUTSTRUCT` WHERE DOCAUTHID NOT IN (select DISTINCT DOCAUTHID from `DOC_AUTHOR`)');
println($nb.' DOC_AUTSTRUCT sans lien vers DOCUMENT');
$sql = $dbHALV3->query('SELECT das.AUTSTRUCTID, da.DOCID FROM `DOC_AUTSTRUCT` das, `DOC_AUTHOR` da  WHERE das.DOCAUTHID = da.DOCAUTHID AND das.`STRUCTID` NOT IN (select STRUCTID from `REF_STRUCTURE`)');
$nb = 0;
foreach ( $sql->fetchAll() as $row ) {
    $dbHALV3->exec('DELETE from DOC_AUTSTRUCT where AUTSTRUCTID='.$row['AUTSTRUCTID']);
    $dbSOLR->exec('INSERT INTO INDEX_QUEUE (DOCID,APPLICATION,ORIGIN,CORE) VALUES ('.$row['DOCID']. ', "' .$LabelSolr. '", "UPDATE","hal")');
    $nb++;
}
println($nb.' DOC_AUTSTRUCT sans lien vers REF_STRUCTURE');
$nb = $dbHALV3->exec('DELETE FROM `DOC_COMMENT` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
println($nb.' DOC_COMMENT sans lien vers DOCUMENT');
$nb = $dbHALV3->exec('DELETE FROM `DOC_FILE` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
println($nb.' DOC_FILE sans lien vers DOCUMENT');
$nb = $dbHALV3->exec('DELETE FROM `DOC_HASCOPY` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
println($nb.' DOC_HASCOPY sans lien vers DOCUMENT');
$nb = $dbHALV3->exec('DELETE FROM `DOC_OWNER` WHERE IDENTIFIANT NOT IN (select DISTINCT IDENTIFIANT from `DOCUMENT`)');
println($nb.' DOC_OWNER sans lien vers DOCUMENT');
$nb = $dbHALV3->exec('DELETE FROM `DOC_OWNER_CLAIM` WHERE IDENTIFIANT NOT IN (select DISTINCT IDENTIFIANT from `DOCUMENT`)');
println($nb.' DOC_OWNER_CLAIM sans lien vers DOCUMENT');
$nb = $dbHALV3->exec('DELETE FROM `DOC_RELATED` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
println($nb.' DOC_RELATED sans lien vers DOCUMENT');
$nb = $dbHALV3->exec('DELETE FROM `USER_LIBRARY_DOC` WHERE IDENTIFIANT NOT IN (select DISTINCT IDENTIFIANT from `DOCUMENT`)');
println($nb.' USER_LIBRARY_DOC sans lien vers DOCUMENT');
//$nb = $dbHALV3->exec('DELETE FROM `DOC_STAT_COUNTER` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
//println($nb.' DOC_STAT_COUNTER sans lien vers DOCUMENT');
//$dbHALV3->exec('DELETE FROM `STAT_VISITOR` WHERE VID NOT IN (select DISTINCT VID from `DOC_STAT_COUNTER`)');
$nb = $dbHALV3->exec('DELETE FROM `DOC_TAMPON` WHERE DOCID NOT IN (select DOCID from `DOCUMENT`)');
println($nb.' DOC_TAMPON sans lien vers DOCUMENT');

// Objet référentiel non valid non utilisé
// Revue
$sql = $dbHALV3->query('SELECT JID FROM `REF_JOURNAL` WHERE `VALID`="INCOMING"');
$nb = 0;
foreach ( $sql->fetchAll() as $row ) {
    $exist = (int) $dbHALV3->query('SELECT count(*) FROM `DOC_METADATA` WHERE METANAME="journal" AND METAVALUE="'.$row['JID'].'"')->fetchColumn();
    if ( $exist == 0 ) {
        $dbHALV3->exec('DELETE from REF_JOURNAL where JID='.$row['JID']);
        $dbSOLR->exec('INSERT INTO INDEX_QUEUE (DOCID,APPLICATION,ORIGIN,CORE) VALUES ('.$row['JID']. ', "' .$LabelSolr. '", "DELETE","ref_journal")');
        $nb++;
    }
}
println($nb.' REF_JOURNAL (INCOMING) sans lien vers DOCUMENT');
// ProjANR
$sql = $dbHALV3->query('SELECT ANRID FROM `REF_PROJANR` WHERE `VALID`="INCOMING" AND `ANRID` NOT IN (select distinct METAVALUE from `DOC_METADATA` where METANAME ="anrProject")');
foreach ( $sql->fetchAll() as $row ) {
    $dbSOLR->exec('INSERT INTO INDEX_QUEUE (DOCID,APPLICATION,ORIGIN,CORE) VALUES ('.$row['ANRID']. ', "' .$LabelSolr. '", "DELETE","ref_projanr")');
}
$nb = $dbHALV3->exec('DELETE FROM `REF_PROJANR` WHERE `VALID`="INCOMING" AND `ANRID` NOT IN (select distinct METAVALUE from `DOC_METADATA` where METANAME ="anrProject")');
println($nb.' REF_PROJANR (INCOMING) sans lien vers DOCUMENT');
// ProjEurop
$sql = $dbHALV3->query('SELECT PROJEUROPID FROM `REF_PROJEUROP` WHERE `VALID`="INCOMING" AND `PROJEUROPID` NOT IN (select distinct METAVALUE from `DOC_METADATA` where METANAME ="europeanProject")');
foreach ( $sql->fetchAll() as $row ) {
    $dbSOLR->exec('INSERT INTO INDEX_QUEUE (DOCID,APPLICATION,ORIGIN,CORE) VALUES ('.$row['PROJEUROPID']. ', "' .$LabelSolr. '", "DELETE","ref_projeurop")');
}
$nb = $dbHALV3->exec('DELETE FROM `REF_PROJEUROP` WHERE `VALID`="INCOMING" AND `PROJEUROPID` NOT IN (select distinct METAVALUE from `DOC_METADATA` where METANAME ="europeanProject")');
println($nb.' REF_PROJEUROP (INCOMING) sans lien vers DOCUMENT');
// Auteur
$sql = $dbHALV3->query('SELECT AUTHORID FROM `REF_AUTHOR` WHERE `VALID`="INCOMING" AND `AUTHORID` NOT IN (select distinct AUTHORID from `DOC_AUTHOR`)');
foreach ( $sql->fetchAll() as $row ) {
    $dbSOLR->exec('INSERT INTO INDEX_QUEUE (DOCID,APPLICATION,ORIGIN,CORE) VALUES ('.$row['AUTHORID']. ', "' .$LabelSolr. '", "DELETE","ref_author")');
}
$nb = $dbHALV3->exec('DELETE FROM `REF_AUTHOR` WHERE `VALID`="INCOMING" AND `AUTHORID` NOT IN (select distinct AUTHORID from `DOC_AUTHOR`)');
println($nb.' REF_AUTHOR (INCOMING) sans lien vers DOCUMENT');
// Structure
$sql = $dbHALV3->query('SELECT STRUCTID FROM `REF_STRUCTURE` WHERE `VALID`="INCOMING" AND STRUCTID NOT IN (select distinct STRUCTID from `DOC_AUTSTRUCT`)');
$nb = 0;
foreach ( $sql->fetchAll() as $row ) {
    $exist = (int) $dbHALV3->query('SELECT count(*) FROM `REF_STRUCT_PARENT` WHERE PARENTID='.$row['STRUCTID'])->fetchColumn();
    if ( $exist == 0 ) {
        $dbHALV3->exec('DELETE from REF_STRUCTURE where STRUCTID='.$row['STRUCTID']);
        $dbSOLR->exec('INSERT INTO INDEX_QUEUE (DOCID,APPLICATION,ORIGIN,CORE) VALUES ('.$row['STRUCTID']. ', "' .$LabelSolr. '", "DELETE","ref_structure")');
        $nb++;
    }
}
println($nb.' REF_STRUCTURE (INCOMING) sans lien vers DOCUMENT');
$nb = $dbHALV3->exec('DELETE FROM `REF_STRUCT_PARENT` WHERE `PARENTID` NOT IN (select STRUCTID from `REF_STRUCTURE`)');
println($nb.' REF_STRUCT_PARENT (PARENTID) sans lien vers REF_STRUCTURE');
$nb = $dbHALV3->exec('DELETE FROM `REF_STRUCT_PARENT` WHERE `STRUCTID` NOT IN (select STRUCTID from `REF_STRUCTURE`)');
println($nb.' REF_STRUCT_PARENT (STRUCTID) sans lien vers REF_STRUCTURE');
// Alias objet référentiel
foreach ( ['AUTHORID'=>'REF_AUTHOR', 'STRUCTID'=>'REF_STRUCTURE', 'ANRID'=>'REF_PROJANR', 'PROJEUROPID'=>'REF_PROJEUROP', 'JID'=>'REF_JOURNAL'] as $pk=>$tble ) {
    $nb = $dbHALV3->exec('DELETE FROM `REF_ALIAS` WHERE REFID NOT IN (select '.$pk.' from `'.$tble.'`) AND REFNOM = "'.$tble.'"');
    println($nb.' '.$pk.' n existant plus dans '.$tble);
}

//doublon de données
$nb = 0;
$docids = array();
$sql = $dbHALV3->query('select *, count(*) as NUM from DOC_METADATA where 1 group by DOCID,METANAME,METAVALUE,METAGROUP having NUM>1');
foreach ( $sql->fetchAll() as $row ) {
    $metagroup = $row['METAGROUP'];
    if (is_string($metagroup)) {
        $condGroup = "METAGROUP = '$metagroup'";
    } elseif (!$metagroup) {
        $condGroup = 'METAGROUP IS NULL';
    } else {
        println("Warning: METAGROUP = $metagroup (ni chaine ni null)");
    }
    $docid = (int) $row['DOCID'];
    $metanameStr = '"'  .$row['METANAME'].   '"';
    $metavalueStr = '"'  . addslashes($row['METAVALUE']).   '"';
    $nb = $row['NUM']-1;
    $dbHALV3->exec("DELETE from DOC_METADATA where DOCID=$docid  and METANAME=$metanameStr and $condGroup and METAVALUE=$metavalueStr limit $nb");
    $nb++;
    $docids[] = $row['DOCID'];
}
println($nb.' doublon de données dans DOC_METADATA');
$nb = 0;
$sql = $dbHALV3->query('select *, count(*) as NUM from DOC_AUTHOR where 1 group by AUTHORID,DOCID having NUM>1');
foreach ( $sql->fetchAll() as $row ) {
    $dbHALV3->exec('DELETE from DOC_AUTHOR where AUTHORID='.$row['AUTHORID'].' and DOCID='.$row['DOCID'].' limit '.($row['NUM']-1));
    $nb++;
    $docids[] = $row['DOCID'];
}
println($nb.' doublon de données dans DOC_AUTHOR');
$nb = 0;
$sql = $dbHALV3->query('select *, count(*) as NUM from DOC_AUTSTRUCT where 1 group by DOCAUTHID,STRUCTID having NUM>1');
foreach ( $sql->fetchAll() as $row ) {
    $dbHALV3->exec('DELETE from DOC_AUTSTRUCT where DOCAUTHID='.$row['DOCAUTHID'].' and STRUCTID='.$row['STRUCTID'].' limit '.($row['NUM']-1));
    $nb++;
    // $docids[] = $row['DOCID']; // Pas de docid dans cette table pour l'instant!
}
println($nb.' doublon de données dans DOC_AUTSTRUCT');
foreach ( array_unique($docids) as $docid ) {
    $dbSOLR->exec('INSERT INTO INDEX_QUEUE (DOCID,APPLICATION,ORIGIN,CORE) VALUES (' .$docid. ', "' .$LabelSolr. '", "UPDATE","hal")');
}
// doublon de données dans DOC_LOG
$nb = 0;
$sql = $dbHALV3->query('select *, count(*) as NUM from DOC_LOG where 1 group by DOCID,UID,LOGACTION,MESG,DATELOG having NUM>1');
foreach ( $sql->fetchAll() as $row ) {
    $bind = [  $row['MESG'] ];   // Il faut escape les quote du message... et autre injection (volontaire ou pas)
    $dbHALV3->exec('DELETE from DOC_LOG where DOCID='.$row['DOCID'].' and UID='.$row['UID'].' and LOGACTION="'.$row['LOGACTION'].'" and (MESG IS NULL OR MESG=?) and DATELOG="'.$row['DATELOG'].'" limit '.($row['NUM']-1),  $bind);
    $nb++;
}
println($nb.' doublon de données dans DOC_LOG');

// metavalue >1 alors que meta unique normalement
//select *, count(*) as NUM from DOC_METADATA where METANAME NOT IN ('abstract', 'acm ', 'afssa_thematique', 'anrProject', 'authorityInstitution', 'bioemco_team', 'brgm_team', 'brgm_thematique', 'collaboration', 'committee', 'conferenceOrganizer', 'director', 'domain', 'europeanProject', 'funding', 'hcl_team', 'hcl_thematique', 'identifier', 'jel', 'keyword', 'localReference', 'mesh', 'pastel_library', 'pastel_thematique', 'publisher', 'scientificEditor', 'seeAlso', 'serie', 'seriesEditor', 'subTitle', 'tematice_discipline', 'tematice_levelTraining', 'tematice_studyField', 'thesisSchool', 'title') group by CONCAT_WS('',DOCID,METANAME) having NUM>1

println();

$timeend = microtime(true);
$time = $timeend - $timestart;
verbose('> Script executé en ' . number_format($time, 3) . ' sec.');
