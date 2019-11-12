<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 16/05/2017
 * Time: 13:47
 */

class Hal_Rdf_Vocabulary
{

    static protected $_prefixUrl = 'https://data.archives-ouvertes.fr/vocabulary';

    /**
     * @param string $meta
     * @param string $value
     * @return string
     */
    static public function getValue($meta, $value)
    {
        return static::$_prefixUrl . DIRECTORY_SEPARATOR . ucfirst($meta) . ucfirst(strtolower($value));
    }

}