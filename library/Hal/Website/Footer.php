<?php

class Hal_Website_Footer
{

    const TABLE = 'WEBSITE_FOOTER';

    const TYPE_DEFAULT = 'default';

    const TYPE_CUSTOM = 'custom';

    protected $_sid = 0;

    protected $_type = '';

    protected $_layoutDir = '';

    protected $_content = array();

    protected $_form = null;

    protected $_languages = array();

    /**
     * Hal_Website_Footer constructor.
     * @param Hal_Site $site
     * @param bool $load
     * @throws Zend_Exception
     */
    public function __construct($site,$load = true)
	{
        $this->_sid = $site->getSid();
        $this->_languages = Zend_Registry::get('languages');
        $this->_layoutDir = $site->getSpace() . LAYOUT.'/';
        if ($load) {
            $this->load();
        }
	}

    public function load ()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(self::TABLE)
            ->where('SID = ?', $this->_sid);
        $row = $db->fetchRow($sql);
        if ($row) {
            $this->_type = $row['TYPE'];
            $this->_content = unserialize($row['CONTENT']);
        }
    }

    public function getForm()
    {
        $form = new Ccsd_Form();
        $form->setAction(PREFIX_URL . 'website/footer');
        $form->setName("form-footer");
        $form->setMethod("post");
        $form->setAttrib("class", "form-horizontal");
        $form->setActions(true)->createSubmitButton('footer', array(
                "label" => "Enregistrer",
                "class" => "btn btn-primary",
                "style" => "margin-top: 15px;"
            )
        );

        $form->addElement('select', 'type', array(
            'label' =>  'Type de personnalisation',
            'multioptions' => array (
                self::TYPE_DEFAULT   =>  'Pied de page par défaut',
                self::TYPE_CUSTOM    =>  'Pied de page personnalisé'
            ),
            'value' => $this->_type,
            'elem' => 'type',
            'class' => 'elem-link',
        ));

        $populate = $value = array();
        foreach($this->_languages as $lang) {
            $populate[$lang] = $lang;
            $value[$lang] = is_array($this->_content) ? Ccsd_Tools::ifsetor($this->_content[$lang], '') : '';
        }

        $form->addElement('MultiTextAreaLang', 'content', array(
            'label' =>  'Contenu',
            'lang'     => Zend_Registry::get('languages'),
            'populate' => $populate,
            'value'    => $value,
            'tiny'	   => true,
            'display'  => Ccsd_Form_Element_MultiText::DISPLAY_ADVANCED,
            'elem-link' => 'type',
            'elem-value' => self::TYPE_CUSTOM ,
        ));

        return $form;
    }

    public function save($data)
    {
        $this->_type = Ccsd_Tools::ifsetor($data['type'], self::TYPE_DEFAULT);
        $this->_content = Ccsd_Tools::ifsetor($data['content'], array());

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete(self::TABLE, 'SID = ' . $this->_sid);
        $bind = array(
            'SID' => $this->_sid,
            'TYPE' => $this->_type,
            'CONTENT' => serialize($this->_content)
        );
        $db->insert(self::TABLE, $bind);
        $this->createFooter();
    }

    /**
     *
     */
    public function createFooter()
    {
        foreach ($this->_languages as $lang) {
            $filename = $this->_layoutDir . 'footer.' . $lang . '.html';
            if ($this->_type == self::TYPE_DEFAULT) {
                if (is_file($filename)) {
                    @unlink($filename);
                }
            } else {
                //Footer personnalisé
                if (isset($this->_content[$lang])) {
                    file_put_contents($filename, $this->_content[$lang]);
                }
            }
        }
    }

    /**
     * @param Hal_Site $site
     * @return mixed
     */
    static public function getFromDb(Hal_Site $site)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE)
            ->where('SID = ?', $site->getSid());

        return $db->fetchAll($sql);
    }

    /**
     * @param Hal_Site $site
     * @param $footers
     */
    static public function setInDb(Hal_Site $site, $footers)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        foreach ($footers as $footer) {
            $footer["SID"] = $site->getSid();
            $db->insert(self::TABLE, $footer);
        }
    }

    /**
     * Copie des configurations de footer
     */
    static public function duplicate(Hal_Site $model, Hal_Site $receiver)
    {
        $source = $model->getRootPath() . LAYOUT . 'footer.fr.html';
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . LAYOUT . 'footer.fr.html';
            copy($source, $dest);
        }

        $source = $model->getRootPath() . LAYOUT . 'footer.en.html';
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . LAYOUT . 'footer.en.html';
            copy($source, $dest);
        }

        self::setInDb($receiver, self::getFromDb($model));
    }
}