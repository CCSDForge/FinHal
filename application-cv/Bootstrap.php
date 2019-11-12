<?php

require_once 'Hal/Application/Bootstrap/Bootstrap.php';

/**
 * Class Bootstrap
 */
class Bootstrap extends Hal_Application_Bootstrap_Bootstrap
{

    // Keep _initAutoload from parent
    // Keep _initConst from parent
    // Keep _initView from parent
    // Keep _initDebug from parent

    /** Argh... Order is important... so we must declare this firstly */
    protected function _initAutoload() {
        parent::_initAutoload();
    }

    protected function _initConfig() {
        parent::_initConfig();
    }

    protected function _initApplicationName()
    {
        Zend_Registry::set('APPLICATION_NAME', 'CV');
        parent::_initApplicationName();
    }

    /** Need to define a namespace ??? */
    protected function _initSession ()     {
        define('SESSION_NAMESPACE', 'session-'.session_id());
        return new Zend_Session_Namespace(SESSION_NAMESPACE);
    }
}
