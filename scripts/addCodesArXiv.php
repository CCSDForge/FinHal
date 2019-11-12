<?php
/**
 * Ce script est a mettre a jour.
 * Sert-il encore?
 * TODO:
 * Modifier les chemin de fichier pour que tous le monde puisse le lancer.
 * 
 */
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 21/04/15
 * Time: 13:27
 */

//Chargement des constantes login/pwd
define('APPLICATION_ENV', 'development');
require_once(__DIR__ . '/../public/bddconst.php');

$files = [
  __DIR__ . '/../library/Thesaurus/languages/fr/domains.php',
  __DIR__ . '/../library/Thesaurus/languages/en/domains.php',
];

//BDD Login / PWD
$dbUser = HAL_USER;
$dbPwd = HAL_PWD;

$dbHALV3 = new PDO(HAL_PDO_URL, $dbUser, $dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

$query = $dbHALV3->query('SELECT CODE, ARXIV FROM `REF_DOMAIN_ARXIV`');
$arxiv = [];
foreach ($query->fetchAll() as $row) {
    $arxiv[$row['CODE']] = $row['ARXIV'];
}

foreach ($files as $file) {
    $content = array();
    $content[] = '<?php ';
    $content[] = 'return array(';

    foreach (include $file as $key => $value) {
        $codeHAL = str_replace('domain_', '', $key);
        $codeArXiv = '';
        $value = preg_replace('/\s*\[[^\]]*\]s*/', '', $value);
        if (isset($arxiv[$codeHAL])) {
            $codeArXiv = ' [' . $arxiv[$codeHAL] . ']';
        }
        $content[] = '"' . addslashes($key) .'" => "' . addcslashes($value, '"') . $codeArXiv . '",';
    }
    $content[] = ');';
    $content = implode("\n", $content);

    file_put_contents(str_replace('domains.php', 'domains-arxiv.php', $file), $content);
}