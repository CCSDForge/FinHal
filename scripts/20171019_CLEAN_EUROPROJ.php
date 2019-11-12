<?php
/**
 * Passage de l'acronyme dans le TITRE pour tous les titres vides
 *
 * Created by PhpStorm.
 * User: Sarah
 * Date: 20/10/2017
 * Time: 9:50
 */

putenv('PORTAIL=hal');
putenv('CACHE_ROOT=/cache/hal');
putenv('DATA_ROOT=/data/hal');
putenv('DOCS_ROOT=/docs');

require_once 'loadHalHeader.php';

Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator('fr'));

define('LOG_FILE', '/sites/logs/php/hal/' . basename(__FILE__) . 'log');

debug('');
debug('','****************************************', 'blue');
debug('',"**  Clean du référentiel des projets européens  **", 'blue');
debug('','****************************************', 'blue');
debug('> Début du script: ', date("H:i:s", $timestart), '');
debug('> Environnement: ', APPLICATION_ENV, '');
debug('', '----------------------------------------', 'yellow');


// 1- Selection des données utilisateurs
$db = Zend_Db_Table_Abstract::getDefaultAdapter();
$sql = $db->select()->from('REF_PROJEUROP')->where('ACRONYME!="" AND TITRE IS NULL');

debug('> Requete : ', $sql);

$i = 0;

foreach($db->fetchAll($sql) as $row) {

    $row['TITRE'] = $row['ACRONYME'];
    $row['ACRONYME'] = '';

    $db->update('REF_PROJEUROP', $row, 'PROJEUROPID = ' . $row['PROJEUROPID']);
    $i++;
}


$timeend = microtime(true);
$time = $timeend - $timestart;
debug('', '----------------------------------------', 'yellow');
debug('> Nombre de projets mis à jour: ' . $i);
debug('> Fin du script: ' . date("H:i:s", $timeend));
debug('> Script executé en ' . number_format($time, 3) . ' sec.');
