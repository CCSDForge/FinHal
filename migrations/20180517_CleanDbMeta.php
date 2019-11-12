<?php
/**
 * Migration des données utilsateurs de USER à USER_PREF_DEPOT
 *
 * Created by PhpStorm.
 * User: Sarah
 * Date: 10/04/2017
 * Time: 17:50
 */


require_once __DIR__ . '/../scripts/loadHalHeader.php';

debug('');
debug('','************************************************', 'blue');
debug('',"**  Clean de la table DOC_METADATA avec TRIM  **", 'blue');
debug('','************************************************', 'blue');
debug('> Début du script: ', date("H:i:s", $timestart), '');
debug('> Environnement: ', APPLICATION_ENV, '');
debug('', '----------------------------------------', 'yellow');


// 1- Selection des données utilisateurs
$db = Zend_Db_Table_Abstract::getDefaultAdapter();
$sql = $db->select()->from(Hal_Document_Meta_Abstract::TABLE_META)->where("METAVALUE REGEXP '^\\n'");

debug('> Requete : ', $sql);

$i = 0;

foreach($db->fetchAll($sql) as $row) {

    print('TOTO');

    /*$sql = 'UPDATE '.Hal_Document_Meta_Abstract::TABLE_META. ' SET METAVALUE="'..'" WHERE METAID='.$row['METAID'];
    $sth = $db->prepare($sql);
    $sth->execute();*/

    $sql = $db->update(Hal_Document_Meta_Abstract::TABLE_META, array('METAVALUE' => trim($row['METAVALUE'])), ['METAID=?'=>$row['METAID']]);
            

    Hal_Document::deleteCaches($row['DOCID']);
    $i++;

    debug('> MetaId modifié: ' . $row['METAID']);
}


$timeend = microtime(true);
$time = $timeend - $timestart;
debug('', '----------------------------------------', 'yellow');
debug('> Nombre de metadonnées modifiées: ' . $i);
debug('> Fin du script: ' . date("H:i:s", $timeend));
debug('> Script executé en ' . number_format($time, 3) . ' sec.');