<?php
/**
 * Insertion des données HCERES
 * - référentiel
 * - données
 */
error_reporting(E_ALL ^ E_DEPRECATED);

define('ENV', 'PREPROD');

putenv('PORTAIL=hceres');
putenv('CACHE_ROOT=/cache/hal/preprod');
putenv('DATA_ROOT=/data/hal/preprod');
//todo à changer
putenv('DOCS_ROOT=/docs/preprod');

//set_include_path('.:/usr/local/var/www/htdocs/ccsd/hal/vendor/library/:/usr/local/var/www/htdocs/ccsd/include');
//set_include_path('.:/sites/phplib_preprod/');

//require_once 'loadZendHeader.php';
define('LOGFILE', '/sites/logs/php/hal/hceres.log');
define('PATHDOCS', '/docs/preprod/');
require_once 'loadHalHeader.php';
define ('SPACE', '/data/hal/preprod/portail/hceres/');
define ('CACHE_PATH', '/cache/preprod/portail/hceres/');
define('DEFAULT_SPACE',  '/data/hal/preprod/portail/default/');
define('DEFAULT_CONFIG_PATH', DEFAULT_CONFIG_ROOT .   'portail');

//Compte propriétaire des dépôts
$uid = 1;  //'ybarborini';
//Chemin vers le ficher CSV du référentiel des entités évaluées
$referentielFile = SPACE . 'import/referentiel.txt';
//Chemin vers le fichier CSV de rapports
$reportFile = SPACE . 'import/rapportshceres.txt';
//Chemin vers le répertoire contenant les fichiers des rapports
$reportDir = SPACE . '/import/pdf/';
// SID du portail de l'hceres
$sid = 5408;

if (ENV == 'LOCAL') {
    $db = new Zend_Db_Adapter_Pdo_Mysql(['host' => 'localhost', 'username' => 'root', 'password' => 'root', 'dbname'   => 'hceres']);
    $loadReferentiel = false;
    $loadReport = true;
} else if (ENV == 'PREPROD') {
    $db = new Zend_Db_Adapter_Pdo_Mysql(['host' => 'localhost', 'username' => 'root', 'password' => 'password', 'dbname'   => 'HALV3_PREPROD']);
    $loadReferentiel = false;
    $loadReport = true;
}
define ('SITEID', $sid);

Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'fr' ) );

/**
 * Tableau de correspondance entre les colonnes et les champs du référentiel
 */
$correspRef = [
    0   =>    'HCERESID',
    1   =>    'IDENTIFIANT',
    2   =>    'IDENTIFIANT',
    3   =>    'IDENTIFIANT',
    4   =>    'NOM',
    5   =>    'NOM_USAGE',
    6   =>    'SIGLE',
    7   =>    'NOM_ALIAS',
    9   =>    'TYPEHCERES', //voir pour l'enum
    10  =>    'STYPEHCERES',
    11  =>    'ADRESSE',
    12  =>    'PAYSID',
    13  =>    'VILLE',
    14  =>    'REGION'
];

/**
 * Tableau de correspondace entre les colonnes et les champs du rapport
 */
$correspReport = [
    0   =>    'localReference',
    3   =>    'type',
    1   =>    'title',
    21  =>    'abstract',
    20  =>    'keyword',
    8   =>    'language',
    7   =>    'date',
    //   =>    'hceres_dom_local',
    //   =>    'hceres_domsci_local',
    //   =>    'hceres_domapp_local',
    //   =>    'hceres_erc_local',
    9   =>    'hceres_campagne_local',
    //0   =>    'hceres_entite_local',
    //   =>    'hceres_etabsupport_local',
    //   =>    'hceres_etabassoc_local',
    //   =>    'hceres_cohabilitation_local',
    2   =>    'file'
];

if ($loadReferentiel) {
    loadReferential($referentielFile, $correspRef, $db);
}

if ($loadReport) {
    loadReport($reportFile, $correspReport, $db, $sid, $uid, $reportDir);
}

/**
 * @param string $csvFile
 * @param array $corresp
 * @param Zend_Db_Adapter_Pdo_Mysql $db
 */
function loadReferential(string $csvFile, array $corresp, Zend_Db_Adapter_Pdo_Mysql $db)
{
    if(file_exists($csvFile)) {

        $src = file_get_contents($csvFile);

        foreach (explode("\r", $src) as $rowNb => $row) {
            if ($rowNb == 0) {
                continue;
            }
            $data = explode("\t", mb_convert_encoding($row,'utf-8','utf-16'));
            if (trim($data[0]) == '') {
                continue;
            }
            insertDataReferential($data, $corresp, $db);
        }
    } else {
        die('fichier non existant');
    }
}

/**
 * @param array $data
 * @param array $corresp
 * @param Zend_Db_Adapter_Pdo_Mysql $db
 */
function insertDataReferential(array $data, array $corresp, Zend_Db_Adapter_Pdo_Mysql $db)
{
    $entity = [];
    foreach ($corresp as $column => $field) {
        if (isset($data[$column])) {
            if ($column == 0) { //hceresid
                $data[$column] = intval($data[$column]);
            } else if ($column == 12) {
                $data[$column] = strtolower(substr(trim($data[$column]), 0, 2 ));
            }
            if (!isset($entity[$field]) || trim($entity[$field]) == '') {
                $entity[$field] = trim($data[$column]);
            }
        }
    }


    echo '"' . $entity['type'] . '"';exit;



    $entity['VALID'] = 'VALID';
    try {
        if ($entity['HCERESID']!= '' && $db->insert('REF_HCERES_NEW', $entity)) {
            $result = ' OK';
        } else {
            $result = ' NOK';
        }
    } catch(Exception $e) {
        $result = ' NOK';
        var_dump($entity);
    }
    println('Insertion de la donnée ' . $entity['HCERESID'] . ': ' . $result);
}

/**
 * @param string $csvFile
 * @param array $corresp
 * @param Zend_Db_Adapter_Pdo_Mysql $db
 * @param int $sid
 * @param int $uid
 */
function loadReport(string $csvFile, array $corresp, Zend_Db_Adapter_Pdo_Mysql $db, int $sid, int $uid, string $reportDir)
{
    if(file_exists($csvFile)) {

        $src = file_get_contents($csvFile);

        foreach (explode("\r", $src) as $rowNb => $row) {
            if ($rowNb == 0) {
                continue;
            }
            $data = explode("\t", mb_convert_encoding($row,'utf-8','utf-16'));
            if (trim($data[0]) == '') {
                continue;
            }
            insertReport($data, $corresp, $sid, $uid, $reportDir);
        }
    } else {
        die('fichier ' . $csvFile . ' non existant');
    }
}

/**
 * @param array $data
 * @param array $corresp
 * @param int $sid
 * @param int $uid
 */
function insertReport(array $data, array $corresp, int $sid, int $uid, string $reportDir)
{
    //echo DEFAULT_CONFIG_PATH;exit;
    $report = [];
    foreach ($corresp as $column => $field) {
        if (isset($data[$column])) {
            if (!isset($report[$field]) || trim($report[$field]) == '') {
                $report[$field] = preg_replace('/\s\s+/', '', trim($data[$column]));
            }
        }
    }
    if (! isset($report['type']) || $report['type'] == '') {
        return false;
    }
    $report['language'] = strtolower($report['language']);
    list($day, $month, $year) = explode('/', $report['date']);
    $report['date'] = $year . '-' . $month . '-' . $day;
    $report['language'] = strtolower($report['language']);
    $report['hceres_campagne_local'] = substr($report['hceres_campagne_local'], 0, 5);

    if ($report['type'] == 'RETAB') {
        $report['type'] = 'REPORT_ETAB';
    }

    echo $report['localReference'];
    //var_dump($report);exit;

    //todo voir pour supprimer les domaines
    $report['domain'] = ['shs'];
    //todo modifier l'id de l'établissement
    //$report['hceres_entite_local'] = new Ccsd_Referentiels_Hceres(1);
    $report['hceres_entite_local'] = 1;

    if ($report['file'] != '' && is_file($reportDir . $report['file'])) {
        $report['file'] = $reportDir . $report['file'];
    } else {
	    //echo $reportDir . $report['file'];
        echo "fichier non présent";
    	return false;
    }
    $document = createHalDocument($sid, $uid, $report['type']);

    addMetaHalDocument($document, $report);

    addFileHalDocument($document, $report['file']);

    addAuthorHalDocument($document);

    //print_r($document->toArray());
    try {
        Hal_Document_Validity::isValid($document);
    } catch(Exception $e) {
        var_dump($e->getErrors());
        echo " | META NOK | ". serialize($e->getErrors()) . "\n";
        return false;
    }
    $document->setTypeSubmit(Hal_Settings::SUBMIT_INIT);
    $docid = $document->save(1, false);
    if ($docid == 0 ) {
        echo " | INSERT NOK \n";
    } else {
        echo " | " . $document->getId() . " \n";
    }
}

/**
 * Création d'un document
 * @param $sid
 * @param $uid
 * @param $typdoc
 * @return Hal_Document
 */
function createHalDocument($sid, $uid, $typdoc) 
{
    $document = new Hal_Document();
    $document->setTypdoc($typdoc);
    
    $document->setSid($sid);
    $document->setContributorId($uid);
    $document->setInputType(Hal_Settings::SUBMIT_ORIGIN_SWORD);

    return $document;
}

/**
 * Ajout des métadonnées au document
 * @param Hal_Document $document
 * @param array $report
 * @return Hal_Document
 */
function addMetaHalDocument(Hal_Document $document, array $report) 
{
    $type = $report['type'];
    $docMeta = [];
    foreach ($report as $meta => $value) {
        if ($meta == 'file' || $meta == 'type') {
            continue;
        }

        if ($meta == 'hceres_entite_local') {
            $docMeta[$meta] = new Ccsd_Referentiels_Hceres($value);
        } else if ($meta == 'title' || $meta == 'abstract') {
            //Par défaut le titre et le résumé sont en français
            $docMeta[$meta]['fr'] = $value;
        } else if ($meta == 'localReference') {
            $docMeta[$meta][] = $value;
        } else if ($meta == 'keyword') {
            $docMeta[$meta]['fr'] = explode(',', $value);
        } else if (in_array($meta, ['language', 'date', 'hceres_campagne_local', 'hceres_entite_local', 'domain'])) {
            if ($meta == 'date') {
                $value = str_replace('/', '-', $value);
            }
            $docMeta[$meta] = $value;
        } else if ($meta == 'hceres_dom_local' && in_array($type, ['REPORT_RECH', 'REPORT_LABO', 'REPORT_MAST', 'REPORT_DOCT'])) {
            $docMeta[$meta] = explode(',', $value);
        } else if ($meta == 'hceres_domsci_local' && in_array($type, ['REPORT_LABO', 'REPORT_DOCT'])) {
            $docMeta[$meta] = $value;
        } else if ($meta == 'hceres_domapp_local' && in_array($type, ['REPORT_LABO', 'REPORT_LICE', 'REPORT_LPRO', 'REPORT_MAST'])) {
            $docMeta[$meta] = $value;
        } else if ($meta == 'hceres_erc_local' && in_array($type, ['REPORT_LABO'])) {
            $docMeta[$meta] = explode(',', $value);
        } else if ($meta == 'hceres_etabsupport_local' && in_array($type, ['REPORT_RECH', 'REPORT_LABO', 'REPORT_LICE', 'REPORT_LPRO', 'REPORT_MAST', 'REPORT_DOCT'])) {
            $docMeta[$meta] = new Ccsd_Referentiels_Hceres($value);
        } else if ($meta == 'hceres_etabassoc_local' && in_array($type, ['REPORT_RECH', 'REPORT_LABO', 'REPORT_DOCT'])) {
            $docMeta[$meta] = explode(',', $value);
        } else if ($meta == 'hceres_cohabilitation_local' && in_array($type, ['REPORT_LICE'])) {
            $docMeta[$meta] = explode(',', $value);
        }
    }

    //Voir pour les référentiels
    $document->setMetas($docMeta);
    return $document;
}

/**
 * Ajout d'un fichier à un document
 * @param Hal_Document $document
 * @param string $filepath
 * @return Hal_Document
 */
function addFileHalDocument(Hal_Document $document, string $filepath)
{
    $file = new Hal_Document_File();
    $file->setType('file');
    $file->setOrigin('author');
    $file->setDefault(1);
    $file->setName(basename($filepath));
    $file->setPath($filepath);
    $file->setSize(filesize($filepath));

    $document->setFiles([$file]);
    $document->getFiles()[0]->setDefault(true);

    return $document;
}


function addAuthorHalDocument(Hal_Document $document)
{
    //todo à revoir pour ne pas avoir ce pb (supprimer les contrôles)
    $author = new Hal_Document_Author();
    $author->setFirstname('Hceres');
    $author->setLastname('Hceres');
    $document->addAuthor($author);

    return $document;
}

