<?php

/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen MALLA OSMAN
 * Date: 29/11/2016
 * Refactor: B Marmol: stop to transform in string and parsing string each time
 *                     add comment for type control
 * =============================================================================================================
 */

/**
 * =============================================== DESCRIPTION =================================================
 * This class allows to create an XML/TEI string for a HAL document
 * His constructor receive a HAL_Document object and base on it to build the TEI elements
 * Using the method create(), it is possible to construct all the TEI elements of the HAL document :
>>> $var = new Hal_Document_Tei(Hal_Document)
>>> $var->create();
 * It is also possible to use one of the 33 methods implemented in this class to create a specific TEI element
like <title>, <author>, <abstract>, ... :
>>> $var->createTitle()
 * =============================================================================================================
 */

class Hal_Document_Tei_Creator extends Hal_Document
{
    const UTF8            = "utf-8";
    const TITLE           = "title";
    const STATUS          = "status";
    const LICENCE         = "licence";
    const ZENDTRANSLATE   = "Zend_Translate";
    const TARGET          = "target";
    const XMLLANG         = "xml:lang";
    const PERSNAME        = "persName";
    const FORENAME        = "forename";
    const SURNAME         = "surname";
    const EMAIL           = "email";
    const NOTATION        = "notation";
    const STRING          = "string";
    const NUMERIC         = "numeric";
    const LASTNAME        = "lastname";
    const FIRSTNAME       = "firstname";
    const EDITOR          = "editor";
    const ANRPROJECT      = "anrProject";
    const FUNDER          = "funder";
    const EURPROJECT      = "europeanProject";
    const EDITION         = "edition";
    const WRITINGDATE     = "writingDate";
    const NOTBEFORE       = "notBefore";
    const SUBTYPE         = "subtype";
    const VALUEATTRIBUTE  = "value_typeAttribute";
    const TRANSLATINGFLAG = "translatingFlag";
    const DEGREE          = "degree";
    const LEVEL           = "level";
    const CONFTITLE       = "conferenceTitle";
    const CONFSTARTDATE   = "conferenceStartDate";
    const CONFENDDATE     = "conferenceEndDate";
    const COUNTRY         = "country";
    const CONFORGANIZER   = "conferenceOrganizer";
    const PUBLISHER       = "publisher";
    const SERIE           = "serie";
    const BIBLSCOPE       = "biblScope";
    const VOLUME          = "volume";
    const ISSUE           = "issue";
    const AUTHORITY       = "authority";
    const LECTURENAME     = "lectureName";
    const LANGUAGE        = "language";
    const SCHEME          = "scheme";
    const CLASSCODE       = "classCode";
    const COLLABORATION   = "collaboration";
    const TEI_LOGDIR      = "/tmp/tei_logFolder/";
    /**
     * Properties */

    /** @var bool :  Say if Creator must return string with XML header */
    protected $_header      = null;
    /** @var Ccsd_DOMDocument */
    protected $_xml         = null;
    // Element <tei>
    protected $_root        = null;
    // Object Hal_Document
    protected $_HalDocument = null;

    /**
     * receive a HAL_Document object
     * create two elements <XML> and <TEI> by default
     * @param Hal_Document $HalDocument
     * @param bool $_header
     */

    public function __construct($HalDocument, $_header = false) {
        if (!$HalDocument instanceof Hal_Document) {
            throw new InvalidArgumentException('Not instance of Hal_Document');
        }
        $this->_HalDocument = $HalDocument;
        $this->_xml = new Ccsd_DOMDocument('1.0', self::UTF8);
        $this->_xml->formatOutput = true;
        $this->_xml->substituteEntities = true;
        $this->_xml->preserveWhiteSpace = false;
        $this->_root = $this->_xml->createElement('TEI');
        $this->_root->setAttribute('xmlns',     'http://www.tei-c.org/ns/1.0');
        $this->_root->setAttribute('xmlns:xsi', "http://www.w3.org/2001/XMLSchema-instance");
        $this->_root->setAttribute('xmlns:hal', 'http://hal.archives-ouvertes.fr/');
        $this->_root->setAttribute('version', '1.1');
        $attr = new DOMAttr('xsi:schemaLocation', "http://www.tei-c.org/ns/1.0 http://api.archives-ouvertes.fr/documents/aofr-sword.xsd");
        $this->_root->setAttributeNode($attr);
        $this->_xml->appendChild($this->_root);
        $this->_header = $_header;
    }
    /**
     * Append a string child to a dom parent
     * @param DOMElement $domParent
     * @param DOMElement|string $domChild
     * @deprecated
     */
    private function appendStringToDomNode($domParent, $domChild) {
        if(isset($domChild) && $domChild != "") {
            if(is_string($domChild)) {
                $d = new Ccsd_DOMDocument('1.0', self::UTF8);
                $d->formatOutput = true;
                $d->substituteEntities = true;
                $d->preserveWhiteSpace = false;
                $d->loadXML('<root>'.$domChild.'</root>');
                foreach ( $d->getElementsByTagName('root')->item(0)->childNodes as $child ) {
                    $domParent->appendChild($this->_xml->importNode($child, true));
                }
            } else {
                $domParent->appendChild($domChild);
            }
        }
    }
    /**
     * In case that the doc is not valid according to the TEI schema :
     * this method create a log file that contain the produced errors in tei_logFolder
     * @param string $XML_TEI
     */
    public function validateTEI($XML_TEI) {
        $teiSchema = __DIR__.'/../Sword/xsd/aofr.xsd';
        $xml = new Ccsd_DOMDocument('1.0',self::UTF8);
        $xml->loadXML($XML_TEI);
        if (!@$xml->schemaValidate($teiSchema)) {
            if (!file_exists(self::TEI_LOGDIR)) {
                mkdir(self::TEI_LOGDIR);
            }
            $fileName = self::TEI_LOGDIR."tei.log";
            if (filesize($fileName) > 10000000) { // 10M
                // On evite de faire croitre le fichier de log...
                unlink($fileName);
            }
            if ( $file = fopen($fileName, "a+") ) {
                fputs($file, $this->_HalDocument->_identifiant."\n");
                $errors = libxml_get_errors();
                foreach ($errors as $error) {
                    $return ="";
                    switch ($error->level) {
                        case LIBXML_ERR_WARNING:
                            $return .= " Warning $error->code: ";
                            break;
                        case LIBXML_ERR_ERROR:
                            $return .= " Error $error->code: ";
                            break;
                        default:
                            $return .= " Fatal Error $error->code: ";
                    }
                    $return .= trim($error->message);
                    if ($error->file) {
                        $return .= " in $error->file";
                    }
                    if (isset($return)) {
                        $return .= " on line $error->line";
                    }
                    fputs($file, $return."\n");
                }
                fclose($file);
                libxml_clear_errors();
            }
        }
    }
    /**
     * @deprecated  will be suppress very soon
     * @param string $var
     * @return string
     */
    public function convertToXMLString($var)
    {
        $doc = new Ccsd_DOMDocument('1.0', self::UTF8);
        $doc->formatOutput = true;
        $doc->substituteEntities = true;
        $doc->preserveWhiteSpace = false;
        $doc->loadXML('<root>' . $var . '</root>');
        foreach ($doc->getElementsByTagName('root')->item(0)->childNodes as $child) {
            $this->_root->appendChild($this->_xml->importNode($child, true));
        }
        return ($this->_header) ? $this->_xml->saveXML() : $this->_xml->saveXML($this->_xml->documentElement);
    }

    /**
     * return an XML string that represent all TEI elements of a HAL Document
     * Keep for compatibility
     * @deprecated: prefer createDOM
     * @return string
     */
    public function create() {
        $teiDom = $this->createDOM();
        return ($this->_header) ? $teiDom->saveXML() : $teiDom->saveXML($teiDom->documentElement);
    }

    /**
     * @return Ccsd_DOMDocument|null
     */
    public function createDOM() {
        // =====================================================================
        // ============================= teiHeader =============================
        // =====================================================================
        // Create and append <teiHeader>
        $this->_root ->appendChild($this->createTeiHeader());

        // =====================================================================
        // ==================== text>body>listBibl>biblFull ====================
        // =====================================================================
        // Create <text>
        $text = $this->_xml->createElement('text');
        // Create <body>
        $body = $this->_xml->createElement('body');
        // Create <listBibl>
        $lb = $this->_xml->createElement('listBibl');
        // Create <biblFull>
        $b = $this->_xml->createElement('biblFull');

        // ============================ titleStmt =============================
        // Append <titleStmt> to <biblFull>
        $b->appendChild($this->createTitleStmt());

        // ============================ editionStmt ===========================
        // Create <editionStmt>
        $es = $this->createEditionStmt();
        $b->appendChild($es);

        // ========================== publicationStmt =========================
        // Create <publicationStmt> and append it to <biblFull>
        $b->appendChild($this->createPublicationStmt());

        // ============================= seriesStmt ===========================
        // Create <seriesStmt> and append it to <biblFull>
        $b->appendChild($this->createSeriesStmt());

        // ============================== notesStmt ===========================
        // Create <notesStmt> and append it to <biblFull>
        $b->appendChild($this->createNotesStmt());

        // ============================= sourceDesc ===========================
        // Create <sourceDesc>
        $sd = $this->_xml->createElement('sourceDesc');

        // Create <biblStruct>
        $biblStruct = $this->_xml->createElement('biblStruct');
        // Create <analytic> and append it to <biblStruct>
        $analiytic = $this->createAnalytic();
        if ($analiytic) $biblStruct -> appendChild($analiytic);
        // Create <monogr> and append it to <biblStruct>
        $biblStruct -> appendChild($this->createMonogr());

        // Create <series> and append it to <biblStruct>
        $series = $this->createSeries();
        if ($series) $biblStruct -> appendChild($series);

        // Create <idno> and append it to <biblStruct>
        foreach ($this->createIdentifier() as $idno) {
            $biblStruct->appendChild($idno);
        }
        // Create <ref> type seeAlso and append it to <biblStruct>
        foreach ($this->createSeeAlso() as $seeAlso) {
            $biblStruct -> appendChild($seeAlso);
        }
        // Create <ref> type publisher and append it to <biblStruct>
        $publisherLink = $this->createPublisherLink();
        if ($publisherLink) $biblStruct -> appendChild($publisherLink);
        // Create <relatedItem> and append it to <biblStruct>
        foreach ($this->createRelatedItem() as $relatedItem){
            $biblStruct->appendChild($relatedItem);
        }
        // Append <biblStruct> to <sourceDesc>
        $sd->appendChild($biblStruct);

        // Create <listPlace> and append it to <sourceDesc>
        $listPlace = $this->createListPlace();
        if ($listPlace) $sd -> appendChild($listPlace);
        // Create <recordingStmt> and append it to <sourceDesc>
        $recortStmt = $this->createRecordingStmt();
        if ($recortStmt) $sd->appendChild($recortStmt);

        // Append <sourceDesc> to <biblFull>
        $b->appendChild($sd);

        // ============================= profileDesc ===========================
        // Create <profileDesc>
        $pd = $this->_xml->createElement('profileDesc');

        // Create <languages> and append it to <profileDesc>
        $pd->appendChild($this->createLanguages());

        // Create <textClass> and append it to <profileDesc>
        $pd ->appendChild($this->createTextClass());

        // Create <abstract> and append it to <profileDesc>
        foreach ($this->createAbstract() as $abstract) {
            $pd -> appendChild($abstract);
        }

        /** add bibliographics information
         * TODO: le XML des references contient un xml:id qui n'est plus unique si on mets un resultat avec plusieurs document!!!
         *       Il faut ABSOLUEMENT modifier les Id avant de remettre le code.
         * Fait: BM
         * @see changeXmlIdValue
         * $listBibl = $this->createReferences();
         * if ($listBibl) $pd->appendChild($listBibl);
         */
        // Create <org> and append it to <profileDesc>
        $org = $this->createOrg();
        if ($org) $pd->appendChild($org);

        // Append <profileDesc> to <biblFull>
        $b->appendChild($pd);

        // Append <biblFull> to <listBibl>
        $lb->appendChild($b);
        // Append <listBibl> to <body>
        $body->appendChild($lb);
        // Append <body> to <text>
        $text->appendChild($body);

        // =====================================================================
        // ============================= text>back =============================
        // =====================================================================
        // Create <back> and append it to <text>
        $text ->appendChild($this->createBack());

        // APPEND THE ROOT AND RETURN THE XML STRING
        // Append <text> to <tei>
        $this->_root->appendChild($text);

        // Validation with schema TEI
        //libxml_use_internal_errors(true);
        //$this->validateTEI($this->_xml->saveXML($this->_xml->documentElement));

        // Return the XML
        return $this->_xml;

    }
    /**
     * @return DOMElement
     * return <teiHeader> */

    private function createTeiHeader() {
        $head = $this->_xml->createElement('teiHeader');
        $fd   = $this->_xml->createElement('fileDesc');
        $ts    = $this->_xml->createElement('titleStmt');
        $title = $this->_xml->createElement(self::TITLE, 'HAL TEI export of '. $this->_HalDocument->getId(true));
        $ts->appendChild($title);
        $fd->appendChild($ts);
        $ps = $this->_xml->createElement('publicationStmt');
        $ps->appendChild($this->_xml->createElement('distributor', 'CCSD'));
        $headeravailability = $this->_xml->createElement('availability');
        $headeravailability->setAttribute(self::STATUS, 'restricted');
        if ( $this->_HalDocument->getLicence() != '' ) {
            $headerlicence = $this->_xml->createElement(self::LICENCE, Ccsd_Tools::translate(Hal_Referentiels_Metadata::getLabel(self::LICENCE, $this->_HalDocument->getLicence()), 'en'));
            $headerlicence->setAttribute(self::TARGET, $this->_HalDocument->getLicence());
        } else {
            $headerlicence = $this->_xml->createElement(self::LICENCE, 'Distributed under a Creative Commons Attribution 4.0 International License');
            $headerlicence->setAttribute(self::TARGET, 'http://creativecommons.org/licenses/by/4.0/');
        }
        $headeravailability->appendChild($headerlicence);
        $ps->appendChild($headeravailability);
        $headerdate = $this->_xml->createElement('date');
        $headerdate->setAttribute('when', date('c'));
        $ps->appendChild($headerdate);
        $fd->appendChild($ps);
        $sd = $this->_xml->createElement('sourceDesc');
        $p = $this->_xml->createElement('p', 'HAL API platform');
        $p->setAttribute('part', 'N');
        $sd->appendChild($p);
        $fd->appendChild($sd);
        $head->appendChild($fd);
        return $head;
    }

    /**
     * @return DOMElement
     */
    private function createTitleStmt() {
        $ts = $this->_xml->createElement('titleStmt');
        foreach ($this->_HalDocument->getTitle() as $l => $t) {
            $ts ->appendChild($this->createTitle($t, $l));
        }
        $subtitle = $this->_HalDocument->getMetaObj('subTitle');
        if ($subtitle) {
            foreach ($subtitle->getValue() as $l => $t) {
                $ts->appendChild($this->createSubTitle($t, $l));
            }
        }
        foreach ($this ->createAuthors() as $autnode) {
            $ts ->appendChild($autnode);
        }
        $contributor = $this->createContributors();
        if ($contributor) $ts ->appendChild($contributor);

        $this->addFinancements($ts);
        return $ts;
    }
    /**
     * return the title of the document <title>
     * @param string $title
     * @param string $lang
     * @return  DOMElement
     */
    private function createTitle($title, $lang) {
        $tit = $this->_xml->createElement(self::TITLE, $title);
        $tit->setAttribute(self::XMLLANG, $lang);
        return $tit;
    }
    /**
     * return the sub title of the document <title type="sub">
     * @param string $subtitle
     * @param string $lang
     * @return DOMElement
     */

    private function createSubTitle($subtitle, $lang) {
        $stit = $this->createTitle($subtitle, $lang);
        $stit->setAttribute('type', 'sub');
        return $stit;
    }
    /**
     * return the authors of the document <author>
     * @return DOMElement[]
     */

    private function createAuthors() {
        $autnodes = [];
        foreach ($this->_HalDocument->_authors as $aut) {
            $autnodes[] = $aut->getXMLNode($this->_xml);
        }
        return $autnodes;
    }

    /**
     * return the contributors of the document <editors>
     * @return DOMElement | null
     */
    private function createContributors() {
        $c = null;
        $lastname  = $this->_HalDocument->getContributor(self::LASTNAME);
        $firstname = $this->_HalDocument->getContributor(self::FIRSTNAME);
        $email     = $this->_HalDocument->getContributor(self::EMAIL);

        if ($lastname  != '' && $firstname != '' && $email != '') {
            $c = $this->_xml->createElement(self::EDITOR);
            $c->setAttribute('role', 'depositor');
            $persName =  $this ->createPersName($firstname,$lastname);
            $c->appendChild($persName);
            $email = $this->_HalDocument->getContributor(self::EMAIL);
            $emailNode = $this ->createEmail($email);
            $c->appendChild($emailNode);
            $emailDomain = $this ->createEmailDomain($email);
            $c->appendChild($emailDomain);
        }
        return $c;
    }

    /**
     * @param string $firstname
     * @param string $lastmame
     * @return DOMElement
     */
    private function createPersName($firstname='', $lastmame='') {
        $persName = $this->_xml->createElement(self::PERSNAME);
        $persName->appendChild($this->_xml->createElement(self::FORENAME, $firstname));
        $persName->appendChild($this->_xml->createElement(self::SURNAME, $lastmame));
        return $persName;
    }
    /**
     * @param string $email
     * @return DOMElement
     */
    private function createEmail($email='') {
        $email = $this->_xml->createElement(self::EMAIL, Hal_Document_Author::getEmailHashed($email, Hal_Settings::EMAIL_HASH_TYPE));
        $email->setAttribute('type', Hal_Settings::EMAIL_HASH_TYPE);
        return $email;
    }
    /**
     * @param string $email
     * @return DOMElement
     */
    private function createEmailDomain($email='') {
        $emailDomain = $this->_xml->createElement(self::EMAIL, Ccsd_Tools::getEmailDomain($email));
        $emailDomain->setAttribute('type', 'domain');
        return $emailDomain;
    }
    /**
     * return the organization financing the document <funder>
     * @param DOMElement $parentNode
     * @return void
     */
    private function addFinancements($parentNode) {
        foreach ($this->createANRProject() as $project) {
            $parentNode->appendChild($project);
        }
        foreach ($this->createEuropeanProject() as $project) {
            $parentNode->appendChild($project);
        }
        foreach ($this->createFunding() as $project) {
            $parentNode->appendChild($project);
        }
    }
    /**
     * return <funder> type ANR Project
     * @return DOMElement[]
     */
    private function createANRProject()
    {
        $projects = [];
        $listProjectsObj = $this->_HalDocument->getMetaObj(self::ANRPROJECT);
        if ($listProjectsObj != null) {
            $listProjects = $listProjectsObj -> getValue();
            if ($listProjects) {
                foreach ($listProjects as $anr) {
                    if ($anr instanceof Ccsd_Referentiels_Anrproject) {
                        $projNode = $this->_xml->createElement(self::FUNDER);
                        $projNode->setAttribute('ref', '#projanr-' . $anr->ANRID);
                        array_push($projects, $projNode);
                    }
                }
            }
        }
        return $projects;
    }
    /**
     * return <funder> type EUROPEAN project
     * @return DOMElement[]
     */
    private function createEuropeanProject() {
        $projects = [];
        $listProjectsObj = $this->_HalDocument->getMetaObj(self::EURPROJECT);
        if ($listProjectsObj != null) {
            $listProjects = $listProjectsObj->getValue();
            if ($listProjects) {
                foreach ($listProjects as $europe) {
                    if ($europe instanceof Ccsd_Referentiels_Europeanproject) {
                        $projNode = $this->_xml->createElement(self::FUNDER);
                        $projNode->setAttribute('ref', '#projeurop-' . $europe->PROJEUROPID);
                        array_push($projects, $projNode);
                    }
                }
            }
        }
        return $projects;
    }
    /**
     * return <funder> type funding
     * @return DOMElement[]
     */
    private function createFunding() {
        $projects = [];
        $funders = $this->_HalDocument->getMetaObj('funding');
        if ($funders) {
            $funderArray = $funders -> getValue();
            foreach ( $funderArray as $funder ) {
                $funding = $this->_xml->createElement(self::FUNDER, $funder);
                array_push($projects, $funding);
            }
        }
        return $projects;
    }

    /**
     * @return DOMElement
     */
    private function createEditionStmt() {
        $es = $this->_xml->createElement('editionStmt');
        foreach  ($this->createEdition() as $edition) {
            $es->appendChild($edition);
        }
        // Create <respStmt> and append it to <editionStmt>
        $respStmt = $this->createRespStmt();
        if ($respStmt != null) {
            $es->appendChild($respStmt);
        }
        return $es;
    }
    /**
     * return the edition(s) of the document <edition>
     * @return DOMElement[]
     */

    private function createEdition() {
        // Pour les différentes versions
        $editions = [];

        foreach ( $this->_HalDocument->getDocVersions() as $n=>$docrow ) {
            $date = Hal_Document::getDateVersionFromDocRow($docrow);
            $edition = $this->_xml->createElement(self::EDITION);
            $edition->setAttribute('n', 'v'.$n);

            $d = $this->_xml->createElement('date', $date);
            $d->setAttribute('type', 'whenSubmitted');
            $edition->appendChild($d);

            if ( $this->_HalDocument->getVersion() == $n ) {
                $edition->setAttribute('type', 'current');
                $dateObjList = $this->createDates();
                foreach ($dateObjList as $d) {
                    $edition->appendChild($d);
                }

                foreach ($this->createFiles() as $fileNode) {
                    $edition->appendChild($fileNode);
                }

                $fs = $this->createMetas();
                if ($fs) $edition->appendChild($fs);

                $linkext=$this->createLinkExt();
                if ($linkext) $edition->appendChild($linkext);
            }
            array_push($editions, $edition);
        }
        return $editions;
    }

    /**
     * Add the Url for external link
     */
    private function createLinkExt() {
        /** @var Hal_Document_Meta_LinkExt $linkext */
        $linkext = $this->_HalDocument-> getMetaObj(Hal_Document_Meta_LinkExt::linkext);
        if (!$linkext) {
            return null;
        }
        $url = $linkext->getUrl();
        $ref = $this->_xml->createElement('ref');
        $ref->setAttribute('type', 'externalLink');
        $ref->setAttribute('target', $url);
        return $ref;
    }
    /**
     * @return DOMElement[]
     */
    private function createFiles() {
        $files = [];
        foreach ( $this->_HalDocument->_files as $file ) {
            if ( $file instanceof Hal_Document_File &&
                in_array($file->getType(), array(Hal_Document::FORMAT_FILE, Hal_Document::FORMAT_ANNEX)) ) {

                $ref = $this->createFile($file);
                $files[] = $ref;
            }
        }
        return $files;
    }

    /**
     * @param Hal_Document_File $file
     * @return DOMElement
     */
    private function createFile($file)
    {
        $ref = $this->_xml->createElement('ref');
        $ref->setAttribute('type', $file->getType());
        if ($file->getType() == 'file') {
            if ($file->getOrigin()) {
                $ref->setAttribute(self::SUBTYPE, $file->getOrigin());
            }
        } else if ($file->getType() == 'annex') {
            $format = $file->getFormat();
            if ($format == '') {
                $format = 'undefined';
            }
            $ref->setAttribute(self::SUBTYPE, $format);
        }
        $ref->setAttribute('n', (int)($file->getDefault() || $file->getDefaultannex()));
        $ref->setAttribute(self::TARGET, $this->_HalDocument->getUri() . '/file/' . rawurlencode($file->getName()));
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $file->getDateVisible())) {
            $date = $this->_xml->createElement('date');
            $date->setAttribute(self::NOTBEFORE, $file->getDateVisible());
            $ref->appendChild($date);
        }
        if ($file->getComment() != '') {
            $ref->appendChild($this->_xml->createElement('desc', $file->getComment()));
        }
        return $ref;
    }

    /**
     * @return DOMElement|null
     */
    private function createMetas()
    {
        $fs = null;
        // Metadonnées Local
        // Define an array to store the names of the local metas
        $tableLocalMetas = [];

        // On charge la configuration du portail de dépôt du document pour les métas spécifiques
        $sid = $this->_HalDocument->getSid();
        // TODO: faire un cache au niveau de site portail pour les meta pour ne pas lite des millions de fois les .ini
        $portail = Hal_Site_Portail::loadSiteFromId($sid);
        $ini = $portail->getConfigMeta();

        // Get the name of the local meta (if existed) et prepare the process for the integration in CreateTei.php
        foreach ($ini['elements'] as $nomMeta => $element) {
            // Get the value of elements
            foreach ($element as $cle => $val) {
                // Get element by options
                if ($cle == "options") {
                    // Test if localTei fgexist and assigned to true (the portal has specific metas)
                    if (isset($val['localMeta']) && $val['localMeta']) {
                        // Add the names of metas to tha variable tableLocalMetas
                        array_push($tableLocalMetas, $nomMeta);
                    }
                }
            }
        }
        $nbOfMetaAdded=0;
        // Test if the return of the method getLocalMeta is an array and his size to not null
        if (count($tableLocalMetas)) {
            // Create a fs element
            $fs = $this->_xml->createElement('fs');
            // Meta type list with id ans text (e.g. inria_presConf)
            $metasList = Hal_Referentiels_Metadata::metaList();
            // Loop over the names of the retrieved local metas

            foreach ($tableLocalMetas as $LML) {
                $meta = $this->_HalDocument->getMetaObj($LML);
                if ($meta === null) {
                    continue;
                }
                $value = $meta->getValue();
                // Test if meta value not null
                if ($value == '' || $value === null) {
                    continue;
                }
                // Create a f element
                $f = $this->_xml->createElement('f');
                // Assign the name of the retrieved local meta to the attribute NAME of the f element
                $f->setAttribute('name', $LML);
                // Assign the value string to the attribut notation
                $f->setAttribute(self::NOTATION, self::STRING);
                // TODO: ne pas faire deux noeuds <f> mais un noeud <f><vAlt><numeric><string></vAlt><f>
                if (in_array($LML, $metasList)) {
                    ///////////// Creation of string element ////////////////
                    // Create a string element and assign the value of the retrieved local meta to it
                    $string = $this->_xml->createElement(self::STRING, Ccsd_Tools::translate(Hal_Referentiels_Metadata::getLabel($LML, $value), 'en'));
                    // Add the string element to the f element
                    $f->appendChild($string);
                    // Add the f element to the fs element
                    $fs->appendChild($f);
                    ///////////// Creation of numeric element ///////////////
                    $f = $this->_xml->createElement('f');
                    $f->setAttribute('name', $LML);
                    $f->setAttribute(self::NOTATION, self::NUMERIC);
                    $numeric = $this->_xml->createElement(self::NUMERIC, $value);
                    $f->appendChild($numeric);
                    $fs->appendChild($f);
                    // Meta based free text
                } else {
                    $string = $this->_xml->createElement(self::STRING, $value);
                    $f->appendChild($string);
                    $fs->appendChild($f);
                }
                $nbOfMetaAdded++;
            }
        }
        if ($nbOfMetaAdded > 0) {
            return $fs;
        } else {
            return null;
        }
    }

    /**
     * return the set of dates for a document (with embargo date for files
     * @return DOMElement[]
     */

    private function createDates() {
        $dates=[];
        $writingDate = $this->_HalDocument->getMetaObj(self::WRITINGDATE);
        if ($writingDate) {
            $d = $this->_xml->createElement('date', $writingDate -> getValue());
            $d->setAttribute('type', 'whenWritten');
            $dates[]=$d;
        }
        $d = $this->_xml->createElement('date', $this->_HalDocument->_modifiedDate);
        $d->setAttribute('type', 'whenModified');
        $dates[]=$d;

        $d = $this->_xml->createElement('date', $this->_HalDocument->_releasedDate);
        $d->setAttribute('type', 'whenReleased');
        $dates[]=$d;

        $d = $this->_xml->createElement('date', $this->_HalDocument->_producedDate);
        $d->setAttribute('type', 'whenProduced');
        $dates[]=$d;

        if ($this->_HalDocument->_format == Hal_Document::FORMAT_FILE) {
            $d = $this->_xml->createElement('date', $this->_HalDocument->getFirstDateVisibleFile());
            $d->setAttribute('type', 'whenEndEmbargoed');
            $dates[] = $d;

            $ref = $this->_xml->createElement('ref');
            $ref->setAttribute('type', 'file');
            $ref->setAttribute(self::TARGET, $this->_HalDocument->getUri(true) . $this->_HalDocument->getUrlMainFile());
            $v = $this->_HalDocument->getDateVisibleMainFile();
            if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $v)) {
                $date = $this->_xml->createElement('date');
                $date->setAttribute(self::NOTBEFORE, $v);
                $ref->appendChild($date);
            }
            $dates[] = $ref;
        }
        return $dates;
    }

    /**
     * @return DOMElement[]
     * @unused : A garder pour inserer les ref biblio dans Tei lorsque cela marchera!
     */
    private function createRefs() {
        // Pour les différentes versions
        $editions = [];
        foreach ($this->_HalDocument->getDocVersions() as $n => $docrow) {
            $date = Hal_Document::getDateVersionFromDocRow($docrow);
            $edition = $this->_xml->createElement(self::EDITION);
            $edition->setAttribute('n', 'v' . $n);
            if ($this->_HalDocument->_version == $n) {
                if ($this->_HalDocument->_format == Hal_Document::FORMAT_FILE) {
                    $ref = $this->_xml->createElement('ref');
                    $ref->setAttribute('type', 'file');
                    $ref->setAttribute(self::TARGET, $this->_HalDocument->getUri(true) . $this->_HalDocument->getUrlMainFile());
                    $v = $this->_HalDocument->getDateVisibleMainFile();
                    if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $v)) {
                        $date = $this->_xml->createElement('date');
                        $date->setAttribute(self::NOTBEFORE, $v);
                        $ref->appendChild($date);
                    }
                    $edition->appendChild($ref);
                }
                foreach ( $this->_HalDocument->_files as $file ) {
                    if ( $file instanceof Hal_Document_File && in_array($file->getType(), array(Hal_Document::FORMAT_FILE, Hal_Document::FORMAT_ANNEX)) ) {
                        $ref = $this->_xml->createElement('ref');
                        $ref->setAttribute('type', $file->getType());
                        if ( $file->getType() == 'file' ) {
                            if ( $file->getOrigin() ) {
                                $ref->setAttribute(self::SUBTYPE, $file->getOrigin());
                            }
                        } else if ( $file->getType() == 'annex' ) {
                            $ref->setAttribute(self::SUBTYPE, $file->getFormat());
                        }
                        $ref->setAttribute('n', (int)($file->getDefault()||$file->getDefaultannex()));
                        $ref->setAttribute(self::TARGET, $this->_HalDocument->getUri().'/file/'.rawurlencode($file->getName()));
                        if ( preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $file->getDateVisible()) ) {
                            $date = $this->_xml->createElement('date');
                            $date->setAttribute(self::NOTBEFORE, $file->getDateVisible());
                            $ref->appendChild($date);
                        }
                        if ( $file->getComment() != '' ) {
                            $ref->appendChild($this->_xml->createElement('desc', $file->getComment()));
                        }
                        $edition->appendChild($ref);
                    }
                }
            }
            array_push($editions, $edition);
        }
        return ($editions);
    }

    /**
     * @return DOMElement|null
     * return the responsable of the document <respStmt> */

    private function createRespStmt() {
        $respStmt = null;

        $lastname = $this->_HalDocument->getContributor(self::LASTNAME);
        $firstname = $this->_HalDocument->getContributor(self::FIRSTNAME);
        $email = $this->_HalDocument->getContributor(self::EMAIL);
        if ( $this->_HalDocument->getContributor('uid') != ''
            && $lastname != '' && $firstname != '' && $email != '' ) {
            $respStmt = $this->_xml->createElement('respStmt');
            $respStmt->appendChild($this->_xml->createElement('resp', 'contributor'));
            $name = $this->_xml->createElement('name');
            $name->setAttribute('key', $this->_HalDocument->getContributor('uid'));

            $persName = $this->createPersName($firstname, $lastname);
            $name->appendChild($persName);
            $emailObj = $this->createEmail($email);
            $name->appendChild($emailObj);
            $emailDomain = $this->createEmailDomain($email);
            $name->appendChild($emailDomain);

            $respStmt->appendChild($name);
        }
        return $respStmt;
    }
    /**
     * @return DOMElement
     * return <publicationStmt> including :
     *        <distributor>
     *        <idno> (halId, halUri, halBibtex, halRefHtml, halRef
     *        <licnence> */

    private function createPublicationStmt() {
        $ps = $this->_xml->createElement('publicationStmt');
        $ps->appendChild($this->_xml->createElement('distributor', 'CCSD'));
        $hal = $this->_xml->createElement('idno', $this->_HalDocument->getId());
        $hal->setAttribute('type', 'halId');
        $ps->appendChild($hal);
        $uri = $this->_xml->createElement('idno', $this->_HalDocument->getUri());
        $uri->setAttribute('type', 'halUri');
        $ps->appendChild($uri);
        $bibtex = $this->_xml->createElement('idno', $this->_HalDocument->getKeyBibtex());
        $bibtex->setAttribute('type', 'halBibtex');
        $ps->appendChild($bibtex);
        $citation = $this->_xml->createElement('idno', $this->_HalDocument->getCitation());
        $citation->setAttribute('type', 'halRefHtml');
        $ps->appendChild($citation);
        $citation = $this->_xml->createElement('idno', strip_tags($this->_HalDocument->getCitation()));
        $citation->setAttribute('type', 'halRef');
        $ps->appendChild($citation);

        $licences = [];
        if ( $this->_HalDocument->getLicence() != '' ) {
            $licences[Ccsd_Tools::translate(Hal_Referentiels_Metadata::getLabel(self::LICENCE, $this->_HalDocument->getLicence()), 'en')] = $this->_HalDocument->getLicence();
        } else if ($softwareLicence = $this->_HalDocument->getHalMeta()->getMeta('softwareLicence')){
            //Licence logiciel
            $referencial = new Thesaurus_Spdx();
            foreach ($softwareLicence as $licence) {
                $licences[$licence] = $referencial->getUrl($licence);
            }
        }
        if ($licences) {
            $headeravailability = $this->_xml->createElement('availability');
            $headeravailability->setAttribute(static::STATUS, 'restricted');
            foreach ($licences as $licence => $url) {
                $headerlicence = $this->_xml->createElement(self::LICENCE, $licence);
                if ($url != '') {
                    $headerlicence->setAttribute(self::TARGET, $url);
                }
                $headeravailability->appendChild($headerlicence);
            }
            $ps->appendChild($headeravailability);
        }

        return $ps;
    }

    /**
     * @return DOMElement
     * return the HAL collections <seriesStmt>
     */

    private function createSeriesStmt() {
        $ss = $this->_xml->createElement('seriesStmt');
        foreach ($this->_HalDocument->_collections as $collection) {
            if ($collection instanceof Hal_Site_Collection) {
                $idno = $this->_xml->createElement('idno', $collection->getFullName());
                $idno->setAttribute('type', 'stamp');
                $idno->setAttribute('n', $collection->getShortname());
                foreach ($collection->getParents() as $parent) {
                    // Note: esperons qu'il n'y aura toujours qu'un seul parent
                    if ($parent instanceof Hal_Site_Collection) {
                        $idno->setAttribute('corresp', $parent->getShortname());
                    }
                    break; // pour etre sur qu'il n'y aura pas deux attributs identiques
                }
                $ss->appendChild($idno);
            }
        }
        return $ss;
    }

    /**
     * @return DOMElement
     * return <notesStmt>
     */

    private function createNotesStmt() {
        $ns = $this->_xml->createElement('notesStmt');
        // List of Meta in notesStmt MetaName => elementName, value for attribute Type, flag for translating
        $notesStmt = array(
            "comment"              => array(self::VALUEATTRIBUTE => "commentary"  , self::TRANSLATINGFLAG => false),
            "description"          => array(self::VALUEATTRIBUTE => "description" , self::TRANSLATINGFLAG => false),
            "audience"             => array(self::VALUEATTRIBUTE => "audience"    , self::TRANSLATINGFLAG => true ),
            "reportType"           => array(self::VALUEATTRIBUTE => "report"      , self::TRANSLATINGFLAG => true ),
            "imageType"            => array(self::VALUEATTRIBUTE => "image"       , self::TRANSLATINGFLAG => true ),
            "lectureType"          => array(self::VALUEATTRIBUTE => "lecture"     , self::TRANSLATINGFLAG => true ),
            "invitedCommunication" => array(self::VALUEATTRIBUTE => "invited"     , self::TRANSLATINGFLAG => true ),
            "popularLevel"         => array(self::VALUEATTRIBUTE => "popular"     , self::TRANSLATINGFLAG => true ),
            "peerReviewing"        => array(self::VALUEATTRIBUTE => "peer"        , self::TRANSLATINGFLAG => true ),
            "proceedings"          => array(self::VALUEATTRIBUTE => "proceedings" , self::TRANSLATINGFLAG => true ),
            "inria_degreeType"     => array(self::VALUEATTRIBUTE => self::DEGREE  , self::TRANSLATINGFLAG => true ),
            "dumas_degreeType"     => array(self::VALUEATTRIBUTE => self::DEGREE  , self::TRANSLATINGFLAG => true ),
            "democrite_degreeType" => array(self::VALUEATTRIBUTE => self::DEGREE  , self::TRANSLATINGFLAG => true ),
            "otherType"            => array(self::VALUEATTRIBUTE => "other"       , self::TRANSLATINGFLAG => true ),
            "platform"             => array(self::VALUEATTRIBUTE => "platform"    , self::TRANSLATINGFLAG => false ),
            "version"              => array(self::VALUEATTRIBUTE => "version"     , self::TRANSLATINGFLAG => false ),
            "programmingLanguage"  => array(self::VALUEATTRIBUTE => "programmingLanguage" , self::TRANSLATINGFLAG => false ),
        );
        foreach ($notesStmt as $noteName => $noteInformation) {
            $noteValuesObj = $this->_HalDocument->getMetaObj($noteName);
            if ($noteValuesObj === null) continue; // pas de meta correspondante
            $noteValues = $noteValuesObj -> getValue();
            if (! is_array($noteValues)) {
                $noteValues = [$noteValues];
            }
            foreach ($noteValues as $noteValue) {
                if($noteValue != '') {
                    if ($noteInformation[self::TRANSLATINGFLAG]) {
                        $note = $this->_xml->createElement("note", Ccsd_Tools::translate(Hal_Referentiels_Metadata::getLabel($noteName, $noteValue), 'en'));
                        $note->setAttribute("type", $noteInformation[self::VALUEATTRIBUTE]);
                        $note->setAttribute("n", $noteValue);
                    } else {
                        $note = $this->_xml->createElement("note", $noteValue);
                        $note->setAttribute("type", $noteInformation[self::VALUEATTRIBUTE]);
                    }
                    $ns->appendChild($note);
                }
            }
        }
        return $ns;
    }

    /**
     * @return DOMElement|null
     * return <analytic> including :
     * <title>
     * <title type="sub">
     * <author> */
    private function createAnalytic() {
        // TODO: analytic doit etre non present pour les types:
        //           Direction ouvrage, Brevet, rapport, these, memoire etudiant , HDR, Cours, Ouvrage , Logiciel, Video
        // ET doit etre plutot present dans monogr
        // Mettre un commentaire pour faire le pb de compatibilite et de depreciation
        // Attention: les auteurs et le titre seront donc a des endroits differents suivant typology
        $analytic = $this->_xml->createElement('analytic');
        foreach ($this->_HalDocument->getTitle() as $l => $t) {
            $analytic ->appendChild($this->createTitle($t, $l));
        }
        foreach ($this->_HalDocument->getSubTitle() as $l => $t) {
            $analytic ->appendChild($this->createSubTitle($t, $l));
        }
        foreach ($this ->createAuthors() as $autnode) {
            $analytic ->appendChild($autnode);
        }   // Title
        if ($analytic->hasChildNodes()) {
            return $analytic;
        } else {
            return null;
        }
    }

    /**
     * @return DOMElement|null
     */
    private function createNnt() {
        if ( $this->_HalDocument->getMetaObj('nnt') != null ) {
            $idno = $this->_xml->createElement('idno', $this->_HalDocument->getMetaObj('nnt')->getValue());
            $idno->setAttribute('type', 'nnt');
            return $idno;
        }
        return null;
    }
    /**
     * @return DOMElement|null
     */
    private function createNumber() {
        if ( $this->_HalDocument->getMetaObj('number') != null) {
            $idno = $this->_xml->createElement('idno', $this->_HalDocument->getMetaObj('number')->getValue());
            $idno->setAttribute('type', strtolower($this->_HalDocument->getTypDoc()).'Number');
            return $idno;
        }
        return null;
    }
    /**
     * @return DOMElement|null
     */
    private function createIsbn()
    {
        if ( $this->_HalDocument->getMetaObj('isbn') != null ) {
            $idno = $this->_xml->createElement('idno', $this->_HalDocument->getMetaObj('isbn')->getValue());
            $idno->setAttribute('type', 'isbn');
            return $idno;
        }
        return null;
    }

    /**
     * @return DOMElement[]
     */
    private function createLocalReference()
    {
        $localRefs=[];
        $localRefObj = $this->_HalDocument->getMetaObj('localReference') ;
        if ($localRefObj == null) {
            return [];
        }
        foreach ($localRefObj -> getValue() as $ref) {
            $idno = $this->_xml->createElement('idno', $ref);
            $idno->setAttribute('type', 'localRef');
            $localRefs[] = $idno;
        }
        return $localRefs;
    }

    /**
     * @return DOMElement[]
     */
    private function createJournal() {
        $journalObjList=[];

        if  ( $oJ = $this->_HalDocument->getMetaObj('journal') )  {
            /** @var Hal_Document_Meta_Journal $oJ */
            $journalObj = $oJ -> getValue();
            $journalIdNode = $this->_xml->createElement('idno', $journalObj->JID);
            $journalIdNode->setAttribute('type', 'halJournalId');
            $journalIdNode->setAttribute(self::STATUS, strtoupper($journalObj->VALID));
            $journalObjList[]=$journalIdNode;

            if ( $journalObj->ISSN ) {
                $issnNode = $this->_xml->createElement('idno', $journalObj->ISSN);
                $issnNode->setAttribute('type', 'issn');
                $journalObjList[]=$issnNode;
            }
            if ( $journalObj->EISSN ) {
                $eissnNode = $this->_xml->createElement('idno', $journalObj->EISSN);
                $eissnNode->setAttribute('type', 'eissn');
                $journalObjList[]=$eissnNode;
            }
            $journal = $this->_xml->createElement(self::TITLE, $journalObj->JNAME);
            $journal->setAttribute(self::LEVEL, 'j');
            $journalObjList[]=$journal;

        }
        return $journalObjList;
    }

    /**
     * @return DOMElement|null
     */
    private function createBookTitle()
    {
        $title=null;
        $bookTitleObj = $this->_HalDocument->getMetaObj('bookTitle');
        if ($bookTitleObj != null) {
            $title = $this->_xml->createElement(self::TITLE, $bookTitleObj ->getValue());
            $title->setAttribute(self::LEVEL, 'm');
        }
        return $title;
    }

    /**
     * @return DOMElement|null
     */
    public function createSource() {
        $source =null;
        $sourceObj = $this->_HalDocument->getMetaObj('source');
        if ( $this->_HalDocument->getTypDoc() == 'COMM' && $sourceObj != null ) {
            $source = $this->_xml->createElement(self::TITLE, $sourceObj -> getValue());
            $source->setAttribute(self::LEVEL, 'm');
        }
        return $source;
    }
    /**
     *
     * @return DOMElement
     * return <monogr> including many elements like journal<journal> and conference<meeting>, .. */

    private function createMonogr() {
        $monogr = $this->_xml->createElement('monogr');

        $nntNode = $this->createNnt();
        if ($nntNode) $monogr ->appendChild($nntNode);

        $numberNode = $this->createNumber();
        if ($numberNode)  $monogr->appendChild($numberNode);

        $isbn = $this->createIsbn();
        if($isbn) $monogr->appendChild($isbn);

        $localrefs=$this->createLocalReference();
        foreach ($localrefs as $ref) {
            $monogr->appendChild($ref);
        }

        foreach ($this->createJournal() as $journalInfoNode) {
            $monogr->appendChild($journalInfoNode);
        }

        $booktitle=$this->createBookTitle();
        if ($booktitle!=null) $monogr->appendChild($booktitle);

        $source = $this->createSource();   // Pour COMM et meta source
        if ($source != null) $monogr->appendChild($source);

        $conftitle     = $this->_HalDocument->getMetaObj(self::CONFTITLE);
        $confstartdate = $this->_HalDocument->getMetaObj(self::CONFSTARTDATE);
        $confenddate   = $this->_HalDocument->getMetaObj(self::CONFENDDATE) ;
        $city          = $this->_HalDocument->getMetaObj('city');
        $countryObj    = $this->_HalDocument->getMetaObj(self::COUNTRY) ;
        $organizerObj  = $this->_HalDocument->getMetaObj(self::CONFORGANIZER);
        if ( !in_array($this->_HalDocument->_typdoc, array('PATENT', 'IMG', 'MAP', 'LECTURE'))
            && ( $conftitle != null || $confstartdate != null || $confenddate != null || $city != null || $countryObj != null || $organizerObj != null )) {
            $meeting = $this->_xml->createElement('meeting');

            if ( $conftitle != null )
                $meeting->appendChild($this->_xml->createElement(self::TITLE, $conftitle->getValue()));

            if ( $confstartdate != null ) {
                $d = $this->_xml->createElement('date', $confstartdate ->getValue());
                $d->setAttribute('type', 'start');
                $meeting->appendChild($d);
            }

            if ( $confenddate != null ) {;
                $d = $this->_xml->createElement('date', $confenddate->getValue());
                $d->setAttribute('type', 'end');
                $meeting->appendChild($d);
            }

            if ( $city != null ) {
                $meeting->appendChild($this->_xml->createElement('settlement', $city->getValue()));
            }

            if ( $countryObj != null ) {
                $country = $this->_xml->createElement(self::COUNTRY, Zend_Locale::getTranslation(strtoupper($countryObj->getValue()), self::COUNTRY, 'en'));
                $country->setAttribute('key', strtoupper($countryObj->getValue()));
                $meeting->appendChild($country);
            }
            $monogr->appendChild($meeting);
            if ($organizerObj != null) {
                $organizers = $organizerObj->getValue();
                if (count($organizers)) {   // Possible not? An empty metadata?
                    $resp = $this->_xml->createElement('respStmt');
                    $resp->appendChild($this->_xml->createElement('resp', self::CONFORGANIZER));
                    foreach ($organizers as $orga) {
                        $resp->appendChild($this->_xml->createElement('name', $orga));
                    }
                    $monogr->appendChild($resp);
                }
            }
        }
        if ( in_array($this->_HalDocument->_typdoc, array('PATENT', 'IMG', 'MAP', 'LECTURE')) ) {
            $city = $this->_HalDocument->getMetaObj('city');
            if ( $city != null ) {
                $monogr->appendChild($this->_xml->createElement('settlement', $city->getValue()));
            }
            $countryObj = $this->_HalDocument->getMetaObj(self::COUNTRY);
            if ( $countryObj != null ) {
                $country = $this->_xml->createElement(self::COUNTRY, Zend_Locale::getTranslation(strtoupper($countryObj->getValue()), 'territory', 'en'));
                $country->setAttribute('key', strtoupper($countryObj->getValue()));
                $monogr->appendChild($country);
            }
        }
        $scientificEditor = $this->_HalDocument->getMetaObj('scientificEditor') ;
        if ($scientificEditor) {
            foreach ($scientificEditor->getValue() as $edsci) {
                $edsci = trim($edsci, ", \t\n\r\0\x0B");  // On enleve les virgules finales... pour eviter les editeurs vides!
                $editorArray = explode(',', $edsci);
                switch (count($editorArray)) {
                    case 0: // pas possible!!!
                    case 1: // 1 seul editeur
                    case 2: // 2 editeurs, ou bien un: prenom, nom: tant pis on laisse, bibtex ne platera pas, c'est deja ca.
                        $monogr->appendChild($this->_xml->createElement(self::EDITOR, $edsci));
                        break;
                    default: // > 2
                        // Au lieu de mettre une meta par editeur, il y a des editeurs separes par des virgules, on les mets dans un tag chacun
                        foreach ($editorArray as $edsci2) {
                            $edsci2 = trim($edsci2);  // On traite la chaine...
                            $monogr->appendChild($this->_xml->createElement(self::EDITOR, $edsci2));
                        }
                        break;
                }
            }
        }

        // sourceDesc>biblStruct>monogr>imprint
        $imprint  = $this->_xml->createElement('imprint');
        $publishers = $this->_HalDocument->getMetaObj(self::PUBLISHER);
        if ($publishers)
            foreach ( $publishers ->getValue() as $publisher ) {
                $imprint->appendChild($this->_xml->createElement(self::PUBLISHER, $publisher));
            }
        // Le cas ci dessous peut arriver dans le cas du type OTHER (un journal et des publisher
        $oJ = $this->_HalDocument->getMetaObj('journal');
        if ( $oJ != null ) {
            /** @var  Hal_Document_Meta_Journal  $oJ */
            /** @var Ccsd_Referentiels_Journal $journal */
            $journal = $oJ ->getValue();
            $journalPublisher = $journal->PUBLISHER;
            if ( $journalPublisher ) {
                $docPulisher = $this->_HalDocument->getMetaObj(self::PUBLISHER);
                // Si pas deja present dans publisher, on l'ajoute
                if (! ($docPulisher && in_array(strtolower($journalPublisher), array_map('strtolower', $docPulisher->getValue())) )) {
                    $imprint->appendChild($this->_xml->createElement(self::PUBLISHER, $journalPublisher));
                }
            }
        }

        $publicationLocation = $this->_HalDocument->getMetaObj('publicationLocation');
        if ( $publicationLocation != null ) {
            $imprint->appendChild($this->_xml->createElement('pubPlace', $publicationLocation->getValue()));
        }
        $serie = $this->_HalDocument->getMetaObj(self::SERIE);
        if ( $serie != null ) {
            $bs = $this->_xml->createElement(self::BIBLSCOPE, $serie -> getValue());
            $bs->setAttribute('unit', self::SERIE);
            $imprint->appendChild($bs);
        }
        $volume = $this->_HalDocument->getMetaObj(self::VOLUME);
        if ( $volume != null) {
            $bs = $this->_xml->createElement(self::BIBLSCOPE, $volume->getValue());
            $bs->setAttribute('unit', self::VOLUME);
            $imprint->appendChild($bs);
        }
        $issue = $this->_HalDocument->getMetaObj(self::ISSUE);
        if ( $issue != null ) {
            $bs = $this->_xml->createElement(self::BIBLSCOPE, $issue->getValue());
            $bs->setAttribute('unit', self::ISSUE);
            $imprint->appendChild($bs);
        }
        $page = $this->_HalDocument->getMetaObj('page');
        if ( $page != null ) {
            $bs = $this->_xml->createElement(self::BIBLSCOPE, $page->getValue());
            $bs->setAttribute('unit', 'pp');
            $imprint->appendChild($bs);
        }
        $date = $this->_HalDocument->getMetaObj('date');
        if ( $date != null ) {
            $d = $this->_xml->createElement('date', $date->getValue());
            if ( in_array($this->_HalDocument->_typdoc, array('THESE', 'HDR', 'MEM', 'ETABTHESE')) ) {
                $d->setAttribute('type', 'dateDefended');
            } else {
                $d->setAttribute('type', 'datePub');
            }
            $circa = $this->_HalDocument->getMetaObj('circa');
            if ( $circa && $circa->getValue() == 1 ) {
                $d->setAttribute('precision', 'unknown');
            }
            $inpress = $this->_HalDocument->getMetaObj('inPress') ;
            if ( $inpress != null  && $inpress->getValue()== 1 ) {
                $d->setAttribute('subtype', 'inPress');
            }
            $imprint->appendChild($d);
        }
        $edate = $this->_HalDocument->getMetaObj('edate');
        if ( $edate !=null ) {
            $d = $this->_xml->createElement('date', $edate->getValue());
            $d->setAttribute('type', 'dateEpub');
            $imprint->appendChild($d);
        }
        $monogr->appendChild($imprint);
        $authorityInstitution = $this->_HalDocument->getMetaObj('authorityInstitution');
        if ($authorityInstitution)
            foreach ( $authorityInstitution  -> getValue() as $orgthe ) {
                $auth = $this->_xml->createElement(self::AUTHORITY, $orgthe);
                $auth->setAttribute('type', 'institution');
                $monogr->appendChild($auth);
            }
        $thesisSchool = $this->_HalDocument->getMetaObj('thesisSchool');
        if ($thesisSchool)
            foreach ( $thesisSchool->getValue() as $school ) {
                $auth = $this->_xml->createElement(self::AUTHORITY, $school);
                $auth->setAttribute('type', 'school');
                $monogr->appendChild($auth);
            }
        $director = $this->_HalDocument->getMetaObj('director');
        if ($director)
            foreach ( $director ->getValue() as $dir ) {
                $auth = $this->_xml->createElement(self::AUTHORITY, $dir);
                $auth->setAttribute('type', 'supervisor');
                $monogr->appendChild($auth);
            }
        $inria_directorEmail = $this->_HalDocument->getMetaObj('inria_directorEmail');
        if ( $inria_directorEmail  != null ) {
            $auth = $this->_xml->createElement(self::AUTHORITY, $inria_directorEmail -> getValue());
            $auth->setAttribute('type', 'supervisorEmail');
            $monogr->appendChild($auth);
        }
        $memsic_directorEmail =  $this->_HalDocument->getMetaObj('memsic_directorEmail');
        if ( $memsic_directorEmail != null ) {
            $auth = $this->_xml->createElement(self::AUTHORITY, $memsic_directorEmail ->getValue());
            $auth->setAttribute('type', 'supervisorEmail');
            $monogr->appendChild($auth);
        }
        $committee = $this->_HalDocument->getMetaObj('committee') ;
        if ($committee)
            foreach ( $committee -> getValue() as $jury ) {
                $auth = $this->_xml->createElement(self::AUTHORITY, $jury);
                $auth->setAttribute('type', 'jury');
                $monogr->appendChild($auth);
            }
        return $monogr;
    }
    /**
     * @return DOMElement|null
     * return <series> */

    private function createSeries() {
        $series=null;
        $seriesEditors = [];
        $seriesEditorObj = $this->_HalDocument->getMetaObj('seriesEditor');
        if ($seriesEditorObj) {
            $seriesEditors = $seriesEditorObj->getValue();
        }
        $lectureNameObj = $this->_HalDocument->getMetaObj(self::LECTURENAME);
        if ( count($seriesEditors) || $lectureNameObj != null) {
            $series = $this->_xml->createElement('series');
            foreach ( $seriesEditors as $edcoll ) {
                $series->appendChild($this->_xml->createElement(self::EDITOR, $edcoll));
            }
            if ( $lectureNameObj) {
                $series->appendChild($this->_xml->createElement(self::TITLE, $lectureNameObj -> getValue()));
            }
        }
        return $series;
    }

    /**
     * @return DOMElement[]
     * return <idno> in <biblStruct> */

    private function createIdentifier() {
        $idnos = [];
        $identifierObj = $this->_HalDocument->getMetaObj('identifier');
        if ($identifierObj === null)
            return $idnos;
        foreach ($identifierObj->getValue() as $code => $id) {
            $idno = $this->_xml->createElement('idno', $id);
            $idno->setAttribute('type', $code);
            array_push($idnos, $idno);
        }
        return $idnos;
    }
    /**
     * @return DOMElement[]
     * return <ref> type seeAlso in <biblStruct>*/

    private function createSeeAlso() {
        $refs = [];
        $urls = [];
        $urlsObj = $this->_HalDocument->getMetaObj('seeAlso');
        if ($urlsObj) $urls = $urlsObj-> getValue();

        $codeRepositoryObj = $this->_HalDocument->getMetaObj('codeRepository');
        if ($codeRepositoryObj) {
            $url = $codeRepositoryObj ->getValue(); // Compatibilite
            $urls[] = $url;
            $ref = $this->_xml->createElement('ref');
            $ref->setAttribute('type', 'codeRepository');
            $ref->setAttribute('target', $url);
            $refs[] = $ref;
        }
        // Todo: Ne pas mettre le codeRepository dans seeAlso mais dans un ref type="codeRepository"
        // Todo il faudrait mettre les URL dans un attribut target plutôt que dans la valeur de la balise
        // Todo: supprimer l'url dans le contenu de l'element ref
        foreach ( $urls as $url ) {
            $ref = $this->_xml->createElement('ref', $url);
            // TODO: peut etre verifier que l'url corresponds a une url
            // Si oui, alors mettre dans target, sinon, en text node
            $comment = $this->_xml -> createComment("Url must be retreive in target attribute, text node with Url will be removed in future version");
            $ref->appendChild($comment);
            $ref->setAttribute('type', 'seeAlso');
            $ref->setAttribute('target', $url);
            $refs[] = $ref;
        }
        return $refs;
    }
    /**
     * @return DOMElement
     * return <ref> type publisher in <biblStruct>*/

    private function createPublisherLink() {
        $ref = null;
        $publisherLinkObj = $this->_HalDocument->getMetaObj('publisherLink');
        if ( $publisherLinkObj ) {
            $ref = $this->_xml->createElement('ref', $publisherLinkObj ->getValue());
            $ref->setAttribute('type', self::PUBLISHER);
        }
        return $ref;
    }
    /**
     * @return DOMElement[]
     * return <relatedItem> */

    private function createRelatedItem() {
        $relatedItems = [];
        foreach ( $this->_HalDocument->_related as $info) {
            $item = $this->_xml->createElement('relatedItem', $info['INFO']);
            $item->setAttribute(self::TARGET, $info['URI']);
            $item->setAttribute('type', $info['RELATION']);
            array_push($relatedItems, $item);
        }
        return $relatedItems;
    }
    /**
     * @return DOMElement|null
     * Manage Latitude and Longitude of image docs
     * return <listPlace> */

    private function createListPlace() {
        // Test if the values of the elements latitude and longitude aren't null
        $listPlace = null;
        $latitudeObj = $this->_HalDocument->getMetaObj('latitude') ;
        $longitudeObj = $this->_HalDocument->getMetaObj('longitude');
        if ($longitudeObj && $latitudeObj) {
            // Create a listPlace element
            $listPlace = $this->_xml->createElement('listPlace');
            // Create a place element
            $place = $this->_xml->createElement('place');
            // Create a location element
            $location = $this->_xml->createElement('location');
            // Create a geo element and assign the values of latitude and longitude to it
            $geo = $this->_xml->createElement('geo', $latitudeObj->getValue() . ' ' . $longitudeObj->getValue());
            // Add the geo element to the location element
            $location->appendChild($geo);
            // Add the location element to the place element
            $place->appendChild($location);
            // Add the place element to the listPlace element
            $listPlace->appendChild($place);
        }
        return $listPlace;
    }
    /**
     * @return DOMElement|null
     * Manage Duration of video docs
     * return <recordingStmt> */

    private function createRecordingStmt() {
        // Test if the value of the duration element isn't null
        $recordingStmt = null;
        $durationObj = $this->_HalDocument->getMetaObj('duration');
        if($durationObj != null) {
            // Create a recordingStmt element
            $recordingStmt = $this->_xml->createElement('recordingStmt');
            //Create a recording element
            $recording = $this->_xml->createElement('recording');
            // Get the type of document
            if($this->_HalDocument->getTypDoc() == 'VIDEO') {
                // if video, assign the value VIDEO to attribute TYPE of the recording element
                $recording->setAttribute('type', 'video');
            }
            else if ($this->_HalDocument->getTypDoc() == 'SON') {
                // if audio, assign the value audio to attribute TYPE of the recording element
                $recording->setAttribute('type', 'audio');
            }
            // Assign the value of duration to the attribute dur of the recording element
            if ($durationObj)
                $recording->setAttribute('dur', $durationObj->getValue());
            // Add the recording element to the recordingStmt element
            $recordingStmt->appendChild($recording);

        }
        return $recordingStmt;
    }

    /**
     * @return DOMElement
     * profileDesc>langUsage>language
     * return <languages> */

    private function createLanguages() {
        $lu = $this->_xml->createElement('langUsage');
        $langObj = $this->_HalDocument->getMetaObj(self::LANGUAGE);

        if ($langObj) {
            $docLang = $langObj->getValue();
        } else {
            // Si pas de langue, on met 'en'
            $docLang = 'en';
        }
        $tradLang = Zend_Locale::getTranslation($docLang, self::LANGUAGE, 'en');

        $lang = $this->_xml->createElement(self::LANGUAGE, $tradLang);
        $lang->setAttribute('ident', $docLang);
        $lu->appendChild($lang);

        return $lu;
    }
    /**
     * @return DOMElement
     * profileDesc>textClass
     * return <textClass> */

    private function createTextClass() {
        $textClass = $this->_xml->createElement('textClass');
        // keyword
        if ( count($kwls = $this->_HalDocument->getKeywords()) ) {
            $kws = $this->_xml->createElement('keywords');
            $kws->setAttribute(self::SCHEME, 'author');
            foreach ( $kwls as $lang=>$keywords ) {
                if ( is_array($keywords) ) {
                    foreach ( $keywords as $keyword ) {
                        $kw = $this->_xml->createElement('term', $keyword);
                        $kw->setAttribute(self::XMLLANG, $lang);
                        $kws->appendChild($kw);
                    }
                } else {
                    $kw = $this->_xml->createElement('term', $keywords);
                    $kw->setAttribute(self::XMLLANG, $lang);
                    $kws->appendChild($kw);
                }
            }
            $textClass->appendChild($kws);
        }
        // classif
        $classif = $this->_HalDocument->getMetaObj('classification');
        if ( $classif != null) {
            $kws = $this->_xml->createElement(self::CLASSCODE, $classif->getValue());
            $kws->setAttribute(self::SCHEME, 'classification');
            $textClass->appendChild($kws);
        }
        // mesh
        $meshObj = $this->_HalDocument->getMetaObj('mesh');
        if ($meshObj)
            foreach ( $meshObj->getValue() as $mesh ) {
                $kws = $this->_xml->createElement(self::CLASSCODE, $mesh);
                $kws->setAttribute(self::SCHEME, 'mesh');
                $textClass->appendChild($kws);
            }
        // jel
        $jelObj = $this->_HalDocument->getMetaObj('jel');
        if ($jelObj)
            foreach ( $jelObj->getValue() as $jel ) {
                $kws = $this->_xml->createElement(self::CLASSCODE, Ccsd_Tools_String::getHalMetaTranslated($jel, 'en', '/', 'jel'));
                $kws->setAttribute(self::SCHEME, 'jel');
                $kws->setAttribute('n', $jel);
                $textClass->appendChild($kws);
            }
        // acm
        $acmObj = $this->_HalDocument->getMetaObj('acm');
        if ($acmObj)
            foreach ( $acmObj ->getValue() as $acm ) {
                $kws = $this->_xml->createElement(self::CLASSCODE, Ccsd_Tools_String::getHalMetaTranslated($acm, 'en', '/', 'acm'));
                $kws->setAttribute(self::SCHEME, 'acm');
                $kws->setAttribute('n', $acm);
                $textClass->appendChild($kws);
            }
        // domain
        $domainObj = $this->_HalDocument->getMetaObj('domain');
        if ($domainObj)
            foreach ( $domainObj-> getValue() as $domain ) {
                $d = $this->_xml->createElement(self::CLASSCODE, Ccsd_Tools_String::getHalDomainTranslated($domain, 'en', '/'));
                $d->setAttribute(self::SCHEME, 'halDomain');
                $d->setAttribute('n', $domain);
                $textClass->appendChild($d);
            }
        // inra classification spécifique VOCINRA
        $vocinraObj = $this->_HalDocument->getMetaObj('inra_indexation_local');
        if ($vocinraObj)
            foreach($vocinraObj->getValue() as $vocinraTerm){
                $v = $this->_xml->createElement(self::CLASSCODE,$vocinraTerm);
                $v->setAttribute(self::SCHEME,'VOCINRA');
                $v->setAttribute('n',$vocinraTerm);
                $textClass->appendChild($v);
            }
        // typdoc
        $typdoc = $this->_xml->createElement(self::CLASSCODE, Ccsd_Tools::translate('typdoc_'.$this->_HalDocument->getTypDoc(), 'en'));
        $typdoc->setAttribute(self::SCHEME, 'halTypology');
        $typdoc->setAttribute('n', $this->_HalDocument->getTypDoc());
        $textClass->appendChild($typdoc);
        return $textClass;
    }
    /**
     * @return DOMElement[]
     * profileDesc>abstract
     * return <abstract> */

    private function createAbstract()
    {
        $abstractList = [];
        foreach ($this->_HalDocument->getAbstract() as $l => $t) {
            if ( is_array($t) ) {
                // Hum: si plusieurs fois la meme langue ???
                // On ne prends que le premier!
                $t = current($t);
            }
            $textInP = $this->_xml->createElement('p', $t);
            $abs = $this->_xml->createElement('abstract');
            $abs->appendChild($textInP);
            $abs->setAttribute(static::XMLLANG, $l);
            $abstractList[] = $abs;
        }
        return $abstractList;
    }
    /**
     * @return DOMElement|null
     * profileDesc>particDesc>org
     * return <org> */

    private function createOrg() {
        $collaboration=null;
        $collabObj = $this->_HalDocument->getMetaObj(self::COLLABORATION);
        if ($collabObj) {
            $collaboration = $this->_xml->createElement('particDesc');
            foreach ( $collabObj ->getValue() as $collab ) {
                $org = $this->_xml->createElement('org', $collab);
                $org->setAttribute('type', 'consortium');
                $collaboration->appendChild($org);
            }
        }
        return $collaboration;
    }
    /**
     * @return DOMElement|null
     * return <back> */

    private function createBack() {
        $back = null;
        $structures = $this->createStructures();
        $projects = $this->createProjects();
        // Create <back>
        $back = $this->_xml->createElement('back');
        if ($structures || $projects) {
            // Append structures to <back>
            if ($structures)
                $back -> appendChild($structures);
            // Append projects to <back>
            if ($projects)
                $back -> appendChild($projects);
            // Return <back>

        }
        return $back;
    }
    /**
     * @return DOMElement
     * return <org> of structures */

    private function createStructures() {
        $listOrg = null;
        $structures = $this->_HalDocument->_structures;
        if ( isset($structures) && is_array($structures) && count($structures) ) {
            $listOrg = $this->_xml->createElement('listOrg');
            $listOrg->setAttribute('type', 'structures');
            $parents = array();

            foreach ( $structures as $s ) {
                $struct = new Hal_Document_Structure($s->getStructId());
                if ( $struct->getStructid() == 0 ) {
                    continue;
                }
                $parents = array_merge($parents, $struct->getParentsStructids());
                $listOrg -> appendChild($struct->getXMLNode($this->_xml));
            }
            $structuresIds = array_map( function($s) {
                /** @var  Hal_Document_Structure $s */
                return $s->getStructid(); } , $structures);
            foreach ( array_unique(array_diff($parents, $structuresIds)) as $sid ) {
                $struct = new Ccsd_Referentiels_Structure($sid);
                if ( $struct->getStructid() == 0 ) {
                    continue;
                }
                $listOrg->appendChild($struct->getXMLNode($this->_xml));
            }

        }
        return $listOrg;
    }
    /**
     * @return DOMElement
     * return <org> */

    private function createProjects()
    {
        // projets
        $listOrg = null;
        $anrProjects = $this->_HalDocument->getMetaObj(self::ANRPROJECT);
        $europProjects = $this->_HalDocument->getMetaObj(self::EURPROJECT);
        if ($anrProjects || $europProjects) {
            $listOrg = $this->_xml->createElement('listOrg');
            $listOrg->setAttribute('type', 'projects');
            if ($anrProjects)
                foreach ($anrProjects->getValue() as $p) {
                    if ($p instanceof Ccsd_Referentiels_Anrproject) {
                        $listOrg->appendChild($p->getXMLNode($this->_xml));
                    }
                }
            if ($europProjects)
                foreach ($europProjects->getValue() as $p) {
                    if ($p instanceof Ccsd_Referentiels_Europeanproject) {
                        $listOrg->appendChild($p->getXMLNode($this->_xml));
                    }
                }
        }
        return $listOrg;
    }

    /**
     * Calcul la valeur de l'XML id pour la rendre unique sur l'ensemble des documents.
     * @param $xmlid
     * @return string
     */
    public static function changeXmlIdValue($xmlid, $docid) {
        $xmlid = "ref$docid-$xmlid";
        return $xmlid;
    }
    /**
     * @todo Ne semble pas utilisee
     * Implement hal doc references in TEI
     * @return DOMElement|null
     */
    public function createReferences() {
        $halDocReferences = new Hal_Document_References($this->_HalDocument->getDocid());
        $halDocReferences->load();
        /** @var string[][]  $references */
        $references = $halDocReferences->get();
        if(count($references)) {
            $listBibl = $this->_xml->createElement('listBibl');
            $listBibl->setAttribute('type', 'references');
            foreach ($references as $reference) {
                if ($reference[Hal_Document_References::REFXML] != '') {
                    $newdom = new Ccsd_DOMDocument();
                    $newdom->loadXML((string)$reference[Hal_Document_References::REFXML]);
                    /** @var DOMElement $node */
                    $node = $newdom ->getElementsByTagName('biblStruct')->item(0);
                    $xmlid = $node-> getAttribute('xml:id');
                    $node-> setAttribute('xml:id', self::changeXmlIdValue($xmlid, $this->_HalDocument->getDocid()));
                    if ($node) {
                        $importedNode = $this->_xml->importNode($node, true);
                        $listBibl->appendChild($importedNode);
                    }
                }
            }
            return $listBibl;
        }
        return null;
    }
    /**
     * @deprecated ??? Ne semble pas utilisee
     * @param Hal_Document_Author $hal_document_author
     * @return string
     * return a specific author */
    public function createAuthor($hal_document_author) {
        $autFromReferentiels = new Ccsd_Referentiels_Author($hal_document_author->getAuthorid());
        return $autFromReferentiels->getXML(false);
    }
}

