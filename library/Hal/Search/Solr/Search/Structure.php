<?php

class Hal_Search_Solr_Search_Structure extends Hal_Search_Solr_Search
{
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



        $userFilterQuery = parent::getUserFilterType($typeFilter);

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
     * @param array $structures
     * @return array
     * @see getLinkedStructures
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
     * @param array $arrayOfAllStructures
     * @param string $sortType
     * @return mixed
     * @see getLinkedStructures
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


}
