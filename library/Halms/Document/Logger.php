<?php

class Halms_Document_Logger
{

    const TABLE = 'DOC_HALMS_HISTORY';

    public static function log($docid, $uid, $status, $comment = '')
    {
        Zend_Db_Table_Abstract::getDefaultAdapter()->insert(self::TABLE, array('DOCID' => $docid, 'UID' => $uid, 'STATUS' => $status, 'COMMENT' => $comment));
    }

    public static function get($docid)
    {
        return Zend_Db_Table_Abstract::getDefaultAdapter()->fetchAll(
            Zend_Db_Table_Abstract::getDefaultAdapter()->select()
            ->from(self::TABLE)
            ->where('DOCID = ?', $docid)
            ->order('DATE_ACTION DESC'));
    }

    public static function getLastComment($docid, $status)
    {
        return Zend_Db_Table_Abstract::getDefaultAdapter()->fetchOne(
            Zend_Db_Table_Abstract::getDefaultAdapter()->select()
                ->from(self::TABLE, 'COMMENT')
                ->where('DOCID = ?', $docid)
                ->where('STATUS = ?', $status)
                ->order('DATE_ACTION DESC'));
    }
}