<?php


class Hal_Document_Meta_Hceresentity extends Hal_Document_Meta_Object
{
    /**
     * Hal_Document_Meta_Hceresentity constructor.
     * @param     $key
     * @param     Hal_Referentiels_Hceres|string $value :
     *                  if value is an Hal_Referentiels_Hceres , it is used as is
     *                  if value is an Id of Hal_Referentiels_Hceres, it is loaded
     * @param     $group
     * @param     $source
     * @param     $uid
     * @param int $status
     */
    public function __construct($key, $value, $group, $source, $uid, $status)
    {
        parent::__construct($key, $value, $group, $source, $uid, $status);

        if ($value instanceof Ccsd_Referentiels_Hceres) {
            $this->_value = $value;
        } else {
            $this->_value = (new Ccsd_Referentiels_Hceres())->load($value);
        }
    }

    public function getIndexationData()
    {
        return $this->_value->getIndexationData();
    }

}
