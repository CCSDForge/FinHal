<?php
/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen
 * Date: 21/12/2016
 * Time: 13:36
 * =============================================================================================================
 */

/**
 * =============================================== DESCRIPTION =================================================
 * Class References is used to manage the Hal document references (biblio.)
 * =============================================================================================================
 */
class Hal_Document_References
{
    /**
     * Constants
     */
    const DOC_REFERENCES    = 'DOC_REFERENCES';
    const GROBID_REFERENCES = 'GROBID_REFERENCES';
    const REFID             = 'REFID';
    const DOCID             = 'DOCID';
    const REFXML_ORIGINAL   = 'REFXML_ORIGINAL';
    const REFHTML           = 'REFHTML';
    const REFXML            = 'REFXML';
    const REFSTATUS         = 'REFSTATUS';
    const REFVALIDETY       = 'REFVALIDETY';
    const DOI               = 'DOI';
    const URL               = 'URL';
    const SOURCE            = 'SOURCE';
    const TARGETDOCID       = 'TARGETDOCID';
    const GRODIB_PROCESS    = 'GRODIB_PROCESS';
    const PID               = 'PID';
    const UPDATE_DATE       = 'UPDATE_DATE';
    const GROBID_DATE       = 'GROBID_DATE';
    const SAVE_DATE         = 'SAVE_DATE';
    const TITLE_ANALYTIC    = 'TITLE_ANALYTIC';
    const AUTHOR_ANALYTIC   = 'AUTHOR_ANALYTIC';
    const TITLE_MONOGR      = 'TITLE_MONOGR';
    const AUTHOR_MONOGR     = 'AUTHOR_MONOGR';
    const AUTHOR_REF        = 'authors-reference';
    const TITLE_REF         = 'title-reference';
    const TITLE_EVENT_REF_V = 'titleEvent-reference-value';
    const VOL_REF_V         = 'vol-reference-value';
    const ISSUE_REF_V       = 'issue-reference-value';
    const PAGES_REF_V       = 'pages-reference-value';
    const DATE_REF_V        = 'date-reference-value';
    const DOI_REF_V         = 'doi-reference-value';
    const URL_REF_V         = 'url-reference-value';
    const ISNULL            = ' IS NULL';
    const UPDATED           = 'UPDATED';
    const UPDATING          = 'UPDATING';
    const NOT_UPDATED       = 'NOT_UPDATED';
    const DELETED           = 'DELETED';
    const VALIDATED         = 'VALIDETED';
    const BADFORMAT         = 'BAD_FORMAT';
    const NOTVERIFIED         = 'NOT_VERIFIED';
    const UTF8              = 'UTF-8';
    const CROSSREF_URL      = 'http://api.crossref.org';
    const OADOI_URL         = 'https://api.oadoi.org';

    /**
     * Properties
     */
    protected $_docId                = 0;
    protected $_file                 = null;
    protected $_db                   = null;
    protected $_domDoc               = null;
    protected $_docReferences        = [];
    /** @var string[][]  tableau de table raw  */
    protected $_loadedReferences     = [];
    protected $_referenceXPaths      = [
        self::TITLE_ANALYTIC  => '/xmlns:biblStruct/xmlns:analytic/xmlns:title',
        self::AUTHOR_ANALYTIC => '/xmlns:biblStruct/xmlns:analytic/xmlns:author/xmlns:persName/xmlns:surname',
        self::TITLE_MONOGR    => '/xmlns:biblStruct/xmlns:monogr/xmlns:title',
        self::AUTHOR_MONOGR   => '/xmlns:biblStruct/xmlns:monogr/xmlns:author/xmlns:persName/xmlns:surname',
        self::DOI             => '/xmlns:biblStruct/xmlns:idno[@type="doi"]'
    ];
    protected $_htmlReferenceClasses = [
        self::AUTHOR_REF,
        self::TITLE_REF,
        self::TITLE_EVENT_REF_V,
        self::VOL_REF_V,
        self::ISSUE_REF_V,
        self::PAGES_REF_V,
        self::DATE_REF_V,
        self::DOI_REF_V,
        self::URL_REF_V
    ];

    /**
     * Hal_Document_References constructor
     * @param $docId : Hal_Document Id
     */
    public function __construct($docId)
    {
        $this->_docId = $docId;
        $this->_db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
    }

    /**
     * Load document's references from db
     */
    public function load()
    {
        $sql = $this->_db->select()->from(self::DOC_REFERENCES, ['REFHTML', 'REFXML', 'REFVALIDETY'])->where(self::DOCID . ' = ?', (int)$this->_docId);
        $this->_loadedReferences = $this->_db->fetchAll($sql, Zend_Db::FETCH_COLUMN);
    }

    /**
     *
     */
    public function fullLoad()
    {
        $sql = $this->_db->select()->from(self::DOC_REFERENCES)->where(self::DOCID . ' = ?', (int)$this->_docId);
        $this->_loadedReferences = $this->_db->fetchAll($sql, Zend_Db::FETCH_COLUMN);
    }

    /**
     * Get document's references from db, it can be only used after calling load method
     */
    public function get()
    {
        return $this->_loadedReferences;
    }

    /**
     * Get the path of the main file of a document
     * return false if file does not exist
     * @param $docId
     * @return string|bool
     */
    public static function getFile($docId)
    {
        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $sql = $db->select()->from(Hal_Document_File::TABLE)->where(self::DOCID . ' = ?', (int)$docId)->where('EXTENSION = ?', 'pdf');
        $racineDoc = Hal_Document::getRacineDoc_s($docId);
        $row = $db->fetchRow($sql);
        $file = new Hal_Document_File();
        $file->load($row, $racineDoc);
        if ($file->file_exists()) {
            return $file->getPath();
        }
        return false;
    }

    /**
     * Get doc id by ref id
     * @param $refId
     * @return string
     */
    public static function getDocIdByRefId($refId)
    {
        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $sql = $db->select()->from(self::DOC_REFERENCES, self::DOCID)->where(self::REFID . ' = ?', $refId);
        return $db->fetchOne($sql);
    }

    /**
     * Get ref id(s) by doc id
     * @param int $docId
     * @param string $refStatus
     * @param int $pid
     * @param int $limitNumber
     * @return array
     * @throws Zend_Db_Statement_Exception
     */
    public static function getRefIdsByDocId($docId, $refStatus, $pid, $limitNumber)
    {
        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $update = "UPDATE DOC_REFERENCES SET PID = " . $pid . ", UPDATE_DATE = '" . date('Y-m-d H:i:s') . "' WHERE REFSTATUS = '" . $refStatus . "' AND DOCID = " . $docId . " AND PID IS NULL LIMIT " . $limitNumber;
        $sql = $db->query($update);
        if ($sql->rowCount() < 1) {
            debug('', 'There are no references', 'red');
            return [];
        }
        $sql = $db->select()->from(self::DOC_REFERENCES, self::REFID)
            ->where(self::PID . ' = ?', $pid)
            ->where(self::DOCID . ' = ?', $docId);
        return $db->fetchCol($sql);
    }

    /**
     * Get DocIds for extracting via Grobid
     * @param int $limitNumber
     * @param int $pid
     * @return Zend_Db_Table_Row_Abstract | array
     */
    public static function getDocIdsForExtracting($limitNumber, $pid)
    {
        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $update = "UPDATE GROBID_REFERENCES SET GRODIB_PROCESS = 'Executing', PID = " . $pid . ", GROBID_DATE = '" . date('Y-m-d H:i:s') . "' WHERE GRODIB_PROCESS = 'Not_Executed' AND PID IS NULL LIMIT " . $limitNumber;
        $sql = $db->query($update);

        $nbUpdated = $sql->rowCount();
        debug(date("Y-m-d H:i:s") . ' ' .$nbUpdated . ' documents updated Executing with PID ' . $pid);

        if ($nbUpdated < 1) {
            debug('', 'There are no documents', 'red');
            return [];
        }
        $sql = $db->select()->from(self::GROBID_REFERENCES, self::DOCID)
            ->where(self::PID . ' = ?', $pid)
            ->where(self::GRODIB_PROCESS . ' = ?', 'Executing');

        $res= $db->fetchCol($sql);
        $nbSelected = count($res);
        debug(date("Y-m-d H:i:s") . ' ' . $nbSelected . ' documents selected Executing with PID ' . $pid);

        return $res;
    }

    /**
     * Get RefIds for updating (perform cURL)
     * Can help to identify references awaiting processing (CURL,...)
     * @param string $refStatus
     * @param pid
     * @param $limitNumber
     * @return array
     */
    public static function getRefIdsForUpdating($refStatus, $pid, $limitNumber)
    {
        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $update = "UPDATE DOC_REFERENCES SET REFSTATUS = 'UPDATING', PID = " . $pid . ", UPDATE_DATE = '" . date('Y-m-d H:i:s') . "' WHERE REFSTATUS = '" . $refStatus . "' AND PID IS NULL LIMIT " . $limitNumber;
        $sql = $db->query($update);
        if ($sql->rowCount() < 1) {
            debug('', 'There are no references', 'red');
            return [];
        }
        $sql = $db->select()->from(self::DOC_REFERENCES, self::REFID)
            ->where(self::PID . ' = ?', $pid)
            ->where(self::REFSTATUS . ' = ?', self::UPDATING);
        return $db->fetchCol($sql);
    }

    /**
     * Extract References using Grobid
     * Return references list in XML format
     * @return bool|array
     */
    public function extract()
    {
        $sql = $this->_db->query("UPDATE GROBID_REFERENCES SET GRODIB_PROCESS = 'Executed' WHERE GRODIB_PROCESS = 'Executing' AND DOCID = " . $this->_docId);

        $nbSelected= $sql->rowCount();
        debug(date("Y-m-d H:i:s") . ' ' . $nbSelected . ' documents Executed with docid ' . $this->_docId);

        $grobidProvider = new Ccsd_Dataprovider_Grobid($this->_db);

        if (!$grobidProvider->buildReferences(self::getFile($this->_docId))) {
            return false;
        }
        return $grobidProvider->getReferences();
    }

    /**
     * Insertion in db
     * @param $table
     * @param $set
     * @return bool
     */
    public function save($table, $set)
    {
        if ($table == self::GROBID_REFERENCES) {
            $sql = $this->_db->select()->from($table)->where(self::DOCID . ' = ?', $this->_docId);
            if (!$this->_db->fetchAll($sql)) {
                $this->_db->insert($table, $set);
                return true;
            }
            return false;
        }
        $this->_db->insert($table, $set);
        return true;
    }

    /**
     * Enrich the reference by doi, url, status, html format, source, targetDocId
     * @param $ch : curl object
     * @param $pid : current id process
     * @param $refId
     * @return bool
     */
    public function update($ch, $refId, $pid)
    {
        $source = 'Grobid';
        $validity = self::NOTVERIFIED;
        // Get reference row from db
        $sql = $this->_db->select()->from(self::DOC_REFERENCES)
            ->where(self::REFID . ' = ?', $refId);
        $row = $this->_db->fetchRow($sql);
        debug('', printf("%-10s | %-10s | %-10s", 'PID ' . $pid, 'DOCID ' . $row[self::DOCID], 'REFID ' . $row[self::REFID]));
        // Is reference set by user
        if (!isset($row[self::REFXML_ORIGINAL])) {
            $this->_db->update(self::DOC_REFERENCES, [self::REFSTATUS => self::UPDATED], self::REFID . ' = ' . $row[self::REFID]);
            return false;
        }
        // Get title, author, doi from REFXML
        $infoFromRefXml = $this->getInfoFromRefXml($row[self::REFXML_ORIGINAL]);
        $referenceTitle = strtok(isset($infoFromRefXml[self::TITLE_ANALYTIC]) ? $infoFromRefXml[self::TITLE_ANALYTIC] : (isset($infoFromRefXml[self::TITLE_MONOGR]) ? $infoFromRefXml[self::TITLE_MONOGR] : null), '.');
        $referenceAuthor = isset($infoFromRefXml[self::AUTHOR_ANALYTIC]) ? $infoFromRefXml[self::AUTHOR_ANALYTIC] : (isset($infoFromRefXml[self::AUTHOR_MONOGR]) ? $infoFromRefXml[self::AUTHOR_MONOGR] : null);
        if (!isset($referenceTitle) || !isset($referenceAuthor)) {
            $validity = self::BADFORMAT;
        }
        $referenceTitle = $this->clean($referenceTitle);
        $referenceAuthor = $this->clean($referenceAuthor);
        if ($referenceTitle == '' || $referenceAuthor == '') {
            $validity = self::BADFORMAT;
        }
        // Trying to get Id_Hal and docid of the reference, from cURL on HAL using Solar request
        $SOLR_Request = 'http://ccsdsolrvip.in2p3.fr:8080/solr/hal/select/?q=title_t:(' . urlencode($referenceTitle) . ')&fq=authLastName_t:(' . urlencode($referenceAuthor) . ')&fq=status_i:11&fl=halId_s&fl=docid&wt=json';
        $results = self::curlCall($SOLR_Request, $ch);
        // if we got docid, we build TargetDocId
        $targetDocId = isset($results['response']['docs'][0]['docid']) ? $results['response']['docs'][0]['docid'] : null;
        // Doi From Grobid, if not Doi form CrossRef by cURL, if not doi = null
        $doi = isset($infoFromRefXml['doi']) ? $infoFromRefXml['doi'] : NULL;
        if (!isset($doi)) {
            $doi = $this->getInfoFromCrossRef($referenceTitle, $referenceAuthor, $ch);
            $source = 'Grobid/CrossRef';
        }
        // URL from Doi, by cURL on oaDoi
        $url = isset($results['response']['docs'][0]['halId_s']) ? HAL_URL . '/' . $results['response']['docs'][0]['halId_s'] : null;
        if (!isset($url)) {
            $url = $this->getUrlFromDoi($doi, $ch);
            $source = ($source == 'Grobid/CrossRef') ? 'Grobid/CrossRef/oaDoi' : $source = 'Grobid/oaDoi';
        }
        // Add the found identifiers to the xml reference (if they don't exist)
        $customsRefXml = $this->appendIdentifiersToXml($row[self::REFXML_ORIGINAL], $doi, $url);
        // Convert Xml reference to Html
        $referenceHtml = '<p class="ref" id="' . $row[self::REFID] . '">' . $this->transformXmlToHtml($customsRefXml) . '</p>';
        // Update db
        $set = [
            self::REFHTML => $referenceHtml,
            self::REFXML => preg_replace('/xmlns[^=]*="[^"]*"/i', '', $customsRefXml),
            self::REFSTATUS => self::UPDATED,
            self::REFVALIDETY => $validity,
            self::DOI => $doi,
            self::URL => $url,
            self::SOURCE => $source,
            self::TARGETDOCID => $targetDocId
        ];
        $this->_db->update(self::DOC_REFERENCES, $set, self::REFID . ' = ' . $row[self::REFID]);
        return true;
    }


    /**
     * Delete references by id 
     * @param $docId
     */

    public static function deleteById($docId){

        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $db->delete(self::DOC_REFERENCES, 'DOCID = ' . $docId);

    }

    /**
     * Delete references where REFHTML is NULL
     * This method is used once per month to clean the table DOC_REFERENCES
     */
    public static function deleteByRefHtmlNull ()
    {
        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $db->delete(self::DOC_REFERENCES, 'REFHTML IS NULL');
    }

    /**
     * Get title, author, doi from REFXML
     * @param $refXml
     * @return array
     */
    private function getInfoFromRefXml($refXml)
    {
        $infoFromXml = [];
        foreach ($this->_referenceXPaths as $key => $value) {
            $dom = new DOMDocument();
            $dom->loadXML((string)$refXml);
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('xmlns', 'http://www.tei-c.org/ns/1.0');
            $dom_node_list = $xpath->query($value);
            if ($dom_node_list->length > 0) {
                $infoFromXml[$key] = $dom_node_list->item(0)->nodeValue;
            }
        }
        return $infoFromXml;
    }

    /**
     * Find DOI from the request CrossRef
     * Make a test of similarity to get the result
     * @param $titleReference : string
     * @param $authorReference : string
     * @param $ch
     * @return null|string
     */
    private function getInfoFromCrossRef($titleReference, $authorReference, $ch)
    {
        $jsonObject = self::curlCall(self::CROSSREF_URL . "/works?rows=10&query.title=" . urlencode($this->clean($titleReference)), $ch);
        $info = [];
        $max = -1;
        $doi = null;
        $author = null;
        if (!isset($jsonObject['message']['items'])) {
            return null;
        }
        foreach ($jsonObject['message']['items'] as $item) {
            if (isset($item['title'][0])) {
                similar_text(strtolower($titleReference), strtolower($item['title'][0]), $percent);
                if (isset($item['author']) && isset($item['author'][0]) && isset($item['author'][0]['family']) && isset($item['DOI'])) {
                    $info [] = ['percent' => $percent, 'doi' => $item['DOI'], 'author' => $item['author'][0]['family']];
                }
            }
        }
        foreach ($info as $value) {
            if ($value['percent'] > $max) {
                $max = $value['percent'];
                $doi = $value['doi'];
                $author = $value['author'];
            }
        }
        // if author in parameter is the same returned by crossRef, it is ok
        if ($author == $authorReference) {
            return $doi;
        }
        return null;
    }

    /**
     * Get URL (Open Acces) from Doi, by cURL on oaDoi
     * @param $doi
     * @param $ch : CURL object
     * @return null|string
     */
    private function getUrlFromDoi($doi, $ch)
    {
        if (!isset($doi)) {
            return null;
        }
        $oadoi = self::curlCall(self::OADOI_URL . "/v1/publication/doi/" . $doi, $ch);
        if (!isset($oadoi['results'][0]['free_fulltext_url'])) {
            return null;
        }
        return $oadoi['results'][0]['free_fulltext_url'];
    }

    /**
     * cURL Process
     * @param $q : query
     * @param $ch : CURL object
     * @return array json
     */
    private static function curlCall($q, $ch)
    {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $q);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $string = curl_exec($ch);
        return json_decode($string, true);
    }

    /**
     * Removes special chars
     * @param $string : string
     * @return string
     */
    private function clean($string)
    {
        return trim(preg_replace('/[^A-Za-z0-9\-]/', urlencode(' '), $string));
    }

    /**
     * Append DOI and URI to the XML reference
     * @param $refXml
     * @param $doi
     * @param $url
     * @return string
     */
    private function appendIdentifiersToXml($refXml, $doi = null, $url = null)
    {
        $doc = new Ccsd_DOMDocument('1.0', self::UTF8);
        $doc->formatOutput = true;
        $doc->substituteEntities = true;
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($refXml);
        $parentEl = $doc->getElementsByTagName('biblStruct');
        $xPath = new DOMXPath($doc);
        $xPath->registerNamespace('xmlns', 'http://www.tei-c.org/ns/1.0');
        $doiOriginalEl = $xPath->query('/xmlns:biblStruct/xmlns:idno[@type="doi"]');
        $uriOriginalEl = $xPath->query('/xmlns:biblStruct/xmlns:idno[@type="uri"]');
        // Create DOI
        if (isset($doi) && !isset($doiOriginalEl->item(0)->nodeValue)) {
            $doiEl = $doc->createElement('idno');
            $doiEl->setAttribute('type', 'doi');
            $doiEl->nodeValue = $doi;
            $parentEl->item(0)->appendChild($doiEl);
        }
        // Create URI
        if (isset($url) && !isset($uriOriginalEl->item(0)->nodeValue)) {
            $uriEl = $doc->createElement('idno');
            $uriEl->setAttribute('type', 'uri');
            $uriEl->nodeValue = htmlspecialchars($url);
            $parentEl->item(0)->appendChild($uriEl);
        }
        return $doc->saveXML($doc->documentElement);
    }

    /**
     * Convert a reference from xml to html format
     * @param $refXml
     * @return string
     */
    private function transformXmlToHtml($refXml)
    {
        // Create DOM object from xml string
        $xml = new DOMDocument;
        $xml->loadXML($refXml);
        // Load XSL file
        $xsl = new DOMDocument;
        $xsl->load(__DIR__ . '/xsl/html.xsl');
        // Configure the transformer
        $processor = new XSLTProcessor;
        // Attach the xsl rules
        $processor->importStylesheet($xsl);
        // Make the transform to an html
        return $processor->transformToXml($xml);
    }

    /**
     * Get the references in HTML format for presentation
     * Search cache file reference_XXXXXX.html
     * if it does not exist : create it from db (return false if there are no references in db)
     *  Return string containing references in HTML
     * @param bool $content
     * @return bool|string
     */
    public function getHTMLReferences($content = true)
    {
        if (!$this->cacheReferencesExist() && !$this->createCacheReferences()) {
            return false;
        }
        $file = Hal_Document::getRacineCache_s($this->_docId) . 'references_' . $this->_docId . '.html';
        if (is_file($file)) {
            if ($content) {
                return $content = file_get_contents($file);
            }
            return $file;
        }
    }

    /**
     * Create the cache file for references
     * @return bool|int
     */
    public function createCacheReferences()
    {
        if (!is_dir(Hal_Document::getRacineCache_s($this->_docId)) && !mkdir(Hal_Document::getRacineCache_s($this->_docId), 0777, true)) {
            return false;
        }
        $this->load();
        $referencesHTML = [];
        foreach ($this->_loadedReferences as $loadedReference) {
            if (isset($loadedReference[self::REFHTML]) && $loadedReference[self::REFVALIDETY] != self::DELETED) {
                $referencesHTML [] = $loadedReference[self::REFHTML];
            }
        }
        if (count($referencesHTML)) {
            $referencesContent = $this->toHTML(implode("", $referencesHTML));
            return file_put_contents(Hal_Document::getRacineCache_s($this->_docId) . '/references_' . $this->_docId . '.html', $referencesContent);
        }
        return false;
    }

    /**
     * Check if the cache file exist or not
     * @return bool
     */
    public function cacheReferencesExist()
    {
        return is_file(Hal_Document::getRacineCache_s($this->_docId) . '/references_' . $this->_docId . '.html') && filesize(Hal_Document::getRacineCache_s($this->_docId) . '/references_' . $this->_docId . '.html') > 0;
    }

    /**
     * Get the number of references in Hal Document from db
     * @return int
     */
    public function getNbReferences()
    {
        $sql = $this->_db->select()->from(self::DOC_REFERENCES)->where('DOCID = ?', (int)$this->_docId)->where(self::REFVALIDETY . ' != ?', self::DELETED)->where(self::REFHTML . ' IS NOT NULL');
        return count($this->_db->fetchAll($sql));
    }

    /**
     * Ckeck that a reference is validated or not from db
     * @param $refId
     * @return string
     */
    public function isReferenceValidated($refId)
    {
        $sql = $this->_db->select()->from(self::DOC_REFERENCES, self::REFVALIDETY)->where(self::REFID . ' = ?', (int)$refId);
        return $this->_db->fetchOne($sql);
    }

    /**
     * Validate a reference in db
     * @param $refId
     */
    public function validateReference($refId)
    {
        $this->_db->update(self::DOC_REFERENCES, array(self::REFVALIDETY => self::VALIDATED), 'REFID = ' . (int)$refId);
    }

    /**
     * Delete a reference from db
     * @param $refId
     */
    public function removeReference($refId)
    {
        $this->_db->update(self::DOC_REFERENCES, [self::REFVALIDETY => self::DELETED], self::REFID . ' = ' . $refId);
        $this->deleteReferenceCache();
    }

    /**
     * Delete the cache file of references
     */
    public function deleteReferenceCache()
    {
        $baseCache = DOCS_CACHE_PATH . wordwrap(sprintf("%08d", $this->_docId), 2, DIRECTORY_SEPARATOR, 1) . '/';
        if (is_file($baseCache . 'references_' . $this->_docId . '.html')) {
            @unlink($baseCache . 'references_' . $this->_docId . '.html');
        }
    }

    /**
     * return the value of an element HTML
     * @param string $html
     * @param string $className
     * @return string
     */
    public function getElementByClassName($html, $className)
    {
        $results = '';
        $dom = new DomDocument('1.0', self::UTF8);
        $dom->loadHTML(utf8_decode($html));
        $finder = new DomXPath($dom);
        $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $className ')]");
        if ($nodes->length > 0) {
            foreach ($nodes as $node) {
                $results = $node->nodeValue;
            }
        }
        return trim($results);
    }

    /**
     * Prepare the information needed to build up the necessary form to edit the reference
     * @param $refId
     * @return array|null
     */
    public function prepareToEditReference($refId)
    {
        $sql = $this->_db->select()->from(self::DOC_REFERENCES, self::REFHTML)->where('REFID = ?', (int)$refId);
        $HTMLReference = $this->_db->fetchOne($sql);
        if ($HTMLReference) {
            $results = [];
            foreach ($this->_htmlReferenceClasses as $type) {
                $results[$type] = $this->getElementByClassName($HTMLReference, $type);
            }
            return $results;
        }
        return [self::AUTHOR_REF => '', self::TITLE_REF => '', self::TITLE_EVENT_REF_V => '', self::VOL_REF_V => '', self::ISSUE_REF_V => '', self::PAGES_REF_V => '', self::DATE_REF_V => '', self::DOI_REF_V => '', self::URL_REF_V => ''];
    }

    /**
     * Check inputs form in case of reference's modification
     * @param $dataForm
     * @return array
     */
    public function checkReferenceModifications($dataForm)
    {
        $msg = [];

        // Une initiale est de la forme Lettre majuscule(Lu) suivie d'un point et potentiellement un tiret (-) avec un nouvelle majuscule suivie d'un point
        // S.    S.-P.    S.-P.-Y.      S. P.
        $initiale = '\\p{Lu}\.((-\\p{Lu}\.)*|( \p{Lu}\.)?)';

        // Le nom de famille de l'auteur commence par une majuscule et est suivie de lettres(L). Il peut contenir un tiret, une apostrophe,
        $lastname = '\\p{Lu}[\\p{L}-\']+';

        $regexAuthor = $initiale . '\s+' . $lastname;
        $fullRegexp = '/^' . $regexAuthor . '((\s*[;,]\s*|\s+and\s+)'.$regexAuthor.')*$/u';

        foreach ($dataForm as $item => $value) {
            if (($item == self::AUTHOR_REF || $item == self::TITLE_REF) && $value == '') {
                $msg [] = [$item => Zend_Registry::get('Zend_Translate')->translate('Ce champ est requis')];
                continue;
            }
            if ($value != '') {
                $value = trim($value);
                if ($item == self::AUTHOR_REF
                    && !preg_match($fullRegexp, $value)) {
                    // La liste d'auteur est du type A1, A2 ; A3 and A4, A5
                    $msg [] = [$item => Zend_Registry::get('Zend_Translate')->translate('Ce champ doit respecter le format proposé')];
                }
                if (($item == self::VOL_REF_V || $item == self::ISSUE_REF_V || $item == self::PAGES_REF_V) && !preg_match('/^[a-zA-Z0-9_-]*$/', $value)) {
                    $msg [] = [$item => Zend_Registry::get('Zend_Translate')->translate('Ce champ ne doit contenir que des caractères alphanumériques')];
                }
                if ($item == self::DATE_REF_V && !preg_match('/^[0-9]*$/', $value)) {
                    $msg [] = [$item => Zend_Registry::get('Zend_Translate')->translate('Ce champ ne doit contenir que des caractères numériques')];
                }
                if ($item == self::DOI_REF_V && !preg_match('/^10[.][0-9]{4,}[^\s\"\<\>]*\/[^\s"\<\>]+$/', $value)) {
                    $msg [] = [$item => Zend_Registry::get('Zend_Translate')->translate('Ce champ doit respecter le format proposé')];
                }
            }
        }
        return $msg;
    }

    /**
     * Edit a reference on demand by user in modification interface
     * @param $refId
     * @param $data
     * @return bool
     */
    public function editReference($refId, $data)
    {
        $sql = $this->_db->select()->from(self::DOC_REFERENCES, self::REFHTML)->where('REFID = ?', (int)$refId);
        $referenceHTML = $this->_db->fetchOne($sql);
        if (empty($referenceHTML)) {
            $this->createReference($data);
            return true; // Create new reference
        }
        foreach ($data as $key => $value) {
            $dom = new DomDocument('1.0', self::UTF8);
            $dom->loadHTML(utf8_decode($referenceHTML), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            $finder = new DomXPath($dom);
            $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $key ')]");
            $value = trim($value);
            if ($value != '') {
                if (isset($nodes->length) && $nodes->length > 0) {
                    // edit element
                    if ($key == self::AUTHOR_REF && strstr($value, ',')) {
                        $auths = explode(',', $value);
                        if (count($auths) > 5) {
                            $output = array_slice($auths, 0, 5);
                            for ($i = 0; $i < sizeof($output) - 1; $i++) {
                                $output[$i] = $output[$i] . ',';
                            }
                            $nodes->item(0)->nodeValue = implode($output) . ', et al.';
                        } else {
                            $nodes->item(0)->nodeValue = $value;
                        }
                    } else if ($key == self::DOI_REF_V) {
                        $nodes->item(0)->setAttribute('href', 'https://dx.doi.org/' . $value);
                        $nodes->item(0)->nodeValue = $value;
                    } else if ($key == self::URL_REF_V) {
                        $nodes->item(0)->setAttribute('href', $value);
                        $nodes->item(0)->nodeValue = $value;
                    } else {
                        $nodes->item(0)->nodeValue = $value;
                    }
                } else {
                    // Create element
                    $dom = $this->createElementOfReference($referenceHTML, $key, $value);
                }
            } else {
                if ($nodes->length > 0) {
                    // Remove element
                    $this->deleteNode($nodes->item(0)->parentNode);
                }
            }
            $referenceHTML = $dom->saveHTML(utf8_encode($dom->documentElement));
        }
        $set = [
            self::REFHTML => $referenceHTML,
            self::REFVALIDETY => self::NOTVERIFIED,
            self::REFXML => $this->transformHtmlToXml($referenceHTML)
        ];
        $this->_db->update(self::DOC_REFERENCES, $set, self::REFID . ' = ' . (int)$refId);
        $this->deleteReferenceCache();
        return true;
    }

    /**
     *
     */
    public function revertPrenomNomInXML()
    {
        $this->fullLoad();

        $references = $this->get();

        foreach($references as $ref) {

            $refId = $ref['REFID'];
            $xml= $ref[self::REFXML_ORIGINAL];

            $dom = new DOMDocument();
            $dom->loadXML((string)$xml);
            $xpath = new DOMXPath($dom);
            $xpath->registerNamespace('xmlns', 'http://www.tei-c.org/ns/1.0');
            $dom_node_list = $xpath->query('/xmlns:biblStruct/xmlns:analytic/xmlns:author/xmlns:persName');

            if ($dom_node_list->length == 0) {
                $dom_node_list = $xpath->query('/xmlns:biblStruct/xmlns:monogr/xmlns:author/xmlns:persName');
            }

            // Pour chaque auteur, on remplace le nom par le prénom et inversement
            foreach ($dom_node_list as $node) {
                $forenameNode = $node->getElementsByTagName('forename');

                if ($forenameNode->length == 0) {
                    continue;
                }
                $forenameNode = $forenameNode->item(0);

                $surnameNode = $node->getElementsByTagName('surname');

                if ($surnameNode->length == 0) {
                    continue;
                }
                $surnameNode = $surnameNode->item(0);

                $surnameValue = $surnameNode->nodeValue;
                $forenameValue = $forenameNode->nodeValue;

                $forenameNode->nodeValue = $surnameValue;
                $surnameNode->nodeValue = $forenameValue;

            }

            $xml = $dom->saveXML();

            if (empty($xml)) {
                continue;
            }

            // Convert Xml reference to Html
            $referenceHtml = '<p class="ref" id="' . $refId . '">' . $this->transformXmlToHtml($xml) . '</p>';
            // Update db
            $set = [
                self::REFXML_ORIGINAL => $xml,
                self::REFHTML => $referenceHtml,
                self::REFXML => preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xml),
                self::REFSTATUS => self::UPDATED,
                self::REFVALIDETY => self::VALIDATED,
                self::UPDATE_DATE => date('Y-m-d H:i:s'),
            ];
            $this->_db->update(self::DOC_REFERENCES, $set, self::REFID . ' = ' . $refId);
        }
        $this->deleteReferenceCache();
    }

    /**
     * Add a reference
     * @param $data
     */
    private function createReference($data)
    {
        $referenceCitation =
            '<span class="citation-reference">
               <span class="authors-reference">' . $data["authors-reference"] . '</span>,&nbsp;
               <span class="title-reference">' . $data["title-reference"] . '</span>';
        if (!empty($data[self::TITLE_EVENT_REF_V])) {
            $referenceCitation = $referenceCitation . '<span class="titleEvent-reference">,&nbsp;<span class="titleEvent-reference-value">' . $data[self::TITLE_EVENT_REF_V] . '</span></span>';
        }
        if (!empty($data[self::VOL_REF_V])) {
            $referenceCitation = $referenceCitation . '<span class="vol-reference">, vol.<span class="vol-reference-value">' . $data[self::VOL_REF_V] . '</span></span>';
        }
        if (!empty($data[self::ISSUE_REF_V])) {
            $referenceCitation = $referenceCitation . '<span class="issue-reference">, issue.<span class="issue-reference-value">' . $data[self::ISSUE_REF_V] . '</span></span>';
        }
        if (!empty($data[self::PAGES_REF_V])) {
            $referenceCitation = $referenceCitation . '<span class="pages-reference">, pp.<span class="pages-reference-value">' . $data[self::PAGES_REF_V] . '</span></span>';
        }
        if (!empty($data[self::DATE_REF_V])) {
            $referenceCitation = $referenceCitation . '<span class="date-reference">, <span class="date-reference-value">' . $data[self::DATE_REF_V] . '</span>.</span>';
        }
        $referenceCitation = $referenceCitation . '</span><span class="identifiers-reference">';
        if (!empty($data[self::DOI_REF_V])) {
            $referenceCitation = $referenceCitation . '<span class="doi-reference"><br>DOI : <a class="doi-reference-value" target="_blank" href="' . $data[self::DOI_REF_V] . '">' . $data["doi-reference-value"] . '</a></span>';
        }
        if (!empty($data[self::URL_REF_V])) {
            $referenceCitation = $referenceCitation . '<span class="url-reference"><br>URL : <a class="url-reference-value" target="_blank" href="' . $data[self::URL_REF_V] . '">' . $data["url-reference-value"] . '</a></span>';
            if (preg_match("/hal.archives-ouvertes.fr/i", $data[self::URL_REF_V])) {
                $stringUrl = parse_url($data[self::URL_REF_V], PHP_URL_PATH);
                $sql = $this->_db->select()->from(Hal_Document::TABLE, 'DOCID')->where('IDENTIFIANT = ?', substr($stringUrl, 1));
                $targetDocId = isset($stringUrl) ? $this->_db->fetchOne($sql) : NULL;
            }
        }
        $referenceCitation = $referenceCitation . '</span>';
        $referenceXML = $this->transformHtmlToXml($referenceCitation);
        $set = [
            self::DOCID => $this->_docId,
            self::REFHTML => $referenceCitation,
            self::REFXML => $referenceXML,
            self::REFSTATUS => self::UPDATED,
            self::REFVALIDETY => self::VALIDATED,
            self::DOI => !empty($data[self::DOI_REF_V]) ? $data[self::DOI_REF_V] : NULL,
            self::URL => !empty($data[self::URL_REF_V]) ? $data[self::URL_REF_V] : NULL,
            self::SOURCE => 'User',
            self::TARGETDOCID => (isset($targetDocId) && $targetDocId) ? $targetDocId : NULL
        ];
        $this->_db->insert(self::DOC_REFERENCES, $set);
        $lastRefId = $this->_db->lastInsertId();
        $referenceHTML = '<p class="ref" id="' . $lastRefId . '">' . $referenceCitation . '</p>';
        $this->_db->update(self::DOC_REFERENCES, [self::REFHTML => $referenceHTML], self::REFID . ' = ' . $lastRefId);
        $this->deleteReferenceCache();
    }

    /**
     * Create an element HTML of a reference, demanded by user in modification interface
     * @param $refHTML
     * @param $elementReferenceClassName
     * @param $newValue
     * @return DomDocument
     */
    private function createElementOfReference($refHTML, $elementReferenceClassName, $newValue)
    {
        // Search where to place the new element
        $dom = new DomDocument('1.0', self::UTF8);
        $dom->loadHTML(utf8_decode($refHTML), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $finder = new DomXPath($dom);
        $types = $this->_htmlReferenceClasses;
        $key = array_search($elementReferenceClassName, $types) - 1;
        if ($key == -1) {
            $key = 0;
        }
        do {
            $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $types[$key] ')]");
            $key--;
        } while ($nodes->length <= 0);
        $elementParent = $nodes->item(0);
        $pos = strpos($types[$key + 1], '-value');
        if ($pos) {
            $newClass = substr($types[$key + 1], 0, -6);
            $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $newClass ')]");
            $elementParent = $nodes->item(0);
        }
        // Creation
        $elmSpanClass = $dom->createElement('span');
        $elmSpanClassValue = $dom->createElement('span');
        $elmValue = $dom->createTextNode($newValue);
        $elmSemiColon = $dom->createTextNode(', ');
        $elmPoint = $dom->createTextNode('.');
        $elmSpanClass->setAttribute('class', substr($elementReferenceClassName, 0, -6));
        $elmSpanClassValue->setAttribute('class', $elementReferenceClassName);
        if ($elementReferenceClassName == self::DOI_REF_V || $elementReferenceClassName == self::URL_REF_V) {
            $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' identifiers-reference ')]");
            $elementParent = $nodes->item(0);
            $elmA = $dom->createElement('a');
            $elmBr = $dom->createElement('br');
            $elmA->setAttribute('class', $elementReferenceClassName);
            $elmA->setAttribute('target', "_blank");
            if ($elementReferenceClassName == self::DOI_REF_V) {
                $elmA->setAttribute('href', 'https://dx.doi.org/' . $newValue);
            } else if ($elementReferenceClassName == self::URL_REF_V) {
                $elmA->setAttribute('href', $newValue);
            }
            $elmA->appendChild($elmValue);
            $elmSpanClass->appendChild($elmBr);
            if ($elementReferenceClassName == self::DOI_REF_V) {
                $elmSpanClass->appendChild($dom->createTextNode('DOI : '));
            } else if ($elementReferenceClassName == self::URL_REF_V) {
                $elmSpanClass->appendChild($dom->createTextNode('URL : '));
            }
            $elmSpanClass->appendChild($elmA);
            $elementParent->appendChild($elmSpanClass);
        } else {
            $elmSpanClassValue->appendChild($elmValue);
            $elmSpanClass->appendChild($elmSemiColon);
            if ($elementReferenceClassName == self::VOL_REF_V) {
                $elmSpanClass->appendChild($dom->createTextNode('vol.'));
            }
            if ($elementReferenceClassName == self::ISSUE_REF_V) {
                $elmSpanClass->appendChild($dom->createTextNode('issue.'));
            }
            if ($elementReferenceClassName == self::PAGES_REF_V) {
                $elmSpanClass->appendChild($dom->createTextNode('pp.'));
            }
            $elmSpanClass->appendChild($elmSpanClassValue);
            if ($elementReferenceClassName == self::DATE_REF_V) {
                $elmSpanClass->appendChild($elmPoint);
            }
            $elementParent->parentNode->insertBefore($elmSpanClass, $elementParent->nextSibling);
        }
        return $dom;
    }

    /**
     * Convert Html reference to Xml in case of modification affected by user in the modification interface
     * @param $referenceHTML
     * @return string
     */
    public function transformHtmlToXml($referenceHTML)
    {
        $doc = new Ccsd_DOMDocument('1.0', self::UTF8);
        $doc->formatOutput = true;
        $doc->substituteEntities = true;
        $doc->preserveWhiteSpace = false;
        $biblStruct = $doc->createElement("biblStruct");
        $analytic = $doc->createElement("analytic");
        $titleMain = $doc->createElement("title");
        $titleMain->setAttribute('type', 'main');
        $analytic->appendChild($titleMain);
        $titleValue = $this->getElementByClassName($referenceHTML, self::TITLE_REF);
        if (!empty($titleValue)) {
            $titleMain->nodeValue = $titleValue;
        }
        $authorsString = $this->getElementByClassName($referenceHTML, self::AUTHOR_REF);
        $authors = [];
        if (!empty($authorsString)) {
            if (!strstr($authorsString, ',')) {
                if (!strstr($authorsString, ' and ')) {
                    // One author
                    $authors [] = $authorsString;
                } else {
                    // Two authors
                    $authors = explode(' and ', $authorsString);
                }
            } else {
                // Three authors or more
                $authors = explode(',', $authorsString);
                $authors[count($authors) - 1] = trim(substr($authors[count($authors) - 1], 4, strlen($authors[count($authors) - 1])));
            }
            foreach ($authors as $auth) {
                $author = $doc->createElement("author");
                $presName = $doc->createElement("presName");
                $fullName = explode('.', $auth);
                if (sizeof($fullName) == 2) {
                    $foreName = $doc->createElement("foreName");
                    $foreName->nodeValue = trim($fullName[0]);
                    $foreName->setAttribute('type', 'first');
                    $surName = $doc->createElement("surName");
                    $surName->nodeValue = trim($fullName[1]);
                    $presName->appendChild($foreName);
                    $presName->appendChild($surName);
                } else {
                    $foreName = $doc->createElement("foreName");
                    $foreName->nodeValue = trim($fullName[0]);
                    $foreName->setAttribute('type', 'first');
                    $presName->appendChild($foreName);
                    for ($i = 1; $i < sizeof($fullName) - 1; $i++) {
                        $foreName = $doc->createElement("foreName");
                        $foreName->nodeValue = trim($fullName[$i]);
                        $foreName->setAttribute('type', 'middle');
                        $presName->appendChild($foreName);
                    }
                    $surName = $doc->createElement("surName");
                    $surName->nodeValue = trim($fullName[sizeof($fullName) - 1]);
                    $presName->appendChild($surName);
                }
                $author->appendChild($presName);
                $analytic->appendChild($author);
            }
        }
        $biblStruct->appendChild($analytic);
        $monogr = $doc->createElement("monogr");
        $imprint = $doc->createElement("imprint");
        $titleEvent = $this->getElementByClassName($referenceHTML, self::TITLE_EVENT_REF_V);
        if (!empty($titleEvent)) {
            $titleMonogr = $doc->createElement("title");
            $titleMonogr->nodeValue = $titleEvent;
            $monogr->appendChild($titleMonogr);
        }
        $vol = $this->getElementByClassName($referenceHTML, self::VOL_REF_V);
        if (!empty($vol)) {
            $biblScopeVol = $doc->createElement("biblScope");
            $biblScopeVol->nodeValue = $vol;
            $biblScopeVol->setAttribute('unit', 'volume');
            $imprint->appendChild($biblScopeVol);
        }
        $issue = $this->getElementByClassName($referenceHTML, self::ISSUE_REF_V);
        if (!empty($issue)) {
            $biblScopeIssue = $doc->createElement("biblScope");
            $biblScopeIssue->nodeValue = $issue;
            $biblScopeIssue->setAttribute('unit', 'issue');
            $imprint->appendChild($biblScopeIssue);
        }
        $pages = $this->getElementByClassName($referenceHTML, self::PAGES_REF_V);
        if (!empty($pages)) {
            $biblScopePages = $doc->createElement("biblScope");
            $biblScopePages->setAttribute('unit', 'page');
            if (strstr($pages, '-')) {
                $p = explode('-', $pages);
                $biblScopePages->setAttribute('form', $p[0]);
                $biblScopePages->setAttribute('to', $p[1]);
            } else {
                $biblScopePages->nodeValue = $pages;
            }
            $imprint->appendChild($biblScopePages);
        }
        $date = $this->getElementByClassName($referenceHTML, self::DATE_REF_V);
        if (!empty($date)) {
            $Date = $doc->createElement("date");
            $Date->setAttribute('type', 'published');
            $Date->setAttribute('when', $date);
            $imprint->appendChild($Date);
        }
        $monogr->appendChild($imprint);
        $biblStruct->appendChild($monogr);
        $doi = $this->getElementByClassName($referenceHTML, self::DOI_REF_V);
        if (!empty($doi)) {
            $idno = $doc->createElement("idno");
            $idno->setAttribute('type', 'doi');
            $idno->nodeValue = $doi;
            $biblStruct->appendChild($idno);
        }
        $url = $this->getElementByClassName($referenceHTML, self::URL_REF_V);
        if (!empty($url)) {
            $idno = $doc->createElement("idno");
            $idno->setAttribute('type', 'uri');
            $idno->nodeValue = $url;
            $biblStruct->appendChild($idno);
        }
        $doc->appendChild($biblStruct);
        return $doc->saveXML($doc->documentElement);
    }

    /**
     * Delete a node completely with his parent
     * @param $node
     */
    private function deleteNode($node)
    {
        $this->deleteChildren($node);
        $parent = $node->parentNode;
        $parent->removeChild($node);
    }

    /**
     * For Delete a node completely with his parent
     * @param $node
     */
    private function deleteChildren($node)
    {
        while (isset($node->firstChild)) {
            $this->deleteChildren($node->firstChild);
            $node->removeChild($node->firstChild);
        }
    }

    /**
     * To generate the html file of references
     * @param $string
     * @return string
     */
    private function toHTML($string) {
        $dom = new Ccsd_DOMDocument('1.0', self::UTF8);
        $dom->loadHTML(utf8_decode($string), LIBXML_HTML_NODEFDTD);
        $dom->formatOutput = true;
        $dom->substituteEntities = true;
        $dom->preserveWhiteSpace = false;
        return($dom->saveHTML());
    }

    /**
     * Retourne la liste des documents cités (identifiant HAL ou DOI)
     * @param $docid int
     * @return array
     */
    static public function getReferences($docid)
    {
        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $sql = $db->select()->from(self::DOC_REFERENCES, [static::DOI, static::URL])->where(self::DOCID . ' = ?', $docid);
        $res = [];
        foreach ($db->fetchAll($sql) as $row) {
            if ($row[static::URL] != '') {
                $res[] = $row[static::URL];
            }
            if ($row[static::DOI] != '') {
                $res[] = 'https://doi.org/' . $row[static::DOI];
            }
        }
        return $res;
    }

    /**
     * Retourne la liste des documents dans HAL qui citent un document
     * @param $docid int
     * @return array
     */
    static public function getIsReferences($docid)
    {
        $db = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
        $sql = $db->select()->from(self::DOC_REFERENCES, [static::DOCID])->where(self::TARGETDOCID . ' = ?', $docid);
        $res = [];
        foreach ($db->fetchAll($sql) as $row) {
            $document = Hal_Document::find($row[static::DOCID]);
            if ($document instanceof Hal_Document) {
                $res[] = $document->getUri();
            }
        }
        return $res;
    }

    /**
     * Retourne le lien exterieur de la référence si il en existe un
     * @return string url du lien exterieur
     */
    public function getLinkExt($id)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql =  $db->select()->distinct()
            ->from('DOC_LINKEXT', 'URL')
            ->where("LINKID = ?", $id);
        return $db->fetchOne($sql);
    }
}


