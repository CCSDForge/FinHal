<?php

/**
 * Vérification de la validité d'un document
 * Class Hal_Document_Validity
 */
class Hal_Document_Validity
{
    /**
     * identifiants autorisés à ne rentrer aucune affiliation
     * @var array
     */
    static protected $_authorizedUids = [
        1, //root
        301560, //edpsciences
        151596, //bmc
        194338, //bmc-cea
        326461, //bmc-sword
        311624 //dissemin
    ];

    /**
     * Indique si un document est valide et s'il peut être enregistré
     * @param Hal_Document $document
     * @param string $step
     * @return bool
     * @uses isValidFile
     * @uses isValidTypdoc
     * @uses isValidMeta
     * @uses isValidAuthor
     * @uses isValidRecap
     * @throws Hal_Document_Exception
     */
    static public function isValid(Hal_Document &$document, $step = null)
    {
        if ($step !== null) {
            $steps = [$step];
        } else {
            $steps = Hal_Settings::getSubmissionsSteps();
        }

        $valid = true;
        $errors = [];
        foreach ($steps as $step) {
            $method = 'isValid' . ucfirst($step);
            try {
                $valid = static::$method($document) && $valid;
            } catch (Hal_Document_Exception $e) {
                $valid = false;
                $errors[$step] = $e->getErrors();
            }
        }

        if (! $valid ) {
            static::throwException($errors);
        }
        return true;
    }

    /**
     * Indique si l'étape typdoc est valide
     * On vérifie que le typdoc appartient bien aux types du portail
     * @param Hal_Document $document
     * @return bool
     * @throws Hal_Document_Exception
     */
    static public function isValidTypdoc (Hal_Document &$document)
    {
        if (! in_array($document->getTypDoc(), Hal_Settings::getTypdocsAvailable($document->getInstance()))) {
            static::throwException('Missing or invalid typdoc');
        }
        return true;
    }

    /**
     * Indique si l'étape file est valide
     * @param Hal_Document $document
     * @return bool
     * @throws Hal_Document_Exception
     */
    static public function isValidFile (Hal_Document &$document)
    {
        if ($document->getTypeSubmit() != Hal_Settings::SUBMIT_UPDATE && $document->getDefaultFile() === false) {
            //Pas de fichier principal
            if (in_array($document->getTypdoc(), Hal_Settings::getTypdocFulltext()) || !$document->canBeNotice()) {
                //Fulltext obligatoire
                if ($document->existFile()) {
                    static::throwException([0=>'Missing main file']);
                } else {
                    static::throwException([0=>'Missing file']);
                }
            } else {
                //Type de dépôt acceptant le dépôt de
                $onlyAnnex = true;
                foreach($document->getFiles() as $file) {
                    /* @var $file Hal_Document_File */
                    $onlyAnnex = $onlyAnnex && $file->getType() == 'annex';
                }
                if (! ($onlyAnnex || ! $document->existFile()) ) {
                    static::throwException([0=>'Missing main file or annex']);
                }
            }
        }

        if ($document->getTypDoc() == 'PATENT' && $document->existFile()) {
            static::throwException([0=>'File not accepted for this document type']);
        }

        return true;
    }

    /**
     * Indique si l'étape meta est valide
     * @param Hal_Document $document
     * @return bool
     * @throws Hal_Document_Exception
     */
    static public function isValidMeta (Hal_Document &$document)
    {
        $form = Hal_Submit_Manager::createMetadataForm($document->getTypDoc(), $document->getAllDomains());

        // Dans le cas où la métadonnée A Paraitre existe, la date de publication n'est pas obligatoire
        if ($document->getMeta('inPress')) {
            $form->getElement('date')->setRequired(false);
        }


        // Pour une "Autre Publication", on rend obligatoire soit la description, soit le nom de la revue, soit le titre de l'ouvrage
        if ('OTHER' == $document->getTypdoc()) {
            $journal = $document->getMeta('journal');
            $booktitle = $document->getMeta('bookTitle');

            if (isset($journal) && !empty($journal)) {
                $form->getElement('bookTitle')->setRequired(false);
                $form->getElement('description')->setRequired(false);
            } else if (isset($bookTitle) && !empty($booktitle)) {
                $form->getElement('journal')->setRequired(false);
                $form->getElement('description')->setRequired(false);
            } else {
                $form->getElement('journal')->setRequired(false);
                $form->getElement('bookTitle')->setRequired(false);
            }
        }
        $metas = Hal_Document_Meta_Domain::explodeInterDomains($document->getMeta(), $form);

        $metas = Hal_Document_Meta_Domain::explodeInterDomains($document->getMeta(), $form);

        // Fonction à effet de bord qui remplit les messages du formulaire ! Ne pas la virer :D
        try {
            if ($form->isValid(array_merge($metas, ['type' => $document->getTypDoc()]))) {
                return true;
            }
        } catch (Zend_Form_Exception $e) {
            // can't arise: we have an array
        }

        $errors = [];
        foreach($form->getMessages() as $meta => $errs) {
            $errors[$meta] = $errs;
        }

        if (count($errors) > 0) {
            static::throwException($errors);
        }
        return true;
    }

    /**
     * Indique si l'étape author est valide
     * @param Hal_Document $document
     * @return bool
     * @throws Hal_Document_Exception
     */
    static public function isValidAuthor (Hal_Document &$document)
    {
        $errors = [];

        // On vérifie la validité des auteurs
        $i = 0;
        $affiliatedAuthors = 0;
        foreach ($document->getAuthors() as $author ) {
            $i++;
            /* @var $author Hal_Document_Author */
            if ( $author->getAuthorid() == 0 && !$author->isWellFormed() ) {
                $errors[] = $i . " : " . Zend_Registry::get(ZT)->translate('Author not well formed') . " (" . $author->getFullname(true) .")";
            }
            if ($author->isAffiliated()) {
                $affiliatedAuthors++;
            }
        }
        // On vérifie la validité des structures
        $i = 0;
        foreach($document->getStructures() as $structure) {
            $i++;
            /* @var $structure Hal_Document_Structure */
            if ( $structure->getStructid() == 0 && !$structure->isWellFormed() ) {
                $errors[] = $i . " : " . Zend_Registry::get(ZT)->translate('Structure not well formed') . " (" . $structure->getStructname() .")";
            }
        }
        // Affiliation author/structure
        if ( $document->getInputType() == Hal_Settings::SUBMIT_ORIGIN_SWORD && in_array($document->getContributor('uid'), static::$_authorizedUids) ) {
            //Cas particulier pour certains comptes en SWORD
            $nbAffiliatedAuthors = 0;
        } else {
            $nbAffiliatedAuthors = Hal_Settings::getNbAffiliatedAuthors(
                $document->getDefaultFile() !== false ? Hal_Document::FORMAT_FILE:  Hal_Document::FORMAT_NOTICE,
                $document->getTypDoc(),
                $document->createProducedDate());
        }

        if ($document->getAuthorsNb() == 0) {
            //Au moins 1 auteur doit être saisi
            $errors[] = Zend_Registry::get(ZT)->translate('Missing author(s)');
        } else if ($nbAffiliatedAuthors === "all" && $affiliatedAuthors != $document->getAuthorsNb()) {
            $errors[] = Zend_Registry::get(ZT)->translate('Please fill affiliation for all the authors');
        } else if ($nbAffiliatedAuthors > 0 && $affiliatedAuthors == 0) {
            $errors[] = Zend_Registry::get(ZT)->translate('Missing affiliation for at least one author');
        }

        if (count($errors)) {
            static::throwException($errors);
        }
        return true;
    }

    /**
     * Validation des métadonnées conforme au type de document + suppression des autres
     *
     * @param Hal_Document $document
     * @return Hal_Document
     */
    static public function eraseIncompatibleMetas(Hal_Document $document) {

        $form = Hal_Submit_Manager::createMetadataForm($document->getTypDoc(), $document->getAllDomains());

        // Dans le cas où la métadonnée A Paraitre existe, la date de publication n'est pas obligatoire
        if ($document->getMeta('inPress')) {
            $form->getElement('date')->setRequired(false);
        }
        try {
            if ($form->isValid(array_merge($document->getMeta(), ['type' => $document->getTypDoc()]))) {
                $document->clearMetas();
                $document->addMetas($form->getValues(), Hal_Auth::getUid());
            }
        } catch (Zend_Form_Exception $e) {
            // Can't arise, param is an array...
        }
        return $document;
    }

    //todo à modifier : pas glop
    /**
     * @param Hal_Document
     * @return bool
     */
    static public function isValidRecap (Hal_Document &$document)
    {
        return true;
    }

    /**
     * @param $errors
     * @throws Hal_Document_Exception
     */
    static private function throwException ($errors)
    {
        if (! is_array($errors)) {
            $errors = Zend_Registry::get(ZT)->translate($errors);
        }
        $exception = new Hal_Document_Exception();
        $exception->setErrors($errors);
        throw $exception;
    }
}