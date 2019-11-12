<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 16/05/2017
 * Time: 13:47
 */

class Hal_Rdf_Schema
{

    const PREFIX_HAL = 'https://data.archives-ouvertes.fr';
    const PREFIX_DBPEDIA = 'http://fr.dbpedia.org';

    /* NAMESPACES */
    const NS_SEPARATOR = ':';
    const NS_ACM    = 'acm';
    const NS_RDF    = 'rdf';
    const NS_RDFS   = 'rdfs';
    const NS_FOAF   = 'foaf';
    const NS_OWL    = 'owl';
    const NS_DC     = 'dc';
    const NS_ORG    = 'org';
    const NS_VCARD  = 'vcard';
    const NS_SKOS   = 'skos';
    const NS_FABIO  = 'fabio';
    const NS_DCTERMS= 'dcterms';
    const NS_PRISM  = 'prism';
    const NS_CERIF  = 'cerif';
    const NS_SIOC   = 'sioc';
    const NS_BIBO   = 'bibo';
    const NS_ORE    = 'ore';
    const NS_HAL    = 'hal';
    const NS_XML    = 'xml';

    static protected $_namespaces = [
        self::NS_ACM       =>  'http://acm.rkbexplorer.com/ontologies/acm#',
        self::NS_RDF       =>  'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        self::NS_RDFS      =>  'http://www.w3.org/2000/01/rdf-schema#',
        self::NS_FOAF      =>  'http://xmlns.com/foaf/0.1/',
        self::NS_OWL       =>  'http://www.w3.org/2002/07/owl#',
        self::NS_DC        =>  'http://purl.org/dc/elements/1.1/',
        self::NS_ORG       =>  'http://www.w3.org/ns/org#',
        self::NS_VCARD     =>  'http://www.w3.org/2006/vcard/ns#',
        self::NS_SKOS      =>  'http://www.w3.org/2004/02/skos/core#',
        self::NS_FABIO     =>  'http://purl.org/spar/fabio/',
        self::NS_DCTERMS   =>  'http://purl.org/dc/terms/',
        self::NS_PRISM     =>  'http://prismstandard.org/namespaces/basic/2.0/',
        self::NS_CERIF     =>  'http://www.eurocris.org/ontologies/cerif/1.3/',
        self::NS_SIOC      =>  'http://rdfs.org/sioc/ns#',
        self::NS_BIBO      =>  'http://purl.org/ontology/bibo/',
        self::NS_ORE       =>  'http://www.openarchives.org/ore/terms/',
        self::NS_HAL       =>  'http://data.archives-ouvertes.fr/schema/',
        self::NS_XML       =>  'http://www.w3.org/2001/XMLSchema#'
    ];

    /* PROPERTIES */
    //BIBO
    const BIBO_VOLUME               = self::NS_BIBO . self::NS_SEPARATOR . 'volume';
    const BIBO_ISBN                 = self::NS_BIBO . self::NS_SEPARATOR . 'isbn';
    const BIBO_ISSUE                = self::NS_BIBO . self::NS_SEPARATOR . 'issue';
    const BIBO_PAGES                = self::NS_BIBO . self::NS_SEPARATOR . 'pages';
    const BIBO_PAGESTART            = self::NS_BIBO . self::NS_SEPARATOR . 'pageStart';
    const BIBO_PAGEEND              = self::NS_BIBO . self::NS_SEPARATOR . 'pageEnd';
    //CERIF
    const CERIF_ABSTRACT            = self::NS_CERIF . self::NS_SEPARATOR . 'abstract';
    const CERIF_ACRONYM             = self::NS_CERIF . self::NS_SEPARATOR . 'acronym';
    const CERIF_ENDDATE             = self::NS_CERIF . self::NS_SEPARATOR . 'endDate';
    const CERIF_INTERNALIDENTIFIER  = self::NS_CERIF . self::NS_SEPARATOR . 'internalIdentifier';
    const CERIF_LINKSTOFUNDING      = self::NS_CERIF . self::NS_SEPARATOR . 'linksToFunding';
    const CERIF_PROJECT             = self::NS_CERIF . self::NS_SEPARATOR . 'Project';
    const CERIF_STARTDATE           = self::NS_CERIF . self::NS_SEPARATOR . 'startDate';
    const CERIF_TITLE               = self::NS_CERIF . self::NS_SEPARATOR . 'title';
    //DC
    const DC_IDENTIFIER             = self::NS_DC . self::NS_SEPARATOR . 'identifier';
    const DC_LANGUAGE               = self::NS_DC . self::NS_SEPARATOR . 'language';
    const DC_SUBJECT                = self::NS_DC . self::NS_SEPARATOR . 'subject';
    const DC_TITLE                  = self::NS_DC . self::NS_SEPARATOR . 'title';
    //DCTERMS
    const DCTERMS_ABSTRACT          = self::NS_DCTERMS . self::NS_SEPARATOR . 'abstract';
    const DCTERMS_AVAILABLE         = self::NS_DCTERMS . self::NS_SEPARATOR . 'available';
    const DCTERMS_ALTERNATIVE       = self::NS_DCTERMS . self::NS_SEPARATOR . 'alternative';
    const DCTERMS_CITATION          = self::NS_DCTERMS . self::NS_SEPARATOR . 'bibliographicCitation';
    const DCTERMS_CREATED           = self::NS_DCTERMS . self::NS_SEPARATOR . 'created';
    const DCTERMS_CREATOR           = self::NS_DCTERMS . self::NS_SEPARATOR . 'creator';
    const DCTERMS_COVERAGE          = self::NS_DCTERMS . self::NS_SEPARATOR . 'coverage';
    const DCTERMS_CONTRIBUTOR       = self::NS_DCTERMS . self::NS_SEPARATOR . 'contributor';
    const DCTERMS_IDENTIFIER        = self::NS_DCTERMS . self::NS_SEPARATOR . 'identifier';
    const DCTERMS_ISPARTOF          = self::NS_DCTERMS . self::NS_SEPARATOR . 'isPartOf';
    const DCTERMS_ISREFERENCEDBY    = self::NS_DCTERMS . self::NS_SEPARATOR . 'isReferencedBy';
    const DCTERMS_ISREPLACEDBY      = self::NS_DCTERMS . self::NS_SEPARATOR . 'isReplacedBy';
    const DCTERMS_ISSUED            = self::NS_DCTERMS . self::NS_SEPARATOR . 'issued';
    const DCTERMS_ISVERSIONOF       = self::NS_DCTERMS . self::NS_SEPARATOR . 'isVersionOf';
    const DCTERMS_HASVERSION        = self::NS_DCTERMS . self::NS_SEPARATOR . 'hasVersion';
    const DCTERMS_LANGUAGE          = self::NS_DCTERMS . self::NS_SEPARATOR . 'language';
    const DCTERMS_MODIFIED          = self::NS_DCTERMS . self::NS_SEPARATOR . 'modified';
    const DCTERMS_POINT             = self::NS_DCTERMS . self::NS_SEPARATOR . 'Point';
    const DCTERMS_PUBLISHER         = self::NS_DCTERMS . self::NS_SEPARATOR . 'publisher';
    const DCTERMS_REFERENCES        = self::NS_DCTERMS . self::NS_SEPARATOR . 'references';
    const DCTERMS_RIGHTS            = self::NS_DCTERMS . self::NS_SEPARATOR . 'rights';
    const DCTERMS_SOURCE            = self::NS_DCTERMS . self::NS_SEPARATOR . 'source';
    const DCTERMS_SPATIAL           = self::NS_DCTERMS . self::NS_SEPARATOR . 'spatial';
    const DCTERMS_SUBJECT           = self::NS_DCTERMS . self::NS_SEPARATOR . 'subject';
    const DCTERMS_TITLE             = self::NS_DCTERMS . self::NS_SEPARATOR . 'title';
    const DCTERMS_TYPE              = self::NS_DCTERMS . self::NS_SEPARATOR . 'type';
    //FABIO
    const FABIO_JOURNAL             = self::NS_FABIO . self::NS_SEPARATOR . 'Journal';
    //FOAF
    const FOAF_FAMILYNAME           = self::NS_FOAF . self::NS_SEPARATOR . 'familyName';
    const FOAF_FIRSTNAME            = self::NS_FOAF . self::NS_SEPARATOR . 'firstName';
    const FOAF_HPAGE                = self::NS_FOAF . self::NS_SEPARATOR . 'homepage';
    const FOAF_INTEREST             = self::NS_FOAF . self::NS_SEPARATOR . 'interest';
    const FOAF_MAIL                 = self::NS_FOAF . self::NS_SEPARATOR . 'mbox_sha1sum';
    const FOAF_MEMBER               = self::NS_FOAF . self::NS_SEPARATOR . 'member';
    const FOAF_NAME                 = self::NS_FOAF . self::NS_SEPARATOR . 'name';
    const FOAF_PERSON               = self::NS_FOAF . self::NS_SEPARATOR . 'Person';
    const FOAF_PUBLICATION          = self::NS_FOAF . self::NS_SEPARATOR . 'publications';
    const FOAF_TOPIC                = self::NS_FOAF . self::NS_SEPARATOR . 'topic_interest';
    //HAL
    const HAL_TOPIC                 = self::NS_HAL . self::NS_SEPARATOR . 'topic';
    const HAL_ACM                   = self::NS_HAL . self::NS_SEPARATOR . 'acmSubject';
    const HAL_JEL                   = self::NS_HAL . self::NS_SEPARATOR . 'jelSubject';
    const HAL_MESH                  = self::NS_HAL . self::NS_SEPARATOR . 'meshSubject';
    const HAL_PERSON                = self::NS_HAL . self::NS_SEPARATOR . 'person';
    const HAL_STATUS                = self::NS_HAL . self::NS_SEPARATOR . 'status';
    const HAL_STRUCTURE             = self::NS_HAL . self::NS_SEPARATOR . 'structure';
    //ORE
    const ORE_AGGREGATES             = self::NS_ORE . self::NS_SEPARATOR . 'aggregates';
    const ORE_ISAGGREGATED          = self::NS_ORE . self::NS_SEPARATOR . 'isAggregatedBy';
    //ORG
    const ORG_CLASSIFICATION        = self::NS_ORG . self::NS_SEPARATOR . 'classification';
    const ORG_ORGANIZATION          = self::NS_ORG . self::NS_SEPARATOR . 'Organization';
    const ORG_ORGANIZATIONALUNIT    = self::NS_ORG . self::NS_SEPARATOR . 'OrganizationalUnit';
    const ORG_UNITOF                = self::NS_ORG . self::NS_SEPARATOR . 'unitOf';
    const ORG_SITEADDRESS           = self::NS_ORG . self::NS_SEPARATOR . 'siteAddress';
    //OWL
    const OWL_SAMEAS                = self::NS_OWL . self::NS_SEPARATOR . 'sameAs';
    //PRISM
    const PRISM_DOI                 = self::NS_PRISM . self::NS_SEPARATOR . 'doi';
    const PRISM_EISSN               = self::NS_PRISM . self::NS_SEPARATOR . 'eIssn';
    const PRISM_ISSN                = self::NS_PRISM . self::NS_SEPARATOR . 'issn';
    const PRISM_URL                 = self::NS_PRISM . self::NS_SEPARATOR . 'url';
    //RDF
    const RDF_ABOUT                 = self::NS_RDF . self::NS_SEPARATOR . 'about';
    const RDF_DATATYPE              = self::NS_RDF . self::NS_SEPARATOR . 'datatype';
    const RDF_RESOURCE              = self::NS_RDF . self::NS_SEPARATOR . 'resource';
    const RDF_VALUE                 = self::NS_RDF . self::NS_SEPARATOR . 'value';
    //RDFS
    const RDFS_SEEALSO              = self::NS_RDFS . self::NS_SEPARATOR . 'seeAlso';
    //SKOS
    const SKOS_ALTLABEL             = self::NS_SKOS . self::NS_SEPARATOR . 'altLabel';
    const SKOS_BROADER              = self::NS_SKOS . self::NS_SEPARATOR . 'broader';
    const SKOS_CONCEPT              = self::NS_SKOS . self::NS_SEPARATOR . 'Concept';
    const SKOS_INSCHEME             = self::NS_SKOS . self::NS_SEPARATOR . 'inScheme';
    const SKOS_NARROWER             = self::NS_SKOS . self::NS_SEPARATOR . 'narrower';
    const SKOS_NOTATION             = self::NS_SKOS . self::NS_SEPARATOR . 'notation';
    const SKOS_PREFLABEL            = self::NS_SKOS . self::NS_SEPARATOR . 'prefLabel';
    //VCARD
    const VCARD_COUNTRYNAME         = self::NS_VCARD . self::NS_SEPARATOR . 'country-name';
    const VCARD_ORG                 = self::NS_VCARD . self::NS_SEPARATOR . 'Organization';
    const VCARD_ORGNAME             = self::NS_VCARD . self::NS_SEPARATOR . 'organization-name';
    const VCARD_URL                 = self::NS_VCARD . self::NS_SEPARATOR . 'url';
    //XML
    const XML_LANG                  = self::NS_XML . self::NS_SEPARATOR . 'lang';

    /**
     * Retourne l'URL du namespace passé en parametre
     * @param string $namespace
     * @return mixed
     * @throws Hal_Rdf_Exception
     */
    static public function getNamespaceUri ($namespace)
    {
        if (!isset(static::$_namespaces[$namespace])) {
            throw new Hal_Rdf_Exception('Namespace unknown');
        }

        return static::$_namespaces[$namespace];
    }

}