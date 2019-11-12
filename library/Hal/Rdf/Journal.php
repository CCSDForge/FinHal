<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Journal extends Hal_Rdf_Abstract
{

    static $_GRAPH  =   'revue';

    private $_data = null;

    /**
     * Hal_Rdf_Journal constructor.
     * @param $journalId
     * @throws Hal_Rdf_Exception
     */
    public function __construct($journalId)
    {
        if (!is_numeric($journalId)) {
            //L'identifiant doit être un entier
            throw new Hal_Rdf_Exception('ID should be an integer');
        }
        $journal = new Ccsd_Referentiels_Journal($journalId);

        $this->_data = $journal->getData();
        if ($this->_data == []) {
            throw new Hal_Rdf_Exception('ID unknown');
        }

        parent::__construct($journalId);
    }

    /**
     * @return string
     */
    public function getElemRoot()
    {
        return Hal_Rdf_Schema::FABIO_JOURNAL;
    }

    /**
     * @return string
     */
    public function createRdf()
    {
        $this->createRdfHeader([Hal_Rdf_Schema::NS_DCTERMS, Hal_Rdf_Schema::NS_FABIO, Hal_Rdf_Schema::NS_SKOS,  Hal_Rdf_Schema::NS_VCARD,  Hal_Rdf_Schema::NS_PRISM ]);

        $elemRoot = $this->appendChild($this->_documentRoot, $this->getElemRoot(), null, [Hal_Rdf_Schema::RDF_ABOUT => Hal_Rdf_Tools::createUri(static::getGraph(), $this->getId())]);

        $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_PREFLABEL, $this->_data['JNAME']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_ALTLABEL, $this->_data['SHORTNAME']);
        if ($this->_data['PUBLISHER'] != '') {
            $publisher = $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_PUBLISHER, null, [], true);
            $organization = $this->appendChild($publisher, Hal_Rdf_Schema::VCARD_ORG, null, [], true);
            $this->appendChild($organization, Hal_Rdf_Schema::VCARD_ORGNAME, $this->_data['PUBLISHER']);
        }
        $this->appendChild($elemRoot, Hal_Rdf_Schema::PRISM_DOI, $this->_data['ROOTDOI']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::PRISM_ISSN, $this->_data['ISSN']);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::PRISM_EISSN, $this->_data['EISSN']);
        if ($this->_data['URL'] != '') {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::PRISM_URL, null, [Hal_Rdf_Schema::RDF_RESOURCE => $this->_data['URL']]);
        }


        return $this->_domDocument->saveXML();
    }
}
