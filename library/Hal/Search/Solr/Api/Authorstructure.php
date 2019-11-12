<?php

class Hal_Search_Solr_Api_Authorstructure
{
    const MAX_AUTHOR_INSTANCES = 100;
    const SOLR_RESPONSE_FACETS = 'facets';
    const SOLR_RESPONSE = 'response';
    const SOLR_RESPONSE_DOCS = 'docs';


    /**
     * @var array
     */
    protected $_authors;


    /**
     * @var array
     */
    protected $_affiliations;


    /**
     * @var string
     */
    protected $_firstname;

    /**
     * @var string
     */
    protected $_lastname;

    /**
     * @var string
     */
    protected $_email;


    /**
     * @var int
     */
    protected $_producedDate;


    /**
     * @var int
     */
    protected $_deviation;


    /**
     * @var boolean
     */
    protected $_getParents;

    /**
     * @var string
     */
    protected $_queryString = '';


    public function __construct($params)
    {

        foreach (['firstName_t', 'email_s', 'getParents', 'producedDateY_i', 'deviation'] as $myParam) {
            if (!isset ($params[$myParam])) {
                $params[$myParam] = '';
            }
        }

        $this->setLastname($params ['lastName_t']);
        $this->setFirstname($params ['firstName_t']);
        $this->setEmail($params ['email_s']);
        $this->setGetParents($params ['getParents']);
        $this->setProducedDate($params ['producedDateY_i']);
        $this->setDeviation($params ['deviation']);


        $this->getAuthorsList();

        if (count($this->getAuthors()) == 0) {
            return [];
        }





        $this->addToQueryString("q=*:*&rows=0&wt=phps&omitHeader=true");
        $this->addToQueryString($this->buildQueryStringAuthorDates());
        $this->addToQueryString($this->buildQueryStringAuthorFilters());
        $this->addToQueryString($this->buildQueryStringAuthorJsonFacets());

        $this->getAffiliationList();

    }

    /**
     * get author forms from solr
     * @return array
     */
    private function getAuthorsList()
    {
        $authorsArr = [];
        $query = "q=lastName_t:" . urlencode($this->getLastname());

        if ($this->getFirstname() != '') {
            $query .= "&fq=firstName_t:" . urlencode($this->getFirstname());
        }

        if ($this->getEmail() != '') {
            $query .= "&fq=email_s:" . urlencode($this->getEmail());
        }

        $query .= '&rows=' . self::MAX_AUTHOR_INSTANCES . '&fl=docid&wt=phps';

        try {
            $resFromSolr = Ccsd_Tools::solrCurl($query, 'ref_author', 'apiselect');
            if ($resFromSolr) {
                $resultAsArray = unserialize($resFromSolr);
                if (isset ($resultAsArray [self::SOLR_RESPONSE] [self::SOLR_RESPONSE_DOCS]) && is_array($resultAsArray [self::SOLR_RESPONSE] [self::SOLR_RESPONSE_DOCS]) && count($resultAsArray [self::SOLR_RESPONSE] [self::SOLR_RESPONSE_DOCS])) {
                    $authorsArr = [];
                    foreach ($resultAsArray [self::SOLR_RESPONSE] [self::SOLR_RESPONSE_DOCS] as $author) {
                        $authorsArr[$author ['docid']] = $author ['docid'];
                    }
                }

            }

        } catch (Exception $e) {
            $authorsArr = [];
        }

        $this->setAuthors($authorsArr);
        return $authorsArr;

    }

    /**
     * @return string
     */
    public function getLastname(): string
    {
        return $this->_lastname;
    }

    /**
     * @param string $lastname
     */
    public function setLastname(string $lastname)
    {
        $this->_lastname = $lastname;
    }

    /**
     * @return string
     */
    public function getFirstname(): string
    {
        return $this->_firstname;
    }

    /**
     * @param string $firstname
     */
    public function setFirstname(string $firstname = '')
    {
        $this->_firstname = $firstname;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->_email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email = '')
    {
        $this->_email = $email;
    }

    /**
     * @return array
     */
    public function getAuthors(): array
    {
        return $this->_authors;
    }

    /**
     * @param array $authors
     */
    public function setAuthors(array $authors)
    {
        $this->_authors = $authors;
    }

    public function addToQueryString(string $string = '')
    {
        if ($string != '') {
            $this->setQueryString($this->getQueryString() . $string);
        }
    }

    /**
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->_queryString;
    }

    /**
     * @param string $queryString
     */
    public function setQueryString(string $queryString)
    {
        $this->_queryString = $queryString;
    }

    private function buildQueryStringAuthorDates(): string
    {

        $query = '';
        if ($this->getProducedDate() != '') {
            if ($this->getDeviation() != '') {
                $query = '&fq=producedDateY_i:' . urlencode('[' . ($this->getProducedDate() - $this->getDeviation()) . ' TO ' . ($this->getProducedDate() + $this->getDeviation()) . ']');
            } else {
                $query = '&fq=producedDateY_i:' . urlencode($this->getProducedDate());
            }
        }

        return $query;
    }

    /**
     * @return int
     */
    public function getProducedDate(): int
    {
        return $this->_producedDate;
    }

    /**
     * @param int $producedDate
     */
    public function setProducedDate($producedDate)
    {
        $this->_producedDate = (int)$producedDate;
    }

    /**
     * @return int
     */
    public function getDeviation(): int
    {
        return $this->_deviation;
    }

    /**
     * @param int $deviation
     */
    public function setDeviation(string $deviation)
    {
        $this->_deviation = (int)$deviation;
    }

    private function buildQueryStringAuthorFilters()
    {
        $authorFilters = '';
        $authorList = implode(' OR ', $this->getAuthors());

        if ($authorList != '') {
            $authorFilters = '&fq=authId_i:(' . urlencode($authorList) . ')';
        }

        return $authorFilters;

    }

    /**
     * @return string
     */
    public function buildQueryStringAuthorJsonFacets(): string
    {

        $facetQuery = '';
        $bucket = [];

        foreach ($this->getAuthors() as $authorId) {
            $authorFacetPrefix = $authorId . Ccsd_Search_Solr::SOLR_FACET_SEPARATOR;
            $bucket[$authorId] = '"' . $authorId . '":' . json_encode(['numBuckets' => true, 'type' => 'terms', 'field' => 'authIdHasPrimaryStructure_fs', 'prefix' => $authorFacetPrefix]);
        }

        $jsonQuery = implode(',', $bucket);

        if ($jsonQuery != '') {
            $facetQuery = '&facet=true&json.facet={' . urlencode($jsonQuery) . '}';
        }

        return $facetQuery;
    }

    /**
     * get affiliations list from solr
     * @return array of affiliation docids
     * @throws Exception
     */
    private function getAffiliationList()
    {
        $affiliationArr = [];

        try {
            $serializedResult = Ccsd_Tools::solrCurl($this->getQueryString());
            if ($serializedResult) {
                $affiliation = unserialize($serializedResult);
            }
        } catch (Exception $e) {
            $this->setAffiliations($affiliationArr);
            return $affiliationArr;
        }

        if (isset ($affiliation [self::SOLR_RESPONSE_FACETS]) && is_array($affiliation [self::SOLR_RESPONSE_FACETS]) && count($affiliation [self::SOLR_RESPONSE_FACETS])) {
            foreach ($affiliation[self::SOLR_RESPONSE_FACETS] as $facetBucket) {
                if ($facetBucket['numBuckets'] == 0) {
                    continue; // no results here
                }
                foreach ($facetBucket as $keyOfArray => $structure) {
                    if ($keyOfArray != 'buckets') {
                        continue; // only buckets are useful
                    }
                    // ok merge all buckets
                    $affiliationArr = array_merge($affiliationArr, array_column($structure, 'val'));
                }
            }
        }

        $this->setAffiliations($affiliationArr);
        return $affiliationArr;

    }

    /**
     * echo author Affiliation List
     * @param string $format
     * @throws Zend_Json_Exception
     */
    public function outputStructuresList($format = 'xml')
    {

        $xmlList = $this->getAffiliationListAsXML();

        if ($format == 'xml') {
            header('Content-Type: text/xml; charset=utf-8');
            echo $xmlList;
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo Zend_Json::fromXml($xmlList);
        }

    }

    /**
     * get structures as xml
     * @return string
     */
    public function getAffiliationListAsXML(): string
    {
        $docidArr = $this->getAffiliationsIdFromSolrData();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= "<response>\n";
        $xml .= '<result name="response" numFound="' . count($docidArr) . '" start="0">' . "\n";

        foreach ($docidArr as $docid) {
            $xml .= (new Ccsd_Referentiels_Structure ($docid, '', $this->isGetParents()))->getXML(false);
        }

        $xml .= "</result>\n";
        return $xml . "</response>";
    }

    /**
     * get deduplicated list of structure id from solr data
     * @return array
     */
    private function getAffiliationsIdFromSolrData(): array
    {

        $structIds = [];

        foreach ($this->getAffiliations() as $affiliation) {
            $dataAffiliation = explode(Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR, $affiliation);

            if (!isset ($dataAffiliation [1])) {
                continue;
            }

            $dataStructure = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $dataAffiliation [1]);

            if (count($dataStructure) == 2 && !array_key_exists($dataStructure [0], $structIds)) {
                $structIds [$dataStructure [0]] = $dataStructure [0];
            }
        }

        return $structIds;
    }

    /**
     * @return array
     */
    public function getAffiliations(): array
    {
        return (array) $this->_affiliations;
    }

    /**
     * @param array $affiliations
     */
    public function setAffiliations(array $affiliations = [])
    {
        $this->_affiliations = $affiliations;
    }

    /**
     * @return bool
     */
    public function isGetParents(): bool
    {
        return $this->_getParents;
    }

    /**
     * @param string $getParents
     */
    public function setGetParents($getParents = 'true')
    {
        switch ($getParents) {
            case 'false':
                $getParents = false;
                break;
            case 'true':
                $getParents = true;
                break;
            default:
                $getParents = true;
                break;
        }

        $this->_getParents = $getParents;
    }


}