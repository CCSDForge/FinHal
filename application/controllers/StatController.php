<?php

class StatController extends Hal_Controller_Action
{
	const STAT_REPARTITION  =   'repartition';
	const STAT_CONSULTATION =   'consultation';
	const STAT_RESSOURCE    =   'ressource';
	const STAT_PROVENANCE   =   'provenance';


    const SPACE_CONTRIBUTOR =   'contributor';
    const SPACE_IDHAL       =   'idhal';
	const SPACE_AUTHOR_FULL =   'author_full';
	const SPACE_AUTHOR      =   'author';
	const SPACE_STRUCT      =   'struct';
	const SPACE_COLLECTION  =   'collection';
	const SPACE_SCIENTIFIQ  =   'science';
	const SPACE_ALL         =   'all';

    const CHART_PIE         =   'PieChart';
    const CHART_COLUMN      =   'ColumnChart';
    const CHART_LINE        =   'LineChart';
    const CHART_BAR         =   'BarChart';
    const CHART_STEPPEDAREA =   'SteppedAreaChart';
    const CHART_GEO         =   'GeoChart';

    protected $_defaultFilters = array();

    protected $_statCollection = false;

    protected $_currentSpace = false;

    public function init()
    {
        $this->_statCollection = defined('MODULE') && defined('SPACE_COLLECTION') && defined('SPACE_NAME') && MODULE == SPACE_COLLECTION;
        $this->_defaultFilters = $this->getDefaultFilters($this->getParam('space', $this->_statCollection ? '' : self::SPACE_CONTRIBUTOR));
    }

    public function userAction()
    {
        $this->indexAction();
        $this->render('index');
    }


    public function indexAction()
	{
        $userQueries = new Hal_User_Stat_Queries(Hal_Auth::getUid());
        $params = $this->getRequest()->getPost();
        if (isset($params['method']) && $params['method'] == 'delete' && isset($params['qid'])) {
            if ($userQueries->delQuery($params['qid'])) {
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage('La requête a été supprimée ');
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Vous ne disposez pas des droits pour supprimer cette requête');
            }
        }
        $form = new Ccsd_Form();
        $form->setMethod('post');
        $queries = array(0 => '');
        foreach($userQueries->getQueries() as $queryId => $label) {
            $queries[$queryId] = $label . ' (' . $this->view->translate('Identifiant de la requête') . ': ' . $queryId . ')' ;
        }
        $form->addElement('select', 'query', array('label'=>'Requêtes prédéfinies', 'multiOptions' => $queries));

        if ( $this->_statCollection ) {
            //Seul l'espace de la collection est dispo
            $this->_defaultFilters = $this->getDefaultFilters('');
        } else {
            $statSpaces = [self::SPACE_CONTRIBUTOR => $this->view->translate('Dépôts où') . ' ' . Hal_Auth::getUsername(). ' ' . $this->view->translate('est déposant ou propriétaire')];
            if ( Hal_Auth::getIdHAL() ) {
                $statSpaces[self::SPACE_IDHAL] = $this->view->translate('Dépôts où') . ' ' . Hal_Auth::getUser()->getFirstname() . ' ' . Hal_Auth::getUser()->getLastname() . ' ' . $this->view->translate('est un des auteurs (IdHAL)');
            } else {
                $statSpaces[self::SPACE_AUTHOR_FULL] = $this->view->translate('Dépôts où') . ' ' . Hal_Auth::getUser()->getFirstname() . ' ' . Hal_Auth::getUser()->getLastname() . ' ' . $this->view->translate('est un des auteurs');
                $statSpaces[self::SPACE_AUTHOR] = $this->view->translate('Dépôts où') . ' ' . Hal_Auth::getUser()->getFirstname()[0] . ' ' . Hal_Auth::getUser()->getLastname() . ' ' . $this->view->translate('est un des auteurs');
            }
            if (Hal_Auth::isHALAdministrator()) {
                $statSpaces[self::SPACE_ALL]   =  $this->view->translate('Dépôts de la base');
                if ($this->_currentSpace == false) {
                    $this->_currentSpace = self::SPACE_ALL;
                    $this->_defaultFilters = $this->getDefaultFilters(self::SPACE_ALL);
                }
            }
            if (Hal_Auth::isHALAdministrator()) {
                $statSpaces[self::SPACE_SCIENTIFIQ]   =  $this->view->translate('Documents scientifiques de la base');
                if ($this->_currentSpace == false) {
                    $this->_currentSpace = self::SPACE_SCIENTIFIQ;
                    $this->_defaultFilters = $this->getDefaultFilters(self::SPACE_SCIENTIFIQ);
                }
            }
            if (Hal_Auth::isAdministrator()) {
                $statSpaces[Hal_Site_Portail::MODULE]   =  $this->view->translate('Dépôts du portail');
                if ($this->_currentSpace == false) {
                    $this->_currentSpace = Hal_Site_Portail::MODULE;
                    $this->_defaultFilters = $this->getDefaultFilters(Hal_Site_Portail::MODULE);
                }
            }
            if (Hal_Auth::isTamponneur()) {
                $statSpaces[Hal_Site_Collection::MODULE]   =  $this->view->translate('Dépôts de toutes mes collections ');
                foreach(Hal_Auth::getDetailsRoles(Hal_Acl::ROLE_TAMPONNEUR) as $tampid => $value) {
                    $statSpaces[Hal_Site_Collection::MODULE . '-' . $tampid]   =  $this->view->translate('Dépôts de la collection ' . $value);
                }
                if ($this->_currentSpace == false) {
                    $this->_currentSpace = Hal_Site_Collection::MODULE;
                }
            }
            if (Hal_Auth::isAdminStruct()) {
                $statSpaces[self::SPACE_STRUCT]   =  $this->view->translate('Dépôts des structures dont je suis référent');
                if ($this->_currentSpace == false) {
                    $this->_currentSpace = self::SPACE_STRUCT;
                }
            }
            $form->addElement('select', 'space', array('label'=>'Espace de sélection', 'multiOptions' => $statSpaces, 'value' => $this->_currentSpace));
        }

        $form->addElement('text', 'defaultFilters', array('label'=>'Filtres par défaut', 'disabled' => 'disable', 'value' => implode(' AND ', $this->_defaultFilters)));

        $value = '';
        $this->view->category = self::STAT_REPARTITION;
        $this->view->start = '2011-01-01';
        $this->view->end = date('Y-m-d');
        if ($this->getParam('id')) {
            $value = 'halId_s:' . $this->getParam('id');
            $doc = Hal_Document::find(0, $this->getParam('id'));

            $this->view->start = $doc->getReleasedDate('yyyy-MM-dd');

            if ($this->getParam('type') == self::STAT_CONSULTATION) {
                $this->view->category = self::STAT_CONSULTATION;
            } else {
                $this->view->category = self::STAT_PROVENANCE;
                if ($this->getParam('type') == self::STAT_PROVENANCE) {
                    $this->view->view = 'country';
                } else {
                    $this->view->view = 'domain';
                }
            }

            $this->view->submit = true;
        }
        $form->addElement('textarea', 'filters', array('rows'=>3, 'label'=>'Ajouter des filtres', 'description' => 'Liste des filtres', 'value' => $value));

        if ( $this->getRequest()->isPost() ) {
            $params = $this->getRequest()->getPost();
            if ( $form->isValid($params) ) {
            } else {
                $form->populate($params);
            }
        }
        $this->view->form = $form;

        $filename = 'stat.fields.phps';
        
        if (Hal_Cache::exist($filename, 86400)) {
              $this->view->fields = unserialize(Hal_Cache::get($filename));
        } else {
            $fields = Hal_Stats::getStatFields();
            Hal_Cache::save($filename, serialize($fields));
            $this->view->fields = $fields;
        }
        
        $this->view->filters = Hal_Settings::getConfigFile('solr.hal.AdvSearchFields.json');
        $this->view->typeConsult = array(
            'all'    =>  $this->view->translate('Tout'),
            'notice'    =>  $this->view->translate('Consultation de la fiche'),
            'file'    =>  $this->view->translate('Téléchargement du fichier principal'),
            'oai'    =>  $this->view->translate('Consultation via OAI'),
            'bibtex'    =>  $this->view->translate('Consultation du format BibTeX'),
            'tei'    =>  $this->view->translate('Consultation du format TEI'),
        );

	}

    public function ajaxgetdefaultfiltersAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        echo implode(' AND ', $this->_defaultFilters);
    }



    public function ajaxnbdocAction()
    {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getPost();
        if (isset($params['filters'])) {
            $this->view->data = Hal_Stats::getCount($params['filters'], $this->_defaultFilters);
        }
    }

    public function ajaxdataAction()
    {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getPost();
        if (isset($params['category'])) {
            if ($params['category'] == self::STAT_REPARTITION) {
                $this->view->facet = $params['facet'];
                $this->view->data = Hal_Stats::getRepartitionData($params['filters'], $this->_defaultFilters, $params['facet'], $params['pivot'], $params['sort'], $params['cumul'], $params['additional']);
                $this->view->chart = $this->getParam('chart', self::CHART_PIE);
                $this->view->charts = array(self::CHART_PIE, self::CHART_COLUMN, self::CHART_BAR, self::CHART_LINE, self::CHART_STEPPEDAREA);
            } else if ($params['category'] == self::STAT_PROVENANCE) {
                //Carte
                $data = Hal_Stats::getDocids($params['filters'], $this->_defaultFilters);
                $res = array();
                if (count($data['docids']) > 0) {
                    $res = Hal_Stats::getConsult('map', $data['docids'], $params['start'], $params['end'], $params['type'], $params['view']);
                }
                $this->view->data = array('total' =>  $data['total'], 'nb' =>  $data['nb'], 'data'  =>  $res);
                $this->view->view = $params['view'];
                $this->view->chart = self::CHART_GEO;
            } else if ($params['category'] == self::STAT_RESSOURCE) {
                //Ressource
                $data = Hal_Stats::getDocids($params['filters'], $this->_defaultFilters);
                $res = array();
                if (count($data['docids']) > 0) {
                    $res = Hal_Stats::getConsult('resource', $data['docids'], $params['start'], $params['end'], $params['type']);
                }
                $this->view->data = array('total' =>  $data['total'], 'nb' =>  $data['nb'], 'data'  =>  $res);
                $this->view->chart = self::CHART_COLUMN;
            } else if ($params['category'] == self::STAT_CONSULTATION) {
                //Consultation
                $data = Hal_Stats::getDocids($params['filters'], $this->_defaultFilters);
                $res = array();
                if (count($data['docids']) > 0) {
                    $res = Hal_Stats::getConsult('hit', $data['docids'], $params['start'], $params['end'], $params['type'], $params['interval']);
                }
                $this->view->data = array('total' =>  $data['total'], 'nb' =>  $data['nb'], 'data'  => $res);
                $this->view->cumul = $params['cumul'];
                $this->view->chart = $this->getParam('chart', self::CHART_LINE);
                $this->view->charts = array(self::CHART_LINE, self::CHART_COLUMN, self::CHART_STEPPEDAREA);
            }
            $this->view->category = $params['category'];
        }
        if (! isset($this->view->data['data']) || count($this->view->data['data']) == 0) {
            $params['category'] = 'nodata';
        }
        $this->render('ajax-' . $params['category']);
    }

    public function ajaxqueryAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $params = $this->getRequest()->getPost();
        if (isset($params['queryid'])) {
            $userQueries = new Hal_User_Stat_Queries(Hal_Auth::getUid());
            echo Zend_Json::encode($userQueries->getQuery($params['queryid']));
        }
    }

    public function ajaxsavequeryAction()
    {
        //Zend_Debug::dump($this->getRequest()->getParams());exit;

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $userQueries = new Hal_User_Stat_Queries(Hal_Auth::getUid());
        $userQueries->saveQuery(Hal_Auth::getUid(), $this->getAllParams());

        $userQueries = new Hal_User_Stat_Queries(Hal_Auth::getUid());
        $queries = array(0 => '');
        foreach($userQueries->getQueries() as $queryId => $label) {
            $queries[$queryId] = $label . ' (' . $this->view->translate('Identifiant de la requête') . ': ' . $queryId . ')' ;
        }
        echo Zend_Json::encode($queries);
    }

    /**
     * Détermination des filtres par défaut à ajouter à la requete (fonction de l'espace de recherche)
     * @param $space
     * @return array
     */
    protected function getDefaultFilters($space)
    {
        $res = array();

        if ($space == self::SPACE_CONTRIBUTOR) {
            $res[] = '(contributorId_i: ' . Hal_Auth::getUid() . ' OR owners_i:' . Hal_Auth::getUid() .')';
        } else if ($space == self::SPACE_IDHAL) {
            $res[] = 'authIdHal_i:' . Hal_Auth::getIdHAL();
        } else if ($space == self::SPACE_AUTHOR_FULL) {
            $res[] = 'authLastName_sci:' . Hal_Auth::getUser()->getLastname();
            $res[] = 'authFirstName_sci:' . Hal_Auth::getUser()->getFirstname();
        } elseif ($space == self::SPACE_AUTHOR) {
            $res[] = 'authLastName_sci:' . Hal_Auth::getUser()->getLastname();
            $res[] = 'authFirstName_t:' . Hal_Auth::getUser()->getFirstname()[0] . '*';
        } elseif ($space == self::SPACE_STRUCT) {
            $res[] = '(structId_i:' . implode(' OR structId_i:',  Hal_Auth::getStructId()) . ')';
        } elseif ($space == self::SPACE_SCIENTIFIQ) {
            $res[] = '(submitType_s:file AND docType_s:(ART OR COMM OR COUV OR OTHER OR OUV OR DOUV OR UNDEFINED OR REPORT OR THESE OR HDR OR LECTURE))';
        } elseif (strpos($space, Hal_Site_Collection::MODULE) !== false) {
            $collId = trim(str_replace(Hal_Site_Collection::MODULE , '', $space), '-');
            if ($collId != '') {
                $res[] = '(collId_i:' . $collId . ')';
            } else {
                $res[] = '(collId_i:' . implode(' OR collId_i:',  Hal_Auth::getTampon()) . ')';
            }
        } elseif ( !in_array($space, [self::SPACE_ALL,self::SPACE_SCIENTIFIQ]) ) {
            //Récupération des filtres par défaut
            $defaultFilters = Hal_Settings::getConfigFile('solr.hal.defaultFilters.json');
            if (is_array($defaultFilters)) {
                foreach ($defaultFilters as $defaultFilterToApply) {
                    $res[] = $defaultFilterToApply;
                }
            }
        }

        if ( defined('MODULE') && defined('SPACE_COLLECTION') && defined('SPACE_NAME') ) {
            if ( MODULE == SPACE_COLLECTION ) {
                $res[] = 'collCode_s:' . strtoupper(SPACE_NAME);
            } else {
                $res[] = 'NOT status_i:111';
            }
        }
        return $res;
    }
}