<?php

/**
 * Solr Search: helper methods
 */
class Hal_Search_Solr_Search extends Ccsd_Search_Solr_Search
{

    /**
     * Retourne les filtres solr sous forme d'URL
     * @param array $defaultFilters tableau de filtres à transformer en URL
     * @param boolean $replace true == force status_i:(Hal_Document::STATUS_VISIBLE OR Hal_Document::STATUS_REPLACED)
     * @return string les filtres solr sous forme d'URL
     */
    static function getDefaultFiltersAsURL($defaultFilters = null, $replace = false)
    {
        $filterQuery = parent::getDefaultFiltersAsURL($defaultFilters);
        if ($filterQuery == null) {
            $filterQuery = '';
        }
        if (defined('MODULE') && defined('SPACE_COLLECTION') && defined('SPACE_NAME') && MODULE == SPACE_COLLECTION) { // cas d'une collection
            $filterQuery .= '&fq=' . 'collCode_s:' . strtoupper(SPACE_NAME);
        } else { // pour les portails on ne montre que status_i:11
            if ($replace) {
                $filterQuery .= '&fq=' . urlencode('status_i:(' . Hal_Document::STATUS_VISIBLE . ' OR ' . Hal_Document::STATUS_REPLACED . ')');
            } else {
                $filterQuery .= '&fq=' . urlencode('status_i:' . Hal_Document::STATUS_VISIBLE);
            }
        }
        return $filterQuery;
    }

    /**
     * Liste des filtres de recherche utilisés dans une requête
     * Pour chaque filtre = param créer une url avec tous les filtres moins un à
     * supprimer
     * filtre
     *
     * @param array $activeFilters
     * @param array $parsedSearchParams
     * @return array URL du filtre
     */
    static function buildActiveFiltersUrl($activeFilters, $parsedSearchParams)
    {
        $url = [];

        $parsedSearchParamsOriginal = $parsedSearchParams;
        $noFilters = $parsedSearchParams;

        if (!is_array($activeFilters)) {
            return null;
        }

        foreach (array_keys($activeFilters) as $filterName) {
            $parsedSearchParams = $parsedSearchParamsOriginal;
            unset($parsedSearchParams [$filterName]);
            $url [$filterName] = $parsedSearchParams;

            unset($noFilters [$filterName]);
        }
        $url ['Tous'] = $noFilters;

        return $url;
    }

    /**
     * Recupère une valeur de facette par le nom du champ, eventuellement avec
     * un prefixe pour la valeur
     *
     * @param string $fieldName
     * @param string $prefix
     * @param string $sortType
     * @param string $typeFilter
     * @return NULL|unknown
     */
    static function getFacetField($fieldName, $prefix = null, $sortType = 'index', $typeFilter = null)
    {


        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=false&facet=true&omitHeader=true&facet.limit=' . Ccsd_Search_Solr::SOLR_MAX_RETURNED_FACETS_RESULTS . '&facet.mincount=1&facet.field={!key=Meta}';

        $baseQueryString .= urlencode($fieldName);
        if ($prefix != null && $prefix != 'all') {
            $baseQueryString .= '&facet.prefix=' . urlencode($prefix);
        }

        $userFilterQuery = self::getUserFilterType($typeFilter);
        $userSortType = self::getUserFacetSortType($sortType);

        $baseQueryString .= '&facet.sort=' . $userSortType;

        if ($userFilterQuery != null) {
            $baseQueryString .= '&fq=(' . $userFilterQuery . ')';
        }

        try {
            $solrResponse = Hal_Tools::solrCurl($baseQueryString, 'hal', 'select', true);
            $solrResponse = unserialize($solrResponse);
        } catch (Exception $e) {
            return null;
        }

        if ($solrResponse ['facet_counts'] == 0) {
            return null;
        }

        return $solrResponse ['facet_counts'] ['facet_fields'] ['Meta'];

    }

    /**
     * Filtre le submitType demandé par l'utilisateur
     *
     * @param string $typeFilter
     * @return NULL|string submitType filter urlencoded
     */
    static function getUserFilterType($typeFilter = null)
    {
        if ($typeFilter == null) {
            return null;
        }

        $userFilterQueryArr = null;

        $typeFilter = explode(' OR ', $typeFilter);
        $typeFilter = array_unique($typeFilter);

        foreach ($typeFilter as $filtre) {
            switch ($filtre) {
                case Hal_Document::FORMAT_FILE :
                case Hal_Document::FORMAT_NOTICE :
                case Hal_Document::FORMAT_ANNEX :
                    $userFilterQueryArr [] = 'submitType_s:' . $filtre;
                    break;
                default :
                    // nada
                    break;
            }
        }

        if (is_array($userFilterQueryArr)) {
            $userFilterQuery = implode(' OR ', $userFilterQueryArr);
            return urlencode($userFilterQuery);
        } else {
            return null;
        }
    }

    /**
     * Filtre le type de tri de facette demandé par l'utilisateur
     * @param string $sortType
     * @return string
     */
    static function getUserFacetSortType($sortType = 'index')
    {
        switch ($sortType) {
            case 'count' :
            case 'index' :
                break;
            default :
                $sortType = 'index';
                break;
        }
        return $sortType;
    }

    /**
     * Retourne un résultat de facette sur un champ solr
     * @param string $facetFieldName
     * @param string $facetPrefix
     * @param string $typeFilter
     * @param string $sortType type de tri des facettes
     * @return array
     */
    static function getFacet($facetFieldName = '', $facetPrefix = 'all', $typeFilter = null, $sortType = 'index'): array
    {
        if ($facetFieldName == '') {
            return [];
        }

        $list = null;

        $userFilterQuery = self::getUserFilterType($typeFilter);
        $userSortType = self::getUserFacetSortType($sortType);

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=false&facet=true&omitHeader=true&facet.limit=' . Ccsd_Search_Solr::SOLR_MAX_RETURNED_FACETS_RESULTS . '&facet.mincount=1&facet.field={!key=list}' . urlencode($facetFieldName);

        // un champ dont les chaines sont à retraiter et qui peut commencer par $facetPrefix . parent::SOLR_ALPHA_SEPARATOR
        $fieldWithSep = self::isFieldWithSeparator($facetFieldName);

        if ($facetPrefix != 'all') {
            $baseQueryString .= '&facet.prefix=' . $facetPrefix;
            if ($fieldWithSep == true) {
                $baseQueryString .= parent::SOLR_ALPHA_SEPARATOR;
            }
        }

        $baseQueryString .= '&facet.sort=' . $userSortType;

        if ($userFilterQuery != null) {
            $baseQueryString .= '&fq=(' . $userFilterQuery . ')';
        }

        try {

            $solrResponse = Hal_Tools::solrCurl($baseQueryString, 'hal', 'select', true);
            $solrResponse = unserialize($solrResponse);
        } catch (Exception $e) {
            if (APPLICATION_ENV == ENV_DEV) {
                echo $e->getMessage();
            }
            return [];
        }

        if (empty($solrResponse ['facet_counts'] ['facet_fields'] ['list'])) {

            return [];
        }

        $list = $solrResponse ['facet_counts'] ['facet_fields'] ['list'];

        if (!is_array($list)) {
            return [];
        }

        $arrayPatternToRemove = [
            '/[A-Z]' . parent::SOLR_ALPHA_SEPARATOR . '/',
            '/^other' . parent::SOLR_ALPHA_SEPARATOR . '/'
        ];

        $nameList = [];
        $arrIndex = '';
        foreach ($list as $value => $count) {

            $name = preg_replace($arrayPatternToRemove, '', $value);

            $nameArr = explode(parent::SOLR_FACET_SEPARATOR, $name);

            $name = $nameArr [0];

            if (isset($nameArr [1]) && ($nameArr [1] != '')) {
                $arrIndex = $nameArr [1];
                $nameList [$arrIndex] ['idHal'] = $nameArr [1];
            } else {
                $arrIndex = $name;
            }

            // si la valeur existe déjà on dédoublonne et on incréminte le count
            if (array_key_exists($arrIndex, $nameList)) {
                if (isset($nameList [$arrIndex] ['count'])) {
                    $nameList [$arrIndex] ['count'] = $nameList [$arrIndex] ['count'] + $count;
                } else {
                    $nameList [$arrIndex] ['count'] = $count;
                }
            } else {
                $nameList [$arrIndex] ['count'] = $count;
            }
            $nameList [$arrIndex] ['name'] = $name;
        }

        return $nameList;
    }

    /**
     *
     * @param string $facetFieldName
     * @return boolean
     */
    static function isFieldWithSeparator($facetFieldName)
    {
        // un champ dont les chaines sont à retraiter et qui peut commencer par $facetPrefix . parent::SOLR_ALPHA_SEPARATOR
        if (substr($facetFieldName, -3, 3) == '_fs') {
            return true;
        }
        return false;
    }

    /**
     * Construit l'URL des facettes
     *
     * @param string $facetQueryFieldName nom de champ solr à utiliser pour la requête générée
     * @param array $parsedSearchParams les paramètres de recherche dans l'url
     * @param string $facetValueCode la valeur à chercher
     * @param int $valueCount
     * @return array
     */
    static function buildFacetUrl($facetQueryFieldName, $parsedSearchParams, $facetValueCode, $valueCount)
    {
        $urlParams ['checked'] = false;
        // le champs de facette est déjà utilisé dans la recherche, on
        // ajoute une valeur

        $arrayOfFacetParams = [];

        // la facette est utilisée dans les filtres
        if (array_key_exists($facetQueryFieldName, $parsedSearchParams)) {

            $arrayOfFacetParams = explode(' OR ', $parsedSearchParams [$facetQueryFieldName]);

            $facetUrlParams [$facetQueryFieldName] = $parsedSearchParams [$facetQueryFieldName];

            // la valeur de cette facette est utilisée comme filtre
            if (in_array($facetValueCode, $arrayOfFacetParams)) {

                // on coche la case
                $urlParams ['checked'] = true;

                // on enlève la valeur de la facette au tableau des
                // filtres en
                // cours
                $arrayWithoutCheckedFacet = array_diff($arrayOfFacetParams, [
                    $facetValueCode
                ]);

                if (!empty($arrayWithoutCheckedFacet)) {

                    $facetUrlParams [$facetQueryFieldName] = implode(' OR ', $arrayWithoutCheckedFacet);

                    $urlParams ['url'] = array_merge($parsedSearchParams, $arrayWithoutCheckedFacet);
                } else {
                    unset($parsedSearchParams [$facetQueryFieldName]);
                    $urlParams ['url'] = $parsedSearchParams;
                    return $urlParams;
                }
            } else {
                // valeur non utilisée on concatène à une valeur
                // existante
                $facetUrlParams [$facetQueryFieldName] .= ' OR ' . $facetValueCode;
            }
        } else {
            // facette non utilisée dans les filtres
            $facetUrlParams [$facetQueryFieldName] = $facetValueCode;
        }

        if (is_array($facetUrlParams)) {
            $urlParams ['url'] = array_merge($parsedSearchParams, $facetUrlParams);
            return $urlParams;
        } else {
            $urlParams ['url'] = $parsedSearchParams;
            return $urlParams;
        }
    }


    /**
     * Initialise les endpoints utilisés par solarium
     * @return array|Ccsd_Search_Solr|mixed
     * @throws Ccsd_FileNotFoundException
     * @throws Zend_Config_Exception
     */
    static function initSolrEndpoints()
    {
        try {
            $solrConf = Zend_Registry::get('solrEndpoints');
            return $solrConf;
        } catch (Exception $e) {

            $solrConf = new Ccsd_Search_Solr([
                'env' => APPLICATION_ENV,
                'core' => 'hal'
            ]);
            Zend_Registry::set('solrEndpoints', $solrConf->getEndpoints());

            return $solrConf->getEndpoints();
        }
    }

    /**
     * Formate un message d'erreur comme s'il venait de solr
     * @param string $error
     * @param string $contentType
     * @param boolean $header
     * @return string
     */
    static function formatErrorAsSolr($error, $contentType, $header = false)
    {
        if ($contentType == 'xml') {
            $headerString = 'Content-Type: text/xml; charset=utf-8';
            $message = '<response><lst name="error"><str name="msg">' . Ccsd_Tools_String::xmlSafe($error) . '</str></lst></response>';
        } else {
            $headerString = 'Content-Type: application/json; charset=utf-8';
            $message = json_encode(['error' => ['message' => $error]]);
        }
        if ($header) {
            header($headerString);
        }
        return $message;
    }

    /**
     * Retourne le formulaire de recherche avancée
     * @return Ccsd_Form Formulaire de recherche avancée
     * @throws Zend_Form_Exception
     */
    static function getFormSearchAdvanced()
    {
        try {
            $fields = Hal_Settings::getConfigFile('solr.hal.AdvSearchFields.json');
        } catch (Exception $e) {
            if (APPLICATION_ENV == ENV_DEV) {
                die($e->getMessage());
            }
            $fields = null;
        }

        if ($fields == null) {
            $fields = [
                'text' => 'hal_text'
            ];
        }

        $f = new Ccsd_Form ();
        $f->setAttrib('class', 'form-horizontal');
        $f->setAttrib('style', 'margin-right:20px');

        $f->setAttrib('id', 'search-advanced-form');

        $f->setMethod('get');

        $f->addElement('multiTextSimpleLang', 'qa', [
            'required' => false,
            'populate' => $fields,
            'pluriValues' => true,
            'class' => 'inputlangmulti'
        ]);

        $f->getElement('qa')->addValidator(new Zend_Validate_StringLength([
            'min' => 1,
            'max' => 200
        ]));

        $decorators = $f->getElement('qa')->getDecorator('Group')->getDecorators();

        $decorators [3] ['options'] ['ul_style'] = 'max-height: 300px; overflow:auto;';

        $f->getElement('qa')->getDecorator('Group')->setDecorators($decorators);

        // ->addFilter('StripTags')
        // ->addFilter('StringTrim');

        $submit = new Zend_Form_Element_Submit('submit_advanced');

        $submit->setOptions([
            'label' => 'Rechercher',
            'value' => 'submit_advanced',
            'class' => 'btn btn-primary'
        ]);

        $submit->addDecorator('HtmlTag', [
            'tag' => 'div',
            'class' => 'pull-right'
        ]);

        $f->addElement($submit);

        $f->getElement('qa')->getDecorator('HtmlTag')->setOptions([
            'tag' => 'div'
        ]);

        return $f;
    }


}
