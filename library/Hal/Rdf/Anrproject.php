<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Anrproject extends Hal_Rdf_Abstract
{

    static $_GRAPH  =   'anrProject';

    private $_data = null;

    private $_uri = null;

    public function __construct($anrId)
    {
        $anrproject = new Ccsd_Referentiels_Anrproject($anrId);
        $this->_data = $anrproject->toArray();

        if (!isset($this->_data['ANRID'])) {
            throw new Hal_Rdf_Exception("ID unknown");
        }
        $this->_id = $this->_data['ANRID'];

        parent::__construct($anrId);
    }


    public function createRdf()
    {
        $this->createRdfHeader([Hal_Rdf_Schema::NS_CERIF]);

        $elemRoot = $this->appendChild($this->_documentRoot, $this->getElemRoot(), null, ['rdf:about' => Hal_Rdf_Tools::createUri(static::getGraph(), $this->getId())]);

        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_TITLE, $this->_data['TITRE']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_STARTDATE, $this->_data['ANNEE']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_ABSTRACT, $this->_data['INTITULE']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_ACRONYM, $this->_data['ACRONYME']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_INTERNALIDENTIFIER, $this->_data['REFERENCE']);

        return $this->_domDocument->saveXML();
    }

    public function getElemRoot()
    {
        return Hal_Rdf_Schema::CERIF_PROJECT;
    }

}