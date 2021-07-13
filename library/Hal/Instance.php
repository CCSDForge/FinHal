<?php
/**
 * Created by PhpStorm.
 * User: iguay
 * Date: 15/05/19
 * Time: 16:28
 */

namespace Hal;

/**
 * Class Instance
 * @package Hal
 */
abstract class Instance
{
    /**
     * @var array
     */
    private static $_name2classe = [];
    /**
     * @var Instance
     * @access private
     * @static
     */
    private static $monInstance = null;

    /**
     * @var string
     * @access private
     */
    private $name = '';

    /**
     * Représentation chainée de l'objet
     *
     * @param void
     * @return string
     */
    public function __toString() {

        return $this->getName();
    }

    /**
     * Constructeur de la classe
     *
     * @param string $sName : nom de l'instance
     * @return void
     */
    private function __construct($sName) {
        $this->name = $sName;
    }

    /**
     * Méthode qui crée l'unique instance de la classe
     * si elle n'existe pas encore puis la retourne.
     *
     * @param string $sName : nom de l'instance
     * @return Instance
     * @throws \Exception
     */
    public static function getInstance($sName = '')
    {
        // on est sur l'instance SPM par défaut
        if (is_null(self::$monInstance)) {
            if ($sName == '') {
                $sName = 'hal';
            }
            if (!array_key_exists($sName, self::$_name2classe)) {
                throw new \Exception("Name instance $sName is not defined!");
            }
            $class = '\\Hal\\Instance\\' . self::$_name2classe[$sName];
            self::$monInstance = new $class($sName);
        }
        return self::$monInstance;
    }

    /**
     * Retourne le nom de l'instance de l'application
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param $name
     * @param $className
     * @throws \Exception
     */
    public static function register($name, $className)
    {
        if (array_key_exists($name, self::$_name2classe)) {
            \Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Name instance $name is redefined!");
            throw new \Exception("Name instance $name is redefined!");
        }
        self::$_name2classe[$name] = $className;
    }
}

foreach (glob(__DIR__."/Instance/*.php") as $filename)
{
    require_once($filename);
}