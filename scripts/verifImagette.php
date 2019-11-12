<?php
define('APPLICATION_ENV', 'production');
$timestart = microtime(true);
println('> Début du script: '. date("H:i:s", $timestart));

//DB
require_once('../public/bddconst.php');

$dbHostThumb = THUMB_HOST;
$dbNameThumb = THUMB_NAME;
$dbUserThumb = THUMB_USER;
$dbPwdThumb = THUMB_PWD;
$pathThumb = '/thumbnails/';


$dbTHUMB = new PDO('mysql:host='. $dbHostThumb .';dbname='. $dbNameThumb .'', $dbUserThumb, $dbPwdThumb, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

$cursor = 1;
while (true) {
    println('cursor :' . $cursor);
    $query = $dbTHUMB->query('SELECT IMGID FROM `THUMB` WHERE STATUS = 1 AND IMGID > ' . $cursor . ' ORDER BY IMGID ASC LIMIT 50000') ;
    $res = $query->fetchAll();
    if (count($res) == 0) {
        break;
    }
    foreach($res as $row) {
        if (! is_dir($pathThumb . wordwrap(sprintf("%08d", $row['IMGID']), 2, DIRECTORY_SEPARATOR, 1))) {
            $sql = "UPDATE `THUMB` SET STATUS = 0 WHERE IMGID = " . $row['IMGID'];
            $dbTHUMB->exec($sql);
            println($row['IMGID'] . ' à recréer');
        }
        $cursor = $row['IMGID'];
    }

}

$timeend = microtime(true);
$time = $timeend - $timestart;
println('> Fin du script: ' . date("H:i:s", $timeend));
println('> Script executé en ' . number_format($time, 3) . ' sec.');


function println($var) {
    echo $var."\n";
}