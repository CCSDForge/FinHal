<?php

// Pour l'envoi du questionnaire sur les stats à tous les administrateurs de portail
// 23/02/2018

$dbHAL = new PDO('mysql:host=ccsddb04.in2p3.fr;port=3306;dbname=HALV3', 'ccsd_sql', 'pap5e2008', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));
$dbCAS = new PDO('mysql:host=ccsddb04.in2p3.fr;dbname=CAS_users', 'cas_sql', 'c88y7YSPWCHe248BNurDDbYDMpE4d6VWUHfv7zF8SaUJttHa', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));

$res = $dbHAL->query('SELECT UID FROM USER_RIGHT WHERE RIGHTID="administrator"')->fetchAll(PDO::FETCH_COLUMN);
$resAsString = '(';

foreach($res as $r) {
    $resAsString .= $r . ',';
}

$resAsString .= '1)';


$res2 = $dbCAS->query('SELECT EMAIL FROM T_UTILISATEURS WHERE UID IN '.$resAsString)->fetchAll(PDO::FETCH_COLUMN);


$resAsString = '';

foreach($res2 as $r) {
    $resAsString .= $r . ',';
}

$fp = fopen('/home/sdenoux/Bureau/emails.txt', 'w');
fwrite($fp, $resAsString);
fclose($fp);