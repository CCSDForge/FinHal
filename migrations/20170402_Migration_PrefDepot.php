<?php
/**
 * Migration des données utilsateurs de USER à USER_PREF_DEPOT
 *
 * Created by PhpStorm.
 * User: Sarah
 * Date: 10/04/2017
 * Time: 17:50
 */


define('LOG_PATH', '/sites/logs/php/hal/migrationUser.log');

require_once __DIR__ . '/../scripts/loadHalHeader.php';

debug('');
debug('','****************************************', 'blue');
debug('',"**  Migration des données utilisateur  **", 'blue');
debug('','****************************************', 'blue');
debug('> Début du script: ', date("H:i:s", $timestart), '');
debug('> Environnement: ', APPLICATION_ENV, '');
debug('', '----------------------------------------', 'yellow');

loginfile(LOG_PATH, '***** Migration des données utilisateurs *****');
loginfile(LOG_PATH, 'Environnement : ' . APPLICATION_ENV);


// 1- Selection des données utilisateurs
$sql = $db->select()->from(Hal_User::TABLE_USER);

debug('> Requete : ', $sql);

$i = 0;

foreach($db->fetchAll($sql) as $row) {

    $uid = $row['UID'];
    debug('> UID : ', $row['UID']);

    debug('> LABORATORYS : ', $row['LABORATORY']);

    if (isset($row['LABORATORY']) && !empty($row['LABORATORY']) && 'null' != $row['LABORATORY']) {

        $labos = explode(',',$row['LABORATORY']);
        foreach ($labos as $lab) {
            $lab = preg_replace('/[^A-Za-z0-9\-.]/', "", $lab);
            debug('> LABORATORY : ', $lab);
            $db->insert(Hal_User::TABLE_PREF_DEPOT, ['UID'=>$uid, 'PREF'=>'laboratory', 'VALUE'=>$lab]);
        }
    }

    debug('> DOMAINS : ', $row['DOMAIN']);

    if (isset($row['DOMAIN']) && !empty($row['DOMAIN']) && 'null' != $row['DOMAIN']) {

        $domains = explode(',',$row['DOMAIN']);
        foreach ($domains as $domain) {
            $domain = preg_replace('/[^A-Za-z0-9\-.]/', "", $domain);
            debug('> DOMAIN : ', $domain);
            $db->insert(Hal_User::TABLE_PREF_DEPOT, ['UID'=>$uid, 'PREF'=>'domain', 'VALUE'=>$domain]);
        }
    }

    $default_author = $row['DEFAULT_AUTHOR'];
    debug('> DEF_AUT: ', $default_author);
    $db->insert(Hal_User::TABLE_PREF_DEPOT, ['UID'=>$uid, 'PREF'=>'default_author', 'VALUE'=>$default_author]);

    // On signifie que l'utilisateur devra remplir ses préférences de dépot à la première connexion
    $db->insert(Hal_User::TABLE_PREF_DEPOT, ['UID' => $uid, 'PREF' => 'noprefs', 'VALUE' => '1']);
    $i++;
}


$timeend = microtime(true);
$time = $timeend - $timestart;
debug('', '----------------------------------------', 'yellow');
debug('> Nombre d\'utilisateurs: ' . $i);
debug('> Fin du script: ' . date("H:i:s", $timeend));
debug('> Script executé en ' . number_format($time, 3) . ' sec.');
loganddebug('', '', '', LOG_PATH);