<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 12/01/18
 * Time: 10:33
 */

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Class Hal_Script
 */
abstract class Hal_Script extends Ccsd_Script
{

    /** @var  Zend_Db_Adapter_Pdo_Mysql */
    private $db = null;
    /** @var Zend_Application */
    private $application = null;

    private $applicationOptions = [
        'instance'     => 'choose a particular instance of code',
    ];

    /**
     * @return array
     */
    public function getOptions()
    {
        return array_merge($this->applicationOptions, parent::getOptions());
    }


    public function initApp() {
        $APPLICATION_PATH =  realpath(__DIR__ . '/../../application');
        $libraries = [];
        $libraries [] =  realpath($APPLICATION_PATH . '/../library') ;
        $libraries [] =  realpath($APPLICATION_PATH . '/../vendor/ccsd/library');
        set_include_path(implode(":", $libraries ). ":" . get_include_path());
    }

    public function setupApp() {
        $opts = $this->getOpts();

        if (isset($opts->instance)) {
            putenv('INSTANCE=' . $opts->instance);
        }

        if ($this->environment && !in_array($this->environment, $this->_valid_envs))  {
            $this->displayError("Incorrect application environment: " . $this->environment . PHP_EOL . "Should be one of these: " . implode(', ', $this->_valid_envs));
        }
        require_once 'Hal/constantes.php';

        try {
            /*---------  Création de la Zend Application -----------*/
            $application = new Zend_Application(APPLICATION_ENV, APPLICATION_INI);
            $this -> application = $application;
            // Besoin des definition de constante venant de app.ini au plus tot
            // Avant le bootstrap
            foreach ($application->getOption('consts') as $const => $value) {
                if(!defined($const)) {
                    define($const, $value);
                }
            }
            $application->getBootstrap()->bootstrap(array('db', 'Translation'));
            $this->db = Zend_Db_Table_Abstract::getDefaultAdapter();
        } catch (Exception $e) {
            die($e->getMessage());
        }
        /*---------  Choix de la langue -----------*/
        Zend_Registry::set('languages', array('fr','en','es','eu'));
        Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));
    }

}
