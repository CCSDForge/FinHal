<?php

class Hal_Search_Solr_Search_Collection extends Hal_Search_Solr_Search
{
    const SOLR_FIELD_COLL_NAME_CODE = 'collNameCode_fs';
    const SOLR_FIELD_COLL_IS_PARENT_OF_COLL = 'collIsParentOfColl_fs';
    const SOLR_FIELD_COLL_IS_PARENT_OF_CATEGORY_COLL = 'collIsParentOfCategoryColl_fs';
    const SOLR_FIELD_COLL_CATEGORY_CODE_NAME = 'collCategoryCodeName_fs';
    const MAX_NB_COLLECTIONS_BY_CATEGORY = 3000;

    /**
     * Liste de collections
     * @param string $typeOfColl
     * @param null $filter
     * @param null $typeFilter
     * @param null $sortType
     * @return array
     */
    public static function getCollections($typeOfColl = 'collection', $filter = null, $typeFilter = null, $sortType = null)
    {

        $coll = [];
        $collectionList = [];

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=false&facet=true&omitHeader=true&facet.limit=' . Ccsd_Search_Solr::SOLR_MAX_RETURNED_FACETS_RESULTS . '&facet.mincount=1&facet.field={!key=CollectionList}';

        $facetFieldName = self::getFacetFieldName($typeOfColl, $filter);

        if ($typeOfColl == Hal_Website_Navigation_Page_Collections::BROWSE_TYPE_COLLECTION_BY_CATEGORY) {
            $collectionList = self::getCollectionsByCategory($filter, $typeFilter, $sortType);
        }

        if (empty($collectionList)) {
            $baseQueryString .= $facetFieldName;
            if ($filter != null) {
                $baseQueryString .= '&facet.prefix=' . urlencode($filter [0]) . Ccsd_Search_Solr::SOLR_FACET_SEPARATOR;
            }

            $userFilterQuery = parent::getUserFilterType($typeFilter);
            $userSortType = parent::getUserFacetSortType($sortType);

            $baseQueryString .= '&facet.sort=' . $userSortType;

            if ($userFilterQuery != null) {
                $baseQueryString .= '&fq=(' . $userFilterQuery . ')';
            }

            try {
                $solrResponse = Hal_Tools::solrCurl($baseQueryString, 'hal', 'select', true);
                $solrResponse = unserialize($solrResponse);
            } catch (Exception $e) {
                return [];
            }

            if (!array_key_exists('facet_counts', $solrResponse) || $solrResponse ['facet_counts'] == 0) {
                return [];
            }

            $collectionList = $solrResponse ['facet_counts'] ['facet_fields'] ['CollectionList'];
        }

        // Masque les collections invisibles
        $hiddenCollections = Hal_Site_Settings_Collection::getNonVisibleCollections();

        if ($facetFieldName == self::SOLR_FIELD_COLL_NAME_CODE) {
            $coll = self::getCollNameCode($collectionList, $hiddenCollections);
        }

        // UGA_FacetSep_HAL Grenoble Alpes_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
        if ($facetFieldName == self::SOLR_FIELD_COLL_IS_PARENT_OF_COLL) {
            $coll = self::getCollIsParentOfColl($collectionList, $hiddenCollections);
        }
        // UGA_FacetSep_UNIV_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
        if ($facetFieldName == self::SOLR_FIELD_COLL_IS_PARENT_OF_CATEGORY_COLL) {
            $coll = self::getCollIsParentOfCategoryColl($typeOfColl, $collectionList, $hiddenCollections);
        }

        // INSTITUTION_JoinSep_CNRS_FacetSep_CNRS - Centre national de la recherche scientifique
        if ($facetFieldName == self::SOLR_FIELD_COLL_CATEGORY_CODE_NAME) {
            $coll = self::getCollCategoryCodeName($typeOfColl, $collectionList, $hiddenCollections);
        }

        return $coll;
    }

    /**
     * @param $typeOfColl
     * @param $filter
     * @return string
     */
    private static function getFacetFieldName($typeOfColl, $filter): string
    {


        switch ($typeOfColl) {
            case '': //break omitted
            case Hal_Website_Navigation_Page_Collections::BROWSE_TYPE_COLLECTION :
                // Liste de collections
                if ($filter != null) {
                    $facetFieldName = self::SOLR_FIELD_COLL_IS_PARENT_OF_COLL; // UGA_FacetSep_HAL Grenoble Alpes_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
                } else {
                    $facetFieldName = self::SOLR_FIELD_COLL_NAME_CODE; // CNRS - Centre national de la recherche scientifique_FacetSep_CNRS
                }
                break;

            case Hal_Website_Navigation_Page_Collections::BROWSE_TYPE_COLLECTION_WITH_CATEGORY : //break omitted
                // Liste de collections + affichage de la catégorie
            case Hal_Website_Navigation_Page_Collections::BROWSE_TYPE_COLLECTION_BY_CATEGORY :
                // Liste de collections, réparties par catégories

                if ($filter != null) {
                    $facetFieldName = self::SOLR_FIELD_COLL_IS_PARENT_OF_CATEGORY_COLL; // UGA_FacetSep_UNIV_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
                } else {
                    $facetFieldName = self::SOLR_FIELD_COLL_CATEGORY_CODE_NAME; // INSTITUTION_JoinSep_CNRS_FacetSep_CNRS - Centre national de la recherche scientifique
                }
                break;

            default:
                // On traite comme si la valeur est collection MAIS PAS NORMAL (exemple: collections!!!)
                Ccsd_Tools::panicMsg(__FILE__, __DIR__, "typeOfColl variable a bad value: $typeOfColl");
                if ($filter != null) {
                    $facetFieldName = self::SOLR_FIELD_COLL_IS_PARENT_OF_COLL; // UGA_FacetSep_HAL Grenoble Alpes_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
                } else {
                    $facetFieldName = self::SOLR_FIELD_COLL_NAME_CODE; // CNRS - Centre national de la recherche scientifique_FacetSep_CNRS
                }
        }
        return $facetFieldName;
    }

    /**
     * Liste de collections réparties selon leur catégorie
     *
     * @param string $filter
     * @param string $typeFilter
     * @param string $sortType
     * @return NULL|multitype:
     */
    public static function getCollectionsByCategory($filter = null, $typeFilter = null, $sortType = null)
    {

        $cat = array_values(Hal_Site_Collection::getCategories());

        $cat = str_replace('type_', '', $cat);
        $cat = array_map('strtoupper', $cat);

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=false&facet=true&omitHeader=true&facet.limit=' . self::MAX_NB_COLLECTIONS_BY_CATEGORY . '&facet.mincount=1';

        $userFilterQuery = parent::getUserFilterType($typeFilter);
        $userSortType = parent::getUserFacetSortType($sortType);

        $baseQueryString .= '&facet.sort=' . $userSortType;

        if ($userFilterQuery != null) {
            $baseQueryString .= '&fq=(' . $userFilterQuery . ')';
        }

        if ($filter != null) {
            $facetField = self::SOLR_FIELD_COLL_IS_PARENT_OF_CATEGORY_COLL; // UGA_FacetSep_UNIV_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
        } else {
            $facetField = self::SOLR_FIELD_COLL_CATEGORY_CODE_NAME; // INSTITUTION_JoinSep_CNRS_FacetSep_CNRS - Centre national de la recherche scientifique
        }

        $baseQueryString .= '&facet.field={!key=CollectionList}' . $facetField;

        $results = [];

        foreach ($cat as $catOfColl) {

            if ($filter != null) {
                $filterPrefix = '&facet.prefix=' . urlencode($filter [0]) . Ccsd_Search_Solr::SOLR_FACET_SEPARATOR . $catOfColl . Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR;
            } else {
                $filterPrefix = '&facet.prefix=' . $catOfColl . Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR;
            }

            try {
                $r = Hal_Tools::solrCurl($baseQueryString . $filterPrefix, 'hal', 'select', true);
            } catch (Exception $e) {
                return null;
            }

            $r = unserialize($r);

            $results += $r ['facet_counts'] ['facet_fields'] ['CollectionList'];
        }

        return $results;
    }

    /**
     * @param array $collectionList
     * @param array $hiddenCollections
     * @param array $coll
     * @return array
     */
    private static function getCollNameCode(array $collectionList, array $hiddenCollections): array
    {
        $coll = [];
        foreach ($collectionList as $collection => $count) {
            $collArray = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $collection);
            list ($label, $code) = $collArray;

            if (in_array($code, $hiddenCollections)) {
                continue;
            }

            $coll [$code] ['label'] = $label;
            $coll [$code] ['count'] = $count;
        }
        return $coll;
    }

    /**
     * @param array $collectionList
     * @param array $hiddenCollections
     * @param array $coll
     * @return array
     */
    private static function getCollIsParentOfColl(array $collectionList, array $hiddenCollections): array
    {
        $coll = [];
        foreach ($collectionList as $collection => $count) {
            $collArray = explode(Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR, $collection);
            $childCollection = $collArray [1];
            $subCollArray = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $childCollection);
            list ($code, $label) = $subCollArray;

            if (in_array($code, $hiddenCollections)) {
                continue;
            }

            $coll [$code] ['label'] = $label;
            $coll [$code] ['count'] = $count;
        }
        return $coll;
    }

    /**
     * @param $typeOfColl
     * @param array $collectionList
     * @param array $hiddenCollections
     * @param array $coll
     * @return array
     */
    private static function getCollIsParentOfCategoryColl($typeOfColl, array $collectionList, array $hiddenCollections): array
    {
        $coll = [];
        foreach ($collectionList as $collection => $count) {
            $collArray = explode(Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR, $collection);
            $categoryCollection = $collArray [0];
            $childCollection = $collArray [1];
            $subCollArray = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $childCollection);
            list ($code, $label) = $subCollArray;

            if (in_array($code, $hiddenCollections)) {
                continue;
            }

            $subCollCategoryArray = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $categoryCollection);
            $category = $subCollCategoryArray [1];

            // Répartition par catégories
            if ($typeOfColl == Hal_Website_Navigation_Page_Collections::BROWSE_TYPE_COLLECTION_BY_CATEGORY) {
                $coll [$category] [$code] ['label'] = $label;
                $coll [$category] [$code] ['count'] = $count;
            }
            // Affichage avec catégorie
            if ($typeOfColl == Hal_Website_Navigation_Page_Collections::BROWSE_TYPE_COLLECTION_WITH_CATEGORY) {
                $coll [$code] ['category'] = $category;
                $coll [$code] ['label'] = $label;
                $coll [$code] ['count'] = $count;
            }
        }
        return $coll;
    }

    /**
     * @param $typeOfColl
     * @param array $collectionList
     * @param array $hiddenCollections
     * @param array $coll
     * @return array
     */
    private static function getCollCategoryCodeName($typeOfColl, array $collectionList, array $hiddenCollections): array
    {
        $coll = [];
        foreach ($collectionList as $collection => $count) {
            $collArray = explode(Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR, $collection);
            $category = $collArray [0];
            $childCollection = $collArray [1];
            $subCollArray = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $childCollection);
            list ($code, $label) = $subCollArray;

            if (in_array($code, $hiddenCollections)) {
                continue;
            }

            // Répartition par catégories
            if ($typeOfColl == Hal_Website_Navigation_Page_Collections::BROWSE_TYPE_COLLECTION_BY_CATEGORY) {
                $coll [$category] [$code] ['label'] = $label;
                $coll [$category] [$code] ['count'] = $count;
            }
            // Affichage avec catégorie
            if ($typeOfColl == Hal_Website_Navigation_Page_Collections::BROWSE_TYPE_COLLECTION_WITH_CATEGORY) {
                $coll [$code] ['category'] = $category;
                $coll [$code] ['label'] = $label;
                $coll [$code] ['count'] = $count;
            }
        }
        return $coll;
    }
}
