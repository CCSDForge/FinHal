<?php

/**
 * Class Hal_Locale
 */
class Hal_Locale extends Ccsd_Locale
{
    /**
     * Retourne une liste des pays avec la possibilité d'une option vide par rapport au parent
     * @param Zend_Locale $locale langue par défaut de l'application
     * @param bool $orderList  affichage des pays les plus utilisés en tête de liste
     * @param bool $separator  affichage un séparateur entre les pays de la tête de liste et les autres
     * @return array
     */
    public static function getCountry($locale = null, $orderList = false, $separator = false)
    {
        return array_merge([''=>''], parent::getCountry($locale, $orderList, $separator));
    }
}
