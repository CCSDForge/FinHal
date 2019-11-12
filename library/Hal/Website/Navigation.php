<?php

/**
 * Navigation spécifique à l'application HAL
 * @author yannick
 *
 */
class Hal_Website_Navigation extends Ccsd_Website_Navigation
{
    /**
     * Liste des pages
     */
    const PAGE_INDEX            = 'index';       //Page d'accueil
    const PAGE_SUBMIT           = 'submit';      //Dépôt
    const PAGE_CUSTOM           = 'custom';      //Page personnalisable
    const PAGE_LINK             = 'link';        //Lien exterieur
    const PAGE_FILE             = 'file';        //Fichier
    const PAGE_NEWS             = 'news';        //Actualités

    const PAGE_BROWSE_PERIOD    = 'period';      //Consultation par période
    const PAGE_BROWSE_STRUCTURE = 'structure';   //Consultation par structure
    const PAGE_BROWSE_AUTHOR    = 'author';      //Consultation par auteur
    const PAGE_BROWSE_DOCTYPE   = 'doctype';     //Consultation par type de dépôt
    const PAGE_LAST             = 'last';        //Consultation les derniers dépôts
    const PAGE_BROWSE_LATESTPUBLICATIONS = 'latestpublications';    //Consultation des dernières publications
    const PAGE_BROWSE_DOMAIN    = 'domain';      //Consultation par domaine
    const PAGE_BROWSE_PORTAIL   = 'portails';    //Consultation des portails de l'archive
    const PAGE_BROWSE_COLLECTION= 'collections'; //Consultation des collections
    const PAGE_BROWSE_META      = 'meta';        //Consultation pour une métadonnée spécifique

    const PAGE_SEARCH           = 'search'; //Recherche

    /**
     * Table de stockage de la navigztion d'un site
     * @var string
     */
    protected $_table   =   'WEBSITE_NAVIGATION';
    /**
     * Clé primaire
     * @var string
     */
    protected $_primary =   'NAVIGATIONID';
    /**
     * Identifiant du site dans l'archive HAL
     * @var int
     */
    protected $_sid = 0;
    /** @var Hal_Site */
    protected $_site = null;

    /**
     * Hal_Website_Navigation constructor.
     * @param Hal_Site $site
     * @param array $options
     */
    public function __construct($site, $options = array())
    {
        parent::__construct($options);
        $this->_site = $site;
    }

    /**
     * Initialisation des options de la navigation
     * @see Ccsd_Website_Navigation::setOptions($options)
     */
    public function setOptions($options = array())
    {
        foreach ($options as $option => $value) {
            $option = strtolower($option);
            switch($option) {
                case 'sid'      :   $this->_sid = $value;
                break;
                case 'languages':   $this->_languages = is_array($value) ? $value : array($value);
                break;
            }
        }
    }

    /**
     * Chargement de la navigation du site
     * @see Ccsd_Website_Navigation::load()
     */
    public function load()
    {
        $sql = $this->_db->select()
            ->from($this->_table)
            ->where('SID = ?', $this->_sid)
            ->order('NAVIGATIONID ASC');

        $this->_pages = array();
        $reader = new Ccsd_Lang_Reader('menu', SPACE . 'languages/', $this->_languages, true);

        foreach ($this->_db->fetchAll($sql) as $row) {
            //Récupération des infos sur la page en base
            $parentPageid = $row['PARENT_PAGEID'];
            $pageid = $row['PAGEID'];
            $typePage = $row['TYPE_PAGE'];

            $options = array('languages' => $this->_languages);
            foreach ($this->_languages as $lang) {
                $options['labels'][$lang] = $reader->get($row['LABEL'], $lang);
            }
            if ($row['PARAMS'] != '') {
                $options = array_merge($options, unserialize($row['PARAMS']));
            }
            // On passe l'ensemble des options deja lues pour creer l'object page
            $options = array_merge($options, $row);
            //Création de la page
            $this->_pages[$pageid] = new $typePage($this, $options);
            $this->_pages[$pageid]->load();
            //Définition de l'ordre des pages
            if ($pageid > $this->_idx) {
                $this->_idx = $pageid;
            }
            if ($parentPageid == 0) {
                $this->_order[$pageid] = array();
            } else {
                if (isset($this->_order[$parentPageid])) {
                    $this->_order[$parentPageid][$pageid] = array();
                } else {
                    foreach($this->_order as $i => $elem) {
                        if (is_array($elem) && isset($this->_order[$i][$parentPageid])) {
                            $this->_order[$i][$parentPageid][$pageid] = array();
                        }
                    }
                }
            }
        }
        // Pas de page en base: on en prends une par defaut
        if (count($this->_pages) == 0) {
            $this->_pages[0] = new Hal_Website_Navigation_Page_Index($this, array (
                'languages' => Zend_Registry::get ( 'languages' ),
                'sid' => $this->_site->getSid()
            ));
            $this->_order[0] = array();
        }
        $this->_idx++;
    }

    /**
     * Enregistrement de la nouvelle navigation
     * @see Ccsd_Website_Navigation::save()
     * @todo : rendre recursif!
     */
    public function save()
    {
        //Zend_Debug::dump($this->_pages);
        //Zend_Debug::dump($this->_order);exit;


        //Suppression de l'ancien menu
        $this->_db->delete($this->_table, 'SID = ' . $this->_sid);

        $lang = array();
        //Enregistrement des nouvelles données
        $i = 0;
        foreach ($this->_order as $pageid => $spageids) {
            $page = $this->_pages[$pageid];
            if (isset($page)) {
                //Initialisation de la pageid
                                                                                                                                        $page->setPageId($i);
                $this->savePage($page);
                $key = $page->getLabelKey();
                $lang[$key] = $page->getLabels();
                $i++;

                if (is_array($spageids) && count($spageids) > 0) {
                    foreach ($spageids as $spageid =>$sspageids) {
                        $subpage = $this->_pages[$spageid];
                        if (isset($subpage)) {
                            $subpage->setPageId($i);
                            $subpage->setPageParentId($page->getPageId());
                            $this->savePage($subpage);
                            $key = $subpage->getLabelKey();
                            $lang[$key] = $subpage->getLabels();
                            $i++;

                            if (is_array($sspageids) && count($sspageids) > 0) {
                                foreach (array_keys($sspageids) as $sspageid) {
                                    $ssubpage = $this->_pages[$sspageid];
                                    if (isset($ssubpage)) {
                                        $ssubpage->setPageId($i);
                                        $ssubpage->setPageParentId($subpage->getPageId());
                                        $this->savePage($ssubpage);
                                        $key = $ssubpage->getLabelKey();
                                        $lang[$key] = $ssubpage->getLabels();
                                        $i++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        //Enregistrement des traductions dans des fichiers
        //Zend_Debug::dump($lang);exit;
        $writer = new Ccsd_Lang_Writer($lang);
        $writer->write(SPACE . LANGUAGES, 'menu');
    }

    /**
     * Retourne la liste des pages à retirer pour une collection
     */
    public function getCollectionPageToExclude()
    {
        return array(
            self::PAGE_SUBMIT,
            self::PAGE_BROWSE_PORTAIL
        );
    }

    /**
     * Enregistrement de la page en base
     * @param Ccsd_Website_Navigation_Page $page page à enregistrer
     */
    public function savePage_old($page)
    {
        //Cas particulier des pages personnalisable
        if ($page->isCustom()) {
            $page->setPermalien($this->getUniqPermalien($page));
        } else if ($page->isFile()) {
            $page->saveFile();
        }

        //Enregistrement en base
        $bind = array(
            'SID'           => $this->_sid,
            'PAGEID'        => $page->getPageId(),
            'TYPE_PAGE'     => $page->getPageClass(),
            'CONTROLLER'    => $page->getController(),
            'ACTION'        => $page->getAction(),
            'LABEL'         => $page->getLabelKey(),
            'PARENT_PAGEID' => $page->getPageParentId(),
            'PARAMS'        => $page->getSuppParams()
        );
        $this->_db->insert($this->_table, $bind);
    }

    /**
     * @param Ccsd_Website_Navigation_Page $page
     */
    public function savePage($page) {
        $page->save();
    }

    /**
     * Vérification de l'unicité du lien permanent
     * @param Hal_Website_Navigation_Page_Custom $page
     * @return string
     */
    public function getUniqPermalien($page)
    {
        $permalien = $page->getPermalien();
        //Liste des permaliens
        $permaliens = array();
        /** @var  Hal_Website_Navigation_Page $p */
        foreach ($this->_pages as $p) {
            /** @var Hal_Website_Navigation_Page_Custom $p */
            if ($p->isCustom() && $p != $page) {
                $permaliens[] = $p->getPermalien();
            }
        }

        while (in_array($permalien, $permaliens)) {
            $newPermalien = preg_replace_callback('#([-_]?)(\d*)$#', function($matches) {if ($matches[0] != '') return ($matches[1] . ($matches[2] +1));}, $permalien);
            $permalien = ($permalien == $newPermalien) ? $permalien . '1' : $newPermalien;
        }
        return $permalien;
    }

    /**
     * Transformation de la navigation en tableau PHP (compatible avec la navigation Zend_Navigation)
     * @return array
     */
    public function toArray()
    {
        $res = array();
        $id = 0;
        foreach ($this->_order as $pageid => $spageids) {
            if (isset($this->_pages[$pageid])) {
                $res[$id] = $this->_pages[$pageid]->toArray();
                if (is_array($spageids) && count($spageids) > 0) {
                    $id2 = 0;
                    foreach ($spageids as $spageid => $sspageids) {
                        if (isset($this->_pages[$spageid])) {
                            $res[$id]['pages'][$id2] = $this->_pages[$spageid]->toArray();
                            if (is_array($sspageids) && count($sspageids) > 0) {
                                foreach (array_keys($sspageids) as $sspageid) {
                                    if (isset($this->_pages[$sspageid])) {
                                        $res[$id]['pages'][$id2]['pages'][] = $this->_pages[$sspageid]->toArray();
                                    }

                                }
                            }
                        }
                        $id2++;
                    }
                }
            }
            $id++;
        }

        return $res;
    }

    /**
     * Création de la navigation pour le site
     * @param string $filename nom du fichier de navigation
     */
    public function createNavigation($filename)
    {
        $dir = substr($filename, 0, strrpos($filename, '/'));
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($filename, Zend_Json::encode($this->toArray()));
    }

    /**
     * @param Hal_Site $site
     */
    static public function deleteNavigation(Hal_Site $site) {

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();

        $db->delete('WEBSITE_NAVIGATION', 'SID = ' . $site->getSid());
        $navfilecache = $site->getRootPath(). "config/navigation.json";
        if (file_exists($navfilecache)) {
            unlink($navfilecache);
        }
    }


    /**
     * @param Hal_Site $site
     * @return mixed
     */
    static public function getFromDb(Hal_Site $site)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from('WEBSITE_NAVIGATION')
            -> where('SID = ?', $site->getSid())
            // ATTENTION: L'odre des menu est determine par le NavigationID !!
            // C'est mal mais pour l'instant, c'est comme ca!
            -> order('NAVIGATIONID');

        return $db->fetchAll($sql);
    }

    /**
     * @param Hal_Site $site
     * @param $headers
     */
    static public function setInDb(Hal_Site $site, $navigations)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        foreach ($navigations as $navigation) {
            unset($navigation["NAVIGATIONID"]);
            $navigation["SID"] = $site->getSid();
            $db->insert('WEBSITE_NAVIGATION', $navigation);
        }
    }

    /**
     * Copie des configurations de header
     * @param Hal_Site $model
     * @param Hal_Site $receiver
     */
    static public function duplicate(Hal_Site $model, Hal_Site $receiver)
    {
        self::deleteNavigation($receiver);

        $source  = $model->getRootPath(). "languages/en/menu.php";
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . "languages/en/menu.php";
            mkdir(dirname($dest), 0755, true);
            copy($source, $dest);
        }

        $source  = $model->getRootPath(). "languages/fr/menu.php";
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . "languages/fr/menu.php";
            mkdir(dirname($dest), 0755, true);
            copy($source, $dest);
        }

        $source  = $model->getRootPath(). CONFIG . "navigation.json";
        if (file_exists($source)) {
            $dest = $receiver->getRootPath() . CONFIG . "navigation.json";
            mkdir(dirname($dest), 0755, true);
            copy($source, $dest);
        }

        $sourcedir  = $model->getRootPath(). PAGES;
        if (file_exists($source)) {
            $destdir = $receiver->getRootPath() . PAGES;
            mkdir($destdir, 0755, true);
            Ccsd_Tools::copy_tree($sourcedir , $destdir, 0644, 0755, [ ]);
        }

        self::setInDb($receiver, self::getFromDb($model));
    }

    /**
     * @return int
     */
    public function getSid() {
        return $this -> _sid;
    }

}