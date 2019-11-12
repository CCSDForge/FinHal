<?php

/**
 * Parcourir par structures
 *    - tous les types
 *    - institution
 *    - department
 *    - laboratory
 *    - researchteam
 *  + filtres facultatifs
 * @author tournoy
 *
 */
class Hal_Website_Navigation_Page_Structure extends Hal_Website_Navigation_Page
{

    const STRUCTURE_ACTION_SUFFIX = '-structure';
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
    protected $_action = 'structure';
    /**
     * Champs de l'utilisateur pour faire les facettes
     *
     * @var string
     */
    protected $_field = '';
    /**
     * Filtre utilisateur
     *
     * @var array[]
     */
    protected $_filter = [];
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
        'field'  => 'setField',
        'filter' => 'setFilter',
        'sort'   => 'setSort'
    ];

    protected $_constraintUniq = true;

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
            $this->_form->addElement('select', 'field', array(
                'required' => true,
                'label' => 'Champ à utiliser',
                'class' => '',
                'value' => $this->getField(),
                'multioptions' => array(
                    'structure' => 'Tous les types de structures',
                    Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION => 'Regroupement d\'Institutions',
                    Ccsd_Referentiels_Structure::TYPE_INSTITUTION => 'Institutions',
                    Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY => 'Regroupement de Laboratoires',
                    Ccsd_Referentiels_Structure::TYPE_LABORATORY => 'Laboratoires',
                    Ccsd_Referentiels_Structure::TYPE_DEPARTMENT => 'Regroupement d\'équipes',
                    Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM => 'Équipes de recherche'
                ),
                'belongsTo' => 'pages_' . $pageidx
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add select element to form");
        }
        try {
            $this->_form->addElement('text', 'filter', array(
                'label' => 'Filtrer avec la ou les structures parentes :',
                'description' => "Afficher uniquement les structures affiliées aux structures ci-dessous",
                'class' => 'search-affi',
                'value' => $this->getFilter(),
                'placeholder' => 'Saisissez les deux premières lettres de la structure (type de structure indifférent)',
                'belongsTo' => 'pages_' . $pageidx
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add text element to form");
        }
        try {
            $this->_form->addElement('radio', 'sort', array(
                'label' => 'Tri par défaut des résultats',
                'description' => "Trier par nombre de documents ou ordre alphabétique, l'utilisateur peut changer le tri dans l'interface",
                'value' => $this->getSort(),
                'multioptions' => array(
                    'index' => 'Tri par ordre alphabétique',
                    'count' => 'Tri par nombre de documents'
                ),
                'belongsTo' => 'pages_' . $pageidx,
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add radio element to form");
        }
        // Ajout d'un décorateur pour remplacer le décorateur par défaut et ajout de marge
        try {
            $radio = $this->_form->getElement('sort');
            $radio->addDecorator('ViewHelper', array('class' => ''));
            $radio->setAttrib('style', 'margin:5px');
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add decorator or attrib to radio form element 'sort'");
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
     * @return Hal_Website_Navigation_Page_Structure
     */
    public function setField($_field)
    {
        $this->_field = $_field;
        return $this;
    }

    /**
     * Retourne le filtre
     *
     * @return string[]
     */
    public function getFilter()
    {
        return $this->_filter;
    }

    /**
     * Initialisation du filtre
     * @param string []|string   (chaine avec les filtres separes par ;)
     */
    public function setFilter($filter = [])
    {
        if (is_string($filter)) {
            if ($filter == '') {
                $filter = [];
            } else {
                $filter = explode(';', $filter);
            }
        }
        $this->_filter = $filter;
    }

    /**
     * Retourne le tri de l'utilisateur
     */
    public function getSort()
    {
        return $this->_sort;
    }

    /**
     * Définit le tri de l'utilisateur
     *
     * @param string $_sort
     * @return Hal_Website_Navigation_Page_Structure
     */
    public function setSort($_sort = 'index')
    {
        $this->_sort = $_sort;
        return $this;
    }

    /**
     * @param array $options
     */
    public function setOptions($options = array())
    {
        parent::setOptions($options);
        $this->setAction($this->getField());
    }

    /**
     * Chargement de la page
     * @param string $class
     * @return Hal_Website_Navigation_Page_Structure
     * @see Ccsd_Website_Navigation_Page::load()
     */
    public function load($class = '')
    {
        if ($class == '') {
            $class = __CLASS__;
        }

        if (!class_exists($class)) {
            throw new InvalidArgumentException ('Class ' . $class . ' is not available.');
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from('WEBSITE_NAVIGATION', 'PARAMS')->where('SID = ?', SITEID)->where('TYPE_PAGE = ?', $class)->where('ACTION = ?', $this->getAction());

        $dbRes = $db->fetchOne($sql);

        if ($dbRes) {
            $str = unserialize($dbRes);


            $this->setField($str ['field']);
            if (isset ($str ['sort'])) {
                $this->setSort($str ['sort']);
            }
            if (isset ($str ['filter'])) {
                $this->setFilter($str ['filter']);
            }
            $this->setAction($str ['field']);

            return $this;
        }
        return null;
    }

    /**
     * Determine le nom de l'action
     *
     * @param string $_action
     * @return Hal_Website_Navigation_Page_Structure
     */
    public function setAction($_action)
    {

        if ($this->hasFilter()) {
            $_action .= self::STRUCTURE_ACTION_SUFFIX;
        }

        switch ($_action) {
            case 'structure' :
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION :
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY :
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_INSTITUTION :
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_DEPARTMENT :
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_LABORATORY :
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM :
                // breaks omitted
            case 'structure' . self::STRUCTURE_ACTION_SUFFIX :
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_REGROUPINSTITUTION . self::STRUCTURE_ACTION_SUFFIX:
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_REGROUPLABORATORY . self::STRUCTURE_ACTION_SUFFIX:
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_INSTITUTION . self::STRUCTURE_ACTION_SUFFIX:
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_DEPARTMENT . self::STRUCTURE_ACTION_SUFFIX:
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_LABORATORY . self::STRUCTURE_ACTION_SUFFIX:
                // breaks omitted
            case Ccsd_Referentiels_Structure::TYPE_RESEARCHTEAM . self::STRUCTURE_ACTION_SUFFIX:
                // breaks omitted
            case '':
                // mais $_action peut être vide
                break;

            default:
                $message = 'Action ' . $_action . ' is not available';
                error_log($message . ' in ' . __CLASS__);
                throw new InvalidArgumentException ($message);
        }


        $this->_action = $_action;

        return $this;
    }

    /**
     * Conversion de la page en tableau associatif
     *
     * @see Ccsd_Website_Navigation_Page::toArray()
     */
    public function toArray()
    {
        $array = parent::toArray();

        return $array + array(
                'field' => $this->getField(),
                'sort' => $this->getSort(),
                'filter' => $this->getFilter()
            );
    }

    /**
     * Retourne les informations complémentaires spécifiques à la page
     *
     * @see Ccsd_Website_Navigation_Page::getSuppParams()
     */
    public function getSuppParams()
    {
        $params = array();

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

    /**
     * Si un filtre est déclaré
     *
     * @return boolean
     */
    protected function hasFilter()
    {
        if (!empty($this->getFilter ())) {
            return true;
        }
        return false;
    }
}