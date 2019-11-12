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
if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . "/../library/Hal/Script.php";

// Define the time zone
date_default_timezone_set("Europe/Paris");

if (4 < 1+1) {
    /** @see file ../config/pwd.json */
    define('EUROPEPMC_FOLDER', 'euroPmc'); // relead defined in pwd.json: EUROPEPMC section
    define('EUROPEPMC_HOST', 'europmc.uk');
    define('EUROPEPMC_USER', 'pmcuser');
    define('EUROPEPMC_PWD', 'theEuroPMCpwd');
}

/**
 * Class EuroPmc
 */
class EuroPmc extends Hal_Script {
    /* Hal_script car besoin des PATH applicatif ! */

    protected $options = array(
        'beginDate|s-s' => 'Date de début (défault : premier jour du mois précédent)',
        'endDate|f-s'   => 'Date de fin (défault : dernier jour du mois précédent)',
        'noTransfer|t'  => 'Ne pas transférer le fichier au serveur EuropePMC (défault : tranférer)',
        'zipXML|z'      => 'Zipper le fichier XML (défault : ne pas zipper)'
    );

    /**
     * @param string $date
     * @param string $format
     * @return bool
     */
    function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    /**
     * @param $zipName
     * @param $files
     * @return string
     * @throws Exception
     */
    private function createZip($zipName, $files) {
        $zip = new ZipArchive();
        if ($zip->open($zipName, ZipArchive::CREATE)) {
            foreach ($files as $localFilePath => $remoteFileName) {
                $zip->addFile($localFilePath, $remoteFileName);
            }
            $zip->close();
            return $zipName;
        } else {
            throw new Exception("CANNOT ZIP THE XML FILE: $zipName");
        }
    }

    /**
     * @param string $uploadFile
     * @param string $localFilePath
     * @throws Exception
     */
    private function transfert2PMC($uploadFile,$localFilePath) {
        // Connecting at EUROPE PMC Server
        $this->debug('>>> Connecting at EUROPE PMC FTP Server');
        if (!($ftpStream = @ftp_connect(EUROPEPMC_HOST))) {
            throw new Exception('??? CANNOT CONNECT TO ' . EUROPEPMC_HOST . ' (CHECK HOST NAME !)');
        }
        // Opening the session
        $this->debug('>>> Connected as ' . EUROPEPMC_USER . '@' . EUROPEPMC_HOST);
        if (!($result = @ftp_login($ftpStream, EUROPEPMC_USER, EUROPEPMC_PWD))) {
            $this->displayError('??? CANNOT CONNECT AS ' . EUROPEPMC_USER . ' (CHECK USERNAME AND/OR PASSWORD !)');
            exit(1);
        }
        // Turn on passive mode transfers
        @ftp_pasv($ftpStream, true);

        // Upload File
        if (@!ftp_put($ftpStream, $uploadFile, $localFilePath, FTP_BINARY)) {
            $this->displayError('??? CANNOT UPLOAD THE FILE !');
            exit(1);
        }
        $this->debug('>>> File uploaded successfully');

        //Closing the session
        $this->debug('>>> Closing FTP connection');
        @ftp_close($ftpStream);
    }

    /**
     * @param Zend_Console_Getopt $opts
     * @throws Exception
     */
    public function main($opts)
    {
        // Define the interval of date by default (last month)
        $month_ini = new DateTime("first day of last month");
        $month_end = new DateTime("last day of last month");

        $this->need_user('apache');
        // Processing the user input

        $beginDate  = $opts->getOption('beginDate');
        if (!$beginDate) {
            $beginDate = $month_ini->format('Y-m-d');
        }
        $endDate    = $opts->getOption('endDate');
        if (!$endDate) {
            $endDate   = $month_end->format('Y-m-d');
        }

        $noTransfer = isset($opts->noTransfer);
        $zipXML     = isset($opts->zipXML);

        // Define the path into xml files folder
        $EUROPEPMC_ROOT_DIR=SPACE_DATA . "/europePMC_folder";

        // Check if the dates are valid and follow the correct format
        if (!$this->validateDate($beginDate) || !$this->validateDate($endDate)) {
            $this->displayError('??? RE-ENTER A VALID DATES RESPECTING THE FOLLOWING FORMAT (YYYY-MM-DD)');
            exit(1);
        }
        // Get the date of today
        $today = date("Y-m-d_H-i-s");

        $this->debug('>>> XML Export to EuropePMC [date = " . $today . "]');

        // Solr request
        $solrRequest = "q=*&
                fq=status_i:11&
                fq=submitType_s:file&
                fq=pubmedId_s:*&
                fq=submittedDate_tdate:" . urlencode("[" . $beginDate . "T00:00:00Z TO " . $endDate . "T23:59:59Z]") . "&
                wt=phps&
                fl=title_s&
                fl=uri_s&
                fl=pubmedId_s&
                rows=10000&
                sort=" . urlencode('submittedDate_s asc');

        // Delete spaces from solr request
        $solrRequest = preg_replace('/\s+/', '', $solrRequest);

        // Create the folder that will contain XML file, if it doesn't exist
        if (!file_exists($EUROPEPMC_ROOT_DIR)) {
            if (!mkdir($EUROPEPMC_ROOT_DIR)) {
                $this->displayError("Can't mkdir: $EUROPEPMC_ROOT_DIR");
                exit(1);
            }
        }

        $localFileName = "hal_$today";
        $localFilePath = "$EUROPEPMC_ROOT_DIR/$localFileName.xml";
        $zipFilePath   = "$EUROPEPMC_ROOT_DIR/$localFileName.zip";

        if ($file = fopen($localFilePath, "w")) {
            $this->debug('>>> Opening XML file : ' . $localFilePath);
        } else {
            $this->displayError('??? CANNOT OPEN THE XML FILE : ' . $localFilePath);
            exit(1);
        }


        // PHP Unserialize of solr request
        try {
            $this->debug('>>> Solr Request : get [title, url, pubmed_id] of Hal documents that have a pubmed_id and fulltext during a defined date interval');
            $this->debug("Solr request use: $solrRequest");
            $res = unserialize(Ccsd_Tools::solrCurl($solrRequest));
        } catch (Exception $e) {
            $this->println('??? CANNOT PERFORM THE SOLR REQUEST');
            $this->println($e->getMessage());
            exit;
        }

        // If there are results
        $nbdocs = $res["response"]["numFound"];
        if (! $nbdocs ||  ($nbdocs == 0)) {
            $this->debug('??? THERE ARE NO MATCHING RESULTS (CHECK YOUR DATES !)');
            exit(0);
        }

        $this->debug(">>> HAL documents [numFound = $nbdocs]");
        $this->debug('>>> Creating DOM elements');

        // Create DOM document
        $doc = new DomDocument("1.0", "utf-8");
        // Set properties of this DOM document
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        // Create comment to precise the interval of dates
        $doc->appendChild($doc->createComment("Getting HAL docs from " . $beginDate . "T00:00:00 To " . $endDate . "T23:59:59"));
        // Create the element Links
        $links = $doc->createElement("links");
        // Loop on all element retrieved by solr request
        foreach ($res["response"]["docs"] as $d) {
            $link = $doc->createElement("link");
            // Set the attribut providerId of the link element to 1331
            $link->setAttribute("providerId", "1331");
            $resource = $doc->createElement("resource");
            $title = $doc->createElement("title");
            $url = $doc->createElement("url");
            $record = $doc->createElement("record");
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
        $this->debug('>>> Creating/Updating europePMC_folder');

        // Create and save the XML File
        $this->debug('>>> Writing XML data');
        fputs($file, $xml_string);
        $this->debug('>>> Closing XML file');
        fclose($file);

        $uploadFileRad = "/" . EUROPEPMC_FOLDER . "/hal_$today";
        $uploadFile = "$uploadFileRad.xml";

        // Zipping XML file or not
        if ($zipXML) {
            try {
                $this->createZip($zipFilePath, [ $localFilePath => "$localFileName.xml" ] );
                $uploadFile = "$uploadFileRad.zip";
            } catch (Exception $e) {
                $this->displayError($e->getMessage());
                exit(1);
            }
        }
        // $uploadFile is either the xml or the zip file now
        if (!$noTransfer) {
            try {
                $this->transfert2PMC($uploadFile, $localFilePath);
            } catch (Exception $e) {
                $this->displayError($e->getMessage());
                exit(1);
            }
        }
        // Debug
        $this->debug('>>> All Process Finished Successfully');
    }
}


$script = new EuroPmc();
$script->run();