<?php

/**
 * Class Hal_Website_Navigation_Page_Collections
 */
class Hal_Website_Navigation_Page_Collections extends Hal_Website_Navigation_Page
{

    const BROWSE_TYPE_COLLECTION = 'collection';
    const BROWSE_TYPE_COLLECTION_BY_CATEGORY = 'collection_by_category';
    const BROWSE_TYPE_COLLECTION_WITH_CATEGORY = 'collection_with_category';
    protected $_controller = 'browse';
    protected $_action = 'collection';
    /**
     * Champs de l'utilisateur pour faire les facettes
     *    Valeur possible: BROWSE_TYPE_COLLECTION, BROWSE_TYPE_COLLECTION_BY_CATEGORY, BROWSE_TYPE_COLLECTION_WITH_CATEGORY
     *
     * @var string
     */
    protected $_field = '';
    /**
     * Filtre utilisateur
     *
     * @var array
     */
    protected $_filter = '';
    /**
     *
     * @var boolean
     */
    protected $_multiple = true;
    /**
     * Tri utilisateur
     *
     * @var string
     */
    protected $_sort;
    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [
        'field' => 'setField',
        'filter' => 'setFilter',
        'sort' => 'setSort',
    ];

    /**
     * Formulaire
     * @param int $pageidx
     * @return Ccsd_Form
     * @see Ccsd_Website_Navigation_Page::getForm()
     */
    public function getForm($pageidx)
    {
        parent::getForm($pageidx);

        try {
            $this->_form->addElement('select', 'field', [
                'required' => true,
                'label' => "Choix de l'affichage",
                'class' => '',
                'value' => $this->getField(),
                'multioptions' => [
                    self::BROWSE_TYPE_COLLECTION => 'Liste de collections',
                    self::BROWSE_TYPE_COLLECTION_WITH_CATEGORY => 'Liste de collections + affichage de la catégorie',
                    self::BROWSE_TYPE_COLLECTION_BY_CATEGORY => 'Liste de collections, réparties par catégories'
                ],
                'belongsTo' => 'pages_' . $pageidx
            ]);
        } catch (Zend_Form_Exception $e) {
            error_log($e->getMessage());
        }

        try {
            $this->_form->addElement('text', 'filter', [
                'label' => 'Filtrer avec la collection parente :',
                'description' => "Afficher uniquement les sous-collections de la collection ci-dessous",
                'class' => 'search-collection',
                'value' => $this->getFilter(),
                'placeholder' => 'Saisissez les deux premières lettres de la collection',
                'belongsTo' => 'pages_' . $pageidx
            ]);
        } catch (Zend_Form_Exception $e) {
            error_log($e->getMessage());
        }

        try {
            $this->_form->addElement('radio', 'sort', [
                'label' => 'Tri par défaut des résultats',
                'description' => "Trier par nombre de documents ou ordre alphabétique, l'utilisateur peut changer le tri dans l'interface",
                'value' => $this->getSort(),
                'multioptions' => [
                    'index' => 'Tri par ordre alphabétique',
                    'count' => 'Tri par nombre de documents'
                ],
                'belongsTo' => 'pages_' . $pageidx
            ]);
        } catch (Zend_Form_Exception $e) {
            error_log($e->getMessage());
        }

        return $this->_form;
    }

    /**
     *
     * @return string
     */
    public function getField()
    {
        return $this->_field;
    }

    /**
     * @param string $_field
     * @return Hal_Website_Navigation_Page_Collections
     */
    public function setField($_field)
    {
        $this->_field = $_field;
        return $this;
    }

    /**
     * Retourne le filtre
     *
     * @return array
     */
    public function getFilter()
    {
        return $this->_filter;
    }

    /**
     * Initialisation du filtre
     * @param string|array $filter
     * @return void
     */
    public function setFilter($filter = [])
    {
        if ($filter == '') {
            $filter = [];
        }
        if (!is_array($filter)) {
            $filter = explode(';', $filter);
        }
        $this->_filter = $filter;
    }

    /**
     * Retourne le tri de l'utilisateur
     * @return string
     */
    public function getSort()
    {
        return $this->_sort;
    }

    /**
     * Définit le tri de l'utilisateur
     *
     * @param string $_sort
     * @return Hal_Website_Navigation_Page_Collections
     */
    public function setSort($_sort = 'index')
    {
        $this->_sort = $_sort;
        return $this;
    }

    /**
     * Chargement de la page
     * @param string $class
     * @return Hal_Website_Navigation_Page_Collections
     * @see Ccsd_Website_Navigation_Page::load()
     */
    public function load($class = '')
    {
        if ($class == '') {
            $class = __CLASS__;
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException ($class . ' is not available.');
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from('WEBSITE_NAVIGATION', 'PARAMS')->where('SID = ?', SITEID)->where('TYPE_PAGE = ?', $class)->where('ACTION = ?', $this->getAction());

        $dbRes = $db->fetchOne($sql); // only one result by construct

        if ($dbRes) {
            $array = unserialize($dbRes);
            $this->setOptions($array);
            return $this;
        }
        return null;
    }

    /**
     * @param array $options
     */
    public function setOptions($options = [])
    {
        parent::setOptions($options);
        $this->setAction($this->getField());
    }

    /**
     * Determine le nom de l'action
     *
     * @param string $_action
     * @return Hal_Website_Navigation_Page_Collections
     */
    public function setAction($_action)
    {


        switch ($_action) {
            case '':
                break;
            case 'collections' : //Ancien fonctionnement
            case self::BROWSE_TYPE_COLLECTION :
                if ($this->hasFilter()) {
                    $this->_action = 'scollection';
                }
                break;
            case 'scollection' :
                $this->_action = $_action;
                break;
            case self::BROWSE_TYPE_COLLECTION_WITH_CATEGORY :
            case self::BROWSE_TYPE_COLLECTION_BY_CATEGORY :
                if ($this->hasFilter()) {
                    $this->_action = 'scollection';
                } else {
                    $this->_action = self::BROWSE_TYPE_COLLECTION;
                }
                break;
        }

        return $this;
    }

    /**
     * Si un filtre est déclaré
     *
     * @return boolean
     */
    protected function hasFilter()
    {
        if (!empty($this->getFilter())) {
            return true;
        }
        return false;
    }

    /**
     * Conversion de la page en tableau associatif
     *
     * @see Ccsd_Website_Navigation_Page::toArray()
     */
    public function toArray()
    {
        $array = parent::toArray();

        $array = $array + [
                'field' => $this->getField(),
                'sort' => $this->getSort(),
                'filter' => $this->getFilter()
            ];

        return $array;
    }

    /**
     * Retourne les informations complémentaires spécifiques à la page
     *
     * @see Ccsd_Website_Navigation_Page::getSuppParams()
     */
    public function getSuppParams()
    {
        $params = [];

        if ($this->getField() != '') {
            $params ['field'] = $this->getField();
        }
        if ($this->getSort() != '') {
            $params ['sort'] = $this->getSort();
        }
        if ($this->getFilter() != []) {
            $params ['filter'] = $this->getFilter();
        }

        return serialize($params);
    }
}