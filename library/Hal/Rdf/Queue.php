<?php
/**
 * Created by PhpStorm.
 * User: tournoy
 * Date: 04/10/17
 * Time: 16:58
 */

class Hal_Rdf_Queue extends Ccsd_Queue
{


    static $_maxSelectFromIndexQueue = 100;
    const SQL_QUEUE_TABLE_NAME = 'RDF_QUEUE';

    /**
     * Rdf_Queue constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setSqlDbAdapter($this->initDb());
        $this->setSqlTableName(self::SQL_QUEUE_TABLE_NAME);
        $this->setSqlSelectRowsLimit(self::setSqlSelectRowsLimit());
    }


    /**
     * Initialise les paramètres pour la base qui contient la file d'indexation
     *
     * @return PDO
     */
    protected function initDb()
    {
        try {
            $db = Zend_Registry::get(__CLASS__);
        } catch (Zend_Exception $e) {
            $adapter = new Ccsd_Db_Adapter_RdfQueue();
            $db = $adapter->getAdapter();
            Zend_Registry::set(__CLASS__, $db);
        }
        return $db;
    }


}