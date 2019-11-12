<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 10/01/18
 * Time: 15:18
 * Class Hal_Db_Adapter_Stats
 * Adapter la base de donnée pour les statistiques
 */
class Hal_Db_Adapter_Stats extends Ccsd_Db_Adapter
{

    /**
     * Retourne l'adapter base de données pour la file d'indexation de solr
     * @param string $env
     * @return Zend_Db_Adapter_Abstract
     */
    public static function getAdapter($env = APPLICATION_ENV)
    {
        $config = new Zend_Config_Ini(__DIR__ . '/config/stats.ini', $env);
        self::$_params = $config->dbstats->toArray();
        return parent::getAdapter();
    }
}