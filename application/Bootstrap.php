<?php

// Besoin pour trouver la class Hal_Application_Bootstrap_Bootstrap...
// Evite de charger l'autoloader dans tous les scripts...

require_once 'Hal/Application/Bootstrap/Bootstrap.php';

/** Init application */
class Bootstrap extends Hal_Application_Bootstrap_Bootstrap {

    // We keep _initAutoload from parent
    // We keep _initConst    from parent
    // We keep _initDebug    from parent

    /** Argh... Order is important... so we must declare this firstly */
    protected function _initAutoload() {
        parent::_initAutoload();
    }

    protected function _initConfig() {
        parent::_initConfig();
    }

    protected function _initApplicationName() {
        Zend_Registry::set('APPLICATION_NAME', 'HAL');
        parent::_initApplicationName();
    }

    protected function _initConst() {
        parent::_initConst();
        if (defined('PATHTEMPDOCS') && !is_dir(PATHTEMPDOCS)) {
            @mkdir(PATHTEMPDOCS);
            // On tourne en nobody, on ne peut pas faire des chgrp et chown
        }
        if (defined('CCSD_USER_PHOTO_PATH') && !is_dir(CCSD_USER_PHOTO_PATH)) {
            @mkdir(CCSD_USER_PHOTO_PATH);
            // On tourne en nobody, on ne peut pas faire des chgrp et chown
        }
    }
    /**
     * Ajout des Helpers de vue
     *
     * @return Zend_View
     */
    protected function _initView() {
        $view = new Hal_View();
        $view->addHelperPath('ZendX/JQuery/View/Helper', 'ZendX_JQuery_View_Helper');
        $view->addHelperPath('Ccsd/View/Helper', 'Ccsd_View_Helper');
        $view->addHelperPath('Hal/View/Helper', 'Hal_View_Helper');
        $view->doctype(Zend_View_Helper_Doctype::XHTML1_RDFA);
        /** @var Zend_Controller_Action_Helper_ViewRenderer $viewRenderer */
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);
        return $view;
    }

    protected function _initTranslation() {
        $translator = new Hal_Translate(Zend_Translate::AN_ARRAY, PATH_TRANSLATION, null, array(
            'scan' => Zend_Translate::LOCALE_DIRECTORY,
            'disableNotices' => true
        ));
        // Hum: si SPACE_DATA n'est pas definie, c'est une erreur!!!
        // Devrait etre teste avant dans le processus
        if (defined('SPACE_DATA')) {
            $defaultSpaceLanguage = SPACE_DATA . '/' . SPACE_SHARED . '/languages';
            if (is_dir($defaultSpaceLanguage) && count(scandir($defaultSpaceLanguage)) > 2) {
                $translator->addTranslation($defaultSpaceLanguage);
            }
        }
        // Thesaurus définis dans le code de l'application
        if (is_dir(APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'languages')) {
            $translator->addTranslation(APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'languages');
        }
        Zend_Registry::set('Zend_Translate', $translator);
    }

    /**
     * Cache Zend_Db_Table
     * @see http://framework.zend.com/manual/1.12/fr/zend.db.table.html#zend.db.table.metadata.caching
     */

    protected function _initZend_Db_TableCache() {
        if (APPLICATION_ENV != 'development') {

            $frontendOptions = [
                'cache_id_prefix' => 'halv3_' . APPLICATION_ENV,
                'automatic_cleaning_factor' => 1,
                'lifetime' => 60,
                'automatic_serialization' => true
            ];

            $dbMetadaCacheDir = sys_get_temp_dir() . '/php';

            if (!file_exists($dbMetadaCacheDir)) {
                mkdir($dbMetadaCacheDir);
            }

            $backendOptions = ['cache_dir' => $dbMetadaCacheDir];

            $dbCache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

            Zend_Db_Table_Abstract::setDefaultMetadataCache($dbCache);
        }
    }

}
