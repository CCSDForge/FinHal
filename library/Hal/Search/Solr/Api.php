<?php

/**
 * Méthodes spécifiques à HAL pour la recherche avec solr
 * @author rtournoy
 *
 */
class Hal_Search_Solr_Api extends Ccsd_Search_Solr_Search
{

    const MAX_ROWS = 10000;
    const MAX_EXPORT_ROWS = 1000;
    const MAX_EXPORT_ROWS_FEEDS = 50;
    const MAX_FACETS = 10000;
    const MAX_NUMBER_OF_AUTHORS_IN_FEEDS = 1;
    const DEFAULT_RESPONSE_FORMAT = self::API_RESPONSE_FORMAT_JSON;
    const FEED_FORMAT_RSS = 'rss';
    const FEED_FORMAT_ATOM = 'atom';
    const DEFAULT_AUTHOR_EMAIL = 'ano.nymous@ccsd.cnrs.fr.invalid';
    const LABEL_ENDNOTE = 'label_endnote';
    const LABEL_BIBTEX = 'label_bibtex';
    const LABEL_XML_TEI = 'label_xml';

    const API_RESPONSE_FORMAT_PDF = 'pdf';
    const API_RESPONSE_FORMAT_BIBTEX = 'bibtex';
    const API_RESPONSE_FORMAT_RTF = 'rtf';
    const API_RESPONSE_FORMAT_ENDNOTE = 'endnote';
    const API_RESPONSE_FORMAT_CSV = 'csv';
    const API_RESPONSE_FORMAT_XML_TEI = 'xml-tei';
    const API_RESPONSE_FORMAT_XML = 'xml';
    const API_RESPONSE_FORMAT_JSON = 'json';

    /**
     * Retourne un résultat solr au format TEI après transformation XSLT
     * @param string $curlResult php serialisé du résultat solr
     * @param string $link url requête solr
     * @return string XML TEI
     */
    static function formatOutputAsTei($curlResult, $link = '')
    {
        $res = unserialize($curlResult);

        if (!$res) {
            self::formatErrorAsSolr('Sorry, error getting search data. Please try again.', self::API_RESPONSE_FORMAT_XML, true);
            error_log('Unserialize error at ' . __METHOD__);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<teiCorpus xmlns="http://www.tei-c.org/ns/1.0">';
        $xml .= '<teiHeader>';
        $xml .= '<fileDesc>';
        $xml .= '<titleStmt><title>Search results from HAL API</title></titleStmt>';
        $xml .= '<publicationStmt><distributor>CCSD</distributor><availability status="restricted"><licence target="http://creativecommons.org/licenses/by/4.0/">Distributed under a Creative Commons Attribution 4.0 International License</licence></availability><date when="' . date('c') . '"/></publicationStmt>';
        $xml .= '<sourceDesc><p part="N">HAL API platform</p></sourceDesc>';
        $xml .= '</fileDesc>';
        $xml .= '<profileDesc>';
        $xml .= '<creation>';
        $xml .= '<measure quantity="' . $res ['response'] ['numFound'] . '" unit="count" commodity="totalSearchResults"/>';
        $xml .= '<measure quantity="' . count($res ['response'] ['docs']) . '" unit="count" commodity="searchResultsInDocument"/>';
        if ($link) {
            $xml .= '<ptr type="query" target="' . Ccsd_Tools_String::xmlSafe($link) . '"/>';
        }
        $xml .= '</creation>';
        $xml .= '</profileDesc>';
        $xml .= '</teiHeader>';
        if (is_array($res ['response'] ['docs'])) {
            foreach ($res ['response'] ['docs'] as $docs) {
                $xml .= $docs [self::LABEL_XML_TEI];
            }
        }
        $xml .= '</teiCorpus>';

        libxml_use_internal_errors(true);

        $xsl = new DOMDocument ();
        $xsl->load(__DIR__ . '/xsl/teiCorpusToTEI.xsl');
        $xmlDom = new DOMDocument ();
        // @see http://www.php.net/manual/en/libxml.constants.php
        if (defined(LIBXML_PARSEHUGE) && defined(LIBXML_COMPACT)) {
            $xmlLoadRes = $xmlDom->loadXML($xml, LIBXML_PARSEHUGE | LIBXML_COMPACT);
        } else {
            $xmlLoadRes = $xmlDom->loadXML($xml);
        }

        if (!$xmlLoadRes) {
            static::logXmlErrors('formatOutputAsTei_loadXML');
        }

        $proc = new XSLTProcessor ();
        $proc->importStyleSheet($xsl);

        $xmlTransform = $proc->transformToXML($xmlDom);

        if (!$xmlTransform) {
            static::logXmlErrors('formatOutputAsTei_transformToXML');
        }

        return $xmlTransform;
    }

    /**
     * Formate une erreur avec le format de réponse de solr
     * @param string $error
     * @param string $contentType
     * @param boolean $header
     * @return string
     */
    static function formatErrorAsSolr($error, $contentType = null, $header = false)
    {
        if ($contentType == self::API_RESPONSE_FORMAT_XML) {
            $headerString = 'Content-Type: text/xml; charset=utf-8';
            $errorMessage = '<response><lst name="error"><str name="msg">' . Ccsd_Tools_String::xmlSafe($error) . '</str></lst></response>';
        } else {
            $headerString = 'Content-Type: application/json; charset=utf-8';
            $errorMessage = '{"error":{"msg":"' . json_encode($error) . '"}}';
        }
        if ($header) {
            header($headerString);
        }
        return $errorMessage;
    }

    /**
     * Log erreurs xml internes
     * si libxml_use_internal_errors(true)
     * @param string $method
     */
    static function logXmlErrors($method = '')
    {
        $serverName = $_SERVER['SERVER_NAME'];
        foreach (libxml_get_errors() as $error) {
            error_log($serverName . ' Error in: ' . $method . '. Error code: ' . $error->code . '. Error Message: ' . trim($error->message));
        }
        libxml_clear_errors();
    }

    /**
     * Retourne un résultat solr du référentiel structure au format TEI
     * @param string $curlResult
     * @return string
     */
    static function formatOutputAsTeiStructure($curlResult)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<listOrg>' . PHP_EOL;

        $solrResult = unserialize($curlResult);

        if ((!$solrResult) || (!isset($solrResult ['response'] ['docs']))) {
            return $xml . '</listOrg>' . PHP_EOL;
        }

        foreach ($solrResult ['response'] ['docs'] as $docs) {
            if (isset($docs [self::LABEL_XML_TEI])) {
                $xml .= $docs [self::LABEL_XML_TEI];
            }
        }
        return $xml . '</listOrg>' . PHP_EOL;

    }

    /**
     * Formate un résultat solr sous forme de flux rss ou atom
     * @param string $curlResult php serialisé
     * @param string $type rss ou atom
     * @param string $rawQuery requete solr
     * @return string
     * @throws Zend_Date_Exception
     * @throws Zend_Feed_Exception
     */
    static function formatOutputAsFeed($curlResult, $rawQuery, $type = self::FEED_FORMAT_RSS)
    {

        try {
            self::validateFeedFormat($type);
        } catch (Zend_Feed_Exception $e) {
            die ($e->getMessage());
        }

        $feed = new Zend_Feed_Writer_Feed ();

        $curlResult = unserialize($curlResult);

        $rawQuery = str_replace($_SERVER ['HTTP_HOST'], 'hal.archives-ouvertes.fr', $rawQuery);
        $rawQuery = str_replace('&wt=' . $type, '', $rawQuery);

        $feed->setTitle('HAL : Dernières publications');


        $filteredQuery = filter_var($rawQuery, FILTER_SANITIZE_URL);


        try {
            $feed->setLink($filteredQuery);
        } catch (Zend_Feed_Exception $exc) {
            // pas de traitement
        }

        try {
            $feed->setFeedLink($filteredQuery, $type);
        } catch (Zend_Feed_Exception $exc) {
            // pas de traitement
        }


        $feed->setGenerator('HAL', '3', 'https://hal.archives-ouvertes.fr/');


        if ($type != self::FEED_FORMAT_RSS) {
            /**
             * RSS spec doesn't allow <author></author> outside <item></item>
             */
            $feed->addAuthor([
                'name' => 'CCSD UMS 3668',
                'email' => 'contact@ccsd.cnrs.fr',
                'uri' => 'https://www.ccsd.cnrs.fr/'
            ]);
        }


        $feed->setDescription('HAL : Dernières publications');
        $feed->setDateModified(time());

        /**
         * Add one or more entries.
         * Note that entries must
         * be manually added once created.
         */
        if (isset($curlResult ['response'] ['docs']) && is_array($curlResult ['response'] ['docs'])) {

            foreach ($curlResult ['response'] ['docs'] as $docs) {

                $entry = $feed->createEntry();

                if (isset($docs ['title_s'] [0])) {

                    $t = '[' . $docs ['halId_s'] . '] ';
                    $t .= $docs ['title_s'] [0];

                    $entry->setTitle($t);
                } else {
                    $entry->setTitle('[' . $docs ['halId_s'] . ']');
                }

                $id = 1;

                if ((isset($docs ['authFullName_s'])) && (is_array($docs ['authFullName_s']))) {
                    foreach ($docs ['authFullName_s'] as $auth) {
                        $entry->addAuthor(['name' => $auth, 'email' => self::DEFAULT_AUTHOR_EMAIL]);
                        $id++;
                        if ($id > static::MAX_NUMBER_OF_AUTHORS_IN_FEEDS) {
                            break;
                        }
                    }
                }


                if (Zend_Uri::check($docs ['uri_s'])) {
                    $entry->setId($docs ['uri_s']);
                    $entry->setLink($docs ['uri_s']);
                } else {
                    $entry->setId('https://hal.archives-ouvertes.fr/' . $docs ['halId_s']);
                }

                if ((isset($docs ['licence_s'])) && ($docs ['licence_s'] != '')) {
                    $entry->setCopyright($docs ['licence_s']);
                }

                $dateCreated = trim($docs ['submittedDate_tdate'], 'Z') . '+00:00';
                $dateCreated = new Zend_Date($dateCreated, Zend_Date::ISO_8601);

                if ($dateCreated !== false) {
                    try {
                        $entry->setDateCreated($dateCreated);
                    } catch (Exception $e) {
                        $entry->setDateCreated(null);
                    }
                }

                if (isset ($docs ['modifiedDate_tdate'])) {
                    $dateModified = trim($docs ['modifiedDate_tdate'], 'Z') . '+00:00';
                    $dateModified = new Zend_Date($dateModified, Zend_Date::ISO_8601);
                } else {
                    $dateModified = false;
                }

                if ($dateModified !== false) {
                    try {
                        $entry->setDateModified($dateModified);
                    } catch (Exception $e) {
                        $entry->setDateModified(null);
                    }
                } else {
                    // obligatoire pour ATOM
                    $entry->setDateModified(null);
                }

                $content = '[...]';

                if ((isset($docs ['abstract_s'] [0])) && ($docs ['abstract_s'] [0] != '')) {
                    $content = htmlspecialchars($docs ['abstract_s'] [0]);
                }

                $entry->setDescription($content);
                $entry->setContent($content);

                $feed->addEntry($entry);
            }
        }

        /**
         * Render the resulting feed to Atom 1.0 and assign to $out.
         * You can substitute "atom" with "rss" to generate an RSS 2.0 feed.
         */
        return $feed->export($type, true);
    }


    /**
     * Valide un type de flux de syndication
     * @param $feedType string
     * @return bool
     * @throws Zend_Feed_Exception
     */
    private static function validateFeedFormat($feedType)
    {
        if (($feedType != self::FEED_FORMAT_RSS) && ($feedType != self::FEED_FORMAT_ATOM)) {
            throw new Zend_Feed_Exception('Invalid feed type specified: ' . $feedType . '.' . ' Should be one of "rss" or "atom".');
        }
        return true;
    }

    /**
     * Use local Latex2rtf command
     * We generate a 'aux' file by ourself
     * @param $string
     * @throws Hal_Search_Solr_Api_Exception
     */
    static function formatOutputAsRTF2($string)
    {
        $obj = unserialize($string);
        $bibtex = '';
        $bibentryNames = [];
        foreach ($obj ['response'] ['docs'] as $docs) {
            $bibentry_s = $docs [self::LABEL_BIBTEX];
            $array = [];
            preg_match('/^@([^\s\{]*)  \s*  \{      # Type de document
                              \s*  ([^,\s]*) \s* ,  # ref name followed by comma
                              /xms', $bibentry_s, $array);
            $bibentryNames[] = $array[2];
            $bibtex .= $docs [self::LABEL_BIBTEX] . PHP_EOL;
        }
        $serverName = $_SERVER['SERVER_NAME'];
        $uniqid = uniqid($serverName);
        $texProjectName = 'tmp-' . $uniqid;
        $auxContent = self::getTexAux($bibentryNames);
        $texContent = self::getTex($texProjectName);

        $bibtexFile = PATHTEMPDOCS . $texProjectName . '.bib';
        $auxFile = PATHTEMPDOCS . $texProjectName . '.aux';
        $texFile = PATHTEMPDOCS . $texProjectName . '.tex';

        file_put_contents($bibtexFile, $bibtex);
        file_put_contents($auxFile, $auxContent);
        file_put_contents($texFile, $texContent);
        // On va construire le bbl
        $zip = new ZipArchive;
        $zipProject = 'tmp' . $uniqid;

        if ($zip->open(PATHTEMPDOCS . 'tmp' . $uniqid . '.zip', ZipArchive::CREATE)) {
            $zip->addFromString('biblio.tex', $texContent);
            $zip->addFromString('biblio.bib', $bibtex);
            $zip->close();
            $bblResult = 'biblio' . $uniqid . '.pdf';
            $generatedPdf = Ccsd_File::compile(PATHTEMPDOCS, $zipProject . '.zip', $bblResult, false, false);
            @unlink(PATHTEMPDOCS . 'tmp' . $uniqid . '.zip');
            if ($generatedPdf['status'] && is_file(PATHTEMPDOCS . $bblResult)) {
                // Ok
            } else {
                throw new Hal_Search_Solr_Api_Exception("Can't generate bbl file");
            }
        } else {
            throw new Hal_Search_Solr_Api_Exception("Can't create Zip file");
        }
        exec(LATEX2RTFCMD . " " . PATHTEMPDOCS . $texProjectName, $output, $return);
        if ($return != 0) {
            throw new Hal_Search_Solr_Api_Exception("Latex2rtf error code: $return\n");
        }
        $outputFile = PATHTEMPDOCS . $texProjectName . self::API_RESPONSE_FORMAT_RTF;
        if (file_exists($outputFile)) {
            return file_get_contents($outputFile);
        } else {
            throw new Hal_Search_Solr_Api_Exception("Latex2rtf produce no output\n");
        }
    }

    /**
     * Construct a aux file like tex to put all bibliographics references.
     * @param string[] $entries
     * @return string
     */
    private static function getTexAux($entries)
    {
        $str = "\\relax\n";
        $str .= "\\bibstyle\n";
        $str .= "\{plain}\n";
        $str .= "\\citation{*}\n";
        $str .= "\\bibdata{t}\n";
        $n = 1;
        foreach ($entries as $entry) {
            $str .= "\\bibcite{" . $entry . "}{" . $n . "}\n";
            $n++;
        }
        return $str;
    }

    /**
     * Return a minimal tex file to format bibliographics reference
     * @param $filename
     * @return string
     */

    static function getTex($filename)
    {
        $str = "\\documentclass{article}  \n";
        $str .= "\\usepackage[latin1]{inputenc}  \n";
        $str .= "\\usepackage[T1]{fontenc}   \n";
        $str .= "\\bibliographystyle{plain}  \n";
        $str .= "\\begin{document}   \n";
        $str .= "\\section{biblio} \n";
        $str .= "\\nocite{*}  \n";
        $str .= "\\bibliography{" . $filename . "}  \n";
        return $str . "\\end{document}   \n";
    }

    /**
     * Formate un résultat solr au format RTF
     * Todo: Tex math formula are not transformed... Not so simple...
     *       Take a look to latex2rtf : http://sourceforge.net/projects/latex2rtf/
     *
     * @param string $string // Bibtex string
     * @return string
     */
    static public function formatOutputAsRTF1($string)
    {
        $rtf = "{\\rtf1\\ansi\\uc1\\deff0\\deflang1024\n";
        $rtf .= "{\\fonttbl{\\f0\\fnil\\fcharset0 Times New Roman;}\n";
        $rtf .= "}\n";
        $rtf .= "{\\stylesheet\n";
        $rtf .= "{\\s61\\ql\\sb240\\sa120\\keepn\\f0\\b\\fs32 \\sbasedon0\\snext62 bibheading;}\n";
        $rtf .= "{\\s62\\ql\\fi-567\\li567\\sb0\sa0\\f0\\fs20 \\sbasedon0\\snext62 bibitem;}\n";
        $rtf .= "}\n";
        $rtf .= "{\\info\n";
        $rtf .= "{\\title Solr query: xxxx }\n";
        $rtf .= "{\\doccomm Created using HAL converter on " . date("%Y-%m-%d") . "\n";
        $rtf .= "}\n";
        $rtf .= "}\n";
        $rtf .= "{\\footer\\pard\\plain\\f0\\fs20\\qc\\chpgn\\par}\n";
        $rtf .= "\\paperw12280\\paperh15900\\margl2680\\margr2700\\margt2540\\margb1760\\pgnstart0\\widowctrl\\qj\\ftnbj\\f0\\aftnnar\n";
        $rtf .= "{{\\pard\\plain\\s61\\ql\\sb240\\sa120\\keepn\\f0\\b\\fs32\\sl240\\slmult1 \\sb120 \\fi0 {\\plain\\b\\fs32 References}\\par\n";
        $obj = unserialize($string);
        if (isset($obj ['response']) && isset($obj ['response'] ['docs']) && is_array($obj ['response'] ['docs'])) {
            $i = 1;
            foreach ($obj ['response'] ['docs'] as $entry) {
                $rtfentry = new Hal_Search_Solr_Api_RTF_RtfEntry($entry ['citationFull_s'], $i++);
                $rtfentry->emphased_ifSet($entry, 'journalPublisher_s');
                $rtfentry->boldify_ifSet($entry, 'halId_s');
                $rtf .= $rtfentry->__toString();
            }
        }
        return $rtf . '}}}';
    }

    /** Try to do Rtf by using latex2rtf
     * Generate a bibtex file and a minimalist tex file
     */
    static public function formatOutputAsRTF3($string)
    {
        $radical = "forRtf";
        $bibtex = self::formatOutputAsBibtex($string);
        $tex = '\documentclass[]{article}\pdfoutput=1\title{References}\begin{document}\maketitle\nocite{*}\bibliographystyle{unsrt}\bibliography{' . $radical . '}\end{document}';
        $uniqid = uniqid('rtf_');
        $localdir = PATHTEMPDOCS . $uniqid;
        mkdir($localdir);
        // $remotedir = "/docs/testing/tmp/$uniqid";
        $remotedir = $localdir;
        file_put_contents("$localdir/$radical.bib" , $bibtex);
        file_put_contents("$localdir/$radical.tex" , $tex);
        $generatedPdf = Ccsd_File::compile($remotedir, '', "$radical.pdf", false, false, null,null, LATEX2RTF_COMPILE_SERVICE);
        $rtffile = "$localdir/$radical.rtf";
        if (file_exists($rtffile)) {
            return file_get_contents($rtffile);
        } else {
            return self::formatOutputAsRTF1($string);
        }
    }

    /**
     * Aiguillage selon les methodes de production du RTF
     * @param $string
     * @return string
     */
    static public function formatOutputAsRTF($string) {
        return self::formatOutputAsRTF1($string);
    }
    /**
     * Formate un résultat bibtex solr au format PDF
     * @param string $string // Bibtex string
     * @return string
     */
    static function formatOuputAsPDF($string)
    {

        $tex = '\documentclass[11pt,a4paper]{article}' . PHP_EOL;
        $tex .= '\pdfoutput=1' . PHP_EOL;
        $tex .= '\def\refname{}' . PHP_EOL;
        $tex .= '\usepackage{natbib}' . PHP_EOL;
        $tex .= '\usepackage{hyperref}' . PHP_EOL;
        $tex .= '\bibliographystyle{unsrtnat}' . PHP_EOL;
        $tex .= '\setlength{\bibhang}{0.5em}' . PHP_EOL;
        $tex .= '\usepackage[utf8]{inputenc}' . PHP_EOL;
        $tex .= '\pagestyle{empty}' . PHP_EOL;
        $tex .= '\setlength{\topmargin}{0cm}' . PHP_EOL;
        $tex .= '\setlength{\headheight}{0cm}' . PHP_EOL;
        $tex .= '\setlength{\headsep}{0cm}' . PHP_EOL;
        $tex .= '\setlength{\textheight}{25.7cm}' . PHP_EOL;
        $tex .= '\setlength{\textwidth}{17cm}' . PHP_EOL;
        $tex .= '\setlength{\hoffset}{0cm}' . PHP_EOL;
        $tex .= '\setlength{\oddsidemargin}{0cm}' . PHP_EOL;
        $tex .= '\setlength{\evensidemargin}{0cm}' . PHP_EOL;
        $tex .= '\pdfoptionpdfminorversion 6' . PHP_EOL . PHP_EOL;
        $tex .= '\newcommand*{\doi}[1]{\href{http://dx.doi.org/#1}{doi: #1}}' . PHP_EOL . PHP_EOL;
        $tex .= '\begin{document}' . PHP_EOL;
        $tex .= '\bibliography{paper}' . PHP_EOL;
        $tex .= '\nocite{*}' . PHP_EOL;
        $tex .= '\end{document}' . PHP_EOL;
        $zip = new ZipArchive;
        $serverName = $_SERVER['SERVER_NAME'];
        $uniqid = uniqid($serverName);
        if ($zip->open(PATHTEMPDOCS . 'tmp' . $uniqid . '.zip', ZipArchive::CREATE)) {
            $zip->addFromString('paper.tex', $tex);
            $zip->addFromString('paper.bib', $string);
            $zip->close();
            $generatedPdf = Ccsd_File::compile(PATHTEMPDOCS, 'tmp' . $uniqid . '.zip', 'paper' . $uniqid . '.pdf', false, false);
            @unlink(PATHTEMPDOCS . 'tmp' . $uniqid . '.zip');
            if ($generatedPdf['status'] && is_file(PATHTEMPDOCS . 'paper' . $uniqid . '.pdf')) {
                $output = file_get_contents(PATHTEMPDOCS . 'paper' . $uniqid . '.pdf');
                @unlink(PATHTEMPDOCS . 'paper' . $uniqid . '.pdf');
            } else {
                return null;
            }
        } else {
            return null;
        }
        return $output;
    }

    /**
     * Formate un résultat solr au format bibtex
     * @param string $curlResult php serialisé
     * @return string
     */
    static function formatOutputAsBibtex($curlResult)
    {
        $output = '';
        $solrResult = unserialize($curlResult);

        if ((!$solrResult) || (!isset($solrResult ['response'] ['docs']))) {
            return $output;
        }

        foreach ($solrResult ['response'] ['docs'] as $docs) {
            $output .= $docs [self::LABEL_BIBTEX] . PHP_EOL;
        }

        return $output;
    }

    /**
     * Formate un résultat solr au format endnote
     * @param string $curlResult
     * @return string
     */
    static function formatOutputAsEndnote($curlResult)
    {
        $output = '';

        $solrResult = unserialize($curlResult);

        if ((!$solrResult) || (!isset($solrResult ['response'] ['docs']))) {
            return $output;
        }

        foreach ($solrResult ['response'] ['docs'] as $docs) {
            $output .= $docs [self::LABEL_ENDNOTE] . PHP_EOL;
        }

        return $output;
    }

    /**
     * Transforme un url php '&param[]=value' en url pour solr
     * @param string $phpUrl
     * @param array $allowedWt
     * @return array
     */
    static function phpUrl2solrUrl($phpUrl, $allowedWt = [])
    {
        if ($phpUrl == '') {
            return null;
        }

        if (!is_array($phpUrl)) {
            $query = explode('&', htmlspecialchars_decode($phpUrl));
        } else {
            $query = $phpUrl;
        }

        foreach ($query as $param) {
            if ($param != '') {

                $paramsArray = explode('=', $param);
                // on ne gère pas les paramètres comme &foo ou &foo=
                // car pour les urls envoyés à solr les paramètres ont forcément une valeur,
                // sinon c'est une erreur d'URL ou un paramètre inutile
                if ((isset($paramsArray[1])) && ($paramsArray[1] != '')) {
                    $queryStringArray [urldecode($paramsArray[0])] [] = urldecode($paramsArray[1]);
                }
            }
        }

        $queryStringArray = static::cleanParamsForApi($queryStringArray);
        $queryStringArray = static::forceUrlParams($queryStringArray);
        $queryStringArray = static::checkResponseFormat($queryStringArray, $allowedWt);

        if (isset($queryStringArray['fq'][0])) {
            static::HalPluginWordpressHack($queryStringArray['fq'][0]);
        }

        foreach ($queryStringArray as $index => $value) {

            if (is_array($value)) {
                foreach ($value as $v) {
                    $parsedArray [] = $index . '=' . urlencode($v);
                }
            } else {
                $parsedArray [] = $index . '=' . urlencode($value);
            }
        }

        return $parsedArray;
    }

    /**
     *
     * @param string[] $queryStringArray
     * @return string[]:
     */
    static function cleanParamsForApi(array $queryStringArray)
    {
        unset($queryStringArray ['controller']); // Zend
        unset($queryStringArray ['action']); // Zend
        unset($queryStringArray ['module']); // Zend

        unset($queryStringArray ['qt']); // @see
        // https://wiki.apache.org/solr/CoreQueryParameters
        unset($queryStringArray ['debug']); // @see
        // https://cwiki.apache.org/confluence/display/solr/Common+Query+Parameters
        unset($queryStringArray ['explainOther']); // @see
        // https://cwiki.apache.org/confluence/display/solr/Common+Query+Parameters
        unset($queryStringArray ['defType']); // @see
        // https://cwiki.apache.org/confluence/display/solr/Common+Query+Parameters
        // @see https://issues.apache.org/jira/browse/SOLR-2854
        unset($queryStringArray ['stream.file']);
        unset($queryStringArray ['stream.url']);
        unset($queryStringArray ['stream.body']);
        unset($queryStringArray ['stream.contentType']);

        return $queryStringArray;
    }

    /**
     *
     * @param array $queryStringArray
     * @return string[]
     */
    static function forceUrlParams(array $queryStringArray)
    {
        if (isset($queryStringArray ['rows'])) {

            if (is_array($queryStringArray ['rows'])) {
                $rows = $queryStringArray ['rows'] [0];
            } else {
                $rows = $queryStringArray ['rows'];
            }

            if (intval($rows) > static::MAX_ROWS) {
                $queryStringArray ['rows'] = static::MAX_ROWS;
            } else {
                if (ctype_digit($rows)) {
                    $queryStringArray ['rows'] = $rows;
                } else {
                    unset($queryStringArray ['rows']);
                }
            }
        }

        if (isset($queryStringArray ['facet.limit'])) {

            $facet_limit_arr = $queryStringArray ['facet.limit'];
            unset($queryStringArray ['facet.limit']);
            foreach ($facet_limit_arr as $facet_limit) {

                $facet_limit = intval($facet_limit);

                if ($facet_limit > static::MAX_FACETS) {
                    $queryStringArray ['facet.limit'] [] = static::MAX_FACETS;
                } else {
                    $queryStringArray ['facet.limit'] [] = $facet_limit;
                }
            }
        }

        return $queryStringArray;
    }

    /**
     * Valide le format de réponse de Solr
     *
     * @param array $queryStringArray
     * @param array $allowedWt
     * @return array
     */
    static function checkResponseFormat(array $queryStringArray, array $allowedWt = ['json', 'xml'])
    {
        if (!isset($queryStringArray ['wt'])) {
            $queryStringArray ['wt'] = static::DEFAULT_RESPONSE_FORMAT;
        }

        if (is_array($queryStringArray ['wt'])) {
            $wt = $queryStringArray ['wt'] [0];
        } else {
            $wt = $queryStringArray ['wt'];
        }

        if (!in_array($wt, $allowedWt)) {
            $wt = static::DEFAULT_RESPONSE_FORMAT;
        }

        switch ($wt) {

            case self::API_RESPONSE_FORMAT_PDF :
                header('Content-type: application/pdf');
                $queryStringArray ['wt'] = 'phps';
                $queryStringArray ['ccsdTag'] = $wt;
                $queryStringArray ['fl'] = self::LABEL_BIBTEX;
                break;

            case self::API_RESPONSE_FORMAT_BIBTEX :
                header('Content-type: text/plain; charset=UTF-8');
                $queryStringArray ['wt'] = 'phps';
                $queryStringArray ['ccsdTag'] = $wt;
                $queryStringArray ['fl'] = self::LABEL_BIBTEX;
                break;

            case self::API_RESPONSE_FORMAT_RTF :
                header('Content-type: text/plain; charset=UTF-8');
                $queryStringArray ['wt'] = 'phps';
                $queryStringArray ['ccsdTag'] = $wt;
                $queryStringArray ['fl'] = 'docid,halId_s,journalPublisher_s,version_i,docType_s,citationFull_s';
                break;

            case self::API_RESPONSE_FORMAT_ENDNOTE :
                header('Content-type: text/plain; charset=UTF-8');
                header('Content-Disposition: attachment; filename="hal.enw"');
                $queryStringArray ['wt'] = 'phps';
                $queryStringArray ['ccsdTag'] = $wt;
                $queryStringArray ['fl'] = self::LABEL_ENDNOTE;
                break;

            case self::API_RESPONSE_FORMAT_CSV :
                header('Content-type: text/csv; charset=UTF-8');

                if (!isset($queryStringArray ['fl'])) {
                    $queryStringArray ['fl'] = 'docid,halId_s,version_i,docType_s,citationFull_s,citationRef_s';
                }
                $queryStringArray ['wt'] = self::API_RESPONSE_FORMAT_CSV;
                break;

            case self::API_RESPONSE_FORMAT_XML_TEI :
                header('Content-Type: text/xml; charset=utf-8');
                $queryStringArray ['wt'] = 'phps';
                $queryStringArray ['ccsdTag'] = $wt;
                $queryStringArray ['fl'] = self::LABEL_XML_TEI;
                break;

            case self::FEED_FORMAT_RSS :

                header('Content-Type: application/rss+xml; charset=utf-8');
                $queryStringArray ['wt'] = 'phps';
                $queryStringArray ['ccsdTag'] = $wt;
                $queryStringArray ['fl'] = 'title_s,authFullName_s,licence_s,abstract_s,halId_s,uri_s,modifiedDate_tdate,submittedDate_tdate,keyword_s';

                if (!isset($queryStringArray ['sort']) || ($queryStringArray ['sort'] == '')) {
                    $queryStringArray ['sort'] = 'modifiedDate_s desc';
                }

                break;

            case self::FEED_FORMAT_ATOM :
                header('Content-Type: application/atom+xml; charset=utf-8');
                $queryStringArray ['wt'] = 'phps';
                $queryStringArray ['ccsdTag'] = $wt;
                $queryStringArray ['fl'] = 'title_s,authFullName_s,licence_s,abstract_s,halId_s,uri_s,modifiedDate_tdate,submittedDate_tdate,keyword_s';

                if (!isset($queryStringArray ['sort']) || ($queryStringArray ['sort'] == '')) {
                    $queryStringArray ['sort'] = 'modifiedDate_s desc';
                }
                break;

            case self::API_RESPONSE_FORMAT_XML :
                header('Content-Type: text/xml; charset=utf-8');
                $queryStringArray ['wt'] = $wt;
                break;

            case self::API_RESPONSE_FORMAT_JSON :
                header('Content-Type: application/json; charset=utf-8');
                $queryStringArray ['wt'] = $wt;
                break;

            default :
                // force value
                $queryStringArray ['wt'] = self::API_RESPONSE_FORMAT_JSON;
                header('Content-Type: application/json; charset=utf-8');
                break;
        }
        return $queryStringArray;
    }

    /**
     * Hack pour eviter des requêtes erronnées venant d'une ancienne version du plugin HAL wordpress
     * Très spécifique
     * @todo enlever quand sera devenu inutile...
     * @param type $stringQuery
     */
    static function HalPluginWordpressHack($stringQuery)
    {

        if ($stringQuery == 'authIdHal_s:()') {
            header('HTTP/1.0 400 Bad Request', true, 400);
            echo Hal_Search_Solr_Api::formatErrorAsSolr('Syntax Error. See help : /docs', self::API_RESPONSE_FORMAT_JSON);
            exit;
        }

        if ($stringQuery == ':()') {
            header('HTTP/1.0 400 Bad Request', true, 400);
            echo Hal_Search_Solr_Api::formatErrorAsSolr('Syntax Error. See help : /docs', self::API_RESPONSE_FORMAT_JSON);
            exit;
        }
    }

    /**
     * Transforme un url Zend "/param/value" en url pour solr
     * @param string[] $phpUrl
     * @param array $allowedWt
     * @return string
     */
    static function zendUrl2solrUrl($phpUrl, $allowedWt = [])
    {
        if ($phpUrl == '') {
            return null;
        }

        $queryStringArray = $phpUrl;

        $queryStringArray = static::cleanParamsForApi($queryStringArray);
        $queryStringArray = static::forceUrlParams($queryStringArray);
        $queryStringArray = static::checkResponseFormat($queryStringArray, $allowedWt);

        foreach ($queryStringArray as $index => $value) {

            if (count($value) > 2) {
                $parsedArray [] = $index . '=' . implode('&' . $index . '=', urlencode($value));
            } else {
                if (is_array($value)) {
                    foreach ($value as $v) {
                        $parsedArray [] = $index . '=' . urlencode($v);
                    }
                } else {
                    $parsedArray [] = $index . '=' . urlencode($value);
                }
            }
        }
        return $parsedArray;
    }


    /**
     * Retourne les types de documents par instance de portail
     * @param string $instance
     * @param string $lang
     * @param array $languages
     * @param string $wt response format
     * @return string
     * @throws Zend_Exception
     * @throws Zend_Json_Exception
     */
    static function getInstanceDocTypes($instance, $lang, $languages, $wt)
    {


        $xml = "<?xml version='1.0' encoding='UTF-8'?>\n";
        $xml .= "<response>\n";
        $xml .= "\t<lst name='responseHeader'>\n";
        $xml .= "\t<int name='status'>0</int>\n";
        $xml .= "\t<int name='QTime'>0</int>\n";
        $xml .= "\t<lst name='params'>\n";
        $xml .= "\t<bool name='indent'>true</bool>\n";
        $xml .= "\t<str name='instance_s'>" . $instance . "</str>\n";
        if (isset($lang)) {
            $xml .= "\t<str name='lang'>" . $lang . "</str>\n";
        }
        $xml .= "\t<str name='wt'>" . $wt . "</str>\n";
        $xml .= "\t</lst>\n";
        $xml .= "\t</lst>\n";


        $sortie = "";
        if ($instance == 'all') {
            $tableauTypdoc = [];
            foreach (new DirectoryIterator(Hal_Site_Portail::MODULE_PATH) as $portail) {
                $file = $portail->getPathname() . DIRECTORY_SEPARATOR . CONFIG . 'typdoc.json';
                if (is_file($file)) {
                    foreach (Zend_Json::decode(file_get_contents($file)) as $hal_typdoc) {
                        if ($hal_typdoc['type'] == 'category') {
                            foreach ($hal_typdoc['children'] as $typdoc) {
                                $tableauTypdoc[$typdoc['id']] = $typdoc['label'];
                            }
                        } else {
                            $tableauTypdoc[$hal_typdoc['id']] = $hal_typdoc['label'];
                        }
                    }
                }
            }
        } else {
            $tableauTypdoc = static::tableauTypdoc($instance);
        }
        foreach ($tableauTypdoc as $typdoc => $libelle) {
            $sortie .= "<doc>\n";
            $sortie .= "\t<str name='docid'>" . $typdoc . "</str>\n";


            foreach ($languages as $lang) {
                $sortie .= "\t<str name='" . $lang . "_label'>" . Zend_Registry::get('Zend_Translate')->translate($libelle, $lang) . "</str>\n";
            }
            $sortie .= "</doc>\n";
        }

        $xml .= "\t<result name='response' numFound='" . count($tableauTypdoc) . "'>\n";
        $xml .= $sortie;
        $xml .= "\t</result>\n";
        $xml .= "\t</response>";

        if ($wt == self::API_RESPONSE_FORMAT_JSON) {
            return Zend_Json::fromXml($xml);
        } else {
            return $xml;
        }

    }


    /**
     * Retourne un tableau de type de document pour une instance de portail
     * @param string $instance
     * @return array
     */
    static function tableauTypdoc($instance)
    {
        $tableauTypdoc = [];
        foreach (Hal_Settings::getTypdocs($instance) as $hal_typdoc) {
            if ($hal_typdoc['type'] == 'category') {
                foreach ($hal_typdoc['children'] as $typdoc) {
                    $tableauTypdoc[$typdoc['id']] = $typdoc['label'];
                }
            } else {
                $tableauTypdoc[$hal_typdoc['id']] = $hal_typdoc['label'];
            }
        }
        return $tableauTypdoc;
    }

}
