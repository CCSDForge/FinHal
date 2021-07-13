<?php
/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen MALLA OSMAN
 * Date: 14/11/2016
 * Time: 16:30
 * =============================================================================================================
 */

/**
 * =============================================== DESCRIPTION ==================================================
 * The following script is used to export HAL docs to Europe PMC server
 * INPUT  = two dates following the format (YYYY-MM-DD) - set by default for last month
 * OUTPUT = XML or ZIP(XML) file : ../script/europePMC_folder/
 * The Script get Hal docs with Pubmed_Id and Fulltext
 * IT create an XML file that contain the values returned by a solr request (See below)
 * The script has two mandatory variables (beginDate, endDate) that allow us to get the results of specific dates
 * Finally, the script transfers the created xml file to Europe PMC server using an FTP connexion
 * ==============================================================================================================
 */

// ================================================== SCRIPT ====================================================

//<editor-fold desc="Constants and parameters">

// Define the path into xml files folder
define("EUROPEPMC_ROOT_FILE",__DIR__."/europePMC_folder/");

// Define the time zone
date_default_timezone_set("Europe/Paris");

// Get the user input (CLN)
$localopts = array(
    'beginDate|s-s' => 'Date de début (défault : premier jour du mois précédent)',
    'endDate|f-s'   => 'Date de fin (défault : dernier jour du mois précédent)',
    'noTransfer|t'  => 'Ne pas transférer le fichier au serveur EuropePMC (défault : tranférer)',
    'zipXML|z'      => 'Zipper le ficheir XML (défault : ne pas zipper)'
);

// Define the HAL header
require_once(__DIR__ . "/loadHalHeader.php");

// Define the interval of date by default (last month)
$month_ini = new DateTime("first day of last month");
$month_end = new DateTime("last day of last month");

// Processing the user input
$beginDate  = isset($opts->beginDate)   ? $opts->beginDate  : $month_ini->format('Y-m-d');
$endDate    = isset($opts->endDate)     ? $opts->endDate    : $month_end->format('Y-m-d');
$noTransfer = isset($opts->noTransfer);
$zipXML     = isset($opts->zipXML);

// Allow debug in case of verbose | in CLI : (-v) = (-d)
if ($verbose) {
    $debug = true;
}

// Check if the dates are valid and follow the correct format
if(!validateDate($beginDate) || !validateDate($endDate)) {
    if($debug) {
        debug('', '??? RE-ENTER A VALID DATES RESPECTING THE FOLLOWING FORMAT (YYYY-MM-DD)', 'red');
    }
    exit;
}
//</editor-fold>

//<editor-fold desc="Processing - solr request, create DOM elements, xml file and transfer to europePMC">
// Get the date of today
$today = date("Y-m-d_H-i-s");

// Debug
if ($debug) {
    debug('', '>>> XML Export to EuropePMC [date = " . $today . "]', 'blue');
}
// Solr request
$solrRequest = "q=*&
                fq=status_i:11&
                fq=submitType_s:file&
                fq=pubmedId_s:*&
                fq=submittedDate_tdate:" . urlencode("[".$beginDate."T00:00:00Z TO ".$endDate."T23:59:59Z]") . "&
                wt=phps&
                fl=title_s&
                fl=uri_s&
                fl=pubmedId_s&
                rows=10000&
                sort=".urlencode('submittedDate_s asc');

// Delete spaces from solr request
$solrRequest = preg_replace('/\s+/', '', $solrRequest);

// PHP Unserialize of solr request
try {
    $res = unserialize(Ccsd_Tools::solrCurl($solrRequest));
    if ($debug) {
        debug('>>> Solr Request : get [title, url, pubmed_id] of Hal documents that have a pubmed_id and fulltext during a defined date interval');
    }
} catch (Exception $e) {
    if ($debug) {
        debug('', '??? CANNOT PERFORM THE SOLR REQUEST', 'red');
    }
    exit;
}

// If there are results
if (isset($res["response"]["numFound"]) && $res["response"]["numFound"] > 0) {

    // Debug
    if ($debug) {
        debug('>>> HAL documents [numFound = " . $res["response"]["numFound"] . "]');
        debug('>>> Creating DOM elements');
    }

    // Create DOM document
    $doc = new DomDocument("1.0", "utf-8");
    // Set properties of this DOM document
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;
    // Create comment to precise the interval of dates
    $doc->appendChild($doc->createComment("Getting HAL docs from ".$beginDate."T00:00:00 To ".$endDate."T23:59:59"));
    // Create the element Links
    $links = $doc->createElement("links");
    // Loop on all element retrieved by solr request
    foreach ($res["response"]["docs"] as $d) {
        // Create the element link
        $link = $doc->createElement("link");
        // Set the attribut providerId of the link element to 1331
        $link->setAttribute("providerId", "1331");
        // Create the element resource
        $resource = $doc->createElement("resource");
        // Create the element title
        $title = $doc->createElement("title");
        // Create the element url
        $url = $doc->createElement("url");
        // Create the element record
        $record = $doc->createElement("record");
        // Create the element source
        $source = $doc->createElement("source");
        // Create the element id
        $id = $doc->createElement("id");
        foreach ($d["title_s"] as $str) {
            // Set the value of the element title
            $titleText = $doc->createTextNode($str);
            $title->appendChild($titleText);
            $resource->appendChild($title);
        }
        // Set the value of the element title
        $urlText = $doc->createTextNode($d["uri_s"]);
        $url->appendChild($urlText);
        $resource->appendChild($url);
        // Set the value of the element source
        $sourceText = $doc->createTextNode("MED");
        $source->appendChild($sourceText);
        $record->appendChild($source);
        // Set the value of the element id
        $idText = $doc->createTextNode($d["pubmedId_s"]);
        $id->appendChild($idText);
        $record->appendChild($id);
        $link->appendChild($resource);
        $link->appendChild($record);
        $links->appendChild($link);
    }
    $doc->appendChild($links);
    $xml_string = $doc->saveXML();

    // Create the folder that will contain XML file, if it doesn't exist
    if (!file_exists(EUROPEPMC_ROOT_FILE)) {
        mkdir(EUROPEPMC_ROOT_FILE);
    }
    // Debug
    if ($debug) {
        debug('>>> Creating/Updating europePMC_folder');
    }
    // Create and save the XML File
    $fileName = EUROPEPMC_ROOT_FILE."hal_".$today.".xml";
    if ( $file = fopen($fileName, "w") ) {
        // Debug
        if ($debug) {
            debug('>>> Opening XML file : ' . $fileName);
        }
        fputs($file,$xml_string);
        //Debug
        if ($debug) {
            debug('>>> Writing XML data');
        }
        // Close XML file
        fclose($file);
        // Debug
        if ($debug) {
            debug('>>> Closing XML file');
        }
    } else {
        if ($debug) {
            debug('', '??? CANNOT OPEN THE XML FILE : '. $fileName, 'red');
        }
        exit;
    }

    // Zipping XML file or not
    if ($zipXML) {
        $fileName = EUROPEPMC_ROOT_FILE."hal_".$today.".zip";
        $zip = new ZipArchive();
        if($zip->open($fileName, ZipArchive::CREATE))
        {
            $zip->addFile(EUROPEPMC_ROOT_FILE."hal_".$today.".xml","hal_".$today.".xml");
            $zip->close();
            $uploadFile = "/" . EUROPEPMC_FOLDER . "/hal_".$today.".zip";
            if ($debug) {
                debug('>>> Zipping XML file');
            }
        } else {
            if ($debug) {
                debug('', '??? CANNOT ZIP THE XML FILE', 'red');
            }
            exit;
        }
    } else {
        $uploadFile = "/" . EUROPEPMC_FOLDER . "/hal_".$today.".xml";
    }

    if(!$noTransfer) {
        // Connecting at EUROPE PMC Server
        if($ftpStream = @ftp_connect(EUROPEPMC_HOST)) {
            // Debug
            if ($debug) {
                debug('>>> Connecting at EUROPE PMC FTP Server');
            }
        } else {
            // Debug
            if ($debug) {
                debug('', '??? CANNOT CONNECT TO ' . EUROPEPMC_HOST . ' (CHECK HOST NAME !)', 'red');
            }
            exit;
        }
        // Opening the session
        if ($result = @ftp_login($ftpStream, EUROPEPMC_USER, EUROPEPMC_PWD)) {
            // Debug
            if ($debug) {
                debug('>>> Connected as ' . EUROPEPMC_USER . '@' . EUROPEPMC_HOST);
            }
        } else {
            // Debug
            if ($debug) {
                debug('', '??? CANNOT CONNECT AS ' . EUROPEPMC_USER . ' (CHECK USERNAME AND/OR PASSWORD !)', 'red');
            }
            exit;
        }
        // Turn on passive mode transfers
        @ftp_pasv($ftpStream, true);

        // Upload File
        if(@!ftp_put($ftpStream, $uploadFile, $fileName, FTP_BINARY)) {
            // Debug
            if ($debug) {
                debug('', '??? CANNOT UPLOAD THE FILE !', 'red');
            }
            exit;
        } else {
            // Debug
            if ($debug) {
                debug('>>> File uploaded successfully');
            }
        }
        //Closing the session
        @ftp_close($ftpStream);
        if ($debug) {
            debug('>>> Closing FTP connection');
        }
    }
    // Debug
    if ($debug) {
        debug('', '>>> All Process Finished Successfully', 'blue');
    }
} else {
    // Debug
    if ($debug) {
        debug('', '??? THERE ARE NO MATCHING RESULTS [numFound = ' . $res["response"]["numFound"] . '] (CHECK YOUR DATES !)', 'red');
    }
}
//</editor-fold>

//<editor-fold desc="Function : validate Date Format">
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}
//</editor-fold>
