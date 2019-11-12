<?php
/**
 * Created by PhpStorm.
 * User: baptiste
 * Date: 09/03/2016
 * Time: 11:31
 */

/*
 * Script de nettoyage de la table DOC_RELATED, si les identifiants ont été mal renseignés.
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
        'test|t' => 'Mode Test (sans suppression des relations)'
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


if (!isset($opts->d)){
    //Message d'aide si pas de docid renseigné
    die($opts->getUsageMessage() . PHP_EOL . PHP_EOL);
}

if (isset($opts->d) && $opts->d == "%") {
    $sql = $db->select()->from('DOC_RELATED');
} else {
    $sql = $db->select()->from('DOC_RELATED')->where('DOCID = ?', $opts->d);
}
$rows = $db->fetchAll($sql);

foreach($rows as $row) {
    //Nettoie les espaces
    $where['DOCID = ?'] = $row['DOCID'];
    $where['IDENTIFIANT = ?'] = $row['IDENTIFIANT'];
    $where['RELATION = ?'] = $row['RELATION'];
    if (!isset($opts->t)){
        $db->update("DOC_RELATED",['IDENTIFIANT' => trim($row['IDENTIFIANT'])], $where);
    }
}

$regex = "[a-z]+-[0-9]{8}";
$regex2 = "^[a-z]+-[0-9]{8}$";
if (isset($opts->d) && $opts->d == "%") {
    $sql = $db->select()->from('DOC_RELATED')->where('IDENTIFIANT REGEXP ?', $regex)->where('IDENTIFIANT NOT REGEXP ?', $regex2);
} else {
    $sql = $db->select()->from('DOC_RELATED')->where('IDENTIFIANT REGEXP ?', $regex)->where('IDENTIFIANT NOT REGEXP ?', $regex2)->where('DOCID = ?', $opts->d);
}
$rows = $db->fetchAll($sql);

$countnet = 0;
foreach($rows as $row){
    preg_match('/'.$regex.'/', $row['IDENTIFIANT'], $identifiant);
    //Corrige les identifiants HAL pour les mettre dans le bon format : hal-00000001
    $where['DOCID = ?'] = $row['DOCID'];
    $where['IDENTIFIANT = ?'] = $row['IDENTIFIANT'];
    $where['RELATION = ?'] = $row['RELATION'];

    if (!isset($opts->t)){
        $db->update("DOC_RELATED", ['IDENTIFIANT' => $identifiant[0]], $where);
    }
    $countnet = $countnet+1;
}

//Récupération des mauvais IDENTIFIANT
$regex = "^[a-z]+-[0-9]{8}$";
if (isset($opts->d) && $opts->d == "%") {
    $sql = $db->select()->from('DOC_RELATED')->where('IDENTIFIANT NOT REGEXP ?', $regex);
} else {
    $sql = $db->select()->from('DOC_RELATED')->where('IDENTIFIANT NOT REGEXP ?', $regex)->where('DOCID = ?', $opts->d);
}
$rows = $db->fetchAll($sql);

$countsup = 0;
foreach($rows as $row){
    println("Docid : ".$row['DOCID']);
    println("Identifiant : ".$row['IDENTIFIANT']);
    if (!isset($opts->t)){
        $db->delete('DOC_RELATED', 'DOCID = "' . $row['DOCID'] . '" AND IDENTIFIANT = "' . $row['IDENTIFIANT'] . '" AND RELATION = "' . $row['RELATION'] . '"');
    }
    $countsup = $countsup+1;
}
println($countnet." relation(s) nettoyee(s)");
println($countsup." relation(s) supprimee(s)");

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