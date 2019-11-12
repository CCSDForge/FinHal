<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Author extends Hal_Rdf_Abstract
{

    const FACET_KWORD_FR    =   'fr_keyword_s';
    const FACET_KWORD_EN    =   'en_keyword_s';
    const FACET_KWORD       =   'keyword_s';
    const FACET_DOMAIN      =   'domain_s';

    static $_GRAPH  =   'author';


    /**
     * @var Hal_Document_Author
     */
    protected $_author = null;

    /**
     * @var array|null
     */
    protected $_facets = null;

    /**
     * Hal_Rdf_Author constructor.
     * @param int $authorId
     * @throws Hal_Rdf_Exception
     */
    public function __construct($authorId)
    {
        if (!is_numeric($authorId)) {
            throw new Hal_Rdf_Exception('ID should be an integer');
        }

        $this->_author = new Hal_Document_Author($authorId);
        if ($this->getAuthor()->getAuthorid() == 0) {
            throw new Hal_Rdf_Exception('ID unknown');
        }
        $this->_facets = $this->getFacet("authId_i:{$this->getAuthor()->getAuthorid()}", [static::FACET_KWORD_FR, static::FACET_KWORD_EN, static::FACET_KWORD, static::FACET_DOMAIN]);

        parent::__construct($authorId);
    }

    /**
     * @return string
     */
    public function getElemRoot()
    {
        return Hal_Rdf_Schema::FOAF_PERSON;
    }

    /**
     * @return Hal_Document_Author
     */
    public function getAuthor()
    {
        return $this->_author;
    }

    /**
     * @return string
     */
    public function createRdf()
    {
        if ($this->getAuthor()->getAuthorid() == 0) {
            return '';
        }
        $this->createRdfHeader([Hal_Rdf_Schema::NS_FOAF, Hal_Rdf_Schema::NS_OWL, Hal_Rdf_Schema::NS_DC, Hal_Rdf_Schema::NS_ORE]);

        $elemRoot = $this->appendChild($this->_documentRoot, $this->getElemRoot(), null, [Hal_Rdf_Schema::RDF_ABOUT => Hal_Rdf_Tools::createUri(static::getGraph(), $this->getAuthor()->getAuthorid())]);
        $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_NAME, $this->getAuthor()->getFullName(true));
        $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_FIRSTNAME, $this->getAuthor()->getFirstname());
        $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_FAMILYNAME, $this->getAuthor()->getLastname());
        $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_MAIL, sha1($this->getAuthor()->getEmail()));

        if ($this->getAuthor()->getUrl()){
            $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_HPAGE, null, [Hal_Rdf_Schema::RDF_RESOURCE => $this->getAuthor()->getUrl(), Hal_Rdf_Schema::DC_TITLE => 'Personal website']);
        }
        if ($this->getAuthor()->getOrganismId() && $this->getAuthor()->getOrganismId() != 0) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_MEMBER, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri('structure', $this->getAuthor()->getOrganismId())]);
        }

        /* IdHAL */
        if ($this->getAuthor()->getIdHal()){
            $this->appendChild($elemRoot, Hal_Rdf_Schema::ORE_ISAGGREGATED, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(static::getGraph(), $this->getAuthor()->getIdhalstring())]);
        }
        /* Identifiants externes*/
        foreach ($this->getAuthor()->getIdsAuthor() as $item) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::OWL_SAMEAS, null, [Hal_Rdf_Schema::RDF_RESOURCE => $item]);
        }

        /* mots clés */
        $this->addKeywords($elemRoot);

        /* domaines */
        if (isset($this->_facets['domain_s'])) {
            $tmp = [];
            foreach (array_keys($this->_facets['domain_s']) as $domain) {
                $domain = str_replace(['0.','1.', '2.'], '', $domain);
                if (! in_array($domain, $tmp)) {
                    $tmp[] = $domain;
                }
            }
            foreach (array_unique($tmp) as $domain) {
                if( count(preg_grep( '/^' . $domain . '/i' , $tmp)) > 1 ) {
                    //Le domaine est égaelement présent en plus précis dans le tableau
                    continue;
                }
                $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_INTEREST, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri('subject', $domain)]);
            }
        }

        return $this->_domDocument->saveXML();
    }

    /**
     * todo à améliorer, voir comment récupérer toutes les facettes keyword avec la langue
     * @param $node DOMElement
     */
    private function addKeywords($node)
    {
        $keywordsWithoutLang = [];
        if (isset($this->_facets[static::FACET_KWORD])) {
            $keywordsWithoutLang = array_keys($this->_facets[static::FACET_KWORD]);
        }

        $tmpLowercase = [];
        foreach (['fr' => static::FACET_KWORD_FR, 'en' => static::FACET_KWORD_EN] as $lang => $ks) {
            if (isset($this->_facets[$ks])) {
                foreach (array_keys($this->_facets[$ks]) as $keyword) {
                    if (($pos = array_search($keyword, $keywordsWithoutLang)) !== false) {
                        unset($keywordsWithoutLang[$pos]);
                    }
                    if (! in_array(strtolower($keyword), $tmpLowercase)) {
                        $tmpLowercase[] = strtolower($keyword);
                        $this->appendChild($node, Hal_Rdf_Schema::FOAF_TOPIC, $keyword, [Hal_Rdf_Schema::XML_LANG => $lang]);
                    }
                }
            }
        }
        if (count($keywordsWithoutLang)) {
            $tmpLowercase = [];
            foreach ($keywordsWithoutLang as $keyword) {
                if (! in_array(strtolower($keyword), $tmpLowercase)) {
                    $tmpLowercase[] = strtolower($keyword);
                    $this->appendChild($node, Hal_Rdf_Schema::FOAF_TOPIC, $keyword);
                }

            }
        }

    }

    /**
     * @param $q
     * @param $facetNames
     * @return array
     */
    protected function getFacet($q, $facetNames)
    {
        $query = "q={$q}&rows=0&wt=phps&facet=true&facet.mincount=1&facet.rows=100";
        foreach ($facetNames as $facetName) {
            $query .= "&facet.field={$facetName}";
        }
        try {
            $res = unserialize(Ccsd_Tools::solrCurl($query));

            if (! isset($res['facet_counts']['facet_fields'])) {
                return [];
            }
        } catch(Exception $e) {
            return [];
        }
        return $res['facet_counts']['facet_fields'];
    }

}