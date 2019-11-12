<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 20/12/16
 * Time: 14:08
 */
class Hal_Document_Meta_Identifier extends Hal_Document_Meta_Complex
{
    // Table des serveurs disposant d'une copie de l'article
    const TABLE_COPY = 'DOC_HASCOPY';

    /**
     * @param int     $docid
     * @param int       $sid
     * @param int[] $metaids
     * @throws Zend_Db_Adapter_Exception
     */
    public function save($docid, $sid, &$metaids = null)
    {
        // Identifiant Externe
        foreach ($this->_value as $group => $val) {
            /** @var  Hal_Document_Meta_Simple $val
             *  @var string $group
             */
            if ($val->getValue() == '' || $group == '') {
                continue;
            }
            $this->_db->insert(self::TABLE_COPY, array(
                'DOCID' => $docid,
                'CODE' => $group,
                'LOCALID' => (string)$val->getValue(),
                'SOURCE' => $val->getSource(),
                'UID' => $val->getModifUid(),
                'DATECRE' => date('Y-m-d H:i:s')
            ));
        }
    }

    /**
     * @param int    $docid
     * @return Hal_Document_Meta_Identifier
     */
    static function load($docid) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE_COPY)
            ->where('DOCID = ?', (int)$docid);

        $i=1;
        /** @var Hal_Document_Meta_Identifier $meta
         *  @var Hal_Document_Meta_Identifier $resMeta
         *  On concatene chaque ligne d'identitifer dans une seule meta identifier
         */
        foreach ($db->fetchAll($sql) as $row) {
            $meta = new Hal_Document_Meta_Identifier('identifier', $row['LOCALID'], $row['CODE'], $row['SOURCE'], $row['UID'], 1);

            if ($i == 1) {
                $resMeta=$meta;
                $i++;
            } else {
                $resMeta->merge($meta);
            }
        }
        if ($i == 1) {
            // Pas d'identifiant...
            return null;
        }
        return $resMeta;
    }

    /**
     * Enregistrement d'un identifiant exterieur (version du dépôt sur arxiv, pubmed, ...)
     * @param int $docid identifiant du dépôt hal
     * @param string $code serveur exterieur
     * @param string $localid identifiant sur serveur eterieur
     * @todo: devrait correspondre a une fct de modif de l'objet puis save...
     */
    static public function addIdExtDb($docid, $code, $localid)
    {
        $bind = array(
            'DOCID'   => $docid,
            'CODE'    => $code,
            'LOCALID' => (string)$localid,
            'DATECRE' => date('Y-m-d H:i:s')
        );
        if ($localid == '') {
            // On enregistre pas un identifiant vide!
            return ;
        }
        try {
            Zend_Db_Table_Abstract::getDefaultAdapter()->insert(self::TABLE_COPY, $bind);
        } catch (Zend_Db_Adapter_Exception $e) {
            // Deja enregistree, pas grave...
        }
    }

    /**
     * @param string $filter
     * @return array|string
     */
    static public function getDefaultValue($filter='')
    {
        if ($filter == '') {
            return [];
        } else {
            return '';
        }
    }
}
