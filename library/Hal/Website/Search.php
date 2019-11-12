<?php

/**
 * Class Hal_Website_Search
 */
class Hal_Website_Search
{

    const CHECKED_FILTER_FILE = 'solr.hal.checkedFilters.json';
    const CHECKED_FILTER_DT = 'filter_dt';
    const CHECKED_FILTER_ST = 'filter_st';
    const FACET_FIELD_SUBMIT_TYPE = 'submitType_s';
    const FACET_FIELD_DOC_TYPE = 'docType_s';
    const SOLR_DEFAULT_QUERY_FILTERS = 'solr.hal.defaultFilters.json';
    const SOLR_FACET_COUNTS = 'facet_counts';
    const SOLR_FACET_FIELDS = 'facet_fields';

    /**
     * @var array
     */
    protected $_doctypeChecked = [];

    /**
     * @var array
     */
    protected $doctypeFacet = [];


    /**
     * @var array
     */
    protected $_submitTypeFacet = [];

    /**
     * @var array
     */
    protected $_submittypeChecked = [];


    /**
     * Hal_Website_Search constructor.
     * @param array|null $doctype : allow an empty array to remove all filters
     * @param array|null $submittype : allow an empty array to remove all filters
     */
    public function __construct($doctype = null, $submittype = null)
    {

        $facets = self::getFacets();

        $this->setDoctypeFacet($facets[self::FACET_FIELD_DOC_TYPE]);
        $this->setSubmitTypeFacet($facets[self::FACET_FIELD_SUBMIT_TYPE]);


        if (!is_array($doctype) && !is_array($submittype)) {
            $this->load();
            return;
        }

        $this->setDoctypeChecked($doctype);
        $this->setSubmittypeChecked($submittype);
    }


    /**
     * Get facets of doc types + submit types
     * @return array
     */
    private static function getFacets()
    {

        $results [self::FACET_FIELD_SUBMIT_TYPE] = [];
        $results [self::FACET_FIELD_DOC_TYPE] = [];

        $queryString = 'q=*:*&rows=0&wt=phps&facet=true&facet.field=' . self::FACET_FIELD_DOC_TYPE . '&facet.field=' . self::FACET_FIELD_SUBMIT_TYPE . '&facet.mincount=1&omitHeader=true';

        $defaultFilterQuery = Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile(self::SOLR_DEFAULT_QUERY_FILTERS));

        if ($defaultFilterQuery != null) {
            $queryString .= $defaultFilterQuery;
        }

        try {
            $solrResponse = Ccsd_Tools::solrCurl($queryString, 'hal');
            $solrResponse = unserialize($solrResponse);
        } catch (Exception $e) {
            $solrResponse = [];
        }


        if ((!isset($solrResponse [self::SOLR_FACET_COUNTS])) || ($solrResponse [self::SOLR_FACET_COUNTS] == 0)) {
            return $results;
        }

        if (isset($solrResponse [self::SOLR_FACET_COUNTS] [self::SOLR_FACET_FIELDS] [self::FACET_FIELD_SUBMIT_TYPE])) {
            $results[self::FACET_FIELD_SUBMIT_TYPE] = $solrResponse [self::SOLR_FACET_COUNTS] [self::SOLR_FACET_FIELDS] [self::FACET_FIELD_SUBMIT_TYPE];
        }

        if (isset($solrResponse [self::SOLR_FACET_COUNTS] [self::SOLR_FACET_FIELDS] [self::FACET_FIELD_DOC_TYPE])) {
            $results[self::FACET_FIELD_DOC_TYPE] = $solrResponse [self::SOLR_FACET_COUNTS] [self::SOLR_FACET_FIELDS] [self::FACET_FIELD_DOC_TYPE];
        }

        return $results;


    }

    /**
     *
     */
    public function load()
    {
        $defaultFilters = Hal_Settings::getConfigFile(self::CHECKED_FILTER_FILE);

        if (is_array($defaultFilters)) {
            if (isset($defaultFilters [self::CHECKED_FILTER_DT])) {
                $this->setDoctypeChecked($defaultFilters [self::CHECKED_FILTER_DT]);
            }
            if (isset($defaultFilters [self::CHECKED_FILTER_ST])) {
                $this->setSubmittypeChecked($defaultFilters [self::CHECKED_FILTER_ST]);
            }
        }


    }

    /**
     * Copie de la configuration concernant les filtres de recherche
     */
    static public function duplicate(Hal_Site $model, Hal_Site $receiver)
    {
        $source = $model->getRootPath() . CONFIG . self::CHECKED_FILTER_FILE;
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . CONFIG . self::CHECKED_FILTER_FILE;
            copy($source, $dest);
        }

        $source = $model->getRootPath() . CONFIG . self::SOLR_DEFAULT_QUERY_FILTERS;
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . CONFIG . self::SOLR_DEFAULT_QUERY_FILTERS;
            copy($source, $dest);
        }
    }

    /**
     * Save Search form with checked doc types + checked submit types
     * @return bool
     */
    public function save()
    {
        $content = json_encode([self::CHECKED_FILTER_DT => $this->getDoctypeChecked(), self::CHECKED_FILTER_ST => $this->getSubmittypeChecked()]);

        if (!is_dir(SPACE . CONFIG)) {
            mkdir(SPACE . CONFIG);
        }

        $configFile = SPACE . CONFIG . self::CHECKED_FILTER_FILE;

        $saveResult = file_put_contents($configFile, $content);

        if ($saveResult) {
            return true;
        }

        return false;

    }

    /**
     * @return array
     */
    public function getDoctypeChecked(): array
    {
        return $this->_doctypeChecked;
    }

    /**
     * @param array|string $doctype
     */
    public function setDoctypeChecked($doctype = [])
    {
        if (is_string($doctype)) {
            $doctype = explode(',', $doctype);
        }

        $this->_doctypeChecked = $doctype;
    }

    /**
     * @return array
     */
    public function getSubmittypeChecked(): array
    {
        return $this->_submittypeChecked;
    }

    /**
     * @param array|string $submittype
     */
    public function setSubmittypeChecked($submittype = [])
    {
        if (is_string($submittype)) {
            $submittype = explode(',', $submittype);
        }

        $this->_submittypeChecked = $submittype;
    }

    /**
     * @return array
     */
    public function getDoctypeFacet(): array
    {
        return $this->doctypeFacet;
    }

    /**
     * @param array $doctypeFacet
     */
    public function setDoctypeFacet(array $doctypeFacet)
    {
        $this->doctypeFacet = $doctypeFacet;
    }

    /**
     * @return array
     */
    public function getSubmitTypeFacet(): array
    {
        return $this->_submitTypeFacet;
    }

    /**
     * @param array $submitTypeFacet
     */
    public function setSubmitTypeFacet(array $submitTypeFacet)
    {
        $this->_submitTypeFacet = $submitTypeFacet;
    }


}