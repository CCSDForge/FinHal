<?php


/* addAliasMd5 */

/**
 * Alimentation du champ MD5 dans la table REF_ALIAS 
 * 
 * Le champ OLDREFMD5 ajouté dans la table REF_ALIAS est alimenté à partir des informations 
 * stockées dans REF_LOG au moment de la suppression de l'enregistrement 
 * correspondant dans la table du référentiel
 *
  * PHP version 5
 *
 * LICENSE: 
 * 
 * Date : 03/03/2016
 * 
 */

set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/phplib'), array(get_include_path()))));

require_once ('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

define('APPLICATION_PATH', __DIR__ . '/../application');

/* Environnements */
$listEnv = array('development', 'testing', 'preprod', 'production');
$defaultEnv = 'development';

try {
    $opts = new Zend_Console_Getopt(array(
        'help|h' => 'Aide',
        'env|e-s' => 'Environnement (' . implode('|', $listEnv). ') (défaut: ' . $defaultEnv . ')',
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

//$db = Zend_Db::factory('PDO_MYSQL', $application->getOption('resources')['db']['params']);
//Zend_Db_Table::setDefaultAdapter($db);

// recherche des OLDREFMD5 à renseigner
$referentiels = array('REF_PROJANR', 'REF_PROJEUROP', 'REF_JOURNAL', 'REF_AUTHOR', 'REF_STRUCTURE');
$idtab = 'OLDREFID';
foreach ($referentiels as $table_ref) {
    $nb=0;
    
    $sql = "SELECT OLDREFID, PREV_VALUES FROM REF_ALIAS as a LEFT JOIN REF_LOG as l ON a.OLDREFID=l.ID_TAB AND a.REFNOM=l.TABLE_NAME COLLATE utf8_unicode_ci WHERE a.REFNOM = '".$table_ref."' AND a.OLDREFMD5 IS NULL AND l.ACTION = 'DELETED'";
    try {
        $aliases = $db->fetchAll($sql);
    }
    catch (Exception $e) {
        die($e->getMessage());
    }
            
    foreach ($aliases as $row) {
        $values = Zend_Json::decode($row['PREV_VALUES']);
        switch ($table_ref) {
            case 'REF_PROJANR' :
                $ref = new Ccsd_Referentiels_Anrproject($row['OLDREFID']);
                break;
            case 'REF_AUTHOR' :
                $ref = new Ccsd_Referentiels_Author($row['OLDREFID']);
                break;
            case 'REF_PROJEUROP' :
                $ref = new Ccsd_Referentiels_Europeanproject($row['OLDREFID']);
                break;
            case 'REF_JOURNAL' :
                $ref = new Ccsd_Referentiels_Journal($row['OLDREFID']);
                break;
            case 'REF_STRUCTURE' :
                $ref = new Ccsd_Referentiels_Structure($row['OLDREFID']);
                break;
        }
        $ref->set($values[0], FALSE);
        $data = array(
            'OLDREFMD5' => new Zend_Db_Expr('UNHEX("' . $ref->getMd5() . '")')
        );
        $where['OLDREFID = ?'] = $row['OLDREFID'];
        $where['REFNOM = ?'] = $table_ref;
        $n = $db->update('REF_ALIAS', $data, $where);
        $nb += $n;
        unset($ref);
    }
    echo "Référentiel ".$table_ref." : ".$nb." lignes modifiées.\n";
    
}