<?php

/*---------  Chargement de l'Autoload et des configurations de HAL -----------*/
set_time_limit(0);

ini_set("memory_limit", '2048M');
ini_set("display_errors", '1');
$timestart = microtime(true);

// Definition par les variables d'environnement
putenv('PORTAIL=hal');
putenv('APPLICATION_ENV=development');
putenv('CACHE_ROOT=' . __DIR__ . '/cache');
putenv('DATA_ROOT='  . __DIR__ . '/data');
putenv('DOCS_ROOT='  . __DIR__ . '/docs');
define('PHPUNITTEST', True);

define('RESSOURCESDIR', __DIR__ . '/ressources');

/*---------  Définition de l'environnement -----------*/
set_include_path(implode(PATH_SEPARATOR, array_merge(array(realpath(__DIR__ . '/../library')), array(get_include_path()))));
$autoloadFile = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require_once $autoloadFile;
}
require_once 'Zend/Application.php';

//Portail par default

/*------------ Chargement des constantes de l'application et de la BDD ----------------*/
require_once __DIR__. '/../library/Hal/constantes.php';
require_once __DIR__. '/../public/bddconst.php';

// Il faut une application et la BD
try {
    $application = new Zend_Application(APPLICATION_ENV, APPLICATION_INI);
    // On a besoin assez tot des constantes, notamment pour RuntimeConstDef
    $application->getBootstrap()->bootstrap();
    /** BUG Zend Phpunit... */
    //$application->getBootstrap()->getPluginResource('frontcontroller')->init();
    //$application->getBootstrap()->getPluginResource('frontcontroller')->init();
     Hal_Translation_Plugin::initLanguages();
} catch (Exception $e) {
    die($e->getMessage());
}

/* Fonction appele dans Hal_Plugin : seulement pour HAL */
if (Zend_Registry::get('APPLICATION_NAME' ) == 'HAL') {
    RuntimeConstDef('hal_test.archives-ouvertes.fr', 'hal', Hal_Site_Portail::MODULE, 'https');
    $_SERVER['SERVER_NAME'] = 'hal-test.archives-ouvertes.fr';
    /** @var Zend_Translate_Adapter $translator */
//    $translator = Zend_Registry::get('Zend_Translate');
//    $translator->addTranslation(__DIR__ . '/ressources/translations');
     // Preparation des arborescences de data docs ...
    Ccsd_Tools::rrmdir(SPACE_DATA);
    Ccsd_Tools::copy_tree(RESSOURCESDIR . '/data/.', SPACE_DATA);
}

// Set the current site and portail
$halSite = Hal_Site_Portail::loadSiteFromId(1);
Hal_Site::setCurrentPortail($halSite);
Hal_Site::setCurrent($halSite);