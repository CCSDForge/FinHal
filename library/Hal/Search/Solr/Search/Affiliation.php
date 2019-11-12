<?php

class Hal_Search_Solr_Search_Affiliation extends Hal_Search_Solr_Search
{


    /**
     * @param $params
     * @return array|null
     * @throws Exception
     */
    public static function rechFormAut($params)
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

    /**
     * Récupère les structIds
     * Cherche dans HAL les affiliations d'un auteur étant donné une liste de formes auteurs + année ou autres paramètres
     * @param $params
     * @param bool $info
     * @return array
     */
    public static function rechAffiliation($params, $info = false)
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
                if (!isset($doc ['authIdHasPrimaryStructure_fs'])) {
                    continue;
                }

                //Filtre les formes auteurs qui correspondent à notre recherche (les autres auteurs liés au document sont supprimés)
                $document = array_filter($doc ['authIdHasPrimaryStructure_fs'], function ($k) use ($resultFormAut) {
                    preg_match('/^[0-9]*/', $k, $id);
                    return in_array($id[0], $resultFormAut);
                });


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

    /**
     * Tri et formate les affiliation pour XML
     * @param array $affiliation
     * @param array $params
     * @return array
     */
    public static function sortAndXMLAffiliation($affiliation, $params)
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

    /**
     * @param $structid
     * @return |null
     * @throws Exception
     */
    public static function rechInfoStruct($structid)
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

    /**
     * @param $diffannee
     * @param $status
     * @param $type
     * @param $matchcountry
     * @param $count
     * @return int
     */
    public static function calcIndice($diffannee, $status, $type, $matchcountry, $count)
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

    /**
     * @param $params
     * @return |null
     * @throws Exception
     */
    public static function rechStruct($params)
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
     * @param $data
     * @param int $timeout
     * @return mixed|null
     */
    public static function rechAffiliations($data, $timeout = 10)
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

    /**
     * @param $arrayAuthId
     * @param $params
     * @return array|null
     * @throws Exception
     */
    public static function rechAffiAut($arrayAuthId, $params)
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

    /**
     * @param $str
     * @return string|string[]|null
     */
    public static function removeCommonWords($str)
    {
        $commonWords = [
            'de', 'du', 'et', 'le', 'la', 'les', 'en', 'laboratoire', 'departement', 'equipe',
            'a', 'from', 'of', 'laboratory', 'department', 'researchteam',
        ];
        return preg_replace('/\b(' . implode('|', $commonWords) . ')\b/', '', $str);
    }

    /**
     * @param $str
     * @return string|string[]|null
     */
    public static function removeBrackets($str)
    {
        return preg_replace('/\(.*\)/', '', $str);
    }

    /**
     * @param $str
     * @return mixed
     */
    public static function cleanStr($str)
    {
        $str = strtolower($str);
        $str = self::removeCommonWords($str);
        $str = self::removeBrackets($str);
        return str_replace(["'", '"', ' ', ','], '', $str);

    }
}
