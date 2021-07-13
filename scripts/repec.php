<?php

// Script Quotidien permettant la construction de l'archive au format ReREc si domaine = SHS:ECO, QFIN:% ou SHS:GESTION
$localopts = array(
    'test|t'   => 'Lance le script en mode test (sans tamponnage/détamponnage)',
    'docid|i-s'  => 'lance le script seulement sur le docid',
);
$opts = null;

require_once(__DIR__ . '/loadHalHeader.php');

$test = isset($opts->test);
$do_docid = null;
if (isset($opts->docid)) {
    $do_docid = $opts->docid;
    $found = false;
} else {
    $found = true;
}

define('REPEC_ROOT_FILE', '/data/hal/' . APPLICATION_ENV . '/repec/');
define("REPEC_HANDLE", "RePEc:hal:");

$series = [
    'wpaper'=>"docType_s:(UNDEFINED OR REPORT)",
    'journl'=>'NOT(docType_s:(SOFTWARE OR VIDEO OR MAP OR SON OR IMG OR THESE OR HDR OR MEM OR REPORT OR UNDEFINED))',
    'cesptp'=>"NOT(docType_s:(SOFTWARE OR VIDEO OR MAP OR SON OR IMG OR THESE OR HDR OR MEM)) AND ( structCode_s:(UMR8174 OR UMR8059 OR UMR8095 OR UMR8595 OR UMR8594) OR structName_sci:('laboratoire d''economie publique' OR 'analyse theorique des organisations et des marches'))",
    'gemwpa'=>'docType_s:(UNDEFINED OR REPORT) AND collCode_s:GRENOBLE-EM',
    'gemptp'=>'NOT(docType_s:(SOFTWARE OR VIDEO OR MAP OR SON OR IMG OR THESE OR HDR OR MEM OR REPORT OR UNDEFINED)) AND collCode_s:GRENOBLE-EM',
    'psewpa'=>'docType_s:(UNDEFINED OR REPORT) AND structCode_s:UMR8545',
    'pseose'=>'collCode_s:LABEX-OSE',
    'gmonwp'=>'collCode_s:G-MOND-WORKING-PAPERS',
    'cepnwp'=>'docType_s:(UNDEFINED OR REPORT) AND structCode_s:(UMR7234 OR UMR7115)',
    'ciredw'=>'docType_s:(UNDEFINED OR REPORT) AND collCode_s:CIRED',
    'wpceem'=> 'collCode_s:CEE-M-WP',
];
$query_domain = "level0_domain_s:qfin OR level1_domain_s:(shs.eco OR shs.gestion)";

/* Est-ce utile ??? */
Zend_Registry::set('languages', array('fr','en','es','eu'));
Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));

Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'fr' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'en' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'es' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'eu' ) );

debug('Export RePEc');
debug('Lancement du script ('.date('d/m/Y \à H:i:s').')');


foreach ( $series as $serie=>$where ) {
    $solrRequest = "q=*&fq=status_i:11&fq=" . urlencode($where) . "&fq=" . urlencode($query_domain) . "&rows=1000000&wt=phps&fl=docid&omitHeader=true";
    debug('> Requête SolR : ' . $solrRequest);

    $res = Ccsd_Tools::solrCurl($solrRequest);
    try {
        $res = unserialize($res);
    } catch (Exception $e) {
        error_log("Can't unserialize result of $solrRequest");
        continue;
    }
    if (isset($res['response']['numFound']) && isset($res['response']['docs'])) {
        debug('# de notices à exporter pour la serie "' . $serie . '" : ' . $res['response']['numFound']);
        foreach ($res['response']['docs'] as $d) {
            $docid = $d['docid'];
            if (($do_docid != null) && ($docid != $do_docid)) {
                // on passe...
                continue;
            }
            $found=true;
            $document = Hal_Document::find($d['docid']);
            if ( false === $document ) {
                continue;
            }
            if (make_repec($document, $serie, $series)) {
                debug('OK '.$document->getDocid());
            } else {
                debug('NOK '.$document->getDocid().'-> error in rdf creation !!!');
            }
            if (($do_docid != null) && $found) {
                break 2;
            }
        }
    }
}

debug("Fin du script (".date("d/m/Y H:i:s").")");

exit(0);
/**
 * Flat the array: from on multidimensional array, do a one dimension array
 * Keys are lost
 * @param array $array
 * @return array
 */
function flatten(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}

/**
 * @param $article Hal_Document
 * @return string
 */
function  getRepecMimeType($article) {
    $type = $article->getTypDoc();
    if ($type == 'VIDEO') {
        return "Video/mp4";
    } elseif  ($type == 'IMG') {
        return "Image/jpeg";
    } else {
        return "Application/pdf";
    }
}

/**
 * @param $article Hal_Document
 * @param $handle string
 * @param $series string[]
 * @return bool
 */
function make_repec($article, $handle, $series)
{
    global $test;
    foreach (array_keys($series) as $serie) {
        if ($serie != $handle) {
            // Nettoyage, le document a peut etre change de serie
            $maybeSerieFile = REPEC_ROOT_FILE . "hal/" . $serie . "/" . $article->getId() . ".rdf";
            if (file_exists($maybeSerieFile)) {
                if ($test) {
                    verbose("Unlink $maybeSerieFile");
                } else {
                    unlink($maybeSerieFile);
                }
            }
        }
    }

    $repecInfo = getRepecInfo($article, $handle);
    // open the file
    if ($test) {
        print "$repecInfo";
    } else {
        $destFile = REPEC_ROOT_FILE . "hal/" . $handle . "/" . $article->getId() . ".rdf";
        if (file_exists($destFile)) {
            if ($article->getLastModifiedDate('U') > filemtime($destFile)) {
                unlink($destFile);
            } else {
                debug("OK -> already done");
                return true;
            }
        }
        if (!$fp = fopen($destFile, "w")) {
            // Error: Can't open file
            return false;
        }
        fwrite($fp, $repecInfo);
        fclose($fp);
    }
    return true;
}

/**
 * Database is unclean... some unwanted charset charset
 *     example: the ' of windows (0x92)
 * @param string $text
 * @return string
 */
function cleanText($text) {
    $res = Ccsd_Tools::nl2space(strip_tags($text));
    // Supp du ' windows
    $res = str_replace("ʼ" , "'",  $res);
    $res = str_replace("’" , "'",  $res);
    $res = str_replace("“" , '"'  ,$res);
    $res = str_replace("”" , '"'  ,$res);
    // Ligature
    $res = str_replace("ﬁ"    , "fi" ,$res);
    $res = str_replace("ﬀ"    , "ff" ,$res);
    $res = str_replace("ﬂ"    , "fl" ,$res);
    $res = str_replace("ﬃ"   , "ffi",$res);
    $res = str_replace("ﬄ"   , "ffl",$res);
    // Si en encodage Windows
    $res = str_replace("\xC2\x91" ,"'"    ,$res);
    $res = str_replace("\xC2\x92" ,"'"    ,$res);
    $res = str_replace("\xC2\x93" ,'"'    ,$res);
    $res = str_replace("\xC2\x94" ,'"'    ,$res);
    // On supprime le reste des chars noon utf8
    // $res = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    return $res;
}
/**
 * @param $article Hal_Document
 * @param $handle string
 * @return string
*/
function getRepecInfo($article, $handle) {
	// make the RePEc template document
	$info = "\xEF\xBB\xBF"; // BOM for UTF-8
	$info .= "Template-Type: ReDIF-Paper 1.0".PHP_EOL;
    foreach ($article->getTitle() as $l => $t) {
        $info .= "Title: ".Ccsd_Tools::nl2space(strip_tags($t)).PHP_EOL;
    }
    foreach ($article->getAuthors() as $author) {
        /** @var $author Hal_Document_Author */
        $info .= "Author-Name: ".        $author->getFullname()  .PHP_EOL;
        $info .= "Author-X-Name-First: ".$author->getFirstname() .PHP_EOL;
        $info .= "Author-X-Name-Last: " .$author->getLastname()  .PHP_EOL;
        if ( $author->getEmail() != "" ) {
            $info .= "Author-Email: "   .$author->getEmail()     .PHP_EOL;
        }
        if ( $author->isAffiliated() ) {
            $aff = [];
            foreach ( $author->getStructid() as $s ) {
                $aff[] = (new Ccsd_Referentiels_Structure($s))->__toString();
            }
            if ( count($aff) ) {
                $info .= "Author-Workplace-Name: ".implode(", ", $aff).PHP_EOL;
            }
        }
    }
    foreach ($article->getAbstract() as $l => $t) {
        if ( is_array($t) ) {
            $t = current($t);
        }
        $info .= "Abstract: ".cleanText($t).PHP_EOL;
    }
	$info .= "Creation-Date: ".$article->getProducedDate().PHP_EOL;
    if ( count($kwls = $article->getKeywords()) ) {
        // Construction d'un seul tableau des kw dans toutes les langues
        $info .= "Keywords: ".implode(',', array_unique(flatten($kwls))).PHP_EOL;
    }
	if ( $article->getTypDoc() != 'UNDEFINED' ) {
        $info .= "Publication-Status: Published in " . strip_tags($article->getCitation()) . PHP_EOL;
    }
    $info .= "Note: View the original document on HAL open archive server: ".$article->getUri(true).PHP_EOL;
    if ( $article->getFormat() == Hal_Document::FORMAT_FILE ) {
        $info .= "File-URL: ".$article->getUri(true).$article->getUrlMainFile().PHP_EOL;
        $mimetype = getRepecMimeType($article);
        $info .= "File-Format: $mimetype".PHP_EOL;
    }
    if ( $article->getMeta('doi') ) {
        $info .= "DOI: ".$article->getMeta('doi').PHP_EOL;
    }
	$info .="Handle: ".REPEC_HANDLE.$handle.':'.$article->getId().PHP_EOL;

	return $info;
}
