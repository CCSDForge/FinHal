<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 07/02/17
 * Time: 14:12
 */
class Hal_Submit_Step_File extends Hal_Submit_Step
{

    /**
     * @var string
     */
    protected $_name = "file";

    /**
     * @var array
     */
    protected $_filesNotDeletable;

    /**
     * @var string
     */
    protected $_idext = "";

    /**
     * @var string
     */
    protected $_idtype = "doi";

    /**
     * Initialisation de la vue de l'étape fichier
     * @param Hal_View $view
     * @param Hal_Document $document
     * @param string $type
     * @param bool $verifValidity
     * @return Hal_View
     */
    public function initView( Hal_View &$view, Hal_Document &$document, $type, $verifValidity = false)
    {
        $view->filemode = $this->_mode;
        $view->typdoc = $document->getTypdoc();
        $view->controller = "submit";

        $view->addFile = true;
        $view->editFile = true;
        $view->type = $type;

        //Le déposant doit forcément déposer un fichier
        $view->submitFulltext = in_array($type, [Hal_Settings::SUBMIT_ADDFILE, Hal_Settings::SUBMIT_ADDANNEX, Hal_Settings::SUBMIT_REPLACE])
            || (in_array($type, [Hal_Settings::SUBMIT_MODIFY, Hal_Settings::SUBMIT_MODERATE]) && $document->getVersion() > 1)
            || in_array($document->getTypdoc(), Hal_Settings::getTypdocFulltext());

        if ($type == Hal_Settings::SUBMIT_ADDFILE || $type == Hal_Settings::SUBMIT_ADDANNEX) {
            //Le déposant ne peut pas supprimer les fichiers de son dépôt
            $view->filesNotDeletable = $this->_filesNotDeletable;
            $view->onlyAnnex = ($document->getFormat() == Hal_Document::FORMAT_FILE) ;
        } else if ($type == Hal_Settings::SUBMIT_UPDATE && $document->existFile()) {
            //Le déposant ne peut pas ajouter de fichier, ni d'en supprimer
            $view->filesNotDeletable = $this->_filesNotDeletable;
            $view->addFile = false;
            $view->editFile = false;
        }

        //Extension des fichiers acceptés
        $view->extensions = Hal_Settings::getFileExtensionAccepted($document->getTypdoc());

        // Dans le cas de l'ajout d'une nouvelle version ou de la modification des métadonnées, on empêche la modification du fichier principal
        $view->mainFileType = ($type == Hal_Settings::SUBMIT_ADDFILE || $type == Hal_Settings::SUBMIT_ADDANNEX || $type == Hal_Settings::SUBMIT_UPDATE) ? [] : Hal_Submit_Manager::allMainFileExtensions();


        if (!$this->_mode) {
            // Vue détaillée
            $view->fileVisibility = Hal_Settings::getFileVisibility($document->getTypdoc());
            $view->embargo = Hal_Settings::getMaxEmbargo($document->getTypdoc());

            $view->divOrigin = Hal_Settings::showOriginFileBox($document->getTypdoc());
            $view->divLicence = Hal_Settings::showLicenceBox($document->getTypdoc());
            if ($view->divLicence) {
                $view->showLicence = Hal_Settings::requiredLicence($document->getTypdoc()) || (in_array($type, array(Hal_Settings::SUBMIT_UPDATE, Hal_Settings::SUBMIT_ADDFILE, Hal_Settings::SUBMIT_ADDANNEX)) && $document->existFile());
                $view->requiredLicence = Hal_Settings::requiredLicence($document->getTypdoc());
                $view->licences = Hal_Settings::getKnownLicences();
            }
        }
        //Fichiers associés au document
        $view->files = $document->getFiles();
        $view->formats = Hal_Settings::getFileFormats();

        //Fichiers se trouvant dans l'espace FTP du déposant
        $view->ftp = array();
        $ftpFiles = Ccsd_User_Models_UserMapper::getUserHomeFtpFiles(Hal_Auth::getUid());
        if (isset ($ftpFiles) && is_array($ftpFiles)) {
            foreach ($ftpFiles as $filename) {
                if (in_array(Ccsd_File::getExtension($filename), $view->extensions)) {
                    $view->ftp[] = $filename;
                }
            }
        }

        $view->listTypdocs = Hal_Settings::getTypdocsSelect(SPACE_NAME);

        // Passage de l'identifiant externe pour pré-remplir la zone
        $view->idext = $this->_idext;
        $view->idtype = $this->_idtype;
        $view->idurl = Hal_Submit_Manager::getIdUrl($this->_idtype, $this->_idext);

        $view->idplaceholder = Hal_Submit_Manager::getExtIdentifiers()[$this->_idtype];

        $view->currentTypdoc = $document->getTypDoc();
        $view->document = $document;

        return $view;

    }

    /**
     * Render pour un fichier
     * @param Hal_View $view
     * @param Hal_Document $document
     * @param string $type
     * @param int $idx indice du fichier en session
     * @return string
     */
    public function renderDetailled(Hal_View &$view, Hal_Document &$document, $type, $idx)
    {
        if ($type == Hal_Settings::SUBMIT_ADDFILE || $type == Hal_Settings::SUBMIT_ADDANNEX) {
            //Le déposant ne peut pas supprimer les fichiers de son dépôt
            $view->filesNotDeletable = $this->_filesNotDeletable;
            $view->onlyAnnex = ($document->getFormat() == Hal_Document::FORMAT_FILE) ;
        }
        $view->addFile = true;
        $view->editFile = true;
        $view->mainFileType = Hal_Settings::getMainFileType($document->getTypdoc());
        $view->divOrigin = Hal_Settings::showOriginFileBox($document->getTypdoc());
        $view->fileVisibility = Hal_Settings::getFileVisibility($document->getTypdoc());
        $view->embargo = Hal_Settings::getMaxEmbargo($document->getTypdoc());
        $file = $document->getFile($idx);
        $acceptedTypes = Hal_Settings::getFileTypes($document->getTypdoc(), $file->getExtension());
        $view->types = !empty($acceptedTypes) ? $acceptedTypes : [$file->getType()];
        $view->formats = Hal_Settings::getFileFormats();
        $view->file = $document->getFile($idx);
        $view->i = $idx;


        $view->canChange = $view->editFile && (! isset($view->filesNotDeletable) || (! in_array($view->i, $view->filesNotDeletable)));
        $view->iconwarning = strpos($view->file->getTypeMIME(), 'html') ? 'style="color:red;"' : 'style="display:none;"';

        return $view->render('submit/step-file/detailed-file.phtml');
    }

    public function setFilesNotDeletable($files)
    {
        $this->_filesNotDeletable = $files;
    }

    public function setIdExt($type, $id)
    {
        $this->_idtype = $type;
        $this->_idext = $id;
    }

    public function updateValidity(Hal_Document &$document, $type)
    {
        parent::updateValidity($document, $type);

        //Interdire de déposer aucun fichier
        if ((Hal_Settings::SUBMIT_REPLACE == $type && 0 == count($document->getFiles())) || (Hal_Settings::SUBMIT_MODIFY == $type && 0 == count($document->getFiles()) && $document->getStatus() == 1 && $document->getVersion() >= 2)) {
            $this->_validity = false;
        }
    }

}