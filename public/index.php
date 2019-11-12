<?php

/* On a juste besoin du path des library */
  set_include_path(implode(PATH_SEPARATOR, array_merge(array(__DIR__. '/../library'), array(get_include_path()))));

/** Zend_Application */
if (file_exists('../vendor/autoload.php'))
    require_once '../vendor/autoload.php';
else
    require_once 'Zend/Application.php';
require_once 'Hal/constantes.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_INI
);
$application->bootstrap()
            ->run();

$db    = Zend_Db_Table_Abstract::getDefaultAdapter();
$casdb = Ccsd_Db_Adapter_Cas::getAdapter();
$db->closeConnection();
$casdb->closeConnection();