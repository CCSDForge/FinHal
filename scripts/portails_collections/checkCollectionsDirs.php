<?php
/**
 * Script pour nettoyer les répertoires de collection
 * + liste des collections qui n'ont pas de répertoire
 *
 * créé un fichier qui contient les commandes à executer pour deplacer les répertoires
 * ne deplace aucun fichier par lui-même
 */

$localopts = [
    'notry' => 'Write commands in file dataToMove.sh',
    'dirs' => 'Report missing directories',
    'move' => 'List extraneous dirs'
];

/** @var Zend_Console_Getopt $opts */
require_once '../loadHalHeader.php';

define('LOG_FILE', '/sites/logs/php/hal/' . basename(__FILE__) . 'log');

$verbose = false;
$debug = false;

if (isset($opts)) {
    if ($opts->verbose) {
        $verbose = true;
    }
    if ($opts->debug) {
        $debug = true;
    }
}

if (!$opts->dirs && !$opts->move) {
    echo $opts->getUsageMessage();
    exit;
}


if ($opts->dirs) {
    reportMissingDirectories();
}

if ($opts->move) {
    moveExtraneousDirs($opts->notry);
}


function getCollectionsCountFromDb()
{
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    return $db->fetchOne("SELECT COUNT(*) AS count FROM SITE WHERE TYPE='COLLECTION'");
}


function getCollectionsFromDb()
{
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    $sql = $db->select()->from('SITE', 'SITE')->where("TYPE='COLLECTION'")->order('SITE ASC');
    $stmt = $db->query($sql);
    $allCollections = $stmt->fetchAll();
    $listeCollections = array_column($allCollections, 'SITE');
    sort($listeCollections);
    return $listeCollections;

}


/**
 * Vérifie sur une collection a un répertoire
 * @param $site
 * @return bool
 */
function collHasDirectory($site)
{

    $root = '/data/hal/' . APPLICATION_ENV . '/collection/';

    $site = $root . $site;
    if (is_dir($site)) {
        return true;
    }

    return false;

}

/**
 * retourne un tableau des répertoires de collection
 * @return array
 */
function getCollDirs()
{
    $root = '/data/hal/' . APPLICATION_ENV . '/collection/';

    foreach (new DirectoryIterator($root) as $file) {
        if (($file->isDir()) && ($file->getFilename() != '.') && ($file->getFilename() != '..')) {
            $collDirs[] = $file->getFilename() . "\n";
        }
    }

    return $collDirs;
}

/**
 * report Missing Directories
 */
function reportMissingDirectories()
{
    foreach (getCollectionsFromDb() as $site) {
        $hasDir = collHasDirectory($site);
        if (!$hasDir) {
            echo 'No directory found for : # ' . $site . ' #' . PHP_EOL;
        }
    }
}

/**
 * @param bool $tryme
 */
function moveExtraneousDirs($notry = false)
{
    $todo = '';
    $root = '/data/hal/' . APPLICATION_ENV . '/collection/';
    $backupDir = $root . '../backupOfExtraneousCollDirs';
    if (($notry) && (!is_dir($backupDir))) {
        mkdir($backupDir);
    }

    $listeCollections = getCollectionsFromDb();

    $dirsToDelete = 0;
    foreach (getCollDirs() as $collectionDirectory) {
        $collectionDirectory = trim($collectionDirectory);
        if (!in_array($collectionDirectory, $listeCollections)) {
            echo 'directory to delete: ' . $collectionDirectory . PHP_EOL;
            if ($notry) {
                $todo .= 'mv ' . $root . $collectionDirectory . ' ' . $backupDir . PHP_EOL;
            }
            $dirsToDelete++;
        }
    }

    if ($todo != '') {
        file_put_contents(__DIR__ . '/dataToMove.sh', $todo);
        echo 'Final step, execute: dataToMove.sh' . PHP_EOL;
    }
}


$timeend = microtime(true);
$time = $timeend - $timestart;

Ccsd_Log::message('Début du script: ' . date("H:i:s", $timestart) . '/ fin du script: ' . date("H:i:s", $timeend), $verbose, '', LOG_FILE);
Ccsd_Log::message('Script executé en ' . number_format($time, 3) . ' sec.', $verbose, '', LOG_FILE);





