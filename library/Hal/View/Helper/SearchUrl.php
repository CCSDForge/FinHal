<?php
/**
 * Le helper d'url standard génère des URL incompatibles avec les elements
 * multiTextSimpleLang de la recherche avancée
 *
 * @author rtournoy
 *
 */
require_once 'Zend/View/Helper/Url.php';
class Hal_View_Helper_SearchUrl extends Hal_View_Helper_Url {

    /**
     * Certaines options sont du type: qa[bookTitle_t][]=test
     * Dans ce cas, on ne doit pas mettre qa=valeur mais bien qa[bookTitle_t][]=test
     * Le tableau d'option contient alors qa => qa[bookTitle_t][]=test.  Il suffit de mettre la valeur a la place de qa=valeur.
     *
     * @var array
     */
    protected $multioptions = [ 'qa' ];

    /**
     * @param array $urlOptions
     * @param null $name
     * @param bool $reset
     * @param bool $encode
     * @param string $searchType
     */
    public function searchUrl(array $urlOptions = array(), $name = null, $reset = false, $encode = true, $searchType = 'simple') {
        return parent::url($urlOptions, $name, $reset, $encode );
    }
}