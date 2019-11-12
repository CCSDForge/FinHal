<?php

/**
 * Plugin de traduction pour HAL
 *
 */
class Hal_Translation_Plugin extends Zend_Controller_Plugin_Abstract
{

    /** Todo: List of langages must be elsewhere... No need to know the list here... */
    /** @deprecated */
    const LANG_FR = 'fr';

    /**
     * Essaie de trouver une langue disponible d'après :
     * 1 - Une URL en paramètre
     * 2 - la session
     * 3 - La langue envoyée par la navigateur
     * 4 - La langue par défaut
     * @param Zend_Controller_Request_Http $request
     */
    public function dispatchLoopStartup(\Zend_Controller_Request_Abstract $request)
    {
        //Initialisation des langues de l'interface
        // SITE Doit deja etre defini!!! Donc apres Hal_Plugin
        self::initLanguages();

        $locale = $this->getHalLocale($request);

        $translator = static::checkTranslator($locale);

        if (defined('LOG_UNTRANSLATED_STRING') && (LOG_UNTRANSLATED_STRING===True)) {
            $translator = $this->logUntranslatedStrings($translator);
        }

        Zend_Registry::set('Zend_Translate', $translator);
        Zend_Registry::set('Zend_Locale', new Zend_Locale($locale));
        Zend_Registry::set('lang', $locale);

        if (APPLICATION_DIR != 'application-api') {
            $localeSession = new Zend_Session_Namespace(SESSION_NAMESPACE);
            $localeSession->lang = $locale;
        }

        // collation locale
        $this->setHalCollation($locale);
    }

    /**
     * Initialisation des langues disponibles de l'interface
     * Public for testing
     */
    public static function initLanguages()
    {
        if (!Zend_Registry::isRegistered('languages')) {
            $languages = [];
            //Récupération des langues du site
            if (defined('SITEID')) {
                $website = Hal_Site::loadSiteFromId(SITEID);
                if ($website !== null) {
                    $languages = $website->getLanguages();
                }
            }
            if (count($languages) == 0) {
                $languages = static::getAvalaibleLanguages();
            }
            Zend_Registry::set('languages', $languages);
        }
    }

    /**
     * Retourne les langues disponibles de la plateforme
     * @return array
     */
    public static function getAvalaibleLanguages()
    {
        return Hal_Settings::getLanguages();
    }

    /**
     * Retourne la locale à utiliser pour les traductions
     * @param Zend_Controller_Request_Http $request
     * @return Zend_Locale
     */
    private function getHalLocale(Zend_Controller_Request_Http $request)
    {

        // langue dans URL
        $locale = $this->getLocaleByUrl($request);

        // langue en session sauf pour l'API
        if (($locale == null) && (APPLICATION_DIR != 'application-api')) {
            $locale = $this->getLocaleBySession();
        }

        // langue du browser
        if ($locale == null) {
            $locale = $this->getLocaleByBrowser();
        }

        // sinon langue par default
        if ($locale == null) {
            $locale = current(Zend_Registry::get('languages'));
        }
        return $locale;
    }

    /**
     * Retourne la langue en fonction du paramètre dans l'URL
     *
     * @param Zend_Controller_Request_Http $request
     * @return null|Zend_Translate_Adapter
     */
    private function getLocaleByUrl(Zend_Controller_Request_Http $request)
    {
        $lang = null;
        if ($request->isPost()) {
            $lang = $request->getPost('lang');
        }
        if ($lang == null) {
            $lang = $request->getParam('lang', null);
        }

        if ($this->isLanguageAllowed($lang) === false) {
            $lang = null;
        }

        return $lang;
    }

    /**
     * Vérifie si la langue fait partie des langues de l'application
     * @param string $lang
     * @return boolean
     */
    private function isLanguageAllowed($lang)
    {
        if (in_array($lang, Zend_Registry::get('languages'))) {
            return true;
        }
        return false;
    }

    /**
     * Retourne la langue en fonction de la session
     *
     * @return NULL|Zend_Translate
     */
    private function getLocaleBySession()
    {
        $localeSession = new Zend_Session_Namespace(SESSION_NAMESPACE);
        $lang = $localeSession->lang;
        if ($this->isLanguageAllowed($lang) === false) {
            $lang = null;
        }
        return $lang;
    }

    /**
     * Retourne la langue en fonction du navigateur
     *
     * @return NULL | string
     */
    private function getLocaleByBrowser()
    {
        try {
            $browserLocale = new Zend_Locale(Zend_Locale::BROWSER);
            $lang = $browserLocale->getLanguage();


            if ($this->isLanguageAllowed($lang) === false) {
                $lang = null;
            }
        } catch (Zend_Locale_Exception $e) {
            $lang = null;
        }

        return $lang;
    }

    /**
     * @param string $lang : Code for locale fr,en,...
     */
    static public function setTranslation($lang) {
        $translator = Hal_Translation_Plugin::checkTranslator($lang);
        $locale = $translator->getLocale();
        $localeSession = new Hal_Session_Namespace(SESSION_NAMESPACE);
        $localeSession->lang = $locale;

        Zend_Registry::set('lang', $locale);
        Zend_Registry::set('Zend_Translate', $translator);
        Zend_Registry::set('Zend_Locale', new Zend_Locale($locale));
    }
    /**
     * Ajoute une traduction si la langue existe
     *
     * @param string $language
     * @return null|Hal_Translate
     */
    static function checkTranslator($language = null)
    {
        if ($language == null) {
            return null;
        }
        if (Zend_Registry::isRegistered('Zend_Translate')) {
            // Definit au moment du bootstrap
            // Il ne reste plus qu'a ajouter le specifique site.
            $translator = Zend_Registry::get('Zend_Translate');
        } else {
            // Todo: Supprimer cela en faisant en sorte que toutes les applications definissent l'objet Translator en Bootstrap.
            /** @var Hal_Translate $translator */
            $translator = new Zend_Translate(Zend_Translate::AN_ARRAY, PATH_TRANSLATION, null, array(
                'scan'           => Zend_Translate::LOCALE_DIRECTORY,
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
            
            Zend_Registry::set('Zend_Translate', $translator);
        }

        // Traduction des métadonnées définies dans le code de l'application
        //  de type PORTAIL ou COLLECTION
        // Note: DEFAULT_CONFIG_PATH depends du module utilise, determine seulement au momment du routage
        if (defined('DEFAULT_CONFIG_PATH') && is_dir(DEFAULT_CONFIG_PATH . '/' . LANGUAGES)) {
            $translator->addTranslation(DEFAULT_CONFIG_PATH . '/' . LANGUAGES);
        }
        // Specifique du SITE ou de la COLLECTION
        if (defined('SPACE') && is_dir(SPACE . 'languages') && count(scandir(SPACE . 'languages')) > 2) {
            $translator->addTranslation(SPACE . 'languages');
        }

        Ccsd_Translator::addTranslations($translator);

        if ($translator->isAvailable($language)) {
            $translator->setLocale($language);
            return $translator;
        } else {
            return null;
        }
    }

    /**
     * log des chaines non traduites
     * @param Zend_Translate_Adapter $translator
     * @return Zend_Translate_Adapter
     */
    private function logUntranslatedStrings($translator)
    {

        if ($translator->getLocale() != static::LANG_FR) {

            $writer = new Zend_Log_Writer_Stream(realpath(sys_get_temp_dir()) . '/traductionsManquantes_' . $translator->getLocale() . '.log');
            $log = new Zend_Log($writer);

            $translator->setOptions(array(
                'log' => $log,
                'logMessage' => "Locale %locale% - manque : '%message%'",
                'logUntranslated' => true
            ));
        }
        return $translator;

        /**
         * log des chaines non traduites //
         */
    }

    /**
     * Set Locale Collation
     * @param string $locale
     * @return string
     */
    private function setHalCollation($locale)
    {
        $languageTags = Hal_Settings::getLanguageTag($locale);
        return setlocale(LC_COLLATE, $languageTags);
    }

}
