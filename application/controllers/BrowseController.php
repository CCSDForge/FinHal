<?php

class BrowseController extends Hal_Controller_Action
{

    /**
     * durée de vide du cache
     */
    const BROWSE_CACHE_LIFETIME = 1;

    protected $_meta = '';

    public function init()
    {

        $action = $this->getRequest()->getActionName();

        switch ($action) {
            // browse by structure type
            case Ccsd_Referentiels_Structure::TYPE_INSTITUTION: //break omitted
            case Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION://break omitted
            case Ccsd_Referentiels_Structure::TYPE_LABORATORY://break omitted
            case Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY://break omitted
            case Ccsd_Referentiels_Structure::TYPE_DEPARTMENT://break omitted
            case Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM://break omitted
                // browse by structure type linked to another structure
            case Ccsd_Referentiels_Structure::TYPE_INSTITUTION . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX: //break omitted
            case Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX://break omitted
            case Ccsd_Referentiels_Structure::TYPE_LABORATORY . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX://break omitted
            case Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX://break omitted
            case Ccsd_Referentiels_Structure::TYPE_DEPARTMENT . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX://break omitted
            case Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX://break omitted
                // browse all structure type
            case 'structure' . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX:
                $this->forward('structure');
                break;
            default:
                break;
        }

        if ((!method_exists($this, $this->getFrontController()->getDispatcher()->formatActionName($action))) && (substr($action, 0, 4) == 'meta')) {
            // Affichage par une métadonnée
            $this->_meta = str_replace('meta-', '', $action);
            $this->getRequest()->setActionName('meta');

        }

    }

    /**
     * Parcours par structures
     */
    public function structureAction()
    {


        $currentAction = $this->getRequest()->getUserParam('action');

        $page = new Hal_Website_Navigation_Page_Structure ();
        $page->setField($currentAction);
        $page->setAction($currentAction);


        $page->load();
        $structuresAsFilters = $page->getFilter();
        $structureType = '';
        $facetField = '';

        $letter = $this->getParam('letter', 'all');
        $typeFilter = $this->getParam('submitType_s');
        $sort = $this->getParam('sort', $page->getSort());

        if (!self::isValidSortParameter($sort) || !self::isValidLetterParameter($letter) || !self::isValidSubmitTypeParameter($typeFilter)) {
            $this->view->message = "Erreur de paramètre";
            $this->view->description = "Un des paramètres utilisé n'est pas valide";
            $this->renderScript('error/error.phtml');
            return;
        }


        $this->view->action = $currentAction;

        switch ($currentAction) {
            // ------------------------------------------------------------------
            case 'structure' . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX :
                $this->view->urlFilterName = 'structName_s';
                $structureType = 'all';
                break;
            case Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX :
                $this->view->urlFilterName = 'rgrpInstStructName_s';
                $structureType = Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION;
                break;
            case Ccsd_Referentiels_Structure::TYPE_INSTITUTION . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX :
                $this->view->urlFilterName = 'instStructName_s';
                $structureType = Ccsd_Referentiels_Structure::TYPE_INSTITUTION;
                break;
            case Ccsd_Referentiels_Structure::TYPE_DEPARTMENT . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX :
                $this->view->urlFilterName = 'deptStructName_s';
                $structureType = Ccsd_Referentiels_Structure::TYPE_DEPARTMENT;
                break;
            case Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX :
                $this->view->urlFilterName = 'rgrpLabStructName_s';
                $structureType = Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY;
                break;
            case Ccsd_Referentiels_Structure::TYPE_LABORATORY . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX :
                $this->view->urlFilterName = 'labStructName_s';
                $structureType = Ccsd_Referentiels_Structure::TYPE_LABORATORY;
                break;
            case Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM . Hal_Website_Navigation_Page_Structure::STRUCTURE_ACTION_SUFFIX :
                $this->view->urlFilterName = 'rteamStructName_s';
                $structureType = Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM;
                break;
            // ------------------------------------------------------------------
            case Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION :
                $this->view->urlFilterName = 'rgrpInstStructName_s';
                $facetField = 'rgrpInstStructName_fs';
                break;
            case Ccsd_Referentiels_Structure::TYPE_INSTITUTION :
                $facetField = 'instStructName_fs';
                $this->view->urlFilterName = 'instStructName_s';
                break;
            case Ccsd_Referentiels_Structure::TYPE_DEPARTMENT :
                $facetField = 'deptStructName_fs';
                $this->view->urlFilterName = 'deptStructName_s';
                break;
            case Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY :
                $this->view->urlFilterName = 'rgrpLabStructName_s';
                $facetField = 'rgrpLabStructName_fs';
                break;
            case Ccsd_Referentiels_Structure::TYPE_LABORATORY :
                $facetField = 'labStructName_fs';
                $this->view->urlFilterName = 'labStructName_s';
                break;
            case Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM :
                $facetField = 'rteamStructName_fs';
                $this->view->urlFilterName = 'rteamStructName_s';
                break;
            default :
                $facetField = 'structName_fs';
                $this->view->urlFilterName = 'structName_s';
                break;
            // ------------------------------------------------------------------
        }

        $this->view->letter = $letter;
        $this->view->typeFilter = $typeFilter;
        $this->view->sortType = $sort;

        $cacheName = Ccsd_Cache::makeCacheFileName('', '', $currentAction . $letter . $typeFilter . $sort);

        if (Hal_Cache::exist($cacheName, self::BROWSE_CACHE_LIFETIME)) {
            $cachedData = Hal_Cache::get($cacheName);
            if ($cachedData) {
                $cachedData = unserialize($cachedData);
                $this->view->facets = $cachedData;
                $this->render('facet');
                return;
            }
        }

        if ($structureType != '') {
            $structure = Hal_Search_Solr_Search_Structure::getLinkedStructures($structuresAsFilters, $letter, $typeFilter, $sort, $structureType);
        } else {
            $structure = Hal_Search_Solr_Search::getFacet($facetField, $letter,$typeFilter, $sort);
        }

        $this->view->facets = $structure;

        if (!empty($structure)) {
            Hal_Cache::save($cacheName, serialize($structure));
        }
        $this->render('facet');
    }

    /**
     * @param string $sortFromUser
     * @return bool
     */
    private static function isValidSortParameter($sortFromUser = ''): bool
    {

        $validSort = ['', 'count', 'index'];
        return in_array($sortFromUser, $validSort);

    }

    /**
     * @param string $letterFromUser
     * @return bool
     */
    private static function isValidLetterParameter($letterFromUser = ''): bool
    {
        $validLetters = array_merge([''], range('A', 'Z'), ['other', 'all']);
        return in_array($letterFromUser, $validLetters);
    }

    /**
     * @param string $submitTypeFromUser eg : 'file OR annex' || 'file'
     * @return bool
     */
    private static function isValidSubmitTypeParameter($submitTypeFromUser = ''): bool
    {
        if ($submitTypeFromUser == '') {
            return true;
        }
        $validsubmitTypes = [Hal_Document::FORMAT_FILE, Hal_Document::FORMAT_NOTICE, Hal_Document::FORMAT_ANNEX];
        $submitTypeFromUserArray = explode(' OR ', $submitTypeFromUser);

        foreach ($submitTypeFromUserArray as $inputValue) {
            if (!in_array($inputValue, $validsubmitTypes)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     */
    public function indexAction()
    {
        $this->renderScript('index/submenu.phtml');
    }

    /**
     * Auteurs affiliés à une structure
     */
    public function authorStructureAction()
    {
        $this->authorAction();
    }

    /**
     * Liste d'auteurs
     */
    public function authorAction()
    {
        $currentAction = $this->getRequest()->getUserParam('action');
        $page = new Hal_Website_Navigation_Page_Author ();
        $page->setAction($currentAction);
        $page->load();
        $structures = $page->toArray()['filter'];


        $letter = $this->getParam('letter', 'all');
        $typeFilter = $this->getParam('submitType_s');
        $sort = $this->getParam('sort', $page->getSort());

        if (!self::isValidSortParameter($sort) || !self::isValidLetterParameter($letter) || !self::isValidSubmitTypeParameter($typeFilter)) {
            $this->view->message = "Erreur de paramètre";
            $this->view->description = "Un des paramètres utilisé n'est pas valide";
            $this->renderScript('error/error.phtml');
            return;
        }


        $this->view->letter = $letter;
        $this->view->typeFilter = $typeFilter;
        $this->view->sortType = $sort;
        $this->view->urlFilterName = 'authLastNameFirstName_s';

        if ($structures != '') {
            $arrayS = [];
            foreach ($structures as $structid) {
                $objStruct = new Ccsd_Referentiels_Structure($structid);
                $arrayS[$structid] = $objStruct->getStructname();
            }

            $this->view->infostructures = $arrayS;
            $this->view->structures = implode(' OR ', $structures);
        }

        $cacheName = Ccsd_Cache::makeCacheFileName('', '', $letter . $typeFilter . $sort);

        if (Hal_Cache::exist($cacheName, self::BROWSE_CACHE_LIFETIME)) {
            $cachedData = Hal_Cache::get($cacheName);
            if ($cachedData) {
                $cachedData = unserialize($cachedData);
                $this->view->facets = $cachedData;
                $this->render('author');
                return;
            }
        }
        if ($structures != '') {
            $authors = Hal_Search_Solr_Search_Author::getLinkedAuthors('all', $structures, $letter, $typeFilter, $sort);
        } else {
            $authors = Hal_Search_Solr_Search::getFacet('authAlphaLastNameFirstNameIdHal_fs', $letter, $typeFilter, $sort);
        }

        $this->view->facets = $authors;

        if (!empty($authors)) {
            Hal_Cache::save($cacheName, serialize($authors));
        }
        $this->render('author');
    }

    /**
     * Liste des domaines
     * @return void
     */
    public function domainAction()
    {

        $page = new Hal_Website_Navigation_Page_Domain();
        $page->load();
        $displayType = $page->getDisplayType();

        $typeFilter = $this->getParam('submitType_s');

        if (!self::isValidSubmitTypeParameter($typeFilter)) {
            $this->view->message = "Erreur de paramètre";
            $this->view->description = "Un des paramètres utilisé n'est pas valide";
            $this->renderScript('error/error.phtml');
            return;
        }

        $this->view->typeFilter = $typeFilter;
        $this->view->sortType = null;

        $cacheName = Ccsd_Cache::makeCacheFileName('', true, $displayType . $this->view->typeFilter);
        if (!Hal_Cache::exist($cacheName, self::BROWSE_CACHE_LIFETIME)) {
            $domains = Hal_Search_Solr_Search_Domain::getDomainConsultationArray($displayType, $typeFilter);


            if ($domains == null) {
                $this->view->domainTree = false;
                return;
            } else {
                Hal_Cache::save($cacheName, json_encode($domains));
            }
        } else {
            $domains = Hal_Cache::get($cacheName);
        }

        $f = new Ccsd_Form ();

        $f->setAction('#');
        try {
            $f->addElement('Liste', 'domain', [
                'data' => $domains,
                'selectable' => false,
                'tagcode' => 'domainCode',
                'taglabel' => 'domainName',
                'tagDisplay' => 'domainDisplay',
                'typeahead_label' => $this->view->translate('Filtrer par nom :'),
                'typeahead_description' => $this->view->translate('Recherche plein texte dans les domaines'),
                'list_title' => '&nbsp;',
                'list_values' => '',
                'typeahead_height' => 'auto',
                'label' => 'Domaines',
                'collapsable' => false,
                'separator' => '.',
                'decorators' => [
                    'Liste'
                ]
            ]);
        } catch (Exception $e) {
            $this->view->domainTree = false;
            return;
        }

        if ($this->getRequest()->isPost()) {

            $f->populate($this->getRequest()->getPost());
        }

        $this->view->domainTree = $f;
    }

    /**
     * Liste des types de documents
     */
    public function doctypeAction()
    {
        $cacheName = Ccsd_Cache::makeCacheFileName();

        if (Hal_Cache::exist($cacheName, self::BROWSE_CACHE_LIFETIME)) {
            $cachedData = Hal_Cache::get($cacheName);
            if ($cachedData) {
                $this->view->docType = unserialize($cachedData);
                return;
            }
        }

        $docTypes = Hal_Search_Solr_Search_Typedoc::getTypeDocsPivotHasFile();

        $this->view->docType = $docTypes;
        if (!empty($docTypes)) {
            Hal_Cache::save($cacheName, serialize($docTypes));
        }
    }

    /**
     * liste des portails
     */
    public function portalAction()
    {
        $cacheName = Ccsd_Cache::makeCacheFileName();

        if (Hal_Cache::exist($cacheName, self::BROWSE_CACHE_LIFETIME)) {
            $portails = Hal_Cache::get($cacheName);
            if ($portails) {
                $this->view->portails = unserialize($portails);
                return;
            }
        }

        // Nombre de dépôts effectués dans les portails
        $query = 'q=*%3A*&start=0&rows=0&wt=phps&omitHeader=true&facet=true&facet.field=sid_i';
        try {
            $res = unserialize(Ccsd_Tools::solrCurl($query));
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
        }
        $submissionNb = $res ['facet_counts'] ['facet_fields'] ['sid_i'];

        $visiblePortails = Hal_Site_Portail::getVisibleInstances();

        //todo : récupérer un liste de portails et la passer directement à la vue !
        $portails = [];
        foreach ($visiblePortails as $row) {
            // Récupération des dépôts du portail
            $query = 'q=*%3A*&start=0&rows=0&wt=phps&omitHeader=true&facet=true&facet.field=submitType_s';
            $query .= Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json', 'json', $row ['SITE'], false));
            try {
                $res = unserialize(Ccsd_Tools::solrCurl($query));
            } catch (Exception $exc) {
                error_log($exc->getMessage(), 0);
            }
            $portails[] = [
                'sid' => $row ['SID'],
                'site' => $row ['SITE'],
                'name' => $row ['NAME'],
                'url' => $row ['URL'],
                'date' => $row ['DATE_CREATION'],
                'img' => ($row ['IMAGETTE'] != null) ? $row ['IMAGETTE'] : 630805,
                'counter' => $res ['facet_counts'] ['facet_fields'] ['submitType_s'],
                'submissions' => isset($submissionNb [$row ['SID']]) ? $submissionNb [$row ['SID']] : 0
            ];
        }

        $this->view->portails = $portails;
        Hal_Cache::save($cacheName, serialize($portails));
    }

    /**
     * Liste des collections et sous-collections
     * @see collectionAction()
     * @deprecated
     */
    public function collectionsAction()
    {
        $this->collectionAction();
        $this->render('collection');
    }

    /**
     * Liste des collections et sous-collections
     */
    public function collectionAction()
    {
        $currentAction = $this->getRequest()->getUserParam('action', '');
        $page = new Hal_Website_Navigation_Page_Collections ();

        $page->setAction($currentAction);
        $page->load();

        // quid si pasa de page correspondante dans le site actuel ???



        $this->view->viewType = $page->getField();


        $typeFilter = $this->getParam('submitType_s');
        $sort = $this->getParam('sort', $page->getSort());

        if (!self::isValidSortParameter($sort) || !self::isValidSubmitTypeParameter($typeFilter)) {
            $this->view->message = "Erreur de paramètre";
            $this->view->description = "Un des paramètres utilisé n'est pas valide";
            $this->renderScript('error/error.phtml');
            return;
        }


        $this->view->typeFilter = $typeFilter;
        $this->view->sortType = $sort;


        $cacheName = Ccsd_Cache::makeCacheFileName('browse' . $currentAction . '_' . $page->getField() . '_' . $typeFilter . '_' . $sort, '', '');

        if (Hal_Cache::exist($cacheName, self::BROWSE_CACHE_LIFETIME, CACHE_PATH)) {
            $cachedData = Hal_Cache::get($cacheName, CACHE_PATH);
            if ($cachedData) {
                $this->view->facets = unserialize($cachedData);
                return;
            }
        }

        $collections = Hal_Search_Solr_Search_Collection::getCollections($page->getField(), $page->getFilter(), $typeFilter, $sort);


        if (!empty($collections)) {
            Hal_Cache::save($cacheName, serialize($collections), CACHE_PATH);
        }

        $this->view->facets = $collections;

    }

    /**
     * Consulter par sous collection
     * @deprecated
     */
    public function scollectionAction()
    {
        $this->forward('collection');
    }

    /**
     * Affichage selon une métadonnée
     */
    public function metaAction()
    {

        $page = new Hal_Website_Navigation_Page_Meta();
        $page->setMeta($this->_meta);
        $page->load();
        $letter = '';

        if ($this->_meta == 'hceres_entityName') {
            $letter = $this->getParam('letter', 'all');
            $this->view->letter = $letter;
            if (!self::isValidLetterParameter($letter)) {
                $this->view->message = "Erreur de paramètre";
                $this->view->description = "Un des paramètres utilisé n'est pas valide";
                $this->renderScript('error/error.phtml');
                return;
            }
        }



        $typeFilter = $this->getParam('submitType_s');
        $sort = $this->getParam('sort', $page->getSort());

        if (!self::isValidSortParameter($sort) || !self::isValidSubmitTypeParameter($typeFilter)) {
            $this->view->message = "Erreur de paramètre";
            $this->view->description = "Un des paramètres utilisé n'est pas valide";
            $this->renderScript('error/error.phtml');
            return;
        }


        $this->view->typeFilter = $typeFilter;
        $this->view->sortType = $sort;

        $this->view->fieldSolr = $this->_meta . '_s';
        $cacheName = Ccsd_Cache::makeCacheFileName('browseMeta_' . $this->_meta . $letter . $typeFilter . $sort . '_' . Zend_Registry::get('lang'));


        if (Hal_Cache::exist($cacheName, self::BROWSE_CACHE_LIFETIME)) {
            $cachedData = Hal_Cache::get($cacheName);
            if ($cachedData) {
                $cachedDataArr = unserialize($cachedData);
                $this->view->facets = $cachedDataArr['facets'];
                return;
            }
        }

        // no cache


        if ($letter != '') {
            $facets = Hal_Search_Solr_Search::getFacetField($this->view->fieldSolr, $letter, $sort, $typeFilter);
        } else {
            $facets = Hal_Search_Solr_Search::getFacetField($this->view->fieldSolr, null, $sort, $typeFilter);
        }

        $isMetaList = Hal_Referentiels_Metadata::isMetaList($this->_meta, null) || Hal_Referentiels_Metadata::isMetaList($this->_meta, SITEID);
        $metaValues = $isMetaList ? Hal_Referentiels_Metadata::getValues($this->_meta) : [];

        $facetList = [];
        if ($isMetaList) {
            // traduction si la métadonnée appartient à une liste de métadonnées
            foreach ($facets as $value => $nb) {
                if (in_array($this->_meta, ['campusaar_genre', 'campusaar_context', 'campusaar_classaar'])) {
                    $translatedValue = $this->view->translate($value);
                } elseif (isset ($metaValues [$value])) {
                    $translatedValue = $this->view->translate($metaValues [$value]);
                } else {
                    $translatedValue = $value;
                }
                $facetList[$translatedValue]['code'] = $value;
                $facetList[$translatedValue]['count'] = $nb;
            }
        } else {
            foreach ($facets as $value => $nb) {
                $translatedValue = $value;
                if ($this->_meta == 'docType') {
                    $translatedValue = $this->view->translate('typdoc_' . $value);
                } else if ($this->_meta == 'hceres_campagne_local') {
                    $translatedValue = $this->view->translate('hceresCampagne_' . $value);
                }
                $facetList[$translatedValue]['code'] = $value;
                $facetList[$translatedValue]['count'] = $nb;
            }
        }
        // tri par PHP si demande de tri alphabétique
        if ($sort != 'count') {
            if ($this->_meta == 'hceres_campagne_local') {
                krsort($facetList);
            } else {
                uksort($facetList, 'strcoll');
            }
        }

        $this->view->facets = $facetList;

        $cachedDataArr['facets'] = $this->view->facets;

        if ($this->view->facets != null) {
            Hal_Cache::save($cacheName, serialize($cachedDataArr));
        }
    }

    /**
     * Derniers dépôts
     */
    public function lastAction()
    {
        $this->forward('index', 'search', null, [
            'q' => '*',
            'rows' => '30',
            'sort' => 'submittedDate_tdate desc'
        ]);
    }

    /**
     * Dernières publications
     */
    public function latestPublicationsAction()
    {
        $this->forward('index', 'search', null, [
            'q' => '*',
            'rows' => '30',
            'sort' => 'producedDate_tdate desc'
        ]);
    }

    /**
     * Liste par Groupe d'années
     *
     * @deprecated
     *
     * @see periodAction()
     */
    public function dateAction()
    {
        $this->forward('period');
    }

    /**
     * Liste par Groupe d'années
     *
     * @deprecated
     *
     * @see periodAction()
     */
    public function yearAction()
    {
        Ccsd_Tools::deprecatedMsg(__FILE__, __LINE__, 'BROWSE YEAR called with SPACE_NAME :' . SPACE_NAME);

        $this->periodAction();
    }

    /**
     * Parcourir par période
     *
     * @return void|null
     */
    public function periodAction()
    {
        $facetYears = [];
        $cacheName = Ccsd_Cache::makeCacheFileName();
        if (Hal_Cache::exist($cacheName, self::BROWSE_CACHE_LIFETIME)) {

            $cachedData = Hal_Cache::get($cacheName);
            if ($cachedData) {
                $cachedData = unserialize($cachedData);
                $this->view->rangeParams = $cachedData ['rangeParams'];
                $this->view->facetYears = $cachedData ['facetYears'];
                $this->view->facet = $cachedData ['facet'];
                $this->render();
                return;
            }
        }

        // Récupération des paramètres de l'utilisateur pour la page
        $page = new Hal_Website_Navigation_Page_Period ();
        $page->load();

        if ($page->getFacetRange()) {
            $query [] = 'facet.range={!key=date}' . $page->getFacetRange();
        }

        if ($page->getFacetRangeStart()) {
            $query [] = 'facet.range.start=' . $page->getFacetRangeStart();
        }

        if ($page->getFacetRangeEnd() != '') {
            $query [] = 'facet.range.end=' . $page->getFacetRangeEnd();
        } else {
            // facet.range.end applique un < strict donc on ajoute une année
            $query [] = 'facet.range.end=' . date('Y', strtotime('+1 year'));
        }

        if ($page->getFacetRangeGap()) {
            $query [] = 'facet.range.gap=' . $page->getFacetRangeGap();
        }

        if ($page->getFacetRangeHardend()) {
            $query [] = 'facet.range.hardend=' . $page->getFacetRangeHardend();
        }

        if ($page->getFacetRangeInclude()) {
            $query [] = 'facet.range.include=' . $page->getFacetRangeInclude();
        }


        if ($page->getFacetRangeOther()) {
            $query [] = 'facet.range.other=' . $page->getFacetRangeOther();
        }

        $baseQueryString = 'q=*:*&facet=true&rows=0&' . implode('&', $query);

        $defaultFilterQuery = Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));

        if ($defaultFilterQuery != null) {
            $baseQueryString .= $defaultFilterQuery;
        }

        // Fichiers
        try {
            $solrResponse = Ccsd_Tools::solrCurl($baseQueryString . '&fq=submitType_s:' . Hal_Document::FORMAT_FILE, 'hal');
            $solrObj = unserialize($solrResponse);
            $file = $solrObj['facet_counts']['facet_ranges']['date'];

        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
            $this->facetYears = null;
            return;
        }

        //Notices
        try {
            $solrResponse = Ccsd_Tools::solrCurl($baseQueryString . '&fq=submitType_s:' . Hal_Document::FORMAT_NOTICE, 'hal');
            $solrObj = unserialize($solrResponse);
            $notice = $solrObj['facet_counts']['facet_ranges']['date'];
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
            $this->facetYears = null;
            return;
        }

        //Annexes
        try {
            $solrResponse = Ccsd_Tools::solrCurl($baseQueryString . '&fq=submitType_s:' . Hal_Document::FORMAT_ANNEX, 'hal');
            $solrObj = unserialize($solrResponse);
            $annex = $solrObj['facet_counts']['facet_ranges']['date'];
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
            $this->facetYears = null;
            return;
        }

        foreach ($file ['counts'] as $year => $count) {
            $facetYears [$year] [Hal_Document::FORMAT_FILE] = $count;
        }
        foreach ($notice ['counts'] as $year => $count) {
            $facetYears [$year] [Hal_Document::FORMAT_NOTICE] = $count;
        }
        foreach ($annex ['counts'] as $year => $count) {
            $facetYears [$year] [Hal_Document::FORMAT_ANNEX] = $count;
            $facetYears [$year] ['total'] = $count + $facetYears [$year] ['file'] + $facetYears [$year] [Hal_Document::FORMAT_NOTICE];
        }

        // communs
        $facet ["gap"] = $file ['gap'];
        $facet ["start"] = $file ['start'];
        $facet ["end"] = $file ['end'];

        foreach (['after', 'before', 'between'] as $field) {
            if (isset($file [$field])) {
                $facet [Hal_Document::FORMAT_FILE]   [$field] = $file   [$field];
                $facet [Hal_Document::FORMAT_NOTICE] [$field] = $notice [$field];
                $facet [Hal_Document::FORMAT_ANNEX]  [$field] = $annex  [$field];

                $facet ['total']  [$field] = $file   [$field] + $notice [$field] + $annex [$field];
            }
        }

        // Tri descendant
        if ($page->getRangeSorting() == 'desc') {
            krsort($facetYears);
        }

        $this->view->rangeParams = $page->toArray();
        $this->view->facetYears = $facetYears;
        $this->view->facet = $facet;

        if ($this->view->facetYears != null) {
            Hal_Cache::save($cacheName, serialize([
                'rangeParams' => $this->view->rangeParams,
                'facetYears' => $this->view->facetYears,
                'facet' => $this->view->facet
            ]));
        }

        $this->render('period');
    }

}

//class


