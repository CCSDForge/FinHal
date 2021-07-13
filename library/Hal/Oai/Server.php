<?php
/*
 * Configuration du serveur OAI de l'application HAL par rapport à la librairie OAI CCSD
 * + déclaration de l'identité
 * + paramètrage des formats
 * + ajout des collections
 * + Définition de comment retrouve t on les documents
 */

class Hal_Oai_Server extends Ccsd_Oai_Server {

    private $_formats = array('oai_dc'=>'dc', 'oai_dcterms'=>'dcterms', 'xml-tei'=>'tei');
    const LIMIT_IDENTIFIERS = 400;
    const LIMIT_RECORDS = 100;

    protected function getIdentity($url) {
        return array(
            'repositoryName'=>'HAL',
            'baseURL'=>$url,
            'protocolVersion'=>'2.0',
            'adminEmail'=>Hal_Settings::MAIL,
            'earliestDatestamp'=>'2002-09-23',
            'deletedRecord'=>'no',
            'granularity'=>'YYYY-MM-DD',
            'description'=>array(
                'oai-identifier'=>array(
                    'attributes'=>array(
                        'xmlns'=>"http://www.openarchives.org/OAI/2.0/oai-identifier", 'xmlns:xsi'=>"http://www.w3.org/2001/XMLSchema-instance", 'xsi:schemaLocation'=>"http://www.openarchives.org/OAI/2.0/oai-identifier http://www.openarchives.org/OAI/2.0/oai-identifier.xsd"),
                        'nodes'=>array('scheme'=>'oai', 'repositoryIdentifier'=>'hal.archives-ouvertes.fr', 'delimiter'=>':', 'sampleIdentifier'=>'oai:HAL:hal-00000001')),
                'eprints'=>array(
                    'attributes'=>array(
                        'xmlns'=>"http://www.openarchives.org/OAI/1.1/eprints", 'xmlns:xsi'=>"http://www.w3.org/2001/XMLSchema-instance", 'xsi:schemaLocation'=>"http://www.openarchives.org/OAI/1.1/eprints http://www.openarchives.org/OAI/1.1/eprints.xsd"),
                        'nodes'=>array('content'=>array('text'=>'Author self-archived open archive'), 'metadataPolicy'=>array('text'=>'1) No commercial use of the extracted data 2) The source must be cited'), 'dataPolicy'=>array('text'=>'')))
            )
        );
    }

    /*
     * retourne les formats dispo sur le serveur OAI
     * @return array code=>array('schema'=>, 'ns'=>)
     */
    protected function getFormats() {
        return [
            'oai_dc' => [
                'schema'=>'http://www.openarchives.org/OAI/2.0/oai_dc.xsd',
                'ns'=>'http://www.openarchives.org/OAI/2.0/oai_dc/'],
            'oai_dcterms' => [
                'schema'=>'http://dublincore.org/schemas/xmls/qdc/dcterms.xsd',
                'ns'=>'http://purl.org/dc/terms/'],
            'xml-tei' => [
                'schema'=> AOFR_SCHEMA_URL,
                'ns'=>'http://hal.archives-ouvertes.fr/']
        ];
    }

    /*
     * retourne les sets dispo sur le serveur OAI
     * @return array code=>name
     */
    protected function getSets() {
        $cacheName = 'oai-sets-'.Zend_Registry::get('website')->getSid().'.phps';
        if (Hal_Cache::exist($cacheName, 7200)) {
            $out = unserialize(Hal_Cache::get($cacheName));
        } else {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $translator = Hal_Translation_Plugin::checkTranslator('en');
            $out = array('openaire'=>'OpenAIRE set');
            // typdoc
            foreach ( Hal_Settings::getTypdocs(Zend_Registry::get('website')->getSiteName()) as $typdoc ) {
                if ( isset($typdoc['children']) ) {
                    foreach ($typdoc['children'] as $type) {
                        $out['type:'.$type['id']] = $translator->translate($type['label']);
                    }
                } else {
                    if ( isset($typdoc['id']) ) {
                        $out['type:'.$typdoc['id']] = $translator->translate($typdoc['label']);
                    }
                }
            }
            // domaines primaires
            $sql = $db->select()
                ->from(array('dom'=>'REF_DOMAIN'), 'dom.CODE')
                ->joinLeft(array('portailDomain'=>'PORTAIL_DOMAIN'), 'dom.ID=portailDomain.ID')
                ->where('dom.LEVEL = 0')
                ->where('portailDomain.SID = 1');
            foreach ( $db->fetchAll($sql) as $row ) {
                $out['subject:'.$row['CODE']] = $translator->translate('domain_'.$row['CODE']);
            }
            // collections: on enleve les collections explicitement non visible
            $sql = $db->select()
                ->from([ 'site' => Hal_Site::TABLE ], array('SITE', 'NAME'))
                ->joinLeft([ 'setting' => Hal_Site_Settings_Collection::TABLE ] , "site.SID = setting.SID and setting.SETTING='visible'")
                ->where('TYPE = "COLLECTION"')
                ->where('(setting.VALUE != 0 OR setting.VALUE is NULL)')  // Une collection sans setting visible est consideree comme visible
                ->order('DATE_CREATION DESC');
            foreach ( $db->fetchAll($sql) as $row ) {
                $out['collection:'.$row['SITE']] = $row['NAME'];
            }
            Hal_Cache::save($cacheName, serialize($out));
        }
        return $out;
    }

    protected function existId($identifier) {
        // identifier format -> oai:HAL:hal-00000001v2
        $identifier = substr(strrchr($identifier, ":"), 1);
        if ( !preg_match('/^([a-z0-9]+(_|-)[0-9]+)(v([0-9]+))?$/i', $identifier, $match) ) {
            return false;
        }
        $document = Hal_Document::find(0, $match[1], Ccsd_Tools::ifsetor($match[4], 0));
        if ( false === $document ) {
            return false;
        }
        return $document->isOnline();
    }

    protected function existFormat($format) {
        return array_key_exists($format, $this->getFormats());
    }

    protected function existSet($set) {
        return array_key_exists($set, $this->getSets());
    }

    protected function checkDateFormat($date) {
        return (new Zend_Validate_Date(array('format' => 'yyyy-MM-dd')) )->isValid($date);
    }

    protected function getId($identifier, $format) {
        $identifier = substr(strrchr($identifier, ":"), 1);
        if ( !preg_match('/^([a-z0-9]+(_|-)[0-9]+)(v([0-9]+))?$/i', $identifier, $match) ) {
            return false;
        }
        $document = Hal_Document::find(0, $match[1], Ccsd_Tools::ifsetor($match[4], 0));
        if ( false === $document ) {
            return false;
        }
        if ( !array_key_exists($format, $this->_formats) ) {
            return false;
        }
        Hal_Document_Visite::add($document->getDocid(), 0, 'oai');
        return array('header'=>$document->getOaiHeader(), 'metadata'=>$document->get($this->_formats[$format]));
    }

    protected function getIds($method, $format, $until, $from, $set, $token) {
        if ( !in_array($method, array('ListIdentifiers', 'ListRecords')) ) {
            return false;
        }
        //On ajoute les filtres par défaut pour tous les SET sauf DUMAS
        // Attention: SET openaire synonyme de collection:OPENAIRE
        $addDefaultFilter = ('collection:DUMAS' != $set);
        $queryString = "q=*:*";
        $conf = array();
        if ( $token == null ) {
            $conf['cursor'] = 0;
            $conf['format'] = $format;
            $query = '';
            if ( $until != null || $from != null ) {
                $query .= "&fq=modifiedDate_s:".urlencode('['.(($from == null)?"*":'"'.$from.' 00:00:00"')." TO ".(($until == null)?"*":'"'.$until.' 23:59:59"')."]");
            }
            if ( $set != null ) {
                if ( $set == 'openaire' ) {
                    $query .= "&fq=collCode_s:OPENAIRE";
                } else {
                    if ( substr($set,0,5) == 'type:' ) {
                        $query .= "&fq=docType_s:".urlencode(substr($set,5));
                    } else if ( substr($set,0,8) == 'subject:' ) {
                        $query .= "&fq=level0_domain_s:".urlencode(substr($set,8));
                    } else if ( substr($set,0,11) == 'collection:' ) {
                        $query .= "&fq=collCode_s:".urlencode(substr($set,11));
                    }
                }
            }
            $conf['query'] = $query;
            $queryString .= $query;
            $queryString .= "&cursorMark=*";
        } else {
            if ( !Hal_Cache::exist('oai-token-'.md5($token).'.phps', 7200) ) {
                return 'token';
            }
            $conf = unserialize(Hal_Cache::get('oai-token-'.md5($token).'.phps'));
            $format = $conf['format'];
            $queryString .= $conf['query']."&cursorMark=".urlencode($token);
            if (strpos($queryString, 'collCode_s:DUMAS') !== false) {
                $addDefaultFilter = false;
            }



        }
        if ( !array_key_exists($format, $this->_formats) ) {
            return false;
        }
        if ($addDefaultFilter) {
            // filtres par défaut
            $queryString .= Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));
        }
        // maximum de retour
        $queryString .= "&rows=".(( $method == "ListIdentifiers" ) ? Hal_Oai_Server::LIMIT_IDENTIFIERS : Hal_Oai_Server::LIMIT_RECORDS);
        // orderby
        $queryString .= "&sort=docid+desc";
        $queryString .= "&fl=docid&wt=phps";

        $result = unserialize(Ccsd_Tools::solrCurl($queryString));
        if (isset($result['response']) && is_array($result['response']) && isset($result['response']['numFound'])) {
            if ( $result['response']['numFound'] == 0 ) {
                return 0;
            } else {
                if ( isset($result['response']['docs']) && is_array($result['response']['docs']) && isset($result['nextCursorMark']) ) {
                    $out = array();
                    foreach ( $result['response']['docs'] as $docid ) {
                        $document = Hal_Document::find($docid['docid']);
                        if ( false === $document ) {
                            continue;
                        }
                        if ( $method == 'ListIdentifiers' ) {
                            $out[] = $document->getOaiHeader();
                        } else {
                            // TODO faire plus propre c'est juste un hotfix parce que l'on perd le format quand on utilise un resumptionToken
                            $out['metadataPrefix'] = $format;
                            $out[] = array('header'=>$document->getOaiHeader(), 'metadata'=>$document->get($this->_formats[$format]));
                        }
                    }
                    // token
                    if ( $result['response']['numFound'] > (( $method == "ListIdentifiers" ) ? Hal_Oai_Server::LIMIT_IDENTIFIERS : Hal_Oai_Server::LIMIT_RECORDS) ) {
                        if ( $result['nextCursorMark'] == $token ) {
                            // c'est la fin
                            if ( count($out) ) {
                                $out[] = '<resumptionToken completeListSize="' . $result['response']['numFound'] . '" />';
                            } else {
                                return 0;
                            }
                        } else {
                            // attention, Solr nous donne un autre cursor même si la prochaine requete ne remonte plus de résultat
                            if ( $result['response']['numFound'] > ($conf['cursor']+count($out)) ) {
                                $out[] = '<resumptionToken expirationDate="' . gmdate("Y-m-d\TH:i:s\Z", time() + 7200) . '" completeListSize="' . $result['response']['numFound'] . '" cursor="' . $conf['cursor'] . '">' . $result['nextCursorMark'] . '</resumptionToken>';
                                $conf['cursor'] += (($method == "ListIdentifiers") ? Hal_Oai_Server::LIMIT_IDENTIFIERS : Hal_Oai_Server::LIMIT_RECORDS);
                                $conf['solr'] = $queryString;
                                Hal_Cache::save('oai-token-' . md5($result['nextCursorMark']) . '.phps', serialize($conf));
                            } else {
                                $out[] = '<resumptionToken completeListSize="' . $result['response']['numFound'] . '" />';
                            }
                        }
                    }
                    return $out;
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
    }

}