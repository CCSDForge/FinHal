<?php

// Definition des constante necessaire a HAL
// Charge dans publi/index.php
// Dans les scripts
//  ...
// On defini ici les constantes AVANT le lancement du Bootstrap de l'application.
//
// Les autres constantes statiques sont definies par l'application.ini
//
// Les constantes dynamiques (:-)) sont definies dans un plugin
//   library/Hal/Plugin.php
// Celle-ci ont besoin de la requete Http, de l'url (pour une collection,...)

define ('ENV_PROD', 'production');
define ('ENV_PREPROD', 'preprod');
define ('ENV_TEST', 'testing');
define ('ENV_DEV', 'development');

/**
 * Try to get the best value between env variable and defaut value
 * @param $envvar
 * @param $default
 * @return array|false|string
 */
function getvalue($envvar, $default) {
    $val =  getenv($envvar) ? getenv($envvar) : $default;
    return ($val);
}

/**
 * Define a directory constant but control that directory exists
 * @param $var
 * @param $envvar
 * @param $default
 */
function myDirDefine($var, $envvar, $default) {
    $dir =  getvalue($envvar, $default);
    if (!is_dir($dir)) {
        error_log("FATAL ERROR: $dir n'est pas un repertoire valide pour $var");
        exit(1);
    }
    define($var, realpath($dir));
    
}

/**
 * Define a file constant but control that file exists
 * @param $var
 * @param $envvar
 * @param $default
 */
function myFileDefine($var, $envvar, $default) {
    $file =  getvalue($envvar, $default);
    if (!is_file($file)) {
        error_log("FATAL ERROR: $file n'est pas un fichier valide pour $var");
        exit(1);
    }
    define($var, realpath($file));
    
}

/**
 * Define a constant with the best value from Env variable and default value
 * @param $var
 * @param $envvar
 * @param $default
 * @return array|false|string
 */
function myDefine($var, $envvar, $default) {
    $dir =  getvalue($envvar, $default);
    define($var, $dir);
    return $dir;
}

if (4 - 1 > 4) {
    // Foo test for putting def of constant that are never executed!!!
    // Suppress all PhpStorm Warning for undefined constants it don't find
    // 4 - 1 and Phpstrom don't detect thic as dead code!
    define('CCSDLIB',      APPROOT . '/vendor/ccsd/library');
    define('PWD_PATH',     APPROOT . '/config');
    define('APPROOT',      __DIR__ . '/../..');
    define('DATA_ROOT',    APPROOT . '/data/hal');
    define('SHARED_DATA',  APPROOT . '/share');
    define('CACHE_CV',     APPROOT . '/cache/cv');
    define('PATHDOCS',     APPROOT . '/docs');
    define('PATHTEMPDOCS', APPROOT . '/docs/tmp');
    define('PDFDOCAPP'   , 'mergePdfApp');
    define('AUREHAL_URL' , 'http://VersAureHal/');
    define('CCSDLIB_SRC' , APPROOT . '/library/public');
    define('PATH_TRANSLATION' , APPROOT .'/applicationXX/languages');
    define('CCSD_MAIL_PATH' , '/sites/mail/hal');
    define('SWH_SERVICE', 'http://swh.org');
    define('LATEX2RTFCMD', "/usr/local/bin/latex2rtf");
    define('SWH_USER', 'swhuser');     // defined by pwd.json
    define('SWH_PWD' , 'swhpasswd');   // defined by pwd.json
    define('DOARXIV', true);// active le module de transfert vers Arxiv
    define('DOPMC', true);    // active le module de transfert vers PMC
    define('DOHAL', false);   // active le module transfert vers HAL pour HalSPM
    define('DOSWH', true);   // active le module transfert vers SWH
    define('REPEC_ROOT_FILE', SPACE_DATA . APPLICATION_ENV  . "/repec/");
    define('REPEC_HANDLE', 'RePEc:hal:');
    define('AOFR_SCHEMA_URL', 'https://hal.archives-ouvertes.fr/documents/aofr.xsd');
    define('IDP_ASSO_AUTO', False);
    define('IDP_CREATE_AUTO',false);
    define('IDP_NO_CREATE_FORM',false);
    define('IDP_CONFIG_DIR', APPROOT . '/' . CONFIG);

    define('THUMB_URL', 'Defined in application.ini');
    define('PDFINFO', 'Defined in application.ini');
    define('PDFFONTS', 'Defined in application.ini');

    define('CROSSREF_USER', 'defined in pwd.json');
    define('CROSSREF_PWD', 'defined in pwd.json');
    define('CROSSREF_URL', 'defined in pwd.json');
    define('HAL_HOST', 'defined in pwd.json');

    define('SWORD_API_URL', 'defined in application.ini');
    define('SOLR_API', 'defined in application.ini');
}
// Récupération de la variable permettant de retrouver le repertoire 'application'
// peut etre defini avant par script et pas pas variable d'env
myDefine('APPLICATION_DIR',  'APPLICATION_DIR', 'application');

// Define path to application directory
myDirDefine('APPLICATION_PATH', 'APPLICATION_PATH', __DIR__ . '/../../' . APPLICATION_DIR . '/');

myDirDefine('APPROOT', 'APPROOT', APPLICATION_PATH . '/../');

// Define application environment: sauf si deja definie lors d'un script en ligne de commande
// L'environnement peut etre defini avant par script avec parametre, donc pas pas getenv!
defined('APPLICATION_ENV') || myDefine('APPLICATION_ENV', 'APPLICATION_ENV', ENV_DEV);

// Define path to Cache
myDirDefine('CACHE_ROOT', 'CACHE_ROOT', APPROOT . "/cache");

// Define path to Data
myDirDefine('DATA_ROOT', 'DATA_ROOT', APPROOT . '/data');

// Define path to Docs
myDirDefine('DOCS_ROOT', 'DOCS_ROOT', APPROOT . '/documents');

//Define Hal portal
// TODO: On ne devrait pas definir le portail a default mais a hal, car default n'est PAS un portail
myDefine('PORTAIL',  'PORTAIL', 'default');

$instance = getenv('INSTANCE');
if ($instance) {
    $sep = '-';
} else {
    # Compatibilite
    $instance = '';
    $sep = '';
}
define('INSTANCEPREFIX',$instance . $sep);
$inifile = APPLICATION_PATH . '/configs/' . INSTANCEPREFIX . 'application.ini';

myFileDefine('APPLICATION_INI', 'APPLICATION_INI', $inifile );
define ('SPACE_DATA', DATA_ROOT . '/' . APPLICATION_ENV);
define ('SPACE_DEFAULT', 'default');
define ('SPACE_SHARED', 'shared');
define ('SPACE_PORTAIL', 'portail');
define ('SPACE_COLLECTION', 'collection');
define ('CONFIG', 'config'.$instance.'/');
define ('CONFIGDIR', APPROOT . '/' . CONFIG);
define ('THESAURUS', 'Thesaurus/');
define ('LIBRARY', 'library/');
define ('PAGES', 'pages/');
define ('LAYOUT', 'layout/');
define ('PUBLIC_DEF', 'public/');
define ('LANGUAGES', 'languages/');
define ('DEFAULT_SID', 1);

define ('ZT', 'Zend_Translate');
define ('APACHE_USER', 'nobody');

define ('HAL_PIWIK_ID', 92);

/* specifiques applications */
/* 
 * API
 * CACHE_PATH   defini dans le Bootstrap de l'API
 *
 * HALMS
 * CACHE_PATH   defini dans le Bootstrap de HALMS
 *
 * CV
 * SESSION_NAMESPACE defini dans le Bootstrap du CV
 *
 * HAL
 * PATHTEMPIMPORT ./library/Hal/Document/loadFromWs.php
 * CACHE_PATH   defini dans le Plugin
*/

require_once APPROOT . '/' . PUBLIC_DEF . 'bddconst.php';

/**
 * @param string $hostname
 * @param string $name
 * @param string $module (SPACE_COLLECTION ou SPACE_PORTAIL)
 * @param string $proto
 * @return string
   */
function RuntimeConstDef($hostname, $name, $module = SPACE_PORTAIL,  $proto = "https") {

    // Must be called once
    if (defined('RUNTIME_CONST_CALL')) {
        // But we must return the good result
        return ($module == SPACE_COLLECTION) ? Hal_Site::TYPE_COLLECTION: Hal_Site::TYPE_PORTAIL;
    }
    // Todo: Mettre cette definition ailleurs, dans bootstrap initSession?
    define ('SESSION_NAMESPACE', 'session-' . session_id());

    if ($module == SPACE_COLLECTION) {
        $type = Hal_Site::TYPE_COLLECTION;
        define ('MODULE', SPACE_COLLECTION);
        define ('SPACE_URL'        , "/$name/public/");
        define ('DEFAULT_SPACE_URL', "/$name/default/");    // Pas utilisee ?
        define ('COLLECTION'       , $name);              // Supprimer L'USAGE!!!
        define ('PREFIX_URL'       , "/$name/");
        define ('COLLECTION_URL'   , "$proto://$hostname"); // Pas utilisee ?
    } else {
        //Affichage d'un portail
        $type = Hal_Site::TYPE_PORTAIL;
        define ('MODULE', SPACE_PORTAIL);
        define ('SPACE_URL', '/public/');
        define ('DEFAULT_SPACE_URL', '/default/');     // Pas utilisee ?
        define ('PREFIX_URL', '/');
    }

    /** DOC: Composante du portail/Collection a utiliser SEULEMENT dans les Path (fichier ou Url) */
    define('SPACE_NAME', $name);
    /** DOC: Composante du portail/Collection a utiliser en dehors des Path  */
    define('SITENAME', SPACE_NAME);

    define('SPACE', SPACE_DATA . '/'. MODULE . '/' . SPACE_NAME . '/');
    define('PATH_PAGES', SPACE_DATA . '/'. MODULE . '/' . SPACE_NAME . '/' . PAGES );

    // Chemin d'accès aux configuration par défaut pour les conférences et portails
    define('DEFAULT_CONFIG_PATH', DEFAULT_CONFIG_ROOT .  MODULE);

    // Chemin d'accès aux données par défaut pour une conférence ou un portail
    define('DEFAULT_SHARED_DATA', APPLICATION_PATH."/../".CONFIG . MODULE . '/');

    // Chemin d'accès aux différents dossiers de cache : pour une conférence, les cv, les documents, le cache par défaut
    define('CACHE_PATH', CACHE_ROOT . '/'. APPLICATION_ENV .'/' . MODULE . '/' . SPACE_NAME );
    define('DEFAULT_CACHE_PATH', CACHE_ROOT . '/'. APPLICATION_ENV . '/'. MODULE . '/' . SPACE_DEFAULT);

    define('RUNTIME_CONST_CALL', true);
    return $type;
}