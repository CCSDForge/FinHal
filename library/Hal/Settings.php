<?php

/**
 * Class Hal_Settings
 * Parametres de l'archive Hal
 *
 */

class Hal_Settings
{
    /**
     * Version de la plateforme
     */
    const VERSION = 3.0;

    /**
     * mail de la plateforme
     */
    const MAIL = "contact@archives-ouvertes.fr";

    /**
     * expediteur des mails envoyés par la plateforme
     */
    const MAIL_FROM = "noreply@ccsd.cnrs.fr";

    /**
     *
     * Choix du type de document vide dans submit.ini
     */
    const DEF_TYPDOC = "none";

    /** @const hashing algorithm used for emails */
    const EMAIL_HASH_TYPE = 'md5';
    /**
     * @var array
     */
    static private $iniFilesCache = [];

    /** @var string */
    static private $_appversion;
    /**
     * Langues disponibles de l'interface
     * @var array
     */
    static private $_languages = array('fr', 'en', 'es', 'eu');

    /**
     * Language tags
     * @see https://tools.ietf.org/html/rfc3066
     */
    static private $_languageTags = array('fr' => array('fr_FR.UTF-8', 'fr_FR', 'fr', 'french'), 'en' => array('en_GB.UTF-8', 'en_GB', 'en', 'english'));

    /**
     * Cache pour la liste des metas du portail principal (hal)
     */
    static private $_coreMeta = [];
    /**
     * ----------------------------------------------------------------------------------------------------------------
     * DEPOT
     */
    /**
     * Type de dépôt
     */
    const SUBMIT_INIT     = 'index'; //Nouveau dépôt
    const SUBMIT_UPDATE   = 'update'; // Modification de dépôt
    const SUBMIT_MODIFY   = 'modify'; // Correction de dépôt
    const SUBMIT_MODERATE = 'moderate'; // Correction par modérateur
    const SUBMIT_REPLACE  = 'replace'; //Nouvelle version
    const SUBMIT_ADDFILE  = 'addfile'; //Ajout du fichier à une notice
    const SUBMIT_ADDANNEX = 'addannex'; //Ajout du fichier à une notice

    /**
     * Interface de dépôt simplifiée / détaillée
     */
    const SUBMIT_MODE_SIMPLE = 1;
    const SUBMIT_MODE_DETAILED = 0;

    /**
     * Origine du dépôt
     */
    const SUBMIT_ORIGIN_WEB =   'WEB';
    const SUBMIT_ORIGIN_WS =   'WS';
    const SUBMIT_ORIGIN_XML =   'XML';
    const SUBMIT_ORIGIN_SWORD =   'SWORD';

    /**
     * Etapes de dépôt
     */
    const SUBMIT_STEP_TYPDOC    = 'typdoc'; //Choix du type de dépôt
    const SUBMIT_STEP_FILE      = 'file';   //Dépôt des fichiers
    const SUBMIT_STEP_META      = 'meta';   //Saisie des métadonnées
    const SUBMIT_STEP_AUTHOR    = 'author'; //Renseignement des auteurs
    const SUBMIT_STEP_RECAP     = 'recap';  //Etape récapitulative

    /**
     * Type des fichiers déposés
     */
    const FILE_TYPE = 'file'; //Fichier
    const FILE_TYPE_SOURCES = 'src'; //Fichier source
    const FILE_TYPE_ANNEX = 'annex'; //Fichier annexe

    /**
     * Droits sur les fichiers
     */
    const FILE_SOURCE_AUTHOR = 'author'; //Fichier auteur
    const FILE_SOURCE_GREEN_PUBLISHER = 'greenPublisher'; //Fichier editeur mais autorise le depot
    const FILE_SOURCE_PUBLISHER_AGREEMENT = 'publisherAgreement'; //Accord explicite de l'éditeur
    const FILE_SOURCE_PUBLISHER_PAID = 'publisherPaid'; //Institution paie pour AO


    static public $submitTypeIconsClass = array('file'=> 'glyphicon glyphicon-file', 'notice'=> '', 'annex'=> 'glyphicon glyphicon-briefcase');

	static public $idHalIconClass = 'glyphicon glyphicon-user';

    /**
     * liste des types de dépôts n'acceptant pas le dépôt de fichier
     * @var array
     */
    static protected $_typdocNotice = array('PATENT');

    /**
     * liste des types de dépôts obligeant le dépôt de fichier
     * @var array
     */
    static protected $_typdocFulltext = array('THESE', 'HDR', 'LECTURE', 'IMG', 'VIDEO', 'SON', 'MAP', 'PRESCONF', 'MEM', 'SOFTWARE', 'ETABTHESE');
    /**
     * Configuration en fonction du type de dépôt
     * @var array
     */
    static protected $_configTypdoc = array(
        'UNDEFINED' => array(
            'typdocsAssociated' => array('ART', 'COMM', 'POSTER', 'OUV', 'COUV', 'DOUV', 'REPORT', 'OTHER', 'THESE', 'HDR', 'ETABTHESE'),
            'citation' => '{date}'
        ),
        'ART' => array(
            'typdocsAssociated' => array('UNDEFINED', 'COMM', 'POSTER', 'OUV', 'COUV', 'DOUV', 'REPORT', 'OTHER'),
            'citation' => '<i>{journal}</i>, {journalPublisher}, {date}, {serie}, {volume}, {page}. {publisherLink}. {doi}',
            'aut_quality' => array('aut', 'crp', 'edt', 'ctb', 'ann', 'trl', 'oth')
        ),
        'COMM' => array(
            'typdocsAssociated' => array('UNDEFINED', 'ART', 'OUV', 'POSTER', 'COUV', 'DOUV', 'REPORT', 'OTHER'),
            'citation' => '<i>{conferenceTitle}</i>, {conferenceOrganizer}, {conferenceStartDate}, {city}, {country}. {page}, {doi}'
        ),
        'POSTER' => array(
            'typdocsAssociated' => array('UNDEFINED', 'ART', 'OUV', 'COMM', 'COUV', 'DOUV', 'REPORT', 'OTHER'),
            'citation' => '{scientificEditor}. <i>{conferenceTitle}</i>, {conferenceStartDate}, {city}, {country}. {publisher}, {source}, {volume}, {page}, {datePublication}, {serie}. {publisherLink}. {doi}',
        ),
        // non utilise pour HalSpm
        'PRESCONF' => array(
            'typdocsAssociated' => array('UNDEFINED', 'ART', 'OUV', 'COMM', 'COUV', 'DOUV', 'REPORT', 'OTHER'),
            'citation' => '{scientificEditor}. <i>{conferenceTitle}</i>, {conferenceStartDate}, {city}, {country}. {publisher}, {source}, {volume}, {page}, {datePublication}, {serie}. {publisherLink}. {doi}',
        ),
        'OUV' => array(
            'typdocsAssociated' => array('UNDEFINED', 'ART', 'COMM', 'POSTER', 'COUV', 'DOUV', 'REPORT', 'OTHER'),
            'citation' => '{scientificEditor}. {publisher}, {volume}, {page}, {date}, {serie}, {seriesEditor}, {isbn}. {doi}. {publisherLink}'
        ),
        // non utilise pour HalSpm
        'COUV' => array(
            'typdocsAssociated' => array('UNDEFINED', 'ART', 'COMM', 'POSTER', 'OUV', 'DOUV', 'REPORT', 'OTHER'),
            'citation' => '{scientificEditor}. <i>{bookTitle}</i>, {volume}, {publisher}, {page}, {date}, {serie}, {isbn}. {doi}. {publisherLink}'
        ),
        // non utilise pour HalSpm
        'DOUV' => array(
            'typdocsAssociated' => array('UNDEFINED', 'ART', 'COMM', 'POSTER', 'OUV', 'COUV', 'REPORT', 'OTHER'),
            'citation' => '{scientificEditor}. <i>{conferenceTitle}</i>, {conferenceStartDate}, {city}, {country}. <i>{journal}</i>, {volume}, {publisher}, {page}, {datePublication}, {serie}, {isbn}. {doi}. {publisherLink}'
        ),
        'REPORT' => array(
            'typdocsAssociated' => array('UNDEFINED', 'ART', 'COMM', 'POSTER', 'OUV', 'COUV', 'OTHER'),
            'citation' => '[{reportType}] {number}, {authorityInstitution}. {date}, {page}'
        ),
        // non utilise pour HalSpm
        'OTHER' => array(
            'typdocsAssociated' => array('UNDEFINED', 'ART', 'COMM', 'POSTER', 'COUV', 'DOUV', 'REPORT'),
            'citation' => '<i>{bookTitle}</i>, {date}, {page}. {doi}',
            'aut_quality' => array('aut', 'crp', 'edt', 'sad', 'ctb', 'wam', 'pht', 'ann', 'trl', 'cwt', 'ill', 'stm', 'pro', 'ard', 'sds', 'ctg', 'oth', 'spk')
        ),
        // non utilise pour HalSpm
        'PATENT' => array(
            'citation' => '{country}, {number}. {localReference}. {date}, {page}',
            'arxiv' =>  false,
            'pubmed' =>  false,
            'licence' => false
        ),
        // non utilise pour HalSpm
        'THESE' => array (
            'typdocsAssociated' => array('HDR', 'ETABTHESE'),
            'fileOrigin' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'visibility' =>  array("now"),
            'citation' => '{domain}. {authorityInstitution}, {date}. {language}. {nnt}'
        ),
        // non utilise pour HalSpm
        'ETABTHESE' => array (
            'typdocsAssociated' => array('HDR', 'THESE'),
            'fileOrigin' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'visibility' =>  array("now"),
            'citation' => '{domain}. {authorityInstitution}, {date}. {language}. {thesisNumber}'
        ),
        // non utilise pour HalSpm
        'HDR'=> array (
            'typdocsAssociated' => array('THESE', 'ETABTHESE'),
            'fileOrigin' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'visibility' =>  array("now"),
            'citation' => '{domain}. {authorityInstitution}, {date}.'
        ),
        // non utilise pour HalSpm
        'MEM'=> array (
            'fileOrigin' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'citation' => '{domain}. {date}.'
        ),
        'LECTURE' => array (
            'fileOrigin' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'visibility' =>  array("now"),
            'citation' => '{lectureType}. {lectureName}, {city}, {country}. {date}, {page}'
        ),
        // non utilise pour HalSpm
        'IMG' => array (
            'fileType' =>  array(self::FILE_TYPE),
            'fileLimit' => 1,
            'fileOrigin' =>  false,
            'requiredLicence' => true,
            'mainFile' =>  array("jpg", "jpeg", "jpe", "jps", "png", "gif", "tif", "tiff", "ms3d", "odg", "otg", "pct", "gls", "svg"),
            'extensions' =>  array("jpg", "jpeg", "jpe", "jps", "png", "gif", "tif", "tiff", "ms3d", "odg", "otg", "pct", "gls", "svg"),
            'grobid' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'citation' => '{imageType}. {source}, {city}, {country}. {date}',
            'nbAffiliatedAuthors' => 0
        ),
        // non utilise pour HalSpm
        'SOFTWARE' => array (
            'fileType' =>  array(self::FILE_TYPE, self::FILE_TYPE_SOURCES),
            'fileLimit' => 1,
            'fileOrigin' =>  false,
            'requiredLicence' => true,
            'mainFile' =>  array('zip', 'gz'),
            'extensions' =>  array("zip", "gz"),
            'grobid' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'softwareHeritage' =>  true,
            'citation' => '{date}, {swh}',
            'nbAffiliatedAuthors' => 0,
            'aut_quality' => array('dev', 'ctr', 'mtn')
        ),
        'MAP' => array (
            'fileType' =>  array(self::FILE_TYPE, self::FILE_TYPE_SOURCES),
            'fileLimit' => 1,
            'fileOrigin' =>  false,
            'requiredLicence' => true,
            'mainFile' =>  array('jpg', 'pdf'),
            'extensions' =>  array("jpg", "jpeg", "jpe", "jps", "png", "gif", "tif", "tiff", "ms3d", "odg", "otg", "pct", "pdf", "doc", "docx", "ppt", "pptx", "odc", "ods", "rtf", "odf", "odt", "ott", "svg"),
            'grobid' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'citation' => '{number}. {city}, {country}. {date}'
        ),
        'VIDEO' => array (
            'fileLimit' => 1,
            'fileOrigin' =>  false,
            'requiredLicence' => true,
            'mainFile' =>  array("avi", "flv", "mov", "movie", "mp4", "mpe", "mpeg", "mpg", "qt", "rm", "rmvb", "rv", "vob", "wmv", "m4a", "m4v", "mpg4"),
            'extensions' =>  array("avi", "flv", "mov", "movie", "mp4", "mpe", "mpeg", "mpg", "qt", "rm", "rmvb", "rv", "vob", "wmv", "m4a", "m4v", "mpg4"),
            'grobid' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'citation' => '{date}',
            'nbAffiliatedAuthors' => 1,
            'aut_quality' => array('aut', 'int', 'dis', 'enq', 'win', 'ard', 'win', 'sds', 'pht', 'dir', 'pro', 'prd', 'com', 'edt', 'sad', 'man', 'med', 'trl', 'ctg', 'ctb', 'oth')
        ),
        'SON' => array (
            'fileLimit' => 1,
            'fileOrigin' =>  false,
            'requiredLicence' => true,
            'mainFile' =>  array("aac", "ac3", "aif", "aifc", "aiff", "au", "bwf", "mp2", "mp3", "m4r", "ogg", "ogm", "ra", "ram", "wma", "wav"),
            'extensions' =>  array("aac", "ac3", "aif", "aifc", "aiff", "au", "bwf", "mp2", "mp3", "m4r", "ogg", "ogm", "ra", "ram", "wma", "wav"),
            'grobid' =>  false,
            'arxiv' =>  false,
            'pubmed' =>  false,
            'citation' => '{date}'
        ),
        // non utilise pour HalSpm
        'REPORT_ETAB'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_COOR'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_LABO'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_RECH'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_LICE'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_LPRO'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_MAST'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_DOCT'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_FORM'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_INTER'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_GLICE'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_GMAST'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_FPROJ'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_RETABINT'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],
        'REPORT_RFOINT'   => ['citation' => '{date}, {hceres_etabsupport_local}, {hceres_etabassoc_local}'],

        'PRESSE' => ['citation' => '[{reportType}] {number}, {date}, {page}'],
        'CR' => ['citation' => '[{reportType}] {number}, {date}, {page}'],
        'AVIS_NOTE' => ['citation' => '[{reportType}] {number}, {date}, {page}'],
        'SCHDIR' => ['citation' => '[{reportType}] {number}, {date}, {page}'],
        'INV' => ['citation' => '[{reportType}] {number}, {date}, {page}'],
        'ORGA_REGINT' => ['citation' => '[{reportType}] {number}, {date}, {page}'],
        'CORRESP' => ['citation' => '[{reportType}] {number}, {date}, {page}'],


        'DEFAULT' => array (
            'fileType' => array(self::FILE_TYPE, self::FILE_TYPE_SOURCES, self::FILE_TYPE_ANNEX),
            'fileOrigin' => true,
            'fileLimit' => false,
            'licence' => true,
            'requiredLicence' => false,
            'mainFile' => array('pdf'),
            'extensions' => array("tex", "eps_tex", "ps_tex", "pstex", "pdf_tex", "pdf_t", "pdftex", "zip", "gz", "odc", "ods", "pages", "cls", "clo", "cnf", "sty", "bst", "bib", "bbl", "toc", "idx", "aux", "def", "loc", "table",
                "pdf", "doc", "docx", "txt", "dot", "dotx", "rtf", "odf", "odt", "ott", "html", "htm",
                "ppt", "pptx", "pot", "potx", "pps", "ppsx", "pptm", "ppsm", "ps", "eps", "odp", "ots", "key", "knt",
                "xls", "xlsx", "xlsm", "xltx", "xlt",
                "xml","xsl",
                "jpg", "jpeg", "jpe", "jps", "png", "gif", "tif", "tiff", "ms3d", "odg", "otg", "pct", "svg", "gls",
                "aac", "ac3", "aif", "aifc", "aiff", "au", "bwf", "mp2", "mp3", "M4r", "ogg", "ogm", "ra", "ram", "wma", "wav",
                "avi", "flv", "mov", "movie", "mp4", "mpe", "mpeg", "mpg", "qt", "rm", "rmvb", "rv", "vob", "wmv", "m4a", "m4v", "mpg4"),
            'visibility' => array("now", "15-d", "1-M", "3-M", "6-M", "1-Y", "date", "2-Y"),
            'embargo' => "2-Y",
            'grobid' => true,
            'arxiv' => true,
            'pubmed' => true,
            'softwareHeritage' =>  false,
            'validNotice' => false,
            'citation' => '{localReference}. {comment}. {date}, {page}',
            'typdocsAssociated' => array(),
            'defaultTypdoc' => '',
            'nbAffiliatedAuthors' => false,
            'aut_quality' => array('aut', 'crp', 'edt', 'sad', 'ctb', 'wam', 'pht', 'ann', 'trl', 'cwt', 'ill', 'stm', 'pro', 'ard', 'sds', 'ctg', 'oth', 'spk', 'csc')
        )
    );

    /**
     * Table des métadonnées founies par des listes de valeurs
     */
    const TABLE_REF_META = 'REF_METADATA';

    /**
     * Domaines arXiv
     */
    const TABLE_DOMAIN_ARXIV = 'REF_DOMAIN_ARXIV';

    /**
     * LICENCES
     */
    static private $_licences = array(
        'http://creativecommons.org/licenses/by/' => array(
            'icon' => array('cc','by'),
            'url' => 'http://creativecommons.org/licenses/by/4.0/',
        ),
        'http://creativecommons.org/licenses/by-nc/' => array(
            'icon' => array('cc','by','nc'),
            'url' => 'http://creativecommons.org/licenses/by-nc/4.0/',
        ),
        'http://creativecommons.org/licenses/by-nd/' => array(
            'icon' => array('cc','by','nd'),
            'url' => 'http://creativecommons.org/licenses/by-nd/4.0/',
        ),
        'http://creativecommons.org/licenses/by-sa/' => array(
            'icon' => array('cc','by','sa'),
            'url' => 'http://creativecommons.org/licenses/by-sa/4.0/',
        ),
        'http://creativecommons.org/licenses/by-nc-nd/' => array(
            'icon' => array('cc','by','nc','nd'),
            'url' => 'http://creativecommons.org/licenses/by-nc-nd/4.0/',
        ),
        'http://creativecommons.org/licenses/by-nc-sa/' => array(
            'icon' => array('cc','by','nc','sa'),
            'url' => 'http://creativecommons.org/licenses/by-nc-sa/4.0/',
        ),
        'http://creativecommons.org/choose/mark/' => array(
            'icon' => array('nc'),
            'url' => 'http://creativecommons.org/choose/mark/',
        ),
        'http://creativecommons.org/publicdomain/zero/1.0/' => array(
            'icon' => array('cc','zero'),
            'url' => 'http://creativecommons.org/publicdomain/zero/1.0/',
        ),
        'http://hal.archives-ouvertes.fr/licences/etalab/' => array(
            'icon' => array('etalab'),
            'url' => 'http://www.etalab.gouv.fr/pages/licence-ouverte-open-licence-5899923.html',
        ),
        'http://hal.archives-ouvertes.fr/licences/copyright/' => array(
        ),
        'http://hal.archives-ouvertes.fr/licences/publicDomain/' => array(
        ),
    );

    static public $_idtypeToDataProvider = [
        "arxiv" => "Ccsd_Dataprovider_Arxiv",
        "bibcode" => "Ccsd_Dataprovider_Bibcode",
        "bioarxiv" => "Ccsd_Dataprovider_Bioarxiv",
        "cern" => "Ccsd_Dataprovider_Cern",
        "doi" => "Ccsd_Dataprovider_Crossref",
        "inspire" => "Ccsd_Dataprovider_Inspire",
        "ird" => "Ccsd_Dataprovider_Ird",
        "oatao" => "Ccsd_Dataprovider_Oatao",
        "pdf" => "Ccsd_Dataprovider_Grobid",
        "pubmed" => "Ccsd_Dataprovider_Pubmed",
        "pubmedcentral" => "Ccsd_Dataprovider_Pubmedcentral",
    ];

    /**
	 * Récupération des types de document par défaut disponibles de l'archive HAL
     * @return array
	 */
	static public function getListDefaultTypdoc()
    {
        $typdocs = Zend_Json::decode(file_get_contents(APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'typdoc.json'));
        $keys = array();
        foreach ($typdocs as $typdoc) {
            if ( isset($typdoc['type']) && $typdoc['type'] == 'typdoc' ) {
                $keys[] = $typdoc['id'];
            }
            if ( isset($typdoc['children']) && is_array($typdoc['children']) && count($typdoc['children']) > 0 ) {
                foreach ($typdoc['children'] as $child) {
                    if ( isset($child['type']) && $child['type'] == 'typdoc' ) {
                        $keys[] = $child['id'];
                    }
                }
            }
        }
        return $keys;
	}

	/**
	 * Récupération des langues de l'archive HAL
     * @return array
	 */
	static public function getLanguages()
    {
		return self::$_languages;
	}
	/**
     * Retourne les languages tags possibles pour une langue
     * @see https://tools.ietf.org/html/rfc3066
     * @see https://secure.php.net/manual/en/function.setlocale.php
     * return array language tags
     */
	static public function getLanguageTag($languageCode) {

            if (!in_array($languageCode, self::getLanguages())) {
                return null;
            }

            if (!is_string($languageCode)) {
                return null;
            }

            if (!isset(self::$_languageTags[$languageCode])) {
                $languageTag = null;
            } else {
                $languageTag = self::$_languageTags[$languageCode];
            }
            return $languageTag;
	}
    /**
     * Retourne la liste des étapes du dépôt
     * @return array
     */
    static public function getSubmissionsSteps()
    {
        return [
            static::SUBMIT_STEP_FILE,
            static::SUBMIT_STEP_META,
            static::SUBMIT_STEP_AUTHOR,
            static::SUBMIT_STEP_RECAP
        ];
    }

    /**
     * Indique si on doit forcer le dépôt d'une notice
     * @param string $typSubmit type de dépôt (nouveau, modif, ...)
     * @param string $typdoc type de document (UNDEFINED, REPORT, ART, IMG, ...)
     * @return boolean
     */
    static public function submitNotice($typSubmit = self::SUBMIT_INIT, $typdoc = null)
    {
        return ($typdoc != null && in_array($typdoc, self::getTypdocNotice()));
    }

    /**
     * Récupérations des typdocs pour le dépôt de notice.
     * @return array
     */
    static public function getTypdocNotice()
    {
        $iniSetting = self::getIniSetting('submit.ini', 'typdocNotice');
        if ($iniSetting !== false) {
            if ( !is_array($iniSetting) ) {
                $iniSetting = [(string)$iniSetting];
            }
            $out = array_merge(self::$_typdocNotice, $iniSetting);
        } else {
            $out = self::$_typdocNotice;
        }
        return $out;
    }

    /**
     * Récupère les typdocs qui imposent le dépôt fulltext
     * @return array
     */
    static public function getTypdocFulltext()
    {
        $iniSetting = self::getIniSetting('submit.ini', 'typdocFulltext');
        if ($iniSetting !== false) {
            if ( !is_array($iniSetting) ) {
                $iniSetting = [(string)$iniSetting];
            }
            $out = $iniSetting;
        } else {
            $out = self::$_typdocFulltext;
        }
        return $out;
    }

    /**
     * @deprecated : use Hal_Site_portail::getTypdocs on Portail object
     * Retourne les types de documents en fonction d'un portail
     *
     * Si dans une collection et pas de fichier de conf utilise la conf du portail par défaut
     * @param string $portail nom court du portail du portail
     * @return array
     */
    static public function getTypdocs ($portail = null)
    {
        if (null === $portail) {
            // TODO: on devrait passer par l'objet current site
            $dir = SPACE . CONFIG;
        } else {
            $dir = SPACE_DATA . '/' . Hal_Site_Portail::MODULE . '/' . $portail . '/' . CONFIG;
        }

        if ( is_file($dir . 'typdoc.json') ) {
            $file = $dir . 'typdoc.json';
        } else {
            $file = APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'typdoc.json';
        }
        return Zend_Json::decode(file_get_contents($file));
    }

    /**
     * @param string $portail
     * @return array
     */
    static public function getTypdocsSelect($portail = null)
    {
        $result = [];
        $result[""] = "";
        $typdocs = self::getTypdocs($portail);
        foreach ($typdocs as $key => $elem) {
            if (isset($elem['type'])) {
                if ($elem['type'] == 'category') {
                    $tmp = [];
                    foreach ($elem['children'] as $keyC =>$child) {
                        $tmp[$keyC] = $child['label'];
                    }
                    $result[$elem['label']] =$tmp;
                } else if ($elem['type'] == 'typdoc') {
                    $result[$key] = $elem['label'];
                }
            }
        }
        return $result;
    }

    /**
     * @param string $portail
     * @return array
     */
    static public function getTypdocsAvailable($portail = null)
    {
        $result = [];
        foreach (self::getTypdocs($portail) as $key => $elem) {
            if (isset($elem['type'])) {
                if ($elem['type'] == 'category') {
                    foreach ($elem['children'] as $keyC =>$child) {
                        $result[] = $keyC;
                    }
                } else if ($elem['type'] == 'typdoc') {
                    $result[] = $key;
                }
            }
        }
        return $result;
    }


    /**
     * On récupère la liste des type de document possible à partir de l'extension d'un fichier
     * Si l'extension n'est pas précisée, on ne filtre rien
     *
     * Tous les types de dépots qui n'ont pas de liste d'extension acceptées s'aligne sur le comportement par défaut
     *
     * @param string $extension
     * @return array
     */
    static public function getTypdocsFiltered($extension = "")
    {
        $extension = strtolower($extension);
        $typdocs = [];

        if ($extension == "")
            return $typdocs;

        $isInDefault = in_array($extension, self::$_configTypdoc["DEFAULT"]["extensions"]);

        $typesAvailable = Hal_Settings::getTypdocsAvailable(SPACE_NAME);

        foreach ($typesAvailable as $i => $type) {
            if ((isset(self::$_configTypdoc[$type])
                    && isset(self::$_configTypdoc[$type]["extensions"])
                    && !in_array($extension, self::$_configTypdoc[$type]["extensions"]))
                || !$isInDefault) {

                $typdocs[] = $typesAvailable[$i];
            }
        }
        return $typdocs;
    }

    /**
     * Retourne la liste des domaines en fonction d'un portail
     *
     * @param int id du portail
     * @return string json
     */
    public static function getDomains( $sid = SITEID )
    {

        $filename = SPACE . CONFIG . 'domains.json';

        if ( file_exists($filename) ) {
            return $filename;
        }
        if ($sid != 1) {
        	$db = Zend_Db_Table_Abstract::getDefaultAdapter();

        	if (($ids = $db->fetchCol($db->select()->from("PORTAIL_DOMAIN", "ID")->where("SID = " . $sid)->order("ID ASC"))) != false) {

        		$sql = $db	->select()
		        			->distinct(true)
		        			->from(array('r1' => 'REF_DOMAIN'), array("r1.CODE"))
		        			->joinLeft(array('r2' => 'REF_DOMAIN'), "r1.PARENT = r2.ID", new Zend_Db_Expr("r2.CODE AS PARENT"))
		        			->where('r1.ID IN (' . implode (",", $ids) . ')')
		        			->order("r1.LEVEL DESC")
		        			->order("r1.ID ASC");

        		$o = $db->fetchAll($sql);

        		$r = array ();
        		foreach ($o as $o2) {
        			$child = array ($o2["CODE"] => array ());
        			if (array_key_exists ($o2["CODE"], $r)) {

        				if ($o2["PARENT"] != null) {
        					if (!array_key_exists ($o2["PARENT"], $r)) {
        						$r[$o2["PARENT"]] = array ();
        					}

        					if (array_key_exists ($o2["CODE"], $r[$o2["PARENT"]])) {
        						$r[$o2["PARENT"]][$o2["CODE"]] = $r[$o2["PARENT"]][$o2["CODE"]] + array ($o2["CODE"] => array ());
        					} else {
        						$r[$o2["PARENT"]][$o2["CODE"]] = $r[$o2["CODE"]];
        						unset ($r[$o2["CODE"]]);
        					}
        				}
        			} else if ($o2["PARENT"]) {
        				if (array_key_exists($o2["PARENT"], $r)) {
        					$r[$o2["PARENT"]] = array_merge( $r[$o2["PARENT"]], array($o2["CODE"] => array()));
        				} else $r[$o2["PARENT"]] = array($o2["CODE"] => array());
        			} else $r[$o2["CODE"]] = array();
        		}

        		@mkdir(dirname($filename), 0777, true);

        		file_put_contents($filename, Zend_Json::encode($r));

        		return $filename;
        	}
        }
        return APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'domains.json';
    }

    /**
     * Add "inter_" prefix to all keys of array, recursively
     * @param $array (keys are strings)
     * @return mixed
     */
    static protected function ajoutInter($array)
    {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $array['inter_'.$k] = self::ajoutInter($v);
                unset($array[$k]);
            } else {
                $array['inter_'.$k] = $v;
                unset($array[$k]);
            }
        }

        return $array;
    }

    /**
     * Todo: This function is used??? YES mais cache par la construction des formulaires
     * Retourne la liste des domaines d'interdisciplinarité en fonction d'un portail
     *
     * @param int id du portail
     * @return string name of a json file
     */
    public static function getDomainsInter( $sid = null )
    {
        $filename = SPACE . CONFIG . 'domains_inter.json';
        // Si les domaines d'interdisciplinarite sont specifies, on les prends
        if ( file_exists($filename) ) {
            return $filename;
        }
        // Sinon, tous les domaines de Hal seront proposes en domaines interdisciplinaire
        // Et on ecrit le fichier
        // DANGER TODO : l'ajout de domaines apres initialisation ne permets pas une mise a jour...
        $allDomains = APPLICATION_PATH . "/../" . LIBRARY . THESAURUS . 'domains.json';
        if (is_file($allDomains)) {
            $tabObj = Zend_Json::decode(file_get_contents($allDomains),true);
            if (is_array($tabObj)){
                $tabObj = self::ajoutInter($tabObj);
            }
            @mkdir(dirname($filename), 0777, true);
            file_put_contents($filename, Zend_Json::encode($tabObj));
            return $filename;
        }
        // TODO: Comprends pas cela: le fichier domains.json du code n'existe pas:
        // Devrait etre un PANIC: ne doit jamais arrive
        return $allDomains;
    }

    /**
     * Retourne le type de document par défaut
     * par exemple pour hal -> préprint, pour medihal -> image, ...
     * @return string
     */
    static public function getDefaultTypdoc()
    {
        $defTypdoc = (string) static::getSetting('defaultTypdoc');

        if (empty($defTypdoc)) {
            //Dans un premier temps, on configure tous les portails avec un type de document vide
            //return Hal_Settings::getTypdocsAvailable()[0];
            return '';
        } else if (self::DEF_TYPDOC == $defTypdoc) {
            return '';
        } else {
            return $defTypdoc;
        }
    }

    /**
     * For a Document Type, return the associated document type
     * @param string
     * @return string[]
     */
    static public function getTypdocAssociated($typdoc)
    {
        $res =  self::getTypdocSetting('typdocsAssociated', $typdoc);
        $res[] = $typdoc;
        return $res;
    }

    /**
     * Retourne la liste des types de fichiers
     * @param string $typdoc
     * @param string $extension
     * @return array
     */
    static public function getFileTypes($typdoc, $extension)
    {
        $types = self::getTypdocSetting('fileType', $typdoc);
        if (! self::isMainFileType($typdoc, $extension)) {
            return array_diff($types, array(self::FILE_TYPE));
        }
        return $types;
    }

    /**
     * @return array
     * @throws ReflectionException
     */
    static public function getFileOrigines() {
        $orig = array();
        $oClass = new ReflectionClass('Hal_Settings');
        foreach ( $oClass->getConstants() as $k=>$v ) {
            if ( substr($k, 0, 12) == 'FILE_SOURCE_' ) {
                $orig[] = $v;
            }
        }
        return $orig;
    }

    /**
     * Retourne la liste des formats de fichiers annexes acceptés
     * @return array
     */
    static public function getFileFormats()
    {
        return Hal_Referentiels_Metadata::getValues('typeAnnex');
    }

    /**
     * Indique s'il y a une limite du nombre de fichier pour un type de dépôt
     * @param $typdoc
     * @return string
     */
    static public function getFileLimit($typdoc)
    {
        return self::getTypdocSetting('fileLimit', $typdoc);
    }

    /**
     * Indique si un type de dépôt possède une configuration spécifique
     * @param string $typdoc typde de dépôt
     * @return bool
     */
    static public function existSettings($typdoc)
    {
        return isset(self::$_configTypdoc[$typdoc]);
    }
    /**
     * Retourne un parametre pour un type de dépot
     * @param $setting string parametre
     * @param $typdoc string typde de dépôt
     * @return string|array
     */
    static public function getTypdocSetting($setting, $typdoc)
    {
        return Ccsd_Tools::ifsetor(self::$_configTypdoc[$typdoc][$setting], self::$_configTypdoc['DEFAULT'][$setting]);
    }

    /**
     * @param $typdoc
     * @return array|string
     */
    static public function getCitationStructure($typdoc)
    {
	return self::getTypdocSetting('citation', $typdoc);
    }

    /**
     * Permet de regarder si un paramétrage spécifique a été défini pour le portail courant
     * @param string $file nom du fichier
     * @param $setting string nom du parametre
     * @return array|string|bool
     */
    static private function getIniSetting($file, $setting)
    {
        $iniFile = 'NoIniFilePresent';
        $site = Hal_Site::getCurrent();
        if ($site) {
            $iniFile = $site->getConfigDir() . $file;
        } else {
            if (defined('SPACE')) {
                $iniFile = SPACE . CONFIG . $file;
            }
        }
        if (is_file($iniFile)) {
            if (array_key_exists($iniFile, self::$iniFilesCache)) {
                $ini = self::$iniFilesCache[$iniFile];
            } else {
                $ini = (new Zend_Config_Ini($iniFile))->toArray();
                self::$iniFilesCache[$iniFile] = $ini;
            }
            if (isset($ini[$setting])) {
                return $ini[$setting];
            }
        }
        return false;
    }

    /**
     * Retourne les extensions de fichiers acceptés
     * @param string $typdoc
     * @return array
     */
    static public function getFileExtensionAccepted($typdoc = null)
    {
        return self::getTypdocSetting('extensions', $typdoc);
    }

    /**
     * Retourne les types de fichiers acceptés comme fichiers principaux
     * @param string $typdoc
     * @return string[]
     */
    static public function getMainFileType($typdoc)
    {
        return self::getTypdocSetting('mainFile', $typdoc);
    }

    /**
     * Indique si on peut utiliser GROBID sur ce type de dépôt
     * @param string $typdoc
     * @return bool
     */
    static public function useGrobid($typdoc)
    {
        return self::getTypdocSetting('grobid', $typdoc);
    }

    /**
     * Retourne les périodes d'embargo disponibles
     * @param $typdoc
     * @return string[]
     */
    static public function getFileVisibility($typdoc)
    {
        //On regarde si un paramétrage spécifique au portail a été défini
        $out = array('now');
        $iniSetting = self::getIniSetting('submit.ini', 'visibility');
        if ($iniSetting !== false) {
            $out = $iniSetting;
        } else {
            $out = self::getTypdocSetting('visibility', $typdoc);
        }
        //Si admin|moderator alors on ajoute l'option date
        if ( ( Hal_Auth::isAdministrator() || Hal_Auth::isModerateur(SITEID) ) && !in_array('date', $out) ) {
            $out[] = 'date';
            if ( !in_array('now', $out) ) {
                array_unshift($out, 'now');
            }
        }
        return $out;
    }

    /**
     * Retourne la période d'embargo maximum
     * @param $typdoc
     * @return string
     */
    static public function getMaxEmbargo($typdoc)
    {
        //On regarde si un paramétrage spécifique au portail a été défini
        $iniSetting = self::getIniSetting('submit.ini', 'embargo');
        if ($iniSetting !== false) {
            return $iniSetting;
        }
        return self::getTypdocSetting('embargo', $typdoc);
    }
    
    /**
     * Retourne le role qu'un auteur peut avoir selon le type de document
     * @param $typdoc
     * @return array
     */
    static public function getAuthorRoles($typdoc = 'DEFAULT')
    {
        return self::getTypdocSetting('aut_quality', $typdoc);
    }
    
    /**
     * Retourne le role qu'un auteur peut avoir selon le type de document
     * @return array
     */
    static public function getAuthorRolesTradCodes()
    {
        $authRoles = Hal_Settings::getAuthorRoles();
        $tradCodes = array();
        
        foreach ($authRoles as $role) {
            $tradCodes[$role] = 'relator_' . $role;
        }
        return $tradCodes;
    }
    
    /**
     * Affichage de la boite permettant d'indiquer l'origine des fichiers déposés
     * @param $typdoc
     * @return boolean
     */
    static public function showOriginFileBox($typdoc)
    {
        return self::getTypdocSetting('fileOrigin', $typdoc);
    }

    /**
     * Affichage de la boite permettant d'indiquer la licence sur les fichiers
     * @param $typdoc
     * @return boolean
     */
    static public function showLicenceBox($typdoc)
    {
        return self::getTypdocSetting('licence', $typdoc);
    }

    /**
     * Indique si la licence est obligatoire pour ce type de document
     * @param $typdoc
     * @return boolean
     */
    static public function requiredLicence($typdoc)
    {
        return self::getTypdocSetting('requiredLicence', $typdoc);
    }


    /**
     * Indique si un fichier peut être le fichier principal d'un document en focntion de son extension
     * @param $typdoc
     * @param $extension
     * @return boolean
     */
    static public function isMainFileType($typdoc, $extension)
    {
        $extension = strtolower($extension);
        return in_array($extension, self::getMainFileType($typdoc));
    }

    /**
     * Indique si un fichier peut être le fichier principal d'un document en focntion de son extension
     * @param $extension
     * @return boolean
     */
    static public function canBeMainFile($extension)
    {
        foreach (array_keys(self::$_configTypdoc) as $type) {
            if (self::isMainFileType($type, $extension)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string[]
     */
    static public function getKnownLicences()
    {
        return array_keys(self::$_licences);
    }

    /**
     * @param $licenceid
     * @return array
     */
    static public function getLicenceInfos($licenceid)
    {
        return self::$_licences[$licenceid];
    }

    /**
     * Retourne la licence sélectionnée
     * @param integer
     * @return array
     */
    static public function getLicence($id)
    {
    	return self::getLicenceInfos($id);
    }
    
    /**
     * Retourne les code de traduction pour les différentes licences
     * Attention ces codes continennent les icones associées (CC, BY, etc)
     * @return array
     */
    static public function getLicencesTradCodes()
    {
        $tradCode = array();

        $tradCode[''] = '';

        foreach (self::getKnownLicences() as $licence) {
            $tradCode[$licence] = 'selectlicence_' . $licence;
        }

        return $tradCode;
    }

    /**
     * Méthode qui retourne le préfixe d'un identfiiant de l'archive (dépend du portail)
     *
     * @param int $sid
     * @param string $typdoc Type de dépôt
     * @return string
     */
    static public function getDocumentPrefix($sid = SITEID, $typdoc='UNDEFINED')
    {
        if ( in_array($typdoc, array('THESE', 'HDR')) ) {
            return 'tel-';
        }
        /*if ( in_array($typdoc, array('IMG', 'MAP', 'SON', 'VIDEO')) ) {
            return 'medihal-';
        }
        if ( $typdoc == 'LECTURE' ) {
            return 'cel-';
        }*/

        $site = Hal_Site::loadSiteFromId($sid);
        $id = $site->getId();

        if ( !empty($id) ) {
            return $id;
        } else {
            return 'hal-';
        }
    }

    /**
     * Indique si les notices doivent être validée techniquement par les valideurs techniques
     *
     * @return boolean
     */
    static public function validNotice()
    {
        return static::getSetting('validNotice');
    }

    /**
     * Indique si les dépôts sont forcés en vue simplifiée/détaillée pour le portail
     *
     * @return int
     */
    static public function submitMode()
    {
        //Vérification de la présence du paramétrage spécifique dans la conf du portail
        $iniSetting = self::getIniSetting('submit.ini', 'submitMode');

        if ($iniSetting !== false) {
            return $iniSetting;
        }
        return null;
    }

    /**
     * Indique si on doit afficher les droits de l'auteur pour le premier dépot dans le portail
     *
     * @return bool
     */
    static public function seeLegal()
    {
        //Lecture du paramétrage spécifique dans la conf du portail
        return self::getIniSetting('submit.ini', 'seeLegal');
    }

    /**
     * @param        $setting
     * @param string $typdoc
     * @return array|bool|string
     */
    static protected function getSetting($setting, $typdoc = 'DEFAULT')
    {
        //Vérification de la présence du paramétrage spécifique dans la conf du portail
        $iniSetting = self::getIniSetting('submit.ini', $setting);

        if ($iniSetting !== false) {
            return $iniSetting;
        }
        return self::getTypdocSetting($setting, $typdoc);
    }

    /**
     * Récupération de l'expéditeur d'un mail
     *
     * @return string
     */
    static public function getMailFrom()
    {
        $iniSetting = self::getIniSetting('config.ini', 'mailFrom');
        if ($iniSetting !== false) {
            return $iniSetting;
        }
        return self::MAIL_FROM;
    }

    /**
     * Retourne la liste des métadonnées du coeur de HAL
     *
     * @return array
     */
    static public function getCoreMetas()
    {
        if (self::$_coreMeta == []) {

            $out = array();
            $ini = new Zend_Config_Ini(DEFAULT_CONFIG_ROOT . Hal_Site_Portail::MODULE . '/meta.ini', 'metas');
            foreach ($ini->elements as $meta => $options) {
                if ($options->type != 'hr' && !in_array($meta, $out)) {
                    $out[] = $meta;
                }
            }
            self::$_coreMeta = $out;
        }
        return self::$_coreMeta;
    }

    /**
     * Retourne la liste des métadonnées
     * @param string $typdoc
     * @return array
     * @throws Zend_Config_Exception
     */
    static public function getMeta($typdoc)

    {
        $site = Hal_Site_Portail::getCurrentPortail();

        $sid = $site->getSid();
        if (array_key_exists($sid, self::$iniFilesCache)
            && array_key_exists($typdoc, self::$iniFilesCache[$sid])) {
            return self::$iniFilesCache[$sid][$typdoc];
        }
        $configDir = $site->getConfigDir();
        /*
         * Chargement de la configuration avec les .ini correspondants
         */
        $chemin1 = DEFAULT_CONFIG_ROOT . Hal_Site_Portail::MODULE . '/' . 'meta.ini';
        $chemin2 = $configDir . 'meta.ini';

        // Recherche de toutes les metas
        $metasSpec =  Hal_Ini::file_merge([$chemin1 => [$typdoc], $chemin2 => [$typdoc]], ['section_default' => 'metas']);
        return $metasSpec;
    }

    /**
     * Retourne la liste des métadonnées
     *
     * @return array
     * echo "<?php return array(" > default/cache/metas.phps ; for i in `find . -name meta.ini`;do grep elements.*.label $i | awk 'BEGIN{FS="."}{printf "\"%s\",\n", $2}' ;  done | sort | uniq >> default/cache/metas.phps ; echo ");" >> default/cache/metas.phps
     */
    static public function getMetas()
    {
        $cacheFile = Hal_Site_Portail::DEFAULT_CACHE_PATH . '/metas.phps';
        if ( is_file( $cacheFile) ) {
            ob_start();
            $metas = include($cacheFile);
            ob_end_clean();
            if (is_array($metas)) {
                return $metas;
            }
        }
        return [];
    }

    /**
     * Retourne la liste des métadonnées multivaluées
     *
     * @return array
     */
    static public function getMultiValuedMetas()
    {
        return array('enpc_secondaryResp', 'abstract', 'acm', 'afssa_thematique', 'anrProject', 'authorityInstitution', 'bioemco_team', 'brgm_team', 'brgm_thematique', 'collaboration', 'committee', 'conferenceOrganizer', 'director', 'domain', 'europeanProject', 'funding', 'hcl_team', 'hcl_thematique', 'identifier', 'jel', 'keyword', 'localReference', 'mesh', 'pastel_library', 'pastel_thematique', 'publisher', 'scientificEditor', 'seeAlso', 'seriesEditor', 'subTitle', 'tematice_discipline', 'tematice_levelTraining', 'tematice_studyField', 'thesisSchool', 'title');
    }

    /**
     * Retourne la liste des métadonnées multilangue
     *
     * @return array
     */
    static public function getMultiLanguageMetas()
    {
        return array('title', 'subTitle', 'abstract', 'keyword');
    }

    /**
     * Retourne la liste des métadonnées multilangue
     *
     * @param string $meta : metadonnée
     * @return bool
     */
    static public function isMultiLanguageMetas($meta)
    {
        return in_array($meta, self::getMultiLanguageMetas());
    }

    /**
     * Retourne la liste des métadonnées de type thesaurus
     *
     * @return array
     */
    static public function getThesaurusMetas()
    {
        return array('domain', 'acm', 'acm2012', 'jel', 'domain_inter');
    }

    /**
     * Retourne le nombre d'auteurs devant être affiliés pour un dépôt
     * @param string $format
     * @param string $typdoc
     * @param string $producedDate
     * @return int retourne all pour tous les auteurs et 1 pour au moins 1 auteur
     */
    static public function getNbAffiliatedAuthors($format, $typdoc, $producedDate)
    {
        //Config pour le portail
        $iniSetting = self::getIniSetting('submit.ini', 'nbAffiliatedAuthors');
        if ($iniSetting !== false) {
            return (int)$iniSetting;
        }
        //Config pour le type de document
        $iniSetting = self::getTypdocSetting('nbAffiliatedAuthors', $typdoc);
        if ($iniSetting !== false) {
            return (int)$iniSetting;
        }

        return 1;
    }

    /**
     * Retourne le chemin d'un fichier de conf pour un portail ou une collection
     * dépend de l'environnement en cours : MODULE et SPACE_NAME
     * @param string $file
     * @param string $spaceName
     * @return string|boolean
     */
    static function getConfigFilePath ($file, $spaceName = SPACE_NAME)
    {
        $configPath = SPACE_DATA . '/'. MODULE . '/' . $spaceName . '/' . CONFIG . $file;

        if (is_readable($configPath)) {
            return $configPath;
        } else {
            $configPath = DEFAULT_CONFIG_ROOT .SPACE_PORTAIL. '/' . $file;
            if (is_readable($configPath)) {
                return $configPath;
            } else {
                return false;
            }
        }
    }

    /**
     * Lit un fichier de conf, stocke en registry le résultat pour accès
     * ultérieur éventuel, retourne la conf sous forme de tableau
     * dépend de l'environnement en cours : MODULE et SPACE_NAME
     *
     * @param string $file
     * @param string $type
     * @param string $spaceName
     * @param boolean $useRegistry
     * @return boolean|mixed|Ambigous <multitype:, multitype:multitype: Zend_Config >
     */
    static function getConfigFile ($file = null, $type = 'json', $spaceName = SPACE_NAME, $useRegistry = true)
    {
        if ($file == null) {
            return false;
        }

        if ($useRegistry) {
            try {
                $configSolr = Zend_Registry::get($file);
                return $configSolr;
            } catch (Zend_Exception $e) {

            }
        }

        $filePath = self::getConfigFilePath($file, $spaceName);
        
        if ($filePath == false) {
            return false;
        }
        switch ($type) {
            case 'json':
                $configSolr = new Zend_Config_Json($filePath, null, array('ignoreconstants' => true));
                break;
            case 'ini':
                $configSolr = new Zend_Config_Ini($filePath);
                break;
        }
        if ($configSolr instanceof Zend_Config) {
            $configSolrArr = $configSolr->toArray();
        } else {
            $configSolrArr = array();
        }
        Zend_Registry::set($file, $configSolrArr);

        return $configSolrArr;
    }

    /**
     * @return array
     */
    static function getDcRelation()
    {
        return Hal_Referentiels_Metadata::getValues('relatedType');
    }

    /**
     * @param $typdoc
     * @return string
     */
    static public function getLabelClassName($typdoc)
    {
        $class = 'label label-' . $typdoc . ' ';
        if (in_array($typdoc, array('ART','COMM','POSTER','OUV','COUV','DOUV', 'PATENT'))) {
            $class .= 'label-danger';
        } else if (in_array($typdoc, array('THESE','HDR','LECTURE','ETABTHESE'))) {
            $class .= 'label-primary';
        } else if (in_array($typdoc, array('IMG','SON','VIDEO','MAP'))) {
            $class .= 'label-success';
        } else {
            $class .= 'label-warning';
        }
        return $class;
    }

    /**
     * Retourne la version de l'application
     * @return string
     */
    public static function getApplicationVersion() {
        if (self::$_appversion) {
            return self::$_appversion;
        }
        $file = APPROOT . '/application/configs/application.ini';
        $gitfile = APPROOT . '/.git/index';
        if (file_exists($gitfile)) {
            $file = $gitfile;
        }
        $stat = stat($file);
        $date = $stat['mtime'];
        return "$date";
    }

    /**
     * Return true if we must try to use Grobid to get extra metadata
     * @return bool
     */
    public static function usegrobid4meta() {
        if (defined('USEGROBID4META') && (USEGROBID4META === false)) {
            return false;
        }
        // By default, we use Grobid: compatibility
        return true;
    }
    /**
     * Return true if we must try to use Doi and CrossRef to get extra metadata with Doi
     * @return bool
     */
    public static function usecrossref4meta() {
        if (defined('USECROSSREF4META') && (USECROSSREF4META === false)) {
            return false;
        }
        // By default, we use Crossref: compatibility
        return true;
    }
    /**
     * Return true if we must show popup with result message of process uploaded file (convert and meta)
     * @return bool
     */
    public static function showUploadReturnMsg() {
        if (defined('SHOWUPLOADRETURNMSG') && (SHOWUPLOADRETURNMSG == false)) {
            return false;
        }
        // By default, we use Crossref: compatibility
        return true;
    }
}
