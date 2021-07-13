<?php

/**
 * Méthodes spécifiques à HAL pour la recherche avec solr
 * @author rtournoy
 *
 */
class Hal_Search_Solr_Search extends Ccsd_Search_Solr_Search
{

    /**
     * Retourne le nombre de documents par type de doc pour chaque catégories
     * @return array
     */
    static function getTypeDocsPivotHasFile()
    {
        $facettes = [];

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=true&facet=true&facet.pivot={!key=result}docType_s,submitType_s&omitHeader=true&facet.mincount=1';

        $typeDocs = Hal_Settings::getTypdocs();

        if (!is_array($typeDocs)) {
            return [];
        }

        $defaultFilterQuery = self::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));

        // Une requête solr pour chaque catégorie
        foreach ($typeDocs as $type) {

            if ($type ['type'] != 'category') {
                continue;
            }
            // requête de base pour récupérer du PHP serialisé
            $queryString = $baseQueryString;

            if ($defaultFilterQuery != null) {
                $queryString .= $defaultFilterQuery;
            }

            $fq = []; // filter Query

            foreach ($type ['children'] as $child) {
                // tableau des types de doc pour cette catégorie
                $fq [] = $child ['id'];
            }
            // ajout des types de docs à la requête de base

            $docTypesFilterQuery = implode(' OR ', $fq);

            $queryString .= '&fq=docType_s:(' . urlencode($docTypesFilterQuery) . ')';

            try {
                $solrResponse = Ccsd_Tools::solrCurl($queryString, 'hal');

                $solrResponse = unserialize($solrResponse);
            } catch (Exception $e) {
                $serverName = $_SERVER['SERVER_NAME'];
                error_log($serverName . ' Query: ' . $queryString . ' ' . $e->getMessage());
                return [];
            }

            if ($solrResponse ['facet_counts'] == 0) {
                return [];
            }

            $numberForCategory = 0;

            $categoryDocType = [];

            $typeDocNumber = 1;
            $numberForCategoryWithFile = 0;
            $numberForCategoryWithoutFile = 0;
            $numberForCategoryAnnex = 0;

            foreach ($solrResponse ['facet_counts'] ['facet_pivot'] ['result'] as $facet) {

                $categoryDocType [$typeDocNumber] ['code'] = $facet ['value'];
                $categoryDocType [$typeDocNumber] ['label'] = 'typdoc_' . $facet ['value'];
                $categoryDocType [$typeDocNumber] ['count'] = $facet ['count'];

                $categoryDocType [$typeDocNumber] ['pivot'] = $facet ['pivot'];

                foreach ($facet ['pivot'] as $pivot) {
                    switch ($pivot ['value']) {
                        case Hal_Document::FORMAT_FILE :
                            $numberForCategoryWithFile = $numberForCategoryWithFile + $pivot ['count'];
                            break;
                        case Hal_Document::FORMAT_NOTICE :
                            $numberForCategoryWithoutFile = $numberForCategoryWithoutFile + $pivot ['count'];
                            break;
                        case Hal_Document::FORMAT_ANNEX :
                            $numberForCategoryAnnex = $numberForCategoryAnnex + $pivot ['count'];
                            break;
                        default:
                            break;
                    }
                }

                $typeDocNumber++;

                // aojut cumul de resultat pour la categorie
                $numberForCategory = $numberForCategory + $facet ['count'];
            }

            // valeurs des sous-types
            $facettes [$type ['label']] ['values'] = $categoryDocType;
            // cumul d'occurences pour la categorie

            $facettes [$type ['label']] ['total'] = $numberForCategory;
            $facettes [$type ['label']] ['totalFile'] = $numberForCategoryWithFile;
            $facettes [$type ['label']] ['totalNotice'] = $numberForCategoryWithoutFile;
            $facettes [$type ['label']] ['totalAnnex'] = $numberForCategoryAnnex;
            $facettes [$type ['label']] ['docTypesFilterQuery'] = $docTypesFilterQuery;

        }

        return $facettes;
    }

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
     * Liste de collections
     * @param string $typeOfColl
     * @param null $filter
     * @param null $typeFilter
     * @param null $sortType
     * @return array
     */
    static function getCollections($typeOfColl = 'collection', $filter = null, $typeFilter = null, $sortType = null)
    {
        $coll = [];
        $collectionList = null;

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=false&facet=true&omitHeader=true&facet.limit=' . Ccsd_Search_Solr::SOLR_MAX_RETURNED_FACETS_RESULTS . '&facet.mincount=1&facet.field={!key=CollectionList}';

        switch ($typeOfColl) {
            case '':
            case 'collection' :
                // Liste de collections
                if ($filter != null) {
                    $facetField = 'collIsParentOfColl_fs'; // UGA_FacetSep_HAL Grenoble Alpes_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
                } else {
                    $facetField = 'collNameCode_fs'; // CNRS - Centre national de la recherche scientifique_FacetSep_CNRS
                }
                break;

            case 'collection_with_category' :
                // Liste de collections + affichage de la catégorie
                if ($filter != null) {
                    $facetField = 'collIsParentOfCategoryColl_fs'; // UGA_FacetSep_UNIV_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
                } else {
                    $facetField = 'collCategoryCodeName_fs'; // INSTITUTION_JoinSep_CNRS_FacetSep_CNRS - Centre national de la recherche scientifique
                }
                break;

            case 'collection_by_category' :

                $collectionList = self::getCollectionsByCategory($filter, $typeFilter, $sortType);

                // Liste de collections, réparties par catégories
                if ($filter != null) {
                    $facetField = 'collIsParentOfCategoryColl_fs'; // UGA_FacetSep_UNIV_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
                } else {
                    $facetField = 'collCategoryCodeName_fs'; // INSTITUTION_JoinSep_CNRS_FacetSep_CNRS - Centre national de la recherche scientifique
                }
                break;
            default:
                // On traite comme si la valeur est collection MAIS PAS NORMAL (exemple: collections!!!)
                Ccsd_Tools::panicMsg(__FILE__, __DIR__, "typeOfColl variable a bad value: $typeOfColl");
                if ($filter != null) {
                    $facetField = 'collIsParentOfColl_fs'; // UGA_FacetSep_HAL Grenoble Alpes_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
                } else {
                    $facetField = 'collNameCode_fs'; // CNRS - Centre national de la recherche scientifique_FacetSep_CNRS
                }
        }

        if ($collectionList == null) {
            $baseQueryString .= $facetField;
            if ($filter != null) {
                $baseQueryString .= '&facet.prefix=' . urlencode($filter [0]) . Ccsd_Search_Solr::SOLR_FACET_SEPARATOR;
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
                return [];
            }

            if (!array_key_exists('facet_counts', $solrResponse) || $solrResponse ['facet_counts'] == 0) {
                return [];
            }

            $collectionList = $solrResponse ['facet_counts'] ['facet_fields'] ['CollectionList'];
        }

        // Masque les collections invisibles
        $hiddenCollections = Hal_Site_Settings_Collection::getNonVisibleCollections();

        if ($facetField == 'collNameCode_fs') {
            foreach ($collectionList as $collection => $count) {
                $collArray = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $collection);
                list ($label, $code) = $collArray;

                if (in_array($code, $hiddenCollections)) {
                    continue;
                }

                $coll [$code] ['label'] = $label;
                $coll [$code] ['count'] = $count;
            }
        }

        // UGA_FacetSep_HAL Grenoble Alpes_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
        if ($facetField == 'collIsParentOfColl_fs') {
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
        }
        // UGA_FacetSep_UNIV_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
        if ($facetField == 'collIsParentOfCategoryColl_fs') {
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
                if ($typeOfColl == 'collection_by_category') {
                    $coll [$category] [$code] ['label'] = $label;
                    $coll [$category] [$code] ['count'] = $count;
                }
                // Affichage avec catégorie
                if ($typeOfColl == 'collection_with_category') {
                    $coll [$code] ['category'] = $category;
                    $coll [$code] ['label'] = $label;
                    $coll [$code] ['count'] = $count;
                }
            }
        }

        // INSTITUTION_JoinSep_CNRS_FacetSep_CNRS - Centre national de la recherche scientifique
        if ($facetField == 'collCategoryCodeName_fs') {
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
                if ($typeOfColl == 'collection_by_category') {
                    $coll [$category] [$code] ['label'] = $label;
                    $coll [$category] [$code] ['count'] = $count;
                }
                // Affichage avec catégorie
                if ($typeOfColl == 'collection_with_category') {
                    $coll [$code] ['category'] = $category;
                    $coll [$code] ['label'] = $label;
                    $coll [$code] ['count'] = $count;
                }
            }
        }

        return $coll;
    }

    /**
     * Liste de collections réparties selon leur catégorie
     *
     * @param string $filter
     * @param string $typeFilter
     * @param string $sortType
     * @return NULL|multitype:
     */
    static function getCollectionsByCategory($filter = null, $typeFilter = null, $sortType = null)
    {
        $cat = array_values(Hal_Site_Collection::getCategories());

        $cat = str_replace('type_', '', $cat);
        $cat = array_map('strtoupper', $cat);

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=false&facet=true&omitHeader=true&facet.limit=' . 3000 . '&facet.mincount=1';

        $userFilterQuery = self::getUserFilterType($typeFilter);
        $userSortType = self::getUserFacetSortType($sortType);

        $baseQueryString .= '&facet.sort=' . $userSortType;

        if ($userFilterQuery != null) {
            $baseQueryString .= '&fq=(' . $userFilterQuery . ')';
        }

        if ($filter != null) {
            $facetField = 'collIsParentOfCategoryColl_fs'; // UGA_FacetSep_UNIV_JoinSep_UNIV-GRENOBLE1_FacetSep_Université Joseph Fourier - Grenoble I
        } else {
            $facetField = 'collCategoryCodeName_fs'; // INSTITUTION_JoinSep_CNRS_FacetSep_CNRS - Centre national de la recherche scientifique
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
     * Les auteurs liés à une structure
     *
     * @param string $type
     * @param array $structures
     * @param string $letter
     * @param string $typeFilter
     * @param string $sortType
     * @return array
     */
    static function getLinkedAuthors($type = 'allTypes', array $structures, $letter, $typeFilter, $sortType): array
    {
        if ($type == 'primary') {
            // si la structure doit être la structure primaire de l'auteur
            $facetField = 'structPrimaryHasAlphaAuthIdHal_fs';
        } else {
            // la structure primaire de l'auteur + toutes les structures
            // parentes de la primaire
            $facetField = 'structHasAlphaAuthIdHal_fs';
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
            $userFilterQuery = urlencode($userFilterQuery);
        } else {
            $userFilterQuery = null;
        }

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=false&omitHeader=true&facet.limit=10000&facet=true&facet.mincount=1';

        if ($letter != 'all') {
            foreach ($structures as $k => $prefix) {
                $facetPrefix = $letter . parent::SOLR_ALPHA_SEPARATOR . $prefix . parent::SOLR_FACET_SEPARATOR;
                $baseQueryString .= '&facet.field={!key=structure_' . $k . '+facet.prefix=' . urlencode($facetPrefix) . '}' . urlencode($facetField);
            }
        } else {
            $facetField = 'structHasAuthIdHal_fs';
            foreach ($structures as $k => $prefix) {
                $facetPrefix = $prefix . parent::SOLR_FACET_SEPARATOR;
                $baseQueryString .= '&facet.field={!key=structure_' . $k . '+facet.prefix=' . urlencode($facetPrefix) . '}' . urlencode($facetField);
            }
        }

        if ($userFilterQuery != null) {
            $baseQueryString .= '&fq=(' . $userFilterQuery . ')';
        }

        try {
            $solrResponse = Hal_Tools::solrCurl($baseQueryString, 'hal', 'select', true);
            $solrResponse = unserialize($solrResponse);
        } catch (Exception $e) {
            return [];
        }


        if (!isset($solrResponse ["facet_counts"] ["facet_fields"])) {
            return [];
        }


        if (count($solrResponse ["facet_counts"] ["facet_fields"], COUNT_RECURSIVE) == count($structures)) {
            return [];
        }


        $nameArrayOfAuth = [];
        $countArrayOfAuth = [];
        $idHalArrayOfAuth = [];
        $arrayOfAuth = [];

        foreach ($solrResponse ["facet_counts"] ["facet_fields"] as $k => $structureList) {
            /**
             * Pour dédoublonner sur l'id de l'auteur
             */
            foreach ($structureList as $authName => $authCount) {
                // la chaine retournée contient les prefixes de recherche
                // avec l'id du labo

                $authName = preg_replace('/.*' . Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR . '/', '', $authName);

                $authNameArr = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $authName);


                $authIdHal = $authNameArr [0];
                $authName = $authNameArr [1];

                // comme id unique on prend l'idhal ou la forme auteur
                if ($authIdHal != '') {
                    $authId = $authIdHal;
                } else {
                    $authId = $authName;
                }

                if (array_key_exists($authId, $nameArrayOfAuth)) {

                    $nameArrayOfAuth [$authId] = $authName;
                    // on ajoute au nombre de docs déjà présents de l'auteur

                    $countArrayOfAuth [$authId] = $authCount + $countArrayOfAuth [$authId];
                } else {

                    $nameArrayOfAuth [$authId] = $authName;
                    $countArrayOfAuth [$authId] = $authCount;
                }

                if ($authIdHal != '') {
                    $idHalArrayOfAuth [$authId] = $authIdHal;
                }
            }
        }

        /* ----------------------------------------------------- */

        switch ($sortType) {
            default :
                // default = index
            case 'index' :

                /**
                 * tri par nom
                 */
                uasort($nameArrayOfAuth, 'strcoll');

                /**
                 * refait le tableau trié par nom
                 */
                foreach ($nameArrayOfAuth as $authId => $authName) {
                    $arrayOfAuth [$authId] ['name'] = $authName;
                    $arrayOfAuth [$authId] ['count'] = $countArrayOfAuth [$authId];
                    if (isset($idHalArrayOfAuth [$authId])) {
                        $arrayOfAuth [$authId] ['idHal'] = $idHalArrayOfAuth [$authId];
                    }
                }
                break;

            case 'count' :
                /**
                 * tri par nombre d'occurence
                 */
                arsort($countArrayOfAuth);

                /**
                 * refait le tableau trié par nombre d'occurence
                 */
                foreach ($countArrayOfAuth as $authId => $authCount) {
                    $arrayOfAuth [$authId] ['name'] = $nameArrayOfAuth [$authId];
                    $arrayOfAuth [$authId] ['count'] = $authCount;
                    if (isset($idHalArrayOfAuth [$authId])) {
                        $arrayOfAuth [$authId] ['idHal'] = $idHalArrayOfAuth [$authId];
                    }
                }
                break;
        }

        return $arrayOfAuth;

    }

    /**
     * Retourne les structures liées à une autre structure
     * @param string $type
     * @param array $structures
     * @param string $letter
     * @param array $typeFilter
     * @param string $sortType
     * @return NULL|array
     */
    static function getLinkedStructures(array $structures, $letter, $typeFilter, $sortType, $type = 'all')
    {

        /**
         * "structIsParentOf_fs": [
         * "301232_JoinSep_I_AlphaSep_219748_FacetSep_Institut National des Sciences Appliquées - Lyon",
         */
        /**
         * "structIsParentOfType_fs": [
         * "300046_JoinSep_laboratory_C_AlphaSep_123_FacetSep_Centre de Spectrométrie Nucléaire et de Spectrométrie de Masse",
         */


        switch ($type) {

            case Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION :
            case Ccsd_Referentiels_Structure::TYPE_INSTITUTION :
            case Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY :
            case Ccsd_Referentiels_Structure::TYPE_LABORATORY :
            case Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM :
            case Ccsd_Referentiels_Structure::TYPE_DEPARTMENT :
                $facetField = 'structIsParentOfType_fs';
                $prefixType = $type . '_';
                break;

            default :
                $prefixType = '';
                $facetField = 'structIsParentOf_fs';
                break;
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
            $userFilterQuery = urlencode($userFilterQuery);
        } else {
            $userFilterQuery = null;
        }

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=false&omitHeader=true&facet.limit=10000&facet=true&facet.mincount=1';

        // ici, reduit le temps d'execution
        // @see https://cwiki.apache.org/confluence/display/solr/Faceting#Faceting-Thefacet.methodParameter
        // facet.threads : Controls parallel execution of field faceting
        $baseQueryString .= '&facet.method=fcs&facet.threads=-1';


        foreach ($structures as $k => $prefix) {
            $facetPrefix = $prefix . parent::SOLR_JOIN_SEPARATOR . $prefixType;
            if ($letter != 'all') {
                $facetPrefix .= $letter;
            }
            $baseQueryString .= '&facet.field={!key=structure_' . $k . urlencode(' ') . 'facet.prefix=' . urlencode($facetPrefix) . '}' . $facetField;
        }


        if ($userFilterQuery != null) {
            $baseQueryString .= '&fq=(' . $userFilterQuery . ')';
        }

        try {
            $solrResponse = Hal_Tools::solrCurl($baseQueryString, 'hal', 'select', true);
            $solrResponse = unserialize($solrResponse);
        } catch (Exception $e) {
            return null;
        }

        if (count($solrResponse ["facet_counts"] ["facet_fields"], COUNT_RECURSIVE) == count($structures)) {
            return null;
        }

        if (!isset($solrResponse ["facet_counts"] ["facet_fields"])) {
            return null;
        }

        $arrayOfAllStructures = static::deduplicatesStructuressWithParentsByName($solrResponse ["facet_counts"] ["facet_fields"]);

        if (count($arrayOfAllStructures) == 0) {
            return null;
        }

        return static::sortStructuresWithParents($arrayOfAllStructures, $sortType);

    }

    /**
     * Dédoublonnage structures trouvées avec un ou plusieurs parents
     * @see getLinkedStructures
     * @param array $structures
     * @return array
     */
    private
    static function deduplicatesStructuressWithParentsByName(array $structures)
    {

        $arrayOfStructuresNames = [];
        $arrayOfStructuresCount = [];

        foreach (array_values($structures) as $structureList) {

            foreach ($structureList as $structName => $structCount) {
                $structNameArr = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $structName);
                $structLabel = $structNameArr[1];

                if (array_key_exists($structLabel, $arrayOfStructuresNames)) {
                    // fusion avec une structure qui a le même label mais d'un parent différent ou avec un identifiant de structure différent
                    $arrayOfStructuresNames [$structLabel] = $structLabel;
                    // la structure existe on ajoute le nombre de docs à l'existant
                    $arrayOfStructuresCount [$structLabel] = (int)$arrayOfStructuresCount [$structLabel] + (int)$structCount;
                } else {
                    $arrayOfStructuresNames [$structLabel] = $structLabel;
                    $arrayOfStructuresCount [$structLabel] = (int)$structCount;
                }
            }
        }

        return ['names' => $arrayOfStructuresNames, 'counts' => $arrayOfStructuresCount];

    }

    /**
     * Tri structures trouvées avec un ou plusieurs parents
     * @see getLinkedStructures
     * @param array $arrayOfAllStructures
     * @param string $sortType
     * @return mixed
     */
    private
    static function sortStructuresWithParents(array $arrayOfAllStructures, $sortType = 'index')
    {

        $nameArrayOfStructures = $arrayOfAllStructures['names'];
        $countArrayOfStructures = $arrayOfAllStructures['counts'];


        if ($sortType == 'count') {
            /**
             * tri par nombre d'occurence
             */
            arsort($countArrayOfStructures);

            /**
             * refait le tableau trié par nombre d'occurence
             */
            foreach ($countArrayOfStructures as $structureId => $structureCount) {
                $arrayOfStructures [$structureId] ['name'] = $nameArrayOfStructures [$structureId];
                $arrayOfStructures [$structureId] ['count'] = $structureCount;
            }
        } else {
            /**
             * tri par nom
             */
            uasort($nameArrayOfStructures, 'strcoll');


            /**
             * refait le tableau trié par nom
             */
            foreach ($nameArrayOfStructures as $structureId => $structureName) {

                $arrayOfStructures [$structureId] ['name'] = $structureName;
                $arrayOfStructures [$structureId] ['count'] = $countArrayOfStructures [$structureId];
            }
        }


        return $arrayOfStructures;


    }

    /**
     * Retourne un tableau des domaines
     * @param string $displayType
     * @param null $typeFilter
     * @return array
     */
    static function getDomainConsultationArray($displayType = null, $typeFilter = null)
    {
        $rootLevel = '0';
        $portalDomains = [];

        if ($displayType == 'portal') {
            $portalDomains = self::getPortalDomainsAsFacetPrefix();

            // On trie par niveau
            ksort($portalDomains);

            // On prend le niveau racine
            $rootLevel = array_keys($portalDomains)[0][0];
        }

        // DomArray doit ressembler à ça
        // 0 => ['domainCode' => 0.shs, 'domainName' => Sciences Humaines et Sociales, 'domainDisplay' => <a href=''>Sciences ...</a><small>(Nombre de publications correspondantes)</small>]
        // 1 => ['domainCode' => 1.shs.lang, 'domainName' => Langues, 'domainDisplay' => <a href=''>Langues ...</a><small>(Nombre de publications correspondantes)</small>]
        // 2 => ['domainCode' => 0.sdv, 'domainName' => Sciences du Vivant, 'domainDisplay' => <a href=''>Sciences ...</a><small>(Nombre de publications correspondantes)</small>]
        return self::fillDomainConsultationArray([], '', $rootLevel, $portalDomains, $typeFilter);

    }

    /**
     * Retourne la liste des domaines d'un portail pour servir de préfixe de facettes
     *
     * @return array
     */
    private
    static function getPortalDomainsAsFacetPrefix()
    {
        $portalDomains = [];
        $dom = json_decode(file_get_contents(Hal_Settings::getDomains()), true);
        foreach (new RecursiveIteratorIterator(new RecursiveArrayIterator($dom), RecursiveIteratorIterator::SELF_FIRST) as $key => $value) {
            $kVal = explode('.', $key);
            $kLevel = count($kVal) - 1; // -1 car commence à niveau 0
            $portalDomains [$kLevel . '.' . $key] = null;
        }
        return $portalDomains;
    }

    /**
     * @param $domArray
     * @param $parentDom
     * @param $level
     * @param $portalDomains
     * @param null $typeFilter
     * @return array
     */
    static function fillDomainConsultationArray($domArray, $parentDom, $level, $portalDomains, $typeFilter = null)
    {
        // Décompte des publications par domaines du niveau $level.'.'.$parentDom
        $doms = self::getFacetField('domain_s', $level . '.' . $parentDom, 'count', $typeFilter);

        if (!empty($portalDomains)) {
            // Permet d'avoir le décompte des publications par domaine du portail
            $doms = self::removeDomainsNotInPortal($portalDomains, $doms);
        }

        foreach ($doms as $dom => $count) {

            $dom = substr_replace($dom, '', 0, 2); // On vire le numéro hiérarchique  0. ou 1. (peut probable mais si on a une hiérarchie à 2 chiffres, ça se passe mal)

            // On ajoute les parents s'ils sont inexistants (shs à shs.lang par exemple)
            $domArray = self::addNeededParents($dom, $domArray);

            $domArray[] = self::formatDomainForDomainConsultation($dom, $count);

            // On ajoute les sous-domaines
            $domArray = self::fillDomainConsultationArray($domArray, $dom, $level + 1, $portalDomains, $typeFilter);
        }


        return $domArray;
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
     * Dans un résultat de facette, supprime les domaines qui n'appartiennent pas au portail
     *
     * @param array $portalDomains
     * @param array $facetDomains
     * @return array
     */
    private static function removeDomainsNotInPortal($portalDomains, $facetDomains)
    {
        foreach ($facetDomains as $dom => $domCount) {
            if (!array_key_exists($dom, $portalDomains)) {
                unset($facetDomains [$dom]);
            }
        }

        return $facetDomains;
    }


    /**
     * @param string $dom
     * @param array $domArray
     * @return array
     */
    static function addNeededParents($dom, $domArray)
    {
        $parentDomain = self::needParent($dom, $domArray);
        if ($parentDomain !== '') {
            // On ajoute le grand-parent
            $domArray = self::addNeededParents($parentDomain, $domArray);

            // On ajoute le parent
            $domArray[] = self::formatDomainForDomainConsultation($parentDomain, '');
        }

        return $domArray;
    }

    /**
     * @param $dom
     * @param $domArray
     * @return string
     */
    static function needParent($dom, $domArray)
    {

        $res = explode('.', $dom);

        // On est dans le cas d'un sous-domaine
        if (!empty($res) && reset($res) !== $dom) {

            $parentDomain = str_replace('.' . end($res), '', $dom);
            $lastDomainEntered = end($domArray)["domainCode"];

            if (!empty($domArray) && strpos($lastDomainEntered, $parentDomain) !== false) {
                // L'element précédent est un parent ou un frère donc pas besoin d'ajouter un parent
                return '';
            } else {
                return $parentDomain;
            }
        }

        return '';
    }

    /**
     * @param string $domain
     * @param string $count
     * @return array
     * @throws Zend_Exception
     */
    static function formatDomainForDomainConsultation($domain, $count)
    {
        $translate = Zend_Registry::get('Zend_Translate');
        $translatedDomain = $translate->translate('domain_' . $domain);

        $h = new Hal_View_Helper_Url ();

        $domainDisplayUrl = $h->url([
            'controller' => 'search',
            'action' => 'index',
            'q' => '*',
            'domain_t' => $domain
        ]);

        $domainDisplay = '<a href="' . $domainDisplayUrl . '">' . $translatedDomain . '</a>';
        if ($count !== '') {
            $domainDisplay .= '&nbsp;<small>(' . $count . ')</small>';
        }

        return ['domainCode' => $domain, 'domainName' => $translatedDomain, 'domainDisplay' => $domainDisplay];
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

    /**
     * Tri et formate les affiliation pour XML
     * @param $affiliation array
     * @param $params array
     * @return array
     */
    static function sortAndXMLAffiliation($affiliation, $params)
    {
        // 6 - Format XML
        foreach ($affiliation['structIds'] as $structid => $val) {
            $structIds [$structid] = (new Ccsd_Referentiels_Structure ($structid))->getXML(false);
            $xml = new Ccsd_DOMDocument('1.0', 'utf-8');
            $xml->loadXML($structIds [$structid]);
            $root = $xml->getElementsByTagName('org')->item(0);
            $status = null;
            $type = null;
            $matchcountry = 0;
            $status = $root->getAttribute('status');
            $type = $root->getAttribute('type');

            if (isset($val['country'])) {
                $params ['country_s'] = strtoupper($params ['country_s']);
                $address = $xml->getElementsByTagName('country')->item(0);
                $country = $address->getAttribute('key');
                if ($country == $params ['country_s']) {
                    $matchcountry = 1;
                }
            }
            $root->setAttribute('count', $affiliation['countdataS'][$structid]);
            $count = $root->getAttribute('count');

            $indice = Hal_Search_Solr_Search::calcIndice($val['date'], $status, $type, $matchcountry, $count);

            $root->setAttribute('indice', $indice);
            $root->setAttribute('authId', $affiliation['dataA'][0]);

            $sortInd[$structid] = $indice;
            if (!isset($doc ['authIdHasPrimaryStructure_fs'])) {
                continue;
            }

            $structIds [$structid] = $xml->saveXML($xml->documentElement) . PHP_EOL;
        }

        // 7 - Dans le cas où il n'y a pas de résultat, on retourne les structures trouvés
        if (empty($structIds)) {
            foreach ($affiliation['struAffi'] as $id => $s) {
                $structIds [$id] = (new Ccsd_Referentiels_Structure ($id))->getXML(false);
                $xml = new Ccsd_DOMDocument('1.0', 'utf-8');
                $xml->loadXML($structIds [$id]);
                $root = $xml->getElementsByTagName('org')->item(0);
                $date = null;
                $status = null;
                $type = null;
                $matchcountry = 0;
                $count = 1;
                $status = $root->getAttribute('status');
                $type = $root->getAttribute('type');

                if (isset($params ['country_s'])) {
                    $params ['country_s'] = strtoupper($params ['country_s']);
                    $address = $xml->getElementsByTagName('country')->item(0);
                    $country = $address->getAttribute('key');
                    if ($country == $params ['country_s']) {
                        $matchcountry = 1;
                    }
                }

                $indice = Hal_Search_Solr_Search::calcIndice($date, $status, $type, $matchcountry, $count);

                $root->setAttribute('indice', $indice);
                $root->setAttribute('count', $count);
                $root->setAttribute('authId', $affiliation['dataA'][0]);

                $sortInd[$id] = $indice;
                $structIds [$id] = $xml->saveXML($xml->documentElement) . PHP_EOL;
            }
        }

        // Tri les résultats sur l'indice de manière décroissante
        arsort($sortInd);
        foreach (array_keys($sortInd) as $key) {
            $structIdsOrdered[$key] = $structIds[$key];
        }
        return $structIdsOrdered;
    }

    /* Cherche dans Ref_Author les formes auteurs d'un auteur nom, prenom + autres
    * @return array Id forme auteur (authId_i)
    */

    static function calcIndice($diffannee, $status, $type, $matchcountry, $count)
    {
        $indice = 0;

        if (isset($diffannee)) {
            if ($diffannee == 0) { //même année
                $indice = $indice + 3;
            } else if ($diffannee > 0 && $diffannee <= 2) {
                $indice = $indice + 2;
            } else if ($diffannee > 2 && $diffannee <= 5) {
                $indice = $indice + 1;
            } else { //Année > 5
                $indice = $indice + 0;
            }
        }

        if ($status == 'VALID') {
            $indice = $indice + 2;
        } else if ($status == 'OLD') {
            $indice = $indice + 1;
        } else { //INCOMING
            $indice = $indice + 0;
        }

        if ($type == 'researchteam') {
            $indice = $indice + 4;
        } else if ($type == 'department') {
            $indice = $indice + 3;
        } else if ($type == 'laboratory') {
            $indice = $indice + 2;
        } else if ($type == 'regrouplaboratory') {
            $indice = $indice + 1;
        } else { //institution ou regroupement d'institution
            $indice = $indice + 0;
        }

        if ($matchcountry == 1) {
            $indice = $indice + 1;
        }

        if ($count > 100) {
            $indice = $indice + 7;
        } else if ($count > 75 && $count <= 100) {
            $indice = $indice + 6;
        } else if ($count > 50 && $count <= 75) {
            $indice = $indice + 5;
        } else if ($count > 30 && $count <= 50) {
            $indice = $indice + 4;
        } else if ($count > 15 && $count <= 30) {
            $indice = $indice + 3;
        } else if ($count > 5 && $count <= 15) {
            $indice = $indice + 2;
        } else if ($count > 1 && $count <= 5) {
            $indice = $indice + 1;
        } else {
            $indice = $indice + 0;
        }

        return $indice;
    }

    /* Cherche dans HAL les affiliations d'un auteur étant donné une liste de formes auteurs + année ou autres paramètres
    * @return array Toutes les affiliations des documents où on retrouve le/les forme(s) auteur(s)
    */

    /**
     * Récupère les structIds
     * @param $params
     * @param bool $info
     * @return array
     */
    static function rechAffiliation($params, $info = false)
    {
        $structIds = [];
        // 1 - récupération des formes auteurs
        $resultFormAut = self::rechFormAut($params);

        if (isset ($resultFormAut)) {
            // 2 - pour chaque forme auteur, on récupère ces affiliations
            $resultAffi = self::rechAffiAut($resultFormAut, $params);

            $countdataS = [];

            // 4 - on récupère les affiliations des formes auteurs récupérées précédemment
            foreach ($resultAffi as $doc) {

                //Filtre les formes auteurs qui correspondent à notre recherche (les autres auteurs liés au document sont supprimés)
                if (isset($doc ['authIdHasPrimaryStructure_fs'])) {
                    $document = array_filter($doc ['authIdHasPrimaryStructure_fs'], function ($k) use ($resultFormAut) {
                        preg_match('/^[0-9]*/', $k, $id);
                        return in_array($id[0], $resultFormAut);
                    });
                }


                if (isset($document) && is_array($document) && count($document)) {

                    foreach ($document as $s) {
                        //Exemple : 49567_FacetSep_Laurent Romary_JoinSep_95237_FacetSep_Institut für Deutsche Sprache und Linguistik
                        $data = explode(Ccsd_Search_Solr::SOLR_JOIN_SEPARATOR, $s); //$data[0] = 49567_FacetSep_Laurent Romary // $data[1] = 95237_FacetSep_Institut für Deutsche Sprache und Linguistik
                        if (isset ($data [1])) {

                            $dataA = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $data [0]);
                            $dataS = explode(Ccsd_Search_Solr::SOLR_FACET_SEPARATOR, $data [1]); // $dataS[0] = 95237 // $dataS[1] = Institut für Deutsche Sprache und Linguistik

                            if (count($dataS) == 2 && !array_key_exists($dataS [0], $structIds)) {
                                $countdataS[$dataS [0]] = 1;
                                $date = null;
                                $country = null;
                                if (isset($params ['country_s'])) {
                                    $country = $params ['country_s'];
                                }
                                if (isset($doc ['dateabs'])) {
                                    $date = $doc ['dateabs'];
                                }
                                $structIds[$dataS[0]] = ['country' => $country, 'date' => $date, 'authId' => $dataA[0]];
                            } else {
                                $countdataS[$dataS [0]] = $countdataS[$dataS [0]] + 1;
                            }
                        }
                    }
                }
            }
        }

        $struAffi = [];
        if (isset($params['text'])) {
            foreach ($structIds as $k => $v) {
                $label = self::rechInfoStruct($k);
                if (isset($label['acronym_s'])) {
                    $structSigle = self::cleanStr($label['acronym_s']);
                }
                if (isset($label['name_s'])) {
                    $structName = self::cleanStr($label['name_s']);
                }
                $str = self::cleanStr($params['text']);


                if (isset($label['acronym_s']) && strstr($str, $structSigle)) { //Si l'acronyme d'une structure est contenu dans la recherche
                    continue;
                } else if (isset($label['name_s']) && strstr($str, $structName)) { //Si le nom d'une structure est contenu dans la recherche
                    continue;
                } else {
                    unset($structIds[$k]);
                }
            }

            if (empty($structIds)) { // Si le text ne correspond à aucune affiliation de l'auteur
                // 3 - on regarde si on a des informations sur l'affiliation
                $struAffi = self::rechStruct($params);
                if (!empty($struAffi)) {
                    $structIds = array_intersect_key($structIds, $struAffi);
                }
            }
        }

        if ($info) {

            $countdataS = isset($countdataS) ? $countdataS : [];
            $dataA = isset($dataA) ? $dataA : [];
            $struAffi = isset($struAffi) ? $struAffi : [];

            return ['structIds' => $structIds, 'countdataS' => $countdataS, 'dataA' => $dataA, 'struAffi' => $struAffi];
        } else {
            return $structIds;
        }
    }

    /* Cherche dans Ref_Structure les structures par rapport à un texte
    * @return array les structures qui contiennent ce texte
    */

    static function rechFormAut($params)
    {
        $query = "fq=lastName_t:" . urlencode($params['lastName_t']);
        $query .= "&fq=firstName_t:" . urlencode($params['firstName_t']);

        $sc = new Ccsd_Search_Solr_Schema(['env' => APPLICATION_ENV, 'core' => 'ref_author', 'handler' => 'schema']);
        $sc->getSchemaFields(false);

        $blacklistedFields = [
            'lastName_t',
            'firstName_t',
            'text'];

        foreach ($sc->getFields() as $field) {
            $field = ( array )$field;
            if (in_array($field ['name'], $blacklistedFields)) {
                continue;
            }

            if (isset ($params [$field ['name']])) {
                $query .= "&fq=" . $field ['name'] . ":" . urlencode($params [$field ['name']]);
            }
        }
        $query .= "&rows=100&fl=docid&wt=phps";
        $res = unserialize(Ccsd_Tools::solrCurl($query, 'ref_author', 'apiselect'));

        if (isset ($res ['response'] ['docs']) && is_array($res ['response'] ['docs']) && count($res ['response'] ['docs'])) {
            foreach ($res ['response'] ['docs'] as $authId) {
                $arrayAuthId[] = $authId['docid'];
            }
            return $arrayAuthId;
        } else {
            return null;
        }
    }

    /* Cherche dans Ref_Structure les infos d'une structure
    * @return array les infos structures
    */

    static function rechAffiAut($arrayAuthId, $params)
    {
        $query = "q=*:*";
        $i = 0;
        $query .= "&fq=(";
        foreach ($arrayAuthId as $authId) {
            if ($i > 0) {
                $query .= "+OR+";
            }
            $query .= "authId_i:" . $authId;
            $i = $i + 1;
        }
        $query .= ")";

        // Si on a une date on tri les résultats du plus proche au moins proche de la date indiquée
        if (isset ($params ['producedDateY_i'])) {
            $sortList = [
                ['champ' => "abs(sub(producedDateY_i," . $params ['producedDateY_i'] . "))", 'type' => 'asc'],
                ['champ' => "sub(producedDateY_i," . $params ['producedDateY_i'] . ")", 'type' => 'asc'],
                ['champ' => "producedDate_tdate", 'type' => 'desc'],//si même année, on prend le plus récent
            ];
            $query .= "&sort=";
            foreach ($sortList as $sort) {
                $query .= $sort['champ'] . urlencode(" ") . $sort['type'];
                if ($sort['champ'] != 'producedDate_tdate') {
                    $query .= urlencode(", ");
                }
            }
            $query .= "&fl=datesub:sub(producedDateY_i," . $params ['producedDateY_i'] . "),dateabs:abs(sub(producedDateY_i," . $params ['producedDateY_i'] . "))";
        }
        $query .= "&fl=authIdHasPrimaryStructure_fs";
        $query .= "&rows=1000&wt=phps";

        $res = unserialize(Ccsd_Tools::solrCurl($query, 'hal', 'apiselect'));

        if (isset ($res ['response'] ['docs']) && is_array($res ['response'] ['docs']) && count($res ['response'] ['docs'])) {
            foreach ($res ['response'] ['docs'] as $doc) {
                $arrayAffi[] = $doc;
            }
            return $arrayAffi;
        } else {
            return null;
        }
    }

    /* Calcul l'indice de confiance des résultats d'une Affiliation d'auteur
    */

    static function rechInfoStruct($structid)
    {
        $query = "fq=docid:" . $structid;
        $query .= "&fl=name_s,acronym_s";
        $query .= "&rows=1000&wt=phps";

        $res = unserialize(Ccsd_Tools::solrCurl($query, 'ref_structure', 'apiselect'));

        if (isset ($res ['response'] ['docs']) && is_array($res ['response'] ['docs']) && count($res ['response'] ['docs'])) {
            foreach ($res ['response'] ['docs'] as $doc) {
                $resultinfo = $doc;
            }
            return $resultinfo;
        } else {
            return null;
        }
    }

    static public function cleanStr($str)
    {
        $str = strtolower($str);
        $str = self::removeCommonWords($str);
        $str = self::removeBrackets($str);
        return str_replace(["'", '"', ' ', ','], '', $str);

    }

    static public function removeCommonWords($str)
    {
        $commonWords = [
            'de', 'du', 'et', 'le', 'la', 'les', 'en', 'laboratoire', 'departement', 'equipe',
            'a', 'from', 'of', 'laboratory', 'department', 'researchteam',
        ];
        return preg_replace('/\b(' . implode('|', $commonWords) . ')\b/', '', $str);
    }

    static public function removeBrackets($str)
    {
        return preg_replace('/\(.*\)/', '', $str);
    }

    static function rechStruct($params)
    {
        if (isset($params['text'])) {
            $querystru = "q=" . urlencode($params['text']);
            $querystru .= "&qf=text&defType=edismax";
        } else {
            $querystru = "q=*:*";
        }
        $querystru .= "&rows=1000&wt=phps";

        $blacklistedFields = [
            'authId_i',
            'producedDateY_i',
            'producedDate_tdate',
            'text'];

        $sc = new Ccsd_Search_Solr_Schema(['env' => APPLICATION_ENV, 'core' => 'ref_structure', 'handler' => 'schema']);
        $sc->getSchemaFields(false);

        foreach ($sc->getFields() as $field) {
            $field = ( array )$field;
            if (in_array($field ['name'], $blacklistedFields)) {
                continue;
            }

            if (isset ($params [$field ['name']])) {
                $querystru .= "&fq=" . $field ['name'] . ":" . urlencode($params [$field ['name']]);
            }
        }

        $resultstru = unserialize(Ccsd_Tools::solrCurl($querystru, 'ref_structure', 'apiselect'));

        if (isset ($resultstru ['response'] ['docs']) && is_array($resultstru ['response'] ['docs']) && count($resultstru ['response'] ['docs'])) {
            foreach ($resultstru ['response'] ['docs'] as $docstru) {
                $struAffi[$docstru ['docid']] = $docstru ['label_s'];
            }
            return $struAffi;
        } else {
            return null;
        }

    }

    /**
     * S. Dx --------- Nouvelle Recherche d'affiliations
     */

    static public function rechAffiliations($data, $timeout = 10)
    {
        // Dans l'idéal, il vaudrait mieux faire un GET plutôt qu'un POST car on ne fait pas de modif de donnéee
        // Des tests en GET ont montré que la requête est plus longue qu'en POST... va savoir pourquoi mais pour l'instant on a choisit de garder le POST

        $curl = curl_init(SOLR_API . '/ref/affiliation/');
        curl_setopt($curl, CURLOPT_USERAGENT, "HAL - DEPOT");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);

        if (!empty($data)) {
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        $affResult = curl_exec($curl);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);

        if (curl_errno($curl) == CURLE_OK) {
            curl_close($curl);

            try {
                $affiliationsArray = Zend_Json::decode($affResult);
                return $affiliationsArray;
            } catch (Exception $e) {
                error_log("Decodage des affiliations plante");
                return null;
            }
        } else {
            curl_close($curl);
            return null;
        }
    }

}
