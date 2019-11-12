<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Domain extends Hal_Rdf_Abstract
{

    static $_GRAPH  =   'subject';

    /**
     * @var Ccsd_Referentiels_Domain
     */
    private $_domain = null;

    private $_domainCode = null;

    public function __construct($domainId)
    {
        $this->_domainCode = $domainId;
        $this->_domain = new Ccsd_Referentiels_Domain();

        if (! $this->_domain->isValidCode($domainId)) {
            throw new Hal_Rdf_Exception("ID unknown");
        }

        $this->setCacheName($domainId);

        $this->_cachePath =  CACHE_ROOT . DIRECTORY_SEPARATOR . APPLICATION_ENV . DIRECTORY_SEPARATOR;
        $this->_cachePath .=  'rdf' . DIRECTORY_SEPARATOR . static::getGraph() . DIRECTORY_SEPARATOR;

    }

    public function setCacheName($id)
    {
        $this->_cacheName =  "{$id}.rdf";
    }

    /**
     * @return string
     */
    public function getElemRoot()
    {
        return Hal_Rdf_Schema::SKOS_CONCEPT;
    }

    public function createRdf()
    {
        $this->createRdfHeader([Hal_Rdf_Schema::NS_SKOS, Hal_Rdf_Schema::NS_DC, Hal_Rdf_Schema::NS_DCTERMS]);

        $elemRoot = $this->appendChild($this->_documentRoot, $this->getElemRoot(), null, [Hal_Rdf_Schema::RDF_ABOUT => Hal_Rdf_Tools::createUri(static::getGraph(), $this->_domainCode)]);

        $this->appendChild($elemRoot, Hal_Rdf_Schema::DC_IDENTIFIER, $this->_domainCode);

        $domainarXiv = Ccsd_Referentiels_Domain::getDomainArxiv($this->_domainCode);

        $codeArXiv = '';
        if ($domainarXiv) {
            foreach ($domainarXiv as $code) {
                $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_IDENTIFIER, $code);
                $codeArXiv .= '[' . $code . ']';
            }
        }

        foreach (['en', 'fr'] as $lang) {
            $trad = $this->_domain->getTranslation($this->_domainCode, $lang);
            $trad = trim(str_replace($codeArXiv, '', $trad));
            $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_PREFLABEL, $trad, [Hal_Rdf_Schema::XML_LANG => $lang]);
        }

        $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_INSCHEME, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Schema::PREFIX_HAL . DIRECTORY_SEPARATOR . static::getGraph()]);


        $broader = $this->_domain->getBroader($this->_domainCode);
        if ($broader) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_BROADER, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(static::getGraph(), $broader)]);
        }

        $narrower = $this->_domain->getNarrower($this->_domain->getCodeId($this->_domainCode));
        if (count($narrower)) {
            foreach ($narrower as $code) {
                $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_NARROWER, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(static::getGraph(), $code)]);
            }
        }

        return $this->_domDocument->saveXML();
    }
}
