<?php
/**
 * Created by PhpStorm.
 * User: baptiste
 * Date: 06/07/2015
 * Time: 14:45
 */

/*
 * Script de nettoyage pour supprimer les métadonnées d'un document qui ne sont pas censées apparaître pour ce type de document ou pour ce portail.
 */

set_time_limit(0);

ini_set("memory_limit", '2048M');
ini_set("display_errors", '1');
$timestart = microtime(true);

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
        'test|t' => 'Mode Test (sans suppression des metas)',
        'sql|s-s' => 'Requête SQL'
    ));
    $opts->parse();
} catch (Zend_Console_Getopt_Exception $e) {
    die($opts->getUsageMessage() . PHP_EOL . PHP_EOL);
}

//Environnement
define('APPLICATION_ENV', (isset($opts->e) && in_array($opts->e, $listEnv)) ? $opts->e : $defaultEnv);

// For calling coherenceMetaDocid...
$recurseOptions=" --env " . APPLICATION_ENV;
if (isset($opts->t)){
    $recurseOptions .= " -t";
}

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

//Message d'aide si pas de requête sql ou de docid renseigné
if (!isset($opts->s) && (!isset($opts->d))) {
    die($opts->getUsageMessage() . PHP_EOL . PHP_EOL);
}

//Récupère la liste des docid à traiter
$arrayOfDocId = [];
if (isset($opts->d)) {
    // Un docid ou %
    if ($opts->d == "%"){
        $arrayOfDocId = $db->fetchCol("SELECT DOCID from DOCUMENT");
    } else {
        $arrayOfDocId[] = $opts->d;
    }
} else {
    // Une requete SQL en option
    if (isset($opts->s)) {
        $arrayOfDocId = $db->fetchCol("SELECT DOCID from DOCUMENT where ".$opts->s);
    }
}

//Boucle pour chaque Docid
foreach ( $arrayOfDocId as $docid ) {
    $output = shell_exec('php coherenceMetaDocid.php -d '.$docid . $recurseOptions);
    echo $output;
}

$timeend = microtime(true);
$time = $timeend - $timestart;
println();
println('> Fin du script: ' . date("H:i:s", $timeend));
println('> Script executé en ' . number_format($time, 3) . ' sec.');
println();

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