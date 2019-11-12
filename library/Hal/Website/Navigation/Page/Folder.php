<?php

/**
 * Class Hal_Website_Navigation_Page_Folder
 */
class Hal_Website_Navigation_Page_Folder extends Hal_Website_Navigation_Page
{
    protected $_controller = 'section';


    protected $_permalien = '';
    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [ 'permalien' => 'setPermalien'];
    /**
     * @return bool
     */
    public function isFolder() {
        return true;
    }
    /**
     * Initialisation du permalien
     * @param string $permalien
     * @return string
     */
    public function setPermalien($permalien)
    {
        $this->_permalien = $permalien;
        return $this->_permalien;
    }

    /**
     * Retour du formulaire e création de la page
     * @param int $pageidx
     * @return Ccsd_Form
     * @see Ccsd_Website_Navigation_Page::getForm()
     */
    public function getForm($pageidx)
    {
        parent::getForm($pageidx);
        if (! $this->_form->getElement('permalien')) {
            try {
                $this->_form->addElement('text', 'permalien', array(
                    'label' => 'Lien permanent',
                    'value'=>$this->getPermalien(),
                    'belongsTo'	=> 'pages_' . $pageidx,
                    'class' => 'permalien',
                ));
            } catch (Zend_Form_Exception $e) {
            }
        }

        $this->_form->getElement('labels')->setOptions(array('class' => 'inputlangmulti permalien-src'));
        return $this->_form;
    }

    /**
     * Retourne les informations complémentaires spécifiques à la page
     * @see Ccsd_Website_Navigation_Page::getSuppParams()
     */
    public function getSuppParams()
    {
        $res = '';
        if ($this->_permalien != '') {
            $res = serialize(array('permalien' => $this->_permalien));
        }
        return $res;
    }

    /**
     * Retourne le lien permanent
     * @return string
     */
    public function getPermalien()
    {
        return $this->_permalien;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        if ($this->_permalien != '') {
            return $this->_permalien;
        }
        return 'list' . $this->getPageId();
    }
} 