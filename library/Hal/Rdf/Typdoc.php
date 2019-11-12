<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Typdoc extends Hal_Rdf_Abstract
{

    static $_GRAPH  =   'doctype';

    const NS_BIBO =    'bibo';
    const PREFIX_BIBO =    'http://purl.org/ontology/bibo/';

    const NS_COAR =    'coar';
    const PREFIX_COAR =    'http://purl.org/coar/resource_type/';

    const NS_FABIO =    'fabio';
    const PREFIX_FABIO =    'http://purl.org/spar/fabio/';


    const TYPE_DEFAULT  =   'DEFAULT';

    static protected $_biboResourceType = [
        Ccsd_Referentiels_Typdoc::TYPE_ART        =>  'Article',
        Ccsd_Referentiels_Typdoc::TYPE_THESE      =>  'Thesis',
        Ccsd_Referentiels_Typdoc::TYPE_BOOK       =>  'Book',
        Ccsd_Referentiels_Typdoc::TYPE_COMM       =>  'Conference',
        Ccsd_Referentiels_Typdoc::TYPE_IMG        =>  'Image',
        Ccsd_Referentiels_Typdoc::TYPE_PATENT     =>  'Patent',
        Ccsd_Referentiels_Typdoc::TYPE_SON        =>  'AudioDocument',
        Ccsd_Referentiels_Typdoc::TYPE_VIDEO      =>  'AudioVisualDocument',
        Ccsd_Referentiels_Typdoc::TYPE_MAP        =>  'Map',
        Ccsd_Referentiels_Typdoc::TYPE_COUV       =>  'Chapter',
        Ccsd_Referentiels_Typdoc::TYPE_REPORT     =>  'Report',
        Ccsd_Referentiels_Typdoc::TYPE_NOTE       =>  'Note',
        self::TYPE_DEFAULT    =>  'Document'
    ];

    static protected $_coarResourceType = [
        Ccsd_Referentiels_Typdoc::TYPE_UNDEFINED  =>  'c_816b',
        Ccsd_Referentiels_Typdoc::TYPE_ART        =>  'c_6501',
        Ccsd_Referentiels_Typdoc::TYPE_THESE =>  'c_db06',
        Ccsd_Referentiels_Typdoc::TYPE_BOOK =>  'c_2f33',
        Ccsd_Referentiels_Typdoc::TYPE_COMM =>  'c_c94f',
        Ccsd_Referentiels_Typdoc::TYPE_IMG =>  'c_c513',
        Ccsd_Referentiels_Typdoc::TYPE_PATENT =>  'c_15cd',
        Ccsd_Referentiels_Typdoc::TYPE_SON =>  'c_18cc',
        Ccsd_Referentiels_Typdoc::TYPE_VIDEO =>  'c_12ce',
        Ccsd_Referentiels_Typdoc::TYPE_MAP =>  'c_12cd',
        Ccsd_Referentiels_Typdoc::TYPE_LECTURE =>  'c_8544',
        Ccsd_Referentiels_Typdoc::TYPE_COUV =>  'c_3248',
        Ccsd_Referentiels_Typdoc::TYPE_REPORT =>  'c_93fc',
        Ccsd_Referentiels_Typdoc::TYPE_NOTE =>  'c_ba08',
        Ccsd_Referentiels_Typdoc::TYPE_POSTER =>  'c_6670',
        Ccsd_Referentiels_Typdoc::TYPE_OUV =>  'c_2f33',
        //'DOUV' =>  '',
        Ccsd_Referentiels_Typdoc::TYPE_OTHER =>  'c_1843',
        //'HDR' =>  '',
        // Pour l'ecole des chartes, thèse d'établissement préparée en deux ans (de niveau intermédiaire entre un mémoire de master 2 et une thèse de doctorat).
        // voir https://fr.wikipedia.org/wiki/Archiviste_pal%C3%A9ographe
        //'ETABTHESE' => '',
        Ccsd_Referentiels_Typdoc::TYPE_MEM =>  'c_bdcc',
        //'MINUTES' =>  '',
        //'SYNTHESE' =>  '',
        Ccsd_Referentiels_Typdoc::TYPE_PRESCONF =>  'c_c94f',
        Ccsd_Referentiels_Typdoc::TYPE_OTHERREPORT =>  'c_18wq',
        Ccsd_Referentiels_Typdoc::TYPE_REPACT =>  'c_18gh',
        self::TYPE_DEFAULT =>  'c_18cf',
    ];

    static protected $_fabioResourceType = [
        Ccsd_Referentiels_Typdoc::TYPE_UNDEFINED  =>  'Preprint',
        Ccsd_Referentiels_Typdoc::TYPE_ART        =>  'Article',
        Ccsd_Referentiels_Typdoc::TYPE_THESE      =>  'DoctoralThesis',
        Ccsd_Referentiels_Typdoc::TYPE_BOOK       =>  'Book',
        Ccsd_Referentiels_Typdoc::TYPE_COMM       =>  'ConferencePaper',
        Ccsd_Referentiels_Typdoc::TYPE_IMG        =>  'Image',
        Ccsd_Referentiels_Typdoc::TYPE_PATENT     =>  'Patent',
        Ccsd_Referentiels_Typdoc::TYPE_SON        =>  'AudioDocument',
        Ccsd_Referentiels_Typdoc::TYPE_VIDEO      =>  'Movie',
        Ccsd_Referentiels_Typdoc::TYPE_COUV       =>  'Chapter',
        Ccsd_Referentiels_Typdoc::TYPE_REPORT     =>  'Report',
        Ccsd_Referentiels_Typdoc::TYPE_NOTE       =>  'LectureNotes',
        Ccsd_Referentiels_Typdoc::TYPE_LECTURE    =>  'LectureNotes',
        Ccsd_Referentiels_Typdoc::TYPE_POSTER     =>  'ConferencePoster',
        self::TYPE_DEFAULT    =>  'Works'
    ];

    /**
     * @var Ccsd_Referentiels_Typdoc
     */
    private $_doctype = null;

    /**
     * @var null
     */
    private $_doctypeCode = null;

    /**
     * Hal_Rdf_Typdoc constructor.
     * @param $doctypeCode
     * @throws Hal_Rdf_Exception
     */
    public function __construct($doctypeCode)
    {
        //todo Vérification de l'existance de ce type de document

        parent::__construct($doctypeCode);

        $this->_doctype = new Ccsd_Referentiels_Typdoc($doctypeCode);

        if (!$this->_doctype->exist($doctypeCode)) {
            throw new Hal_Rdf_Exception('DOCTYPE unknown');
        }
    }
    /**
     * @param string $doctypeCode
     */
    public function setId($doctypeCode)
    {
        $this->_doctypeCode = $doctypeCode;
    }

    /**
     * @param string $graph
     * @param int $id
     * @return string
     */
    static public function computeCachePath($graph, $id) {
        $cachePath = CACHE_ROOT . DIRECTORY_SEPARATOR . APPLICATION_ENV . DIRECTORY_SEPARATOR;
        $cachePath .= 'rdf' . DIRECTORY_SEPARATOR . static::getGraph(). DIRECTORY_SEPARATOR;
        return $cachePath;
    }
    /**
     * @return string
     */
    public function getElemRoot()
    {
        return Hal_Rdf_Schema::SKOS_CONCEPT;
    }


    /**
     * @return string
     * @throws Zend_Exception
     */
    public function createRdf()
    {
        $this->createRdfHeader([Hal_Rdf_Schema::NS_DC, Hal_Rdf_Schema::NS_SKOS, Hal_Rdf_Schema::NS_RDFS, Hal_Rdf_Schema::NS_OWL]);

        $elemRoot = $this->appendChild($this->_documentRoot, $this->getElemRoot(), null, [Hal_Rdf_Schema::RDF_ABOUT => $this->getUri($this->_doctypeCode)]);

        $this->appendChild($elemRoot, Hal_Rdf_Schema::DC_IDENTIFIER, $this->_doctypeCode);

        foreach (['en', 'fr'] as $lang) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_PREFLABEL, Zend_Registry::get(ZT)->translate('typdoc_' . $this->_doctypeCode, $lang), [Hal_Rdf_Schema::XML_LANG => $lang]);
        }
        $this->appendChild($elemRoot, Hal_Rdf_Schema::SKOS_INSCHEME, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Schema::PREFIX_HAL . DIRECTORY_SEPARATOR . static::getGraph()]);

        $predicat = static::existResourceType($this->_doctypeCode, static::NS_FABIO) ? Hal_Rdf_Schema::OWL_SAMEAS : Hal_Rdf_Schema::RDFS_SEEALSO;
        $this->appendChild($elemRoot, $predicat, null, [Hal_Rdf_Schema::RDF_RESOURCE  => $this->getFabioResourceType($this->_doctypeCode)]);

        $predicat = static::existResourceType($this->_doctypeCode, static::NS_BIBO) ? Hal_Rdf_Schema::OWL_SAMEAS : Hal_Rdf_Schema::RDFS_SEEALSO;
        $this->appendChild($elemRoot, $predicat, null, [Hal_Rdf_Schema::RDF_RESOURCE => $this->getBiboResourceType($this->_doctypeCode)]);

        $predicat = static::existResourceType($this->_doctypeCode, static::NS_COAR) ? Hal_Rdf_Schema::OWL_SAMEAS : Hal_Rdf_Schema::RDFS_SEEALSO;
        $this->appendChild($elemRoot, $predicat, null, [Hal_Rdf_Schema::RDF_RESOURCE  => $this->getCOARResourceType($this->_doctypeCode)]);

        return $this->_domDocument->saveXML();
    }

    /**
     * @param int $id
     * @return string
     */
    public function getUri ($id)
    {
        if (static::existResourceType($this->_doctypeCode, static::NS_FABIO)) {
            $doctype = static::getResourceType($id, static::NS_FABIO);
        } else {
            $doctype = ucfirst(strtolower($id));
        }
        return Hal_Rdf_Tools::createUri(static::getGraph(), $doctype);
    }

    /**
     * @param $type
     * @return string
     */
    protected function getBiboResourceType($type)
    {
        return static::PREFIX_BIBO . static::getResourceType($type, static::NS_BIBO);
    }

    /**
     * @param $type
     * @return string
     */
    protected function getCOARResourceType($type)
    {
        return static::PREFIX_COAR . static::getResourceType($type, static::NS_COAR);
    }

    /**
     * @param $type
     * @return string
     */
    protected function getFabioResourceType($type)
    {
        return static::PREFIX_FABIO . static::getResourceType($type, static::NS_FABIO);
    }

    /**
     * @param $halType
     * @param $onto
     * @return mixed
     */
    static public function getResourceType($halType, $onto)
    {
        $array = static::getOntoDocTypes($onto);
        if (isset($array[$halType])) {
            $type = $array[$halType];
        } else {
            $type = $array[static::TYPE_DEFAULT];
        }
        return $type;
    }

    /**
     * Indique si un type de document HAL a une correspondance dans une ontologie (coar, fabio, bibo)
     * @param $halType
     * @param $onto
     * @return bool
     */
    static public function existResourceType($halType, $onto)
    {
        $array = static::getOntoDocTypes($onto);
        return array_key_exists($halType, $array);
    }

    /**
     * retourne la liste des types de dépôts pour une ontologie donnée
     * @param $onto
     * @return array
     */
    static private function getOntoDocTypes($onto)
    {
        if ($onto == static::NS_BIBO) {
            $array = static::$_biboResourceType;
        } else if ($onto == static::NS_FABIO) {
            $array = static::$_fabioResourceType;
        } else {
            $array = static::$_coarResourceType;
        }
        return $array;
    }

}
