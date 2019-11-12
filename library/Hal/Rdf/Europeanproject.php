<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Europeanproject extends Hal_Rdf_Abstract
{

    static $_GRAPH  =   'europeanProject';

    private $_data = null;

    private $_uri = null;


    public function __construct($europeanId)
    {
        $europeanproject = new Ccsd_Referentiels_Europeanproject($europeanId);
        $this->_data = $europeanproject->toArray();

        if (!isset($this->_data['PROJEUROPID'])) {
            throw new Hal_Rdf_Exception("ID unknown");
        }
        $this->_id = $this->_data['PROJEUROPID'];

        parent::__construct($europeanId);
    }

    public function createRdf()
    {
        $this->createRdfHeader([Hal_Rdf_Schema::NS_CERIF]);

        $elemRoot = $this->appendChild($this->_documentRoot, $this->getElemRoot(), null, [Hal_Rdf_Schema::RDF_ABOUT => Hal_Rdf_Tools::createUri(static::getGraph(), $this->getId())]);

        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_TITLE, $this->_data['TITRE']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_STARTDATE, $this->_data['SDATE']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_ENDDATE, $this->_data['EDATE']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_ACRONYM, $this->_data['ACRONYME'] . ' ' . $this->_data['CALLID']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_INTERNALIDENTIFIER, $this->_data['NUMERO']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::CERIF_LINKSTOFUNDING, $this->_data['FUNDEDBY']);

        return $this->_domDocument->saveXML();
    }

    public function getElemRoot()
    {
        return Hal_Rdf_Schema::CERIF_PROJECT;
    }
}
