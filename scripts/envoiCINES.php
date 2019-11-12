<?php
/**
 * Script envoi des docs à archiver au CINES
 */


$tDepart = microtime(true);

$localopts = [
    'docid=i' => 'DOCID unique à archiver',
    'sqlwhere=s' => ' pour spécifier la condition SQL à utiliser pour trouver les DOCID dans la table DOCUMENTS',
    'resume=i' => 'DOCID dans la table DOC_ARCHIVE de reprise pour les documents en échec de conversion avec PDF Creator',
    'from=s' => 'Date de début pour les dépôts',
    'to=s' => 'Date de fin pour les dépôts',
    'forceconv' => 'Ne teste pas si le fichier est archivable, force la conversion'
];


if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

define('DEFAULT_CONFIG_PATH', DEFAULT_CONFIG_ROOT . SPACE_PORTAIL);

if (APPLICATION_ENV == 'development') {
    define('LOG_DIR', realpath(sys_get_temp_dir()) . '/');
} else {
    define('LOG_DIR', '/sites/logs/php/archivage-cines/');
}

require_once(APPLICATION_PATH . '/../public/bddconst.php');

Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator('fr'));

$db = Zend_Db_Table_Abstract::getDefaultAdapter();

// Recherche des docid A envoyer
$monFichierLogs = LOG_DIR . APPLICATION_ENV . '-script-envoiArchivage.requete';

$dateButoir = date('Y-m-d', mktime(0, 0, 0, date("m") - 3, date("d"), date("Y")));
$dateButoirVeille = date('Y-m-d', mktime(0, 0, 0, date("m") - 3, date("d") - 1, date("Y")));

$maTable = 'DOCUMENT';
$ordre = 'DOCID ASC';
// limite : argument 1 nb enregistrements demandés, argument 2 nombre d'enregistrements à omettre

// Envoi de tous les depots de 3 mois toute version confondue
$conditions = [];
$conditions [] = "FORMAT = 'file'";
$conditions [] = "DOCSTATUS IN (11, 111)";

// si pas de docid particulier ou de requete SQL
if ((!$opts->docid) && (!$opts->sqlwhere) && (!$opts->resume) && (!$opts->from) && (!$opts->to)) {
    $conditions [] = "DATESUBMIT > '" . $dateButoirVeille . "'";
    $conditions [] = "DATESUBMIT < '" . $dateButoir . "'";
} else {
    if ($opts->docid) {
        $conditions [] = "DOCID = " . ( int )$opts->docid;
    }
    if ($opts->sqlwhere) {
        $conditions [] = $opts->sqlwhere;
    }

    if (($opts->from) && ($opts->to)) {
        $conditions [] = "DATESUBMIT >= '" . $opts->from . "'";
        $conditions [] = "DATESUBMIT <= '" . $opts->to . "'";
    }


    /*
     * Recupération après indisponibilité de PDFCreator
     */
    if ($opts->resume) {
        $maTable = Ccsd_Archive::TABLE_DONNEES;
        $conditions = [];
        $conditions [] = "CODE_ERREUR LIKE '%NON CONVERTIBLE%'";
        $conditions [] = "DOCID IN (SELECT DOCID FROM DOCUMENT)";
        $conditions [] = "DOCID >= " . $opts->resume;
    }
}


if ($opts->forceconv) {

    $forceConversion = true;
} else {
    $forceConversion = false;
}


$select = $db->select()->from($maTable)->order($ordre);

foreach ($conditions as $whereCondition) {
    $select->where($whereCondition);
}

$resultats = $db->fetchAll($select);

$configs = $db->getConfig();

$journal = "Requete : " . str_repeat('-', 20) . PHP_EOL;
$journal .= "Base    : " . $configs ['dbname'] . PHP_EOL;
$journal .= "Env     : " . APPLICATION_ENV . PHP_EOL;
$journal .= 'SQL     : ' . $select->__toString() . PHP_EOL;
$journal .= "Nb depots trouves : " . count($resultats) . PHP_EOL;

file_put_contents($monFichierLogs, $journal, FILE_APPEND);


$connectionArchivage = new Ccsd_Archivage_Connection();

if (!$connectionArchivage instanceof Ccsd_Archivage_Connection) {
    $journal .= $connectionArchivage;
    file_put_contents($monFichierLogs, $journal, FILE_APPEND);
    die($journal);
}

echo $journal;

foreach ($resultats as $enregistrement) {
    $monArchive = new Ccsd_Archive ($enregistrement ['DOCID'], APPLICATION_ENV, $connectionArchivage);
    $monArchive->forceConversion = $forceConversion;
    $monArchive->envoiArchivage(false);
}

$duree = microtime(true) - $tDepart;
$journal = "J'ai traite " . count($resultats) . " depots en " . $duree . " s" . PHP_EOL;

file_put_contents($monFichierLogs, $journal, FILE_APPEND);

