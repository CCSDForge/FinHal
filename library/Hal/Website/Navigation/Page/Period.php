<?php

/**
 *  Parcour par période
 * @author tournoy
 *
 */
class Hal_Website_Navigation_Page_Period extends Hal_Website_Navigation_Page
{

    /**
     * Controleur
     *
     * @var string
     */
    protected $_controller = 'browse';
    /**
     * Action
     *
     * @var string
     */
    protected $_action = 'period';
    /**
     * Filtre utilisateur
     *
     * @var array
     */
    protected $_filter = array();

    /**
     * Specifies the field to facet by range.
     *
     * @var string
     */
    protected $_facetRange;

    /**
     * Specifies the start of the facet range.
     *
     * @var string
     */
    protected $_facetRangeStart;

    /**
     * Specifies the end of the facet range.
     *
     * @var string
     */
    protected $_facetRangeEnd;

    /**
     * Specifies the span of the range as a value to be added to the lower bound.
     *
     * @var string
     */
    protected $_facetRangeGap;

    /**
     * A boolean parameter that specifies how Solr handles a range gap that cannot be evenly divided between the range start and end values.
     * If true, the last range constraint will have the facet.range.end value an upper bound.
     * If false, the last range will have the smallest possible upper bound greater then facet.range.end such that the range is the exact width of the specified range gap.
     * The default value for this parameter is false.
     *
     * @var boolean
     */
    protected $_facetRangeHardend;

    /**
     * Specifies inclusion and exclusion preferences for the upper and lower bounds of the range.
     * Parameter allowing closing or opening of the compartments defined by the boundaries and the gap
     * See the facet.range.include topic for more detailed information.
     *
     * @var string
     */
    protected $_facetRangeInclude;

    /**
     * Specifies counts for Solr to compute in addition to the counts for each facet range constraint.
     *
     * @var string
     */
    protected $_facetRangeOther;

    /**
     * Tri de la plage (asc | desc)
     *
     * @var string
     */
    protected $_rangeSorting;


    /**
     * Caractères utilisés pour inclure ou exclure des bornes dans les plages de dates
     *  en fonction des paramètres de l'utilisateur
     * [ ] incluent la borne
     * { } excluent la borne
     *
     * @var array
     */
    protected $_facetRangeIncludeRequestBoundChars;

    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [
        'facetrange'        => 'setFacetRange',
        'facetrangestart'   => 'setFacetRangeStart',
        'facetrangeend'     => 'setFacetRangeEnd',
        'facetrangegap'     => 'setFacetRangeGap',
        'facetrangehardend' => 'setFacetRangeHardend',
        'facetrangeinclude' => 'setFacetRangeInclude',
        'facetrangeother'   => 'setFacetRangeOther',
        'rangesorting'      => 'setRangeSorting',
        'filter'            => 'setFilter',
        ];

    /**
     * Chargement de la page
     *
     * @see Ccsd_Website_Navigation_Page::load()
     */
    public function load()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from('WEBSITE_NAVIGATION', 'PARAMS')->where('SID = ?', $this->getSid())->where('TYPE_PAGE = ?', __CLASS__);
        $dbRes = $db->fetchOne($sql);

        if ($dbRes) {
            $str = unserialize($dbRes);
            parent::setOptions($str);

            $this->setFacetRangeIncludeRequestBoundChars($this->defineFacetRangeIncludeRequestBoundChars());

            return $this;
        }
        return null;
    }

    /**
     * Caractères utilisés pour définir les bornes de dates dans une requête, en fonction des choix de l'utilisateur
     * @return array Caractères utilisés pour définir les bornes de dates
     */
    private function defineFacetRangeIncludeRequestBoundChars()
    {
        switch ($this->getFacetRangeInclude()) {
            case 'upper':
                //All gap-based ranges include their upper bound.
                // all - 1st - last - before - after groups
                $requestBoundChars['allGapLower'] = '{';
                $requestBoundChars['allGapUpper'] = ']';

                // bewteen 1 st and last range group
                $requestBoundChars['betweenGapLower'] = '{';
                $requestBoundChars['betweenGapUpper'] = ']';

                //before groups
                $requestBoundChars['beforeGapLower'] = '{';
                $requestBoundChars['beforeGapUpper'] = ']';

                //after groups
                $requestBoundChars['afterGapLower'] = '{';
                $requestBoundChars['afterGapUpper'] = ']';
                break;
            case 'edge':

                // le 1er et dernier group d'années ont un comportement différent des autres
                // The first and last gap ranges include their edge bounds (lower for the first one, upper for the last one) even if the corresponding upper/lower option is not specified.
                $requestBoundChars['allGapLower'] = '{';
                $requestBoundChars['allGapUpper'] = '}';

                //TODO traitement different du groupe 1 et 2

                $requestBoundChars['firstGapLower'] = '{';
                $requestBoundChars['firstGapUpper'] = ']';

                $requestBoundChars['lastGapLower'] = '[';
                $requestBoundChars['lastGapUpper'] = '}';

                $requestBoundChars['betweenGapLower'] = '[';
                $requestBoundChars['betweenGapUpper'] = '}';

                $requestBoundChars['beforeGapLower'] = '[';
                $requestBoundChars['beforeGapUpper'] = '}';
                $requestBoundChars['afterGapLower'] = '[';
                $requestBoundChars['afterGapUpper'] = '}';

                break;
            case 'outer':
                //The "before" and "after" ranges will be inclusive of their bounds, even if the first or last ranges already include those boundaries.
                $requestBoundChars['allGapLower'] = '{';
                $requestBoundChars['allGapUpper'] = '}';

                $requestBoundChars['betweenGapLower'] = '{';
                $requestBoundChars['betweenGapUpper'] = ']';

                $requestBoundChars['beforeGapLower'] = '[';
                $requestBoundChars['beforeGapUpper'] = ']';
                $requestBoundChars['afterGapLower'] = '[';
                $requestBoundChars['afterGapUpper'] = ']';

                break;
            case 'all':
                //Includes all options: lower, upper, edge, outer.
                $requestBoundChars['allGapLower'] = '[';
                $requestBoundChars['allGapUpper'] = ']';

                $requestBoundChars['betweenGapLower'] = '[';
                $requestBoundChars['betweenGapUpper'] = ']';


                $requestBoundChars['beforeGapLower'] = '[';
                $requestBoundChars['beforeGapUpper'] = ']';
                $requestBoundChars['afterGapLower'] = '[';
                $requestBoundChars['afterGapUpper'] = ']';
                break;
            case 'lower':
                //  All gap-based ranges include their lower bound.
                //break omitted

            default:
                // default == lower
                $requestBoundChars['allGapLower'] = '[';
                $requestBoundChars['allGapUpper'] = '}';

                $requestBoundChars['betweenGapLower'] = '[';
                $requestBoundChars['betweenGapUpper'] = '}';


                $requestBoundChars['beforeGapLower'] = '[';
                $requestBoundChars['beforeGapUpper'] = '}';
                $requestBoundChars['afterGapLower'] = '[';
                $requestBoundChars['afterGapUpper'] = '}';
                //By default, the ranges used to compute range faceting between facet.range.start and facet.range.end are inclusive of their lower bounds and exclusive of the upper bounds.
                break;
        }

        return $requestBoundChars;

    }

    /**
     * @return string
     */
    public function getFacetRangeInclude()
    {
        return $this->_facetRangeInclude;
    }

    /**
     *
     * @param string $_facetRangeInclude
     * @return Hal_Website_Navigation_Page_Period
     */
    public function setFacetRangeInclude($_facetRangeInclude = '')
    {
        switch ($_facetRangeInclude) {

            case 'lower' :
                // All gap-based ranges include their lower bound.
            case 'upper' :
                // All gap-based ranges include their upper bound.
            case 'edge' :
                // The first and last gap ranges include their edge bounds (lower for the first one, upper for the last one) even if the corresponding upper/lower option is not specified.
            case 'outer' :
                // The "before" and "after" ranges will be inclusive of their bounds, even if the first or last ranges already include those boundaries.
            case 'all' :
                // Includes all options: lower, upper, edge, outer.
                $this->_facetRangeInclude = $_facetRangeInclude;
                break;

            default :
                $this->_facetRangeInclude = 'lower';
                break;
        }

        return $this;
    }

    /**
     * Retourne les informations complémentaires spécifiques à la page
     *
     * @see Ccsd_Website_Navigation_Page::getSuppParams()
     */
    public function getSuppParams()
    {
        return serialize(array(
            'facetRange' => $this->getFacetRange(),
            'facetRangeStart' => $this->getFacetRangeStart(),
            'facetRangeEnd' => $this->getFacetRangeEnd(),
            'facetRangeGap' => $this->getFacetRangeGap(),
            'facetRangeHardend' => $this->getFacetRangeHardend(),
            'facetRangeInclude' => $this->getFacetRangeInclude(),
            'facetRangeOther' => $this->getFacetRangeOther(),
            'rangeSorting' => $this->getRangeSorting(),
            'filter' => $this->getFilter()
        ));
    }

    /**
     * @return string : the facet range
     */
    public function getFacetRange()
    {
        return $this->_facetRange;
    }

    /**
     * @param string $_facetRange
     * @return Hal_Website_Navigation_Page_Period
     */
    public function setFacetRange($_facetRange)
    {
        $this->_facetRange = $_facetRange;
        return $this;
    }

    /**
     * @return string : the facet range start
     */
    public function getFacetRangeStart()
    {
        return $this->_facetRangeStart;
    }

    /**
     * @param string $_facetRangeStart
     * @return Hal_Website_Navigation_Page_Period
     */
    public function setFacetRangeStart($_facetRangeStart)
    {
        $this->_facetRangeStart = $_facetRangeStart;
        return $this;
    }

    /**
     * @return string
     */
    public function getFacetRangeEnd()
    {
        return $this->_facetRangeEnd;
    }

    /**
     * @param string $_facetRangeEnd
     * @return Hal_Website_Navigation_Page_Period
     */
    public function setFacetRangeEnd($_facetRangeEnd)
    {
        $this->_facetRangeEnd = $_facetRangeEnd;
        return $this;
    }

    /**
     * @return string
     */
    public function getFacetRangeGap()
    {
        return $this->_facetRangeGap;
    }

    /**
     * @param string $_facetRangeGap
     * @return Hal_Website_Navigation_Page_Period
     */
    public function setFacetRangeGap($_facetRangeGap)
    {
        $this->_facetRangeGap = $_facetRangeGap;
        return $this;
    }

    /**
     * @return bool
     */
    public function getFacetRangeHardend()
    {
        return $this->_facetRangeHardend;
    }

    /**
     * @param $_facetRangeHardend
     * @return $this
     */
    public function setFacetRangeHardend($_facetRangeHardend)
    {
        $this->_facetRangeHardend = $_facetRangeHardend;
        return $this;
    }

    /**
     * @return string
     */
    public function getFacetRangeOther()
    {
        return $this->_facetRangeOther;
    }

    /**
     * @param string $_facetRangeOther
     * @return Hal_Website_Navigation_Page_Period
     */
    public function setFacetRangeOther($_facetRangeOther)
    {
        switch ($_facetRangeOther) {

            case 'before' :
                // All records with field values lower then lower bound of the first range.
            case 'after' :
                // All records with field values greater then the upper bound of the last range.
            case 'between' :
                // All records with field values between the start and end bounds of all ranges.
            case 'none' :
                // Do not compute any counts.
            case 'all' :
                // Compute counts for before, between, and after.
                $this->_facetRangeOther = $_facetRangeOther;
                break;
            default :
                $this->_facetRangeOther = 'all';
                break;
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getRangeSorting()
    {
        return $this->_rangeSorting;
    }

    /**
     *
     * @param string $_rangeSorting
     * @return Hal_Website_Navigation_Page_Period
     */
    public function setRangeSorting($_rangeSorting)
    {
        if (($_rangeSorting != 'asc') || ($_rangeSorting != 'desc')) {
            $_rangeSorting = 'desc';
        }
        $this->_rangeSorting = $_rangeSorting;
        return $this;
    }

    /**
     * Retourne le filter
     *
     * @return string[]
     */
    public function getFilter()
    {
        return $this->_filter;
    }

    /**
     * Initialisation du filter
     * @param string|string[] $filter
     * @return string[]
     */
    public function setFilter($filter)
    {
        if (!is_array($filter)) {
            $filter = explode(';', $filter);
        }
        $this->_filter = $filter;
        return $filter;
    }

    /**
     * Retour du formulaire de création de la page
     * @param int $pageidx
     * @return Ccsd_Form
     * @see Ccsd_Website_Navigation_Page::getForm()
     */
    public function getForm($pageidx)
    {
        parent::getForm($pageidx);
        try {
            $this->_form->addElement('select', 'facetRange', array(
                'required' => true,
                'label' => 'Champs à utiliser',
                'class' => '',
                'value' => $this->getFacetRange(),
                'multioptions' => array(
                    'producedDateY_i' => 'hal_producedDateY_i',
                    'submittedDateY_i' => 'hal_submittedDateY_i',
                    'writingDateY_i' => 'hal_writingDateY_i',
                    'defenseDateY_i' => 'hal_defenseDateY_i'
                ),
            'belongsTo' => 'pages_' . $pageidx
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add select element to form");
        }
        try {
            $this->_form->addElement('text', 'facetRangeStart', array(
                'required' => true,
                'description' => 'Début de la période (p. ex. : 2000)',
                'label' => 'Première année',
                'value' => $this->getFacetRangeStart(),
                'belongsTo' => 'pages_' . $pageidx,
                'validators' => array(
                    array(
                        'validator' => 'Int',
                        'options' => array(
                            'messages' => 'Doit être un nombre.'
                        ),
                        'breakChainOnFailure' => true
                    ),
                    array(
                        'validator' => 'between',
                        'options' => array(
                            'min' => 1700,
                            'max' => ( int )date('Y') + 1,
                            'messages' => array(
                                Zend_Validate_Between::NOT_BETWEEN => 'Entre %min% to %max% .'
                            )
                        )
                    )
                )
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add text facetRangeStart element to form");
        }
        try {
            $this->_form->addElement('text', 'facetRangeEnd', array(
                'label' => 'Dernière année',
                'description' => "Dernière valeur possible pour la période" . ' ' . "(p. ex. : " . date('Y') . ") " . "<strong>Laisser vide pour l'année en cours</strong>",
                'value' => $this->getFacetRangeEnd(),
                'belongsTo' => 'pages_' . $pageidx,
                'validators' => array(
                    array(
                        'Between',
                        false,
                        array(
                            'min' => 1800,
                            'max' => ( int )date('Y') + 10
                        )
                    )
                )
            ));
            } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add text facetRangeEnd element to form");
        }
        try {
            $this->_form->addElement('text', 'facetRangeGap', array(
                'required' => true,
                'label' => "Intervalle : taille des groupes d'années",
                'description' => 'Nombre de valeurs pour chaque groupe (p. ex. 5 pour des groupes de 5 ans [1990-1995] ; [1995-2000], etc.)',
                'value' => $this->getFacetRangeGap(),
                'belongsTo' => 'pages_' . $pageidx,
                'validators' => array(
                    array(
                        'Between',
                        false,
                        array(
                            'min' => 1,
                            'max' => 100
                        )
                    )
                )
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add text facetRangeEnd element to form");
        }

        try {
            $this->_form->addElement('hidden', 'facetRangeHardend', array(
                'value' => 'true'
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add hidden facetRangeHardend element to form");
        }

        try {
            $this->_form->addElement('select', 'facetRangeOther', array(

                'label' => 'Valeurs à calculer au delà de la première et dernière année',
                'description' => 'Paramètre qui spécifie quelles valeurs, en dehors des groupes définis, doivent être ajoutées au résultat',
                'value' => $this->getFacetRangeOther(),
                'multioptions' => array(
                    'all' => "Toutes : Calculer toutes les valeurs supplémentaires (Avant, Après, et Total)",
                    'before' => "Avant : toutes les valeurs qui existent avant la première année",
                    'after' => "Après : toutes les valeurs qui existent après la dernière année",
                    'between' => "Total : le total des valeurs entre la première et la dernière année",
                    'none' => "Aucune : ne pas calculer de valeurs supplémentaires"

                ),
                'belongsTo' => 'pages_' . $pageidx
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add hidden facetRangeHardend element to form");
        }
        try {
            $this->_form->addElement('select', 'facetRangeInclude', array(
                'description' => "Ce paramètre permet de choisir quelles valeurs inclure ou exclure dans les différents groupes",
                'label' => 'Inclusion dans les groupes',
                'value' => $this->getFacetRangeInclude(),
                'multioptions' => array(
                    'lower' => 'Par défaut : Les groupes incluent les valeurs de leurs années inférieures, excluent celles des années supérieures',
                    'upper' => 'Les groupes incluent les valeurs de leurs années supérieures, excluent celles des années inférieures',
                    'edge' => 'Le 1er et dernier groupe incluent les valeurs de leurs années (année inférieure pour le 1er, année supérieure pour le dernier)',
                    'outer' => 'Les groupes "Avant" et "Après" incluent les valeurs de leurs années',
                    'all' => 'Combiner toutes les options, tous les groupes incluent les valeurs supérieures et inférieures'

                ),
                'belongsTo' => 'pages_' . $pageidx
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add select facetRangeInclude element to form");
        }
        try {
            $this->_form->addElement('radio', 'rangeSorting', array(
                'label' => 'Sens de tri des groupes',
                'description' => 'P. ex. Descendant pour avoir les années récentes en premier',
                'value' => $this->getRangeSorting(),
                'multioptions' => array(
                    'asc' => 'sort_asc',
                    'desc' => 'sort_desc'
                ),
                'belongsTo' => 'pages_' . $pageidx
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add radio rangeSorting element to form");
        }
        return $this->_form;
    }

    /**
     * Conversion de la page en tableau associatif
     *
     * @see Ccsd_Website_Navigation_Page::toArray()
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array = $array + array(
                'facetRange' => $this->getFacetRange(),
                'facetRangeStart' => $this->getFacetRangeStart(),
                'facetRangeEnd' => $this->getFacetRangeEnd(),
                'facetRangeGap' => $this->getFacetRangeGap(),
                'facetRangeHardend' => $this->getFacetRangeHardend(),
                'facetRangeInclude' => $this->getFacetRangeInclude(),
                'facetRangeOther' => $this->getFacetRangeOther(),
                'rangeSorting' => $this->getRangeSorting(),
                'requestBoundChars' => $this->getFacetRangeIncludeRequestBoundChars()
            );

        $array ['filter'] = $this->getFilter();

        return $array;
    }

    /**
     * @return array
     */
    public function getFacetRangeIncludeRequestBoundChars()
    {
        if ($this->_facetRangeIncludeRequestBoundChars == null) {
            $this->defineFacetRangeIncludeRequestBoundChars();
        }
        return $this->_facetRangeIncludeRequestBoundChars;
    }

    /**
     * Caractères à utiliser pour borner les requêtes de plages de dates
     * Servira dans la vue pour générer une requête du type [2005 TO 2015}
     * @param array $facetRangeIncludeRequestBoundChars
     */
    public function setFacetRangeIncludeRequestBoundChars(array $facetRangeIncludeRequestBoundChars)
    {
        $this->_facetRangeIncludeRequestBoundChars = $facetRangeIncludeRequestBoundChars;
    }


}