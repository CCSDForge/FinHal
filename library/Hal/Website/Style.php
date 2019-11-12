<?php

class Hal_Website_Style extends Ccsd_Website_Style
{
    /**
     * Hal_Website_Style constructor.
     */
	public function __construct()
	{
		$this->_fieldSID = 'SID';
		$this->_sid = SITEID;
		$this->_dirname = SPACE . 'public/';
		$this->_publicUrl = SPACE_URL;
		$this->_tplUrl = '/css/templates/';
		$this->initForm();
	}

    /**
     * Suppression du style d'un site
     */
	static public function resetStyle(Hal_Site $site)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $db->delete(self::TABLE,'SID = '. $site->getSid());
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
     * @param $styles
     */
    static public function setInDb(Hal_Site $site, $styles)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        foreach ($styles as $style) {
            $style["SID"] = $site->getSid();
            $db->insert(self::TABLE, $style);
        }
    }

    /**
     * @param Hal_Site $model
     * @param Hal_Site $receiver
     */
    static public function duplicate(Hal_Site $model, Hal_Site $receiver)
    {
        $source = $model->getRootPath() . PUBLIC_DEF . 'style.css';
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . PUBLIC_DEF . 'style.css';
            copy($source, $dest);
        }

        // Suppression du style existant
        self::resetStyle($receiver);
        self::setInDb($receiver, self::getFromDb($model));
    }
}