<?php

/**
 * Page personnalisable
 *
 */
class Hal_Website_Navigation_Page_Custom extends Hal_Website_Navigation_Page
{
    const PERMALIEN     =       'permalien';
        /**
         * Page éditable
         * @var boolean
         */
    protected $_editable  = true;
    
        /**
         * Controller
         * @var string
         */
    protected $_controller = 'page';
    
    /**
     * Page multiple
     * @var boolean
     */
    protected $_multiple = true;
    
    /**
     * Lien permanent
     * @var string
     */
    protected $_permalien = '';
    
    /**
     * Nom de la page
     * @var string
     */
    protected $_page = '';
    /**
     * Fields of the sub class
     * @var string[]
     */
    protected $_localFields = [ 'permalien' => 'setPermalien', 'page' => 'setPage' ];

    /**
     * @return bool
     */
    public function isCustom() {
        return true;
    }
    /**
     * Conversion de la page en tableau associatif
     * @see Ccsd_Website_Navigation_Page::toArray()
     */
    public function toArray()
    {
        $array = parent::toArray();
        $array[self::PERMALIEN] = $this->getPermalien();
        return $array;
    }
    
    /**
     * Retourne l'action de la page (permalien ans notre cas)
     * @see Ccsd_Website_Navigation_Page::getAction()
     */
    public function getAction()
    {
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
        if (! $this->_form->getElement(self::PERMALIEN)) {
            try {
                $this->_form->addElement('text', self::PERMALIEN, array(
                    'required' => true,
                    'label' => 'Lien permanent',
                    'value' => $this->getPermalien(),
                    'belongsTo' => 'pages_' . $pageidx,
                    'class' => 'permalien',
                ));
            } catch (Zend_Form_Exception $e) {
            }
        }
        $this->_form->getElement('labels')->setOptions(array('class' => 'inputlangmulti permalien-src'));
        return $this->_form;
    }

    /**
     * Specific save for Custom object: Permalien need to be uniquify
     * @throws Exception
     */
    public function save() {
        /** @var Hal_Website_Navigation $nav_obj */
        $nav_obj = $this -> getNavigation();
        if ($nav_obj === null) {
            throw new Exception("Can't save a Custom page if not in navigation context");
        }
        $nav_obj = $this -> getNavigation();
        $this->setPermalien($nav_obj->getUniqPermalien($this));
        parent::save();
    }
    /**
     * Retourne les informations complémentaires spécifiques à la page
     * @see Ccsd_Website_Navigation_Page::getSuppParams()
     */
    public function getSuppParams()
    {
        $res = '';
        if ($this->_permalien != '') {
                $res = serialize(array(self::PERMALIEN => $this->_permalien));
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
     * Initialisation du permalien
     * @param string $permalien
     * @return string
     */
    public function setPermalien($permalien)
    {
        if ($this->_permalien != '' && $this->_permalien != $permalien) {
                //L'utilisateur a changé le nom du permalien, on déplace les fichiers s'il y en a
                $this->renamePage($this->_permalien, $permalien);
        }
        $this->_permalien = $permalien;
        return $this->_permalien;
    }
    
    /**
     * Initialisation du nom de la page
     * @param string $pagename
     * @return string
     */
    public function setPage($pagename)
    {
        $this->_page = $pagename;
        return $this->_page;
    }
    
    /**
     * Retourne le contenu d'une page
     * @param string $lang
     * @return string
     */
    public function getContent($lang)
    {
        $filename = $this->getPagePath($lang);
        if (file_exists($filename)) {
                return file_get_contents($filename);
        }
        return '';
    }
    
    /**
     * Enregistrement du contenu d'une page 
     * @param string[] $data
     * @param string[] $locales
     */
    public function setContent($data, $locales = array ())
    {
        if (! is_dir(PATH_PAGES)) {
                mkdir(PATH_PAGES, 0777, true);
        }
        
        foreach ($locales as $lang) {
                if (($filepath = realpath($this->getPagePath($lang))) !== false) {
                        file_put_contents($filepath, "");
                }
        }

        foreach ($data as $lang => $content) {
                file_put_contents($this->getPagePath($lang), $content);
        }
    }
    /**
     * @return Ccsd_Form
     */
    public function getContentForm()
    {
        $content = $populate = array();
        
        foreach ($this->_languages as $lang) {
            $populate[$lang] = $lang;
                $content[$lang] = $this->getContent($lang);
        }

        $form = new Ccsd_Form();
        try {
        $form->setName("page_modification");
        $form->setMethod("post");
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't set form name or method");
        }
        $form->setAttrib("class", "form-horizontal");

        try {
            $form->addElement('MultiTextAreaLang', 'content', array(
                'lang' => $this->_languages,
                'populate' => $populate,
                'value' => $content,
                'tiny' => true,
                'display' => Ccsd_Form_Element_MultiText::DISPLAY_ADVANCED
            ));
        } catch (Zend_Form_Exception $e) {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Can't add MultiTextAreaLang element to form");
        }

        $form->getElement('content')->getDecorator('HtmlTag')->setOption('class', 'col-md-12');
        
        $form->setActions(true)->createSubmitButton();

        return $form;
    }
    /**
     * @param string $old
     * @param string $new
     */
    public function renamePage($old, $new)
    {
        foreach ($this->_languages as $lang) {
                $filename = $this->getPagePath($lang, $old);
                if (file_exists($filename)) {
                        rename($filename, $this->getPagePath($lang, $new));
                }
        }
    }
    /**
     * @param string $lang
     * @param string $page
     * @return string
     */
    public function getPagePath($lang, $page = '')
    {
        if ($page == '') {
                $page = $this->_page;
        }
        return PATH_PAGES . $page . '.' . $lang . '.html';
    }
    
} 