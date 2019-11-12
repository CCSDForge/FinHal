<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Idhal extends Hal_Rdf_Abstract
{

    static $_GRAPH  =   'author';

    /**
     * @var Hal_Cv   Pourquoi Id n'est pas utilise ???
     */
    private $_idhal = null;
    /**
     * @var string
     */
    private $_idhalCode = null;
    /**
     * @var string[]
     */
    protected $_documents = [];

    /**
     * Hal_Rdf_Idhal constructor.
     * @param string $idHalCode
     * @throws Hal_Rdf_Exception
     */
    public function __construct($idHalCode)
    {
        parent::__construct($idHalCode);
        $this->_idhal = new Hal_Cv(0, $idHalCode);
        $this->_idhal->load(false);

        if ($this->_idhal->getIdHal() == 0) {
            throw new Hal_Rdf_Exception("ID unknown");
        }
        $this->_documents = $this->getDocuments($this->_idhalCode);
    }

    /**
     * @param $idHalCode
     */
    public function setId($idHalCode)
    {
        $this->_idhalCode = $idHalCode;
    }

    /**
     * Le cache est un sous rep du cache auteur!
     * Pas d'utilisation de l'Id ????
     * @param string $graph
     * @param int $id
     * @return string
     */
    static public function computeCachePath($graph, $id) {
        $cachePath = CACHE_ROOT . DIRECTORY_SEPARATOR . APPLICATION_ENV . DIRECTORY_SEPARATOR;
        $cachePath .= 'rdf' . DIRECTORY_SEPARATOR . static::getGraph(). DIRECTORY_SEPARATOR;
        $cachePath .= 'idhal' . DIRECTORY_SEPARATOR;
        return $cachePath;
    }

    /**
     * @return Hal_Cv
     */
    public function getIdhal()
    {
        return $this->_idhal;
    }

    /**
     * @return string
     */
    public function getElemRoot()
    {
        return Hal_Rdf_Schema::FOAF_PERSON;
    }

    /**
     * @return string
     */
    public function createRdf()
    {
        $this->createRdfHeader([Hal_Rdf_Schema::NS_RDFS, Hal_Rdf_Schema::NS_FOAF, Hal_Rdf_Schema::NS_OWL, Hal_Rdf_Schema::NS_HAL, Hal_Rdf_Schema::NS_ORE]);

        $elemRoot = $this->appendChild($this->_documentRoot, $this->getElemRoot(), null, [Hal_Rdf_Schema::RDF_ABOUT => Hal_Rdf_Tools::createUri(static::getGraph(), $this->_idhalCode)]);

        $formAuthor = new Hal_Document_Author($this->_idhal->getDefaultFormAuthor()['AUTHORID']);

        $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_NAME, $formAuthor->getFullName(true));
        $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_FIRSTNAME, $formAuthor->getFirstname());
        $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_FAMILYNAME, $formAuthor->getLastname());
        $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_MAIL, sha1($formAuthor->getEmail()));

        /* Formes auteur */
        if ($this->_idhal->getFormAuthors()){
            foreach($this->_idhal->getFormAuthors() as $formAuthor) {
                $this->appendChild($elemRoot, Hal_Rdf_Schema::ORE_AGGREGATES, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(static::getGraph(), $formAuthor['AUTHORID'])]);
            }
        }

        /* Identifiants exterieurs */
        $serverUrl = $this->_idhal->getServerUrl();
        $serverExt = $this->_idhal->getServerExt();
        foreach ($this->_idhal->getIdExt() as $serverId => $id) {
            if (isset($serverExt[$serverId])) {
                $this->appendChild($elemRoot, Hal_Rdf_Schema::NS_HAL . HAL_Rdf_Schema::NS_SEPARATOR . str_replace(' ', '', mb_strtolower($serverExt[$serverId])), $id);
            }
            if (isset($serverUrl[$serverId])) {
                $this->appendChild($elemRoot, Hal_Rdf_Schema::OWL_SAMEAS, null, [Hal_Rdf_Schema::RDF_RESOURCE => $serverUrl[$serverId] . $id]);
            }
        }

        /**
         * @var  $serverId
         * @var  $id
         */
        $serverExt = $this->_idhal->getSocialServerExt();
        foreach ($this->_idhal->getSocialUrlExt() as $serverId => $id) {

            if (!preg_match('/^http/', $id)) {
                $this->appendChild($elemRoot, Hal_Rdf_Schema::NS_HAL . HAL_Rdf_Schema::NS_SEPARATOR . str_replace(' ', '',mb_strtolower($serverExt[$serverId])), $id);
            } else {
                $this->appendChild($elemRoot, Hal_Rdf_Schema::RDFS_SEEALSO, null, [Hal_Rdf_Schema::RDF_RESOURCE => $id]);
            }

        }

        /** @var string $document */
        foreach ($this->_documents as $document) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::FOAF_PUBLICATION, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri('document', $document)]);

        }

        return $this->_domDocument->saveXML();
    }

    /**
     * Retourne les documents pour un auteur
     * @param $idhal
     * @return string[]
     */
    protected function getDocuments($idhal)
    {
        $query = "q=authIdHal_s:{$idhal}&fl=halId_s&rows=10000&wt=phps";

        try {
            $res = unserialize(Ccsd_Tools::solrCurl($query));
            if (! isset($res['response']['docs'])) {
                return [];
            }
        } catch(Exception $e) {
            return [];
        }
        $docs = [];
        foreach ($res['response']['docs'] as $doc) {
            $docs[] = $doc['halId_s'];
        }
        return $docs;
    }

}