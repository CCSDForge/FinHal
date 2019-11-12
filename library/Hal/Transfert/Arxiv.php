<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 18/09/17
 * Time: 14:25
 */

/**
 * Class Hal_Transfert_Arxiv
 *
 * Need the constant:ARXIV_SERVER ARXIV_SERVICE  ARXIV_USER ARXIV_PWD to be define!
 */

class Hal_Transfert_Arxiv extends Hal_Transfert {

    const ERROR_RESUME   = 'resume';
    const ERROR_NOSOURCE = 'filenosource';
    const ERROR_FILESIZE = 'filesize';
    const ERROR_NOBBL    = 'nobbl';
    const ERROR_DOMAIN   = 'domain';
    const MAX_SIZE_FILE  =  3000000; //  3 Mo
    const TOTAL_MAX_SIZE = 10000000; // 10 Mo

    const TABLE_DOMAIN_ARXIV = 'REF_DOMAIN_ARXIV';
    static public $IDCODE = 'arxiv'; /* code dans la table  DOC_HASCOPY */

    protected $urlService = ARXIV_SERVICE;
    protected $user       = ARXIV_USER;
    protected $pwd        = ARXIV_PWD;

    protected $filenamePrefix = "arxiv-";
    /** @var Hal_Document */
    protected $document = null;
    protected $documentCategories = null;

    protected $name       = "arXiv";

    protected $loginUrl   = 'https://arxiv.org/user/login';
    protected $cookieJar  = '/tmp/cookie.txt';

    protected $deleteUrl  = 'https://arxiv.org/user/%s/delete';

    static public $TABLE        = 'DOC_IDARXIV';
    static protected $EXTFIELDNAME = 'ARXIVID';
    static protected $zipExcludeRe = '/(\.bib|\.log|\.zip)$/';

    public $remoteIdTag     = 'arxiv_id';
    public $statusTag       = 'status';
    public $submissionIdTag = 'submission_id';
    public $commentTag      = 'foovalue';
    public $trackingIdTag   = 'tracking_id';

    /** Constructeur from a array (ex: from a row db)
     * @param  array $row
     * @return Hal_Transfert_Arxiv
     */
    static protected function array2obj($row) {
        return new Hal_Transfert_Arxiv($row['DOCID'],$row['ARXIVID'],$row['PENDING']);
    }

    /**
     * Hal_Transfert constructor from a document
     *     Return Transfert Object if present in database
     *     For creating a new transfert, use
     * @see init_transfert
     * @param Hal_Document $document
     * @return Hal_Transfert_Arxiv|false
     */
    static public function transfert($document) {
        $o = new static();
        /** @var  Hal_Transfert_Arxiv $o */
        $o -> document = $document;
        $o -> _docid = $document->getDocid();
        // Load info de transfert
        if ($o -> load($document -> getDocid()) !== false) {
            $o -> _collection = $o -> getSwordCollection();
            list($o -> serviceUrl, $o -> method) = $o -> getServiceUrl();
            return $o;
        } else {
            return false;
        }
    }

    /**
     * Hal_Transfert constructor from a document
     * @param Hal_Document $document
     * @return Hal_Transfert_Arxiv|bool
     */
    static public function init_transfert($document) {
        $o = new static();
        /** @var  Hal_Transfert_Arxiv $o */
        $o -> document = $document;
        $o -> _docid = $document->getDocid();
        // Load info de transfert
        if ($o -> load($document -> getDocid()) !== false) {
            $o->_collection = $o->getSwordCollection();
            list($o->serviceUrl, $o->method) = $o->getServiceUrl();
            $o ->loaded = true;
        }
        return $o;
    }

    /**
     * @param Ccsd_Sword_Client $sword
     * @return array
     * @throws Hal_Transfert_Exception
     */
    private function sendData($sword) {
        // Envoie du fichier d'upload
        $related = [];
        try {
            $swordentry = $sword->deposit($this -> user, $this -> pwd);
        } catch ( Exception $e ) {
            throw new Hal_Transfert_Exception( Hal_Transfert_Response::INTERNAL, 'Uploaded "'.$sword->getFile().'" file failed ('.$e->getMessage().')...');
        }

        if ( is_a($swordentry, 'Ccsd_Sword_Entry' )) {
            // Retour coherent
            if ( is_a ($swordentry , 'Ccsd_Sword_Errordocument' )) {
                // Mais c'est une erreur
                throw new Hal_Transfert_Exception( Hal_Transfert_Response::INTERNAL, '['.$swordentry->status.'] '.$swordentry->summary);
            }
            // On s'attends a avoir qq chose
            if ($swordentry->content_src != '' && $swordentry->content_type != '' ) {
                $related[] = array('type' => $swordentry->content_type, 'href' => $swordentry->content_src);
            } else {
                throw new Hal_Transfert_Exception(  Hal_Transfert_Response::INTERNAL,
                    '[' . $swordentry->status . '] ' . $swordentry->statusmessage);
            }
        } else {
            // Meme pas un Sword_entry...
            throw new Hal_Transfert_Exception(  Hal_Transfert_Response::INTERNAL,
                'Uploaded "'.$sword->getFile().'" file failed...');
        }
        return $related;
    }
    /**
     * @param Ccsd_Sword_Client $sword
     * @return array
     * @throws Hal_Transfert_Exception
     */
    private function sendAtom($sword)
    {
        try {
            $swordentry = $sword->deposit($this->user, $this->pwd, $this->method);
        } catch ( Exception $e ) {
            throw new Hal_Transfert_Exception( Hal_Transfert_Response::INTERNAL,  'Uploaded entry file failed ('.$e->getMessage().')...');
        }
        if ( is_a($swordentry , 'Ccsd_Sword_Entry' )) {
            // retour coherent
            if ( is_a($swordentry , 'Ccsd_Sword_Errordocument' )) {
                // Mais c'est une erreur
                 throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, '['.$swordentry->status.'] '.$swordentry->summary, $swordentry->xml);
            }
            // On s'attends a avoir qq chose
            if (isset($swordentry->alternate) && $swordentry->alternate != '' &&
                isset($swordentry->edit) && $swordentry->edit != '' ) {
                return array($swordentry -> edit, $swordentry->alternate);
            } else {
                throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL,'Uploaded entry file failed, no resolve URL...', $swordentry->xml);
            }
        } else {
            throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, 'Uploaded metadata failed...');
        }
    }

    /**
     * @return string
     */
    protected function getSwordCollection() {
        $document = $this -> document;
        return Hal_Arxiv::document2archive($document);
    }

    /**
     * @return array
     */
    protected function getServiceUrl() {
        // SWORD POST|PUT-ing metadata of the media via the "related" link
        $document = $this->document;
        $collection = $this -> getSwordCollection();
        $externId = $this->isAllreadyOn();
        if ( $document->getVersion() == 1 || $externId === false ) {
            $url = $this->urlService . $collection.'-collection';
            $method = 'POST';
        } else {
            $url = $this->urlService.'edit/'.$externId;
            $method = 'PUT';
        }
        return array($url, $method);
    }
    /**
     * @param $fSrc Hal_Document_File[]
     * @throws Hal_Arxiv_Exception
     * @return array
     */
    /* Private but public for tests... */
    public function getSwordInfo($fSrc) {
        $document = $this->document;

        # Renvoie un tableau [ 'setFile' => val , 'setHeaders' => val ]
        # Leve une exception HAL_Arxiv_Exception
        if ((  count($fSrc) == 1 && in_array($fSrc[0]->getExtension(), ['rtf', 'docx', 'doc']) ) ||
            (! count($fSrc)))
        {
            # Le fichier source n'est pas du latex
            # On envoie que le PDF
            # Ou bien pas de fichier source car seulement un PDF
            return array('setFile' => $document->getDefaultFile()->getPath() ,
                         'setHeaders' => 'Content-Type: application/pdf');
        } else {
            # Fichier source latex/zip (au moins un fichier)
            $zipfilename = PATHTEMPDOCS . 'arxiv-' . $document->_docid . '.zip';
            self::create_zip($zipfilename, $fSrc); # Exception de create_zip non traitee
            return array('setFile' => $zipfilename,
                         'setHeaders' => 'Content-Type: application/zip');
        }
    }

    /**
     * @param array $medias
     * @return string
     */
    public function getArxivSwordEntry($medias = array())
    {

        $document = $this->document;
        if ($document->_docid != 0 && count($medias)) {
            $xml = new Ccsd_DOMDocument('1.0', 'utf-8');
            $xml->formatOutput = true;
            $xml->substituteEntities = true;
            $xml->preserveWhiteSpace = false;
            $entry = $xml->createElement('entry');
            $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.w3.org/2005/Atom');
            $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:arxiv', 'http://arxiv.org/schemas/atom/');
            $xml->appendChild($entry);
            $entry->appendChild($xml->createElement('id', $document->_identifiant));
            $entry->appendChild($xml->createElement('updated', $document->getSubmittedDate('c')));
            $author = $xml->createElement('author');
            // TODO Need to be in conf file!
            $author->appendChild($xml->createElement('name', 'HAL'));
            $author->appendChild($xml->createElement('email', 'hal@ccsd.cnrs.fr'));
            $entry->appendChild($author);
            $title = $document->getTitle('en');
            if ($title == '') {
                foreach ($document->getTitle() as $t) {
                    $title = $t;
                    break;
                }
            }
            $entry->appendChild($xml->createElement('title', str_replace("&lt;", "<", strip_tags(str_replace("<", "&lt;", Ccsd_Tools::decodeLatex($title))))));
            $entry->appendChild($xml->createElement('summary', str_replace("&lt;", "<", strip_tags(str_replace("<", "&lt;", Ccsd_Tools::decodeLatex($document->getAbstract('en')))))));
            /** @var $author Hal_Document_Author */
            foreach ($document->getAuthors() as $author) {
                if ($author->getFullname()) {
                    $contrib = $xml->createElement('contributor');
                    $contrib->appendChild($xml->createElement('name', Ccsd_Tools::decodeLatex($author->getFullname())));
                    if ($author->getEmail()) {
                        $contrib->appendChild($xml->createElement('email', $author->getEmail()));
                    }
                    $aff = [];
                    foreach ($author->getStructid() as $s) {
                        $sigle = (new Ccsd_Referentiels_Structure($s))->getSigle();
                        if ($sigle) {
                            $aff[] = $sigle;
                        }
                    }
                    if (count($aff)) {
                        $contrib->appendChild($xml->createElement('arxiv:affiliation', Ccsd_Tools::decodeLatex(implode(',', $aff))));
                    }
                    $entry->appendChild($contrib);
                }
            }
            $i = 0;
            foreach ($this -> getArxivCategories() as $code => $category) {
                $cat = $xml->createElement(($i == 0) ? 'arxiv:primary_category' : 'category');
                $cat->setAttribute('scheme', 'http://arxiv.org/terms/arXiv/');
                $cat->setAttribute('label', $category);
                $cat->setAttribute('term', 'http://arxiv.org/terms/arXiv/' . $category);
                $entry->appendChild($cat);
                if ($i++ > 3) { // 4 domaines max sur arXiv
                    break;
                }
            }
            $comment = $document->getHalMeta()->getMeta('comment');
            $localRef = trim(implode(' ', $document->getHalMeta()->getMeta('localReference')));
            $comment  = str_replace("&lt;", "<", strip_tags(str_replace("<", "&lt;", Ccsd_Tools::decodeLatex($comment))));
            $reportNo = str_replace("&lt;", "<", strip_tags(str_replace("<", "&lt;", Ccsd_Tools::decodeLatex($localRef))));
            $entry->appendChild($xml->createElement('arxiv:comment', $comment));
            $entry->appendChild($xml->createElement('arxiv:report_no', $reportNo));
            if (in_array($document->getTypDoc(), ['ART', 'COMM', 'PRESCONF', 'OUV', 'COUV'])) {
                $entry->appendChild($xml->createElement('arxiv:journal_ref', trim(Ccsd_Tools::decodeLatex(strip_tags(str_replace(["&lt;", "&gt;"], ["<", ">"], $document->getCitation()))), " ,.;\t\n\r\0\x0B")));
            }
            $entry->appendChild($xml->createElement('arxiv:doi', $document->getHalMeta()->getMeta('doi')));
            foreach ($medias as $media) {
                if (isset($media['type']) && isset($media['href'])) {
                    $link = $xml->createElement('link');
                    $link->setAttribute('href', $media['href']);
                    $link->setAttribute('type', $media['type']);
                    $link->setAttribute('rel', 'related');
                    $entry->appendChild($link);
                }
            }
            return $xml->saveXML();
        }
        return '';
    }


    /**
     * Return sword client with media file
     * @return Ccsd_Sword_Client
     * @throws Hal_Transfert_Exception
     */
    private function getSwordMedia() {
        $document   = $this -> document;
        $collection = $this -> _collection;
        $sword = new Ccsd_Sword_Client($this -> urlService.$collection.'-collection');
        $sword->setOBO($document->getFromNormalized());
        $sword->setHeaders('X-Packaging: http://purl.org/net/sword-types/bagit');
        if ( $document->getFileNb() ) {
            /** @var $fSrc Hal_Document_File[] */
            $fSrc = $document->getFilesByType('src');
            $swordInfo = $this->getSwordInfo($fSrc);
            $sword->setFile($swordInfo['setFile']);
            $sword->setHeaders($swordInfo['setHeaders']);
        } else {
            throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, 'No file to submit...');
        }
        return $sword;
    }
    /** Return the Atom file
     * @param string       $url
     * @param array        $related
     * @return Ccsd_Sword_Client
     * @throws Hal_Transfert_Exception
     */
    private function  getSwordMetadata($url, $related) {
        $document = $this -> document;
        $sword = new Ccsd_Sword_Client($url);
        $sword->setOBO($document->getFromNormalized());
        $sword->setHeaders('X-Packaging: http://purl.org/net/sword-types/bagit');
        $sword->setHeaders('Content-Type: application/atom+xml;type="entry";charset="utf-8"');
        $entry = $this -> getArxivSwordEntry($related);
        if ( $entry == '' ) {
            throw new Hal_Transfert_Exception(Hal_Transfert_Response::INTERNAL, 'Cannot create entry file...');
        }
        $sword->setFile($entry);
        return $sword;
    }

    /**
     * @param Hal_Document_File $file
     * @return bool
     */
    static private function isALatexPdfFile($file) {
        if ($file->getTypeMIME() == 'application/pdf'){
            $infofile = Hal_Document_File::get_PDF_info($file->getPath());
            // La determination se fait pour l'instant seulement avec la presence de font de type 1 ou 1C
            if (array_key_exists('Fonts', $infofile)
                && (   array_key_exists ("Type 1C" , $infofile['Fonts'])
                    || array_key_exists ("Type 1"  , $infofile['Fonts']))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retourne l'ensemble des categories Arxiv pour l'article
     * Sauf dans le cas d'une mise a jour sur Arxiv, dans ce cas,
     * seule la category principale (la premiere) est retournee
     * @return string[]
     */
    public function getArxivCategories()
    {
        $document = $this -> document;
        if ($document->_docid != 0) {
            if (APPLICATION_ENV != ENV_PROD) {
                return [ 'test.soft' => 'Test Soft'];
            }
            $categories = Hal_Arxiv::haldomaines2arxivsubjects($document->getDomains());

            if ($document->getVersion() == 1 || $this->getOldTransferts() == []) {
                return $categories;
            } else {
                $val = reset($categories);
                return [key($categories) => $val];
            }
        }
        return [];
    }


    /**
     * Indique si un document peut être envoyé sur arXiv (affiche la boîte d'information)
     * @return int (0:non proposé, 1:proposé, 2:obligatoire)
     */
    public function canShowTransfert()
    {
        $document = $this->document;
        if ($document->getIdsCopy('arxiv') != '') {
            //On vérifie que ce n'est pas une nouvelle version
            if ($this -> getOldTransferts() != []) {
                //La version précédente est sur arxiv, on oblige le dépôt sur arxiv
                return 2;
            }
            //Le document est un lien arxiv
            return 0;
        }

        $document->initFormat();
        if ($document->getFormat() != Hal_Document::FORMAT_FILE) {
            //Le document est une notice
            return 0;
        }

        if (! Hal_Settings::getTypdocSetting('arxiv', $document->getTypDoc())) {
            //Type de dépôt incompatible
            return 0;
        }

        return 1;
    }
    /**
     * @param Hal_Document $document
     * @param string[] $errors
     * @return bool
     */
    static public function canTransfert($document, &$errors) {
        $errors = [];
        $abstract = $document->getAbstract('en');
        if ($abstract == '') {
            //Pas de résumé anglais
            $errors[] = self::ERROR_RESUME;
        }

        // Un seul fichier PDF fait par latex => pas de source
        $files = $document->getFiles();
        if (count($files) == 1) {
            $file = current($files);
            if (static::isALatexPdfFile($file)) {
                $errors[] = self::ERROR_NOSOURCE;
            }
        }

        $filesource = $document->getFilesByType('src');
        $totalsize = 0;
        $bblIsPresent = false;
        $bibIsPresent = false;
        foreach ($filesource as $file) {
            if ($file->getExtension() == 'bbl'){
                $bblIsPresent = true;
            }
            if ($file->getExtension() == 'bib'){
                $bibIsPresent = true;
            }
            if (!preg_match(static::$zipExcludeRe, $file->getName())) {
                $size = filesize($file->getPath());
                if ($size >= static::MAX_SIZE_FILE) {
                    //Chaque fichier doit être inférieur à 3Mb
                    $errors[] = self::ERROR_FILESIZE;
                }
                $totalsize = $totalsize + $size;
            }
        }
        // Si il y a un fichier .bib alors il faut obligatoirement le bbl
        // Soit parce que le déposant a fait une compil Hal, soit parce qu'il l'a déposé
        if ($bibIsPresent && !$bblIsPresent) {
            //Arxiv demande un fichier bbl
            $errors[] = self::ERROR_NOBBL;
        }
        if ($totalsize >= static::TOTAL_MAX_SIZE){
            //Le total des fichiers doit être inférieur à 10Mb
            $errors[] = self::ERROR_FILESIZE;
        }
        if (!self::domainArxivOk($document->getDomains())) {
            //Pas de sous domaine arXiv
            $errors[] = self::ERROR_DOMAIN;
        }

        return [] == $errors;
    }

    /**
     * @deprecated
     * Equivalence de code arxiv
     * @param string $code
     * @return string
     */
    static public function transformArxivCode($code) {
        if ($code == 'math.math-mp') {
            return 'phys.mphy';
        } else {
            if ($code == 'info.info-bi') {
                return 'sdv.bibs';
            } else {
                return $code;
            }
        }
    }

    /**
     * @deprecated
     * Retourne le tableau code_arxiv => Libelle anglais du domaine arxiv
     * @param string[] $domains
     * @return string[]
     */
    static public function getDomains2ArxivCategories($domains) {
        $categories = [];
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        /** @var Zend_Translate_Adapter $translator */
        $translator = Zend_Registry::get('Zend_Translate');
        foreach ($domains as $dom) {
            $dom = self::transformArxivCode($dom);
            $sql = $db->select()->from(self::TABLE_DOMAIN_ARXIV, 'ARXIV')->where('CODE = ?', $dom);
            $code = $db->fetchOne($sql);
            if ($code) {
                $categories[$code] = $translator ->translate('domain_' . $dom, 'en');
            }
        }
        return $categories;
    }

    /**
     * Indique s'il existe des domaines arXiv pour les domaines d'un document
     * @param mixed $domainHal
     * @return bool
     */
    static public function domainArxivOk($domainHal)
    {

        if (!isset($domainHal) || empty($domainHal)) {
            return false;
        }

        if (! is_array($domainHal)) {
            $domainHal = array($domainHal);
        }

        if ($domainHal == ['test']) {
            // Cas de test, inexistant en base, mais accetable pour Arxiv
            return true;
        }

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(static::TABLE_DOMAIN_ARXIV, 'COUNT(*)')
            ->where('ARXIV LIKE ?', '%.%')
            ->where('CODE IN (?)', $domainHal);
        return $db->fetchOne($sql) > 0;
    }

    /**
     * @return Hal_Transfert_Response
     */
    public function send($force = false) {
        $document = $this -> document;
        $this -> fullLoad();
        $this -> isAllreadyOn();

        $response = new Hal_Transfert_Response();
        $errors = [];
        if (!$force && !$this->canTransfert($document, $errors)) {
            $response->result = Hal_Transfert_Response::INTERNAL;
            $response->reason = implode("\n", $errors);
            return $response;
        }
        try {
            $sword = $this -> getSwordMedia();
            list($url, $method) = $this->getServiceUrl();
            $this -> method = $method;
        } catch (Hal_Transfert_Exception $e) {
            $response->result = Hal_Transfert_Response::INTERNAL;
            $response->reason = $e -> getMessage();
            return $response;
        }
        try {
            $related = $this->sendData($sword);
        // Todo: Ce code devrait etre plus haut: pas la peine d'envoyer des choses a Arxiv pour ensuite faire une exception...
        // Mais il faudra alors pouvoir mettre le related maintenant!
            $sword = $this -> getSwordMetadata($url, $related);
        } catch (Hal_Transfert_Exception $e) {
            $response->result = Hal_Transfert_Response::INTERNAL;
            $response -> reason = $e -> getMessage();
            return $response;
        }
        try {
            list($edit, $alternate) = $this->sendAtom($sword);
        } catch (Hal_Transfert_Exception $e) {
            $response->result = Hal_Transfert_Response::INTERNAL;
            $response->reason = $e -> getMessage();
            return $response;
        }
        $this -> setPendingUrl($alternate);
        $this -> save();
        sleep(10); // we let distant system process the document before query it
        try {
            $response = $this -> waitresult($edit,$alternate);
        } catch (Hal_Transfert_Exception $e) {
            $response-> result =Hal_Transfert_Response::WARN;
            $response-> alternate = $e -> getMessage();
            $response-> edit = $edit;
        }
        return $response;
    }

    /**
     * @param string $editurl
     * @param string $url
     * @param int $attente
     * @return Hal_Transfert_Response
     * @throws Hal_Transfert_Exception
     */
    public function waitresult($editurl, $url, $attente = null, $dolog = false) {
        if ($attente === null) {
            $attente = static::MAX_RECURSION;
        }
        $document = $this -> document;
        $docid = $document->getDocid();
        $response = new Hal_Transfert_Response();
        try {
            $trackingInfo = $this->verify_deposit($url);
        } catch (Exception $e) {
            throw new Hal_Transfert_Exception (Hal_Transfert_Response::INTERNAL, 'Bad Arxiv response...');
        }

        file_put_contents ( PATHTEMPDOCS . 'sword_arXiv_' . $docid . '_' . time () . '.txt', $trackingInfo->asXML() );
        switch ( $trackingInfo->get_status()) {
            case 'published':
                $this-> modified = true;
                $this->_pendingUrl = $trackingInfo->get_trackingUrl();
                $this->_remoteId = $trackingInfo->get_remoteid();
                $this -> needReindex = true;
                $this->save();

                $response-> externalId = $this->_remoteId;
                $response-> result = Hal_Transfert_Response::OK;
                $response-> alternate = $this->_pendingUrl;
                $response-> edit = $editurl;
                if ($dolog) {
                    Ccsd_Log::message('Update OK for '.$this -> _docid.': '.$this->_remoteId, true, 'INFO');
                }

                break;
            case 'submitted':
                $this -> modified = true;
                $this->_pendingUrl = $trackingInfo->get_trackingUrl();
                $this->save();

                $response-> result = Hal_Transfert_Response::OK;
                $response-> alternate = $this->_pendingUrl;
                $response-> edit = $editurl;
                break;
            case 'on hold':
                $response-> result = Hal_Transfert_Response::WARN;
                $response-> reason = 'On hold sur ArXiv';
                $response-> alternate = $url; # on garde l'url de surveillance
                $response-> edit = $editurl;
                break;
            case 'processing':
                // Ce sera bientot pret...  On prolonge l'attente si necessaire:
                $attente++;
                // PAS DE BREAK, on passe dans incomplete
            case 'incomplete':
                if ( $attente-- <= 0) {
                    throw new Hal_Transfert_Exception (Hal_Transfert_Response::INTERNAL, 'Attente maximal atteinte, sans doute un probleme de compilation Latex');
                }
                sleep(1); // we let distant system process the document before query it
                $response = $this->waitresult($editurl, $trackingInfo->get_trackingUrl() , $attente);
                break;
            /** Ces cas n'arrive qu'apres la soumission initiale */
            case 'user deleted':
            case 'removed':
                if ($dolog) {
                    Ccsd_Log::message('Removed OK for '.$this -> _docid, true, 'INFO');
                }
                $this -> delete();
                break;
            default: // failed, unknown
                if ($dolog) {
                    Ccsd_Log::message('Error for '. $this -> _docid .': status -> '.$trackingInfo->get_status(), true, 'INFO');
                }
                throw new Hal_Transfert_Exception ( Hal_Transfert_Response::INTERNAL, $trackingInfo->get_error());
        }

        return $response;
    }

    /**
     * Use only in test: else we don't have to delete by app
     * @param string $submitId
     */
    public function deleteSubmission($submitId) {
        $curl = curl_init ( $this->loginUrl );
        $postdata = "username=".$this->user."&password=".$this->pwd;
        $deleteurl = sprintf($this->deleteUrl, "$submitId");

        curl_setopt($curl, CURLOPT_URL, $this->loginUrl);
        curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookieJar);
        curl_setopt($curl, CURLOPT_POSTFIELDS,$postdata );
        curl_exec ( $curl ); // Login
        curl_setopt($curl, CURLOPT_URL, $deleteurl);
        curl_exec ( $curl ); // Delete
    }

    /**
     * @param $url
     * @return Hal_Arxiv_TrackingInfo
     */
    public function verify_deposit($url) {
        return Hal_Arxiv_TrackingInfo::getTrackingInfo($url);
    }
}