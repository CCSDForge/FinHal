<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 07/02/17
 * Time: 14:12
 */
class Hal_Submit_Step_Meta extends Hal_Submit_Step
{
    protected $_name = "meta";

    /**
     * Initialisation de l'étape métadonnées
     * @param Hal_View $view
     * @param Hal_Document $document
     * @param string $type
     * @param bool $verifValidity
     */
    public function initView(Hal_View &$view, Hal_Document &$document, $type, $verifValidity = false)
    {
        $view->metamode = $this->_mode;

        $view->valid = $this->_validity;
        $this->initMetaForm($view, $document, $document->getMeta(), $verifValidity);
    }

    /**
     * Affiche de l'arbre des domaines
     *
     * STATUT
     * 0 : caché
     * 1 : affiché
     * 2 : ouvert (répertoire)
     * 3 : fermé (répertoire)
     *
     * @param $domainData
     * @param $domainAsArray
     * @return bool|string
     */
    protected function getDisplayCode($domainData, $domainAsArray, $parentVisibility)
    {
        if (is_array($domainData)) {
            $code = '';
            foreach ($domainData as $key => $subdomain) {

                $keyVisible = false;

                if (!$parentVisibility) {
                    // Le parent n'est pas visible donc les sous branches ne sont pas visibles
                    $code .= '0';
                } else if (!empty($subdomain)) {
                    // On est à un noeud qui a des enfants => il est soit ouvert=2, soit fermé=3
                    $code .= in_array($key, $domainAsArray) ? '2' : '3';
                    $keyVisible = true;
                } else {
                    // On est à une feuille visible avec son parent est visible
                    $code .= '1';
                }

                if (!empty($subdomain)) {
                    // On ajoute le code de ses enfants
                    $code .= $this->getDisplayCode($subdomain, $domainAsArray, $keyVisible);
                }
            }
            return $code;
        } else {
            // Si c'est une feuille, soit elle est visible = 1, soit cachée = 0
            return in_array($domainData, $domainAsArray) ? '1' : '0';
        }
    }

    /**
     * @param $data
     * @param Zend_Form $form
     */
    protected function openDomains($data, Zend_Form &$form)
    {
        $domainThesaurus = $form->getElement('domain');
        $lastDomain = isset($data['domain']) && !empty($data['domain']) ? end($data['domain']) : '';

        $display = '';

        if (!empty($lastDomain)) {
            // Ouverture sur le dernier élément
            $domainData = $domainThesaurus->getData();

            $domainAsArray = Ccsd_Tools_String::getHalDomainPaths($lastDomain);
            $display = $this->getDisplayCode($domainData, $domainAsArray, true);


        } else {
            //Ouverture simple
            $display = '1';
        }

        $domainThesaurus->setStatus_tree($display);
        //$domainThesaurus->setTypeahead_value('');
    }

    /**
     * @param Hal_View           $view
     * @param Hal_Document        $document
     * @param null                $data
     * @param array               $display
     * @param bool                $verifValidity
     */
    protected function initMetaForm(Hal_View &$view, Hal_Document &$document, $data = null, $verifValidity = false)
    {
        $view->form = Hal_Submit_Manager::getMetadataForm($document->getTypdoc(), $document->getAllDomains());

        $view->form->getElement('type')->setValue($document->getTypdoc());

        // On traite l'interdisciplinarité
        $data = Hal_Document_Meta_Domain::explodeInterDomains($data, $view->form);

        // On gère l'ouverture des domaines s'il n'y en a aucun
        $this->openDomains($data, $view->form);

        $view->form->populate($data);

        if ($verifValidity) {
            $data["type"] = $document->getTypdoc();

            // Dans le cas où la métadonnée A Paraitre existe, la date de publication n'est pas obligatoire
            if (isset($data['inPress']) && $data['inPress'] && (!isset($data['date']) || empty($data['date']))) {
                $view->form->getElement('date')->setRequired(false);
            }

            // Pour une "Autre Publication", on rend obligatoire soit la description, soit le nom de la revue, soit le titre de l'ouvrage
            if ('OTHER' == $document->getTypdoc()) {
                if (isset($data['journal']) && !empty($data['journal'])) {
                    $view->form->getElement('bookTitle')->setRequired(false);
                    $view->form->getElement('description')->setRequired(false);
                } else if (isset($data['bookTitle']) && !empty($data['bookTitle'])) {
                    $view->form->getElement('journal')->setRequired(false);
                    $view->form->getElement('description')->setRequired(false);
                } else {
                    $view->form->getElement('journal')->setRequired(false);
                    $view->form->getElement('bookTitle')->setRequired(false);
                }
            }

            $view->form->isValid($data);
        }
    }

    /**
     * @param string $name   // Nom de referentiel
     * @param $value
     * @return Ccsd_Referentiels_Abstract
     */
    protected function filterSimpleObject($name, $value)
    {
        // FILTRER LES STRUCTURES NON CRÉES
        if (isset($value) && is_array($value)) {
            $className = 'Ccsd_Referentiels_'.$name;
            return new $className(0, $value);
        }

        return $value;
    }

    /**
     * @param string $name   // Nom de referentiel
     * @param $value
     * @return array
     */
    protected function filterComplexeObject($name, $value)
    {
        if (isset($value)) {
            $return = [];
            foreach ($value as $obj) {
                if (is_array($obj)) {
                    $className = 'Ccsd_Referentiels_'.$name;
                    $return[] = new $className(0, $obj);
                } else {
                    $return[] = $obj;
                }
            }
            return $return;
        }
        return $value;
    }

    /**
     * @param Hal_Document $document
     * @param string|null  $SubmitType
     * @param array        $params
     * Warning: step other than recap can call with $SubmitType = null
     */
    public function submit(Hal_Document &$document, $SubmitType, $params)
    {

        if (isset($params['journal'])) {
            $params['journal'] = $this->filterSimpleObject('Journal', $params['journal']);
        }

        if (isset($params['anrProject'])) {
            $params['anrProject'] = $this->filterComplexeObject('Anrproject', $params['anrProject']);
        }

        if (isset($params['europeanProject'])) {
            $params['europeanProject'] = $this->filterComplexeObject('Europeanproject', $params['europeanProject']);
        }

        // On traite l'interdisciplinarité
        $params = Hal_Document_Meta_Domain::mergeInterDomains($params);

        // On ne veut pas remplacer des données qui n'auraient pas encore servi pour ce type de document mais pourrait servir plus tard (si on change de type de doc)
        // C'est pourquoi on fait un merge et non un setMeta
        //$halMeta = new Hal_Document_Metadatas();
        //$halMeta->addMetasFromArray($params, 'web', Hal_Auth::getUid());
        //$document->mergeHalMeta($halMeta);

        // todo : revoir ça !!!!! Le probleme étant que si on fait un merge, on ne peut plus supprimer de métadonnées
        // todo : pour toutes les métadonnées qui n'ont pas changé de valeur, ne pas les modifier car potentiellement on mooifie la source / le uid / etc

        $uid = Hal_Auth::getUid();

        $document->setMetas($params, $uid);
    }
}