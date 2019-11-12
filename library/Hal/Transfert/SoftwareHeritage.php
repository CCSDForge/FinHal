<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 18/09/17
 * Time: 14:25
 */

class Hal_Transfert_SoftwareHeritage extends Hal_Transfert {
    const SH = 'softwareheritage';
    const APIVERSION = '1';
    const ERROR_ONLINE    = 'alreadyonline';
    const ERROR_NOID      = 'noid';
    const ERROR_NODOC     = 'nodoc';
    const ERROR_INVALIDTYPE  = 'invalidtype';
    const ERROR_LANG         = 'noeng';
    const ERROR_FILEFORMAT   = 'fileformat';
    const ERROR_EMBARGO   = 'embargo';
    const ERROR_DOMAIN    = 'domain';

    const WAITINGTIME = 4; // Second beetween two status query to Swh

    static public $IDCODE          = 'swh';

    protected $urlService = SWH_SERVICE;
    protected $user       = SWH_USER;
    protected $pwd        = SWH_PWD;

    /** @var string : Le fichier zip temporaire sera prefixe par cette chaine */
    protected $filenamePrefix = "swh-";
    /** @var string Nom d'affichage de la plateforme de transfert */
    protected $name       = "SofwareHeritage";
    /** @var string : Nom de la table de base de données contenant les informations de transferts vers SWH */
    static public $TABLE        = 'DOC_SWH';
    /** @var string : Nom du champs dans la base de données */
    static protected $EXTFIELDNAME = 'REMOTEID';
    // Cela ne devrait pas eliminer grand chose au debut!
    static protected $zipExcludeRe = '/éééééééééééé/';

    public $remoteIdTag     = 'deposit_swh_id';
    public $statusTag       = 'deposit_status';
    public $submissionIdTag = 'deposit_id';
    public $commentTag      = 'deposit_status_detail';
    public $trackingIdTag   = 'foovalue'; //  l url de tracking se deduit du submit id tag

    /**
     * Pour la vue des logiciel: donne la forme de l'url utilise pour pointer vers software heritage.
     * @param Hal_Document $document
     * @return string
     */
    static public function getOriginUrlParam($document) {
        $identPart = $document->getId(false);
        $serverPart="https://hal.archives-ouvertes.fr/";
        $res = 'origin=' . $serverPart . $identPart;
        return $res;
    }
    /** Constructuer from a array (ex: from a row db)
     * @param  array $row
     * @return Hal_Transfert_SoftwareHeritage
     */
    static protected function array2obj($row) {
        return new Hal_Transfert_SoftwareHeritage($row['DOCID'],$row['REMOTEID'],$row['PENDING']);
    }

    /**
     * Usage: On a lu plusieurs ligne, pour eviter de relire la base pour chaque ligne, on evite init_transfert/transfert
     * @param array $row
     * @return Hal_Transfert_SoftwareHeritage
     */
    static public function dbRow2obj($row) {
        $o = new static();
        $docid = $row['DOCID'];
        $pendingUrl = $row['PENDING'];
        $remoteId = $row[static::$EXTFIELDNAME];
        $document = new Hal_Document($docid);
        $o -> _docid = $docid;
        $o -> document = $document;
        $o -> _pendingUrl = $pendingUrl;
        $o -> _remoteId = $remoteId;
        $o -> _collection = $o -> getSwordCollection();
        $o -> isFromDb = true;
        return $o;
    }

    /**
     * Hal_Transfert constructor from a document
     *     Return Transfert Object if present in database
     *     For creating a new transfert, use
     * @see init_transfert
     * @param Hal_Document $document
     * @return Hal_Transfert_SoftwareHeritage|bool
     */
    static public function transfert($document) {
        $o = new static();
        $o -> document = $document;
        $o -> _docid = $document->getDocid();
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
     * @return Hal_Transfert_SoftwareHeritage
     */
    static public function init_transfert($document) {
        $o = new static();
        $o -> document = $document;
        $o -> _docid = $document->getDocid();
        if ($o -> load($document -> getDocid()) !== false) {
            $o -> _collection = $o -> getSwordCollection();
            list($o -> serviceUrl, $o -> method) = $o -> getServiceUrl();
            $o ->loaded = true;
        }
        return $o;
    }

    /**
     * @return string
     */
    protected function getSwordCollection() {
        return "hal";
    }

    /**
     * Sur SWH, Hal fait toujours un nouveau depot.  C'est SHW qui determine si c'est une nouvelle version avec l'identifiant passe en ExternalId
     * Return a couple:
     *     the url of collection
     * and the method (PUT/GET depending it's a new document or a new version
     * @return array
     */
    public function getServiceUrl() {
        $collection = $this -> _collection;
        // SWORD POST|PUT-ing metadata of the media via the "related" link
        $rootUrl = $this->urlService . '/' . self::APIVERSION ;

        $url = "$rootUrl/$collection/";
        $method = 'POST';
        return array($url, $method);
    }

    /**
     * @param int
     * @return string
     * @todo: l'url de status est donnee dans la reponse, il vaut mieux la prendre la plutot que de la construire
     */
    public function id2pendingUrl($submissionId) {
        list($url) = $this -> getServiceUrl();   // on a pas besoin de l'element method du retour de getServiceUrl
        return $url . $submissionId . '/status/';
    }
    /**
     * @param $document Hal_Document
     * @param $fileList Hal_Document_File[]
     * @throws Hal_Transfert_Exception
     * @return string[]
     */
    public function getSwordMedia($document, $fileList) {
        // A -t -on un tgz si oui, on envoie que cela.
        foreach ($fileList as $file) {
            if (preg_match('/(\.tgz|tar\.gz)$/', $file->getPath())) {
                // A faire un Objet SwordAssociatedMedia / etendu avec Zip ou Tgz...
                // Cela permettrait de faire passer le type de fichier dans l'object, avec juste le filename, c'est pas simple...
                // $type = 'tgz';
                $zipfilename = PATHTEMPDOCS . $this->filenamePrefix . $document->_docid . '.tgz';
                copy($file->getPath(), $zipfilename);
                return ['application/x-tar', $zipfilename ];
            }
        }
        // On a soit un zip (et on accepte pour plus tard d'avoir d'autre fichiers...
        // $type = 'zip';
        $zipfilename = PATHTEMPDOCS . $this->filenamePrefix . $document->_docid . '.zip';
        self::create_zip($zipfilename, $fileList); # Exception de create_zip non traitee

        return ['application/zip', $zipfilename ] ;
    }

    /**
     * @param Ccsd_DOMDocument $xmlObj
     * @param DOMElement $parentNode
     * @param string $tagname
     * @param $value
     * @return DOMElement
     */
    private function addChildIfNonEmpty($xmlObj, $parentNode, $tagname, $value) {
        if (!isset($value) || (is_string($value) && $value=='')) {
            return null;
        }
        $addedElem = $xmlObj ->createElement($tagname, $value);
        $parentNode-> appendChild($addedElem);
        return $addedElem;
    }
    /**
     * @param  array $medias
     * @return string
     */
    private function getSwordAtom($medias)
    {
        $document = $this->document;
        if ($document->getDocid() != 0) {
            $xml = new Ccsd_DOMDocument('1.0', 'utf-8');
            $xml->formatOutput = true;
            $xml->substituteEntities = true;
            $xml->preserveWhiteSpace = false;
            $entry = $xml->createElement('entry');
            $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.w3.org/2005/Atom');
            $entry->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:codemeta', 'https://doi.org/10.5063/SCHEMA/CODEMETA-2.0');
            $xml->appendChild($entry);

            $title = $document->getTitle('en');
            if ($title == '') {
                foreach ($document->getTitle() as $t) {
                    $title = $t;
                    break;
                }
            }
            $softname   = str_replace("&lt;", "<", strip_tags(str_replace("<", "&lt;", Ccsd_Tools::decodeLatex($title))));
            $entry->appendChild($xml->createElement('codemeta:name', $softname));

            $this->addChildIfNonEmpty ($xml, $entry,'client', 'hal');
            $this->addChildIfNonEmpty ($xml, $entry,'id', $document->_identifiant);
            $this->addChildIfNonEmpty ($xml, $entry,'external_identifier', $document->_identifiant);

            /*  Hack pour avoir une Url en hal.archives-ouvertes.fr et non pas en portail/... */
            $saveSid = $document -> getSid();
            $document -> setSid (1);
            $entry->appendChild($xml->createElement('codemeta:url', $document -> getUri()));
            $document -> setSid($saveSid);

            foreach ($document -> getHasCopy() as $idname => $id) {
                $idelem = $this->addChildIfNonEmpty ($xml, $entry,'codemeta:identifier', $id);
                if ($idelem !== null) {
                    $idelem -> setAttribute('name', $idname);
                }
            }

            foreach ($document -> getDomains() as $domain) {
                $entry->appendChild($xml->createElement('codemeta:applicationCategory', $domain));
            }
            $comment = $document -> getMetaObj('comment');
            if ($comment) {
                $this->addChildIfNonEmpty ($xml, $entry,  'codemeta:releaseNotes',$comment->getValue());
            }
            $seeAlso = $document -> getMetaObj('seeAlso');
            if ($seeAlso) {
                $values = $seeAlso -> getValue();
                if (!is_array($values)) {
                    $values = [ $values ];
                }
                foreach ($values as $value) {
                    $this->addChildIfNonEmpty($xml, $entry, 'codemeta:relatedLink', $value);
                }
            }

            $referencePublication = $document->getMetaObj('localReference');
            if ($referencePublication) {
                $values = $referencePublication-> getValue();
                if (!is_array($values)) {
                    $values = [ $values ];
                }
                foreach ($values as $value) {
                    $this->addChildIfNonEmpty($xml, $entry, 'codemeta:referencePublication', $value);
                }
            }

            $keywords = $document -> getHalMeta()->getHalMeta('keyword');
            if ($keywords) {
                $sep = ''; // Premiere iteration, on ne met pas la virgule devant
                $listOfKwdString = '';
                foreach ($keywords->getValue() as $keywordsByLang) {
                    $kwdstringbylang = implode(",", $keywordsByLang);
                    $listOfKwdString .= $sep . $kwdstringbylang;
                    $sep = ',';
                }
                $this->addChildIfNonEmpty ($xml, $entry,  'codemeta:keywords',$listOfKwdString);
            }

            $writingDate = $document-> getMetaObj('writingDate');
            if ($writingDate && $writingDate->getValue()) {
                $writingDate = $writingDate->getValue();
            } else {
                $writingDate = $document->getSubmittedDate('c');
            }
            $entry->appendChild($xml->createElement('codemeta:dateCreated', $writingDate));
            $entry->appendChild($xml->createElement('codemeta:datePublished', $document->getSubmittedDate('c')));

            $summary = str_replace("&lt;", "<", strip_tags(str_replace("<", "&lt;", Ccsd_Tools::decodeLatex($document->getAbstract('en')))));
            $this->addChildIfNonEmpty ($xml, $entry,'codemeta:description', $summary);

            $this->addChildIfNonEmpty ($xml, $entry,'codemeta:version', $document -> getVersion());
            $version= $document->getHalMeta()->getMeta('version');
            $this->addChildIfNonEmpty ($xml, $entry,'codemeta:softwareVersion', $version);

            /** @var Hal_Document_Meta_Complex $runtimePlatform */
            $runtimePlatform = $document->getHalMeta()->getHalMeta('runtimePlatform');
            if ($runtimePlatform) {
                foreach ($runtimePlatform->getValue() as $rp) {
                    $entry->appendChild($xml->createElement('codemeta:runtimePlatform', $rp));
                }
            }
            $developmentStatus = $document->getHalMeta()->getMeta('developmentStatus');
            $this->addChildIfNonEmpty ($xml, $entry,'codemeta:developmentStatus', $developmentStatus);
            $codeRepository= $document->getHalMeta()->getMeta('codeRepository');
            $this->addChildIfNonEmpty ($xml, $entry,'codemeta:codeRepository', $codeRepository);

            /** @var Hal_Document_Meta_Simple[] $platforms */
            $platforms= $document->getHalMeta()->getMeta('platform');
            foreach ($platforms as $platform) {
                $entry->appendChild($xml->createElement('codemeta:operatingSystem', $platform));
            }
            /** @var Hal_Document_Meta_Complex $programmingLanguage */
            $programmingLanguage= $document->getHalMeta()->getHalMeta('programmingLanguage');

            if ($programmingLanguage) {
                foreach ($programmingLanguage->getValue() as $pl) {
                    $this->addChildIfNonEmpty($xml, $entry, 'codemeta:programmingLanguage', $pl);
                }
            }
            $softwareLicence = $document->getHalMeta()->getHalMeta('softwareLicence');
            if ($softwareLicence) {
                foreach ($softwareLicence -> getValue() as $licence) {
                    $lic = $xml->createElement('codemeta:license');
                    $lic_name = $xml->createElement('codemeta:name', $licence);
                    $lic->appendChild($lic_name);
                    $entry->appendChild($lic);
                }
            }

            // Funders


            $author = $xml->createElement('author');
            $author->appendChild($xml->createElement('name', 'HAL'));
            $author->appendChild($xml->createElement('email', 'hal@ccsd.cnrs.fr'));
            $entry->appendChild($author);
            /** @var $author Hal_Document_Author */
            foreach ($document->getAuthors() as $author) {
                if ($author->getFullname()) {
                    $contrib = $xml->createElement('codemeta:author');
                    $contrib->appendChild($xml->createElement('codemeta:name', Ccsd_Tools::decodeLatex($author->getFullname())));
                    $affiliations = [];
                    foreach ($author->getStructid() as $s) {
                        $sigle = (new Ccsd_Referentiels_Structure($s))->getSigle();
                        if ($sigle) {
                            $affiliations[] = $sigle;
                        }
                    }

                    foreach ($affiliations as $aff) {
                        $contrib->appendChild($xml->createElement('codemeta:affiliation', Ccsd_Tools::decodeLatex($aff)));
                    }

                    $entry->appendChild($contrib);
                }
            }

            $entry->appendChild($xml->createElement('codemeta:contributor', $document->getContributor('fullname')));

            //$comment = $document->getHalMeta()->getMeta('comment');
            //$localRef = trim(implode(' ', $document->getHalMeta()->getMeta('localReference')));
            //$comment  = str_replace("&lt;", "<", strip_tags(str_replace("<", "&lt;", Ccsd_Tools::decodeLatex($comment))));
            //$reportNo = str_replace("&lt;", "<", strip_tags(str_replace("<", "&lt;", Ccsd_Tools::decodeLatex($localRef))));
            // $entry->appendChild($xml->createElement('arxiv:comment', $comment));
            // $entry->appendChild($xml->createElement('arxiv:report_no', $reportNo));
            // if (in_array($document->getTypDoc(), ['ART', 'COMM', 'PRESCONF', 'OUV', 'COUV'])) {
            //     $entry->appendChild($xml->createElement('arxiv:journal_ref', trim(Ccsd_Tools::decodeLatex(strip_tags(str_replace(["&lt;", "&gt;"], ["<", ">"], $document->getCitation()))), " ,.;\t\n\r\0\x0B")));
            // }
            // $entry->appendChild($xml->createElement('arxiv:doi', $document->getHalMeta()->getMeta('doi')));
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
     * @return bool
     */
    public function canShowTransfert()
    {
        $document = $this -> document;
        $document->initFormat();
        if ($document->getFormat() != Hal_Document::FORMAT_FILE) {
            //Le document est une notice
            return false;
        }

        if ('SOFTWARE' != $document->getTypDoc()) {
            //Type de dépôt incompatible
            return false;
        }

        return true;
    }

    /**
     * @param Hal_Document $document
     * @param string[] $errors
     * @return bool
     */
    static public function canTransfert($document, &$errors) {

        $errors = [];
        if ($document->getIdsCopy(static::SH) != '') {
            //Le document a déja un identifiant SH
            $errors[] = static::ERROR_ONLINE;
        }

        $document->initFormat();
        if ($document->getFormat() != Hal_Document::FORMAT_FILE) {
            //Le document est une notice
            $errors[] = static::ERROR_NODOC;
        }

        if ('SOFTWARE' != $document->getTypDoc()) {
            //Type de dépôt incompatible
            $errors[] = static::ERROR_INVALIDTYPE;
        }

        $mainFile = $document->getDefaultFile();
        if ($mainFile) {
            if ($mainFile->getDateVisible() > date('Y-m-d')) {
                //Il y a un embargo sur le fichier
                $errors[] = static::ERROR_EMBARGO;
            }

            if ($mainFile->getOrigin() != Hal_Settings::FILE_SOURCE_AUTHOR) {
                //le fichier principal n'est pas un fichier auteur
                $errors[] = static::ERROR_FILEFORMAT;
            }
        }

        return []==$errors;
    }

    /**
     * PATHCE POUR SWH
     * @return string
     */
    public function getPendingUrl() {
        $url = $this -> _pendingUrl;
        // Patche: l'url de status donnee conduit a une redirection vers la meme avec un / a la fin
        // https://deposit.softwareheritage.org/1/hal/70/status
        // au lieu de https://deposit.softwareheritage.org/1/hal/70/status/
        if (!preg_match('|/$|', $url)) {
            $url .= "$url/";
        }
        return $url;
    }


    /** Do transfert with sword protocol
     * @param bool $force
     * @return Hal_Transfert_Response
     * @throws Hal_Transfert_Exception
     */
    public function send($force = false)
    {
        // No capability to add extra Header with library swordapp
        // So, hack to use the useragnt header to give the Slug Header
        global $sal_useragent;
        $document        = $this -> document;
        $depositlocation = $this -> serviceUrl;
        $sUser           = $this -> user;
        $password        = $this -> pwd;

        $fileList = $document -> getFiles();
        $response = new Hal_Transfert_Response();
        $url = $this->getPendingUrl();
        if ($url != NULL) {
            // On a deja essaye
            // 1 recup du status precedent...
            $status = $this->verify_deposit($url);
            // 2 suivant le status...
            ////// ok :  on fait un retour et on publie
            ////// nok : on peut refaire un depot
        }

        $atom = $this -> getSwordAtom([]);
        $atomFilename = PATHTEMPDOCS . 'sword-atom-' . $this -> filenamePrefix . $document -> getDocid() . '_' . time () . '.txt';
        file_put_contents($atomFilename, $atom);
        $sword = new SWORDAPPClient();
        // Create the item by depositing an atom document
        // ATTENTION: faire cette affectation APRES que la classe soit loadee!
        $sal_useragent   = "Slug: " . $this ->document -> getId(false);
        $swordResponse = $sword->depositAtomEntry($depositlocation, $sUser, $password, '',$atomFilename,  true);
        if (($swordResponse->sac_status < 200) || ($swordResponse->sac_status >= 300)) {
            // Probleme au depot:
            $response -> reason = $swordResponse -> sac_statusmessage;
            $response -> result = Hal_Transfert_Response::INTERNAL;
            return $response;
        }

        /** @var SimpleXMLElement $edit_media */
        $edit_media = (string) $swordResponse->sac_edit_media_iri;
        $edit_iri = (string) $swordResponse->sac_edit_iri; // doit etre une chaine, pas un XML object
        $response -> edit = $edit_iri;

        $zipInfo = $this->getSwordMedia($document, $fileList);
        $contentzip = $zipInfo [1];
        $compressMimeType = $zipInfo [0];
        try {
            $swordResponse = $sword->addExtraFileToMediaResource($edit_media, $sUser, $password, '', $contentzip, $compressMimeType, false);
        } catch (Exception $e) {
            $response -> reason = "Return of SWH not parsable";
            $response -> result = Hal_Transfert_Response::INTERNAL;
            return $response;
        }
        if (($swordResponse->sac_status < 200) || ($swordResponse->sac_status >= 300)) {
            // Probleme au depot:
            $response -> reason = $swordResponse -> sac_statusmessage;
            $response -> result = Hal_Transfert_Response::INTERNAL;
            return $response;
        }

        // La librairie ne recupere que les choses standard... On va plus loin! Il nous faut le <link ref='alternate' href=''>
        $sac_xml = @new SimpleXMLElement($swordResponse ->sac_xml);
        $sac_ns = $sac_xml->getNamespaces(true);
        // Build the deposit response object
        $swordResponse->buildhierarchy($sac_xml, $sac_ns);
        // Le ADD a supprimé le in-progress et a rendu le depot 'ready'
        // Pas besoin de faire le completeIncompleteDeposit
        /**  $responseDeposit = $sword->completeIncompleteDeposit($edit_iri, $sUser, $password, ''); */
        $alternate=null;
        foreach ($swordResponse -> sac_links as $linkObj) {
            if ($linkObj->sac_linkrel == 'alternate') {
                $alternate  = (string) $linkObj->sac_linkhref; // doit etre une chaine, pas un XML object
            }
        }
        if ($alternate !== null) {
            $this -> setPendingUrl( $alternate);
            $response->alternate = $alternate;
            try {
                $response = $this -> waitresult($edit_iri, $alternate, 200);  // tmp: augmentation forte de l'attente
            } catch (Hal_Transfert_Exception $e) {
                $response-> result =Hal_Transfert_Response::WARN;
                $response-> reason = $e -> getMessage();
                $response-> edit = $edit_iri;
            }
        }
        return $response;
    }

    /**
     * @param string $editurl
     * @param string $url
     * @param int $attente
     * @param bool $dolog
     * @return Hal_Transfert_Response
     * @throws Hal_Transfert_Exception
     */
    public function waitresult($editurl, $url, $attente = null, $dolog = false)
    {
        $document = $this->document;
        $docid = $document->getDocid();

        if ($attente === null) {
            $attente = static::MAX_RECURSION;
        }
        $trackingInfo = $this->verify_deposit($url);
        $logfilename = PATHTEMPDOCS . 'sword_' . $this->filenamePrefix . $docid . '_' . time() . '.txt';
        // Todo: Not implemented
        file_put_contents($logfilename, $trackingInfo->asXML());
        /**
         * - *partial* : multipart deposit is still ongoing
         * - *deposited*: deposit completed (was ready-for-checks)
         * - *rejected*: deposit failed the checks
         * - *verified*: content and metadata verified (was ready-for-load)
         * - *done*: loading completed successfully (was success)
         * - *failed*: the deposit loading has failed (was failure)  */
        switch ($trackingInfo->get_status()) {
            case 'done':
                $this->needReindex = true;
                $this->setRemoteId($trackingInfo->get_remoteId());
                $this->setPendingUrl($this -> id2pendingUrl($trackingInfo->get_submissionId()));
                $this->save();
                $response = new Hal_Transfert_Response();
                $response->externalId = $trackingInfo->get_remoteId();
                $response->result     = Hal_Transfert_Response::OK;
                $response->alternate  = $url;
                $response->edit       = $editurl;
                break;
            case 'verified':
                // On decide d'attendre un vrai success.
                // On enregistre qd meme le status, mais on continue d'attendre
                $this->setPendingUrl($this -> id2pendingUrl($trackingInfo->get_submissionId()));
                $this->save();
                $response = new Hal_Transfert_Response();
                $response->result     = Hal_Transfert_Response::OK;
                $response->alternate  = $url;
                $response->edit       = $editurl;
                break;
            case 'loading':
            case 'deposited':
                $this->setPendingUrl($this -> id2pendingUrl($trackingInfo->get_submissionId()));
                $this->save();
                /* pas de reponse  */
                if ($attente-- <= 0) {
                    // throw new Hal_Transfert_Exception (Hal_Transfert_Response::INTERNAL, "Attente maximale atteinte, sans doute un probleme sur SWH...\nDocument en status" . $trackingInfo->get_status(),$trackingInfo );
                    $response = new Hal_Transfert_Response();
                    $this->save();
                    $response->result     = Hal_Transfert_Response::INTERNAL;
                    $response->alternate  = $url;
                    $response->edit       = $editurl;
                    $response->reason     = "Attente maximale atteinte, sans doute un probleme sur SWH...\nDocument en status" . $trackingInfo->get_status();
                    return $response;
                }
                sleep(self::WAITINGTIME); // we let distant system process the document before query it
                $response = $this->waitresult($editurl, $url, $attente);
                break;
            // rejected, failed
            // partial : Ne devrait pas arriver, c'est un bug!
            default:
                $response = new Hal_Transfert_Response();
                $this->save();
                $response->result     = Hal_Transfert_Response::INTERNAL;
                $response->alternate  = $url;
                $response->edit       = $editurl;
                $response->reason     = $trackingInfo->get_status() . $trackingInfo -> get_extraInfo('comment');
        }
        return $response;
    }
    /**
     * Use only in test: else we don't have to delete by app
     * @param string $submitId
     */
    public function deleteSubmission($submitId)
    {
    // Not implemented
    }

    /**
     * @param $url
     * @return Hal_Transfert_TrackingInfo
     */
    public function verify_deposit($url) {
        return Hal_Transfert_TrackingInfo::getTrackingInfo($url, $this);
    }
    
    /**
     * @param bool $dolog
     */
    static public function check_all_pending_status($dolog = false) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db -> select() -> from(static::$TABLE) -> where (static::$EXTFIELDNAME . ' IS NULL');
        $toReindex = [];
        $aTransferts = $db->fetchAll($sql);

        if ($dolog) {
            Ccsd_Log::message("Nombre de documents à vérifier sur SWH : " . count($aTransferts), true, 'INFO');
        }
        foreach ($aTransferts as $row) {
            $transfert = self::dbRow2obj($row);
            $transfert -> check_pending_status(false, $dolog);
            if ($transfert -> needReindex) {
                $toReindex[] = $transfert->_docid;
            }
        }
        Hal_Document::deleteCaches($toReindex);
        Ccsd_Search_Solr_Indexer::addToIndexQueue($toReindex);
    }
}
