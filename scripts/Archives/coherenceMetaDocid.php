<?php
/**
 * Created by PhpStorm.
 * User: baptiste
 * Date: 08/07/2015
 * Time: 16:57
 */

set_time_limit(0);

ini_set("memory_limit", '2048M');
ini_set("display_errors", '1');

set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/phplib'), array(get_include_path()))));

require_once ('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

define('APPLICATION_PATH', __DIR__ . '/../application');
define ('SPACE_DATA', realpath(dirname(__FILE__) . '/../data'));
define ('SPACE_PORTAIL', 'portail');
define('CONFIG', 'config/');


/* Environnements */
$listEnv = array('development', 'testing', 'preprod', 'production');
$defaultEnv = 'development';


try {
    $opts = new Zend_Console_Getopt(array(
        'help|h' => 'Aide',
        'env|e-s' => 'Environnement (' . implode('|', $listEnv). ') (défaut: ' . $defaultEnv . ')',
        'docid|d-s' => 'Docid(% = tous les documents)',
        'test|t' => 'Mode Test (sans suppression des metas)'
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    die($opts->getUsageMessage() . PHP_EOL . PHP_EOL);
}

//Environnement
define('APPLICATION_ENV', (isset($opts->e) && in_array($opts->e, $listEnv)) ? $opts->e : $defaultEnv);

try {
    if ( APPLICATION_ENV == 'production' ) {
        $library = array('/sites/library');
    } else if ( APPLICATION_ENV == 'preprod' ) {
        $library = array('/sites/library_preprod');
    } else if ( APPLICATION_ENV == 'testing' ) {
        $library = array('/sites/library_test');
    } else {
        $library[] = realpath(APPLICATION_PATH . '/../../library');
    }
    set_include_path(implode(PATH_SEPARATOR, array_merge($library, array(get_include_path()))));

    $application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
    $application->getBootstrap()->bootstrap(array('db'));
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    foreach ($application->getOption('consts') as $const => $value) {
        define($const, $value);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

//Affichage de l'aide
if (isset($opts->h)) { die($opts->getUsageMessage()); }

//Message d'aide si pas de docid renseigné
if (!isset($opts->d)) {
    die($opts->getUsageMessage() . PHP_EOL . PHP_EOL);
}

$docid = $opts->d;

//Récupération du SID du document
$sql = $db->select()->from('DOCUMENT', 'SID')->where('DOCID = ?', $docid);
$sid = $db->fetchOne($sql);
$sql = $db->select()->from('SITE', 'SITE')->where('SID = ?', $sid);
$portail = $db->fetchOne($sql);

//Définition de la constante portail
define('SPACE_NAME', $portail);

println('Portail : '.$portail);
println("Docid : ".$docid);

//Récupération des metadonnées du document
$sql = $db->select()->from('DOC_METADATA', 'METANAME')->where('DOCID = ?', $docid)->where('(SID IS NULL) OR (SID ='.$sid.')');
$metaToDelete = $db->fetchCol($sql);
$sql = $db->select()->from('DOCUMENT', 'TYPDOC')->where('DOCID = ?', $docid);
$typdoc = $db->fetchOne($sql);

$metacoherente = array();
//Récupération des métadonnées du/des fichier(s) .ini
$getMeta = Hal_Settings::getMeta($typdoc);
foreach(array_keys($getMeta['elements']) as $i => $meta){
    if ($getMeta['elements'][$meta]['type'] != 'invisible' && $getMeta['elements'][$meta]['type'] != 'hr'){
        $metacoherente[] = $meta;
    }
}

//Comparaison entre les métadonnées existantes et les métadonnes .ini
$metadiff = array_diff($metaToDelete,$metacoherente);
if (!empty($metadiff)){
    println('Métadonnée(s) détruite(s) pour le docid '.$docid.' :');
    foreach ($metadiff as $i => $meta){
        if (!$opts->t){
            //Suppression de la métadonnée
            $db->delete('DOC_METADATA', 'METANAME = "'.$meta.'" AND DOCID = '.$docid);
        }
        println('- '.$meta);
    }
} else {
    println('Aucune erreur de métadonnée !');
}
println('');


/**
 * Affichage
 * @param string $s
 * @param string $v
 * @param string $color
 */
function println($s = '', $v = '', $color = '')
{
    if ($v != '') {
        switch($color) {
            case 'red'      :  $c = '31m';break;
            case 'green'    :  $c = '32m';break;
            case 'yellow'   :  $c = '33m';break;
            case 'blue'     :  $c = '34m';break;
            default         :  $c = '30m';break;
        }
        $v = "\033[" . $c . $v . "\033[0m";
    }

    print $s . $v . PHP_EOL;
}