<?php

class Hal_Search_Solr_Search_Author extends Hal_Search_Solr_Search
{

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
    public static function getLinkedAuthors($type = 'allTypes', array $structures, $letter, $typeFilter, $sortType): array
    {
        $facetField = self::getFacetFieldNameForType($type);

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
                $facetPrefix = $letter . Ccsd_Search_Solr::SOLR_ALPHA_SEPARATOR . $prefix . Ccsd_Search_Solr::SOLR_FACET_SEPARATOR;
                $baseQueryString .= '&facet.field={!key=structure_' . $k . '+facet.prefix=' . urlencode($facetPrefix) . '}' . urlencode($facetField);
            }
        } else {
            $facetField = 'structHasAuthIdHal_fs';
            foreach ($structures as $k => $prefix) {
                $facetPrefix = $prefix . Ccsd_Search_Solr::SOLR_FACET_SEPARATOR;
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

        if ($sortType == 'count') {
            $arrayOfAuth = self::sortByCount($countArrayOfAuth, $nameArrayOfAuth, $arrayOfAuth, $idHalArrayOfAuth);
        } else {
            $arrayOfAuth = self::sortByName($nameArrayOfAuth, $arrayOfAuth, $countArrayOfAuth, $idHalArrayOfAuth);
        }


        return $arrayOfAuth;

    }

    /**
     * @param $type
     * @return string
     */
    private static function getFacetFieldNameForType($type): string
    {
        if ($type == 'primary') {
            // si la structure doit être la structure primaire de l'auteur
            $facetField = 'structPrimaryHasAlphaAuthIdHal_fs';
        } else {
            // la structure primaire de l'auteur + toutes les structures
            // parentes de la primaire
            $facetField = 'structHasAlphaAuthIdHal_fs';
        }
        return $facetField;
    }

    /**
     * refait le tableau trié par nombre d'occurence
     * @param array $countArrayOfAuth
     * @param array $nameArrayOfAuth
     * @param array $arrayOfAuth
     * @param array $idHalArrayOfAuth
     * @return array
     */
    private static function sortByCount(array $countArrayOfAuth, array $nameArrayOfAuth, array $arrayOfAuth, array $idHalArrayOfAuth): array
    {
        arsort($countArrayOfAuth);
        foreach ($countArrayOfAuth as $authId => $authCount) {
            $arrayOfAuth [$authId] ['name'] = $nameArrayOfAuth [$authId];
            $arrayOfAuth [$authId] ['count'] = $authCount;
            if (isset($idHalArrayOfAuth [$authId])) {
                $arrayOfAuth [$authId] ['idHal'] = $idHalArrayOfAuth [$authId];
            }
        }
        return $arrayOfAuth;
    }

    /**
     * Refait le tableau trié par nom
     * @param array $nameArrayOfAuth
     * @param array $arrayOfAuth
     * @param array $countArrayOfAuth
     * @param array $idHalArrayOfAuth
     * @return array
     */
    private static function sortByName(array $nameArrayOfAuth, array $arrayOfAuth, array $countArrayOfAuth, array $idHalArrayOfAuth): array
    {
        uasort($nameArrayOfAuth, 'strcoll');
        foreach ($nameArrayOfAuth as $authId => $authName) {
            $arrayOfAuth [$authId] ['name'] = $authName;
            $arrayOfAuth [$authId] ['count'] = $countArrayOfAuth [$authId];
            if (isset($idHalArrayOfAuth [$authId])) {
                $arrayOfAuth [$authId] ['idHal'] = $idHalArrayOfAuth [$authId];
            }
        }
        return $arrayOfAuth;
    }
}
