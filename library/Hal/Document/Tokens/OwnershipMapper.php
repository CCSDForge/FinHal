<?php

/**
 * Gestion des tokens pour les document du CCSD
 * @author sdenoux
 * @see Ccsd_User_Models_UserTokens  Inspire de ...,
 */
class Hal_Document_Tokens_OwnershipMapper
{
    static protected $_tableName = 'Hal_Document_Tokens_OwnershipTable';
    /** @var Zend_Db_Table_Abstract  */
    protected $_dbTable;

    /**
     * @param string|Zend_Db_Table_Abstract $dbTable
     * @return Hal_Document_Tokens_OwnershipMapper
     * @throws Exception
     */
    public function setDbTable ($dbTable)
    {
        if (is_string($dbTable)) {
            $dbTable = new $dbTable();
        }
        if (! $dbTable instanceof Zend_Db_Table_Abstract) {
            throw new Exception('Invalid table data gateway provided');
        }
        $this->_dbTable = $dbTable;
        return $this;
    }

    /**
     * @return Zend_Db_Table_Abstract
     * @throws Exception
     */
    private function getDbTable ()
    {
        if (null === $this->_dbTable) {
            $this->setDbTable(self::$_tableName);
        }
        return $this->_dbTable;
    }

    /**
     * Enregistre un token
     *
     * @param Hal_Document_Tokens_Ownership $userTokens
     * @return int Dernier ID de token enregistré
     * @throws Exception
     */
    public function save (Hal_Document_Tokens_Ownership $userTokens)
    {
        $data = array(
            'UID' => $userTokens->getUid(),
            'DOCID' => $userTokens->getDocid(),
            'TOKEN' => $userTokens->getToken(),
            'USAGE' => $userTokens->getUsage()
        );

        $lastInsertId = $this->getDbTable()->insert($data);

        return $lastInsertId;
    }

    /**
     * Vérifie si un token existe
     * Si oui retourne les infos sur la ligne du token
     *
     * @param string $token
     * @param Hal_Document_Tokens_Ownership $userTokens
     * @return null|Hal_Document_Tokens_Ownership
     * @throws Exception
     */
    public function findByToken ($token, Hal_Document_Tokens_Ownership $userTokens)
    {
        $result = $this->getDbTable()->find($token);
        if (0 == count($result)) {
            return null;
        }
        $row = $result->current();
        $userTokens->setUid($row->UID)
            ->setDocid($row->DOCID)
            ->setToken($row->TOKEN)
            ->setTime_modified($row->TIME_MODIFIED)
            ->setUsage($row->USAGE);
        return $userTokens;
    }

    /**
     * Supprime un token
     *
     * @param string $token
     * @param Hal_Document_Tokens_Ownership $userTokens
     * @throws Exception
     */
    public function delete ($token, Hal_Document_Tokens_Ownership $userTokens)
    {
        $this->getDbTable()->delete(
            array(
                'TOKEN = ?' => $token
            ));
    }
}

