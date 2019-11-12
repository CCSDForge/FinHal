<?php

/**
 * Lien exterieur
 * @author yannick
 *
 */
class Hal_Website_Navigation_Page_File extends Hal_Website_Navigation_Page
{
    /**
     * Page multiple
     * @var boolean
     */
    protected $_multiple = true;
    
    /**
     * controller
     * @var string
     */
    protected $_controller = '';
    /**
     * Lien vers le fichier
     * @var string
     */
    protected $_src = '';
    
    /**
     * Cible du lien
     * @var string
     */
    protected $_target = '';
    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [ 'src' => 'setSrc', 'target' => 'setTarget'];
    /**
     * @return bool
     */
    public function isFile() {
        return true;
    }
    /**
     * Retourne l'action de la page (lien vers fichier)
     * @see Ccsd_Website_Navigation_Page::getController()
     */
    public function getAction() 
    {
        return SPACE_URL . $this->getSrc();
    }
    
    /**
     * Récupération du lien de la page
     * @return string
     */
    public function getSrc()
    {
        return $this->_src;
    }

    /**
     * Initialisation du lien de le fichier
     * @param string $src
     */
    public function setSrc($src)
    {
        if ($src != '') {
            $this->_src = $src;
        }
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
     * @see Ccsd_Website_Navigation_Page::toArray()
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array['target'] = $this->getTarget();
        return $array;
    }
    /**
     * Specific save for Custum object: Permalien need to be uniquify
     */
    public function save() {
        //C'est pas super propre mais bon...
        if ($this->getSrc() != '') {
            if (isset($_FILES['pages_' . $this->getPageId()]['tmp_name']['src']) && is_file($_FILES['pages_' . $this->getPageId()]['tmp_name']['src'])) {
                $this->setSrc(Ccsd_Tools::getNewFileName($this->getSrc(), SPACE . 'public/'));
                @mkdir(SPACE . 'public/');
                rename($_FILES['pages_' . $this->getPageId()]['tmp_name']['src'], SPACE . 'public/' . $this->getSrc());
            }
        }
        parent::save();
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
            $this->_form->addElement('file', 'src',
                array('required' => true,
                    'label' => 'Lien',
                    'value' => $this->getSrc(),
                    'belongsTo' => 'pages_' . $pageidx));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add file element to form");
        }
        try {
            $this->_form->addElement('select', 'target',
                array('required' => true,
                    'label' => 'Cible', 'value' => $this->getTarget(),
                    'belongsTo' => 'pages_' . $pageidx,
                    'multioptions' => array(
                        '_self' => 'Page courante (_self)',
                        '_blank' => 'Nouvelle page (_blank)')));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add select element to form");
        }
    	//Zend_Debug::dump($this->_form->getElements());
        return $this->_form;
    }
    
    /**
     * Retourne les informations complémentaires spécifiques à la page
     * @see Ccsd_Website_Navigation_Page::getSuppParams()
     */
    public function getSuppParams()
    {
    	return serialize(array('src' => $this->getSrc(), 'target' => $this->getTarget()));
    }
} 