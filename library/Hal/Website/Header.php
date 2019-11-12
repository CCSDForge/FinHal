<?php

/**
 * Class Hal_Website_Header
 */
class Hal_Website_Header extends Ccsd_Website_Header
{
    /**
     * Hal_Website_Header constructor.
     * @param Hal_Site $site
     * @throws Zend_Exception
     */
	public function __construct($site)
	{
		$sid = $site->getSid();
		$publicDir = $site->getSpace() . 'public/';
		$publicUrl = $site->getSpaceUrl();
		$langDir = $site->getSpace() . 'languages/';
		$layoutDir = $site->getSpace() . LAYOUT .'/';
		parent::__construct($sid, $publicDir, $publicUrl, $layoutDir, $langDir);
        $this->_fieldSID = 'SID';
		$this->_languages = Zend_Registry::get('languages');
	}

    /**
     * @param Hal_Site $site
     * @return mixed
     */
    static public function getFromDb(Hal_Site $site)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE)
            ->where('SID = ?', $site->getSid())
            ->order('LOGOID');

        return $db->fetchAll($sql);
    }

    /**
     * @param Hal_Site $site
     * @param $headers
     */
    static public function setInDb(Hal_Site $site, $headers)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        foreach ($headers as $header) {
            unset($header['LOGOID']);
            $header["SID"] = $site->getSid();
            $db->insert(self::TABLE, $header);
        }
    }

    /**
     * Copie des configurations de header
     * @param Hal_Site $model
     * @param Hal_Site $receiver
     */
    static public function duplicate(Hal_Site $model, Hal_Site $receiver)
    {
        $name = $model->getSite();

        // Création du dossier layout
        if (! is_dir($receiver->getRootPath() . LAYOUT )) {
            mkdir($receiver->getRootPath() . LAYOUT, 0777, true);
        }

        $source = $model->getRootPath() . LAYOUT . 'header.fr.html';
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . LAYOUT . 'header.fr.html';

            $texte = file_get_contents($source);
            preg_replace("|$name|", $receiver->getSite(), $texte);
            file_put_contents($dest, $texte);
        }

        $source = $model->getRootPath() . LAYOUT . 'header.en.html';
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . LAYOUT . 'header.en.html';

            $texte = file_get_contents($source);
            preg_replace("|$name|", $receiver->getSite(), $texte);
            file_put_contents($dest, $texte);
        }
        self::setInDb($receiver, self::getFromDb($model));
    }
}