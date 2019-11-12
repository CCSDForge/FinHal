<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 13/12/16
 * Time: 14:48
 */
class Hal_Document_Meta_Simple extends Hal_Document_Meta_Abstract
{
    /**
     * @param string
     * @return string
     */
    static public function getDefaultValue($filter='')
    {
        return '';
    }

    /**
     * @param int[] $metaids
     * @param int   $docid
     * @param int   $sid
     */
    public function save($docid, $sid, &$metaids = null)
    {
        $coreMetas = Hal_Settings::getCoreMetas();
        $sid = in_array($this->getKey(), $coreMetas) ? 0 : $sid;
        $this->insertLine($metaids, $docid, $sid);
        $this->_status = 1;
    }

    /**
     * @param Hal_Document_Meta_Simple $newMeta
     *
     * La meta manuelle est prioritaire ($uid!=0)
     * Si les 2 métas sont de type "automatique" ($uid=0), la méta qui a été validée ($status) est prioritaire
     * Dans les autres cas, la nouvelle métadonnée est prioritaire
     */
    public function merge($newMeta)
    {
        if (($this->getModifUid() != 0 && $newMeta->getModifUid() == 0) ||
            ($this->getStatus()   == 1 && $newMeta->getModifUid() == 0)) {
            return;
        } else {
            $this->_value = $newMeta->getValue();
            $this->_source = $newMeta->getSource();
            $this->_modificationUid = $newMeta->getModifUid();
            $this->_status = $newMeta->getStatus();
        }
    }

    /**
     * Modification d'une métadonnée à partir de sa précédent valeur et le docid associé
     * @param $docid
     * @param $value
     * @return bool|int
     */
    public function replaceMeta($docid, $value)
    {
        try {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();

            $where['METANAME = ?'] = $this->getKey();
            if ($value != null) {
                $where['METAVALUE = ?'] = $this->cleanForDb($value);
            }
            if ($this->getGroup() != '') {
                $where['METAGROUP = ?'] = $this->getGroup();
            }
            if (is_array($docid)) {
                $where['DOCID IN (?)'] = $docid;
            } else {
                $where['DOCID = ?'] = (int)$docid;
            }

            $metavalue = $this->getValue();
            if (preg_match('/înter_/', $this->getValue())) {
                $metavalue = preg_replace('/înter_/', '', $metavalue);
            }
            $sql = $db->update(Hal_Document_Metadatas::TABLE_META, array('METAVALUE' => $this->cleanForDb($metavalue)), $where);
            Hal_Document::deleteCaches($docid);
            return $sql;

        } catch (Exception $e) {
            return false;
        }
    }

}
