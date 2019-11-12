<?php
/**
 * Script de lecture des mails de retour d'archivage du CINES
 */

$tDepart = microtime(true);

define('APPLICATION_ENV', 'production');


if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}


if (APPLICATION_ENV == 'development') {
    define('LOG_DIR', realpath(sys_get_temp_dir()) . '/');
} else {
    define('LOG_DIR', '/sites/logs/php/archivage-cines/');
}

require_once(APPLICATION_PATH . '/../public/bddconst.php');

Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator('fr'));

$monArchive = new Ccsd_Archive(0, APPLICATION_ENV);
$monArchive->lireCompteMail();

