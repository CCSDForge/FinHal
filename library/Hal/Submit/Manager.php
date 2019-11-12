<?php

/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 04/11/2016
 * Time: 15:42
 */

class Hal_Submit_Manager_Exception extends Exception
{}

class Hal_Submit_Manager
{
    /**
     * Nombre d'auteurs MAX pour lesquels on va rechercher une affiliation
     */
    const MAX_AFFILIATED_AUTHORS = 10;

    const LOG_DATE_FORMAT = 'H:i:s';

    const GROBID = "grobid";
    const DOI = "doi";
    const ARXIV = "arxiv";

    static public $source_extensions = ['eps', 'bib', 'bbl', 'tex', 'ind', 'gls', 'sty', 'cls', 'bst', 'aux', 'dvi'];

    const SRC_URL = 'SRC_URL';
    const SRC_FTP = 'SRC_FTP';
    const SRC_FILE = 'SRC_FILE';

    /**
     * @var Hal_Document
     */
    protected $_document;

    /**
     * @var Hal_Submit_Status
     */
    protected $_status;

    /**
     * @var Hal_Submit_Options
     */
    protected $_options;


    /************* UTILS *************/

    /**
     * Hal_Submit_Managerclass constructor.
     * @param Hal_Session_Namespace $session
     */
    public function __construct(Hal_Session_Namespace $session)
    {
        $this->_document = $session->document;
        $this->_status = $session->submitStatus;
        $this->_options = $session->submitOptions;
    }


    /**
     * @param $files
     * @param $tmpDir
     */
    public function copyFilesInTmp($files, $tmpDir) {

        if (count($files)) {

            if (!is_dir($tmpDir)) {
                mkdir($tmpDir);
            }
            foreach ($files as $file) {
                /* @var Hal_Document_File $file */
                $filePath = $tmpDir . $file->getName();
                $dirPath = rtrim(str_replace(basename($file->getName()), '', $filePath), '/');
                if (!is_dir($dirPath)) {
                    mkdir($dirPath, 0777, true);
                }
                if (copy($file->getPath(), $filePath)) {
                    $file->setPath($filePath);
                }
            }
        }
    }

    /************* ETAPE FICHIER *************/

    /**
     * @param Hal_Document_File $file
     * @return bool
     */
    public function shouldBeReplacedAsDefault(Hal_Document_File $file)
    {
        $typdoc = $this->getTypdocFromMetadata($file->getPath(), null);

        if (!empty($typdoc)) {
            $max = Hal_Settings::getFileLimit($typdoc);
            return $this->_document->getFileNb() >= $max;
        } else {
            return false;
        }
    }

    /**
     * Modification du type de document selon le nombre de fichier présents
     * Utilisation : lorsqu'on a déposé une première IMG, on veut que le type IMG soit choisi par défaut pour accélérer le processus
     * si l'on ajoute plus de fichiers, on souhaite qu'il reset le type de document car le type IMG est trop bloquant.
     *
     * @throws Hal_Submit_Manager_Exception
     */
    public function setTypDocFromFileLimit()
    {
        // Si l'on a atteint le nombre limite de fichier pour ce type de document, on revient au type de document par défaut
        $max = Hal_Settings::getFileLimit($this->_document->getTypdoc());
        if ($max && $this->_document->getFileNb() >= $max) {
            $this->_document->setTypdoc(Hal_Settings::getDefaultTypdoc());
        }

        // Si le type de document par défaut n'accepte toujours pas plus de fichiers
        $newmax = Hal_Settings::getFileLimit($this->_document->getTypdoc());
        if ($newmax && $this->_document->getFileNb() >= $newmax) {
            throw new Hal_Submit_Manager_Exception("Vous ne pouvez pas rajouter de fichier pour ce type de document");
        }
    }

    /**
     * On vérifie s'il n'y pas un erreur de dépot du fichier
     * @param $files
     * @param $extension
     * @param $typdoc
     * @return string
     */
    public function existSubmitError($files, $extension, $typdoc) {

        // Vérification de l'existence d'un fichier
        if (empty($files)) {
            return "Erreur dans l'envoi du fichier";
        } else {

            // 1 seul fichier passé en paramètre
            if (!is_array($files)) {
                $files = [$files];
            }

            // Récupération des erreurs sur les fichiers chargés
            foreach ($files as $file) {
                if (isset($file->error) && $file->error != "") {
                    return $file->error;
                }
            }
        }

        // Vérification de l'extension du fichier
        if (!in_array(strtolower($extension), Hal_Settings::getFileExtensionAccepted($typdoc))) {
            return '<strong>' . $extension . '</strong> : ' . 'Type de fichier non autorisé';
        }

        // TODO : Warning en cas de Fichier HTML

        return "";
    }

    /**
     * Transforme l'ojet fichier en tableau
     * @param $file
     * @return array
     */
    public function fromFileObjectToArray($file)
    {
        $fileArray = [];

        $fileArray["name"] = $file->name;
        $fileArray["type"] = $file->type;

        return $fileArray;

    }

    /**
     * Fonction unique de traitement d'un fichier quelque soit son point d'entrée (drop, url, ftp, etc)
     * @param $from
     * @param $src
     * @param $type
     * @param $tmpDir
     * @param Hal_View $view
     * @return array|mixed
     * @throws Exception
     */
    public function processNewFile($from, $src, $type, $tmpDir, Hal_View $view)
    {

        // STEP 1 : Modifier le type de document si le nombre de fichier ne correspond plus au choix (ex: dépot IMG puis d'un ART)
        $this->setTypDocFromFileLimit();

        // STEP 2 : Création de l'espace temporaire du dépôt
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir);
        }

        $file = [];

        // STEP 3 : Récupération du fichier
        if ($from == self::SRC_URL) {
            $file = $this->getFileFromUrl($src);

            if (!file_put_contents($tmpDir . $file["name"], $file["content"])) {
                throw new Hal_Submit_Manager_Exception("Impossible de sauvegarder l'URL saisie");
            }

        } else if ($from == self::SRC_FTP) {
            $file = Ccsd_User_Models_User::CCSD_FTP_PATH . Hal_Auth::getUid() . '/' . $src;

            if (!file_exists($file) || !copy($file, $tmpDir . $src)) {
                throw new Hal_Submit_Manager_Exception("Problème de copie du fichier");
            }

            $file = [];
            $file["name"] = $src;

        } else if ($from == self::SRC_FILE) {
            $file = $this->fromFileObjectToArray($src);
        } else {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "$from is not a good value for parameter from");
        }

        // STEP 4 : Recherche des erreurs sur le fichier
        $extension = Ccsd_File::getExtension($file["name"]);
        $filetype = array_key_exists("type", $file) ? $file["type"] : null;

        $res = $this->existSubmitError($file["name"], $extension, $this->_document->getTypdoc());
        if ($res != "") {
            throw new Hal_Submit_Manager_Exception($res);
        }

        $filename = $file["name"];
        $filepath = $tmpDir . $filename;

        $idsx = [];

            // STEP 5 : On enlève comme principal le fichier actuel si on a modifié le nouveau fichier
        $currentDefaultFile = $this->_document->getDefaultFile();
        if ($currentDefaultFile && $this->shouldBeReplacedAsDefault($currentDefaultFile) && (Ccsd_File::canConvert($filepath) || strtolower($extension) == "pdf")) {
            $this->_document->majMainFile($currentDefaultFile);
            $currentDefaultFile->setType(Hal_Settings::FILE_TYPE_ANNEX);
            // On veut signifier qu'il y a eu un changement sur ce document.
            $idsx[] = $this->_document->getFileIdByName($currentDefaultFile->getName());
        }

        // STEP 6 : Ajout du fichier au document

        $idsx = array_merge($idsx, $this->addFileToDocument($this->fileDataToFileObject($filename, $filepath, null, $filetype), $tmpDir));

        $existMain = $this->_document->getFile($idsx[0])->getDefault();

        // STEP 7 : Récupếration de la vue des fichiers
        if ($this->_status->getStep(Hal_Settings::SUBMIT_STEP_FILE)->getMode() == Hal_Settings::SUBMIT_MODE_DETAILED) {
            $toreturn = $this->getDetailledFiles($view, $type, $idsx);
            $toreturn["existMain"] = $existMain;
        } else {
            $toreturn = $this->getSimpleFiles($idsx, $filename, $existMain);
        }

        $toreturn["convertedName"] = isset($toreturn["convertedName"]) ? $toreturn["convertedName"] : '';

        // STEP 8 : Préparation du message succès/échec de la récupération de métas
        $returnCode = $this->prepareFileReturnedMsg($view, $toreturn["existMain"], array_key_exists("converted", $toreturn), false, $filename, $toreturn["convertedName"]);

        // les messages d'info sur le traitement du fichier téléversé
        if (!Hal_Settings::showUploadReturnMsg()) {
            $toreturn["noReturnMsg"] = true;
        }
        // Aucun message retourné s'il ne s'agit pas du fichier principal
        if ($returnCode) {
            $toreturn["sucessMsg"] = $view->render('submit/step-file/sucessmsg.phtml');
        }

        // STEP 9 : Rechargement des vues impactées pour l'affichage dans les vues
        return $toreturn;
    }

    /**
     * @param string $filename
     * @param string $filepath
     * @param int $filesize
     * @param string $typeMIME
     * @return Hal_Document_File
     */
    public function fileDataToFileObject($filename, $filepath, $filesize = null, $typeMIME = null)
    {
        if (null == $filesize) {
            $filesize = (@filesize($filepath))?filesize($filepath):0;
        }
        if (null == $typeMIME) {
            $typeMIME = Ccsd_File::getMimeType($filepath);
        }

        $file = array(
            'name' => $filename,
            'path' => $filepath,
            'size' => $filesize,
            'typeMIME' => $typeMIME
        );

        $fileObj = new Hal_Document_File(0, $file);

        return $fileObj;
    }

    /**
     *
     * Récupération des métadonnées du fichier et consolidation Crossref si Doi trouvé
     *
     * @param Hal_Document_File $file
     * @return array
     */
    public function getMetaArrayFromFile(Hal_Document_File $file)
    {
        $metasArray = [];

        if ($file->isPdf() && Hal_Settings::usegrobid4meta()) {
            $metasArray[self::GROBID] = $this->createMetadatas($file->getPath(), "pdf");
        } else if (in_array($file->getExtension(), ['jpg', 'jpeg'])){
            $file->setDefault(true);
            $image = new Ccsd_Externdoc_Image($file->getPath());
            $metasArray["image"] = $image->getMetadatas();
        }

        // Complétion par DOI s'il a été trouvé
        // Grobid est appele avec consolidate=1, pas besoin de requeter CrossRef de notre cote.
        //if (isset($metasArray[self::GROBID][Ccsd_Externdoc::META][Ccsd_Externdoc::META_IDENTIFIER][self::DOI])) {
        //    try {
        //        $metasArray[self::DOI] = Hal_Submit_Manager::createMetadatas($metasArray[self::GROBID][Ccsd_Externdoc::META][Ccsd_Externdoc::META_IDENTIFIER][self::DOI], self::DOI);
        //    } catch (Exception $e) {
        //        //TODO Faire quelque chose de cette erreur
        //        Ccsd_Tools::panicMsg(__FILE__, __LINE__, "getMetaArrayFromFile: " . $e->getMessage());
        //    }
        //}

        return $metasArray;
    }

    /**
     *
     * Méthode pour ajouter un fichier au document en session
     * Il est converti dans certains cas (png, doc, etc)
     *
     * @param Hal_Document_File $file
     * @param $tmpDir
     * @return array
     */
    public function addFileToDocument(Hal_Document_File $file, $tmpDir)
    {
        // error_log("Avant recuperation : ".date(self::LOG_DATE_FORMAT));

        $extension = $file->getExtension();

        $metasArray = [];

        // Récupération des métadonnées PDF ou IMAGE
        if (false === $this->_document->getDefaultFile() && Hal_Settings::canBeMainFile($extension) && $this->_status->getSubmitType() != Hal_Settings::SUBMIT_MODERATE) {
            $file->setDefault(true);
            $file->setOrigin(Hal_Settings::FILE_SOURCE_AUTHOR);
            $file->setType(Hal_Settings::FILE_TYPE);

            if ($this->_options->completeMeta() || $this->_options->completeAuthors()) {
                try {
                    $metasArray = $this->getMetaArrayFromFile($file);
                } catch (Exception $e) {
                    //Aucune métadonnées récupérées de Grobid ou service inaccessible
                    //todo : envoyer une exception ?
                    //error_log("Exception recup grobid ou crossref pour " . $document->getDocid() . ": " . $e -> getMessage());
                }
            }

            // Chargement des métadonnées dans le document
            if (!empty($metasArray)) {
                $this->loadExternalMeta($metasArray, $this->_options);
            }

            // On met à jour le type de document s'il est vide
            if ('' == $this->_document->getTypDoc()) {
                $this->changeCurrentTypdoc($this->getTypdocFromMetadata($file->getPath(), $this->_document->getHalMeta()));
            }
        }

        // Modif du format du fichier
        if ($file->getType() === '') {
            $file->setType($this->sourceOrAnnex($extension));
        }

        $idx[] = $this->_document->addFile($file);

        // CONVERSION DU FICHIER SI POSSIBLE
        $convertedName = $this->convertFile($tmpDir.$file->getName(), $tmpDir);

        // Ajout du fichier converti si nécessaire
        if (!empty($convertedName) && $convertedName !== false && $convertedName != $file->getName()) {
            // Le fichier converti est considéré comme "fichier source"
            $this->_document->getFile($idx[0])->setType(Hal_Settings::FILE_TYPE_SOURCES);
            $fileObject = $this->fileDataToFileObject($convertedName, $tmpDir.$convertedName);
            $fileObject->setSource(Hal_Document_File::SOURCE_CONVERTED);
            $idConverted = $this->addFileToDocument($fileObject, $tmpDir);
            $idx = array_merge($idx, $idConverted);
        }

        //error_log("Document Ajoute : ".date(self::LOG_DATE_FORMAT));
        //error_log("------------------------------------");
        return $idx;
    }

    /**
     * @param $extension
     * @return string
     */
    private function sourceOrAnnex($extension)
    {
        if (in_array(strtolower($extension), self::$source_extensions)) {
            return Hal_Settings::FILE_TYPE_SOURCES;
        } else {
            return Hal_Settings::FILE_TYPE_ANNEX;
        }
    }

    /**
     * Ajout de fichiers zippés au document en session
     *
     * @param $archive
     * @param $tmpDir
     * @return array
     */
    public function addZippedFiles($archive, $tmpDir)
    {
        $filesInSession = array();
        $isTex = false;
        foreach($this->_document->getFiles() as $file) {
            $filesInSession[] = $file->getPath();
            $isTex = $isTex || (strtolower($file->getExtension()) == 'tex');
        }

        $idsx = [];

        foreach (Ccsd_File::unarchiver($archive) as $filepath) {
            if (! in_array($filepath, $filesInSession)) {
                $filename = str_replace($tmpDir, '', $filepath);
                $fileObject = $this->fileDataToFileObject($filename, $filepath);
                $fileObject->setSource(Hal_Document_File::SOURCE_UNZIPPED);
                if ($isTex) {
                    // S'il y a une .tex dans le zip, on considère tous les fichiers comme sources.
                    $fileObject->setType(Hal_Settings::FILE_TYPE_SOURCES);
                }
                $idsx = array_merge($idsx, $this->addFileToDocument($fileObject, $tmpDir));
            }
        }

        return $idsx;
    }

    /**
     * @param $metas
     * @param $author
     * @param $structs
     * @return array
     */
    public function prepareAffiliationParams($metas, $author, $structs)
    {
        $data = [];

        if (isset($author['lastname'])) {
            $data['lastName_t'] = $author['lastname'];
        }

        if (isset($author['firstname'])) {
            $data['firstName_t'] = $author['firstname'];
        }

        if (isset($author['authorid'])) {
            $data['authId_i'] = $author['authorid'];
        }

        if (isset($author['email'])) {
            $data['email_s'] = $author['email'];
        }
        if (isset($metas[Ccsd_Externdoc::META_DATE])) {
            $data['producedDate_s'] = $metas[Ccsd_Externdoc::META_DATE];
        }
        if (isset($metas[Ccsd_Externdoc::META_KEYWORD])) {
            foreach ($metas[Ccsd_Externdoc::META_KEYWORD] as $keyword) {
                //$data['keywords_t'][] = $keyword;
                //todo : comment on est sensé les encoder pour l'API affiliation
            }
        }

        // Ajout des affiliations trouvées dans le PDF ou par les identifiants
        if (count($structs)>0) {
            foreach ($structs as $i => $str) {
                $structArray = $metas[Ccsd_Externdoc::STRUCTURES][$structs[$i]];
                if (isset($structArray[Ccsd_Externdoc_Grobid::STRUCT_NAME])) {
                    $data['structure_t'][$i]['structName_t'] = $structArray[Ccsd_Externdoc_Grobid::STRUCT_NAME];
                }
                if (isset($structArray[Ccsd_Externdoc_Grobid::STRUCT_COUNTRY])) {
                    $data['structure_t'][$i]['structCountry_t'] = $structArray[Ccsd_Externdoc_Grobid::STRUCT_COUNTRY];
                }
                if (isset($structArray[Ccsd_Externdoc_Grobid::STRUCT_TYPE])) {
                    $data['structure_t'][$i]['structType_t'] = $structArray[Ccsd_Externdoc_Grobid::STRUCT_TYPE];
                }
            }

            // On encode les structures car ce n'est pas un simple tableau clé/valeur mais il a potentiellement des tableaux à l'intérieur (pour les structures)
            $data['structure_t'] = urlencode(json_encode($data['structure_t']));
        }

        return $data;
    }


    /**
     * @param Hal_Document_Author $docauthor
     * @param $res
     * @return bool
     */
    public function loadAuthorFromApiAffiliationResult(Hal_Document_Author &$docauthor, $res)
    {

        if (!isset($res) || !isset($res['results']) || $res['results'] == 'none' || empty($res['results']['authors']) ) {
            // Forme Auteur non trouvee
            return false;
        }

        $resAuthor = $res['results']['authors'];
        $firstAuthor = array_shift($resAuthor);


        // On choisit la première forme auteur
        // LE 'DOCID' est en fait le AUTHID
        $docauthor->setAuthorid($firstAuthor['docid']);
        $docauthor->load();

        return true;
    }

    /**
     * @param $res
     * @return array|null
     */
    public function createStructFromApiAffiliationResult($res)
    {

        if (!isset($res) || !isset($res['results']) || $res['results'] == 'none' || empty($res['results']['structures'])) {
            //  Affiliation non trouvee
            return null;
        }

        $resStructure = $res['results']['structures'];
        $firstStructure = array_shift($resStructure);

        // LE 'DOCID' est en fait le STRUCTID
        return ['struct' => new Hal_Document_Structure($firstStructure['docid']),
            'indice' => $firstStructure['score']];
    }

    /**
     * @param Hal_Document_Author $docauthor
     * @param $author
     */
    protected function loadAuthorWithoutAffiliation(Hal_Document_Author $docauthor, $author)
    {
        unset($author[Ccsd_Externdoc_Grobid::STRUCTURES]);
        $authorid = Hal_Document_Author::findByAuthor($author);
        if ($authorid) {
            //Auteur déjà présent dans le référentiel
            $docauthor->setAuthorid($authorid);
            $docauthor->load();
        } else {
            $docauthor->set($author);
        }
    }

    /**
     * @param $authorsAndStructs
     */
    public function addAndAffiliateAuthors($authorsAndStructs) {

        // Aucun auteur à ajouter
        if (!isset($authorsAndStructs[Ccsd_Externdoc::AUTHORS])) {
            return;
        }

        $foundStructures = [];
        $structsToAuthors = [];
        $nbRequests = 0;

        // Ajout des auteurs au document
        foreach ($authorsAndStructs[Ccsd_Externdoc::AUTHORS] as $authorData) {

            $docAuthor = new Hal_Document_Author();
            $authorLoaded = false;

            // Recherche d'affiliation sans structure trouvée par grobid ou identifiant
            if (!array_key_exists(Ccsd_Externdoc_Grobid::STRUCTURES, $authorData) || empty($authorData[Ccsd_Externdoc_Grobid::STRUCTURES])) {

                // todo : optimiser le copier/coller
                $data = $this->prepareAffiliationParams($authorsAndStructs, $authorData, []);
                $res = Hal_Search_Solr_Search_Affiliation::rechAffiliations($data);
                $authorLoaded = $this->loadAuthorFromApiAffiliationResult($docAuthor, $res);
                $foundStruct = $this->createStructFromApiAffiliationResult($res);

                // On affilie l'auteur
                if (null != $foundStruct) {
                    $structidx = $this->_document->addStructure($foundStruct['struct']);
                    $docAuthor->addStructidx($structidx);
                }
                $nbRequests++;
            } else {
                // Recherche d'affiliation avec structure trouvée par grobid ou identifiant
                foreach ($authorData[Ccsd_Externdoc_Grobid::STRUCTURES] as $j) {

                    // On conserve la correspondance d'une structure avec les auteurs qui doivent l'affilier
                    $structsToAuthors[$j][] = $docAuthor;

                    if (array_key_exists($j, $foundStructures)) {
                        continue;
                    } else if ($nbRequests < self::MAX_AFFILIATED_AUTHORS) {
                        // On cherche la forme auteur et l'affiliation de l'auteur (On cherche limite le nombre de requêtes pour diminuer le temps de chargement quand il y a beaucoup d'auteurs)

                        $structidsx = isset($authorData[Ccsd_Externdoc_Grobid::STRUCTURES]) ? $authorData[Ccsd_Externdoc_Grobid::STRUCTURES] : [];

                        $data = $this->prepareAffiliationParams($authorsAndStructs, $authorData, $structidsx);
                        $res = Hal_Search_Solr_Search_Affiliation::rechAffiliations($data);
                        $authorLoaded = $this->loadAuthorFromApiAffiliationResult($docAuthor, $res);
                        $foundStruct = $this->createStructFromApiAffiliationResult($res);

                        // On garde en mémoire les affiliations déjà trouvées qui ont un fort indice de probabilité
                        // (On est dans le cas simple pour l'instant où on ne récupère qu'une seule affiliation même si on en a envoyé plusieurs)
                        if (null != $foundStruct && ($foundStruct['indice'] == 'Calc_Certain' || $foundStruct['indice'] == 'Calc_TresProbable' || $foundStruct['indice'] == 'Calc_Probable')) {
                            $foundStructures[$j] = $foundStruct['struct'];
                            // On ne cherche pas d'autres structure si on en a trouvé une (sinon les autres auteurs risquent de ne pas être affiliés)
                            break;
                        } else if (null != $foundStruct) {
                            // Si l'indice est petit, seul cet auteur est affilié
                            $structidx = $this->_document->addStructure($foundStruct['struct']);
                            $docAuthor->addStructidx($structidx);

                            // On ne cherche pas d'autres structure si on en a trouvé une (sinon les autres auteurs risquent de ne pas être affiliés)
                            break;
                        }

                        $nbRequests++;
                    }
                }
            }

            if (!$authorLoaded) {
                // On créée un nouvel auteur s'il n'a pas été trouvé
                unset($authorData[Ccsd_Externdoc_Grobid::STRUCTURES]);
                $this->loadAuthorWithoutAffiliation($docAuthor, $authorData);
            }

            $this->_document->addAuthor($docAuthor);
        }


        // On affilie réellement les auteurs et ajoute les structures au document
        foreach ($foundStructures as $k => $struct) {

            $structidx = $this->_document->addStructure($struct);

            /** @var Hal_Document_Author $author */
            foreach ($structsToAuthors[$k] as $author) {
                $author->addStructidx($structidx);
            }
        }

    }

    /**
     * @param $authorsAndStructs
     */
    public function addAndDontAffiliateAuthors($authorsAndStructs)
    {
        // Ajout des auteurs au document
        foreach ($authorsAndStructs[Ccsd_Externdoc::AUTHORS] as $authorData) {
            unset($authorData[Ccsd_Externdoc::STRUCTURES]);

            $docAuthor = new Hal_Document_Author();
            $this->loadAuthorWithoutAffiliation($docAuthor, $authorData);

            $this->_document->addAuthor($docAuthor);
        }
    }

    /**
     * @param $metasArray
     * @return Hal_Document_Metadatas
     */
    public function getObjectMetaFromSourcesArray($metasArray)
    {
        $halMeta = new Hal_Document_Metadatas();
        //métadonnées générales
        if (!empty($metasArray)) {
            foreach ($metasArray as $source => $metas) {
                $newMeta = new Hal_Document_Metadatas();
                $newMeta->addMetasFromArray($metas[Ccsd_Externdoc::META], $source, 0); //Ajoute les métas (Titre, résumé, volume, identifiant, langue, etc...)
                $halMeta->merge($newMeta);
            }
        }

        return $halMeta;
    }

    /**
     * @param $metasArray
     * @return array
     */
    public function getAuthorAndStructsFromSourcesArray($metasArray)
    {
        $authorsAndStructs = [];

        //métadonnées générales
        if (!empty($metasArray)) {
            foreach ($metasArray as $source => $metas) {
                $authorsAndStructs = Ccsd_Externdoc::mergeAuthorAndStructMetas($authorsAndStructs, $metas);
            }
        }

        return $authorsAndStructs;
    }


    /**
     * Chargement des métadonnées en provenance de Grobid ou d'un autre service externe
     * Ajout et affiliation des auteurs
     *
     * On garde le parametre OPTIONS car dans certains cas on ne veut pas prendre les options en session
     *
     * @param $metasArray
     * @param Hal_Submit_Options|null $options
     */
    public function loadExternalMeta($metasArray, Hal_Submit_Options $options = null)
    {
        if (null === $options) {
            // On prend les options par défaut
            $options = $this->_options;
        }

        if ($options->completeMeta()) {
            // On ajoute les métas trouvées en externe au document
            $this->_document->mergeHalMeta($this->getObjectMetaFromSourcesArray($metasArray));
        }

        if (!$options->completeAuthors()) {
            return;
        }

        $authorsAndStructs = $this->getAuthorAndStructsFromSourcesArray($metasArray);

        if ($options->affiliateAuthors()) {
            // On ajoute les auteurs en cherchant à les affilier
            $this->addAndAffiliateAuthors($authorsAndStructs);
        } else {
            // On ajoute les auteurs sans chercher à les affilier
            $this->addAndDontAffiliateAuthors($authorsAndStructs);
        }
    }

    /**
     * Ajout des métadonnées liées à un identifiant externe au document en session
     *
     * @param $idExt
     * @param $idType
     * @throws Hal_Submit_Manager_Exception
     */
    public function addIdExtToDocument($idExt, $idType)
    {
        try {
            $metasArray[$idType] = $this->createMetadatas($idExt, $idType);
            // Le type de document dépend de ce qu'on a trouvé comme métadonnées
            $this->changeCurrentTypdoc($metasArray[$idType][Ccsd_Externdoc::DOC_TYPE]);
        } catch (Exception $e) {
            //Aucune métadonnées récupérées
            //todo : envoyer une exception ?
        }

        if (!empty($metasArray)) {

            // Complétion par DOI s'il a été trouvé
            // On enlève le cas de CrossRef (idtype = doi) sinon on passe 2 fois dans createMetadatas
            if (isset($metasArray[$idType][Ccsd_Externdoc::META][Ccsd_Externdoc::META_IDENTIFIER][self::DOI]) && $idType != 'doi') {
                try {
                    $metasArray[self::DOI] = $this->createMetadatas($metasArray[$idType][Ccsd_Externdoc::META][Ccsd_Externdoc::META_IDENTIFIER]['doi'], "doi");
                    $this->changeCurrentTypdoc($metasArray[self::DOI][Ccsd_Externdoc_Crossref::DOC_TYPE]);
                } catch (Exception $e) {
                    //Aucune métadonnées récupérées
                    //todo : envoyer une exception ?
                }
            }

            $this->loadExternalMeta($metasArray);
        } else {
            throw new Hal_Submit_Manager_Exception("Aucune métadonnée récupérée pour cet identifiant.");
        }
    }

    /**
     * Récupération des métadonnées lié à un identifiant
     *
     * @param $idext
     * @param $idType
     * @return mixed
     * @throws Hal_Submit_Manager_Exception
     */
    public function createMetadatas($idext, $idType)
    {
        // On récupère le dataprovider correspondant au type d'identifiant envoyé
        $class = Hal_Settings::$_idtypeToDataProvider[$idType];
        $config = \Hal\Config::getInstance();
        if (class_exists ($class)) {
            /** @var Ccsd_Dataprovider $dp */
            $dp = new $class(Zend_Db_Table_Abstract::getDefaultAdapter(), $config);
            $o = $dp->getDocument($idext);

            /* @var Ccsd_Externdoc $o*/

            if (isset($o)) {
                return $o->getMetadatas();
            } else {
                throw new Hal_Submit_Manager_Exception($dp->getError());
            }
        }

        throw new Hal_Submit_Manager_Exception("Le type de l'identifiant n'a pas été reconnu");
    }

    /**
     * On recherche le type de document le plus probable à partir de l'extension du fichier et de ses metadonnées
     *
     * @param $filepath
     * @param Hal_Document_Metadatas | null $metadata
     * @return string
     */
    public function getTypdocFromMetadata($filepath, $metadata)
    {
        $extension = Ccsd_File::getExtension($filepath);

        // On cherche à définir le type de fichier à partir de l'extension du fichier
        if (in_array($extension, Hal_Settings::getFileExtensionAccepted("IMG"))) {
            return 'IMG';
        }

        if (in_array($extension, Hal_Settings::getFileExtensionAccepted("VIDEO"))) {
            return 'VIDEO';
        }

        if (in_array($extension, Hal_Settings::getFileExtensionAccepted("SON"))) {
            return 'SON';
        }

        if (!isset($metadata)) {
            return '';
        }

        // Si on trouve une donnée de journal => ARTICLE
        $journal = $metadata->getMeta(Ccsd_Externdoc::META_JOURNAL);
        $conftitle = $metadata->getMeta(Ccsd_Externdoc::META_CONFTITLE);
        if (isset($journal) && $journal->getJName() != "") {
            return 'ART';
        } else if (isset($metadata) && !empty($conftitle)) {
            return 'COMM';
        }

        return '';
    }

    /**
     * Renvoie les exemples de structure pour chaque type d'identifiant
     * @return array
     */
    static public function getExtIdentifiers()
    {
        return array(
            "doi" => "10.xxx",
            "arxiv" => "1401.0006 ou math/0602059",
            "pubmed" => "",
            "pubmedcentral" => "",
            "bibcode" => "",
            "cern" => "",
            "inspire" => "",
            "oatao" => "",
            "ird" => "");
    }

    /**
     * On récupère les métadonnées obligatoires préselectionnées par le système...
     *
     * @return array : Metadatas
     */
    public function getDefaultMetas()
    {
        $typdoc = $this->_document->getTypdoc();

        $metas = Hal_Settings::getMeta($typdoc);

        $finalMeta = array();

        foreach ($metas['elements'] as $key => $content) {
            if(array_key_exists('options', $content) && array_key_exists('required', $content['options']) && $content['options']['required'] == "1" ) {
                if (isset($content['options']['value']) && $content['options']['value'] != '') {
                    $finalMeta[$key] = $content['options']['value'];
                } else {
                    $values = Hal_Referentiels_Metadata::getValues($key);

                    if (!empty($values)) {
                        $finalMeta[$key] = array_shift($values);
                    }
                }
            }
        }
        return $finalMeta;
    }

    /**
     * Récupération d'un fichier à partir de son URL
     * @param $url
     * @return array
     * @throws Hal_Submit_Manager_Exception
     */
    public function getFileFromUrl($url)
    {
        if ($url != '' && filter_var($url, FILTER_VALIDATE_URL) !== false) {

            //Récupération du fichier via cURL
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_USERAGENT, "CCSD - HAL Proxy");
            curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_FAILONERROR, 1);
            curl_setopt($curl, CURLOPT_TIMEOUT, 5);
            curl_setopt($curl, CURLOPT_MAXREDIRS, 5);
            $content = curl_exec($curl);
            curl_close($curl);
            if ( strlen($content)) {
                $filename = $contentType = '';
                foreach ( get_headers($url) as $header ) {
                    if ( preg_match('/^Content-Type: *(.+)$/i', $header, $match) ) {
                        $contentType = trim($match[1]);
                    }
                    if ( preg_match('/^Content-Disposition: *[a-z]+; *filename="?([^"]+)"?$/i', $header, $match) ) {
                        $filename = $match[1];
                    }
                }
                if ( $filename == '' ) {
                    $filename = basename($url);
                }

                return ["type" => $contentType, "name" => $filename, "content" => $content];

            } else {
                throw new Hal_Submit_Manager_Exception("Impossible de récupérer l'URL saisie");
            }
        } else {
            throw new Hal_Submit_Manager_Exception("Erreur dans l'URL saisie");
        }
    }

    /**
     * @param $typdoc
     */
    public function changeCurrentTypdoc($typdoc)
    {
        $this->_document->setTypDoc($typdoc);
        // Ajout des métas par défaut pour le nouveau type de document
        if (!empty($typdoc)) {
            $this->_document->addMetas($this->getDefaultMetas(), 0);
        }

        $this->_status->setCurrentType($typdoc);
    }

    /**
     * @param $typdoc
     * @return array|bool
     */
    public function mainFileExtensions($typdoc)
    {
        if ("" == $typdoc) {
            return self::allMainFileExtensions();
        } else {
            return Hal_Settings::getMainFileType($typdoc);
        }
    }

    /**
     * @return array
     */
    static public function allMainFileExtensions()
    {
        return array_merge(Hal_Settings::getMainFileType('DEFAULT'), Hal_Settings::getMainFileType('IMG'), Hal_Settings::getMainFileType('VIDEO'), Hal_Settings::getMainFileType('SON'));
    }

    /**
     * @param $filepath
     * @param $tmpDir
     * @return bool|string
     */
    public function convertFile ($filepath, $tmpDir)
    {
        $extension = Ccsd_File::getExtension($filepath);

        //Conversion du fichier
        if (Ccsd_File::canConvert($filepath)) {
            return Ccsd_File::convert($filepath, $tmpDir);
            // On vérifie que l'image n'est pas déjà  au format converti pour ne pas dupliquer l'image
        }

        /*else if (!in_array($extension, array('jpeg', 'jpg', 'jpe')) && Ccsd_File::canConvertImg($filepath)) {return Ccsd_File::convertImg($filepath, $tmpDir);}*/

        else {
            return '';
        }
    }

    /**
     * @param $view
     * @param $type
     * @return string
     */
    public function getDetailledFilesFullBlock($view, $type) {
        $toreturn = "";

        foreach (array_keys($this->_document->getFiles()) as $idx) {
            $toreturn = $toreturn . $this->_status->getStep(Hal_Settings::SUBMIT_STEP_FILE)->renderDetailled($view, $this->_document, $type, $idx);
        }

        return $toreturn;
    }

    /**
     * @param $view
     * @param $type
     * @param $idsx
     * @return array
     */
    public function getDetailledFiles($view, $type, $idsx) {
        $toreturn = [];

        foreach ($idsx as $idx) {
            $toreturn["filerow"][$idx] = $this->_status->getStep(Hal_Settings::SUBMIT_STEP_FILE)->renderDetailled($view, $this->_document, $type, $idx);
        }

        return $toreturn;
    }

    /**
     * @param $idsx
     * @param $filename
     * @param $existMain
     * @return mixed
     */
    public function getSimpleFiles($idsx, $filename, $existMain)
    {
        $idxMain = $idsx[0];

        // Préparation de la réponse pour affichage dans la vue
        $toreturn["existMain"] = $existMain;
        $toreturn["convertedName"] = '';
        $toreturn["idx"] = $idxMain;
        $toreturn["name"] = $filename;
        $toreturn["main"] = $this->_document->getFile($idxMain)->getDefault();

        // Ajout de l'imagette du fichier
        $toreturn["thumb"] = $this->_document->getFile($idxMain)->getTmpThumb();

        // Retour des fichiers pour la vue simplifiée
        array_shift($idsx);

        // En réalité il n'y a qu'1 fichier converti (ça avait été fait lorsque le dézip était automatique)
        foreach ($idsx as $idConverted) {
            $convertedFile = $this->_document->getFile($idConverted);
            $toreturn["converted"][] = ["convertedIdx" => $idConverted,
                "convertedFile" => $convertedFile->getName(),
                "convertedMain" => $convertedFile->getDefault(),
                "convertedThumb" => $convertedFile->getTmpThumb()];

            $toreturn["existMain"] = $toreturn["existMain"] ? : $convertedFile->getDefault();
            $toreturn["convertedName"] = $convertedFile->getName();
        }

        return $toreturn;
    }

    /**
     * @param Hal_View $view
     * @param bool $success
     * @param bool $converted
     * @param bool $compiled
     * @param string $filename
     * @param string $convertedName
     * @return int
     */
    public function prepareFileReturnedMsg($view, $success, $converted, $compiled, $filename, $convertedName)
    {
        /* Ajout d'un message Succès / Echec de la conversion du fichier */
        $view->converted = $converted;
        $view->compiled = $compiled;
        $view->convertedName = $convertedName;

        $view->filename = Ccsd_File::shortenFilename($filename, 50);

        /* Ajout d'un message Succès / Echec de la récupération des métadonnées */
        if ($success) {
            $view->typechosen = $this->_document->getTypDoc();
            $view->pdfMetas = $this->_document->getMetasFromSource('grobid');

            if (empty($view->pdfMetas)) {
                $view->pdfMetas = $this->_document->getMetasFromSource('image');
            }

            $view->doiMetas = $this->_document->getMetasFromSource('doi');

            $view->returncode = 3;

            if (!empty($view->pdfMetas)) {
                $view->returncode = 1;
            }

            if (!empty($view->doiMetas)) {
                $view->returncode = 2;
                $view->doiUrl = $this->_document->getIdsCopy('doi');
            }
        } else {
            $view->returncode = 0;
        }

        return $view->returncode;
    }

    /**
     * @param $view
     * @param $type
     * @param $id
     * @return int
     */
    public function prepareIdReturnedMsg($view, $type, $id)
    {
        /* Ajout d'un message Succès / Echec de la récupération des métadonnées */
        $view->typechosen = $this->_document->getTypDoc();
        $view->idMetas = $this->_document->getMetasFromSource($type);

        $view->returncode = 3;

        if (!empty($view->idMetas)) {
            $view->returncode = 4;
            $view->idUrl = $id;
        }

        return $view->returncode;
    }

    /************* ETAPE METADONNEES *************/

    /**
     * @param $typdoc
     * @param $domains
     * @return Ccsd_Form
     */
    static public function getMetadataForm($typdoc, $domains)
    {
        $form = self::createMetadataForm($typdoc, $domains);
        $form->setAttrib('class', 'form-horizontal');
        $form->setMethod('post');
        $form->setAttrib('enctype', 'multipart/form-data');
        $form->setName('HAL_SUBMIT');

        //Pour les éléments non obligatoires, on ajoute une classe pour l'affichage vue simple / vue détaillée
        /** @var Zend_Form_Element $element */
        foreach ($form->getElements() as $element) {

            if (!$element->isRequired()) {
                $form->getElement($element->getName())->setAttrib('class', 'meta-complete');
            }
        }

        $form->removeDecorator('form');

        return $form;
    }

    /**
     * Création du formulaire des métadonnees
     * @param string $typdoc
     * @param string[] $domains
     * @return Ccsd_Form
     */
    static public function createMetadataForm($typdoc, $domains)
    {
        $form = new Ccsd_Form();
        $list = self::getMetadataList($typdoc, $domains);

        $form->setOptions($list);
        $typeElement = $form->getElement('type');
        if ($typeElement != null) {
            $typeElement->setValue($typdoc);
        } else {
            Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Pas de typeElement type dans formulaire: typdoc=$typdoc et domains=[" . implode(",", $domains). "]");
        }
        return $form;
    }

    /**
     * @param string $typdoc
     * @param string[] $domains
     * @param string[] $section
     * @return array
     */
    static public function getMetadataList($typdoc, $domains, $section = ['section_default' => 'metas'])
    {
        if (empty($typdoc)) {
            $typdoc = 'none';
        }
        $parts = [$typdoc];
        $parts = array_merge($parts, $domains);
        return Hal_Ini::file_merge( [DEFAULT_CONFIG_PATH . '/meta.ini' => $parts, SPACE . CONFIG . 'meta.ini' => $parts], $section);
    }

    /**
     * @param $idType
     * @param $idExt
     * @return string
     */
    static public function getIdUrl($idType, $idExt)
    {
        $idurl = '';

        $class = Hal_Settings::$_idtypeToDataProvider[$idType];
        $config=Hal\Config::getInstance();
        $o = new $class ($idExt, Zend_Db_Table_Abstract::getDefaultAdapter(),$config);

        if (property_exists ( $class, '_URL' )) {
            $idurl = $o->_URL.'/'.$idExt;
        }

        return $idurl;
    }
}
