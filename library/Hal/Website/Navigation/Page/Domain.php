<?php

/**
 * Class Hal_Website_Navigation_Page_Domain
 */
class Hal_Website_Navigation_Page_Domain extends Hal_Website_Navigation_Page {
	protected $_controller = 'browse';
	protected $_action = 'domain';

	/**
	 * Champs de l'utilisateur pour faire les facettes
	 *
	 * @var string
	 */
	protected $_displayType = 'portal';

	/**
	 *
	 * @var boolean
	 */
	protected $_multiple = false;
    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [ 'displaytype' => 'setDisplayType'];
	/**
	 * Formulaire
	 * @param int $pageidx
     * @return Ccsd_Form
	 * @see Ccsd_Website_Navigation_Page::getForm()
	 */
	public function getForm($pageidx) {
		parent::getForm ( $pageidx );
        try {
            $this->_form->addElement('select', 'displayType', array(
                'required' => true,
                'label' => "Type d'affichage",
                'class' => '',
                'value' => $this->getDisplayType(),
                'multioptions' => array(
                    'portal' => 'Uniquement les domaines propres au portail',
                    'all' => 'Tous les domaines utilisés dans les dépôts'

                ),
                'belongsTo' => 'pages_' . $pageidx
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add select element to form");
        }

		return $this->_form;
	}

	/**
	 * Chargement de la page
	 * @param string $class
     * @return Hal_Website_Navigation_Page_Domain
	 * @see Ccsd_Website_Navigation_Page::load()
	 */
	public function load($class = __CLASS__) {
		if (! class_exists ( $class )) {
			throw new InvalidArgumentException ( $class . ' is not available.' );
		}

		$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
		$sql = $db->select ()->from ( 'WEBSITE_NAVIGATION', 'PARAMS' )->where ( 'SID = ?', SITEID )->where ( 'TYPE_PAGE = ?', $class )->where ( 'ACTION = ?', $this->getAction () );

		$dbRes = $db->fetchOne ( $sql );

		if ($dbRes) {
			$str = unserialize ( $dbRes );
			if (isset ( $str ['displayType'] )) {
				$this->setDisplayType ( $str ['displayType'] );
			}
			return $this;
		}
		return null;
	}

	/**
	 * Determine le nom de l'action
	 *
	 * @param string $_action
	 * @return Hal_Website_Navigation_Page_Domain
	 */
	public function setAction($_action) {
		$this->_action = $_action;

		return $this;
	}

	/**
	 *
	 * @return string
	 */
	public function getDisplayType() {
		return $this->_displayType;
	}

	/**
	 *
	 * @param string $_displayType
     * @return Hal_Website_Navigation_Page_Domain
	 */
	public function setDisplayType($_displayType) {
		$this->_displayType = $_displayType;
		return $this;
	}

	/**
	 * Conversion de la page en tableau associatif
	 *
	 * @see Ccsd_Website_Navigation_Page::toArray()
	 */
	public function toArray() {
		$array = parent::toArray ();

		$array = $array + array (
				'displayType' => $this->getDisplayType ()
		);

		return $array;
	}

	/**
	 * Retourne les informations complémentaires spécifiques à la page
	 *
	 * @see Ccsd_Website_Navigation_Page::getSuppParams()
	 */
	public function getSuppParams() {
		$params = array ();

		if ($this->getDisplayType () != '') {
			$params ['displayType'] = $this->getDisplayType ();
		}
		return serialize ( $params );
	}
}