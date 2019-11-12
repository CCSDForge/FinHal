<?php

/**
 * 
 * @author yannick
 *
 */
class Hal_Website_Navigation_Page extends Ccsd_Website_Navigation_Page
{
    /**
     * @var Hal_Website_Navigation
     */
    protected $nav;
	/**
	 * Retourne la traduction du nom de la classe
	 */
	public function getPageClassLabel($lang = '')
    {
    	if ($lang == '') {
            $lang = Zend_Registry::get('Zend_Translate')->getLocale();
        }
        return Zend_Registry::get('Zend_Translate')->translate(get_class($this), $lang);
    }
    
    /**
     * Retourne le label de la page 
     * @see Ccsd_Website_Navigation_Page::getLabel($lang)
     */
    public function getLabel($lang)
    {
    	$label = parent::getLabel($lang);
    	if ($label == '') {
    		$label = $this->getPageClassLabel($lang);
    	}
    	return $label;
    }
    
    /**
     * Indique si la page est un répertoire
     * @return boolean
     */
    public function isFolder()
    {
        // This function is redefine in Hal_Website_Navigation_Page_Folder
    	return false;
    }
    /**
     * Indique si la page est un custom
     * @return boolean
     */
    public function isCustom()
    {
        // This function is redefine in Hal_Website_Navigation_Page_Custom
    	return false;
    }
    /**
     * Indique si la page est un fichier
     * @return boolean
     */
    public function isFile()
    {
        // This function is redefine in Hal_Website_Navigation_Page_File
    	return false;
    }
    /**
     * Récupération du code html complet pour la cible de la page
     * @return string
     */
    public function getHtmlTarget()
    {
        $target = $this->_target;
        if (isset($target) && ($target != '')) {
            return "target='$target'";
        } else {
            return '';
        }
    }

    /**
     * @return int
     */
    public function getSid() {
        $sid = $this -> _sid;
        if (!$sid) {
            // sid not set, we get it from navigation menu.
            $sid = $this -> nav-> getSid();
        }
        if (!$sid) {
            // sid not set, we get it from navigation menu.
            $sid = Hal_Site::getCurrent()-> getSid();
        }
        return $sid;
    }
}