<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 13/12/16
 * Time: 14:49
 */
class Hal_Document_Meta_Complex extends Hal_Document_Meta_Abstract
{
    /** @var Hal_Document_Meta_Simple[]  */
    protected $_value;
    /**
     * Hal_Document_Meta_Complex constructor.
     * @param string $key
     * @param string $value
     * @param string $group
     * @param string $source
     * @param int    $uid
     * @param int    $status
     */
    public function __construct($key, $value, $group, $source, $uid, $status)
    {
        parent::__construct($key, $value, $group, $source, $uid, $status);

        $this->_value = array();
        $this->_value[$group] = new Hal_Document_Meta_Simple($this->getKey(), $value, $group, $source, $uid, $status);
    }

    /**
     * @param string $filter
     * @return array|string
     */
    public function getValue($filter = '')
    {
        // Création du tableau à renvoyer
        $array = array();
        foreach ($this->_value as $group => $val) {
            $array[$group] = $val->getValue();
        }
        if ($filter != '') {
            return array_key_exists($filter, $array) ? $array[$filter] : '';
        } else {
            return $array;
        }
    }

    /**
     * @deprecated : use getValue
     * @param string $filter
     * @return array|string
     */
    public function getHalValue($filter = '')
    {
        if ($filter != '') {
            return array_key_exists($filter, $this->_value) ? $this->_value[$filter] : null;
        } else {
            return $this;
        }
    }

    /**
     * @param string $filter
     * @return array|string
     */
    static public function getDefaultValue($filter = '')
    {
        if ($filter == '') {
            return [];
        } else {
            return '';
        }
    }

    /**
     * @param int     $docid
     * @param int     $sid
     * @param int[]   $metaids
     */
    public function save($docid, $sid, &$metaids = null)
    {
        foreach ($this->_value as $val) {
            if (isset($val)) {
                $val->save($docid, $sid, $metaids);
            }
        }

        $this->_status = 1;
    }

    /**
     * @param Hal_Document_Meta_Complex $newMeta
     */
    public function merge($newMeta)
    {
        /** @var Hal_Document_Meta_Simple $nV */
        $nV = $newMeta->getRealValue();
        foreach ($nV as $group => $val) {
            if (array_key_exists($group, $this->_value)) {
                $this->_value[$group]->merge($val);
            } else {
                $this->_value[$group] = $val;
            }
        }
    }

    /**
     * @param $source
     * @return string
     */
    public function getMetasFromSource($source)
    {
        foreach ($this->_value as $m) {
            if ($m->getSource() == $source) {
                return $this->getKey();
            }
        }

        return "";
    }

    /**
     * Return existetnce of group $group in the complex meta
     * @param $group
     * @return bool
     */
    public function existsGroup($group) {
        return array_key_exists($group, $this->_value);
    }

    /**
     * Remove all group in complex meta
     * @param $group
     */
    public function removeGroup($group) {
        if ($this->existsGroup($group)) {
            unset($this->_value[$group]);
        }
    }
      /**
     * @return bool
     */
    public function isMultiValued() {
        return true;
    }
}
