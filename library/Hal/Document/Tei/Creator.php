<?php

/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen MALLA OSMAN
 * Date: 29/11/2016
 * Time: 16:58
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
    //<editor-fold desc="Constants">
    /**
     * Constants */

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
    //</editor-fold>

    //<editor-fold desc="Properties">
    /**
     * Properties */

    // Browser header
    protected $_header      = null;
    // Element <xml>
    protected $_xml         = null;
    // Element <tei>
    protected $_root        = null;
    // Object Hal_Document
    protected $_HalDocument = null;
    //</editor-fold>

    //<editor-fold desc="Constructor">
    /**
     * receive a HAL_Document object
     * create two elements <XML> and <TEI> by default */

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
        $this->_root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.tei-c.org/ns/1.0');
        $this->_root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:hal', 'http://hal.archives-ouvertes.fr/');
        $this->_xml->appendChild($this->_root);
        $this->_header = $_header;
    }
    //</editor-fold>

    //<editor-fold desc="appendStringToDomNode">
    /**
     * Append a string child to a dom parent
     * @param DOMElement $domParent
     * @param DOMElement|string $domChild
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

    private function validateTEI($XML_TEI) {
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
     * return an XML string that represent all TEI elements of a HAL Document */

    public function create() {

        // =====================================================================
        // ============================= teiHeader =============================
        // =====================================================================
        // Create and append <teiHeader>
        $this->appendStringToDomNode($this->_root, $this->createTeiHeader(false));

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
        // Create <titleStmt>
        $ts = $this->_xml->createElement('titleStmt');
        // Create <title> and append it to <titleStmt>
        $this->appendStringToDomNode($ts, $this->createTitle(false));
        // Create <title> (subtitle) and append it to <titleStmt>
        $this->appendStringToDomNode($ts, $this->createSubTitle(false));
        // Create <author> and append it to <titleStmt>
        $this->appendStringToDomNode($ts, $this->createAuthors(false));
        // Create <editor> and append it to <titleStmt>
        $this->appendStringToDomNode($ts, $this->createContributors(false));
        // Create <funder> and append it to <titleStmt>
        $this->appendStringToDomNode($ts, $this->createFinancements(false));
        // Append <titleStmt> to <biblFull>
        $b->appendChild($ts);

        // ============================ editionStmt ===========================
        // Create <editionStmt>
        $es = $this->_xml->createElement('editionStmt');
        // Create <edition> and append it to <editionStmt>
        $this->appendStringToDomNode($es, $this->createEdition(false));
        // Create <respStmt> and append it to <editionStmt>
        $this->appendStringToDomNode($es, $this->createRespStmt(false));
        // Append <editionStmt> to <biblFull>
        $b->appendChild($es);

        // ========================== publicationStmt =========================
        // Create <publicationStmt> and append it to <biblFull>
        $this->appendStringToDomNode($b, $this->createPublicationStmt(false));

        // ============================= seriesStmt ===========================
        // Create <seriesStmt> and append it to <biblFull>
        $this->appendStringToDomNode($b, $this->createSeriesStmt(false));

        // ============================== notesStmt ===========================
        // Create <notesStmt> and append it to <biblFull>
        $this->appendStringToDomNode($b, $this->createNotesStmt(false));

        // ============================= sourceDesc ===========================
        // Create <sourceDesc>
        $sd = $this->_xml->createElement('sourceDesc');

        // Create <biblStruct>
        $biblStruct = $this->_xml->createElement('biblStruct');
        // Create <analytic> and append it to <biblStruct>
        $this->appendStringToDomNode($biblStruct, $this->createAnalytic(false));
        // Create <monogr> and append it to <biblStruct>
        $this->appendStringToDomNode($biblStruct, $this->createMonogr(false));
        // Create <series> and append it to <biblStruct>
        $this->appendStringToDomNode($biblStruct, $this->createSeries(false));
        // Create <idno> and append it to <biblStruct>
        $this->appendStringToDomNode($biblStruct, $this->createIdentifier(false));
        // Create <ref> type seeAlso and append it to <biblStruct>
        $this->appendStringToDomNode($biblStruct, $this->createSeeAlso(false));
        // Create <ref> type publisher and append it to <biblStruct>
        $this->appendStringToDomNode($biblStruct, $this->createPublisherLink(false));
        // Create <relatedItem> and append it to <biblStruct>
        $this->appendStringToDomNode($biblStruct, $this->createRelatedItem(false));
        // Append <biblStruct> to <sourceDesc>
        $sd->appendChild($biblStruct);

        // Create <listPlace> and append it to <sourceDesc>
        $this->appendStringToDomNode($sd, $this->createListPlace(false));
        // Create <recordingStmt> and append it to <sourceDesc>
        $this->appendStringToDomNode($sd, $this->createRecordingStmt(false));

        // Append <sourceDesc> to <biblFull>
        $b->appendChild($sd);

        // ============================= profileDesc ===========================
        // Create <profileDesc>
        $pd = $this->_xml->createElement('profileDesc');

        // Create <languages> and append it to <profileDesc>
        $this->appendStringToDomNode($pd, $this->createLanguages(false));

        // Create <textClass> and append it to <profileDesc>
        $this->appendStringToDomNode($pd, $this->createTextClass(false));

        // Create <abstract> and append it to <profileDesc>
        $this->appendStringToDomNode($pd, $this->createAbstract(false));

        // Create <org> and append it to <profileDesc>
        $this->appendStringToDomNode($pd, $this->createOrg(false));

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
        $this->appendStringToDomNode($text, $this->createBack(false));

        // APPEND THE ROOT AND RETURN THE XML STRING
        // Append <text> to <tei>
        $this->_root->appendChild($text);

        // Validation with schema TEI
        //libxml_use_internal_errors(true);
        //$this->validateTEI($this->_xml->saveXML($this->_xml->documentElement));

        // Return the XML
        return ($this->_header) ? $this->_xml->saveXML() : $this->_xml->saveXML($this->_xml->documentElement);
    }
    //</editor-fold>

    //<editor-fold desc="createTeiHeader">
    /**
     * return <teiHeader> */

    public function createTeiHeader($showXML = true) {
        $head = $this->_xml->createElement('teiHeader');
        $fd = $this->_xml->createElement('fileDesc');
        $ts = $this->_xml->createElement('titleStmt');
        $title = $this->_xml->createElement(self::TITLE, 'HAL TEI export of '.$this->_HalDocument->_identifiant);
        $ts->appendChild($title);
        $fd->appendChild($ts);
        $ps = $this->_xml->createElement('publicationStmt');
        $ps->appendChild($this->_xml->createElement('distributor', 'CCSD'));
        $headeravailability = $this->_xml->createElement('availability');
        $headeravailability->setAttribute(self::STATUS, 'restricted');
        if ( $this->_HalDocument->getLicence() != '' ) {
            $headerlicence = $this->_xml->createElement(self::LICENCE, Zend_Registry::get(self::ZENDTRANSLATE)->translate(Hal_Referentiels_Metadata::getLabel(self::LICENCE, $this->_HalDocument->getLicence()), 'en'));
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
        if($showXML) {
            return $this->convertToXMLString($this->_xml->saveXML($head));
        } else {
            return $this->_xml->saveXML($head);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createTitle">
    /**
     * return the title of the document <title> */

    public function createTitle($showXML = true) {
        $titlesList = [];
        foreach ($this->_HalDocument->getTitle() as $l => $t) {
            $tit = $this->_xml->createElement(self::TITLE, $t);
            $tit->setAttribute(self::XMLLANG, $l);
            array_push($titlesList, $this->_xml->saveXML($tit));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $titlesList));
        } else {
            return implode("", $titlesList);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createSubTitle">
    /**
     * return the sub title of the document <title type="sub"> */

    public function createSubTitle($showXML = true) {
        $subTitlesList = array();
        foreach ($this->_HalDocument->getMeta('subTitle') as $l => $t) {
            $stit = $this->_xml->createElement(self::TITLE, $t);
            $stit->setAttribute(self::XMLLANG, $l);
            $stit->setAttribute('type', 'sub');
            array_push($subTitlesList, $this->_xml->saveXML($stit));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $subTitlesList));
        } else {
            return implode("", $subTitlesList);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createAuthors">
    /**
     * return the authors of the document <author> */

    public function createAuthors($showXML = true) {
        $authorsList = array();
        foreach ($this->_HalDocument->_authors as $a) {
            $autFromReferentiels = new Ccsd_Referentiels_Author($a->getAuthorid());
            array_push($authorsList, $autFromReferentiels->getXML(false, $a->getStructid(), $a->getQuality()));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $authorsList));
        } else {
            return implode("", $authorsList);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createContributors">
    /**
     * return the contributors of the document <editors> */

    public function createContributors($showXML = true) {
        if ($this->_HalDocument->getContributor(self::LASTNAME) != '' && $this->_HalDocument->getContributor(self::FIRSTNAME) != '' && $this->_HalDocument->getContributor(self::EMAIL) != '') {
            $c = $this->_xml->createElement(self::EDITOR);
            $c->setAttribute('role', 'depositor');
            $persName = $this->_xml->createElement(self::PERSNAME);
            $persName->appendChild($this->_xml->createElement(self::FORENAME, $this->_HalDocument->getContributor(self::FIRSTNAME)));
            $persName->appendChild($this->_xml->createElement(self::SURNAME, $this->_HalDocument->getContributor(self::LASTNAME)));
            $c->appendChild($persName);
            $email = $this->_xml->createElement(self::EMAIL, Hal_Document_Author::getEmailHashed((string)$this->_HalDocument->getContributor(self::EMAIL), Hal_Settings::EMAIL_HASH_TYPE));
            $email->setAttribute('type',Hal_Settings::EMAIL_HASH_TYPE);
            $c->appendChild($email);
            $emailDomain = $this->_xml->createElement(self::EMAIL, Ccsd_Tools::getEmailDomain((string)$this->_HalDocument->getContributor(self::EMAIL)));
            $emailDomain->setAttribute('type','domain');
            $c->appendChild($emailDomain);

            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($c));
            } else {
                return $this->_xml->saveXML($c);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createFinancements">
    /**
     * return the organization financing the document <funder> */

    public function createFinancements($showXML = true) {
        $ANRProjects = $this->createANRProject(false);
        $europeanProjects = $this->createEuropeanProject(false);
        $fundings = $this->createFunding(false);
        if (strlen($ANRProjects) > 0 || strlen($europeanProjects) > 0 || strlen($fundings) > 0) {
                $funders = [];
            array_push($funders, $ANRProjects);
            array_push($funders, $europeanProjects);
            array_push($funders, $fundings);
            if ($showXML) {
                return $this->convertToXMLString(implode("", $funders));
            } else {
                return implode("", $funders);
            }
        }
    }

    /**
     * return <funder> type ANR Project */

    public function createANRProject($showXML = true) {
        if ( is_array($this->_HalDocument->getMeta(self::ANRPROJECT)) ) {
            $projanrs = [];
            foreach ( $this->_HalDocument->getMeta(self::ANRPROJECT) as $anr ) {
                if ( $anr instanceof Ccsd_Referentiels_Anrproject ) {
                    $p = $this->_xml->createElement(self::FUNDER);
                    $p->setAttribute('ref', '#projanr-'.$anr->ANRID);
                    array_push($projanrs, $this->_xml->saveXML($p));
                }
            }
            if ($showXML) {
                return $this->convertToXMLString(implode("", $projanrs));
            } else {
                return implode("", $projanrs);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createEuropeanProject">
    /**
     * return <funder> type EUROPEAN project */

    public function createEuropeanProject($showXML = true) {
        if ( is_array($this->_HalDocument->getMeta(self::EURPROJECT)) ) {
            $projeuropes = [];
            foreach ( $this->_HalDocument->getMeta(self::EURPROJECT) as $europe ) {
                if ( $europe instanceof Ccsd_Referentiels_Europeanproject ) {
                    $p = $this->_xml->createElement(self::FUNDER);
                    $p->setAttribute('ref', '#projeurop-'.$europe->PROJEUROPID);
                    array_push($projeuropes, $this->_xml->saveXML($p));
                }
            }
            if ($showXML) {
                return $this->convertToXMLString(implode("", $projeuropes));
            } else {
                return implode("", $projeuropes);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createFunding">
    /**
     * return <funder> type funding */

    public function createFunding($showXML = true) {
        if ( is_array($this->_HalDocument->getMeta('funding')) ) {
            $fundings = [];
            foreach ( $this->_HalDocument->getMeta('funding') as $funder ) {
                $f = $this->_xml->createElement(self::FUNDER, $funder);
                array_push($fundings, $this->_xml->saveXML($f));
            }
            if ($showXML) {
                return $this->convertToXMLString(implode("", $fundings));
            } else {
                return implode("", $fundings);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createEdition">
    /**
     * return the edition(s) of the document <edition>  */

    public function createEdition($showXML = true) {
        // Pour les différentes versions
        $editions = [];
        foreach ( $this->_HalDocument->_versions as $n=>$date ) {
            $edition = $this->_xml->createElement(self::EDITION);
            $edition->setAttribute('n', 'v'.$n);
            $d = $this->_xml->createElement('date', $date);
            $d->setAttribute('type', 'whenSubmitted');
            $edition->appendChild($d);
            if ( $this->_HalDocument->_version == $n ) {
                $edition->setAttribute('type', 'current');
                if ( $this->_HalDocument->getMeta(self::WRITINGDATE) ) {
                    $edition->appendChild($d);
                    $d = $this->_xml->createElement('date', $this->_HalDocument->getMeta(self::WRITINGDATE));
                    $edition->appendChild($d);
                    $d->setAttribute('type', 'whenWritten');
                }
                $d = $this->_xml->createElement('date', $this->_HalDocument->_modifiedDate);
                $d->setAttribute('type', 'whenModified');
                $edition->appendChild($d);
                $d = $this->_xml->createElement('date', $this->_HalDocument->_releasedDate);
                $d->setAttribute('type', 'whenReleased');
                $edition->appendChild($d);
                $d = $this->_xml->createElement('date', $this->_HalDocument->_producedDate);
                $d->setAttribute('type', 'whenProduced');
                $edition->appendChild($d);
                if ( $this->_HalDocument->_format == Hal_Document::FORMAT_FILE ) {
                    $d = $this->_xml->createElement('date', $this->_HalDocument->getFirstDateVisibleFile());
                    $d->setAttribute('type', 'whenEndEmbargoed');
                    $edition->appendChild($d);
                    $ref = $this->_xml->createElement('ref');
                    $ref->setAttribute('type', 'file');
                    $ref->setAttribute(self::TARGET, $this->_HalDocument->getUri(true).$this->_HalDocument->getUrlMainFile());
                    $v = $this->_HalDocument->getDateVisibleMainFile();
                    if ( preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $v) ) {
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
            // Metadonnées Local
            // Define an array to store the names of the local metas
            $tableLocalMetas = array();

            // On charge la configuration du portail de dépôt du document pour les métas spécifiques
            $sid = $this->_HalDocument->getSid();

            $portail = Hal_Site_Portail::loadSiteFromId($sid);
            $ini = $portail->getConfigMeta();

            // Get the name of the local meta (if existed) et prepare the process for the integration in CreateTei.php
            foreach ($ini['elements'] as $nomMeta => $element) {
                // Get the value of elements
                foreach ($element as $cle => $val) {
                    // Get element by options
                    if ($cle == "options") {
                        // Test if localTei exist and assigned to true (the portal has specific metas)
                        if (isset($val['localMeta']) && $val['localMeta']) {
                            // Add the names of metas to tha variable tableLocalMetas
                            array_push($tableLocalMetas, $nomMeta);
                        }
                    }
                }
            }
            // Test if the return of the method getLocalMeta is an array and his size to not null
            if(is_array($tableLocalMetas) && count($tableLocalMetas)) {
                // Create a fs element
                $fs = $this->_xml->createElement('fs');
                // Loop over the names of the retrieved local metas
                foreach ($tableLocalMetas as $LML) {
                    // Test if meta value not null
                    if ($this->_HalDocument->getMeta($LML) != '') {
                        $metasList = Hal_Referentiels_Metadata::metaList();
                        // Meta type list with id ans text (e.g. inria_presConf)
                        if (in_array($LML, $metasList)) {
                            ///////////// Creation of string element ////////////////
                            // Create a f element
                            $f = $this->_xml->createElement('f');
                            // Assign the name of the retrieved local meta to the attribute NAME of the f element
                            $f->setAttribute('name', $LML);
                            // Assign the value string to the attribut notation
                            $f->setAttribute(self::NOTATION, self::STRING);
                            // Create a string element and assign the value of the retrieved local meta to it
                            $string = $this->_xml->createElement(self::STRING, Zend_Registry::get(self::ZENDTRANSLATE)->translate(Hal_Referentiels_Metadata::getLabel($LML, $this->_HalDocument->getMeta($LML)), 'en'));
                            // Add the string element to the f element
                            $f->appendChild($string);
                            // Add the f element to the fs element
                            $fs->appendChild($f);
                            ///////////// Creation of numeric element ///////////////
                            $f = $this->_xml->createElement('f');
                            $f->setAttribute('name', $LML);
                            $f->setAttribute(self::NOTATION, self::NUMERIC);
                            $numeric = $this->_xml->createElement(self::NUMERIC, $this->_HalDocument->getMeta($LML));
                            $f->appendChild($numeric);
                            $fs->appendChild($f);
                            // Meta based free text
                        } else {
                            $f = $this->_xml->createElement('f');
                            $f->setAttribute('name', $LML);
                            $f->setAttribute(self::NOTATION, self::STRING);
                            $string = $this->_xml->createElement(self::STRING, $this->_HalDocument->getMeta($LML));
                            $f->appendChild($string);
                            $fs->appendChild($f);
                        }
                    }
                }
                if ($fs->nodeValue !='') {
                    // Add the fs element to the edition element
                    $edition->appendChild($fs);
                }
            }
            array_push($editions, $this->_xml->saveXML($edition));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $editions));
        } else {
            return implode("", $editions);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createDates">
    /**
     * return <date> */

    public function createDates($showXML = true) {
        // Pour les différentes versions
        $editions = [];
        foreach ( $this->_HalDocument->_versions as $n=>$date ) {
            $edition = $this->_xml->createElement(self::EDITION);
            $edition->setAttribute('n', 'v' . $n);
            $d = $this->_xml->createElement('date', $date);
            $d->setAttribute('type', 'whenSubmitted');
            $edition->appendChild($d);
            if ($this->_HalDocument->_version == $n) {
                $edition->setAttribute('type', 'current');
                if ($this->_HalDocument->getMeta(self::WRITINGDATE)) {
                    $edition->appendChild($d);
                    $d = $this->_xml->createElement('date', $this->_HalDocument->getMeta(self::WRITINGDATE));
                    $edition->appendChild($d);
                    $d->setAttribute('type', 'whenWritten');
                }
                $d = $this->_xml->createElement('date', $this->_HalDocument->_modifiedDate);
                $d->setAttribute('type', 'whenModified');
                $edition->appendChild($d);
                $d = $this->_xml->createElement('date', $this->_HalDocument->_releasedDate);
                $d->setAttribute('type', 'whenReleased');
                $edition->appendChild($d);
                $d = $this->_xml->createElement('date', $this->_HalDocument->_producedDate);
                $d->setAttribute('type', 'whenProduced');
                $edition->appendChild($d);
                if ($this->_HalDocument->_format == Hal_Document::FORMAT_FILE) {
                    $d = $this->_xml->createElement('date', $this->_HalDocument->getFirstDateVisibleFile());
                    $d->setAttribute('type', 'whenEndEmbargoed');
                    $edition->appendChild($d);
                }
            }
            array_push($editions, $this->_xml->saveXML($edition));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $editions));
        } else {
            return implode("", $editions);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createRefs">
    /**
     * return the file of document <ref> */

    public function createRefs($showXML = true) {
        // Pour les différentes versions
        $editions = [];
        foreach ($this->_HalDocument->_versions as $n => $date) {
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
            array_push($editions, $this->_xml->saveXML($edition));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $editions));
        } else {
            return implode("", $editions);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createLocalMeta">
    /**
     * IL SEMBLE QU'ON NE PASSE JAMAIS PAR CETTE FONCTION !!!
     * return the local metas of the portail of the document <fs> */

    public function createLocalMeta($showXML = true) {
        // Metadonnées Local
        // Define an array to store the names of the local metas
        $tableLocalMetas = array();

        // On charge la configuration du portail de dépôt du document pour les métas spécifiques
        $sid = $this->_HalDocument->getSid();
        $portail = Hal_Site::loadSiteFromId($sid);
        $ini = $portail->getConfigMeta();

        // Get the name of the local meta (if existed) et prepare the process for the integration in CreateTei.php
        foreach ($ini['elements'] as $nomMeta => $element) {
            // Get the value of elements
            foreach ($element as $cle => $val) {
                // Get element by options
                if ($cle == "options") {
                    // Test if localTei exist and assigned to true (the portal has specific metas)
                    if (isset($val['localMeta']) && $val['localMeta']) {
                        // Add the names of metas to tha variable tableLocalMetas
                        array_push($tableLocalMetas, $nomMeta);
                    }
                }
            }
        }
        // Test if the return of the method getLocalMeta is an array and his size to not null
        if(is_array($tableLocalMetas) && count($tableLocalMetas)) {
            // Create a fs element
            $fs = $this->_xml->createElement('fs');
            // Loop over the names of the retrieved local metas
            foreach ($tableLocalMetas as $LML) {
                // Test if meta value not null
                if ($this->_HalDocument->getMeta($LML) != '') {
                    $metasList = Hal_Referentiels_Metadata::metaList();
                    // Meta type list with id ans text (e.g. inria_presConf)
                    if (in_array($LML, $metasList)) {
                        ///////////// Creation of string element ////////////////
                        // Create a f element
                        $f = $this->_xml->createElement('f');
                        // Assign the name of the retrieved local meta to the attribute NAME of the f element
                        $f->setAttribute('name', $LML);
                        // Assign the value string to the attribut notation
                        $f->setAttribute(self::NOTATION, self::STRING);
                        // Create a string element and assign the value of the retrieved local meta to it
                        $string = $this->_xml->createElement(self::STRING, Zend_Registry::get(self::ZENDTRANSLATE)->translate(Hal_Referentiels_Metadata::getLabel($LML, $this->_HalDocument->getMeta($LML)), 'en'));
                        // Add the string element to the f element
                        $f->appendChild($string);
                        // Add the f element to the fs element
                        $fs->appendChild($f);
                        ///////////// Creation of numeric element ///////////////
                        $f = $this->_xml->createElement('f');
                        $f->setAttribute('name', $LML);
                        $f->setAttribute(self::NOTATION, self::NUMERIC);
                        $numeric = $this->_xml->createElement(self::NUMERIC, $this->_HalDocument->getMeta($LML));
                        $f->appendChild($numeric);
                        $fs->appendChild($f);
                        // Meta based free text
                    } else {
                        $f = $this->_xml->createElement('f');
                        $f->setAttribute('name', $LML);
                        $f->setAttribute(self::NOTATION, self::STRING);
                        $string = $this->_xml->createElement(self::STRING, $this->_HalDocument->getMeta($LML));
                        $f->appendChild($string);
                        $fs->appendChild($f);
                    }
                }
            }
            if ($fs->nodeValue !='') {
                if ($showXML) {
                    return $this->convertToXMLString($this->_xml->saveXML($fs));
                } else {
                    return $this->_xml->saveXML($fs);
                }
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createRespStmt">
    /**
     * @param bool $showXML
     * @return string|DOMElement
     * return the responsable of the document <respStmt> */

    public function createRespStmt($showXML = true) {
        if ( $this->_HalDocument->getContributor('uid') != '' && $this->_HalDocument->getContributor(self::LASTNAME) != '' && $this->_HalDocument->getContributor(self::FIRSTNAME) != '' && $this->_HalDocument->getContributor(self::EMAIL) != '' ) {
            $respStmt = $this->_xml->createElement('respStmt');
            $respStmt->appendChild($this->_xml->createElement('resp', 'contributor'));
            $name = $this->_xml->createElement('name');
            $name->setAttribute('key', $this->_HalDocument->getContributor('uid'));
            $persName = $this->_xml->createElement(self::PERSNAME);
            $persName->appendChild($this->_xml->createElement(self::FORENAME, $this->_HalDocument->getContributor(self::FIRSTNAME)));
            $persName->appendChild($this->_xml->createElement(self::SURNAME, $this->_HalDocument->getContributor(self::LASTNAME)));
            $name->appendChild($persName);
            $email = $this->_xml->createElement(self::EMAIL, Hal_Document_Author::getEmailHashed((string)$this->_HalDocument->getContributor(self::EMAIL), Hal_Settings::EMAIL_HASH_TYPE));
            $email->setAttribute('type',Hal_Settings::EMAIL_HASH_TYPE);
            $name->appendChild($email);
            $emailDomain = $this->_xml->createElement(self::EMAIL, Ccsd_Tools::getEmailDomain((string)$this->_HalDocument->getContributor(self::EMAIL)));
            $emailDomain->setAttribute('type','domain');
            $name->appendChild($emailDomain);

            $respStmt->appendChild($name);
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($respStmt));
            } else {
                return $this->_xml->saveXML($respStmt);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createPublicationStmt">
    /**
     * @param bool $showXML
     * @return string|DOMElement
     * return <publicationStmt> including :
     *        <distributor>
     *        <idno> (halId, halUri, halBibtex, halRefHtml, halRef
     *        <licnence> */

    public function createPublicationStmt($showXML = true) {
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
            $licences[Zend_Registry::get(self::ZENDTRANSLATE)->translate(Hal_Referentiels_Metadata::getLabel(self::LICENCE, $this->_HalDocument->getLicence()), 'en')] = $this->_HalDocument->getLicence();
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

        if ($showXML) {
            return $this->convertToXMLString($this->_xml->saveXML($ps));
        } else {
            return $this->_xml->saveXML($ps);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createSeriesStmt">
    /**
     * @param bool $showXML
     * @return string|DOMElement
     * return the HAL collections <seriesStmt>
     */

    public function createSeriesStmt($showXML = true) {
        $ss = $this->_xml->createElement('seriesStmt');
        foreach ($this->_HalDocument->_collections as $collection) {
            if ($collection instanceof Hal_Site_Collection) {
                $idno = $this->_xml->createElement('idno', $collection->getName());
                $idno->setAttribute('type', 'stamp');
                $idno->setAttribute('n', $collection->getCode());
                foreach ($collection->getParents() as $parent) {
                    if ($parent instanceof Hal_Site_Collection) {
                        $idno->setAttribute('p', $parent->getCode());
                    }
                }
                $ss->appendChild($idno);
            }
        }
        if ($showXML) {
            return $this->convertToXMLString($this->_xml->saveXML($ss));
        } else {
            return $this->_xml->saveXML($ss);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createNotesStmt">
    /**
     * @param bool $showXML
     * @return string|DOMElement
     * return <notesStmt>
     */

    public function createNotesStmt($showXML = true) {
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
            $noteValues = $this->_HalDocument->getMeta($noteName);
            if (! is_array($noteValues)) {
                $noteValues = [$noteValues];
            }
            foreach ($noteValues as $noteValue) {
                if($noteValue != '') {
                    if ($noteInformation[self::TRANSLATINGFLAG]) {
                        $note = $this->_xml->createElement("note", Zend_Registry::get(self::ZENDTRANSLATE)->translate(Hal_Referentiels_Metadata::getLabel($noteName, $noteValue), 'en'));
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
        if ($showXML) {
            return $this->convertToXMLString($this->_xml->saveXML($ns));
        } else {
            return $this->_xml->saveXML($ns);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createAnalytic">
    /**
     * @param bool $showXML
     * @return string|DOMElement
     * return <analytic> including :
     * <title>
     * <title type="sub">
     * <author> */
    public function createAnalytic($showXML = true) {
        if ($this->createTitle(false) || $this->createSubTitle(false) || $this->createAuthors(false)) {
            $analytic = $this->_xml->createElement('analytic');
            // Title
            $this->appendStringToDomNode($analytic, $this->createTitle(false));

            // Subtitle
            $this->appendStringToDomNode($analytic, $this->createSubTitle(false));

            // Author
            $this->appendStringToDomNode($analytic, $this->createAuthors(false));
            // Analytic
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($analytic));
            } else {
                return $this->_xml->saveXML($analytic);
            }
        }
        return "";
    }
    //</editor-fold>

    //<editor-fold desc="createMonogr">
    /**
     * return <monogr> including many elements like journal<journal> and confrence<meeting>, .. */

    public function createMonogr($showXML = true) {
        $monogr = $this->_xml->createElement('monogr');
        if ( $this->_HalDocument->getMeta('nnt') != '' ) {
            $idno = $this->_xml->createElement('idno', $this->_HalDocument->getMeta('nnt'));
            $idno->setAttribute('type', 'nnt');
            $monogr->appendChild($idno);
        }
        if ( $this->_HalDocument->getMeta('number') != '' ) {
            $idno = $this->_xml->createElement('idno', $this->_HalDocument->getMeta('number'));
            $idno->setAttribute('type', strtolower($this->_HalDocument->getTypDoc()).'Number');
            $monogr->appendChild($idno);
        }
        if ( $this->_HalDocument->getMeta('isbn') != '' ) {
            $idno = $this->_xml->createElement('idno', $this->_HalDocument->getMeta('isbn'));
            $idno->setAttribute('type', 'isbn');
            $monogr->appendChild($idno);
        }
        foreach ($this->_HalDocument->getMeta('localReference') as $ref) {
            $idno = $this->_xml->createElement('idno', $ref);
            $idno->setAttribute('type', 'localRef');
            $monogr->appendChild($idno);
        }
        if ( ( $oJ = $this->_HalDocument->getMeta('journal') ) instanceof Ccsd_Referentiels_Journal ) {
            /** @var Ccsd_Referentiels_Journal $oJ */
            $journal = $this->_xml->createElement('idno', $oJ->JID);
            $journal->setAttribute('type', 'halJournalId');
            $journal->setAttribute(self::STATUS, strtoupper($oJ->VALID));
            $monogr->appendChild($journal);
            if ( $oJ->ISSN ) {
                $journal = $this->_xml->createElement('idno', $oJ->ISSN);
                $journal->setAttribute('type', 'issn');
                $monogr->appendChild($journal);
            }
            if ( $oJ->EISSN ) {
                $journal = $this->_xml->createElement('idno', $oJ->EISSN);
                $journal->setAttribute('type', 'eissn');
                $monogr->appendChild($journal);
            }
            $journal = $this->_xml->createElement(self::TITLE, $oJ->JNAME);
            $journal->setAttribute(self::LEVEL, 'j');
            $monogr->appendChild($journal);
            if ( $oJ->PUBLISHER ) {
                $journalPublisher = $oJ->PUBLISHER;
            }
        }
        if ( $this->_HalDocument->getMeta('bookTitle') != '' ) {
            $title = $this->_xml->createElement(self::TITLE, $this->_HalDocument->getMeta('bookTitle'));
            $title->setAttribute(self::LEVEL, 'm');
            $monogr->appendChild($title);
        }
        if ( $this->_HalDocument->getTypDoc() == 'COMM' && $this->_HalDocument->getMeta('source') != '' ) {
            $title = $this->_xml->createElement(self::TITLE, $this->_HalDocument->getMeta('source'));
            $title->setAttribute(self::LEVEL, 'm');
            $monogr->appendChild($title);
        }
        if ( !in_array($this->_HalDocument->_typdoc, array('PATENT', 'IMG', 'MAP', 'LECTURE')) && ( $this->_HalDocument->getMeta(self::CONFTITLE) != '' || $this->_HalDocument->getMeta(self::CONFSTARTDATE) != '' || $this->_HalDocument->getMeta(self::CONFENDDATE) != '' || $this->_HalDocument->getMeta('city') != '' || $this->_HalDocument->getMeta(self::COUNTRY) != '' || count($this->_HalDocument->getMeta(self::CONFORGANIZER)) ) ) {
            $meeting = $this->_xml->createElement('meeting');
            if ( $this->_HalDocument->getMeta(self::CONFTITLE) != '' )
                $meeting->appendChild($this->_xml->createElement(self::TITLE, $this->_HalDocument->getMeta(self::CONFTITLE)));
            if ( $this->_HalDocument->getMeta(self::CONFSTARTDATE) != '' ) {
                $d = $this->_xml->createElement('date', $this->_HalDocument->getMeta(self::CONFSTARTDATE));
                $d->setAttribute('type', 'start');
                $meeting->appendChild($d);
            }
            if ( $this->_HalDocument->getMeta(self::CONFENDDATE) != '' ) {
                $d = $this->_xml->createElement('date', $this->_HalDocument->getMeta(self::CONFENDDATE));
                $d->setAttribute('type', 'end');
                $meeting->appendChild($d);
            }
            if ( $this->_HalDocument->getMeta('city') != '' ) {
                $meeting->appendChild($this->_xml->createElement('settlement', $this->_HalDocument->getMeta('city')));
            }
            if ( $this->_HalDocument->getMeta(self::COUNTRY) != '' ) {
                $country = $this->_xml->createElement(self::COUNTRY, Zend_Locale::getTranslation(strtoupper($this->_HalDocument->getMeta(self::COUNTRY)), self::COUNTRY, 'en'));
                $country->setAttribute('key', strtoupper($this->_HalDocument->getMeta(self::COUNTRY)));
                $meeting->appendChild($country);
            }
            $monogr->appendChild($meeting);
            if ( count($this->_HalDocument->getMeta(self::CONFORGANIZER)) ) {
                $resp = $this->_xml->createElement('respStmt');
                $resp->appendChild($this->_xml->createElement('resp', self::CONFORGANIZER));
                foreach ( $this->_HalDocument->getMeta(self::CONFORGANIZER) as $orga ) {
                    $resp->appendChild($this->_xml->createElement('name', $orga));
                }
                $monogr->appendChild($resp);
            }
        }
        if ( in_array($this->_HalDocument->_typdoc, array('PATENT', 'IMG', 'MAP', 'LECTURE')) ) {
            if ( $this->_HalDocument->getMeta('city') != '' ) {
                $monogr->appendChild($this->_xml->createElement('settlement', $this->_HalDocument->getMeta('city')));
            }
            if ( $this->_HalDocument->getMeta(self::COUNTRY) != '' ) {
                $country = $this->_xml->createElement(self::COUNTRY, Zend_Locale::getTranslation(strtoupper($this->_HalDocument->getMeta(self::COUNTRY)), 'territory', 'en'));
                $country->setAttribute('key', strtoupper($this->_HalDocument->getMeta(self::COUNTRY)));
                $monogr->appendChild($country);
            }
        }
        foreach ( $this->_HalDocument->getMeta('scientificEditor') as $edsci ) {
            $edsci = trim($edsci, ", \t\n\r\0\x0B");  // On enleve les virgules finales... pour eviter les editeurs vides!
            $editorArray = explode(',' , $edsci);
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

        // sourceDesc>biblStruct>monogr>imprint
        $imprint  = $this->_xml->createElement('imprint');
        foreach ( $this->_HalDocument->getMeta(self::PUBLISHER) as $publisher ) {
            $imprint->appendChild($this->_xml->createElement(self::PUBLISHER, $publisher));
        }
        if ( isset($journalPublisher) && $journalPublisher != '' && !in_array(strtolower($journalPublisher), array_map('strtolower', $this->_HalDocument->getMeta(self::PUBLISHER))) ) {
            $imprint->appendChild($this->_xml->createElement(self::PUBLISHER, $journalPublisher));
        }
        if ( $this->_HalDocument->getMeta('publicationLocation') != '' ) {
            $imprint->appendChild($this->_xml->createElement('pubPlace', $this->_HalDocument->getMeta('publicationLocation')));
        }
        if ( $this->_HalDocument->getMeta(self::SERIE) != '' ) {
            $bs = $this->_xml->createElement(self::BIBLSCOPE, $this->_HalDocument->getMeta(self::SERIE));
            $bs->setAttribute('unit', self::SERIE);
            $imprint->appendChild($bs);
        }
        if ( $this->_HalDocument->getMeta(self::VOLUME) != '' ) {
            $bs = $this->_xml->createElement(self::BIBLSCOPE, $this->_HalDocument->getMeta(self::VOLUME));
            $bs->setAttribute('unit', self::VOLUME);
            $imprint->appendChild($bs);
        }
        if ( $this->_HalDocument->getMeta(self::ISSUE) != '' ) {
            $bs = $this->_xml->createElement(self::BIBLSCOPE, $this->_HalDocument->getMeta(self::ISSUE));
            $bs->setAttribute('unit', self::ISSUE);
            $imprint->appendChild($bs);
        }
        if ( $this->_HalDocument->getMeta('page') != '' ) {
            $bs = $this->_xml->createElement(self::BIBLSCOPE, $this->_HalDocument->getMeta('page'));
            $bs->setAttribute('unit', 'pp');
            $imprint->appendChild($bs);
        }
        if ( $this->_HalDocument->getMeta('date') != '' ) {
            $d = $this->_xml->createElement('date', $this->_HalDocument->getMeta('date'));
            if ( in_array($this->_HalDocument->_typdoc, array('THESE', 'HDR', 'MEM')) ) {
                $d->setAttribute('type', 'dateDefended');
            } else {
                $d->setAttribute('type', 'datePub');
            }
            if ( $this->_HalDocument->getMeta('circa') == 1 ) {
                $d->setAttribute('precision', 'unknown');
            }
            if ( $this->_HalDocument->getMeta('inPress') == 1 ) {
                $d->setAttribute('subtype', 'inPress');
            }
            $imprint->appendChild($d);
        }
        if ( $this->_HalDocument->getMeta('edate') != '' ) {
            $d = $this->_xml->createElement('date', $this->_HalDocument->getMeta('edate'));
            $d->setAttribute('type', 'dateEpub');
            $imprint->appendChild($d);
        }
        $monogr->appendChild($imprint);
        foreach ( $this->_HalDocument->getMeta('authorityInstitution') as $orgthe ) {
            $auth = $this->_xml->createElement(self::AUTHORITY, $orgthe);
            $auth->setAttribute('type', 'institution');
            $monogr->appendChild($auth);
        }
        foreach ( $this->_HalDocument->getMeta('thesisSchool') as $school ) {
            $auth = $this->_xml->createElement(self::AUTHORITY, $school);
            $auth->setAttribute('type', 'school');
            $monogr->appendChild($auth);
        }
        foreach ( $this->_HalDocument->getMeta('director') as $dir ) {
            $auth = $this->_xml->createElement(self::AUTHORITY, $dir);
            $auth->setAttribute('type', 'supervisor');
            $monogr->appendChild($auth);
        }
        if ( $this->_HalDocument->getMeta('inria_directorEmail') != '' ) {
            $auth = $this->_xml->createElement(self::AUTHORITY, $this->_HalDocument->getMeta('inria_directorEmail'));
            $auth->setAttribute('type', 'supervisorEmail');
            $monogr->appendChild($auth);
        }
        if ( $this->_HalDocument->getMeta('memsic_directorEmail') != '' ) {
            $auth = $this->_xml->createElement(self::AUTHORITY, $this->_HalDocument->getMeta('memsic_directorEmail'));
            $auth->setAttribute('type', 'supervisorEmail');
            $monogr->appendChild($auth);
        }
        foreach ( $this->_HalDocument->getMeta('committee') as $jury ) {
            $auth = $this->_xml->createElement(self::AUTHORITY, $jury);
            $auth->setAttribute('type', 'jury');
            $monogr->appendChild($auth);
        }
        if ($showXML) {
            return $this->convertToXMLString($this->_xml->saveXML($monogr));
        } else {
            return $this->_xml->saveXML($monogr);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createSeries">
    /**
     * @param bool $showXML
     * return <series> */

    public function createSeries($showXML = true) {
        if ( count($this->_HalDocument->getMeta('seriesEditor')) || $this->_HalDocument->getMeta(self::LECTURENAME) != '' ) {
            $series = $this->_xml->createElement('series');
            foreach ( $this->_HalDocument->getMeta('seriesEditor') as $edcoll ) {
                $series->appendChild($this->_xml->createElement(self::EDITOR, $edcoll));
            }
            if ( $this->_HalDocument->getMeta(self::LECTURENAME) != '' ) {
                $series->appendChild($this->_xml->createElement(self::TITLE, $this->_HalDocument->getMeta(self::LECTURENAME)));
            }
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($series));
            } else {
                return $this->_xml->saveXML($series);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createIdentifier">
    /**
     * @param bool $showXML
     * return <idno> in <biblStruct> */

    public function createIdentifier($showXML = true) {
        $idnos = [];
        foreach ($this->_HalDocument->getMeta('identifier') as $code => $id) {
            $idno = $this->_xml->createElement('idno', $id);
            $idno->setAttribute('type', $code);
            array_push($idnos, $this->_xml->saveXML($idno));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $idnos));
        } else {
            return implode("", $idnos);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createSeeAlso">
    /**
     * @param bool $showXML
     * return <ref> type seeAlso in <biblStruct>*/

    public function createSeeAlso($showXML = true) {
        $refs = [];
        $urls = $this->_HalDocument->getMeta('seeAlso');
        if ($this->_HalDocument->getHalMeta()->getMeta('codeRepository')) {
            $urls[] = $this->_HalDocument->getHalMeta()->getMeta('codeRepository');
        }
        //todo il faudrait mettre les URL dans un attribut target plutôt que dans la valeur de la balise
        foreach ( $urls as $url ) {
            $ref = $this->_xml->createElement('ref', $url);
            $ref->setAttribute('type', 'seeAlso');
            array_push($refs, $this->_xml->saveXML($ref));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $refs));
        } else {
            return implode("", $refs);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createPublisherLink">
    /**
     * @param bool $showXML
     * return <ref> type publisher in <biblStruct>*/

    public function createPublisherLink($showXML = true) {
        if ( $this->_HalDocument->getMeta('publisherLink') != '' ) {
            $ref = $this->_xml->createElement('ref', $this->_HalDocument->getMeta('publisherLink'));
            $ref->setAttribute('type', self::PUBLISHER);
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($ref));
            } else {
                return $this->_xml->saveXML($ref);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createRelatedItem">
    /**
     * @param bool $showXML
     * return <relatedItem> */

    public function createRelatedItem($showXML = true) {
        $relatedItems = [];
        foreach ( $this->_HalDocument->_related as $info) {
            $item = $this->_xml->createElement('relatedItem', $info['INFO']);
            $item->setAttribute(self::TARGET, $info['URI']);
            $item->setAttribute('type', $info['RELATION']);
            array_push($relatedItems, $this->_xml->saveXML($item));
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $relatedItems));
        } else {
            return implode("", $relatedItems);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createListPlace">
    /**
     * @param bool $showXML
     * Manage Latitude and Longitude of image docs
     * return <listPlace> */

    public function createListPlace($showXML = true) {
        // Test if the values of the elements latitude and longitude aren't null
        if ($this->_HalDocument->getMeta('latitude') !='' && $this->_HalDocument->getMeta('longitude')!='') {
            // Create a listPlace element
            $listPlace = $this->_xml->createElement('listPlace');
            // Create a place element
            $place = $this->_xml->createElement('place');
            // Create a location element
            $location = $this->_xml->createElement('location');
            // Create a geo element and assign the values of latitude and longitude to it
            $geo = $this->_xml->createElement('geo', $this->_HalDocument->getMeta('latitude') . ' ' . $this->_HalDocument->getMeta('longitude'));
            // Add the geo element to the location element
            $location->appendChild($geo);
            // Add the location element to the place element
            $place->appendChild($location);
            // Add the place element to the listPlace element
            $listPlace->appendChild($place);
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($listPlace));
            } else {
                return $this->_xml->saveXML($listPlace);
            }
        }
    }
    //</editor-fold>

    //<editor-fold desc="createRecordingStmt">
    /**
     * @param bool $showXML
     * Manage Duration of video docs
     * return <recordingStmt> */

    public function createRecordingStmt($showXML = true) {
        // Test if the value of the duration element isn't null
        if($this->_HalDocument->getMeta('duration') !='') {
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
            $recording->setAttribute('dur', $this->_HalDocument->getMeta('duration'));
            // Add the recording element to the recordingStmt element
            $recordingStmt->appendChild($recording);
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($recordingStmt));
            } else {
                return $this->_xml->saveXML($recordingStmt);
            }
        }
    }

    //</editor-fold>

    //<editor-fold desc="createLanguages">
    /**
     * @param bool $showXML
     * profileDesc>langUsage>language
     * return <languages> */

    public function createLanguages($showXML = true) {
        $lu = $this->_xml->createElement('langUsage');
        $lang = $this->_xml->createElement(self::LANGUAGE, Zend_Locale::getTranslation($this->_HalDocument->getMeta(self::LANGUAGE), self::LANGUAGE, 'en'));
        $lang->setAttribute('ident', $this->_HalDocument->getMeta(self::LANGUAGE));
        $lu->appendChild($lang);
        if ($showXML) {
            return $this->convertToXMLString($this->_xml->saveXML($lu));
        } else {
            return $this->_xml->saveXML($lu);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createTextClass">
    /**
     * @param bool $showXML
     * profileDesc>textClass
     * return <textClass> */

    public function createTextClass($showXML = true) {
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
        if ( ( $classif = $this->_HalDocument->getMeta('classification') ) != '' ) {
            $kws = $this->_xml->createElement(self::CLASSCODE, $classif);
            $kws->setAttribute(self::SCHEME, 'classification');
            $textClass->appendChild($kws);
        }
        // mesh
        foreach ( $this->_HalDocument->getMeta('mesh') as $mesh ) {
            $kws = $this->_xml->createElement(self::CLASSCODE, $mesh);
            $kws->setAttribute(self::SCHEME, 'mesh');
            $textClass->appendChild($kws);
        }
        // jel
        foreach ( $this->_HalDocument->getMeta('jel') as $jel ) {
            $kws = $this->_xml->createElement(self::CLASSCODE, Ccsd_Tools_String::getHalMetaTranslated($jel, 'en', '/', 'jel'));
            $kws->setAttribute(self::SCHEME, 'jel');
            $kws->setAttribute('n', $jel);
            $textClass->appendChild($kws);
        }
        // acm
        foreach ( $this->_HalDocument->getMeta('acm') as $acm ) {
            $kws = $this->_xml->createElement(self::CLASSCODE, Ccsd_Tools_String::getHalMetaTranslated($acm, 'en', '/', 'acm'));
            $kws->setAttribute(self::SCHEME, 'acm');
            $kws->setAttribute('n', $acm);
            $textClass->appendChild($kws);
        }
        // domain
        foreach ( $this->_HalDocument->getMeta('domain') as $domain ) {
            $d = $this->_xml->createElement(self::CLASSCODE, Ccsd_Tools_String::getHalDomainTranslated($domain, 'en', '/'));
            $d->setAttribute(self::SCHEME, 'halDomain');
            $d->setAttribute('n', $domain);
            $textClass->appendChild($d);
        }
        // typdoc
        $typdoc = $this->_xml->createElement(self::CLASSCODE, Zend_Registry::get(self::ZENDTRANSLATE)->translate('typdoc_'.$this->_HalDocument->getTypDoc(), 'en'));
        $typdoc->setAttribute(self::SCHEME, 'halTypology');
        $typdoc->setAttribute('n', $this->_HalDocument->getTypDoc());
        $textClass->appendChild($typdoc);
        if ($showXML) {
            return $this->convertToXMLString($this->_xml->saveXML($textClass));
        } else {
            return $this->_xml->saveXML($textClass);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createAbstract">
    /**
     * @param bool $showXML
     * profileDesc>abstract
     * return <abstract> */

    public function createAbstract($showXML = true)
    {
        $abstractList = [];
        foreach ($this->_HalDocument->getAbstract() as $l => $t) {
            if ( is_array($t) ) {
                $t = current($t);
            }

            $abs = $this->_xml->createElement('abstract', $t);
            $abs->setAttribute(static::XMLLANG, $l);
            $abstractList[] = $this->_xml->saveXML($abs);
        }
        if ($showXML) {
            return $this->convertToXMLString(implode("", $abstractList));
        } else {
            return implode("", $abstractList);
        }
    }
    //</editor-fold>

    //<editor-fold desc="createOrg">
    /**
     * @param bool $showXML
     * profileDesc>particDesc>org
     * return <org> */

    public function createOrg($showXML = true) {
        if ( is_array($this->_HalDocument->getMeta(self::COLLABORATION)) && count($this->_HalDocument->getMeta(self::COLLABORATION)) ) {
            $collaboration = $this->_xml->createElement('particDesc');
            foreach ( $this->_HalDocument->getMeta(self::COLLABORATION) as $collab ) {
                $org = $this->_xml->createElement('org', $collab);
                $org->setAttribute('type', 'consortium');
                $collaboration->appendChild($org);
            }
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($collaboration));
            } else {
                return $this->_xml->saveXML($collaboration);
            }
        }
        return "";
    }
    //</editor-fold>

    //<editor-fold desc="createBack">
    /**
     * @param bool $showXML
     * return <back> */

    public function createBack($showXML = true) {

        $structures = $this->createStructures(false);
        $projects = $this->createProjects(false);

        if ($structures || $projects) {
            // Create <back>
            $back = $this->_xml->createElement('back');
            // Append structures to <back>
            $this->appendStringToDomNode($back, $structures);
            // Append projects to <back>
            $this->appendStringToDomNode($back, $projects);
            // Return <back>
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($back));
            } else {
                return $this->_xml->saveXML($back);
            }
        }
        return "";
    }

    //</editor-fold>

    //<editor-fold desc="createStructures">
    /**
     * @param bool $showXML
     * return <org> of structures */

    public function createStructures($showXML = true) {
        $structures = $this->_HalDocument->_structures;
        if ( isset($structures) && is_array($structures) && count($structures) ) {
            $listOrg = $this->_xml->createElement('listOrg');
            $listOrg->setAttribute('type', 'structures');
            $parents = array();
            foreach ( $structures as $s ) {
                $struct = new Ccsd_Referentiels_Structure($s->getStructId());
                if ( $struct->getStructid() == 0 ) {
                    continue;
                }
                $parents = array_merge($parents, $struct->getParentsStructids());
                $this->appendStringToDomNode($listOrg, $struct->getXML(false));
            }
            $structuresIds = array_map( function($s) { return $s->getStructid(); } , $structures);
            foreach ( array_unique(array_diff($parents, $structuresIds)) as $sid ) {
                $struct = new Ccsd_Referentiels_Structure($sid);
                if ( $struct->getStructid() == 0 ) {
                    continue;
                }
                $this->appendStringToDomNode($listOrg, $struct->getXML(false));
            }
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($listOrg));
            } else {
                return $this->_xml->saveXML($listOrg);
            }
        }
        return "";
    }
    //</editor-fold>

    //<editor-fold desc="createProjects">
    /**
     * @param bool $showXML
     * return <org> */

    public function createProjects($showXML = true) {
        // projets
        if ( ($this->_HalDocument->getMeta(self::ANRPROJECT) != null && is_array($this->_HalDocument->getMeta(self::ANRPROJECT)) && count($this->_HalDocument->getMeta(self::ANRPROJECT))) || ($this->_HalDocument->getMeta(self::EURPROJECT) != null && is_array($this->_HalDocument->getMeta(self::EURPROJECT)) && count($this->_HalDocument->getMeta(self::EURPROJECT))) ) {
            $listOrg = $this->_xml->createElement('listOrg');
            $listOrg->setAttribute('type', 'projects');
            foreach ($this->_HalDocument->getMeta(self::ANRPROJECT) as $p) {
                if ($p instanceof Ccsd_Referentiels_Anrproject) {
                    $this->appendStringToDomNode($listOrg,$p->getXML(false));
                }
            }
            foreach ($this->_HalDocument->getMeta(self::EURPROJECT) as $p) {
                if ($p instanceof Ccsd_Referentiels_Europeanproject) {
                    $this->appendStringToDomNode($listOrg,$p->getXML(false));
                }
            }
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($listOrg));
            } else {
                return $this->_xml->saveXML($listOrg);
            }
        }
        return "";
    }
    //</editor-fold>

    //<editor-fold desc="createReferences">
    /**
     * Implement hal doc references in TEI
     * @param bool $showXML
     * @return string
     */
    public function createReferences($showXML = true) {
        $halDocReferences = new Hal_Document_References($this->_HalDocument->getDocid());
        $halDocReferences->load();
        $references = $halDocReferences->get();
        if(count($references)) {
            $listBibl = $this->_xml->createElement('listBibl');
            $listBibl->setAttribute('type', 'references');
            foreach ($references as $reference) {
                $this->appendStringToDomNode($listBibl, (string) $reference['REFXML']);
            }
            if ($showXML) {
                return $this->convertToXMLString($this->_xml->saveXML($listBibl));
            } else {
                return $this->_xml->saveXML($listBibl);
            }
        }
        return "";
    }
    //</editor-fold>

    //<editor-fold desc="createAuthor(Hal_Document_Author)">
    /**
     * return a specific author */
    public function createAuthor($hal_document_author) {
        $autFromReferentiels = new Ccsd_Referentiels_Author($hal_document_author->getAuthorid());
        return $autFromReferentiels->getXML(false);
    }
    //</editor-fold>
}

