<?php
/**
 * Contrôleur des widgets de la page d'accueil
 *
 * PHP version 5
 *
 * @category CategoryName
 * @package  PackageName
 * @author   Original Author <author@example.com>
 * @license
 * @link
 */

/**
 * Class WidgetController : gestion des widgets de la page d'accueil
 *
 * @category CategoryName
 * @package  PackageName
 * @author   Original Author <author@example.com>
 * @license
 * @link
 */
class WidgetController extends Hal_Controller_Action
{
    /**
     * fonction pour l'action index
     * modifie de l'instance courante de l'objet Widget
     *
     * @return void
     */
    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $type = $this->getParam('type', '');

        $cacheFilename = $this->getParam('cache', 'widget');

        if (method_exists($this, $type . 'Widget')) {
            $this->{$type . 'Widget'}($cacheFilename);
        } else {
            $this->errorWidget();
        }
    }

    /**
     * Twitter
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void
     */
    public function twitterWidget($cacheFilename)
    {
        $format = $this->getParam('format');
        $compte = $this->getParam('href');

        $this->view->twitter = $compte;
        $this->view->format = $format;
        echo $this->renderWidget('twitter', $cacheFilename);
    }

    /**
     * Les dernières publications du portail
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void // affichage du widget
     */
    protected function lastpubWidget($cacheFilename)
    {
        $limit = (int) $this->getParam('limit', 5);

        $query  = 'q=*%3A*';
        $query .= '&fq=submitType_s:file';
        $query .= '&sort=producedDate_tdate+desc&rows=' . $limit . '&fl=citationFull_s&fl=halId_s&fl=thumbId_i&fl=version_i%2C&wt=phps&omitHeader=true';
        if (($main = Hal_Site_Settings_Portail::getMainTypdocs()) != '') {
            $cond = preg_replace('/,',' OR ',$main);
            $query .= '&fq=docType_s:'.urlencode('('.$cond.')');
        }

        try {
            $res = Hal_Tools::solrCurl($query, 'hal', 'select', true);
            $this->view->articles = unserialize($res);
            echo $this->renderWidget('lastpub', $cacheFilename);
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
        }
    }

    /**
     * Les derniers dépôts du portail
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void // affichage du widget
     */
    protected function lastWidget($cacheFilename)
    {
        $limit = (int) $this->getParam('limit', 5);

        $query  = 'q=*%3A*';
        $query .= '&fq=submitType_s:file';
        $query .= '&sort=submittedDate_tdate+desc&rows=' . $limit . '&fl=citationFull_s&fl=halId_s&fl=thumbId_i&fl=version_i%2C&wt=phps&omitHeader=true';
        if (($main = Hal_Site_Settings_Portail::getMainTypdocs()) != '') {
            $cond = preg_replace('/,/',' OR ',$main);
            $query .= '&fq=docType_s:'.urlencode('('.$cond.')');
        }
        try {
            $res = Hal_Tools::solrCurl($query, 'hal', 'select', true);
            $this->view->articles = unserialize($res);
            echo $this->renderWidget('last', $cacheFilename);
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
        }
    }

    /**
     * Actualités
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void // affichage du widget
     */
    public function newsWidget($cacheFilename)
    {
        $limit = (int) $this->getParam('limit', 5);
        $news = new Hal_News();
        $this->view->displayBtn = false;
        $this->view->feeds = $news->getFeeds(true, $limit);
        echo $this->renderWidget('feed', $cacheFilename);
    }

    /**
     * Compteurs
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void // affichage du widget
     */
    public function countWidget($cacheFilename)
    {
        $formats = $this->getParam('format', array());
        if (!is_array($formats)) {
            $formats = array($formats);
        }

        $query  = 'q=*%3A*';
        $query .= '&start=0&rows=0&wt=phps&omitHeader=true&facet=true&facet.field=submitType_s';
        if (($main = Hal_Site_Settings_Portail::getMainTypdocs()) != '') {
            $cond = preg_replace('/,/' , ' OR ', $main);
            $query .= '&fq=docType_s:'.urlencode('('.$cond.')');
        }
        $res = [];
        try {
            $res = Hal_Tools::solrCurl($query, 'hal', 'select', true);
            $res = unserialize($res);
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
        }
        if (isset($res['facet_counts']['facet_fields']['submitType_s'])) {
            $counter = $res['facet_counts']['facet_fields']['submitType_s'];
            $nb = 0;
            if (is_array($formats)) {
                foreach ($formats as $format) {
                    $nb +=  isset($counter[$format]) ? $counter[$format] : 0;
                }
            }
            $this->view->nb = $nb;
            echo $this->renderWidget('count', $cacheFilename);
        }
    }

    /**
     * Politique Sherpa
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void // affichage du widget
     */
    public function sherpaWidget($cacheFilename)
    {
        echo $this->renderWidget('sherpa', $cacheFilename);
    }

    /**
     * Recherche
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void // affichage du widget
    */
    public function searchWidget($cacheFilename)
    {
        $filters = [];
        $res = Hal_Settings::getConfigFile(Hal_Website_Search::CHECKED_FILTER_FILE);
        foreach (['filter_dt' => 'docType_s', 'filter_st' => 'submitType_s'] as $key => $field) {
            if (isset($res[$key]) && $res[$key] != '') {
                if (is_array($res[$key])) {
                    $filters = $res[$key];
                } else {
                    $filters = explode(',', $res[$key]) ;
                }
                foreach ($filters as $filter) {
                    $filters[] = $field . '[]=' . $filter;
                }
            }
        }
        $this->view->fq = implode('&', $filters);
        echo $this->renderWidget('search', $cacheFilename);
    }

    /**
     * Recherche avancée
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void // affichage du widget
     */
    public function searchAdvWidget($cacheFilename)
    {
        $filters = [];
        $res = Hal_Settings::getConfigFile('solr.hal.checkedFilters.json');
        foreach (['filter_dt' => 'docType_s', 'filter_st' => 'submitType_s'] as $key => $field) {
            if (isset($res[$key]) && $res[$key] != '') {
                foreach (explode(',', $res[$key]) as $filter) {
                    $filters[] = $field . '[]=' . $filter;
                }
            }
        }
        $this->view->fq = implode('&', $filters);
        echo $this->renderWidget('searchAdv', $cacheFilename);
    }
    /**
     * Flux RSS
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void  // affichage du widget
    */
    public function feedWidget($cacheFilename)
    {
        $data = array();
        try {
            $feed = Zend_Feed_Reader::import( html_entity_decode($this->getParam('href')) );

            foreach ($feed as $entry) {
                $elem = array(
                    'title' => $entry->getTitle(),
                    'description' => $entry->getDescription(),
                    'date' => $entry->getDateModified(),
                    'link' => $entry->getLink()
                );
                $data[] = $elem;
            }
            // limitation du nombre d'items affichés
            $limit = $this->getParam('limit');
            if (isset($limit)) {
                $data = array_slice($data, 0, $limit);
            }
        } catch (Exception $e) {}
        $this->view->displayBtn = true;
        $this->view->feeds = $data;
        echo $this->renderWidget('feed', $cacheFilename);
    }

    /**
     * Statistiques
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void // affichage du widget
    */
    public function statsWidget($cacheFilename)
    {
        $userQueries = new Hal_User_Stat_Queries(Hal_Auth::getUid());
        $queryid = $this->getParam('format');
        $query = false;
        if ($queryid == 'typdoc') {
            $query = $userQueries->getQuery(1);
        } else if ($queryid == 'evol') {
            $query = $userQueries->getQuery(3);
        } else if ($queryid == 'domain') {
            $query = $userQueries->getQuery(6);
        } else {
            $query = $userQueries->getQuery((int)$queryid);
        }
        if ($query) {
            $defaultFilters = Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'));
            if ($query['CATEGORY'] == 'repartition') {
                $data = Hal_Stats::getRepartitionData($query['FILTERS'], $defaultFilters, $query['FACET'], $query['PIVOT'], $query['SORT'], $query['CUMUL'], $query['ADDITIONAL']);
                $this->view->data = $data['data'];
            } else if ($query['CATEGORY'] == 'provenance') {
                $data = Hal_Stats::getDocids($query['FILTERS'], $defaultFilters);
                $res = array();
                if (count($data['docids']) > 0) {
                    $res = Hal_Stats::getConsult('map', $data['docids'], $query['DATE_START'], $query['DATE_END'], $query['TYPE'], $query['VIEW']);
                }

                $this->view->data = array_merge(array('Pays' => 'Hits'), $res);
            } else if ($query['CATEGORY'] == 'ressource') {
                $data = Hal_Stats::getDocids($query['FILTERS'], $defaultFilters, 100);
                $res = array();
                if (count($data['docids']) > 0) {
                    $res = Hal_Stats::getConsult('resource', $data['docids'], $query['DATE_START'], $query['DATE_END'], $query['TYPE']);
                }
                $this->view->data = $res;
            } if ($query['CATEGORY'] == 'consultation') {
                $data = Hal_Stats::getDocids($query['filters'], $defaultFilters);
                $res = array();
                if (count($data['docids']) > 0) {
                    $res = Hal_Stats::getConsult('hit', $data['docids'], $query['DATE_START'], $query['DATE_END'], $query['TYPE'], $query['INTERVAL']);
                }
                $this->view->data = $res;
            }
            $this->view->chart = $query["CHART"];
            $this->view->cumul = $query["CUMUL"];

            echo $this->renderWidget('stats', $cacheFilename);
        }
    }

    /**
     * Nuage de mot clés
     *
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return void  // affichage du widget
    */
    public function cloudWidget($cacheFilename)
    {
        $query  = 'q=*%3A*';
        $query .= '&start=0&rows=0&wt=phps&omitHeader=true&facet=true&facet.mincount=1&facet.field=keyword_s';
        try {
            $res = Hal_Tools::solrCurl($query, 'hal', 'select', true);
            $res = unserialize($res);
            if (isset($res['facet_counts']['facet_fields']['keyword_s'])) {
                $this->view->tags = $res['facet_counts']['facet_fields']['keyword_s'];
            }
        } catch (Exception $exc) {
            error_log($exc->getMessage(), 0);
        }
        // TODO: que rend on en cas d'Exception ???
        echo $this->renderWidget('cloud', $cacheFilename);
    }

    public function cartohalWidget($cacheFilename)
    {
        echo $this->renderWidget('cartohal', $cacheFilename);
    }

    /**
     * Widget non traité
     *
     * @return void  //  affichage du message d'erreur
    */
    public function errorWidget()
    {
        echo $this->view->translate('Widget non disponible') . ' : ' . $this->getParam('type');
    }

    /**
     * Affichage d'un Widget
     *
     * @param string $script        nom du widget
     * @param string $cacheFilename nom du fichier de cache
     *
     * @return string // contenu du fichier phtml
    */
    protected function renderWidget($script, $cacheFilename)
    {
        $render = $this->view->render('widget/' . $script . '.phtml');
        Hal_Cache::save($cacheFilename, $render);
        return $render;
    }

}
