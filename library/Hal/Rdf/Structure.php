<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Structure extends Hal_Rdf_Abstract
{
    static $_GRAPH  =   'structure';

    /**
     * @var Ccsd_Referentiels_Structure
     */
    private $_structure = null;

    public function __construct($structureId)
    {
        if (!is_numeric($structureId)) {
            //L'identifiant doit être un entier
            throw new Hal_Rdf_Exception('ID should be an integer');
        }

        $this->setStructure($structureId);

        if ($this->getStructure()->getStructid() == 0) {
            //La dtructure n'existe pas
            throw new Hal_Rdf_Exception('structure unknown');
        }

        //Définition de la racine du document (cas particulier pour les équipes de recherches
        $this->_elemRoot = $this->getElemRoot();

        parent::__construct($structureId);
    }

    public function setStructure($structureId)
    {
        $this->_structure = new Ccsd_Referentiels_Structure($structureId,[], true);
    }

    /**
     * @return Ccsd_Referentiels_Structure
     */
    public function getStructure()
    {
        return $this->_structure;
    }

    /**
     * Retourne le type de structure RDF
     * @return string
     */
    public function getElemRoot()
    {
        if ($this->getStructure()->getTypestruct() == Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM) {
            return Hal_Rdf_Schema::ORG_ORGANIZATIONALUNIT;
        }
        return Hal_Rdf_Schema::ORG_ORGANIZATION;
    }

    /**
     * @return string
     */
    public function createRdf()
    {
        $this->createRdfHeader([Hal_Rdf_Schema::NS_DCTERMS, Hal_Rdf_Schema::NS_ORG, Hal_Rdf_Schema::NS_OWL, Hal_Rdf_Schema::NS_SKOS, Hal_Rdf_Schema::NS_VCARD, Hal_Rdf_Schema::NS_HAL]);

        $elemRoot = $this->appendChild($this->_documentRoot, $this->_elemRoot, null, [Hal_Rdf_Schema::RDF_ABOUT => Hal_Rdf_Tools::createUri(static::getGraph(), $this->_structure->getStructid())]);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::ORG_CLASSIFICATION, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Vocabulary::getValue('structureType', $this->_structure->getTypestruct())]);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_PREFLABEL, $this->_structure->getStructname());
        $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_ALTLABEL, $this->_structure->getSigle());

        if ($this->_structure->getUrl()) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::VCARD_URL, null, [Hal_Rdf_Schema::RDF_RESOURCE => $this->_structure->getUrl()]);
        }

        if ($this->_structure->getAddress()) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::ORG_SITEADDRESS, $this->_structure->getAddress());
            //todo essayer de récupérer la ville avec DBpedia spotlight
        }

        if ($code = $this->_structure->getPaysid()) {
            //Lien vers DBPEDIA pour les pays
            $uri = Hal_Rdf_Schema::PREFIX_DBPEDIA . DIRECTORY_SEPARATOR . 'resource' . DIRECTORY_SEPARATOR . Zend_Locale::getTranslation(strtoupper($code), 'country');
            $this->appendChild($elemRoot, Hal_Rdf_Schema::VCARD_COUNTRYNAME, null, [Hal_Rdf_Schema::RDF_RESOURCE => $uri]);
        }

        $codes = [];

        foreach ($this->_structure->getParents() as $struct) {
            //Structures supérieures
            /**@var $structSup Ccsd_Referentiels_Structure */
            $structSup = $struct['struct'];
            $this->appendChild($elemRoot, Hal_Rdf_Schema::ORG_UNITOF, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(static::getGraph(), $structSup->getStructid())]);
            if ($struct['code'] != '') {
                $codes[] = $struct['code'];
            }
        }
        foreach ($codes as $code) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_IDENTIFIER, $code);
        }

        foreach ($this->_structure->getIdextLink() as $server => $array) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::OWL_SAMEAS, null, [Hal_Rdf_Schema::RDF_RESOURCE => $array['url']]);
            $this->appendChild($elemRoot, Hal_Rdf_Schema::NS_HAL . HAL_Rdf_Schema::NS_SEPARATOR . mb_strtolower($server), $array['id']);
        }
        //Statut de la structure
        $this->appendChild($elemRoot, Hal_Rdf_Schema::HAL_STATUS, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Vocabulary::getValue('status', $this->_structure->getValid())]);

        return $this->_domDocument->saveXML();
    }

}