<?php

/**
 * Modèle pour la table des tokens utilisateurs CCSD
 * @author rtournoy
 *
 */
class Hal_Document_Tokens_OwnershipTable extends Zend_Db_Table_Abstract
{
    protected $_name = 'OWNER_TOKENS';

    protected $_primary = 'TOKEN';

    public function __construct ($env = APPLICATION_ENV)
    {
        $this->_setAdapter(Zend_Db_Table_Abstract::getDefaultAdapter());
    }

}

