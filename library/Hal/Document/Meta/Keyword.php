<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 22/12/16
 * Time: 15:23
 */
class Hal_Document_Meta_Keyword extends Hal_Document_Meta_Abstract
{
    protected $isMultilingue = true;

    protected $_defautlView = 'displayMetaArray.phtml';
    /**
     * Hal_Document_Meta_Keyword constructor.
     * @param string $key
     * @param string $value
     * @param string $group
     * @param string $source
     * @param int $uid
     * @param int $status
     */
    public function __construct($key, $value, $group, $source, $uid, $status)
    {
        parent::__construct($key, $value, $group, $source, $uid, $status);
        $this->_value = array();
        // Pour les keyword, le groupe est une langue!!! Pas un entier
        // Si la langue n'est pas presente... on prends anglais.
        if (!preg_match("/^[a-z]*$/", $group)) {
            $group = 'en';
        }
        $this->addValue($value, $group, $source, $uid, $status);
    }

    /**
     * @param array | string $value
     * @param string $group
     * @param $source
     * @param int $uid
     * @param int $status
     */
    public function addValue($value, $group, $source, $uid, $status)
    {

        if (is_array($value)) {
            /** Todo: Why not just iterate overs addValue for all array items
             * What is special in first element
             * What if array is empty ? We have an error on $value[0]
             */
            $this->_value[$group][] = new Hal_Document_Meta_Simple($this->getKey(), $value[0], $group, $source, $uid, $status);
            for ($i = 1 ; $i < count($value) ; $i++) {
                $this->addValue($value[$i], $group, $source, $uid, $status);
            }
        } else {
            $this->_value[$group][] = new Hal_Document_Meta_Simple($this->getKey(), $value, $group, $source, $uid, $status);
        }
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
            foreach ($val as $meta) {
                /** @var Hal_Document_Meta_Simple $meta */
                $array[$group][] = $meta->getValue();
            }
        }

        if ($filter != '') {
            return array_key_exists($filter, $array) ? $array[$filter] : [];
        } else {
            return empty($array) ? [] : $array;
        }
    }

    /**
     * @deprecated : Use getValue
     * @param string $filter
     * @return Hal_Document_Meta_Keyword
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
     * @param int     $docid
     * @param int     $sid
     * @param int[]   $metaids
     */
    public function save($docid, $sid, &$metaids = null)
    {
        foreach ($this->_value as $val) {
            foreach ($val as $meta) {
                /** @var Hal_Document_Meta_Simple $meta */
                $meta->save($docid, $sid, $metaids);
            }
        }
        $this->_status = 1;
    }

    /**
     * @param Hal_Document_Meta_Keyword $newMeta
     */
    public function merge($newMeta)
    {
        foreach ($newMeta->getRealValue() as $group => $val) {
            // On ajoute les valeurs non existantes
            if (!array_key_exists($group, $this->_value)) {
                $this->_value[$group] = $val;
            // Pour les valeurs existantes, on merge
            } else {
                $groupMeta = $this->getValue($group);
                foreach ($val as $meta) {
                     /** @var Hal_Document_Meta_Simple $meta */
                    $index = array_search($meta->getValue(), $groupMeta);
                    if ($index) {
                        $meta->merge($this->_value[$group][$index]);
                    } else {
                        $this->_value[$group][] = $meta;
                    }
                }
            }
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

    /**
     * @param $source
     * @return string
     */
    public function getMetasFromSource($source)
    {
        foreach ($this->_value as $g) {
            foreach ($g as $m) {
                /** @var Hal_Document_Meta_Simple $m */
                if ($m->getSource() == $source) {
                    return $m->getKey();
                }
            }
        }

        return "";
    }

    /**
     * @return bool
     */
    public function isMultiValued() {
        return true;
    }
}
