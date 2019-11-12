<?php

/**
 * API solr
 * @author rtournoy
 *
 */
class SearchController extends Zend_Controller_Action
{
    private $_defaultFilters = [];
    private $_loadDefaultFilters = false;

    public function init()
    {
        $action = $this->getRequest()->getActionName();

        if (in_array($action, ['authorstructure', 'index', 'rss', 'affiliation'])) {

            return $this->_defaultFilters [] = 'status_i:11';
        }

        $webSite = $this->isValidWebsite($action);

        if ($webSite === false) {
            $this->redirect('/docs/search/');
        }
        /* pas forcement un portail */
        Hal_Site::setCurrent($webSite);
        Zend_Registry::set('website', $webSite);
        $this->_loadDefaultFilters = true;
        $this->getRequest()->setActionName('index');


    }

    /**
     *
     * @param string $site
     * @return bool|Hal_Site
     */


    /**
     * Teste si le portail ou la collection existe
     *
     * @param string $site
     * @return bool|false|Hal_Site|string
     * @throws Zend_Exception
     * @todo: a mettre dans Hal_Site... Rien a faire ici!
     */
    private function isValidWebsite($site)
    {

        // detection du portail ou de la collection
        $type = Hal_Site::getTypeFromShortName($site);
        /** @var Zend_Cache_Backend_File $cache */
        $cache = Zend_Registry::get('apicache');


        $cacheName = Ccsd_Tools::cleanFileName($type . '-' . $site);

        $webSite = $cache->load($cacheName);

        /** @var $webSite Hal_Site */
        if ($webSite == false) {
            $webSite = Hal_Site::exist($site, $type, true);
            if (!$webSite) {
                // website or coll 404
                return false;
            }
            $cache->save($webSite, $cacheName);
        }

        $webSite->registerSiteConstants();
        return $webSite;
    }


    /**
     * Récupération des structures d'un auteur ([firstName_t], lastName_t, [email_s])
     * Possibilité de fournir une date (producedDateY_i) permettant de reduire la recherche à Y Possibilité de fournir un ecart permettant d'élargir la recherche à Y ± deviation
     * @throws Zend_Json_Exception
     */
    public function authorstructureAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $params = $this->getRequest()->getParams();
        if (!isset ($params ['lastName_t'])) {
            $this->redirect('/docs/search');
        }

        $outputFormat = $this->getRequest()->getParam('wt', 'json');


        $affiliation = new Hal_Search_Solr_Api_Authorstructure($params);
        $affiliation->outputStructuresList($outputFormat);

    }

    /*
     * Récupération des structures d'un auteur
     */

    public function affiliationAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $params = $this->getRequest()->getParams();
        if (!isset ($params ['lastName_t']) && !isset ($params ['firstName_t'])) { //Champs obligatoires
            $this->redirect('/docs/search');
        }

        //Récupère un tableau avec toutes les informations nécessaires
        $affiliation = Hal_Search_Solr_Search_Affiliation::rechAffiliation($params, true);

        //Tri et formate en XML les affiliations récupérées
        $structIdsOrdered = Hal_Search_Solr_Search_Affiliation::sortAndXMLAffiliation($affiliation, $params);



        // 8 - Création de l'XML final
        $xml = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $xml .= "<response>\n";
        $xml .= '<result name="response" start="0" numFound="' . count($structIdsOrdered) . '">' . "\n";
        foreach ($structIdsOrdered as $struct) {
            $xml .= $struct;
        }
        $xml .= "</result>\n";
        $xml .= "</response>";


        if ($this->getRequest()->getParam('wt', 'json') == 'xml') {
            header('Content-Type: text/xml; charset=utf-8');
            echo $xml;
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo Zend_Json::fromXml($xml);
        }


    }


    /**
     * Recherche API
     * @throws Zend_Date_Exception
     * @throws Zend_Feed_Exception
     */
    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $allowedWt = [
            'json',
            'xml',
            'xml-tei',
            'csv',
            'pdf',
            'bibtex',
            'rss',
            'atom',
            'endnote',
            'rtf'
        ];

        $rawQuery = $_SERVER ['QUERY_STRING'];

        if ($rawQuery == '') {
            $request = Zend_Controller_Front::getInstance()->getRequest();
            $rawQuery = $request->getParams();
            $parsedArray = Hal_Search_Solr_Api::zendUrl2solrUrl($rawQuery, $allowedWt);
        } else {
            $parsedArray = Hal_Search_Solr_Api::phpUrl2solrUrl($rawQuery, $allowedWt);
        }

        $link = "https://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];

        if (in_array('ccsdTag=pdf', $parsedArray)) {
            $key = key(preg_grep('/sort=/', $parsedArray));
            $parsedArray[$key] = 'sort=docType_s+asc,producedDate_tdate+desc';
        }
        $queryString = implode('&', $parsedArray);
        try {
            if (!$this->_loadDefaultFilters && count($this->_defaultFilters)) {
                $queryString .= '&fq=' . implode('&fq=', array_map('urlencode', $this->_defaultFilters));
            }
            $curlResult = Hal_Tools::solrCurl($queryString, 'hal', 'apiselect', $this->_loadDefaultFilters);
        } catch (Exception $e) {
            echo Hal_Search_Solr_Api::formatErrorAsSolr($e->getMessage() . 'See help : /docs', in_array('wt=xml', $parsedArray) ? 'xml' : 'json');
            return false;
        }

        if (!$curlResult) {
            echo Hal_Search_Solr_Api::formatErrorAsSolr('Error. See help : /docs', in_array('wt=xml', $parsedArray) ? 'xml' : 'json');
            exit ();
        }
        // tomcat header en cas d'erreur tomcat
        if (substr($curlResult, 0, 6) == '<html>') {
            echo Hal_Search_Solr_Api::formatErrorAsSolr('Error. See help : /docs', in_array('wt=xml', $parsedArray) ? 'xml' : 'json');
            exit ();
        }

        // Si le paramètre wt=phps existe encore c'est qu'il a été rajouté
        // en vue d'une utilisation interne de l'API après que les
        // paramètres aient été vérifiés dans
        // Hal_Search_Solr_Api::forceUrlParams
        if (in_array('wt=phps', $parsedArray)) {

            switch (true) {

                case (in_array('ccsdTag=xml-tei', $parsedArray)) :
                    echo Hal_Search_Solr_Api::formatOutputAsTei($curlResult, $link);
                    break;

                case (in_array('ccsdTag=pdf', $parsedArray)) :
                    $bibtex = Hal_Search_Solr_Api::formatOutputAsBibtex($curlResult);
                    $pdfOutput = Hal_Search_Solr_Api::formatOuputAsPDF($bibtex);
                    if ($pdfOutput == null) {
                        $pdfOutput = Hal_Search_Solr_Api::formatErrorAsSolr('Error creating PDF output', 'json');
                    }
                    echo $pdfOutput;
                    break;

                case (in_array('ccsdTag=bibtex', $parsedArray)) :
                    echo Hal_Search_Solr_Api::formatOutputAsBibtex($curlResult);
                    break;

                case (in_array('ccsdTag=endnote', $parsedArray)) :
                    echo Hal_Search_Solr_Api::formatOutputAsEndnote($curlResult);
                    break;

                case (in_array('ccsdTag=rss', $parsedArray)) :
                    header('Content-Type: text/xml; charset=utf-8');
                    echo Hal_Search_Solr_Api::formatOutputAsFeed($curlResult, $link, Hal_Search_Solr_Api::FEED_FORMAT_RSS);
                    break;

                case (in_array('ccsdTag=atom', $parsedArray)) :
                    header('Content-Type: text/xml; charset=utf-8');
                    echo Hal_Search_Solr_Api::formatOutputAsFeed($curlResult, $link, Hal_Search_Solr_Api::FEED_FORMAT_ATOM);
                    break;
                case (in_array('ccsdTag=rtf', $parsedArray)) :
                    header('Content-Type: application/rtf; charset=ascii');
                    echo Hal_Search_Solr_Api::formatOutputAsRTF($curlResult);
                    break;

            }
        } else {
            echo $curlResult;
        }
    }

    /**
     * Méthode pour permettre aux anciens HALV2 flux RSS de fonctionner dans HALV3
     */
    public function rssAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $instance = $this->getRequest()->getParam('portail');
        $p ['published'] = $this->getRequest()->getParam('published');

        if ($p ['published'] == null) {
            $p ['from'] = $this->getRequest()->getParam('from', 'daily');
        }

        $p ['format'] = $this->getRequest()->getParam('format', 'fulltext');
        $p ['author'] = $this->getRequest()->getParam('author');
        $p ['lab'] = $this->getRequest()->getParam('lab');
        $p ['researchteam'] = $this->getRequest()->getParam('researchteam');
        $p ['tampon'] = $this->getRequest()->getParam('tampon');
        $p ['type'] = $this->getRequest()->getParam('type');
        $p ['domain'] = $this->getRequest()->getParam('domain');
        $p ['anr'] = $this->getRequest()->getParam('anr');
        $p ['limit'] = $this->getRequest()->getParam('limit', 30);

        $query ['q'] = null;
        $query ['fq'] = null;
        $limit = '';
        $sort = '&sort=submittedDate_tdate+desc';
        $query ['typeDoc'] = [];

        foreach ($p as $pName => $pValue) {

            switch ($pName) {

                case 'published' :
                    if ($pValue != null) {
                        $query ['fq'] [] = 'producedDateY_i:' . urlencode($pValue);
                    }
                    break;

                case 'from' :
                    if ($pValue == 'yesterday') {
                        $query ['fq'] [] = 'releasedDate_tdate:[' . urlencode('NOW/DAY-2DAY TO NOW/DAY+1DAY') . ']';
                    } elseif ($pValue == 'weekly') {
                        $query ['fq'] [] = 'releasedDate_tdate:[' . urlencode('NOW/DAY-7DAY TO NOW/DAY+1DAY') . ']';
                    } elseif ($pValue == 'monthly') {
                        $query ['fq'] [] = 'releasedDate_tdate:[' . urlencode('NOW/DAY-1MONTH TO NOW/DAY+1DAY') . ']';
                    } elseif ($pValue == 'yearly') {
                        $query ['fq'] [] = 'releasedDate_tdate:[' . urlencode('NOW/DAY-1YEAR TO NOW/DAY+1DAY') . ']';
                    } elseif ($pValue == 'all') {
                        // pas de filtre
                    } else {
                        // daily (par défaut) ou autre
                        $query ['fq'] [] = 'releasedDate_tdate:[' . urlencode('NOW/DAY-1DAY TO NOW/DAY+1DAY') . ']';
                    }
                    break;

                case 'format' :
                    if ($pValue == 'fulltext') {
                        $query ['fq'] [] = 'submitType_s:file';
                    }
                    break;

                case 'author' :
                    if ($pValue != null) {
                        $query ['q'] [] = 'authFullName_t:(' . urlencode(str_replace('_', ' ', $pValue)) . ')';
                    }
                    break;

                case 'lab' :
                case 'researchteam' :
                    if ($pValue != null) {
                        $query ['q'] [] = 'structure_t:(' . urlencode($pValue) . ')';
                    }
                    break;

                case 'tampon' :
                    if ($pValue != null) {

                        if (is_array($pValue)) {
                            $query ['q'] [] = 'collCode_s:(' . urlencode(implode(' OR ', $pValue)) . ')';
                        } else {
                            $query ['q'] [] = 'collCode_s:(' . urlencode($pValue) . ')';
                        }
                    }
                    break;

                case 'type' :
                    if ($pValue != null) {

                        if (is_array($pValue)) {
                            foreach ($pValue as $v) {
                                $tmp = $this->convertRssTypeDoc($v);
                                if (is_array($tmp)) {
                                    $query ['typeDoc'] = array_merge($tmp, $query ['typeDoc']);
                                }
                            }
                        } else {

                            $tmp = $this->convertRssTypeDoc($pValue);
                            if (is_array($tmp)) {
                                $query ['typeDoc'] = array_merge($tmp, $query ['typeDoc']);
                            }
                        }
                    }
                    break;

                case 'domain' :
                    if ($pValue != null) {
                        if (is_array($pValue)) {

                            $domains = explode(' OR ', $pValue);
                            $domains = str_replace(':', '.', $domains); // en V2 shs:droit, en V3 shs.droit

                            $query ['q'] [] = 'domain_t:(' . urlencode($domains) . ')';
                        } else {
                            $pValue = str_replace(':', '.', $pValue); // en V2 shs:droit, en V3 shs.droit
                            $query ['q'] [] = 'domain_t:(' . urlencode($pValue) . ')';
                        }
                    }
                    break;

                case 'anr' :
                    if ($pValue != null) {
                        $query ['q'] [] = 'anrProject_t:(' . urlencode($pValue) . ')';
                    }
                    break;

                case 'limit' :
                    if ($pValue != null) {
                        $limit = '&rows=' . urlencode($pValue);
                    }
                    break;

                default :
                    break;
            }
        }

        $q = 'q=';
        $fq = '&fq=';
        $fqTypeDoc = '&fq=';

        if (is_array($query ['q'])) {
            $q .= implode(' AND ', $query ['q']);
        } else {
            $q = 'q=*:*';
        }
        if (is_array($query ['fq'])) {
            $fq .= implode('&fq=', $query ['fq']);
        } else {
            $fq = '';
        }

        if (is_array($query ['typeDoc'])) {
            $fqTypeDoc .= implode(' OR ', $query ['typeDoc']);
        } else {
            $fqTypeDoc = '';
        }

        $query = $q . $fq . $fqTypeDoc . $limit . $sort . '&wt=rss';

        $query = trim(SOLR_API . '/search/' . $instance . '/?' . $query);

        header('HTTP/1.1 301 Moved Permanently');
        header('Via: Halv2 To Rss API');
        header('Location: ' . $query);
        exit ();

        // published année ( au format AAAA), année-année

        // from daily (par défaut), yesterday, weekly, monthly, yearly, all

        // format fulltext (par défaut), withoutfile (accès aux dépôts en notices également) http://hal.archives-ouvertes.fr/rss.php?format=fulltext

        // author chaîne de caractères contenant le nom de l'auteur à rechercher ( nom ou prénom_nom ) http://hal.archives-ouvertes.fr/rss.php?author=alain_martin

        // lab chaîne de caractères contenant le nom (sigle, nom, tutelle) du laboratoire à rechercher http://hal.archives-ouvertes.fr/rss.php?lab=UMR8549

        // researchteam chaîne de caractères contenant le nom de l'équipe de recherche à rechercher http://hal.archives-ouvertes.fr/rss.php?researchteam=AduPRO

        // tampon chaîne de caractères ou tableau contenant les codes des tampons à rechercher http://hal.archives-ouvertes.fr/rss.php?tampon=LKB

        // type de publication chaîne de caractères ou tableau contenant les codes des types de publication à rechercher http://hal.archives-ouvertes.fr/rss.php?type=ART_ACL

        // domain chaîne de caractères ou tableau contenant les codes domaines à rechercher http://hal.archives-ouvertes.fr/rss.php?domain=PHYS

        // anr chaîne de caractères contenant la référence du projet ANR à rechercher http://hal.archives-ouvertes.fr/rss.php?anr=ANR-05-CEXC

        // limit
    }

    /**
     * Convertit les types de doc halv2 => halv3 pour les flux RSS
     *
     * @deprecated uniquement pour la migration des fils RSS
     * @param string $typeDoc
     * @return array
     */
    private function convertRssTypeDoc($typeDoc)
    {
        if ($typeDoc == 'ART_ACL') {
            $query [] = '(docType_s:ART AND peerReviewing_s:1 AND popularLevel_s:0)';
        } else if ($typeDoc == 'ART_SCL') {
            $query [] = '(docType_s:ART AND peerReviewing_s:0 AND popularLevel_s:0)';
        } else if ($typeDoc == 'ART_VULG') {
            $query [] = '(docType_s:ART AND popularLevel_s:1)';
        } else if ($typeDoc == 'PRES_CONF') {
            $query [] = '(docType_s:(POSTER OR PRESCONF))';
        } else if ($typeDoc == 'ART_PRO') {
            $query [] = '(docType_s:ART AND peerReviewing_s:1 AND popularLevel_s:1)';
        } else if ($typeDoc == 'OUVS') {
            $query [] = '(docType_s:OUV AND popularLevel_s:0)';
        } else if ($typeDoc == 'OUV_VULG') {
            $query [] = '(docType_s:OUV AND popularLevel_s:1)';
        } else if ($typeDoc == 'COVS') {
            $query [] = '(docType_s:COUV AND popularLevel_s:0)';
        } else if ($typeDoc == 'COV_VULG' || $typeDoc == 'COVV') {
            $query [] = '(docType_s:COUV AND popularLevel_s:1)';
        } else if ($typeDoc == 'CONF_INV') {
            $query [] = '(docType_s:COMM AND popularLevel_s:0 AND peerReviewing_s:1 AND invitedCommunication:1)';
        } else if ($typeDoc == 'COMM_ACT') {

            $query [] = '(docType_s:COMMAND popularLevel_s:0 AND peerReviewing_s:1 AND proceedings_s:1 AND invitedCommunication:0)';
        } else if ($typeDoc == 'COMM_SACT') {

            $query [] = '(docType_s:COMM AND popularLevel_s:0 AND peerReviewing_s:1 AND proceedings_s:0 AND invitedCommunication:0)';
        } else if ($typeDoc == 'COURS') {

            $query [] = '(docType_s:LECTURE)';
        } else if (in_array($typeDoc, [
            'POSTER',
            'MEM',
            'DOUV',
            'THESE',
            'HDR',
            'ETABTHESE',
            'REPORT',
            'PATENT',
            'IMG',
            'OTHER',
            'UNDEFINED'
        ])) { // type de documents inchangé
            $query [] = '(docType_s:' . $typeDoc . ')';
        } else {
            $query [] = '(docType_s:UNDEFINED)';
        }

        return $query;
    }
}
