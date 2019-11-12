<?php

/**
 * Définition des droits d'accès aux documents
 * Class Hal_Document_Acl
 */
class Hal_Document_Acl
{

    /**
     * Indique si le dépôt a été déposé par l'utilisateur connecté
     * @param Hal_Document $document
     * @return bool
     */
    static public function isContributor($document)
    {
        return Hal_Auth::getUid() == $document->getContributor('uid');
    }

    /**
     * Indique si l'utilisateur connecté est propriétaire du dépôt
     * @param Hal_Document $document
     * @return bool
     */
    static public function isOwner($document)
    {
        return self::isContributor($document) || in_array(Hal_Auth::getUid(), $document->getOwner());
    }

    /**
     * Indique si l'utilisateur connecté peut accéder à un document
     * @param Hal_Document $document
     * @return boolean
     */
    static public function canView($document)
    {
        if (self::isOwner($document) || Hal_Auth::isHALAdministrator() || Hal_Auth::isAdministrator() || Hal_Auth::isAdminStruct($document->getStructids()) || Hal_Auth::isModerateur() || Hal_Auth::isValidateur() ) {
            return true;
        }

        return false;

    }

    /**
     * Indique si l'utilisateur connecté peut modifier le dépôt (demande de modification)
     * @param Hal_Document $document
     * @return bool
     */
    static public function canModify($document)
    {
        if ($document->getDocid() == 0) {
            //Le document demandé n'existe pas
            return false;
        }
        if ( Hal_Auth::isHALAdministrator() ) {
            return true;
        }
        if ( Hal_Auth::isAdministrator() ) {
            return true;
        }
        if ($document->getStatus() != Hal_Document::STATUS_MODIFICATION && $document->getStatus() != Hal_Document::STATUS_MYSPACE) {
            //Le document n'est pas en demande de modifications
            return false;
        }
        //L'utilisateur courant doit être le contributeur
        return self::isOwner($document);

    }

    /**
     * Personnes autorisées à modifier / ajouter une référence
     * @param $document
     * @return bool
     */
    static public function canModifyReference($document)
    {
        if ($document->getDocid() == 0) {
            //Le document demandé n'existe pas
            return false;
        }

        return Hal_Auth::isHALAdministrator()  || Hal_Auth::isAdministrator() || self::isOwner($document);
    }

    /**
     * Indique si l'utilisateur connecté peut modifier les métadonnées
     * @param Hal_Document $document
     * @param string $pwd
     * @param bool $bSubmitAllowed : indicateur portail avec ou sans dépôt autorisé
     * @return bool
     */
    static public function canUpdate($document, $pwd = '', $bSubmitAllowed = true)
    {
        if ($document->getDocid() == 0) {
            //Le document demandé n'existe pas
            return false;
        }
        if ( Hal_Auth::isHALAdministrator() ) {
            return true;
        }
        if (! $document->isVisible()) {
            //Le document est en ligne
            return false;
        }
        //pas de droit pour les portails sans dépôt
        if (!$bSubmitAllowed) {
            return false;
        }
        if (self::isOwner($document) || Hal_Auth::isAdministrator() || $document->getPwd() == $pwd ) {
            //L'utilisateur connecté est le déposant ou administrateur ou il a le mot de passe
            return true;
        }
        if ( Hal_Auth::isAdminStruct($document->getStructids()) ) {
            //L'utilisateur connecté est référent pour une structure
            return true;
        }
        if ( Hal_Auth::getUid() == 131274 && $document->getTypDoc() == 'THESE' ) {
            //Droit en update pour STAR
            return true;
        }
        return false;
    }

    /**
     * Indique si l'utilisateur connecté peut déposer une nouvelle version
     * @param Hal_Document $document
     * @param string $pwd
     * @return bool
     */
    static public function canReplace($document, $pwd = '', $bSubmitAllowed = true)
    {
        if ($document->getDocid() == 0) {
            //Le document demandé n'existe pas
            return false;
        }
        if (! $document->isFulltext()) {
            //Pas de nouvelle version sur les notices
            return false;
        }
        if ($document->isVersionsNonDispos()) {
            //Il existe une nouvelle version en cours de modération
            return false;
        }

        if ($document->getStatus() != Hal_Document::STATUS_VISIBLE) {
            //Le document n'est pas dans le statut "En ligne"
            return false;
        }
        //pas de droit pour les portails sans dépôt
        if (!$bSubmitAllowed) {
            return false;
        }
        if (self::isOwner($document) || Hal_Auth::isHALAdministrator() || Hal_Auth::isAdministrator()||
            $document->getPwd() == $pwd ) {
            //L'utilisateur connecté est le déposant ou administrateur ou il a le mot de passe
            return true;
        }
        //On vérifie si l'utilisateur connecté est référent pour une structure
        return Hal_Auth::isAdminStruct($document->getStructids());
    }

    /**
     * Indique si l'utilisateur connecté peut supprimer le dépôt
     * @param Hal_Document $document
     * @return bool
     */
    static public function canDelete($document, $bSubmitAllowed = true)
    {
        if ($document->getDocid() == 0) {
            //Le document demandé n'existe pas
            return false;
        }
        if ($document->isVisible()) {
            if ($document->isFulltext()) {
                return Hal_Auth::isHALAdministrator();
            }
        }
        //pas de droit pour les portails sans dépôt
        if (!$bSubmitAllowed) {
            return false;
        }
        return self::isOwner($document) || Hal_Auth::isHALAdministrator() || Hal_Auth::isAdminStruct($document->getStructids());
    }

    /**
     * Indique si l'utilisateur peut refuser la propriété du dépot
     * @param Hal_Document $document
     * @return bool
     */
    static public function canUnshare($document)
    {
        if ($document->getDocid() == 0) {
            //Le document demandé n'existe pas
            return false;
        }

        // L'utilisateur doit être propriétaire mais non déposant && le document doit être en ligne
        return !(Hal_Document_Acl::isContributor($document)) && $document->isOnline() ;
    }

    /**
     * @param Hal_Document $document
     * @return bool
     */
    static public function canViewHistory($document)
    {
        if ($document->getDocid() == 0) {
            //Le document demandé n'existe pas
            return false;
        }
        return self::isOwner($document) || Hal_Auth::isAdministrator() || Hal_Auth::isModerateur() || Hal_Auth::isValidateur() || Hal_Auth::isAdminStruct($document->getStructids());
    }
}