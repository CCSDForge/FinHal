<?php
/*
 * Configuration du serveur OAI de l'application HAL par rapport à la librairie OAI CCSD
 * + déclaration de l'identité
 * + paramètrage des formats
 * + ajout des collections
 * + Définition de comment retrouve t on les documents
 */

/**
 * Class Hal_Oai_Server
 */
class Hal_Oai_Server extends Ccsd_Oai_Server {

    private $_formats = array('oai_dc'=>'dc', 'oai_dcterms'=>'dcterms', 'xml-tei'=>'tei');
    const LIMIT_IDENTIFIERS = 400;
    const LIMIT_RECORDS     = 100;
    const TOKEN_TTL         = 7200;
    const SITE_OAI_SET_TTL         = 7200;
    /**
     * @param $url
     * @return array
     */
    protected function getIdentity($url) {
        $oaiVersion = $this->version;
        switch ($oaiVersion) {
            case "v1": $oaiRepoIdent = "HAL"; break;
            case "v2": $oaiRepoIdent = "hal.archives-ouvertes.fr"; break;
            default :
                Ccsd_Tools::panicMsg(__FILE__,__LINE__, "getOaiIdentifier called with bad oaiversion = $oaiVersion, must be v1 or v2");
                $oaiRepoIdent = "HAL";
        }
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
                        'nodes'=>array('scheme'=>'oai', 'repositoryIdentifier'=>'hal.archives-ouvertes.fr', 'delimiter'=>':', 'sampleIdentifier'=>'oai:' . $oaiRepoIdent . ':hal-00000001')),
                'eprints'=>array(
                    'attributes'=>array(
                        'xmlns'=>"http://www.openarchives.org/OAI/1.1/eprints", 'xmlns:xsi'=>"http://www.w3.org/2001/XMLSchema-instance", 'xsi:schemaLocation'=>"http://www.openarchives.org/OAI/1.1/eprints http://www.openarchives.org/OAI/1.1/eprints.xsd"),
                        'nodes'=>array('content'=>array('text'=>'Author self-archived open archive'), 'metadataPolicy'=>array('text'=>'1) No commercial use of the extracted data 2) The source must be cited'), 'dataPolicy'=>array('text'=>'')))
            )
        );
    }

    /**
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

    /**
     * @param $token
     * @return bool
     */
    protected static function tokenCacheExist($token) {
        return Hal_Cache::exist('oai-token-'.md5($token).'.phps', self::TOKEN_TTL) ;
    }

    /**
     * @param $token
     * @param $conf
     */
    protected static function tokenCacheSave($token, $conf) {
        Hal_Cache::save('oai-token-' . md5($token) . '.phps', serialize($conf));
    }
    /**
     * retourne les sets dispo sur le serveur OAI
     * @return array code=>name
     */
    protected function getSets() {
        $website = Hal_Site::getCurrentPortail();
        $cacheName = 'oai-sets-'. $website->getSid().'.phps';
        if (Hal_Cache::exist($cacheName, self::SITE_OAI_SET_TTL)) {
            $out = unserialize(Hal_Cache::get($cacheName));
        } else {
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $translator = Hal_Translation_Plugin::checkTranslator('en');
            $siteName = $website->getShortname();
            // Un Set special openaire
            $out = [
                'openaire'=> 'OpenAIRE set',
                'oa'      => 'Deposit where a file is on Hal (maybe unavailable because of embargo)'
                ];

            // typdoc
            foreach (Hal_Settings::getTypdocs($siteName) as $typdoc ) {
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

    /**
     * @param $identifier
     * @return bool
     */
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

    /**
     * @param $format
     * @return bool
     */
    protected function existFormat($format) {
        return array_key_exists($format, $this->getFormats());
    }

    /**
     * @param string $set
     * @return bool
     */
    protected function existSet($set) {
        return array_key_exists($set, $this->getSets());
    }

    /**
     * @param string $date
     * @return bool
     */
    protected function checkDateFormat($date) {
        return (new Zend_Validate_Date(array('format' => 'yyyy-MM-dd')) )->isValid($date);
    }

    /**
     * @param string $identifier
     * @param string $format
     * @return array|bool
     */
    protected function getId($identifier, $format) {
        $identifier = substr(strrchr($identifier, ":"), 1);
        if (!preg_match('/^([a-z0-9]+(_|-)[0-9]+)(v([0-9]+))?$/i', $identifier, $match)) {
            return false;
        }
        $document = Hal_Document::find(0, $match[1], Ccsd_Tools::ifsetor($match[4], 0));
        if (false === $document) {
            return false;
        }
        if (!array_key_exists($format, $this->_formats)) {
            return false;
        }
        // On ne log plus les visite Oai
        // Hal_Document_Visite::add($document->getDocid(), 0, 'oai');
        return array('header' => $document->getOaiHeader($this->version), 'metadata' => $document->get($this->_formats[$format]));

    }

    /**
     * @param string $method
     * @param string $until
     * @param string $from
     * @param string $set
     * @return string
     */
    protected function getOAIQuery($method, $until, $from, $set): string
    {
        $addDefaultFilter = ('collection:DUMAS' != $set);
        $query = 'q=*:*';
        if ($until != null || $from != null) {
            $query .= "&fq=modifiedDate_s:" . urlencode('[' . (($from == null) ? "*" : '"' . $from . ' 00:00:00"') . " TO " . (($until == null) ? "*" : '"' . $until . ' 23:59:59"') . "]");
        }
        if ($set != null) {
            if ($set == 'openaire') {
                // Hack pour transformer le nom openaire en la collection OPENAIRE
                $query .= "&fq=collCode_s:OPENAIRE";
            } elseif ($set == 'oa') {
                // On traite le set de l'openarchive: les depots de type 'file'
                // Attention: pour l'instant, les depots sous embargo sont inclus!
                $query .= "&fq=submitType_s:file";
            } else {
                if (substr($set, 0, 5) == 'type:') {
                    $query .= "&fq=docType_s:" . urlencode(substr($set, 5));
                } else if (substr($set, 0, 8) == 'subject:') {
                    $query .= "&fq=level0_domain_s:" . urlencode(substr($set, 8));
                } else if (substr($set, 0, 11) == 'collection:') {
                    $query .= "&fq=collCode_s:" . urlencode(substr($set, 11));
                }
            }
        }
        //if (strpos($query, 'collCode_s:DUMAS') !== false) {
        //    $addDefaultFilter = false;
        //}
        if ($addDefaultFilter) {
            // filtres par défaut
            $query .= Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));
        }
        // maximum de retour
        $query .= "&rows=" . (($method == "ListIdentifiers") ? Hal_Oai_Server::LIMIT_IDENTIFIERS : Hal_Oai_Server::LIMIT_RECORDS);
        // orderby
        $query .= "&sort=docid+desc";
        $query .= "&fl=docid&wt=phps";
        return $query;
    }

    /**'
     * @param $method
     * @param $format
     * @param $until
     * @param $from
     * @param $set
     * @param $token
     * @return array|bool
     * @throws Exception
     */
    protected function getIds($method, $format, $until, $from, $set, $token) {
        if ( !in_array($method, array('ListIdentifiers', 'ListRecords')) ) {
            return false;
        }

        //On ajoute les filtres par défaut pour tous les SET sauf DUMAS
        // Attention: SET openaire synonyme de collection:OPENAIRE
        if ( $token == null ) {
            $conf = array();
            $conf['cursor'] = 0;
            $conf['format'] = $format;
            $conf['deletedDocs'] = 0;
            $query = $this->getOAIQuery($method, $until, $from, $set);
            $conf['query'] = $query;

            $queryString = $query . "&cursorMark=*";
        } else {
            if ( !self::tokenCacheExist($token) ) {
                return 'token';
            }
            $conf = unserialize(Hal_Cache::get('oai-token-'.md5($token).'.phps'));
            $format = $conf['format'];
            $queryString = $conf['query']."&cursorMark=".urlencode($token);
        }
        if ( !array_key_exists($format, $this->_formats) ) {
            return false;
        }
        $result = unserialize(Ccsd_Tools::solrCurl($queryString));

        //Control de reponse
        // TODO Utiliser des Exceptions!!!
        if (!(isset($result['response']) && is_array($result['response']) && isset($result['response']['numFound']))) {
            return false;
        }

        $numFound = $result['response']['numFound'];
        if ( $numFound == 0 ) return 0;

        if (!(isset($result['response']['docs'])
            && is_array($result['response']['docs'])
            && isset($result['nextCursorMark']) )){
            return false;
        }

        // Traitement resultats
        $out = array();
        $nbSolrResult = count($result['response']['docs']);
        foreach ( $result['response']['docs'] as $docid ) {
            $document = Hal_Document::find($docid['docid']);
            if ( false === $document ) {
                $conf['deletedDocs']++;
                continue;
            }
            if ( $method == self::OAI_VERB_LISTIDS) {
                $out[] = $document->getOaiHeader($this->version);
            } else {
                // TODO faire plus propre c'est juste un hotfix parce que l'on perd le format quand on utilise un resumptionToken
                $out['metadataPrefix'] = $format;
                $out[] = [
                    'header'=>$document->getOaiHeader($this->version),
                    'metadata'=>$document->get($this->_formats[$format])
                ];
            }
        }
        // token
        // Attention: out contient moins de document que SolR nous en a donner, ceux absent de Hal ont ete supprime

        // Est-on en mode pagine
        if ( $numFound > self::getOAILimit($method)) {
            // On peut encore donner des resultats
            // attention, Solr nous donne un autre cursor même si la prochaine requete ne remonte plus de résultat
            if ( ($result['nextCursorMark'] == $token ) || $numFound <= ($conf['cursor']+$nbSolrResult) ) {
                // c'est la fin
                if ( count($out) ) {
                    $totalDocs = $numFound - $conf['deletedDocs'];
                    $out[] = '<resumptionToken completeListSize="' . $totalDocs . '" />';
                } else {
                    // Ne devrait pas se produire sauf si tous les derniers document sont absents de Hal et ont ete supprimes
                    return 0;
                }
            } else {
                $out[] = '<resumptionToken expirationDate="' . gmdate("Y-m-d\TH:i:s\Z", time() + self::TOKEN_TTL) . '" completeListSize="' . $numFound . '" cursor="' . $conf['cursor'] . '">' . $result['nextCursorMark'] . '</resumptionToken>';
                $conf['cursor'] += self::getOAILimit($method);
                $conf['solr'] = $queryString;
                self::tokenCacheSave($result['nextCursorMark'], $conf);
            }
        }
        return $out;
    }
    /**
     * @param string $method
     * @return int
     */
    static public function getOAILimit($method) {
        return ($method == self::OAI_VERB_LISTIDS ? self::LIMIT_IDENTIFIERS : self::LIMIT_RECORDS) ;
    }
}