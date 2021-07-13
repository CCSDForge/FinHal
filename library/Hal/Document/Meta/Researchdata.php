<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 13/12/16
 * Time: 14:49
 */
class Hal_Document_Meta_Researchdata extends Hal_Document_Meta_Complex
{

    const RESOLVER_URL = "http://dx.doi.org";

    // Fonction statique. Peut-être devrait-elle être une fonction pour chaque researchdoi qu'on puisse appeler directement avec la valeur de l'objet ?
    static public function getDataUrl($doi)
    {
        return self::RESOLVER_URL . '/' . $doi;
    }
}
