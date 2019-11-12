<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

abstract class Hal_Rdf_Abstract
{

    static $_GRAPH;

    protected $_uriPrefix = 'https://data.archives-ouvertes.fr';

    protected $_namespaces = [
        'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
        'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
        'foaf' => 'http://xmlns.com/foaf/0.1/',
        'owl' => 'http://www.w3.org/2002/07/owl#',
        'dc' => 'http://purl.org/dc/elements/1.1/',
        'org' => 'http://www.w3.org/ns/org#',
        'vcard' => 'http://www.w3.org/2006/vcard/ns#',
        'skos' => 'http://www.w3.org/2004/02/skos/core#',
        'fabio' => 'http://purl.org/spar/fabio/',
        'dcterms' => 'http://purl.org/dc/terms/',
        'prism' => 'http://prismstandard.org/namespaces/basic/2.0/',
        'cerif' => 'http://www.eurocris.org/ontologies/cerif/1.3/',
        'sioc' => 'http://rdfs.org/sioc/ns#',
        'bibo' => 'http://purl.org/ontology/bibo/',
        'ore' => 'http://www.openarchives.org/ore/terms/',
    ];
    /**
     * Repertoire de sauvegarde du fichier de cache
     * @var string
     */
    protected $_cachePath = null;

    protected $_id = null;

    /**
     * @var string
     * 1jour
     */
    protected $_cacheDuration = 86400;

    protected $_cacheName = null;

    /**
     * @var Ccsd_DOMDocument
     */
    protected $_domDocument = null;

    /**
     * @var DOMElement
     */
    protected $_documentRoot = null;

    /**
     * @var string
     */
    protected $_elemRoot = '';

    /**
     * Hal_Rdf_Abstract constructor.
     * @param $id
     */
    public function __construct($id)
    {
        $this->setId($id);
        $this->setCacheName($id);
        $this->initCachePath();
    }

    /**
     * @param string $graph
     * @param int $id
     * @return string
     */
    static public function computeCachePath($graph, $id) {
        $cachePath = CACHE_ROOT . DIRECTORY_SEPARATOR . APPLICATION_ENV . DIRECTORY_SEPARATOR;
        $cachePath .= 'rdf' . DIRECTORY_SEPARATOR . $graph . DIRECTORY_SEPARATOR;
        $cachePath .= substr(wordwrap(sprintf("%08d", $id), 2, DIRECTORY_SEPARATOR, 1), 0, 5) . DIRECTORY_SEPARATOR;
        return $cachePath;
    }
    /**
     *
     */
    public function initCachePath()
    {
        $this->_cachePath = static::computeCachePath($this->getGraph(), $this->_id);
    }

    /**
     * Retourne le nom du graphe
     * @return string
     */
    static public function getGraph()
    {
        return static::$_GRAPH;
    }

    /**
     * @return null
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @param $id
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @param bool $useCache
     * @return bool|string
     * @throws Hal_Rdf_Exception
     */
    public function getRdf($useCache = false)
    {
        if ($useCache && Ccsd_Cache::exist($this->_cacheName, $this->_cacheDuration, $this->_cachePath)) {
            return Ccsd_Cache::get($this->_cacheName, $this->_cachePath);
        }
        $content = $this->createRdf();
        Ccsd_Cache::save($this->_cacheName, $content, $this->_cachePath);
        return $content;
    }

    /**
     * Fonction qui va retourner le RDF d'un élément
     * @return string
     * @throws Hal_Rdf_Exception
     */
    abstract public function createRdf();

    /**
     * Delete a RDF file (a cache file)
     * @return bool
     * @throws Hal_Rdf_Exception
     */
    public function deleteRdf()
    {
        if ($this->getCacheName() == null || $this->getCachePath() == null) {
            throw new Hal_Rdf_Exception("We are trying to delete a file without a name or path");
        }

        return Ccsd_Cache::delete($this->getCacheName(), $this->getCachePath());
    }

    /**
     * @param string $graph
     * @param int $docid
     * @return bool
     */
    static function deleteCacheRdfFromDocid($graph, $docid) {
        $cachePath = static::computeCachePath($graph,$docid);
        $cacheName = static::computeCacheName($docid);
        return Ccsd_Cache::delete($cacheName, $cachePath, true);
    }
    /**
     * Retourne le nom du fichier de cache
     * @return string
     */
    public function getCacheName()
    {
        return $this->_cacheName;
    }

    /**
     * @param int $id
     * @return string
     */
    static public function computeCacheName($id) {
        return "{$id}.rdf";
    }

    /**
     * @param $id
     */
    public function setCacheName($id)
    {
        $this->_cacheName = static::computeCacheName($id);
    }

    /**
     * @return string
     */
    public function getCachePath()
    {
        return $this->_cachePath;
    }

    /**
     * @param null $namespaces
     */
    public function createRdfHeader($namespaces = null)
    {
        $this->_domDocument = new Ccsd_DOMDocument('1.0', 'UTF-8');
        $this->_domDocument->formatOutput = true;
        $this->_domDocument->substituteEntities = true;
        $this->_domDocument->preserveWhiteSpace = false;
        $this->_documentRoot = $this->_domDocument->createElement('rdf:RDF');
        if ($namespaces == null) {
            $namespaces = array_keys($this->_namespaces);
        }
        $namespaces = array_merge([Hal_Rdf_Schema::NS_RDF], $namespaces);
        foreach ($namespaces as $namespace) {
            try {
                $this->_documentRoot->setAttributeNS('http://www.w3.org/2000/xmlns/', "xmlns:{$namespace}", Hal_Rdf_Schema::getNamespaceUri($namespace));
            } catch (Hal_Rdf_Exception $e) {
                //Le namespace n'es pas connu
            }
        }
        $this->_domDocument->appendChild($this->_documentRoot);
    }

    /**
     * @param $xmlstr
     * @param $elementName
     * @return DOMNode
     */
    public function getNode($xmlstr, $elementName)
    {
        $dom = new Ccsd_DOMDocument();
        $dom->loadXML($xmlstr);

        $node = $dom->getElementsByTagName($elementName)->item(0);

        return $this->_domDocument->importNode($node, true);
    }

    /**
     * @param DOMElement $node
     * @param string $name
     * @param null $value
     * @param array $attributes
     * @param bool $force
     * @return null|DOMElement
     */
    public function appendChild(&$node, $name, $value = null, $attributes = [], $force = false)
    {
        if (!$force && $value == '' && !count($attributes)) {
            return null;
        }

        $elem = $this->_domDocument->createElement($name, $value);
        if (count($attributes)) {
            foreach ($attributes as $aname => $avalue) {
                $elem->setAttribute($aname, $avalue);
            }
        }
        $node->appendChild($elem);

        return $elem;
    }

}