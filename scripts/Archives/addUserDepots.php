<?php
/**
 * Created by PhpStorm.
 * User: baptiste
 * Date: 02/02/2016
 * Time: 11:18
 */

set_time_limit(0);

ini_set('memory_limit','8182M');
ini_set("display_errors", '1');
$timestart = microtime(true);

set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/phplib', '/sites/library'), array(get_include_path()))));

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
        'dep|d-s' => '-d 11 = dépôts visible // -d 9 = dépôts validation scientifique // -d 99 = dépôts refusés',
        'test|t' => 'Mode Test (sans modifications)',
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

//Message d'aide si pas de requête sql ou de docid renseigné
if (!isset($opts->s) && (!isset($opts->d))) {
    die($opts->getUsageMessage() . PHP_EOL . PHP_EOL);
}

//Récupère la liste des uid à traiter
$array = [];
if (isset($opts->d)) {
    if ($opts->d == "11"){
        $array = $db->fetchAll("SELECT d.uid, COUNT( u.uid ) AS nbdoc FROM `DOCUMENT` as `d` INNER JOIN USER as `u` ON d.uid = u.uid WHERE DOCSTATUS = 11 OR DOCSTATUS = 111 GROUP BY d.uid");
    } else if ($opts->d == "9"){
        $array = $db->fetchAll("SELECT d.uid, COUNT( u.uid ) AS nbdoc FROM `DOCUMENT` as `d` INNER JOIN USER as `u` ON d.uid = u.uid WHERE DOCSTATUS = 9 GROUP BY d.uid");
    } else if ($opts->d == "99"){
        $array = $db->fetchAll("SELECT d.uid, COUNT( u.uid ) AS nbdoc FROM `DOCUMENT` as `d` INNER JOIN USER as `u` ON d.uid = u.uid WHERE DOCSTATUS = 99 GROUP BY d.uid");
    }
}

foreach ( $array as $uid ) {
    println("Uid : ".$uid['uid']);
    println("NbDocs : ".$uid['nbdoc']);

    if (!$opts->t){
        if ($opts->d == "11"){
            //Ajout du nb de dépôts visibles
            $db->update("USER", ['NBDOCVIS' =>  $uid['nbdoc']], 'UID = ' .  $uid['uid']);
        } else if ($opts->d == "9"){
            //Ajout du nb de dépôts validation scientifique
            $db->update("USER", ['NBDOCSCI' =>  $uid['nbdoc']], 'UID = ' .  $uid['uid']);
        } else if ($opts->d == "99"){
            //Ajout du nb de dépôts refusés
            $db->update("USER", ['NBDOCREF' =>  $uid['nbdoc']], 'UID = ' .  $uid['uid']);
        }
    }

}

println('');


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