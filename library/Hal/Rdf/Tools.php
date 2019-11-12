<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 16/05/2017
 * Time: 13:47
 */

class Hal_Rdf_Tools
{

    /**
     * Indique si la requete effectuée demande la ressource au format RDF
     * @param $request Zend_Controller_Request_Http
     * @return bool
     *
     * @throws Zend_Controller_Request_Exception
     */
    static public function requestRdfFormat($request)
    {
        return (strpos(strtolower((string)$request->getHeader('Accept')) , 'application/rdf+xml') !== false);
    }

    /**
     * Créé l'URI d'une entrée de référentiel
     * @param $graph
     * @param $id
     * @return string
     */
    static public function createUri($graph, $id)
    {
        return Hal_Rdf_Schema::PREFIX_HAL . DIRECTORY_SEPARATOR . $graph . DIRECTORY_SEPARATOR . $id ;
    }

}