<?php

/**
 * Hal Recherche
 *
 * @author
 * @version
 */
class SearchController extends Hal_Controller_Action
{

    const CACHE_LIFETIME = 3600;

    static $_defaultExportedFields = "halId_s,version_i,uri_s,docType_s,doiId_s,nntId_s,title_s,subTitle_s,authFullName_s,producedDate_s,domain_s,journalTitle_s,journalPublisher_s,volume_s,number_s,page_s,conferenceTitle_s,conferenceStartDate_s,country_s,language_s,inPress_bool";

    /**
     * @param $authors
     * @return mixed
     */
    static public function sortAuthorValues($a, $b)
    {
        // On veut trier par ordre alphabétique du nom de famille.
        // Ce n'est pas idéal. On considère que le nom de famille est le denier mot... c'est pas du 100%

        $alasts = explode(" ", $a);
        $aLastName = array_pop($alasts);
        $blasts = explode(" ", $b);
        $bLastName = array_pop($blasts);

        if (isset($aLastName) && isset($bLastName)) {
            return strcasecmp($aLastName, $bLastName);
        }
        return 0;
    }

    // Todo: A mettre ailleurs que dans le controlleur

    public function init()
    {
        Hal_Search_Solr_Search::initSolrEndpoints();
    }

    /**
     * Export avancé
     */
    public function advancedExportAction()
    {
        $f = new Ccsd_Form([
            'name' => 'bringData',
            'action' => $this->view->url([
                'controller' => 'search',
                'action' => 'ajaxadvancedexport'
            ])
        ]);

        $f->addElement('hidden', 'uri', [
            'value' => $this->getRequest()->getParam('uri')
        ]);

        $f->addElement('select', 'wt', [
                'required' => true,
                'label' => "Format d'export",
                'description' => "Choisissez en premier votre format d'export",
                'class' => '',
                'value' => '',
                'multioptions' => [
                    'csv' => 'CSV',
                    'xml' => 'XML',
                    'json' => 'JSON'
                ]
            ]
        );

        $htmlOfMultisortable_available = '';
        $this->view->defaultExportedFields = explode(',', static::$_defaultExportedFields);

        foreach ($this->getExportMetadataFields() as $htmlId => $field) {
            if (!in_array($htmlId, $this->view->defaultExportedFields)) {
                $htmlOfMultisortable_available .= PHP_EOL . '<li id="' . $htmlId . '"><span class="glyphicon glyphicon-move" >&nbsp;</span>' . $field . '</li>';
            }
        }
        $f->addElement(new Zend_Form_Element_Text('multisortableFields_available', [
            'value' => $htmlOfMultisortable_available,
            'helper' => 'formNote',
            'description' => '<span class="help-block">' . $this->view->translate("Faites glissez les champs à exporter dans le cadre <span style=\"font-style:normal;\">Champs sélectionnés pour l'export</span>") . '</span>'
        ]));

        $f->addElement('submit', 'export', [
            'label' => "Préparer l'export",
            'class' => "btn btn-primary"
        ]);
        $this->view->form = $f;
    }
    /**
     * @param string $field
     * @param string $default
     * @param string $byLang // le code langue sur 2 lettres
     * @return string
     */
    // Todo: A mettre ailleurs que dans le controlleur
    /**
     * Liste des champs qui peuvent être exportés
     * @return array
     */
    public function getExportMetadataFields()
    {
        $cacheName = Hal_Cache::makeCacheFileName('getExportMetadataFields', true);

        if (Hal_Cache::exist($cacheName, static::CACHE_LIFETIME)) {
            return unserialize(Hal_Cache::get($cacheName));
        }

        $rhey = [];

        $sc = new Ccsd_Search_Solr_Schema([
            'env' => APPLICATION_ENV,
            'core' => 'hal',
            'handler' => 'schema'
        ]);

        $sc->getSchemaFieldsByType([
            'string',
            'tdate',
            'int',
            'tint',
            'double',
            'location'
        ]);

        foreach ($sc->getFields() as $field) {
            $rhey [$field] = $this->get_translated_field('hal_' . $field, $field);
        }

        $sc->getSchemaDynamicFields();
        foreach ($sc->getDynamicFields() as $dfield) {
            $dfield = (array)$dfield;

            if ($dfield ['type'] == 'string') {
                switch ($dfield ['name']) {
                    case '*_subTitle_s' :
                    case '*_title_s' :
                    case '*_abstract_s' :
                    case '*_keyword_s' :
                        // Champs avec langue!
                        foreach ($dfield ['fieldList'] as $fieldList) {
                            // Can't use get_translated_field
                            $langCode = $this->get_lang_code($fieldList);
                            if ($langCode !== false) {
                                $rhey [$fieldList] = $this->get_translated_field(
                                    'hal_' . $this->getFieldNameWithoutStar($dfield ['name']),
                                    $fieldList,
                                    'lang_' . $langCode // pour recuperer la langue
                                );
                            }
                            //if ($this->view->translate()->getTranslator()->isTranslated('hal_' . substr($dfield ['name'], 2))) {
                            //    $rhey [$fieldList] = $this->view->translate('hal_' . substr($dfield ['name'], 2)) .
                            //        ' ' . $this->view->translate('en') .
                            //        ' ' . $this->view->translate('lang_' .
                            //           substr($fieldList, 0, 2));
                            //} else {
                            //    $rhey [$fieldList] = $fieldList;
                            //}
                        }
                        break;
                    default :
                        foreach ($dfield ['fieldList'] as $fieldList) {
                            if (preg_match('/^[0-9]+_s$/',$fieldList )) {
                                // champs 0_s,... 7_s !!! on supprime de la sortie...
                            } else {
                                $rhey [$fieldList] = $this->get_translated_field('hal_' . $fieldList, $fieldList);
                            }
                        }
                        break;
                }
            }
        }
        uasort($rhey, 'strcoll');
        Hal_Cache::save($cacheName, serialize($rhey));

        return $rhey;
    }

    /**
     * @param string $field
     * @param string $default
     * @param string $byLang
     * @return string|Zend_View_Helper_Translate
     */
    public function get_translated_field($field, $default, $byLang = '')
    {
        if ($this->view->translate()->getTranslator()->isTranslated($field)) {
            if ($byLang != '') {
                return $this->view->translate($field) .
                    ' ' . $this->view->translate('en') .
                    ' ' . $this->view->translate($byLang);
            } else {
                return $this->view->translate($field);
            }
        } else {
            return $default;
        }
    }

    /**
     * Suppress the *_ at beginning of solr fieldname
     * @param $fieldname
     * @return bool|string
     */
    public function getFieldNameWithoutStar($fieldname)
    {
        return substr($fieldname, 2);
    }

    /**
     * Les champs dynamique de Solr sont prefixes par la langue xx_
     * Si pas xx_ alors ce n'est pas un champs de langue, on rends false.
     * @param $lang
     * @return bool|string
     */
    public function get_lang_code($lang)
    {
        /* Le field contenant la langue est XXxxx_fieldname */
        if (preg_match('/^[a-z][a-z]_/i', $lang)) {
            return strtolower(substr($lang, 0, 2));
        } else {
            return false;
        }
    }

    /**
     */
    public function ajaxadvancedexportAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $uri = explode('&', $this->getRequest()->getParam('uri'));
        $fl = $this->getRequest()->getParam('fl');

        if ($fl == null) {
            $uri [] = 'fl=' . static::$_defaultExportedFields;
        } else {
            $uri [] = 'fl=' . urlencode($fl);
        }

        $uri [] = 'sort=' . urlencode($this->getRequest()->getParam('sort', 'score desc'));

        $uri = str_replace([
            'wt=phps'
        ], 'wt=' . urlencode($this->getRequest()->getParam('wt', 'json')), $uri);

        $queryString = implode('&', $uri);

        $fullQuery = SOLR_API . '/search/' . SPACE_NAME . '/' . $queryString;
        $fullQueryEscaped = SOLR_API . '/search/' . SPACE_NAME . '/' . htmlspecialchars($queryString, ENT_QUOTES);

        $curlRes = curl_init();
        curl_setopt($curlRes, CURLOPT_USERAGENT, $this->getRequest()->getActionName());
        curl_setopt($curlRes, CURLOPT_URL, $fullQuery);
        curl_setopt($curlRes, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlRes, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlRes, CURLOPT_TIMEOUT, 60); // timeout in seconds

        $data = curl_exec($curlRes);

        if (curl_errno($curlRes) != CURLE_OK) {
            // échec écriture du fichier d'export
            echo json_encode([
                'url' => $fullQueryEscaped
            ]);
            return;
        }

        switch ($this->getRequest()->getParam('wt', 'json')) {
            default :
            case 'xml' :
            case 'xml-tei' :
                $fileExt = 'xml';
                break;

            case 'pdf' :
                $fileExt = 'pdf';
                break;

            case 'bibtex' :
                $fileExt = 'bib';
                break;

            case 'csv' :
                $fileExt = 'csv';
                break;

            case 'endnote' :
                $fileExt = 'enw';
                break;

            case 'json' :
                $fileExt = 'json';
                break;
        }

        $export = new Hal_Export_SearchAPI($data, '', $fileExt);
        $link2file = $export->exportAsDownload();

        echo Zend_Json::encode(['url' => $fullQueryEscaped, 'file' => $link2file]);
    }

    /**
     * Export des documents XML en fonction des docid passés en paramètre
     */
    public function exportAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $listOfDocid = $this->_getParam('docid');
        $format = $this->_getParam('format');

        $arrayOfDocid = explode(',', $listOfDocid);

        // <maxBooleanClauses>1024</maxBooleanClauses> @see
        // https://wiki.apache.org/solr/SolrConfigXml#The_Query_Section
        $arrayOfDocid = array_slice($arrayOfDocid, 0, 1024);
        $arrayOfDocid = array_filter($arrayOfDocid, 'ctype_digit');

        $querOfDocid = implode(' OR ', $arrayOfDocid);

        $q = '?df=docid&q=' . urlencode($querOfDocid);
        $fileExt = 'xml';
        switch ($format) {
            // default :
            case 'xml-tei' :
                $mimeType = 'application/xml';
                $fileExt = 'xml';
                $q .= '&wt=xml-tei';
                break;

            case 'pdf' :
                $mimeType = 'application/pdf';
                $fileExt = 'pdf';
                $q .= '&wt=pdf';
                break;

            case 'rtf' :
                $mimeType = 'application/rtf';
                $fileExt = 'rtf';
                $q .= '&wt=rtf';
                break;

            case 'bibtex' :
                $mimeType = 'application/text';
                $fileExt = 'bib';
                $q .= '&wt=bibtex';
                break;

            case 'csv' :
                $mimeType = 'application/text';
                $fileExt = 'csv';
                $q .= '&wt=csv';
                break;

            case 'endnote' :
                $mimeType = 'application/text';
                $fileExt = 'enw';
                $q .= '&wt=endnote';
                break;

            case 'advanced' :
                $q .= '&wt=phps';
                $url = $this->view->url([
                    'controller' => 'search',
                    'action' => 'advanced-export',
                    'uri' => $q
                ]);
                $this->redirect($url);
                return;
                break;
        }

        // <maxBooleanClauses>1024</maxBooleanClauses> @see
        // https://wiki.apache.org/solr/SolrConfigXml#The_Query_Section
        $q .= '&rows=1024';

        $queryUrl = SOLR_API . '/search/index/' . $q;

        $tuCurl = curl_init();
        curl_setopt($tuCurl, CURLOPT_URL, $queryUrl);
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($tuCurl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($tuCurl, CURLOPT_TIMEOUT, 60); // timeout in seconds

        $data = curl_exec($tuCurl);

        if (curl_errno($tuCurl) != CURLE_OK) {
            return null;
        }

        if ($this->getRequest()->isGet()) {
            $export = new Hal_Export_SearchAPI($data, '', $fileExt);
            $xmlData = $export->exportAsAttachment();

            $this->getResponse()->clearAllHeaders()->setHeader("Pragma", "public", true)->setHeader('Cache-control', 'must-revalidate, post-check=0, pre-check=0', true)->setHeader('Cache-control', 'private')->setHeader('Expires', '0', true)->setHeader('Content-Type', $mimeType)->setHeader('Content-Disposition', 'attachment; filename=' . basename($export->getFilename()))->setBody($xmlData);
        }

        return;
    }

    public function ajaxsaveAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();

        if ($request->isPost()) {
            $params = $request->getPost();
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $result = $db->insert('USER_SEARCH', [
                'UID' => Hal_Auth::getUid(),
                'LIB' => htmlspecialchars($params ['lib']),
                'URL' => htmlspecialchars($params ['url']),
                'URL_API' => htmlspecialchars($params ['url_api']),
                'SID' => SITEID
            ]);

            echo $result ? 'ok' : 'ko';
        }
    }

    /**
     * Retourne des suggestion de termes pour l'auto-complétion
     * @return void
     */
    public function ajaxautocompleteAction()
    {


        // disable view and layout
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return;
        }

        $q = $this->_getParam('term');

        if ($q == null) {
            return;
        }

        $q = strtolower($q);

        $facetMetaData [] = [
            'solr_index_name' => 'auth_autocomplete',
            'category_name' => $this->view->translate('Auteur')
        ];
        $facetMetaData [] = [
            'solr_index_name' => 'title_autocomplete',
            'category_name' => $this->view->translate('Titre')
        ];
        $facetMetaData [] = [
            'solr_index_name' => 'keyword_autocomplete',
            'category_name' => $this->view->translate('Mot-Clé')
        ];
        $facetMetaData [] = [
            'solr_index_name' => 'identifiers_id',
            'category_name' => $this->view->translate('Identifiant')
        ];

        // create a client instance

        $client = new Solarium\Client(Zend_Registry::get('solrEndpoints'));
        $query = $client->createSelect();
        $query->setResponseWriter('phps');
        $query->setOmitHeader(false);

        // doit être égal au nombre de facettes demandées
        $query->addParam('facet.threads', 4);
        // @see https://cwiki.apache.org/confluence/display/solr/Faceting#Faceting-Thefacet.methodParameter
        $query->addParam('facet.method', 'enum');

        $search = new Hal_Search_Solr_Search ();

        $search->setQuery($query);

        $search->queryAddDefaultFilters(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));

        $query = $search->getQuery();

        $facetSet = $query->getFacetSet();

        foreach ($facetMetaData as $meta) {
            $facetSet->createFacetField($meta ['solr_index_name'])->setField($meta ['solr_index_name'])->setLimit(5)->setMincount(1)->setPrefix($q);
        }

        // set a query
        $query->setQuery('*:*');
        $query->setRows(0);

        /**
         * Garder Pour debug, comment afficher la requête envoyée à solr
         */
        /**        $request = $client->createRequest($query);
         *         echo PHP_EOL . '<pre>';
         *        print_r(urldecode($request->__toString()));
         *         echo PHP_EOL . '</pre>' . PHP_EOL;
         */
        /**
         * Pour debug, comment afficher la requête envoyée à solr //
         */
        try {
            $resultset = $client->select($query);
        } catch (Exception $e) {
            return;
        }

        if ($resultset != null) {
            $i = 0;
            $valuesArray = [];

            foreach ($facetMetaData as $meta) {
                $values = '';
                $values = $resultset->getFacetSet()->getFacet($meta ['solr_index_name'])->getValues();

                if (is_array($values)) {

                    foreach ($values as $label => $nombre) {
                        $valuesArray [$i] ['label'] = Ccsd_Tools::truncate($label, 60);
                        $valuesArray [$i] ['count'] = $nombre;
                        $valuesArray [$i] ['category'] = $this->view->translate($meta ['category_name']);
                        $i++;
                    }
                }
            }
        }

        if (empty($valuesArray)) {
            return;
        }

        $this->_helper->json($valuesArray);
    }

    /**
     * Création des filtres pour le formulaire de recherche initial
     *
     * @return void
     */
    public function ajaxfiltersAction()
    {
        $this->_helper->layout->disableLayout();
        if (!$this->getRequest()->isXmlHttpRequest()) {
            return;
        }

        $this->view->search = new Hal_Website_Search();
    }

    /**
     * Recherche
     *
     * @return void boolean
     */
    public function indexAction()
    {
        $q = null;

        $form = new Ccsd_Search_Solr_Form_Search ();

        $search = new Ccsd_Search_Solr_Search ();
        $this->view->formAdvanced = Hal_Search_Solr_Search::getFormSearchAdvanced();

        $this->view->formAdvanced->setAction($this->view->url(['controller' => 'search', 'action' => 'index'], null, true));

        $this->view->paginatorNumberOfResultsArray = Hal_Settings_Search::$_numberSearchResultsArray;
        $this->view->paginatordefaultNumberOfResults = Hal_Settings_Search::DEFAULT_NUMBER_SEARCH_RESULTS;

        /**
         * traitement formulaires
         */
        if (($this->getRequest()->getParam('q') == null) && ($this->getRequest()->getParam('qa') == null)) {
            $this->view->searchType = 'simple';
            $this->view->formAdvanced->setAttrib('style', 'display:none;');
            return;
        }

        // recherche simple
        if ($form->isValid($this->getRequest()->getParams())) {

            $this->view->searchType = 'simple';
            $this->view->formAdvanced->setAttrib('style', 'display:none;margin-right:20px');

            $q = $form->getValue('q');

            // recherche simple
            $search->setRawSearchParams($this->getRequest()->getParams());

            $search->setParsedSearchParamsbyKey('q', $q);
        }

        // recherche avancée
        if (($this->view->formAdvanced->isValid($this->getRequest()->getParams())) && ($this->getRequest()->getParam('qa') != null)) {

            $this->view->searchType = 'advanced';
            $this->view->formAdvanced->setAttrib('style', 'display:block;margin-right:20px');

            $search->setRawSearchParams($this->getRequest()->getParams());
            $q = $search->queryParseAdvancedSearch();
        }

        if ($q == null) {
            return;
        }

        /**
         * traitement formulaires //
         */
        /**
         * Pagination
         */
        $currentPage = $this->_getParam('page', 1);
        $startParam = ($currentPage - 1) * $search->getParsedSearchParamsbyKey('rows');

        /**
         * Pagination //
         */
        /**
         * Préparation requête
         */
        // create a client instance
        $client = new Solarium\Client(Zend_Registry::get('solrEndpoints'));

        // get a select query instance
        /** @var \Solarium\QueryType\Select\Query\Query $query */
        $query = $client->createSelect()->setOmitHeader(true)->setResponseWriter('phps')->setQuery($q)->setStart($startParam)->setRows($search->getParsedSearchParamsbyKey('rows'));

        $search->setQuery($query);

        // get the dismax query parser
        $query->getDisMax()->setQueryParser('edismax');

        $search->setParsedSearchParamsbyKey('controller', 'search')->setParsedSearchParamsbyKey('action', 'index');

        // pré-filtre de la recherche
        if (is_array($search->getRawSearchParamsbyKey('docType_s'))) {
            $search->setRawSearchParamsbyKey('docType_s', implode(' OR ', $search->getRawSearchParamsbyKey('docType_s')));
        }

        // pré-filtre de la recherche
        if (is_array($search->getRawSearchParamsbyKey('submitType_s'))) {
            $search->setRawSearchParamsbyKey('submitType_s', implode(' OR ', $search->getRawSearchParamsbyKey('submitType_s')));
        }

        $search->queryAddSort();
        $search->queryAddDefaultFilters(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));

        $search->queryAddFilters();

        $query = $search->getQuery();

        $request = $client->createRequest($query);

        // récupère l'URI de la requête sans les facettes pour les exports
        // aussi pour enregistrer les recherches utilisateur
        // optionnellement on pourrait laisser les facettes à l'export
        $searchUri = $request->getUri();

        $searchUri = str_replace('select?', '?', $searchUri);
        $searchUri = str_replace('&rows=' . $search->getParsedSearchParamsbyKey('rows'), '', $searchUri);
        $searchUri = str_replace('&start=' . $startParam, '', $searchUri);

        $mySearch = new Hal_User_Search(['url' => $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . $this->getRequest()->getRequestUri()]);

        $this->view->mySearch = $mySearch;

        // par défaut c'est '*,score' qui est retourné
        $searchUri = str_replace('&fl=%2A%2Cscore', '', $searchUri);
        $exportUriList = [];
        $this->view->mySearch->setUrl_api($searchUri);

        $beginUrl = SOLR_API . '/search/' . SPACE_NAME . '/';

        $exportUriList ['rss'] = $beginUrl . str_replace('wt=phps', 'wt=rss', $searchUri . '&rows=' . Hal_Search_Solr_Api::MAX_EXPORT_ROWS_FEEDS);
        $exportUriList ['atom'] = $beginUrl . str_replace('wt=phps', 'wt=atom', $searchUri . '&rows=' . Hal_Search_Solr_Api::MAX_EXPORT_ROWS_FEEDS);

        $searchUri .= '&rows=' . Hal_Search_Solr_Api::MAX_EXPORT_ROWS;
        $exportUriList ['phps'] = $searchUri . '&fl=docid';
        $exportUriList ['csv'] = $beginUrl . str_replace('wt=phps', 'wt=csv', $searchUri);
        $exportUriList ['pdf'] = $beginUrl . str_replace('wt=phps', 'wt=pdf', $searchUri);
        $exportUriList ['xmltei'] = $beginUrl . str_replace('wt=phps', 'wt=xml-tei', $searchUri);
        $exportUriList ['bibtex'] = $beginUrl . str_replace('wt=phps', 'wt=bibtex', $searchUri);
        $exportUriList ['endnote'] = $beginUrl . str_replace('wt=phps', 'wt=endnote', $searchUri);
        $exportUriList ['rtf'] = $beginUrl . str_replace('wt=phps', 'wt=rtf', $searchUri);
        $exportUriList ['advanced'] = PREFIX_URL . 'search/advanced-export/uri/' . rawurlencode($searchUri);

        $this->view->exportUriList = $exportUriList;

        $query->setFields(Hal_Settings::getConfigFile('solr.hal.returnedFields.json'));
        $search->setQuery($query);
        $search->queryAddFacets(Hal_Settings::getConfigFile('solr.hal.facets.json'))->queryAddResultPerPage(Hal_Settings_Search::$_numberSearchResultsArray, Hal_Settings_Search::DEFAULT_NUMBER_SEARCH_RESULTS);

        $query = $search->getQuery();

        /**       $requestA = $client->createRequest($query);
         *        echo PHP_EOL . '<pre>';
         *        print_r(urldecode($requestA->__toString()));
         *        echo PHP_EOL . '</pre>' . PHP_EOL;
         */

        /**
         * Ajout des filtres en cours au formulaire de recherche
         */
        $parsedSearchParams = $search->getParsedSearchParams();
        if (is_array($parsedSearchParams)) {
            unset($parsedSearchParams ['controller'], $parsedSearchParams ['action'], $parsedSearchParams ['q'], $parsedSearchParams ['qa']);

            foreach ($parsedSearchParams as $elementName => $elementValue) {

                $elementName = $this->view->escape($elementName);
                $elementValue = $this->view->escape($elementValue);

                try {
                    $form->addElement('hidden', $elementName, ['value' => $elementValue]);
                } catch (Exception $e) {
                    // Mauvaise URL: ex: l'elementName est invalide, on ignore le parametre
                }
                $this->view->formAdvanced->addElement('hidden', $elementName, ['value' => $elementValue]);
            }
        }

        /**
         * // Ajout des filtres en cours au formulaire de recherche
         */
        /**
         * // Préparation requête
         */
        // paramètres de recherche pour les vues
        $this->view->parsedSearchParams = $search->getParsedSearchParams();

        $this->view->activeFilters = Hal_Search_Solr_Search::buildActiveFiltersUrl($search->getParsedFilterParams(), $search->getParsedSearchParams());

        $this->view->paginatorNumberOfResultsPerPage = $search->getParsedSearchParamsbyKey('rows');

        /**
         * Pagination
         */
        $adapter = new Ccsd_Paginator_Adapter_Solarium($client, $query);
        $paginator = new Zend_Paginator($adapter);
        $paginator->setItemCountPerPage($this->view->paginatorNumberOfResultsPerPage);
        $paginator->setCurrentPageNumber($currentPage);

        $this->view->paginator = $paginator;
        Zend_Paginator::setDefaultScrollingStyle(Hal_Settings_Search::PAGINATOR_STYLE);
        Zend_View_Helper_PaginationControl::setDefaultViewPartial('partial/search_pagination.phtml');

        /**
         * // Pagination//
         */
        try {
            /** @var  \Solarium\QueryType\Select\Result\Result $resultset */
            $resultset = $paginator->getCurrentItems();
        } catch (Exception $e) {

            error_log($e->getMessage() . '. Query: ' . $searchUri);

            $message = Ccsd_Search_Solr_Search::parseSolrError($e);
            $message = $this->view->translate($message);
            $newSearchUrl = $this->view->url(['controller' => 'search']);

            $newSearch = '<li><a class="btn btn-default btn-xs" href="' . $this->view->escape($newSearchUrl) . '"><span class="glyphicon glyphicon-remove"></span>'
                . $this->view->translate('Nouvelle recherche') . '</a></li>';

            $reTrySearchUrl = $this->view->url($parsedSearchParams);
            $reTrySearch = '<li><a class="btn btn-default btn-xs" href="' . $this->view->escape($reTrySearchUrl) . '"><span class="glyphicon glyphicon-refresh"></span>'
                . $this->view->translate('Recommencer la Recherche') . '</a></li>';

            $message .= '<ul>';

            $message .= $reTrySearch;
            $message .= $newSearch;

            $message .= '</ul>';

            $this->_helper->FlashMessenger->setNamespace('warning')->addMessage($message);

            if (APPLICATION_ENV == 'development') {
                echo '<pre>Code :' . $e->getCode();
                echo '<br>Message : ';
                echo htmlspecialchars($e->getMessage());
                echo '</pre>';
            }

            return;
        }


        if ($resultset != null) {

            /**
             * Données pour les facettes
             *
             * @see solr.hal.json
             */
            $allFacetsArray = [];
            $facetsArr = Hal_Settings::getConfigFile('solr.hal.facets.json');
            if (is_array($facetsArr)) {
                $indexOfArray = 0;
                foreach ($facetsArr as $facet) {

                    $allFacetsArray [$indexOfArray] ['fieldName'] = $facet ['fieldName'];
                    $allFacetsArray [$indexOfArray] ['displayName'] = $facet ['displayName'];
                    $allFacetsArray [$indexOfArray] ['displayFilter'] = $facet ['displayFilter'];
                    $allFacetsArray [$indexOfArray] ['searchMapping'] = $facet ['searchMapping'];
                    $allFacetsArray [$indexOfArray] ['hasSepInValue'] = $facet ['hasSepInValue'];
                    $allFacetsArray [$indexOfArray] ['closed'] = isset($facet ['closed']) ? $facet ['closed'] : false;
                    $allFacetsArray [$indexOfArray] ['translated'] = $facet ['translated'];
                    $allFacetsArray [$indexOfArray] ['translationPrefix'] = $facet ['translationPrefix'];

                    if ($facet['fieldName'] == "authIdHalFullName_fs") {
                        // On tri par nom de famille pour le cas des auteurs
                        $values = $resultset->getFacetSet()->getFacet($facet ['fieldName'])->getValues();
                        uksort($values, "SearchController::sortAuthorValues");
                        $allFacetsArray [$indexOfArray] ['values'] = $values;
                    } else {
                        $allFacetsArray [$indexOfArray] ['values'] = $resultset->getFacetSet()->getFacet($facet ['fieldName'])->getValues();
                    }

                    $indexOfArray++;
                }
                unset($indexOfArray);
            }

            $this->view->facetsArray = $allFacetsArray;

            /**
             * Données pour les facettes//
             */
            // nombre de résultats
            $this->view->numFound = $resultset->getNumFound();

            //instance de l'appli
            $oInstance = Hal_Instance::getInstance('');
            $this->view->instance = $oInstance->getName();

            // résultats
            $this->view->results = $resultset;
        } // if $resultset
    }

// action index

    /**
     * Opensearch
     */
    public function opensearchAction()
    {
        header('Content-Type: application/opensearchdescription+xml');
        $this->_helper->layout->disableLayout();
        $url = Zend_Registry::get('website')->getUrl();
        $this->view->ShortName = ucfirst(Zend_Registry::get('website')->getSiteName());
        $this->view->siteUrl = $url;
        $this->view->Description = $url;
    }

    /**
     * Liste les filtres en cours pour la recherche consultation
     *
     * @return array
     */
    public function filtersAction()
    {
        if ((defined('MODULE')) && defined('SPACE_COLLECTION') && defined('SPACE_NAME')) {
            if (MODULE == SPACE_COLLECTION) {
                return $this->view->filters = ['collCode_s:' . strtoupper(SPACE_NAME)];
            } else {
                // portail
                return $this->view->filters = array_merge(['NOT status_i:111'], Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));
            }
        }
        return [];
    }

}

//end class
