<?php


/**
 * Created by VsCode.
 * User: Genicot Jean-Baptiste
 * Date: 13/09/18
 * Time: 09:49
 * Class Hal_Db_Adapter_ReferenceBiblio
 * Adapter de la base de donnée pour les références bibliographiques
 */

class Hal_Db_Adapter_ReferenceBiblio extends Ccsd_Db_Adapter
{

    private static $_adapter;

    /**
     * Retourne l'adapter base de données pour les références bibliographiques
     * @param string $env
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getAdapter($env = APPLICATION_ENV)
    {
        if (!isset(static::$_adapter)){
            $config = new Zend_Config_Ini(__DIR__ . '/config/refbiblio.ini', $env);
            self::$_params = $config->dbrefbiblio->toArray();
            static::$_adapter = parent::getAdapter();
        }
        return static::$_adapter;
    }
}
?>