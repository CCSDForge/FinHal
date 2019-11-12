<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 10/08/17
 * Time: 11:32
 */
class Hal_PubMedCentral
{
    const PMC = 'pubmedcentral';
    const PM = 'pubmed';

    const ERROR_ONLINE  = 'alreadyonline';
    const ERROR_NOID    = 'noid';
    const ERROR_NODOC   = 'nodoc';
    const ERROR_INVALIDTYPE  = 'invalidtype';
    const ERROR_LANG  = 'noeng';
    const ERROR_FILEFORMAT  = 'fileformat';
    const ERROR_DOMAIN  = 'domain';


    /**
     * Indique si un document peut être envoyé sur pubmed central
     * @param Hal_Document $document
     * @return array
     */
    static public function canTransfert($document)
    {

        $error = [];

        if ($document->getIdsCopy(self::PMC) != '') {
            //Le document a déja un identifiant PMC
            $error[] = self::ERROR_ONLINE;
        }

        if ($document->getIdsCopy(self::PM) == '') {
            //Le document n'a pas d'identifiant PubMed
            $error[] = self::ERROR_NOID;
        }

        if ($document->getIdsCopy(self::PM) != '' && Halms_Tools::isPMC($document->getIdsCopy(self::PM)) ) {
            //Le document a un identifiant PubMed et est sur PubMed Central
            $error[] = self::ERROR_ONLINE;
        }

        $document->initFormat();
        if ($document->getFormat() != Hal_Document::FORMAT_FILE) {
            //Le document est une notice
            $error[] = self::ERROR_NODOC;
        }

        if (! Hal_Settings::getTypdocSetting(self::PM, $document->getTypDoc())) {
            //Type de dépôt incompatible
            $error[] = self::ERROR_INVALIDTYPE;
        }

        if ($document->getMeta('language') != 'en') {
            //Langue différente d'anglais
            $error[] = self::ERROR_LANG;
        }

        $mainFile = $document->getDefaultFile();
        if ($mainFile && $mainFile->getOrigin() != Hal_Settings::FILE_SOURCE_AUTHOR) {
            //le fichier principal n'est pas un fichier auteur
            $error[] = self::ERROR_FILEFORMAT;
        }

        $domain = false;

        foreach($document->getDomains() as $domain) {
            if (substr($domain, 0, 3) == 'sdv') {
                $domain = true;
            }
        }

        if (!$domain) {
            //Il n'y a pas de domain en SDV
            $error[] = self::ERROR_DOMAIN;
        }

        return $error;
    }

    /**
     * @param Hal_Document $document
     * @return bool
     */
    static public function canShowTransfert(Hal_Document $document)
    {
        $document->initFormat();
        if ($document->getFormat() != Hal_Document::FORMAT_FILE) {
            //Le document est une notice
            return false;
        }

        if (! Hal_Settings::getTypdocSetting(self::PM, $document->getTypDoc())) {
            //Type de dépôt incompatible
            return false;
        }

        return true;
    }
}