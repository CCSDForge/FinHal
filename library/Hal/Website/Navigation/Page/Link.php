<?php

/**
 * Lien exterieur
 * @author yannick
 *
 */
class Hal_Website_Navigation_Page_Link extends Hal_Website_Navigation_Page
{
	/**
	 * Page multiple
	 * @var boolean
	 */
	protected $_multiple = true;
    /**
     * Lien vers le site exterieur
     * @var string
     */
	protected $_link = '';
	/**
	 * Cible du lien
	 * @var string
	 */
    protected $_target = '';
    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [ 'link' => 'setLink', 'target' => 'setTarget' ];

    /**
     * Retourne le controller de la page (lien exterieur, pas de controller)
     * @see Ccsd_Website_Navigation_Page::getController()
     */
    public function getController()
    {
    	return '';
    }
    
    /**
     * Retourne l'action de la page (lien exterieur)
     * @see Ccsd_Website_Navigation_Page::getController()
     */
    public function getAction() 
    {
    	return $this->getLink();
    }
    
    /**
     * Récupération du lien de la page
     * @return string
     */
    public function getLink()
    {
    	return $this->_link;
    }

    /**
     * Initialisation du lien de la page
     * @param string $link
     */
    public function setLink($link)
    {
    	$this->_link = $link;
    }
    
    /**
     * Récupération de la cible de la page
     * @return string
     */
    public function getTarget()
    {
    	return $this->_target;
    }
    
    /**
     * Initialisation de la cible de la page
     * @param string $target
     */
    public function setTarget($target)
    {
    	$this->_target = $target;
    }
    
    /**
     * Conversion de la page en tableau associatif
     * @return array
     * @see Ccsd_Website_Navigation_Page::toArray()
     */
    public function toArray()
    {
    	$array = parent::toArray();
    	$array['target'] = $this->getTarget();
    	return $array;
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
            $this->_form->addElement('text', 'link',
                array('required' => true,
                    'label' => 'Lien',
                    'value'=>$this->getLink(),
                    'belongsTo'	=> 'pages_' . $pageidx));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add text element to form");
        }
    	try {
            $this->_form->addElement('select', 'target',
                array('required' => true,
                    'label' => 'Cible', 'value'=>$this->getTarget(),
                    'belongsTo'	=> 'pages_' . $pageidx,
                    'multioptions' => array (
                        '_self' => 'Page courante (_self)',
                        '_blank' => 'Nouvelle page (_blank)')));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add select element to form");
        }
    	return $this->_form;
    }
    
    /**
     * Retourne les informations complémentaires spécifiques à la page
     * @see Ccsd_Website_Navigation_Page::getSuppParams()
     */
    public function getSuppParams()
    {
    	return serialize(array('link' => $this->getLink(), 'target' => $this->getTarget()));
    }
} 