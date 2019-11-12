<?php

class Hal_User_Stat_Queries
{
    const CATEGORY_REPARTITION = 'repartition';
    const CATEGORY_CONSULTATION = 'consultation';

    const TABLE = 'USER_STAT_QUERIES';

    protected $_uid = 0;


    public function __construct($uid)
    {
        $this->_uid = $uid;
    }

    public function getQueries($addCommonQueries = true)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, array('QUERYID', 'LABEL'));
        $uids = array($this->_uid);
        if ($addCommonQueries) {
            $uids[] = 0; //Rajout des requetes communes
        }
        $sql->where('UID in (?)', $uids)->order('LABEL ASC');
        return $db->fetchPairs($sql);
    }

    public function getQuery($queryid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE)->where('QUERYID = ?', $queryid);
        return $db->fetchRow($sql);
    }

    public function saveQuery($uid, $options)
    {
        if (! isset($options['category'])) {
            return false;
        }
        $bind = array(
            'UID'           =>  $uid,
            'LABEL'         =>  $options['label'],
            'SPACE'         =>  $options['space'],
            'CATEGORY'      =>  $options['category'],
            'CHART'         =>  $options['chart']);

        if ($options['category'] == StatController::STAT_REPARTITION) {
            $bind['FILTERS'] = $options['filters'] != '' ? $options['filters'] : '*' ;
            $bind['FACET'] = $options['facet'] ;
            $bind['PIVOT'] = $options['pivot'] ;
            $bind['SORT'] = $options['sort'] ;
            $bind['CUMUL'] = $options['cumul'] ;
            $bind['ADDITIONAL'] = $options['additional'] ;
        } else if ($options['category'] == StatController::STAT_CONSULTATION) {
            $bind['CUMUL'] = $options['cumul'] ;
            $bind['INTERVAL'] = $options['interval'] ;
            $bind['DATE_START'] = $options['start'] ;
            $bind['DATE_END'] = $options['end'] ;
            $bind['TYPE'] = $options['type'] ;
        } else if ($options['category'] == StatController::STAT_RESSOURCE) {
            $bind['DATE_START'] = $options['start'] ;
            $bind['DATE_END'] = $options['end'] ;
            $bind['TYPE'] = $options['type'] ;
        } else if ($options['category'] == StatController::STAT_PROVENANCE) {
            $bind['DATE_START'] = $options['start'] ;
            $bind['DATE_END'] = $options['end'] ;
            $bind['TYPE'] = $options['type'] ;
            $bind['VIEW'] = $options['view'] ;
        } else {
            return false;
        }
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->insert(self::TABLE, $bind);
        return $db->lastInsertId(self::TABLE);
    }



    public function delQuery($queryId)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        return $db->delete(self::TABLE, 'QUERYID =' . $queryId . ' AND UID != 0');
    }

}