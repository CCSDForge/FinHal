<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 13/12/16
 * Time: 14:49
 */
class Hal_Document_Meta_Domaininter extends Hal_Document_Meta_Complex
{
    /**
     * On enregistre les métadonnées comme étant DOMAIN
     * @param int[] $metaids
     * @param int   $docid
     * @param int   $sid
     */
    public function save($docid, $sid, &$metaids = null)
    {
        Ccsd_Tools::panicMsg(__FILE__, __LINE__, 'Trying to save meta domain_inter in Db');
    }
}
