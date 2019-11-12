<?php

/*---------  Chargement de l'Autoload et des configurations de HAL -----------*/
set_time_limit(0);

ini_set("memory_limit", '2048M');
ini_set("display_errors", '1');
$timestart = microtime(true);

require_once ('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

$session = new Zend_Session_Namespace();
