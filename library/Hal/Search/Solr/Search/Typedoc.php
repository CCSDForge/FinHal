<?php

class Hal_Search_Solr_Search_Typedoc extends Hal_Search_Solr_Search
{

    /**
     * Retourne le nombre de documents par type de doc pour chaque catégories
     * @return array
     */
    public static function getTypeDocsPivotHasFile()
    {
        $facettes = [];

        $baseQueryString = 'q=*:*&rows=0&wt=phps&indent=true&facet=true&facet.pivot={!key=result}docType_s,submitType_s&omitHeader=true&facet.mincount=1';

        $typeDocs = Hal_Settings::getTypdocs();

        if (!is_array($typeDocs)) {
            return [];
        }

        $defaultFilterQuery = parent::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));

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
            $totalBySubmitType[Hal_Document::FORMAT_FILE] = 0;
            $totalBySubmitType[Hal_Document::FORMAT_NOTICE] = 0;
            $totalBySubmitType[Hal_Document::FORMAT_ANNEX] = 0;

            foreach ($solrResponse ['facet_counts'] ['facet_pivot'] ['result'] as $facet) {

                $categoryDocType [$typeDocNumber] ['code'] = $facet ['value'];
                $categoryDocType [$typeDocNumber] ['label'] = 'typdoc_' . $facet ['value'];
                $categoryDocType [$typeDocNumber] ['count'] = $facet ['count'];

                $categoryDocType [$typeDocNumber] ['pivot'] = $facet ['pivot'];

                $totalBySubmitType = self::incrementTotalBySubmitType($facet, $totalBySubmitType);

                $typeDocNumber++;

                // aojut cumul de resultat pour la categorie
                $numberForCategory = $numberForCategory + $facet ['count'];
            }

            // valeurs des sous-types
            $facettes [$type ['label']] ['values'] = $categoryDocType;
            // cumul d'occurences pour la categorie

            $facettes [$type ['label']] ['total'] = $numberForCategory;
            $facettes [$type ['label']] ['totalFile'] = $totalBySubmitType[Hal_Document::FORMAT_FILE];
            $facettes [$type ['label']] ['totalNotice'] = $totalBySubmitType[Hal_Document::FORMAT_NOTICE];
            $facettes [$type ['label']] ['totalAnnex'] = $totalBySubmitType[Hal_Document::FORMAT_ANNEX];
            $facettes [$type ['label']] ['docTypesFilterQuery'] = $docTypesFilterQuery;

        }

        return $facettes;
    }

    /**
     * @param $facet
     * @param $totalBySubmitType
     * @return mixed
     */
    private static function incrementTotalBySubmitType($facet, $totalBySubmitType)
    {
        foreach ($facet ['pivot'] as $pivot) {
            switch ($pivot ['value']) {
                case Hal_Document::FORMAT_FILE :
                    $totalBySubmitType[Hal_Document::FORMAT_FILE] = $totalBySubmitType[Hal_Document::FORMAT_FILE] + $pivot ['count'];
                    break;
                case Hal_Document::FORMAT_NOTICE :
                    $totalBySubmitType[Hal_Document::FORMAT_NOTICE] = $totalBySubmitType[Hal_Document::FORMAT_NOTICE] + $pivot ['count'];
                    break;
                case Hal_Document::FORMAT_ANNEX :
                    $totalBySubmitType[Hal_Document::FORMAT_ANNEX] = $totalBySubmitType[Hal_Document::FORMAT_ANNEX] + $pivot ['count'];
                    break;
                default:
                    break;
            }
        }
        return $totalBySubmitType;
    }
}
