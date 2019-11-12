<?php

// Pour l'envoi du questionnaire sur les stats à tous les administrateurs de portail
// 23/02/2018

$dbHAL = new PDO('mysql:host=localhost;port=3306;dbname=HALV3', 'username', 'password', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));
$dbCAS = new PDO('mysql:host=localhost;dbname=CAS_users', 'username', 'password', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));

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