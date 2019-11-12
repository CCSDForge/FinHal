<?php

class Hal_Tools
{

    static $preflang = ['fr', 'en'];

    /**
     * Curl sur le serveur de requêtes de solr
     * Exemple : Hal_Tools::solrCurl('q=*:*&wt=json','ref_journal');
     *
     * @see /library/Ccsd/Search/Solr/configs/endpoints.ini
     * @param string $queryString requête du type q=docid:19
     * @param string $core solr core par défaut hal
     * @param string $handler handler solr par défaut select
     * @param boolean $addDefaultFilters par défaut false
     * @param boolean
     * @throws Exception
     * @return mixed string boolean du GET ou curl_error()
     */
    public static function solrCurl($queryString, $core = 'hal', $handler = 'select', $addDefaultFilters = false, $replace = false)
    {
        if ($addDefaultFilters) {
            //Ajout des filtres par defaut de l'environnement
            $queryString .= Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json'), $replace);
        }

        return Ccsd_Tools::solrCurl($queryString, $core, $handler);
    }

    public static function encryptMail($mail, $text = '', $class = '', $style = '')
    {
        $class = ($class != "") ? 'class="' . $class . '" ' : '';
        $id = uniqid('link');
        $style = ($style != "") ? 'style="' . $style . '" ' : '';

        $return = '<a href="" id="' . $id . '" ' . $class . $style . '></a>';
        $return .= '<script type="text/javascript" language="javascript"> decryptMail("' . str_rot13($mail) . '", "' . $id . '", "' . $text . '")</script>';

        return $return;
    }

    /**
     * @return mixed
     * TODO Devrait etre dans l'objet/class  Website
     */
    public static function getLanguages()
    {
        return Zend_Registry::get('website')->getLanguages();
    }

    /**
     * Retourne un tableau [codeLangue => libelleLangue] pour les langues acceptees sur le site
     *
     * @return array
     *
     * TODO Devrait passe par l'objet/class  Website pour ne pas faire appel a Zend_Registry::get('website')
     */
    public static function getLangWebsite()
    {
        return array_intersect_key(Ccsd_Locale::getLanguage(), array_flip(Zend_Registry::get('website')->getLanguages()));
    }

    /**
     * @param $content : mixte
     * @param null $lang string
     * @param null $preflang string[]
     * @return string
     *
     * $content Pour un contenu tableau contenant une entree par langue
     *          Peut aussi etre un object qui implemente la methode getlang (pas d'interface...)
     * Valeur de retour: le contenu correspondant a la meilleure langue possible
     * Si une langue explicite est demandee elle sera retournee si elle existe
     * Si pas de langue demande, on utilisera Zend_Registry::get('lang')
     * Si la langue demande n'existe pas, on utilisera le tableau optionnel $preflang pour trouver la meilleure
     * Si rien ne marche, on prendra la premiere langue non vide
     *
     * Attention: Les valeurs sont des chaines (la methode getlang doit retourner une chaine, pas un objet correspondant a une version linguistique...
     *
     */

    public static function getbylang($content, $lang = null, $preflang = null)
    {

        if (is_string($content)) {
            return $content;
        }
        if ($lang == null) {
            $lang = Zend_Registry::get('lang');
        }
        if ($preflang == null) {
            $preflang = self::$preflang;
        }
        array_unshift($preflang, $lang);
        if (is_array($content)) {
            foreach ($preflang as $lang) {
                if ((isset($content[$lang])) && ($content[$lang] != '')) {
                    return $content[$lang];
                }
            }
            foreach ($content as $lang => $langContent) {
                if ($langContent != '') {
                    return $langContent;
                }
            }
        }
        if (method_exists($content, 'getlang')) {
            foreach ($preflang as $lang) {
                $res = $content->getlang($lang);
                if ($res != '') {
                    return $res;
                }
            }
            foreach ($content as $lang => $langContent) {
                $res = $content->getlang($lang);
                if ($res != '') {
                    return $res;
                }
            }
        }
        return '';
    }


    /**
     * Filtres les chaînes de caractères
     * @param $string string
     */
    public static function stringFilter($string)
    {
        $string = htmlspecialchars($string); //Convertit les caractères spéciaux en entités HTML
        $string = trim($string); //Enlève les espaces avant et devant le chaîne

        return $string;
    }
}

