<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 07/02/17
 * Time: 14:13
 */
class Hal_Submit_Step_Recap extends Hal_Submit_Step
{
    /** @var string  */
    protected $_name = "recap";

    /**
     * @param Hal_View $view
     * @param Hal_Document $document
     * @param string $type
     * @param bool $verifValidity
     * @return bool|mixed
     */
    public function initView(Hal_View &$view, Hal_Document &$document, $type, $verifValidity = false)
    {
        // Affichage du transfert verx Arxiv
        $transfertArxiv = Hal_Transfert_Arxiv::init_transfert($document);
        $view->canTransferArxiv = $transfertArxiv -> canShowTransfert();
        $view->submitArxiv = in_array($type, array(Hal_Settings::SUBMIT_MODERATE, Hal_Settings::SUBMIT_INIT, Hal_Settings::SUBMIT_ADDFILE, Hal_Settings::SUBMIT_REPLACE, Hal_Settings::SUBMIT_MODIFY)) && $document->mainFileVisible() && $view->canTransferArxiv;
        $errors = [];
        // recup seulement du pourquoi on ne peut pas deposer
        Hal_Transfert_Arxiv::canTransfert($document,$errors) ;
        $view->arxivErrors = $errors;
        $view->goToArxiv = $document->gotoArxiv();

        // Affichage du tranfert vers PMC
        $view->submitPMC = in_array($type, array(Hal_Settings::SUBMIT_MODERATE, Hal_Settings::SUBMIT_INIT, Hal_Settings::SUBMIT_ADDFILE, Hal_Settings::SUBMIT_REPLACE)) && Hal_PubMedCentral::canShowTransfert($document);
        $view->pmcErrors = Hal_PubMedCentral::canTransfert($document);

        // Affichage du tranfert vers Software Heritage
        $transfertSWH = Hal_Transfert_SoftwareHeritage::init_transfert($document);
        $view->submitSWH = in_array($type, array(Hal_Settings::SUBMIT_MODERATE, Hal_Settings::SUBMIT_INIT, Hal_Settings::SUBMIT_ADDFILE, Hal_Settings::SUBMIT_REPLACE)) && $transfertSWH ->canShowTransfert();
        $errors = [];
        // recup seulement du pourquoi on ne peut pas deposer
        Hal_Transfert_SoftwareHeritage::canTransfert($document, $errors);
        $view->swhErrors = $errors;
        if (count($view->swhErrors) == 0) {
            //Par défaut on transfere sur SH
            $view->goToSWH = true;
        }

        // Choix du texte du bouton de dépot
        switch($type) {
            case Hal_Settings::SUBMIT_REPLACE   :
                $view->btnLabel = "Déposer une nouvelle version";
                break;
            case Hal_Settings::SUBMIT_ADDFILE   :
                $view->btnLabel = "Ajouter un fichier au dépôt";
                break;
            case Hal_Settings::SUBMIT_ADDANNEX   :
                $view->btnLabel = "Ajouter un fichier au dépôt";
                break;
            case Hal_Settings::SUBMIT_UPDATE :
            case Hal_Settings::SUBMIT_MODIFY :
                $view->btnLabel = "Modifier le dépôt";
                break;
            case Hal_Settings::SUBMIT_MODERATE  :
                $view->btnLabel = "Enregistrer";
                break;
            default :
                $view->btnLabel = "Déposer";
                break;
        }

        // Format du document déposé
        $view->format = $document->getFormat();
        $view->docstatus = $document->getStatus();
        $view->type = $type;
        $view->typdocLabel = "typdoc_".$document->getTypDoc();

        // Recherche de doublon du document sur les identifiants
        $doublons = array_keys(Hal_Document_Doublonresearcher::doublon($document));

        // On considère qu'un doublon trouvé sur les identifiants est plus probablement un doublon qu'un doublon sur le titre
        if (count($doublons)) {
            $view->doublonID = $doublons[0];
            $doublonDoc = Hal_Document::find(0, $view->doublonID);
            $view->doublonCit = $doublonDoc->getCitation('full');
        } else {
            // Recherche de doublon sur le titre
            $titleDoublons = Hal_Document_Doublonresearcher::getDoublonsOnTitle($document);
            if (count($titleDoublons)) {
                $view->doublonID = $titleDoublons[0]['docid'];
                $view->doublonCit = $titleDoublons[0]['citationFull_s'];
            }
        }

        // Validité de l'étape
        $view->valid = $this->_validity;
        $view->citation = $this->_validity ? $document->getCitation('full', true) : "";
        $view->error = (!$this->_validity && $verifValidity) ? "Votre dépôt n'est pas encore complet !" : "";

        //CGU spécifiques pour le portail SPM
        $oInstance = Hal_Instance::getInstance('');
        if ($oInstance->getName() == 'halspm') {
            $view->cgu = 'En déposant ce document, vous concédez au « réutilisateur » un droit non exclusif et gratuit de libre « réutilisation » de l’« information » 
                à des fins commerciales ou non, dans le monde entier et pour une durée illimitée, dans les conditions exprimées par la licence ouverte/open licence version 2.0. 
                '."<br>".'Pour en savoir plus sur la licence ouverte : <a href="https://www.etalab.gouv.fr/wp-content/uploads/2017/04/ETALAB-Licence-Ouverte-v2.0.pdf" target = "blank">Licence Ouverte </a>';
        }

        return true;
    }

    /**
     * @param Hal_Document $document
     * @param string       $type
     * @param array        $params
     */
    public function submit(Hal_Document &$document, $type, $params)
    {
        if (isset($params['related']['IDENTIFIANT']) && is_array($params['related']['IDENTIFIANT'])) {
            $related = array();
            foreach($params['related']['IDENTIFIANT'] as $i => $identifiant) {
                if (trim($identifiant) != '') {
                    $related[] = array(
                        'IDENTIFIANT' => $identifiant,
                        'RELATION' => $params['related']['RELATION'][$i],
                        'INFO' => $params['related']['INFO'][$i]
                    );
                }
            }
            $document->setRelated($related);
        }

        $transfert = Hal_Transfert_Arxiv::init_transfert($document);
        $transfertArxivAsked = isset($params['arxiv']);

        $transfertArxiv = $transfertArxivAsked && ($transfert ->canShowTransfert() > 0);
        if ($transfertArxiv || ($transfert->getRemoteId() != null)) {
            $transfert ->save();
        } else {
            $transfert ->delete();
        }
        $document->setTransfertArxiv($transfertArxiv);
        $document->setTransfertPMC(isset($params['pubmedcentral']) && $params['pubmedcentral']);
        $document->setTransfertSWH(isset($params['swh']) && $params['swh']);

        $logUid = 0;
        if ($type == Hal_Settings::SUBMIT_MODERATE || $type == Hal_Settings::SUBMIT_ADDFILE || $type == Hal_Settings::SUBMIT_ADDANNEX || $type == Hal_Settings::SUBMIT_UPDATE || $type == Hal_Settings::SUBMIT_MODIFY) {
            $logUid = Hal_Auth::getUid();
        }

        $moderationMsg = isset($params['moderationMsg']) ? $params['moderationMsg'] : "";

        if ($moderationMsg !== "") {
            $document->setModerationMsg($moderationMsg);
        }

        $document = Hal_Document_Validity::eraseIncompatibleMetas($document);

        // On vérifie qu'on n'est pas en train d'enregistrer une notice V1 alors qu'il en existe déjà une en ligne pour ce document.
        // @see https://wiki.ccsd.cnrs.fr/wikis/ccsd/index.php/Versionnement_des_documents
        $docidToModify = $document->onlineVersion();
        if ($document->isNotice() && $docidToModify !== 0) {
            $toModify = Hal_Document::find($docidToModify, '', 0, true);
            $toModify->setHalMeta($document->getHalMeta());
            $toModify->setAuthors($document->getAuthors());
            $toModify->setTypeSubmit(Hal_Settings::SUBMIT_MODIFY);

            $document = $toModify;
        }

        $isSaved = $document->save($logUid);

        if ($isSaved === false) {
            Ccsd_Tools::panicMsg(__DIR__, __LINE__, 'Erreur à l\'enregistrement du dépôt !');
        }

        if ($type == Hal_Settings::SUBMIT_ADDFILE || $type == Hal_Settings::SUBMIT_ADDANNEX || $type == Hal_Settings::SUBMIT_REPLACE) {
            $docOwners = new Hal_Document_Owner();
            $docOwners->saveDocOwners($document->getId(), $document->getOwner());
        }
    }
}
