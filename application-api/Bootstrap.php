<?php

require_once 'Hal/Application/Bootstrap/Bootstrap.php';
/**
 * API
 */
class Bootstrap extends Hal_Application_Bootstrap_Bootstrap {

    // We keep _initAutoload from parent
    // We keep _initConst    from parent
    // We keep _initDebug    from parent
    // We keep __initView    from parent

    /** Argh... Order is important... so we must declare this firstly */
    protected function _initAutoload() {
        parent::_initAutoload();
    }

    protected function _initConfig() {
        parent::_initConfig();
    }

    protected function _initApplicationName() {
        Zend_Registry::set('APPLICATION_NAME', 'API');
        parent::_initApplicationName();
    }

    /**
     * No session for API usage
     */
    protected function _initSession() {
    }

    /**
     * Cache
     */
    protected function _initCache() {

        define('CACHE_PATH', CACHE_ROOT.'/'.APPLICATION_ENV.'/api/');
        if (!is_dir(CACHE_PATH)) {
            mkdir(CACHE_PATH, 0777, true);
        }
                
        $frontendOptions = [ 'lifetime' => 43200, // 12H
            'automatic_serialization' => true];

        $backendOptions = [ 'cache_dir' => CACHE_PATH,
            'file_name_prefix' => 'hal_api_cache',
            'hashed_directory_level' => 1];


        $apiCache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

        Zend_Registry::set('apicache', $apiCache);
    }
}
