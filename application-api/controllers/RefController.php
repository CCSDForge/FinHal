<?php

class RefController extends Zend_Controller_Action
{

    public function indexAction()
    {
        return $this->redirect('/docs/ref');
    }

    public function __call($method, $args)
    {


        if ('Action' == substr($method, -6)) {
            // Si une méthode d'action n'est pas trouvée rediriger vers l'action
            // index
            return $this->redirect('/docs');
        }
        // pour toute autre méthode, levée d'une exception
        throw new Exception('Méthode invalide "' . $method . '" appelée', 500);
    }


    /**
     * API affiliation
     *
     * Récupération des structures d'un auteur
     * Autre version de l'action de SearchController
     *
     * Available with GET or JSON POST
     *
     */
    public function affiliationAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Type: application/json; charset=utf-8');


        $structure = null;

        $validParams = ['lastName_t', 'firstName_t',  'middleName_t', 'email_s', 'authId_i',
            'structure_t', 'structName_t', 'structStatus_s', 'structCountry_s', 'structAddress_t', 'structType_s', 'structAcronym_s', 'structId_i',
            'keyword_t', 'producedDate_s',
            'country_s'];






        if ($this->getFrontController()->getRequest()->getHeader('Content-Type') == 'application/json') {
            $allParams = Zend_Json::decode($this->getRequest()->getRawBody());
        } else {
            $allParams = $this->getRequest()->getParams();

        }


        unset($allParams['controller'], $allParams['action'], $allParams['module'], $allParams['XDEBUG_SESSION_START']);


        foreach ($allParams as $param => $pv) {
            if ($param == 'XDEBUG_SESSION_START') {
                continue;
            }
            if (!in_array($param, $validParams)) {
                echo Hal_Search_Solr_Search::formatErrorAsSolr(['Unexpected param' => htmlspecialchars($param), 'Valid Params' => $validParams], 'json', true);
                return false;
            }
        }


        if (count($allParams) == 0) {
            echo Hal_Search_Solr_Search::formatErrorAsSolr(['Expected at least params' => '(lastName_t and firstName_t) or authId_i', 'Valid Params' => $validParams], 'json', true);
            return false;
        }


        foreach ($validParams as $k => $paramName) {
            if (!array_key_exists($paramName, $allParams)) {
                $params[$paramName] = null;
            } else {
                $params[$paramName] = $allParams[$paramName];
            }
        }

        // TODO filter params values

        $affiliation = new Hal_Search_Solr_Api_Affiliation();


        if (strlen($params['firstName_t']) == 1) {
            $params['firstName_t'] .= '.';
        }

        if (strlen($params['middleName_t']) == 1) {
            $params['middleName_t'] .= '.';
        }

        // make an author
        $author = Hal_Search_Solr_Api_Affiliation_Author::addAuthorFromParams($params);


        if ($params['producedDate_s']) {
            $annee = $params['producedDate_s'];
        } else {
            $annee = null;
        }


        // add affiliations to author if we have it
        if (($params['structure_t'] != null) && (is_array($params['structure_t']))) {
            foreach ($params['structure_t'] as $structArray) {
                $structure = $author->addStructureFromParams($structArray);
            }
        }


        if ($params['structName_t'] != null) {
            $structure = $author->addStructureFromParams($params);
        }


        $affiliation->completeAffiliations($author, $params);



        if ($author->getEtat() == Hal_Search_Solr_Api_Affiliation_Author::MY_ENUM_ETAT_NON_CALC && $structure == null) {
            die(json_encode(['results' => 'none']));
        }


        if ($structure != null) {

            $res = $affiliation->rechUneAffiPlusProbable($author, $annee, $structure);

            // si pas d'affiliation trouvée recherche par structure seule
            if ($res == null) {
                $structure = $author->addStructureFromParams($params);
                $labs = $structure->findByHALStructure($structure);


                if ($labs == null) {
                    die(json_encode(['results' => 'none']));
                }


                /** @var Hal_Search_Solr_Api_Affiliation_Structure $labs */

                $author = Hal_Search_Solr_Api_Affiliation_Author::addAuthorFromParams($params);

                $affiliation->completeAffiliations($author, $params);


                $res['knownlabids'] = array($labs->getDocid());

            } else {

                $author = new Hal_Search_Solr_Api_Affiliation_Author();
                $author->setDocid($res['docid']);
                $author->setPrenom($res['prenom']);
                //$author->setAutreNom($res['nom']);
                $author->setNom($res['nom']);
                $author->setIdHal($res['idHal']);
            }
            $labs = $res['knownlabids'];
            foreach ($labs as $lab) {
                $structure = Hal_Search_Solr_Api_Affiliation_Structure::getLaboComplet($lab);
                $author->addHALAuteurAffis($structure);
            }


        }


        $affiliationsArr = $author->getHALAuteurAffis();


        foreach ($affiliationsArr as $aff) {

            /** @var Hal_Search_Solr_Api_Affiliation_Structure $aff */
            /** @var Hal_Search_Solr_Api_Affiliation_Author $aut */

            $aut = $aff->getIdAuteurArticle();
            $autAsArray = $aut->toArray();
            $authorDocid = $autAsArray['docid'];
            $authorsArr['authors'][$authorDocid] = $autAsArray;

            $affAsArray = $aff->toArray();
            $affAsArray['score'] = $autAsArray['score'];

            $authorsArr['structures'][] = $affAsArray;

        }

        $output['results'] = $authorsArr;

        $output = json_encode($output);


        echo $output;


    }


    /**
     * Recherche dans le référentiel des domaines
     */
    public
    function domainAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $allowedWt = array(
            'json',
            'xml',
            'csv'
        );

        $parsedArray = self::getParsedQuery($allowedWt);

        echo self::searchApi('ref_domain', $parsedArray, $allowedWt);
    }

    /**
     * Retourne un tableau des paramètres de la requête
     *
     * @param array $allowedWt
     * @return array
     */
    static function getParsedQuery($allowedWt = array())
    {
        $rawQuery = $_SERVER['QUERY_STRING'];

        // syntaxe zend
        if ($rawQuery == '') {
            $request = Zend_Controller_Front::getInstance()->getRequest();
            $parsedArray = Hal_Search_Solr_Api::zendUrl2solrUrl($request->getParams(), $allowedWt);
        } else {
            $parsedArray = Hal_Search_Solr_Api::phpUrl2solrUrl($rawQuery, $allowedWt);
        }

        return $parsedArray;
    }

    /**
     * Curl de Solr pour l'API
     *
     * @param string $core
     * @param array $parsedArray
     * @param array $allowedWt
     * @return boolean Ambigous boolean>
     */
    static function searchApi($core, $parsedArray, $allowedWt)
    {
        $queryString = implode('&', $parsedArray);

        try {
            $curlResult = Ccsd_Tools::solrCurl($queryString, $core, 'apiselect');
        } catch (Exception $e) {
            echo $e->getMessage();
            echo Hal_Search_Solr_Api::formatErrorAsSolr($e->getMessage() . 'See help : /docs');
            return false;
        }

        if (!$curlResult) {
            echo Hal_Search_Solr_Api::formatErrorAsSolr('Error. See help : /docs');
            exit();
        }

        // tomcat header en cas d'erreur tomcat
        if (substr($curlResult, 0, 6) == '<html>') {
            echo Hal_Search_Solr_Api::formatErrorAsSolr('Error. See help : /docs');
            exit();
        }

        return $curlResult;
    }

    /**
     * Recherche dans le référentiel des metadonnées
     */
    public
    function metadatalistAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $allowedWt = array(
            'json',
            'xml',
            'csv'
        );

        $parsedArray = self::getParsedQuery($allowedWt);

        echo self::searchApi('ref_metadatalist', $parsedArray, $allowedWt);
    }

    public
    function authorAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $allowedWt = array(
            'json',
            'xml',
            'csv'
        );

        $parsedArray = self::getParsedQuery($allowedWt);

        echo self::searchApi('ref_author', $parsedArray, $allowedWt);
    }

    /**
     * Recherche dans le référentiel des revues
     */
    public
    function journalAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $allowedWt = array(
            'json',
            'xml',
            'csv'
        );

        $parsedArray = self::getParsedQuery($allowedWt);

        echo self::searchApi('ref_journal', $parsedArray, $allowedWt);
    }

    /**
     * Recherche dans le référentiel des structures
     */
    public
    function structureAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $allowedWt = array(
            'json',
            'xml',
            'xml-tei',
            'csv'
        );

        $parsedArray = self::getParsedQuery($allowedWt);

        $curlResult = self::searchApi('ref_structure', $parsedArray, $allowedWt);

        if ((in_array('wt=phps', $parsedArray)) && (in_array('fl=label_xml', $parsedArray))) {
            echo Hal_Search_Solr_Api::formatOutputAsTeiStructure($curlResult);
            exit();
        }

        echo $curlResult;
    }

    /**
     * Recherche dans le référentiel des projets ANR
     */
    public
    function anrprojectAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $allowedWt = array(
            'json',
            'xml',
            'csv'
        );

        $parsedArray = self::getParsedQuery($allowedWt);

        echo self::searchApi('ref_projanr', $parsedArray, $allowedWt);
    }

    /**
     * Recherche dans le référentiel des projets européens
     */
    public
    function europeanprojectAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $allowedWt = array(
            'json',
            'xml',
            'csv'
        );

        $parsedArray = self::getParsedQuery($allowedWt);

        echo self::searchApi('ref_projeurop', $parsedArray, $allowedWt);
    }

    /*
     * Liste les instances
     */

    public
    function instanceAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $wt = $this->getRequest()->getParam('wt', 'json');

        $xml = "<?xml version='1.0' encoding='UTF-8'?>" . PHP_EOL;
        $out['response'] = [];
        $xml .= "<response>" . PHP_EOL;
        $instances = Hal_Site_Portail::getInstances();

        $xml .= "<result name='response' numFound='" . count($instances) . "'>" . PHP_EOL;
        $out['response']['numFound'] = count($instances);
        $out['response']['docs'] = [];
        foreach ($instances as $instance) {
            $xml .= "<doc>" . PHP_EOL;
            $xml .= "<str name='id'>" . $instance['SID'] . "</str>" . PHP_EOL;
            $xml .= "<str name='code'>" . $instance['SITE'] . "</str>" . PHP_EOL;
            $xml .= "<str name='name'>" . $instance['NAME'] . "</str>" . PHP_EOL;
            $xml .= "<str name='url'>" . $instance['URL'] . "</str>" . PHP_EOL;
            $xml .= "</doc>" . PHP_EOL;
            $out['response']['docs'][] = ['id' => $instance['SID'], 'code' => $instance['SITE'], 'name' => $instance['NAME'], 'url' => $instance['URL']];
        }
        $xml .= "</result>" . PHP_EOL;
        $xml .= "</response>" . PHP_EOL;

        switch ($wt) {
            case "json":
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($out);
                break;
            default:
                header('Content-Type: text/xml; charset=utf-8');
                echo $xml;
        }
    }

    /*
     * Liste les types de documents pour une instance donnée
     */

    public
    function doctypeAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $instance = $this->getRequest()->getParam('instance_s', 'all');
        $lang = $this->getRequest()->getParam('lang', 'fr');
        $wt = $this->getRequest()->getParam('wt', 'json');


        if (!in_array($wt, array('json', 'xml'))) {
            $wt = 'json';
        }


        $availableLanguages = Hal_Translation_Plugin::getAvalaibleLanguages();

        if (!in_array($lang, $availableLanguages)) {
            echo Hal_Search_Solr_Search::formatErrorAsSolr('Unexpected language, unsupported', $wt, true);
            exit();
        }


        $cache = Zend_Registry::get('apicache');
        // pb noms d'instance avec un '-'
        $cleanInstance = str_replace('-', '_', $instance);
        $cacheName = $cleanInstance . '_' . $lang . '_' . $wt;

        if (($output = $cache->load($cacheName)) === false) {
            $website = Hal_Site::exist($instance, Hal_Site::TYPE_PORTAIL, true);
            if ($instance == 'all') {
                // on retourne la concatenation de tous les types de tous les portails
                $languages = $availableLanguages;
            } else if ($website) {
                $languages = $website->getLanguages();
            } else {
                // le portail n'existe pas
                echo Hal_Search_Solr_Search::formatErrorAsSolr('The requested instance does not exist. Please consult our documentation.', $wt, true);
                exit();
            }

            if (!in_array($lang, $languages)) {
                // La langue demandée n'appartient pas aux langues du portail
                echo Hal_Search_Solr_Search::formatErrorAsSolr('This portal does not support the requested language.', $wt, true);
                exit();
            }
            $languages = [$lang];

            $output = Hal_Search_Solr_Api::getInstanceDocTypes($instance, $lang, $languages, $wt);
            $cache->save($output, $cacheName);
        }

        switch ($wt) {
            case "json":
                header('Content-Type: application/json; charset=utf-8');
                break;
            case "xml":
                header('Content-Type: text/xml; charset=utf-8');
                break;
        }
        echo $output;
    }

    public
    function metadataAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        // Le parametre portail doit être définit, si il est vide je prends le
        // portail par défaut
        // HAL avec sid = 1
        $instance = $this->getRequest()->getParam('instance_s', 'hal');
        $lang = $this->getRequest()->getParam('lang', null);
        $typdocParam = $this->getRequest()->getParam('docType_s', 'UNDEFINED');
        $wt = $this->getRequest()->getParam('wt', 'json');

        $proprietesSite = Hal_Site::exist($instance, Hal_Site::TYPE_PORTAIL, true);
        if (!$proprietesSite) {
            echo Hal_Search_Solr_Search::formatErrorAsSolr('The portal "' . $instance . '" does not exist.', $wt, true);
            exit();
        }
        $languages = $proprietesSite->getLanguages();

        if ($lang != null) {
            if (!in_array($lang, $languages)) {
                // La langue demandée n'appartient pas aux languages du portail
                echo Hal_Search_Solr_Search::formatErrorAsSolr('The portal does not support the requested lang.', $wt, true);
                exit();
            }
            $languages = array(
                $lang
            );
        }

        /*
         * Chargement de la configuration avec les .ini correspondants
         */
        $chemin1 = DEFAULT_CONFIG_PATH . INSTANCEPREFIX . '/meta.ini';
        $chemin2 = SPACE_DATA . '/' . SPACE_PORTAIL . '/' . $instance . '/' . CONFIG . 'meta.ini';

        // Recherche des domaines ou existent des metas spécifiques
        $ini = Hal_Ini::file_merge(array(
            $chemin1 => array('domain'),
            $chemin2 => array('domain')
        ));
        $domaines = $ini['specific'];

        // Recherche de toutes les metas
        $ini = Hal_Ini::file_merge(array(
            $chemin1 => array('metas'),
            $chemin2 => array('metas')
        ));
        $tableauMetas = $this->lireMetas($ini, $languages);


        // Les metas par domaines
        foreach ($domaines as $domaine) {
            $ini = Hal_Ini::file_merge(array(
                $chemin1 => $domaine,
                $chemin2 => $domaine
            ), array(
                "skipExtends" => true
            ));
            $this->ajouteMeta('domain', $domaine, $this->lireMetas($ini, $languages), $tableauMetas);
        }


        $tableauTypdoc = Hal_Search_Solr_Api::tableauTypdoc($instance);
        if (array_key_exists($typdocParam, $tableauTypdoc)) {
            $ini = Hal_Ini::file_merge(array(
                $chemin1 => array($typdocParam),
                $chemin2 => array($typdocParam)
            ), array(
                'skipExtends' => true
            ));
            $this->ajouteMeta('docType_s', $typdocParam, $this->lireMetas($ini, $languages), $tableauMetas);
        } else {
            echo Hal_Search_Solr_Search::formatErrorAsSolr('The parameter "docType_s", value "' . $typdocParam . '" is not valid.', $wt, true);
            exit();
        }

        // Entete XML;
        $xml = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $xml .= "<response>\n";
        $xml .= "\t<lst name='responseHeader'>\n";
        $xml .= "\t<int name='status'>0</int>\n";
        $xml .= "\t<int name='QTime'>0</int>\n";
        $xml .= "\t<lst name='params'>\n";
        $xml .= "\t<bool name='indent'>true</bool>\n";
        $xml .= "\t<str name='instance_s'>" . $instance . "</str>\n";
        $xml .= isset($typdocParam) ? "\t<str name='docType_s'>" . $typdocParam . "</str>\n" : "";
        $xml .= isset($lang) ? "\t<str name='lang'>" . $lang . "</str>\n" : "";
        $xml .= "\t<str name='wt'>" . $wt . "</str>\n";
        $xml .= "\t</lst>\n";
        $xml .= "\t</lst>\n";

        $xmlMeta = "";
        $nbMeta = 0;
        foreach ($tableauMetas as $nomMeta => $meta) {
            if ($meta['type'] != "invisible") {
                $xmlMeta .= $this->toXml($nomMeta, $meta);
                $nbMeta++;
            }
        }
        //méta file
        if (isset($typdocParam)) {
            if (!in_array($typdocParam, Hal_Settings::getTypdocNotice())) {
                $meta = [];
                $meta['label'] = ['fr' => 'fichier', 'en' => 'file'];
                if (Hal_Settings::getFileLimit($typdocParam) == 1) {
                    $meta['type'] = 'single';
                } else {
                    $meta['type'] = 'multiple';
                }
                if (in_array($typdocParam, Hal_Settings::getTypdocFulltext())) {
                    $meta['obligatoire'] = true;
                }
                $xmlMeta .= $this->toXml('file', $meta);
                $nbMeta++;
            }
        }
        $xml .= "\t<result name='response' numFound='" . $nbMeta . "'>\n";
        $xml .= $xmlMeta;
        $xml .= "\t</result>\n";
        $xml .= "\t</response>";

        switch ($wt) {
            case "json":
                header('Content-Type: application/json; charset=utf-8');
                print(Zend_Json::fromXml($xml));
                break;
            default:
                header('Content-Type: text/xml; charset=utf-8');
                print($xml);
        }
    }

    public
    function lireMetas($ini, $languages)
    {
        $tableauMetas = array();
        // cas d'un typdoc sans meta specifique
        if (count($ini) == 0) {
            return $tableauMetas;
        }
        foreach ($ini['elements'] as $nomElement => $element) {
            foreach ($element as $cle => $val) {
                switch ($cle) {
                    case "type":
                        switch ($val) {
                            case "invisible":
                                $type = "invisible";
                                break;
                            case "hr":
                                $type = null;
                                break;
                            case "date":
                                $type = "date";
                                break;
                            case "referentiel":
                            case "select":
                                $type = "liste";
                                break;
                            case "multiReferentiel":
                            case "multiselect":
                            case "thesaurus":
                                $type = "liste_multiple";
                                break;
                            case "identifiant":
                            case "multiTextSimple":
                            case "multiTextSimpleLang":
                            case "multiTextAreaLang":
                            case "multiTextArea":
                                $type = "texte_multiple";
                                break;
                            default:
                                $type = "texte";
                        }
                        break;
                    case "options":
                        if (isset($val['label']) && strlen($val['label']) > 0) {
                            foreach ($languages as $lang) {
                                $tableauMetas[$nomElement]['label'][$lang] = $this->view->translate($val['label'], $lang);
                            }
                        }
                        if (isset($val['description']) && strlen($val['description']) > 0) {
                            foreach ($languages as $lang) {
                                $tableauMetas[$nomElement]['description'][$lang] = $this->view->translate($val['description'], $lang);
                            }
                        }
                        if (isset($val['required']) && $val['required'] == 1) {
                            $tableauMetas[$nomElement]['obligatoire'] = 1;
                        }
                        break;
                }
                if (isset($type) && $nomElement != 'type') {
                    $tableauMetas[$nomElement]['type'] = $type;
                }
            }
        }
        return $tableauMetas;
    }

    public
    function ajouteMeta($type, $valeur, $tableauMetaAAjouter, &$tableauMetas)
    {
        foreach ($tableauMetaAAjouter as $nomMeta => $meta) {
            // rajout du caractere obligatoire
            if (isset($meta['obligatoire'])) {
                $tableauMetas[$nomMeta]['obligatoire'][$type] = isset($tableauMetas[$nomMeta]['obligatoire'][$type]) && is_array($tableauMetas[$nomMeta]['obligatoire'][$type]) ? array_unique(
                    array_merge($tableauMetas[$nomMeta]['obligatoire'][$type], array(
                        $valeur
                    ))) : array(
                    $valeur
                );
            }
            // rajout d'eventuel libelle et description par type
            if (isset($meta['label'])) {
                $tableauMetas[$nomMeta]['label'][$type][$valeur] = $meta['label'];
            }
            if (isset($meta['description'])) {
                $tableauMetas[$nomMeta]['description'][$type][$valeur] = $meta['description'];
            }
            if (isset($meta['type'])) {
                $tableauMetas[$nomMeta]['type'] = $meta['type'];
                $tableauMetas[$nomMeta]['docType_s'][$type] = isset($tableauMetas[$nomMeta]['docType_s'][$type]) && is_array($tableauMetas[$nomMeta]['docType_s'][$type]) ? array_unique(array_merge($tableauMetas[$nomMeta]['docType_s'][$type], array(
                    $valeur
                ))) : array(
                    $valeur
                );
            }
        }
    }

    /*
     * Fonction qui permet de rajouter des metas spécifiques par type au tableau
     * de metas Pour les meta contextuelle meta['contexte']['propriete'] =
     * 'valeurContexte'; exemple meta['KEYWORD']['domain']['obligatoire'] =
     * 'shs' ou meta['JEL']['typdoc']['present'] = 'PRES_CONF' type : domain /
     * typdoc valeur shs / PRES_CONF keyword['obligatoire'] = 1 ou alors type =
     * typdoc valeur = JEL PRES_CONF['type'] = texte_multiple
     */

    public
    function toXml($nomMeta, $meta)
    {
        $sortie = "<doc>\n";
        $sortie .= "\t<str name='docid'>" . $nomMeta . "</str>\n";
        if ($meta['type'] == 'liste_multiple') {
            $sortie .= "\t<arr name='type'>liste</arr>\n";
        } else
            if ($meta['type'] == 'texte_multiple') {
                $sortie .= "\t<arr name='type'>texte</arr>\n";
            } else {
                $sortie .= "\t<str name='type'>" . $meta['type'] . "</str>\n";
            }
        $sortie .= $this->ecrireMetaTraduite('label', $meta);
        $sortie .= $this->ecrireMetaTraduite('description', $meta);
        $sortie .= isset($meta['obligatoire']) ? $this->ecrireChampMultivalue($meta['obligatoire'], "required") : "";
        $sortie .= isset($meta['docType_s']) ? $this->ecrireChampMultivalue($meta['docType_s'], "present") : "";
        $sortie .= "</doc>\n";
        return $sortie;
    }

    /*
     * Fonction qui parse le fichier de configuration
     */

    public
    function ecrireMetaTraduite($nomMeta, $meta)
    {
        $sortie = "";
        if (isset($meta[$nomMeta])) {
            foreach ($meta[$nomMeta] as $cle => $val) {
                if (is_array($val)) {
                    $sortie .= "\t<arr name='specificLabels'>\n";
                    foreach ($val as $critere => $libelles) {
                        $libelleCle = ($cle == 'typdoc') ? "docType_s" : $cle;
                        foreach ($libelles as $lang => $traduction)
                            $sortie .= "\t\t<str name='" . $lang . "_" . $nomMeta . "' " . $libelleCle . "='" . $critere . "'>" . $traduction . "</str>\n";
                    }
                    $sortie .= "\t</arr>\n";
                } else {
                    $sortie .= "\t<str name='" . $cle . "_" . $nomMeta . "'>" . $val . "</str>\n";
                }
            }
        }
        return $sortie;
    }

    public
    function ecrireChampMultivalue($champs, $nomChamp)
    {
        $sortie = "";
        if (is_array($champs)) {
            $sortie .= "\t<arr name='" . $nomChamp . "'>\n";
            foreach (array_unique($champs) as $champ => $valeur) {

                $champ = is_int($champ) ? "docType_s" : $champ;
                if (is_array($valeur)) {
                    foreach ($valeur as $val) {
                        $sortie .= "\t\t<str name='" . $champ . "'>" . $val . "</str>\n";
                    }
                } else {
                    $sortie .= "\t\t<str name='" . $champ . "'>" . $valeur . "</str>\n";
                }
            }
            $sortie .= "\t</arr>\n";
        } else {
            $sortie .= "\t<str name='" . $nomChamp . "'>1</str>\n";
        }
        return $sortie;
    }

}
