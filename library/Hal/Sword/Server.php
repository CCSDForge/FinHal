<?php

/**
 * serveur SWORD de l'application HAL
 */

class Hal_Sword_Server
{
    const COMP_GROBID = 'grobid';
    const COMP_IDEXT = 'idext';
    const COMP_AFFILIATION = 'affiliation';

    const LOADFILTER_NOAFFIL = 'noaffiliation';
    const LOADFILTER_FLAG_NOAFFIL = 1;
    /**
     * caractère pour séparer un type de serveur de sa valeur dans l'entête http on-behalf-of
     * eg login|test;uid|1;idhal|mon-idhal-dupond
     */
    const EXTSERVER_SEP = '|';

    /**
     * Type d'identifiants acceptés pour le on-behalf-of
     * @var array
     */
    static public $onBehalf_AllowedExtId = ['login', 'uid', 'idhal', 'orcid'];
    static public $validationErrorOutput = '';

    protected $name = null;
    protected $title = null;
    protected $uri = null;
    protected $email = null;
    protected $link = null;
    protected $server_version = null;
    protected $acceptPackaging = array();
    protected $collection = array('accept' => array(), 'acceptPackaging' => array(), 'title' => '', 'policy' => '', 'abstract' => '', 'treatment' => '');

    private $version = '2.0';            # SWORD version
    private $maxUploadSize = '204800';    # SWORD MAXUPLOADSIZE IN KB
    private $mediation = 'true';        # SWORD On-Behalf-Of
    private $progess = 'false';            # SWORD In-Progress

    private $swordErrors = array(
        'ErrorContent' => array('name' => 'ErrorContent', 'iri' => 'http://purl.org/net/sword/error/ErrorContent', 'description' => 'The supplied format is not the same as that identified in the Packaging header and/or that supported by the server', 'code' => '406 Not Acceptable'),
        'ErrorChecksumMismatch' => array('name' => 'ErrorChecksumMismatch', 'iri' => 'http://purl.org/net/sword/error/ErrorChecksumMismatch', 'description' => 'Checksum sent does not match the calculated checksum', 'code' => '412 Precondition Failed'),
        'ErrorBadRequest' => array('name' => 'ErrorBadRequest', 'iri' => 'http://purl.org/net/sword/error/ErrorBadRequest', 'description' => 'Some parameters sent with the request were not understood', 'code' => '400 Bad Request'),
        'TargetOwnerUnknown' => array('name' => 'TargetOwnerUnknown', 'iri' => 'http://purl.org/net/sword/error/TargetOwnerUnknown', 'description' => 'Used in mediated deposit when the server does not know the identity of the On-Behalf-Of user', 'code' => '403 Forbidden'),
        'MediationNotAllowed' => array('name' => 'MediationNotAllowed', 'iri' => 'http://purl.org/net/sword/error/MediationNotAllowed', 'description' => 'Used where a client has attempted a mediated deposit, but this is not supported by the server', 'code' => '412 Precondition Failed'),
        'MethodNotAllowed' => array('name' => 'MethodNotAllowed', 'iri' => 'http://purl.org/net/sword/error/MethodNotAllowed', 'description' => 'Used when the client has attempted one of the HTTP update verbs (POST, PUT, DELETE) but the server has decided not to respond to such requests on the specified resource at that time', 'code' => '405 Method Not Allowed'),
        'MaxUploadSizeExceeded' => array('name' => 'MaxUploadSizeExceeded', 'iri' => 'http://purl.org/net/sword/error/MaxUploadSizeExceeded', 'description' => "Used when the client has attempted to supply to the server a file which exceeds the server's maximum upload size limit", 'code' => '413 Request Entity Too Large'),
        'Unauthorized' => array('name' => 'Unauthorized', 'iri' => '', 'description' => 'The request was a valid request, but the server is refusing to respond to it', 'code' => '403 Forbidden'),
        'DocError' => array('name' => 'DocError', 'iri' => '', 'description' => 'Document contains error', 'code' => '403 Forbidden'));

    private $uid = 0;
    private $instance = 1;
    private $instanceName = 'hal';
    private $owner = array();
    private $packaging = null;
    /** @var DOMDocument */
    private $content = null;
    /** @var array string[] */
    private $files = array();
    private $xslt = array();

    private $_arxiv = false;
    private $_pmc = false;
    private $_repec = false;
    private $_oai = false;
    private $_completion = false;
    private $_loadFilter = 0;

    private $_type = 'import';

    /**
     * Hal_Sword_Server constructor.
     * @param int $uid
     */
    public function __construct($uid = 0)
    {
        $this->name = 'HAL SWORD API Server';
        $this->title = 'The HAL archive';
        $this->uri = SWORD_API_URL;
        $this->email = 'hal@ccsd.cnrs.fr';
        $this->link = SOLR_API;
        $this->server_version = '1.0';
        $this->collection['accept'] = array('text/xml', 'application/zip');
        $this->collection['acceptPackaging'] = array('http://purl.org/net/sword-types/AOfr', 'http://jats.nlm.nih.gov/publishing/tag-library/');
        $this->collection['title'] = 'HAL';
        $this->collection['policy'] = 'Open Access';
        $this->collection['abstract'] = 'HAL e-print archive portal at ';
        $this->collection['treatment'] = 'Submission will be posted pending moderator approval';
        $this->xslt['http://purl.org/net/sword-types/METSDSpaceSIP'] = 'mets.xsl';
        $this->xslt['http://jats.nlm.nih.gov/publishing/tag-library/'] = 'jats.xsl';

        if ((int)$uid) {
            $this->uid = (int)$uid;
        } else {
            echo $this->error('Unauthorized', 'Basic HTTP Authentification Error');
            exit;
        }
    }

    // Merge de la meta "Keyword" trouvée automatiquement dans la meta à conserver. 
    // La variable @param : $tokeepmetas est modifiée par référence dans la fonction
    /**
     * @param string $code
     * @param string $message
     * @return string
     */
    public function error($code, $message = '')
    {
        if (in_array($code, array_keys($this->swordErrors))) {
            $error = $this->swordErrors[$code];
            header('HTTP/1.1 ' . $error['code']);
        } else {
            $error = null;
            header('HTTP/1.1 400 Bad Request');
        }
        header('Content-Type: text/xml; charset=utf-8');
        try {
            $xml = new Ccsd_DOMDocument('1.0', 'utf-8');
            $root = $xml->createElementNS('http://purl.org/net/sword/error/', 'sword:error');
            $xml->appendChild($root);
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.w3.org/2005/Atom');
            if ($error !== null && $error['iri'] != '') {
                $root->setAttribute('href', $error['iri']);
            }
            $root->appendChild($xml->createElement('title', 'ERROR'));
            $root->appendChild($xml->createElement('updated', date('c')));
            $author = $xml->createElement('author');
            $author->appendChild($xml->createElement('name', $this->name));
            $root->appendChild($author);
            $source = $xml->createElement('source');
            $generator = $xml->createElement('generator', $this->email);
            $generator->setAttribute('uri', $this->uri);
            $generator->setAttribute('version', $this->server_version);
            $source->appendChild($generator);
            $root->appendChild($source);
            if ($error !== null && $error['description'] != '') {
                $root->appendChild($xml->createElement('summary', $error['description']));
            }
            $root->appendChild($xml->createElement('sword:treatment', 'processing failed'));
            $root->appendChild($xml->createElement('sword:verboseDescription', $message));
            $link = $xml->createElement('link');
            $link->setAttribute('rel', 'alternate');
            $link->setAttribute('href', $this->link);
            $link->setAttribute('type', 'text/html');
            $root->appendChild($link);
            $xml->formatOutput = true;
            return $xml->saveXML();
        } catch (Exception $e) {
            error_log($e->getMessage());
            return '';
        }
    }
    /**
     * Handle the SWORD request
     * @param Zend_Controller_Request_Http
     * @param Hal_Site portail de dépôt
     * @return string xml
     * @throws Zend_Controller_Request_Exception
     * @throws Zend_Db_Adapter_Exception
     */
    public function handle(Zend_Controller_Request_Http $request, Hal_Site $website)
    {
        $hasmoved = null;
        // portail de dépôt
        $this->instance = $website->getSid();
        $this->instanceName = $website->getShortName();

        $currentIdent = null;
        // vérification de l'entête In-Progress par rapport à la conf
        $inProgress = (string)$request->getHeader('In-Progress');
        if ($inProgress == 'true' && $this->progess == 'false') {
            echo $this->error('ErrorBadRequest', 'In-Progress header is not supported by the server');
            exit;
        }
        // le contributeur est propriétaire du dépôt SWORD
        $this->owner[] = $this->uid;
        // récupération des co-propriétaires
        $onBehalfOf = (string)$request->getHeader('On-Behalf-Of');
        if ($onBehalfOf !== 'false' && $this->mediation == 'false') {
            echo $this->error('MediationNotAllowed', 'On-Behalf-Of header is not supported by the server');
            exit;
        }

        if ($onBehalfOf) {
            $resOnBehalf = $this->handleOnBehalf($onBehalfOf);
            if (!$resOnBehalf) {
                echo $this->error('TargetOwnerUnknown', 'Username or UID or User External identifier "' . htmlspecialchars(trim($onBehalfOf)) . '" is unknown by the server');
                exit;
            }
            $this->owner = array_merge($this->owner, $resOnBehalf);
            unset($resOnBehalf);
        }
        // Entête spécifique HAL : Export-To-Arxiv, Export-To-PMC, Hide-For-RePEc et Hide-In-OAI
        $this->_arxiv = (string)$request->getHeader('Export-To-Arxiv');
        $this->_pmc = (string)$request->getHeader('Export-To-PMC');
        $this->_repec = (string)$request->getHeader('Hide-For-RePEc');
        $this->_oai = (string)$request->getHeader('Hide-In-OAI');
        $this->_completion = (string)$request->getHeader('X-Allow-Completion');
        $this->_type = (string)$request->getHeader('Submit-Type');
        $loadFilterHeader = (string)$request->getHeader('LoadFilter');

        // Parsing des options
        if (strpos($loadFilterHeader, self::LOADFILTER_NOAFFIL) !== false) {
            $this->_loadFilter |= self::LOADFILTER_FLAG_NOAFFIL;
        }

        // switch suivant verbe HTTP : GET, POST, PUT ou DELETE
        if ($request->isGet()) { // récupération du status de la ressource ArticleStatusStruct
            $identifiant = $request->getParam('identifiant');
            $version = $request->getParam('version', 0);
            if ($identifiant == false) {
                echo $this->error('ErrorBadRequest', 'No data can be found ; Please provide paper Id');
                exit;
            }
            $article = Hal_Document::find(0, $identifiant, $version);
            if ($article === false) {
                echo $this->error('ErrorBadRequest', 'Paper Id can not be found');
                exit;
            }

            $xml = '';
            switch ($article->getStatus()) {
                case Hal_Document::STATUS_VISIBLE :
                    $xml = '<status>accept</status>' . PHP_EOL . '<comment></comment>';
                    break;
                case Hal_Document::STATUS_REPLACED :
                    $xml = '<status>replace</status>' . PHP_EOL . '<comment></comment>';
                    break;
                case Hal_Document::STATUS_BUFFER :
                case Hal_Document::STATUS_TRANSARXIV :
                case Hal_Document::STATUS_VALIDATE :
                case Hal_Document::STATUS_MYSPACE :
                    $xml = '<status>verify</status>' . PHP_EOL . '<comment></comment>';
                    break;
                case Hal_Document::STATUS_MODIFICATION :
                    $xml = '<status>update</status>' . PHP_EOL;
                    $log = Hal_Document_Logger::getLastComment($article->getDocid(), Hal_Document_Logger::ACTION_ASKMODIF);
                    $xml .= '<comment>' . Ccsd_Tools_String::xmlSafe($log) . '</comment>';
                    break;
                case Hal_Document::STATUS_DELETED :
                    $xml = '<status>delete</status>' . PHP_EOL;
                    $log = Hal_Document_Logger::getLastComment($article->getDocid(), Hal_Document_Logger::ACTION_HIDE);
                    $xml .= '<comment>' . Ccsd_Tools_String::xmlSafe($log) . '</comment>';
                    break;
            }

            if ($xml) {
                header('Content-Type: text/xml; charset=utf-8');
                $pwd = '';
                if ($this->uid == $article->getContributor('uid') || in_array($this->uid, $article->getOwner())) {
                    $pwd = 'password' . '="' . Ccsd_Tools_String::xmlSafe($article->getPwd()) . '"';
                }
                echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL . '<document id="' . $article->getId() . '" version="' . $article->getVersion() . '" ' . $pwd . '>' . PHP_EOL . $xml . PHP_EOL . '</document>';
            } else {
                echo $this->error('ErrorBadRequest', 'No data can be found ; paper Id does not match a document');
            }
            exit;
        } else if ($request->isDelete()) { // suppression de la ressource
            $identifiant = $request->getParam('identifiant');
            $version = $request->getParam('version', 0);
            if ($identifiant == null) {
                echo $this->error('ErrorBadRequest', 'No data can be found ; Please provide paper Id');
                exit;
            }
            $article = Hal_Document::find(0, $identifiant, $version);
            if ($article->_docid == 0) {
                echo $this->error('ErrorBadRequest', 'Paper Id can not be found');
                exit;
            }
            if ($article->isOwner($this->uid) && ($article->isFulltext() == false || $article->isOnline() == false)) {
                header('HTTP/1.1 204 No Content');
                $article->delete($this->uid, 'SWORD request', false);
            } else {
                echo $this->error('ErrorBadRequest', 'Paper Id can not be deleted');
            }
            exit;
        } else if ($request->isPut()) { // correction après demande de modif en validation, modification métadonnée, nouvelle version
            $identifiant = $request->getParam('identifiant');
            $version = $request->getParam('version', 0);
            if ($identifiant == null) {
                echo $this->error('ErrorBadRequest', 'No data can be found ; Please provide paper Id');
                exit;
            }
            $article = Hal_Document::find(0, $identifiant, $version);
            if ($article->_docid == 0) {
                echo $this->error('ErrorBadRequest', 'Paper Id can not be found');
                exit;
            }
            if (!(Hal_Document_Acl::canUpdate($article) || Hal_Document_Acl::canModify($article))) {
                echo $this->error('ErrorBadRequest', 'Paper Id can not be updated');
                exit;
            }
            $status = $article -> getStatus();
            // Ne pas accepter de mise a jour pour un papier en moderation ou autre cas penible (par exemple)
            if (( $status ==  Hal_Document::STATUS_TRANSARXIV) || ($status == Hal_Document::STATUS_BUFFER) || ($status == Hal_Document::STATUS_DELETED)) {
                echo $this -> error('ErrorBadRequest', "Paper status prevent it to be update");
                exit;
            }
            $currentIdent = $article -> getId();
            if ( $currentIdent != $identifiant) {
                // Le document est edite avec un vieil identifiant (status 88 par example)
                echo $this -> error('ErrorBadRequest', "Paper has moved to $currentIdent, verify your sync is allready valid.  if yes, change the hal identifier on your side");
                exit;
            }
        } else if ($request->isPost()) { // dépôt
            $article = new Hal_Document();
            $article->setContributor(new Hal_User(array('uid' => $this->uid)));
            $article->setSid($this->instance);
            $article->setInstance($this->instanceName);
        } else {
            echo $this->error('MethodNotAllowed', 'Use GET, POST, PUT or DELETE HTTP verb');
            exit;
        }

        // Arrivee ici seulement apres un PUT ou POST
        // récupération du format XML du dépôt et vérification par rapport à la conf
        $xPackaging = $request->getHeader('Packaging');
        if ($xPackaging === false) {
            $xPackaging = $request->getHeader('X-Packaging');
        }
        if ($xPackaging === false || count($this->collection['acceptPackaging']) == 0 || !in_array($xPackaging, $this->collection['acceptPackaging'])) {
            echo $this->error('ErrorContent', 'The supplied format packaging is not supported by the server');
            exit;
        } else {
            $this->packaging = $xPackaging;
        }
        // récupération du message HTTP : raw data
        $body = $request->getRawBody();
        if ($request->isPut() && @is_file($body)) {
            $body = file_get_contents($body);
        }
        if ($body == false) {
            echo $this->error('ErrorBadRequest', 'No data can be found');
            exit;
        }
        // vérification de la signature si besoin
        $md5 = $request->getHeader('Content-MD5');
        if ($md5 !== false && $md5 != md5($body)) {
            echo $this->error('ErrorChecksumMismatch', 'MD5 sum did not match');
            exit;
        }

        // récuparation du fichier descritif des métas [et des fichiers]
        $mime = $request->getHeader('Content-Type');
        if ($mime === false || count($this->collection['accept']) == 0 || !in_array($mime, $this->collection['accept'])) {
            echo $this->error('ErrorContent', 'The supplied content-type format is not supported by the server');
            exit;
        }

        do {
            $uniqid = 'sword' . uniqid();
        } while (is_dir(PATHTEMPDOCS . $uniqid));
        define('PATHTEMPIMPORT', PATHTEMPDOCS . $uniqid);

        switch ($mime) {
            case 'text/xml' :
                try {
                    $this->content = new DOMDocument();
                    $this->content->substituteEntities = true;
                    $this->content->preserveWhiteSpace = false;
                    set_error_handler('\Ccsd\Xml\Exception::HandleXmlError');
                    $this->content->loadXML($body);
                    restore_error_handler();
                } catch (Exception $e) {
                    echo $this->error('ErrorBadRequest', 'Could not open the XML description: ' . $e->getMessage());
                    exit;
                }
                break;
            case 'application/zip' :
                // En cas de fichier Zip, il faudra traiter les fichiers uploaded dans loadFromTEI
                // Dans le cas d'un fichier XML seul, on ne peut pas faire reference a un nom de fichier qui ne soit pas une URL

                try {
                    // dézippe du fichier
                    mkdir(PATHTEMPIMPORT);
                    $zipfilename = PATHTEMPIMPORT . DIRECTORY_SEPARATOR . 'file.zip';
                    if (!file_put_contents($zipfilename, $body)) {
                        echo $this->error('ErrorBadRequest', 'Could not save the Zip file');
                        exit;
                    }

                    $zip = new ZipArchive;
                    if ($zip->open($zipfilename) === true) {
                        $zip->extractTo(PATHTEMPIMPORT);
                        $zip->close();
                    } else {
                        echo $this->error('ErrorBadRequest', 'Could not open the Zip file');
                        exit;
                    }
                    unlink($zipfilename);
                    // récupération du nom du fichier xml dans le Zip
                    $dispo = $request->getHeader('Content-Disposition');
                    if ($dispo == false || !preg_match('/filename=[\'"]?([\/a-z0-9_-]+\.xml)/i', $dispo, $match)) {
                        echo $this->error('ErrorBadRequest', 'Could not open the XML description: content-disposition header missing or mismatched');
                        exit;
                    }
                    if (!is_file(PATHTEMPIMPORT . DIRECTORY_SEPARATOR . $match[1])) {
                        echo $this->error('ErrorBadRequest', 'Could not open the XML description: ' . $match[1] . ' file not found');
                        exit;
                    }
                    $this->content = new DOMDocument();
                    $this->content->substituteEntities = true;
                    $this->content->preserveWhiteSpace = false;
                    set_error_handler('Ccsd_Xml_Exception::HandleXmlError');
                    $this->content->load(PATHTEMPIMPORT . DIRECTORY_SEPARATOR . $match[1]);
                    restore_error_handler();

                    unlink(PATHTEMPIMPORT . DIRECTORY_SEPARATOR . $match[1]);
                    // récupération des fichiers
                    $dir = new DirectoryIterator(PATHTEMPIMPORT);
                    foreach ($dir as $fileinfo) {
                        if ($fileinfo->isFile()) {
                            $this->files[] = $fileinfo->getFilename();
                        }
                    }

                    //Tri des fichiers pour placer le fichier principal en premiere position (jats -> tei)
                    uasort($this->files, function ($a, $b) {
                        foreach (['/article.*\.pdf$/i', '/\.pdf$/i'] as $regex) {
                            foreach ([$a => -1, $b => 1] as $f => $v) {
                                if (preg_match($regex, $f)) {
                                    return $v;
                                }
                            }
                        }
                        return 0;
                    });
                    $this->files = array_values($this->files);
                } catch (Exception $e) {
                    echo $this->error('ErrorBadRequest', 'Could not read raw data: ' . $e->getMessage());
                    exit;
                }
                break;
            default:
                echo $this->error('ErrorContent', 'The supplied content-type format is not supported by the server');
                exit;
        }

        // this->content xml des métadonnées, this->files les chemins des fichiers
        if ($this->packaging != 'http://purl.org/net/sword-types/AOfr') {
            try {
                $xsl = new DOMDocument();
                $xsl->load(__DIR__ . '/xsl/' . $this->xslt[$this->packaging]);
                $proc = new XSLTProcessor();
                $proc->registerPHPFunctions();
                $proc->setParameter('', 'files', implode(',', $this->files));
                $proc->importStyleSheet($xsl);
                $transfoXML = $proc->transformToXML($this->content);
                if ($transfoXML === false) {
                    echo $this->error('ErrorContent', 'The supplied XML description can not transformed to HAL TEI format');
                    exit;
                }
                $sourceFile = $this->content;
                $this->content = new DOMDocument();
                $this->content->loadXML($transfoXML);
            } catch (Exception $e) {
                echo $this->error('ErrorContent', 'The supplied XML description can not be parsing');
                exit;
            }
        }
        // Validation du XML des métadonnées
        $mapping = array('http://www.w3.org/2001/xml.xsd' => 'xml.xsd');
        libxml_set_external_entity_loader(
            function ($public, $system, $context) use ($mapping) {
                if (isset($mapping[$system])) {
                    return __DIR__ . '/xsd/' . $mapping[$system];
                }
                if (isset($public) && isset($mapping[$public])) {
                    return __DIR__ . '/xsd/' . $mapping[$public];
                }
                if (is_file($system)) {
                    return $system;
                }
                return false;
            }
        );

        if ("" != $this->_completion) {
            //Completion demandée, on utilise le schéma interne
            $schema = 'inner-aofr.xsd';
        } else {
            $schema = 'aofr.xsd';
        }
        try {
            set_error_handler("\Ccsd\Xml\Exception::validateErrorHandler");
            @$this->content->schemaValidate(__DIR__ . '/xsd/' . $schema);
        } catch (\Ccsd\Xml\Exception $e) {
                echo $this->error('ErrorContent', 'The supplied XML description does not validate the AOfr schema...' . "\n\n" . $e->getMessage());
                exit;
        } finally {
            restore_error_handler();
        }
        try {
            $teiOptions = [];
            // Certains site de synchronisation ecrasent joyeusement les affiliation modifiees par les admin pour un retour a leur valeur d'origine.
            // Dans ce cas, on ne load pas les affiliation en provenance de la TEI.
            if ($currentIdent && (($this->_loadFilter & self::LOADFILTER_FLAG_NOAFFIL) || in_array(Hal_Auth::getUid(), []) )) {
                $teiOptions = [Hal_Document_Tei::LOAD_AFFIL_OPT => false];
            }
            // devrait appeler un constructeur plutot que de modifier article deja initialise!!!
            // Avec cela, on ecrase joyeusement toute les initialisation faite...
            $article->loadFromTEI($this->content, PATHTEMPIMPORT, $this->instanceName, $teiOptions);

            $type = Hal_Settings::SUBMIT_INIT;
            if ($article->getDocid() != 0) {
                $formatBeforeUpdate = $article->getFormat();
                $article->initFormat();
                if ($article->getFormat() == Hal_Document::FORMAT_FILE) {
                    if ($formatBeforeUpdate == Hal_Document::FORMAT_NOTICE) {
                        $type = Hal_Settings::SUBMIT_ADDFILE;
                    } else if ($article->getStatus() == Hal_Document::STATUS_VISIBLE) {
                        $type = Hal_Settings::SUBMIT_REPLACE;
                    } else if ($article->getStatus() == Hal_Document::STATUS_MODIFICATION) {
                        $type = Hal_Settings::SUBMIT_MODIFY;
                    }
                } else {
                    $type = Hal_Settings::SUBMIT_UPDATE;
                }
            }
            $article->setTypeSubmit($type);
            $article->setInputType(Hal_Settings::SUBMIT_ORIGIN_SWORD);
            $article->setContributorId($this->uid);

            if ("" != $this->_completion) {

                $identifiers = array();

                // Récupation des autres identifiants externes
                if (strpos($this->_completion, self::COMP_IDEXT) !== false)
                    $identifiers = $article->getMeta('identifier');

                // Récupération de l'identifiant dans le cas du PDF
                if (strpos($this->_completion, self::COMP_GROBID) !== false) {
                    foreach ($article->getFiles() as $file) {
                        if ($file->getType() == Hal_Settings::FILE_TYPE) {
                            $identifiers['pdf'] = $file->getPath();
                        }
                    }
                }


                // COMPLETION DES METADONNEES
                // On merge les metas récupérée par le service de Meta avec les metas actuelles (qui priment s'il y a redondance)
                $metas = $this->getAutoCompletedMetas($identifiers);

                if (isset($metas) && isset($metas['metas']) && is_array($metas['metas'])) {

                    $allMetas = $article->getMeta();

                    // Merge à un niveau plus bas des Mots-Clés mais pas de l'ensemble des metas (on ne voudrait pas merger les titres par e.g c'est pouquoi on utilise pas array_merge_recursive)
                    $this->mergeKeywordMetas($metas[Ccsd_Externdoc::META], $allMetas);
                    $allMetas = array_merge($metas[Ccsd_Externdoc::META], $allMetas);

                    $article->setMetas($allMetas);
                }

                // COMPLETION DES AUTEURS
                // * Si aucun auteurs renseignés => on en cherche
                // * Si 1 auteur renseigné => on en cherche d'autres
                // * Si plusieurs auteurs rensignés => on en cherche pas plus

                $authorArticle = $article->getAuthors();

                if (count($authorArticle) < 2) {
                    if (isset($metas) && isset($metas['authors'])) {
                        $authors = $this->getAutoCompletedAuthors($metas);

                        foreach ($authors as $docAuthor) {

                            //Si l'auteur principal est déjà présent on ne le rajoute pas : Même nom de famille, même prénom ou même initiale
                            if (array_key_exists(0, $authorArticle) && $docAuthor->isConsideredSameAuthor($authorArticle[0])) {
                                continue;
                            }
                            $article->addAuthor($docAuthor);
                        }
                    }
                }

                // Auto complétion des affiliations
                if (strpos($this->_completion, self::COMP_AFFILIATION) !== false) {
                    for ($idx = 0; $idx < count($article->getAuthors()); $idx++) {
                        $author = $article->getAuthor($idx);
                        if (!$author->isAffiliated()) {
                            $this->addAuthorAffiliation($article, $idx, $author);
                        }
                    }
                }
            }

            if (is_array($this->owner) && count($this->owner)) {
                $article->setOwner($this->owner);
            }
            if ($article->getTypeSubmit() != Hal_Settings::SUBMIT_UPDATE) {
                if ($this->_arxiv == 'true') {
                    $article->setTransfertArxiv(true);
                }
                if ($this->_pmc == 'true') {
                    $article->setTransfertPMC(true);
                }
                if ($this->_oai == 'true') {
                    $article->setHideOAI(true);
                }
                if ($this->_repec == 'true') {
                    $article->setHideRePEc(true);
                }
            }
            // Un deuxieme, on est jamais trop prudent!
            $article->initFormat();
            try {
                $docValid = Hal_Document_Validity::isValid($article);
                $article = Hal_Document_Validity::eraseIncompatibleMetas($article);
            } catch (Hal_Document_Exception $halDocExc) {
                echo $this->error('ErrorBadRequest', json_encode($halDocExc->getErrors()));
                error_log(__METHOD__ . ': ' . $halDocExc->getTraceAsString());
                exit;
            }

            //Sauvegarde des fichiers provenant de Prodinra
            if ($this->uid == 132775) {
                $bkpDir = PATHTEMPDOCS . 'sword-backup/';
                if (!is_dir($bkpDir)) {
                    mkdir($bkpDir);
                }
                $this->content->save($bkpDir . 'prodinra-' . time() . '.xml');
            }

            if ($docValid === true) {
                $doublons = Hal_Document_Doublonresearcher::doublon($article);
                if (count($doublons)) {

                    $identifiant = $this->NoticeFromAnArrayOfDocids($doublons);

                    //Si Dépôt fulltext + 1 notice + Pas de FullText en attente
                    if (count($article->getFiles()) != 0 && $identifiant != '' && !Hal_Document::VerifyFulltextWaiting($identifiant)) {
                        $artN = $article;
                        $article = Hal_Document::find(0, $identifiant);
                        $format = $article->getFormat();

                        /** @var Hal_Document_Metadatas $articlemeta */
                        $articlemeta = $artN->getHalMeta();
                        /** @var Hal_Document_Metadatas $artmeta */
                        $artmeta = $article->getHalMeta();

                        //On complète les métadonnées
                        $artmeta->merge($articlemeta);

                        //On ajoute les fichiers dans la notice
                        foreach(($artN->getFiles()) as $file){
                            $article->addFile($file);
                        }

                        //On fait les modifications en base
                        if ($format == Hal_Document::FORMAT_NOTICE) {
                            $article->setTypeSubmit(Hal_Settings::SUBMIT_ADDFILE);
                        } else {
                            $article->setTypeSubmit(Hal_Settings::SUBMIT_ADDANNEX);
                        }
                    } else {
                        echo $this->error('ErrorBadRequest', Zend_Json::encode(['duplicate-entry' => $doublons]));
                        exit;
                    }
                }

                // JB correction ticket #61 gitlab
                // La sauvegarde du fichier ne transmettait pas l'information du déposant
                // Donc l'information ne pouvait être logger en fin de chaîne
                // Et c'était du coup toujours le contributeur qui était loggé

                $article->save($this->uid);

                if ($article->getDocid() && count($article->getCollections())) {
                    /** @var Hal_Site_Collection $col */
                    foreach ($article->getCollections() as $col) {
                        Hal_Document_Collection::add($article->getDocid(), $col, $this->uid, false);
                    }
                }
                if (count($this->owner)) {
                    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
                    foreach ($this->owner as $uid) {
                        try {
                            $db->delete(Hal_Document_Owner::TABLE, 'IDENTIFIANT = "' . $article->getId(false) . '" AND UID = ' . (int)$uid);
                            $bind = array('IDENTIFIANT' => $article->getId(false), 'UID' => $uid);
                            $db->insert(Hal_Document_Owner::TABLE, $bind);
                        } catch (Exception $exception) {
                            error_log('Error with owner jobs: ' . $exception->getMessage());
                        }
                    }
                }
                //postsave
                Hal_Submit::postSave($this->uid, $article);
            }
        } catch (Exception $e) {
            error_log('SWORD error: ' . $e->getTraceAsString());
            echo $this->error('', 'Internal server error' . ": " . $e ->getMessage());
            exit;
        }

        if ($article->getDocid()) {
            header('Location: ' . $website->getUrl() . '/' . $article->getId(true));

            if ($type == Hal_Settings::SUBMIT_UPDATE) {
                header('HTTP/1.1 200 OK');
            } else if ($article->getFormat() == Hal_Document::FORMAT_NOTICE) {
                header('HTTP/1.1 202 Accepted');
            } else {
                header('HTTP/1.1 201 Created');
            }
            echo $this->entry($article->getId(), $article->getPwd(), $article->getVersion(), $website->getUrl() . '/' . $article->getId(true));

            if (isset($sourceFile) && in_array($this->uid, [301560, 151596, 194338, 326461])) {
                //Sauvegarde des fichiers XML pour les dépôts des comptes edpsciences, bmc, bmc-cea, bmc-sword
                $bkpDir = PATHTEMPDOCS . 'sword-backup/';
                if (!is_dir($bkpDir)) {
                    mkdir($bkpDir);
                }
                $sourceFile->save($bkpDir . $this->uid . '-' . $article->getId() . 'v' . $article->getVersion() . '.xml');
            }

            exit;
        } else {
            echo $this->error('', "Can't save data");
            exit;
        }
    }

    /**
     * Gère l'entête On-Behalf-Of
     * Dépôt pour le compte d'une autre personne, via son login, UID, ou un de ses identifiants extérieurs
     * @param string $onBehalfOf
     * @return array|bool
     */
    public function handleOnBehalf($onBehalfOf = '')
    {
        if (!$onBehalfOf) {
            return false;
        }

        $userInfo = $this->processOnBehalfOfHeader($onBehalfOf);

        $owners = [];
        $userMapper = new Ccsd_User_Models_UserMapper();
        $userUID = false;


        foreach ($userInfo as $info) {

            // cas 1/2 aucun service spécifié : login ou UID
            if (count($info) == 1) {
                $userResult = $userMapper->findByUsernameOrUID($info[0]);
                if ($userResult == null) {
                    continue;
                }
                $userUID = (int)$userResult->current()->toArray()['UID'];
            } else {

                //cas 2/2 : où un service est associé à l'identifiant externe

                if (!$this->isAllowedExternalIdentifier(strtolower($info[0]))) {
                    echo $this->error('TargetOwnerUnknown', 'Username or UID or User External identifier Type: "' . htmlspecialchars($info[0]) . '" is unknown by the server');
                    exit;
                }

                switch ($info[0]) {
                    case 'idhal':
                        // cherche un idhal : $info[0]
                        $userUID = Hal_User::getUidFromIdHalUri($info[1]);
                        break;
                    case 'uid':
                        //break omitted
                    case 'login':
                        $userResult = $userMapper->findByUsernameOrUID($info[1]);
                        if ($userResult == null) {
                            continue;
                        }
                        $userUID = (int)$userResult->current()->toArray()['UID'];
                        break;
                    default:
                        // cherche l'identifiant $info[1]  pour le service : $info[0]
                        $userUID = Hal_User::getUidFromIdExt($info[1], $info[0]);
                        break;
                }


                if (!$userUID) {
                    continue;
                }
            }

            if ($userUID) {
                $owners[] = $userUID;
                $userUID = false;
            }
        }

        if (count($owners) == 0) {
            echo $this->error('TargetOwnerUnknown', 'Username or UID or User External identifier "' . htmlspecialchars(trim($onBehalfOf)) . '" is unknown by the server');
            exit;
        }

        return array_unique($owners);
    }

    /**
     * @param string $onBehalfOf
     * @return array[]
     */
    public static function processOnBehalfOfHeader($onBehalfOf)
    {

        $userInfo = explode(';', $onBehalfOf);
        $userInfo = array_unique($userInfo);
        $userInfo = array_map('trim', $userInfo);
        $infoMap=[];
        foreach ($userInfo as $info) {
            $infoMap[] = explode(static::EXTSERVER_SEP, $info);

        }
        return $infoMap;
    }

    /**
     * @param string $identifier  ??
     * @return bool
     */
    public function isAllowedExternalIdentifier($identifier)
    {

        if (!in_array($identifier, static::getOnBehalfAllowedExtId())) {
            return false;
        }
        return true;
    }

    // TO DO : clean en utilisant Hal_Document::addAuthorWithAffiliations

    /**
     * @return array
     */
    static public function getOnBehalfAllowedExtId()
    {
        return self::$onBehalf_AllowedExtId;
    }

    /**
     * @param int[] $ids
     * @return array
     * @uses Ccsd_Dataprovider_Arxiv
     * @uses Ccsd_Dataprovider_Crossref
     * @uses Ccsd_Dataprovider_Datacite
     * @uses Ccsd_Dataprovider_Ird
     * ...
     */
    private function getAutoCompletedMetas($ids)
    {
        $metas = array();

        foreach ($ids as $id => $value) {

            // On récupère le dataprovider correspondant au type d'identifiant envoyé
            $class = Hal_Settings::$_idtypeToDataProvider[$id];
            $config = \Hal\Config::getInstance();
            if (class_exists ($class)) {
                /** @var Ccsd_Dataprovider $o */
                $o = new $class(Zend_Db_Table_Abstract::getDefaultAdapter(), $config);

                // Si la méthode getDocument retourne null, on peut récupérer l'erreur mais il ne s'agit pas d'une erreur majeure
                // On ne renvoie donc pas d'erreur HTTP
                $o = $o->getDocument($value);

                /* @var Ccsd_Externdoc $o*/

                if (isset($o)) {

                    $fullMetas = $o->getMetadatas();
                    // Merge à un niveau plus bas des Mots-Clés mais pas de l'ensemble des metas (on ne voudrait pas merger les titres par e.g c'est pouquoi on utilise pas array_merge_recursive)
                    $this->mergeKeywordMetas($fullMetas[Ccsd_Externdoc::META], $metas[Ccsd_Externdoc::META]);

                    $metas[Ccsd_Externdoc::META] = array_merge($fullMetas[Ccsd_Externdoc::META], $metas[Ccsd_Externdoc::META]);

                    $metas[Ccsd_Externdoc::AUTHORS] = array_merge($fullMetas[Ccsd_Externdoc::AUTHORS], $metas[Ccsd_Externdoc::AUTHORS]);
                }
            }
        }
        return $metas;
    }

    /**
     * SWORD service document
     * @param Ccsd_Externdoc[] $tomergemetas
     * @param Ccsd_Externdoc[] $tokeepmetas
     */

    private function mergeKeywordMetas($tomergemetas, &$tokeepmetas)
    {
        // Récupération de la langue des metas à merger
        $lang = $tomergemetas[Ccsd_Externdoc::META_LANG];
        if (gettype($lang) == 'array') {
            $lang = $tomergemetas[Ccsd_Externdoc::META_LANG][0];
        }
        // Merge spécifique des keywords
        if (isset($tomergemetas[Ccsd_Externdoc::META_KEYWORD]) && isset($tokeepmetas[Ccsd_Externdoc::META_KEYWORD])) {
            $tokeepmetas[Ccsd_Externdoc::META_KEYWORD][$lang] = array_merge($tomergemetas[Ccsd_Externdoc::META_KEYWORD][$lang], $tokeepmetas[Ccsd_Externdoc::META_KEYWORD][$lang]);
        }
    }

    /**
     * @param Ccsd_Externdoc[] $metas
     * @return Hal_Document_Author[]
     * @throws Exception
     */
    private function getAutoCompletedAuthors($metas)
    {
        $authors = array();
        foreach ($metas['authors'] as $author) {
            if (isset($author['lastname']) && trim($author['lastname']) != '' && isset($author['firstname']) && trim($author['firstname']) != '') {
                $authorid = Hal_Document_Author::find($author['lastname'], $author['firstname'], Ccsd_Tools::ifsetor($author['email'], ''));

                if ($authorid) {
                    $docAuthor = new Hal_Document_Author($authorid);
                } else {
                    $docAuthor = new Hal_Document_Author();
                    $docAuthor->set($author);
                }

                $authors[] = $docAuthor;
            }
        }

        return $authors;
    }

    /**
     * @param Hal_Document $article
     * @param int $authoridx
     * @param Hal_Document_Author $docAuthor
     */
    protected function addAuthorAffiliation($article, $authoridx, $docAuthor)
    {
        if ($authoridx !== false) {
            $structids = $docAuthor->getLastStructures();

            foreach ($structids as $structid) {
                //On ajoute les affiliations de l'auteur en vérifiant que les structures ne sont pas déjà associés au dépôt
                $add = true;
                foreach ($article->getStructures() as $idx => $structure) {
                    if ($structure->getStructid() != 0 && $structure->getStructid() == $structid) {
                        $article->getAuthor($authoridx)->addStructidx($idx);
                        $add = false;
                        break;
                    }
                }
                if ($add) {
                    $docStructure = new Hal_Document_Structure();
                    if ($docStructure->loadFromSolr($structid)) {
                        $idx = $article->addStructure($docStructure);
                        if ($idx !== false) {
                            $article->getAuthor($authoridx)->addStructidx($idx);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $identifiant
     * @param string $pwd
     * @param string $version
     * @param string $url
     * @return string
     */
    public function entry($identifiant, $pwd, $version, $url)
    {
        header('Content-Type: application/atom+xml');
        try {
            $xml = new Ccsd_DOMDocument('1.0', 'utf-8');
            $root = $xml->createElement('entry');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.w3.org/2005/Atom');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:sword', 'http://purl.org/net/sword/terms/');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dcterms', 'http://purl.org/dc/terms/');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:hal', 'http://hal.archives-ouvertes.fr/');
            $xml->appendChild($root);
            $root->appendChild($xml->createElement('title', 'Accepted media deposit to HAL'));
            $root->appendChild($xml->createElement('id', $identifiant));
            $cdata   = $xml->createCDATASection($pwd);
            $pwdElem = $xml->createElement('hal:password', '');
            $pwdElem -> appendChild($cdata);
            $root->appendChild($pwdElem);
            $root->appendChild($xml->createElement('hal:version', $version));
            $root->appendChild($xml->createElement('updated', date('c')));
            $root->appendChild($xml->createElement('summary', 'A media deposit was stored in the HAL workspace'));
            $root->appendChild($xml->createElement('sword:treatment', 'stored in HAL workspace'));
            $root->appendChild($xml->createElement('sword:userAgent', $this->name));
            $source = $xml->createElement('source');
            $generator = $xml->createElement('generator', $this->email);
            $generator->setAttribute('uri', $this->uri);
            $generator->setAttribute('version', $this->server_version);
            $source->appendChild($generator);
            $root->appendChild($source);
            $link = $xml->createElement('link');
            $link->setAttribute('rel', 'alternate');
            $link->setAttribute('href', $url);
            $root->appendChild($link);
            $xml->formatOutput = true;
            return $xml->saveXML();
        } catch (Exception $e) {
            return '';
        }
    }

    public function ServiceDocument()
    {
        if (count($this->collection['accept']) && count($this->collection['acceptPackaging']) && $this->collection['title']) {
            $xml = new Ccsd_DOMDocument('1.0', 'utf-8');
            $root = $xml->createElement('service');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.w3.org/2007/app');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:atom', 'http://www.w3.org/2005/Atom');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:sword', 'http://purl.org/net/sword/terms/');
            $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:dcterms', 'http://purl.org/dc/terms/');
            $xml->appendChild($root);
            $root->appendChild($xml->createElement('sword:version', $this->version));
            $root->appendChild($xml->createElement('sword:maxUploadSize', $this->maxUploadSize));
            $workspace = $xml->createElement('workspace');
            $workspace->appendChild($xml->createElement('atom:title', $this->title));

            // chaque portail est une collection
            $instances = Hal_Site_Portail::getInstances();
            foreach ($instances as $instance) {
                $collection = $xml->createElement('collection');
                $collection->setAttribute('href', $this->uri . '/' . $instance['SITE']);
                $collection->appendChild($xml->createElement('atom:title', $instance['NAME']));
                foreach ($this->collection['accept'] as $accept) {
                    $collection->appendChild($xml->createElement('accept', $accept));
                }
                $collection->appendChild($xml->createElement('sword:collectionPolicy', $this->collection['policy']));
                $collection->appendChild($xml->createElement('dcterms:abstract', $this->collection['abstract'] . $instance['URL']));
                $collection->appendChild($xml->createElement('sword:mediation', $this->mediation));
                $collection->appendChild($xml->createElement('sword:treatment', $this->collection['treatment']));
                foreach ($this->collection['acceptPackaging'] as $packaging) {
                    $collection->appendChild($xml->createElement('sword:acceptPackaging', $packaging));
                }
                $workspace->appendChild($collection);
            }

            $root->appendChild($workspace);

            $xml->formatOutput = true;
            header('Content-Type: application/atomsvc+xml; charset=utf-8');
            echo $xml->saveXML();
        } else {
            echo $this->error('Unknown', 'SWORD Server configuration error');
        }
        exit;
    }

    /**
     * Retourne la notice la plus ancienne si c'est un tableau de notice
     * @param $docids array Tableau de docid
     * @return string|false
     */
    public function NoticeFromAnArrayOfDocids ($docids)
    {
        $noticeid = '';
        $date = date("Y-m-d H:i:s");
        foreach ($docids as $identifiant => $v){
            $doc = Hal_Document::find(0, $identifiant);

            // @TODO si $doc est null, alors ne doit on pas faire un continue plutot qu'un return ?
            if (!$doc || count($doc->getFiles())){ // Au moins un des documents est un fulltext
                return false;
            } else { //C'est une notice
                if ($date > $doc->getSubmittedDate()){
                    $date = $doc->getSubmittedDate();
                    $noticeid = $identifiant;
                }
            }
        }
        return $noticeid;
    }
}
