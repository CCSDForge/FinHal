<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/01/2017
 * Time: 21:06
 */

class Hal_Rdf_Document extends Hal_Rdf_Abstract
{

    const RELATION  = 'RELATION';

    static $_GRAPH  =   'document';

    /**
     * @var Hal_Document
     */
    private $_document = null;

    /**
     * Hal_Rdf_Document constructor.
     * @param int $docid identifiant du document
     * @throws Hal_Rdf_Exception
     */
    public function __construct($docid)
    {
        $this->initDocument($docid);
        if ($this->getDocument() === false || $this->getDocument()->getDocid() == 0) {
            throw new Hal_Rdf_Exception("ID unknown");
        }

        $this->_elemRoot = $this->getElemRoot($this->_document->getTypDoc());

        parent::__construct($docid);
    }

    /**
     * @param $docid
     */
    public function initDocument($docid)
    {
        $this->_document = Hal_Document::find($docid, '', 0);
    }

    /**
     * @return Hal_Document
     */
    public function getDocument()
    {
        return $this->_document;
    }

    /**
     * Retourne le RDF d'un document
     * @return string
     */
    public function createRdf()
    {
        $this->createRdfHeader([Hal_Rdf_Schema::NS_FABIO, Hal_Rdf_Schema::NS_HAL, Hal_Rdf_Schema::NS_FOAF, Hal_Rdf_Schema::NS_OWL, Hal_Rdf_Schema::NS_DC, Hal_Rdf_Schema::NS_DCTERMS, Hal_Rdf_Schema::NS_SKOS, Hal_Rdf_Schema::NS_BIBO, Hal_Rdf_Schema::NS_ORE]);

        $elemRoot = $this->addDocumentHeader();

        // Citation
        $citation = str_replace(['&#x3008;', '&#x3009;'], ['〈', '〉'], strip_tags($this->_document->getCitation('full')));
        $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_CITATION, $citation);

        // Identifiant du document
        $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_IDENTIFIER, $this->_document->getId());
        // URI du document
        $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_IDENTIFIER, $this->_document->getUri());

        // NNT
        $value = $this->_document->getMeta('nnt');
        if ($value) {
            $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_IDENTIFIER, $value);
        }

        //Identifiants externes
        foreach ($this->_document->getIdsCopyUrl() as $server => $id) {
            $ns = $server == 'doi' ? Hal_Rdf_Schema::NS_BIBO : Hal_Rdf_Schema::NS_HAL;
            $this->appendChild($elemRoot, $ns . Hal_Rdf_Schema::NS_SEPARATOR . strtolower($server), $id['id']);
            $this->appendChild($elemRoot, Hal_Rdf_Schema::OWL_SAMEAS, null, [Hal_Rdf_Schema::RDF_RESOURCE => $id['link']]);
        }

        $this->addDocumentContributor($elemRoot);

        $this->addDocumentMetas($elemRoot);

        $this->addDocumentAuthors($elemRoot);

        $this->addDocumentFiles($elemRoot);

        $this->addDocumentRelated($elemRoot);

        return $this->_domDocument->saveXML();
    }

    /**
     * @return DOMElement
     */
    private function addDocumentHeader()
    {
        $lastVersion = 1;

        $documentUri = Hal_Rdf_Tools::createUri(static::getGraph(), $this->getDocument()->getId());

        // URI du document HAL (sans notion de version)
        $elemRoot = $this->appendChild($this->_documentRoot, $this->_elemRoot, null, [Hal_Rdf_Schema::RDF_ABOUT => $documentUri]);
        foreach ($this->_document->getDocVersions() as $version => $date) {
            if ($version > $lastVersion) {
                $lastVersion = $version;
            }

            // Liste des versions disponibles
            $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_HASVERSION, null, [Hal_Rdf_Schema::RDF_RESOURCE => $documentUri . 'v' . $version]);
        }

        // URI du document HAL (avec version)
        $elemRoot = $this->appendChild($this->_documentRoot, $this->_elemRoot, null, [Hal_Rdf_Schema::RDF_ABOUT => $documentUri. 'v' . $this->_document->getVersion()]);

        //  URI d'un document HAL
        $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_ISVERSIONOF, null, [Hal_Rdf_Schema::RDF_RESOURCE => $documentUri]);

        if ($lastVersion != $this->_document->getVersion()) {
            /* le document n'est pas la dernière version */
            $this->appendChild($elemRoot, Hal_Rdf_Schema::DCTERMS_ISREPLACEDBY, null, [Hal_Rdf_Schema::RDF_RESOURCE => $documentUri . 'v' . $lastVersion]);
        }

        return $elemRoot;
    }

    /**
     * @param $node DOMElement
     */
    private function addDocumentContributor($node)
    {
        // Contributeur
        $addNewContrib = true;
        $uid = $this->_document->getContributor('uid');
        $idhal = Hal_Cv::existForUid($uid);
        if ($idhal) {
            // URI vers le référentiel idhal (déposant possédant un idHAL)
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_CONTRIBUTOR, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(Hal_Rdf_Author::$_GRAPH, $idhal)]);
            $addNewContrib = false;
        }

        if ($addNewContrib) {
            // Déposant ne possédant pas de forme auteur
            $contributor = $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_CONTRIBUTOR, null, [], true);
            $person = $this->appendChild($contributor, Hal_Rdf_Schema::FOAF_PERSON, null, [], true);
            $this->appendChild($person, Hal_Rdf_Schema::FOAF_NAME, $this->_document->getContributor('fullname'));
            $this->appendChild($person, Hal_Rdf_Schema::FOAF_FIRSTNAME, $this->_document->getContributor('firstname'));
            $this->appendChild($person, Hal_Rdf_Schema::FOAF_FAMILYNAME, $this->_document->getContributor('lastname'));
        }
    }

    /**
     * @param $node DOMElement
     * @throws Hal_Rdf_Exception
     */
    private function addDocumentMetas($node)
    {
        //Type de document
        $typdoc = new Hal_Rdf_Typdoc($this->_document->getTypDoc());
        $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_TYPE, null, [Hal_Rdf_Schema::RDF_RESOURCE => $typdoc->getUri($this->_document->getTypDoc())]);

        //Titre
        foreach ($this->_document->getTitle() as $lang => $title) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_TITLE, $title, [Hal_Rdf_Schema::XML_LANG => strtolower($lang)]);
        }
        //Sous titre
        foreach ($this->_document->getSubTitle() as $lang => $stitle) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_ALTERNATIVE, $stitle, [Hal_Rdf_Schema::XML_LANG => strtolower($lang)]);
        }
        //Abstract
        foreach ($this->_document->getAbstract() as $lang => $abstract) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_ABSTRACT, $abstract, [Hal_Rdf_Schema::XML_LANG => strtolower($lang)]);
        }

        $this->addMetaKeywords($node);

        //topic
        foreach ($this->_document->getDomains() as $domain) {
            $this->appendChild($node, Hal_Rdf_Schema::HAL_TOPIC, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(Hal_Rdf_Domain::$_GRAPH, $domain)]);
        }

        //language
        $lang = $this->_document->getMeta('language');
        if ($lang) {
            $this->appendChild($node, Hal_Rdf_Schema::DC_LANGUAGE, $lang);
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_LANGUAGE, null, [Hal_Rdf_Schema::RDF_RESOURCE => 'http://lexvo.org/id/iso639-1/' . $lang]);
        }

        //Projets
        foreach (['anrProject' => 'Ccsd_Referentiels_Anrproject', 'europeanProject' => 'Ccsd_Referentiels_Europeanproject'] as $projectType => $projectClass) {
            if ($projects = $this->_document->getMeta($projectType)) {
                foreach ($projects as $project) {
                    /* @var $projectClass Ccsd_Referentiels_Anrproject|Ccsd_Referentiels_Europeanproject*/
                    if ($project instanceof $projectClass) {
                        $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_SOURCE, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri($projectType, $project->getId())]);
                    }
                }
            }
        }

        //conferenceTitle
        $conferenceTitle = $this->_document->getMeta('conferenceTitle');
        if ($conferenceTitle) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_SOURCE, $conferenceTitle);
        }

        //coverage
        $city = $this->_document->getMeta('city');
        $country = $this->_document->getMeta('country');
        if ($city && $country) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_COVERAGE, $city . ', ' . Zend_Locale::getTranslation(strtoupper($country), 'country'));
        }

        //spatial
        $lat = $this->_document->getMeta('latitude');
        $lng = $this->_document->getMeta('longitude');
        if ($lat && $lng) {
            $value = '';
            if ($city && $country) {
                $value .= 'name="' . $city . ', ' . $country . '"; ';
            }
            $value .= 'north=' . $lat . '; ';
            $value .= 'east=' . $lng;

            $spatial = $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_SPATIAL, null, [], true);
            $point = $this->appendChild($spatial, Hal_Rdf_Schema::DCTERMS_POINT, null, [], true);
            $this->appendChild($point, Hal_Rdf_Schema::RDF_VALUE, $value);
        }

        $this->addMetaRevue($node);

        $this->addMetaDates($node);

        //licence
        $licence = $this->_document->getLicence();
        if ($licence) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_RIGHTS, null,[Hal_Rdf_Schema::RDF_RESOURCE => $licence]);
        }
    }

    /**
     * @param $node DOMElement
     * @throws Hal_Rdf_Exception
     */
    private function addMetaKeywords($node)
    {
        //keywords
        foreach ($this->_document->getKeywords() as $lang => $words) {
            foreach ($words as $word) {
                $this->appendChild($node, Hal_Rdf_Schema::DC_SUBJECT, $word, [Hal_Rdf_Schema::XML_LANG => strtolower($lang)]);
            }
        }

        //acm
        $values = $this->_document->getMeta('acm');
        if ($values) {
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach($values as $value) {
                $this->appendChild($node, Hal_Rdf_Schema::HAL_ACM, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Schema::getNamespaceUri(Hal_Rdf_Schema::NS_ACM) . $value]);
            }
        }

        //jel
        $values = $this->_document->getMeta('jel');
        if ($values) {
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach($values as $value) {
                $this->appendChild($node, Hal_Rdf_Schema::HAL_JEL, $value);
            }
        }
        //mesh
        $values = $this->_document->getMeta('mesh');
        if ($values) {
            if (!is_array($values)) {
                $values = [$values];
            }
            foreach($values as $value) {
                $this->appendChild($node, Hal_Rdf_Schema::HAL_MESH, $value);
            }
        }
    }

    /**
     * @param $node DOMElement
     */
    private function addDocumentAuthors($node)
    {
        foreach ($this->_document->getAuthors() as $author) {
            /* @var $author Hal_Document_Author */
            $creator = $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_CREATOR, null, [], true);
            $halAuthor = $this->appendChild($creator, $this->getAuthorClassRole($author->getQuality()), null, [], true);

            $this->appendChild($halAuthor, Hal_Rdf_Schema::HAL_PERSON, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(Hal_Rdf_Author::$_GRAPH, $author->getAuthorid())]);
            foreach ($author->getStructidx() as $structid) {
                $structure = $this->_document->getStructure($structid);
                $this->appendChild($halAuthor, Hal_Rdf_Schema::HAL_STRUCTURE, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(Hal_Rdf_Structure::$_GRAPH, $structure->getStructid())]);
            }
        }
    }

    /**
     * @param $node DOMElement
     */
    private function addDocumentFiles($node)
    {
        if ($this->_document->getFileNb()) {
            //Présence de fichiers
            foreach ($this->_document->getFiles() as $file) {
                /* @var $file Hal_Document_File */
                if ($file->getDefault()) {
                    $this->appendChild($node, Hal_Rdf_Schema::ORE_AGGREGATES, null,[Hal_Rdf_Schema::RDF_RESOURCE => $this->_document->getUri(true) . '/file/' . rawurlencode($file->getName())]);
                }
            }
        }
    }

    /**
     * @param $node DOMElement
     */
    private function addDocumentRelated($node)
    {

        $sid = $this->_document->getSid();
        $ws = Hal_Site::loadSiteFromId($sid);
        $url = $ws->getUrl();
        $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_ISPARTOF, null,[Hal_Rdf_Schema::RDF_RESOURCE => $url]);
        /**@var $collection Hal_Site_Collection */
        foreach ($this->_document->getCollections() as $collection) {
            $this->appendChild(
                $node,
                Hal_Rdf_Schema::DCTERMS_ISPARTOF,
                null,
                [Hal_Rdf_Schema::RDF_RESOURCE => $url . '/' . $collection->getShortname()]);
        }

        foreach ($this->_document->getRelated() as  $related) {
            if (!isset($related[static::RELATION])) {
                continue;
            }
            $defaultNsPrefix = Hal_Rdf_Schema::NS_DCTERMS . Hal_Rdf_Schema::NS_SEPARATOR ;
            if ($related[static::RELATION] == 'illustrate' || $related[static::RELATION] == 'isIllustratedBy') {
                //pas d'équivalence rdf dans Dcterm : remplacé par seeAlso
                $related[static::RELATION] = Hal_Rdf_Schema::RDFS_SEEALSO;
                $defaultNsPrefix = '';
            }
            $this->appendChild($node, $defaultNsPrefix . $related[static::RELATION], null,[Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(static::getGraph(), $related['IDENTIFIANT'])]);
        }

        //Récupération des références
        foreach (Hal_Document_References::getReferences($this->_document->getDocid()) as $url) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_REFERENCES, null,[Hal_Rdf_Schema::RDF_RESOURCE => $url]);
        }
        if (ENV_PROD !== APPLICATION_ENV) {
            //todo enlever ce test quand la table aura des index
            foreach (Hal_Document_References::getIsReferences($this->_document->getDocid()) as $url) {
                $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_ISREFERENCEDBY, null,[Hal_Rdf_Schema::RDF_RESOURCE => $url]);
            }
        }
    }

    /**
     * @param $node DOMElement
     */
    private function addMetaRevue($node)
    {
        //revue
        $revue = $this->_document->getMeta('journal');
        if ($revue && $revue instanceof Ccsd_Referentiels_Journal) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_ISPARTOF, null, [Hal_Rdf_Schema::RDF_RESOURCE => Hal_Rdf_Tools::createUri(Hal_Rdf_Journal::$_GRAPH, $revue->getData()['JID'])]);
        }

        //volume
        $volume = $this->_document->getMeta('volume');
        if ($volume) {
            $this->appendChild($node, Hal_Rdf_Schema::BIBO_VOLUME, $volume);
        }

        //issue
        $issue = $this->_document->getMeta('issue');
        if ($issue) {
            $this->appendChild($node, Hal_Rdf_Schema::BIBO_ISSUE, $issue);
        }

        //pages
        $page = $this->_document->getMeta('page');
        if ($page) {
            $add = true;
            if (strpos($page, '-')) {
                $pageTmp = preg_replace('/\s+/i', ' ',$page);
                $pageTmp = preg_replace('/[-]+/i', '-',$pageTmp);
                $pageTmp = preg_replace('/[a-z.#]/i', '',$pageTmp);
                $tmp = explode('-', $pageTmp);
                if (count($tmp) == 2 && strpos(trim($tmp[0]), ' ') === false && strpos(trim($tmp[1]), ' ') === false ) {
                    $this->appendChild($node, Hal_Rdf_Schema::BIBO_PAGESTART, $tmp[0]);
                    $this->appendChild($node, Hal_Rdf_Schema::BIBO_PAGEEND, $tmp[1]);
                    $add = false;
                }
            }
            if ($add) {
                $this->appendChild($node, Hal_Rdf_Schema::BIBO_PAGES, $page);
            }
        }

        //isbn
        $isbn = $this->_document->getMeta('isbn');
        if ($isbn) {
            $this->appendChild($node,  Hal_Rdf_Schema::BIBO_ISBN, $isbn);
        }
    }

    /**
     * @param $node DOMElement
     * @throws Hal_Rdf_Exception
     */
    private function addMetaDates($node)
    {
        // producedDate
        $date = $this->_document->getProducedDate();
        if (strlen($date) == 4) {
            $dataType = 'gYear';
        } else if (strlen($date) == 7) {
            $dataType = 'gYearMonth';
        } else {
            $date = substr($date, 0, 10);
            $dataType = 'date';
        }

        $uri = Hal_Rdf_Schema::getNamespaceUri(Hal_Rdf_Schema::NS_XML);

        $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_ISSUED, $date, [Hal_Rdf_Schema::RDF_DATATYPE => $uri . $dataType]);

        // submittedDate
        $date = $this->_document->getSubmittedDate();
        //todo pas top peut mieux faire
        $date = str_replace(' ', 'T',  $date);
        $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_CREATED, $date, [Hal_Rdf_Schema::RDF_DATATYPE => $uri . 'dateTime']);


        // modifiedDate
        $date = $this->_document->getLastModifiedDate();
        //todo pas top peut mieux faire
        $date = str_replace(' ', 'T',  $date);$this->appendChild($node, Hal_Rdf_Schema::DCTERMS_MODIFIED, $date,[Hal_Rdf_Schema::RDF_DATATYPE => $uri . 'dateTime']);

        // date embargo
        if ($date = $this->_document->getFirstDateVisibleFile()) {
            $this->appendChild($node, Hal_Rdf_Schema::DCTERMS_AVAILABLE, $date,[Hal_Rdf_Schema::RDF_DATATYPE => $uri . 'date']);
        }
    }

    /**
     * @param $type string
     * @return string
     */
    public function getElemRoot($type)
    {
        if (Hal_Rdf_Typdoc::existResourceType($type, Hal_Rdf_Typdoc::NS_FABIO)) {
            return Hal_Rdf_Typdoc::NS_FABIO . Hal_Rdf_Schema::NS_SEPARATOR . Hal_Rdf_Typdoc::getResourceType($type, Hal_Rdf_Typdoc::NS_FABIO);
        }
        return Hal_Rdf_Schema::NS_HAL . Hal_Rdf_Schema::NS_SEPARATOR . ucfirst(strtolower($type));
    }


    /**
     * Retourne la classe qui définit le rôle d'une personne pour un dépôt
     * @param $role
     * @param bool $withPrefix
     * @return null|string
     */
    private function getAuthorClassRole($role, $withPrefix = true)
    {
        $class = null;
        switch ($role) {
            case 'aut'  :   $class = 'Author';
                            break;
            case 'crp'  :   $class = 'CorrespondingAuthor';
                            break;
            case 'edt'  :   $class = 'Editor';
                            break;
            case 'ctb'  :   $class = 'Contributor';
                            break;
            case 'sad'  :   $class = 'ScientificAdvisor';
                            break;
            case 'pro'  :   $class = 'Producer';
                            break;
            case 'spk'  :   $class = 'Speaker';
                            break;
            default     :   $class = 'Person';
                            break;
        }
        if ($withPrefix) {
            $class = Hal_Rdf_Schema::NS_HAL . Hal_Rdf_Schema::NS_SEPARATOR . $class;
        }
        return $class;
    }

}