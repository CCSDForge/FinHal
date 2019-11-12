<?php

/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 08/03/2017
 * Time: 15:05
 */
class Hal_Submit
{

    /**
     * Traiement post enregistrement d'un document
     * @param $uid
     * @param Hal_Document $document
     */
    static public function postSave($uid, Hal_Document $document)
    {
        /**
         * Dépôt de thèses par l'ABES (mise en ligne au moment du dépôt)
         */
        if ($uid == 131274 && $document->getTypDoc() == 'THESE' && $document->getFormat() == Hal_Document::FORMAT_FILE) {
            $document->putOnline($uid);
        }
        // Temporaire du 2 ou 7 mai 2018
        //if ($uid == 108776 && $document->getFormat() == Hal_Document::FORMAT_FILE && $document->getSid() == 25) {
        //     $document->putOnline($uid);
        //}
    }
}