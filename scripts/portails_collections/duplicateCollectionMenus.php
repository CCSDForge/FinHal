<?php
/**
 * User: marmol
 * Date: 12/12/17
 *
 * Ce script permet la duplication de la hierarchie de menu d'une collection vers une autre.
 *
 * Fait pour IFIP, qui a plein de collection aux menus identiques.  Permet de changer un menu et de le dupliquer sur l'ensemble des collection
 *
 * Ne pas utiliser sur autre chose que les collections IFIP
 */

$localopts = array(
    'From|F=s' => 'Sid collection a dupliquer',
    'To|T=s'         => 'Sid collection cible ou Sql expression LIKE: eg "IFIP-AICT-%',
    'dryrun'       => 'Testing mode',
);

if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

class Hal_Site_Duplicator
{
    /**
     * @var Hal_Site
     */
    protected $_model = null;

    /**
     * @var Hal_Site
     */
    protected $_receiver = null;

    /**
     * Hal_Site_Duplicator constructor.
     * @param Hal_Site $model
     * @param Hal_Site $receiver
     */
    public function __construct(Hal_Site $model, Hal_Site $receiver)
    {
        $this->setModel($model)->setReceiver($receiver);
    }

    /**
     * @param Hal_Site $model
     * @return $this
     */
    public function setModel(Hal_Site $model)
    {
        $this->_model = $model;
        return $this;
    }

    /**
     * @return Hal_Site
     */
    public function getModel()
    {
        return $this->_model;
    }

    /**
     * @param Hal_Site $receiver
     * @return $this
     */
    public function setReceiver(Hal_Site $receiver)
    {
        $this->_receiver = $receiver;
        return $this;
    }

    /**
     * @return Hal_Site
     */
    public function getReceiver()
    {
        return $this->_receiver;
    }

    /**
     * @param bool
     */
    private function deleteOldNavigation($test = false) {

        $db =  Zend_Db_Table_Abstract::getDefaultAdapter();
        if ($test) {
            print ("Will do: Delete from WEBSITE_NAVIGATION WHERE SID = ' . (int)$this->getReceiver()->getSid();");
            print ("Will do: unlink(" . $this->getReceiver()->getRootPath() . "config/navigation.json)");
        } else {
            $db->delete('WEBSITE_NAVIGATION', 'SID = ' . (int)$this->getReceiver()->getSid());
            $navfilecache = $this ->getReceiver()->getRootPath(). "config/navigation.json";
            if (file_exists($navfilecache)) {
                unlink($navfilecache);
            }
        }
    }

    /**
     *
     */
    private function overwriteNavigationJson() {
        $nav = new Hal_Website_Navigation($this->getReceiver());
        $nav -> setOptions(['sid' => $this->getReceiver()->getSid(), 'languages' => 'en' ]);
        $nav->load();
        $nav->save();  // Sinon, les pageId ne sont pas positionnes...
        // on devrait le faire dans load, mais pas sur que ca cree pas un bug ailleurs!!!
        $nav->createNavigation($this->getReceiver()->getRootPath(). 'config/navigation.json') ;
    }

    /**
     * @param string $content
     * @return mixed
     */
    public function change_menu_label($content) {
        // On recupere la chaine exacte de l'onglet correspondant a la collection
        // Pour garder le capitalisation, les espaces,...
        // ATTETION: ne marche que pour les collections IFIP dont la seule difference entre menu est le menu-label-2!
        $lang = 'en';
        $reader = new Ccsd_Lang_Reader('menu', $this->getReceiver()->getRootPath(). 'languages/', [$lang], true);
        $oldlabel = $reader->get('menu-label-2', $lang);
        switch ($oldlabel) {
            case  'Browse':
            case  'By Author':
            case  'By Year':
            case  'By Author Affiliation':
            case  'By TC':
            case  'By WG':
            case  'Conferences':
            case  'AICT Series':
            case  'LNBIP':
            case  'LNCS':
            case  'Search':
                // Not an expected Label, we use a label from the name of conf
                $oldlabel = $this->getReceiver()->getFullName();
                $oldlabel = preg_replace('/^IFIP-/', ' '  , $oldlabel);
                $oldlabel = preg_replace('/-(\d)/' , ' \1', $oldlabel);
                break;
            default:
                // Ok we keep the founded label


        }
        verbose("Label trouve: $oldlabel\n");
        return preg_replace('/AICT XXX/', $oldlabel, $content);
    }

    /**
     * @param bool
     */
    public function duplicateNavigation($test = false) {

        $this -> deleteOldNavigation($test);
        $source  = $this -> getModel()->getRootPath(). "languages/en/menu.php";
        $dest    = $this -> getReceiver()->getRootPath(). "languages/en/menu.php";
        if ($test) {
            print ("Will do: duplicateCollectionWebSiteNavigation($this->getReceiver()->getSid())\n");
            print ("Will do: copy($source, $dest)\n");
        } else {
            $this->getModel()->duplicateNavigation($this -> getReceiver()->getSid());
            $this->overwriteNavigationJson();
            verbose("Do: cp $source $dest\n");
            $languages_en_menu_php=file_get_contents($source);
            $new_menu_php = $this -> change_menu_label($languages_en_menu_php);
            file_put_contents($dest, $new_menu_php);
        }
    }

}

/* A SUPPRIMER QD LE BOOTSTRAP FERA l'INITIALISATION DU TRANSLATOR */
$translator = new Hal_Translate(Zend_Translate::AN_ARRAY, PATH_TRANSLATION, null, array(
            'scan' => Zend_Translate::LOCALE_DIRECTORY,
            'disableNotices' => true
));
if (defined('SPACE_DATA')) {
    $defaultSpaceLanguage = SPACE_DATA . '/' . SPACE_SHARED . '/languages';
    if (is_dir($defaultSpaceLanguage) && count(scandir($defaultSpaceLanguage)) > 2) {
        $translator->addTranslation($defaultSpaceLanguage);
    }
}
// Thesaurus définis dans le code de l'application
if (is_dir(APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'languages')) {
    $translator->addTranslation(APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'languages');
}

// Traduction des métadonnées définies dans le code de l'application
if (defined('DEFAULT_CONFIG_PATH') && is_dir(DEFAULT_CONFIG_PATH . '/' . LANGUAGES)) {
    $translator->addTranslation(DEFAULT_CONFIG_PATH . '/' . LANGUAGES);
}

Zend_Registry::set('Zend_Translate', $translator);
/* FIN: A SUPPRIMER QD LE BOOTSTRAP FERA l'INITIALISATION DU TRANSLATOR */


/** @var Zend_Console_Getopt $opts */
$test    = isset($opts->test) || isset($opts->dryrun);

$fromCollection = (int) $opts -> From;
$toCollectionArg   = $opts -> To;
define('MODULE','collection');

if (!need_user('apache')) {
    print "WARNING: ce script devrait etre lance en utilisateur " . APACHE_USER ."\n";
}

if ($fromCollection == null || $toCollectionArg == null) {
    print "Argument --From et --To obligatoire\n";
    exit;
}
$db =  Zend_Db_Table_Abstract::getDefaultAdapter();
$toCollection = null;
$numToColl = (int) $toCollectionArg;
if ($numToColl != 0) {
    $sql = $db ->select() -> from( 'SITE', [ 'SID', 'SITE' ]) -> where ('SID = ?', $numToColl);
    $toCollectionInfos = $db -> fetchall($sql );
    $toCollection = $toCollectionInfos[0];
}
if ($toCollection == null) {
    print "Pas de collection cible\n";
    exit;
}

$fromcol = Hal_Site::loadSiteFromId($fromCollection);

$collId   = $toCollection['SID'];
$tocol = Hal_Site::loadSiteFromId($collId);
$tocol->registerSiteConstants();

$codeFrom = $fromcol->getShortname();
$codeTo   = $tocol->getShortname();
print "Duplicate de $codeFrom vers $codeTo\n";

$duplicator = new Hal_Site_Duplicator($fromcol, $tocol);

if (!preg_match('/^IFIP/', $codeTo) || !preg_match('/^IFIP/', $codeFrom)) {
    print "ERROR: Ce programme ne fonctionne que pour les collections IFIP\n";
    exit (1);
}

$duplicator ->  duplicateNavigation($test);

