<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 15/12/16
 * Time: 17:21
 *
 * Meta class to implement Metadata when values type is implemented as Object
 *
 * @see Hal_Document_Meta_Simple is used for Metadata when values type are scalar
 * @see Hal_Document_Meta_Complex is used for multivalued and multilingual metadata
 *
 */
class Hal_Document_Meta_Object extends Hal_Document_Meta_Simple
{

    /**
     * @param string $filter
     * @return string
     */
    static public function getDefaultValue($filter='')
    {
        return null;
    }

    /**
     * @param int     $docid
     * @param int       $sid
     * @param int   $metaids
     */
    public function save($docid, $sid, &$metaids = null)
    {
        $obj = $this->getValue();

        $this->_value = $obj->save();

        parent::save($docid, $sid, $metaids);

        $this->_value = $obj;
    }

    /**
     * @param bool $group
     * @return mixed
     */
    public function getValue($group = false)
    {
        return $this->_value;
    }

    /**
     * Idem but even not overwriting
     * @param bool $group
     * @return mixed
     */
    public final function getValueObj($group = false)
    {
        return $this->_value;
    }

    /**
     * @param Hal_Document_Meta_Object $newMeta
     */
    public function merge($newMeta)
    {
        if (($this->getModifUid() != 0 && $newMeta->getModifUid() == 0) ||
            ($this->getStatus()   == 1 && $newMeta->getModifUid() == 0)) {
            return;
        } else {
            $this->_value = $newMeta->getValue();
        }
    }
}
