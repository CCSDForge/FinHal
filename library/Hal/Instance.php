<?php
/**
 * Created by PhpStorm.
 * User: iguay
 * Date: 15/05/19
 * Time: 16:28
 */

class Hal_Instance
{

    /**
     * @var Hal_Instance
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
     * @return Hal_Instance
     */
    public static function getInstance($sName)
    {
        // on est sur l'instance SPM par défaut
        if (!isset($sName) || !is_string($sName) || $sName == '') {
            $sName = 'hal';
        }
        if (is_null(self::$monInstance)) {
            self::$monInstance = new Hal_Instance($sName);
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

}

?>
