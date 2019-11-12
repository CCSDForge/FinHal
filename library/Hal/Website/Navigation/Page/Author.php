<?php
/**
 * Parcourir par auteur
 *  TODO: Argh!!!!  Inherit of Structure!!! Very bad thing...
 *  TODO: Must change that
 */
class Hal_Website_Navigation_Page_Author extends Hal_Website_Navigation_Page_Structure {
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
	protected $_action = 'author';
    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [
        'field' => 'setField',
        'filter'=> 'setFilter',
        'sort' => 'setSort',
        ];
    /**
     * @param string $class
     * @return Hal_Website_Navigation_Page_Author
     * @see Hal_Website_Navigation_Page_Structure::load()
     */
	public function load($class=__CLASS__) {
		parent::load($class);
		return $this;
	}
    /**
     * @param array $options
     */
	public function setOptions($options = array()) {
        parent::setOptions ( $options );
		$this->setAction ( $this->getField () );
	}
	/**
	 * (non-PHPdoc)
	 * @param int $pageidx
     * @return Ccsd_Form
	 * @see Hal_Website_Navigation_Page_Structure::getForm()
	 */
	public function getForm($pageidx) {
		Ccsd_Website_Navigation_Page::getForm ( $pageidx );

		try {
            $this->_form->addElement('hidden', 'field', array(
                'required' => true,
                'label' => '',
                'value' => 'author',
                'belongsTo' => 'pages_' . $pageidx
            ));
        } catch (Zend_Form_Exception $e) {
		    Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add hidden field to form");
        }
        try {
            $this->_form->addElement ( 'text', 'filter', array (
                'label' => 'Filtrer avec la ou les structures :',
                'description' => "Afficher uniquement les auteurs affiliés aux structures ci-dessous",
                'class' => 'search-affi',
                'value' => $this->getFilter (),
                'placeholder' => 'Saisissez les deux premières lettres de la structure (type de structure indifférent)',
                'belongsTo' => 'pages_' . $pageidx
            ) );
		} catch (Zend_Form_Exception $e) {
		    Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add text field to form");
        }
        try {
            $this->_form->addElement ( 'radio', 'sort', array (
                'label' => 'Tri par défaut des résultats',
                'description' => "Trier par nombre de documents ou ordre alphabétique, l'utilisateur peut changer le tri dans l'interface",
                'value' => $this->getSort (),
                'multioptions' => array (
                    'index' => 'Tri par ordre alphabétique',
                    'count' => 'Tri par nombre de documents'
                ),
                'belongsTo' => 'pages_' . $pageidx
            ) );
        } catch (Zend_Form_Exception $e) {
		    Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add radio field to form");
        }
		return $this->_form;
	}
	/**
	 * (non-PHPdoc)
	 * @param string $_action
     * @return Hal_Website_Navigation_Page_Author
	 * @see Hal_Website_Navigation_Page_Structure::setAction()
	 */
	public function setAction($_action) {
		if ($this->hasFilter ()) {
			$_action .= '-structure';
		}
		$this->_action = $_action;
		return $this;
	}
    /**
     * Retourne le tri de l'utilisateur
     */
    public function getSort() {
        return $this->_sort;
    }
    /**
     * Définit le tri de l'utilisateur
     *
     * @param string $_sort
     * @return Hal_Website_Navigation_Page_Author
     */
    public function setSort($_sort = 'index') {
        $this->_sort = $_sort;
        return $this;
    }
    /**
     *
     * @return string
     */
    public function getField() {
        return $this->_field;
    }
    /**
     *
     * @param string $_field
     * @return Hal_Website_Navigation_Page_Author
     */
    public function setField($_field) {
        $this->_field = $_field;
        return $this;
    }
    /**
     * Retourne le filtre
     *
     * @return string[]
     */
    public function getFilter() {
        return $this->_filter;
    }
    /**
     * Initialisation du filtre
     * @param string []|string  (string[] ou '')
     */
    public function setFilter($filter = '') {
        if ((!is_array($filter)) && ($filter != '')) {
            $filter = explode(';', $filter);
        }
        $this->_filter = $filter;
    }
}