<?php

class Hal_Referentiels_Metadata
{
    const TABLE = 'REF_METADATA';

    static protected $referentials = array('country', 'language', 'domain');

    /**
     * Liste les métadonnées dont la valeur doit être selectionnée dans une liste
     * @param int $sid permet de limiter aux métadonnées d'un site
     * @param bool $mergeReferentials . retourne également les métadonnées de Zend (pays, langue, ...)
     * @return array
     */
    static public function metaList($sid = null, $mergeReferentials = true)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->distinct()
            ->from(self::TABLE, 'METANAME');
        if ($sid !== null) {
            $sql->where('SID = ?', (int) $sid);
        }

        $referentials = $db->fetchCol($sql);
        if ($mergeReferentials) {
            $referentials = array_merge(self::$referentials, $referentials);
        }
        return $referentials;
    }

    /**
     * Retourne le SITEID associé à une métadonnée
     * @param $metaname
     * @return string
     */
    static public function getSid($metaname)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->distinct()
            ->from(self::TABLE, 'SID')
            ->where('METANAME = ?', $metaname);
        return $db->fetchOne($sql);
    }

    /**
     * Indique si une métadonnée fait partie des "métadonnées liste" (liste de valeur définie)
     * @param string $meta :nom de la métadonnée
     * @param int $sid
     * @return bool
     */
    static public function isMetaList($meta, $sid = null)
    {
        return in_array($meta, self::metaList($sid));
    }

    /**
     * Retourne les valeurs disponibles pour une métadonnée
     * @param $metaname
     * @return array
     */
    static public function getValues($metaname, $sid = 0)
    {
        $filename = $metaname . '.phps';
        $dirname = Hal_Site_Portail::DEFAULT_CACHE_PATH . '/';

        if (Hal_Cache::exist($filename, 0, $dirname)) {
            return unserialize(Hal_Cache::get($filename, $dirname));
        }
        $values = self::loadValues($metaname, $sid);
        Hal_Cache::save($filename, serialize($values), $dirname);
        return $values;
    }

    /**
     * @param $metaname
     * @return int
     */
    static public function getMaxValue($metaname)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->distinct()
            ->from(self::TABLE, new Zend_Db_Expr('MAX(CAST(METAVALUE AS UNSIGNED))'))
            ->where('METANAME = ?', $metaname)
            ->where('METAVALUE != "empty"');
        return (int) $db->fetchOne($sql);
    }

    /**
     * Retourne le label pour une meta de type "métadonnée liste"
     */
    static public function getLabel($meta, $value)
    {
        return $meta.'_'.$value;
    }

    /**
     * Charge la liste de valeurs de la base
     * @param $metaname
     * @return array
     */
    static public function loadValues($metaname, $sid = 0)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE, array('METAVALUE', new Zend_Db_Expr("CONCAT('" . $metaname. "_', METAVALUE)")))
            ->where('METANAME = ?', $metaname)
            ->order('SORT ASC');

        if ( $sid != 0 ) {
            $sql->where('SID = ?', $sid);
        }

        return $db->fetchPairs($sql);
    }

    /**
     * Enregistrement des valeurs d'une métadonnée
     * @param string $metaname
     * @param array $values
     */
    static public function saveValues($metaname, $values, $sid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        foreach($values as $k => $metavalue) {

            $bind = array(
            	'SID' 		=> $sid,
                'METANAME'  => $metaname,
                'METAVALUE' => $metavalue,
                'SORT'      => $k+1
            );

            try {
            	$db->insert(self::TABLE, $bind);
            } catch (Exception $e) {
            	continue;
            }

        }
        self::deleteCache($metaname);
    }

    static public function addValue($metaname, $metavalue, $sid)
    {
        //Récupération de l'ordre du nouvel element à insérer
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, new Zend_Db_Expr('MAX(SORT)+1'))->where('METANAME = ?', $metaname);
        $sort = $db->fetchOne($sql);
        $bind = array(
            'SID' 		=> $sid,
            'METANAME'  => $metaname,
            'METAVALUE' => $metavalue,
            'SORT'      => $sort
        );
        self::deleteCache($metaname);
        return $db->insert(self::TABLE, $bind);
    }


    /**
     * Effacement d'une valeur pour une métadonnée donnée
     * @param string $metaname
     * @param mixed $metavalue
     * @param int $sid
     * @return int
     */
    static public function delete ($metaname, $metavalue, $sid)
    {
        self::deleteCache($metaname);
        return Zend_Db_Table_Abstract::getDefaultAdapter()->delete(self::TABLE, "METANAME LIKE '$metaname' AND METAVALUE = $metavalue AND SID = $sid");
    }

    static public function deleteCache($metaname)
    {
        //Suppression du cache s'il existe
        $filename = $metaname . '.phps';
        $dirname = DEFAULT_CACHE_PATH . '/';

        if (Hal_Cache::exist($filename, 0, $dirname)) {
            Hal_Cache::delete($filename, $dirname);
        }
    }
}