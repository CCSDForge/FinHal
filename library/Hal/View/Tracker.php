<?php

namespace Hal\View;

use phpDocumentor\Reflection\Types\Boolean;

/**
 * Interface Tracker
 * @package Hal\View
 *
 * Interface qui sera implémenté par les classes des Trackers (Tarteaucitron.php, Matomo.php, Atinternet.php,...)
 */

interface Tracker
{
//  Méthode qui ajoute les lignes du code au Header de layout.phtml
// pour rendre possible l'utilisation de Tracker.
    public function addHeader();
//  Méthode qui ajout les lignes du code au Body de layout.phtml
//  pour faire le traçage des pages.
    public function getHeader(): string;
//  Méthode pour vérifier si le traçage de la page est authorisé
    public function followHeader(): bool;
}