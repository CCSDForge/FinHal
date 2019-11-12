<?php
/**
 *
 * Ce script permet de récupérer sous forme de CSV les DOI et métadonnées des jeux de données associés au DOI d'un article trouvé dans HAL.
 *
 * @see https://wiki.ccsd.cnrs.fr/wikis/ccsd/index.php/Schollix
 */

require_once __DIR__ . "/../../library/Hal/Script.php";

/**
 * Foo class to put those method and suppress PhpStorm Warning
 * @property string $title
 * @property $target
 * @property stdClass $identifiers
 * @property $name
 */
class SchollixObj extends stdClass {

}

/**
 * Script to get Schollix informations
 * Schollix provide an API to get information on scientifics data objects associated to a publication
 * Returns the DOI of the scientific Data ossociated to the publication with given DOi
 *
 * Result of script is a CSV file containing HAL DOI onject, DOI of associated data, Title of scientific data
 *
 * Display also some statistics
 *
 */
class Schollix extends Hal_Script
{
    protected  $options = array(
        'output|f' => 'Output CSV file',
        'dryrun|t' => 'Testing mode',
        'csv' => 'CSV mode'
    );
    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt)
    {
        /** TODO : Changer la condition DateSubmit car non paramétrable au lancement du script */
            $db = Zend_Db_Table_Abstract::getDefaultAdapter();
            $sql = $db->select()->from('DOC_HASCOPY')
                ->join('DOCUMENT', "DOC_HASCOPY.DOCID = DOCUMENT.DOCID AND CODE = 'doi'")
                ->where("DOCUMENT.DATESUBMIT >= '2015-01-01' AND (DOCUMENT.DOCSTATUS = 11 OR DOCUMENT.DOCSTATUS = 111)");

            $this -> debug("$sql");
            $all = $db->fetchall($sql);

            $this -> verbose("Get " . count($all) . " DOIs\n");
            $nb = 0;
            $nberror=0;
            $total = 0;
            $curl = curl_init();

            if ($getopt->getOption('csv')) {
                $fh = fopen("Scholix.csv", "w");
                fputcsv($fh, ['DOCID','HAL DOI','Data DOI', 'Date','Title', 'Publisher']);
            }

            if ($all == null) {
                $this->verbose("No results");
            } else {
                foreach ($all as $row) {
                    $doi = $row['LOCALID'];
                    $docid = $row['DOCID'];
                    $date = $row['DATECRE'];
                    $this -> debug( "$docid:  $date : $doi  \n");
                    $total++;
                    $path = Hal_ResearchData::BASEURL . $doi;
                    curl_setopt($curl, CURLOPT_URL, $path);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
                    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                    $res = curl_exec($curl);

                    /** @var stdClass $json */
                    $json = json_decode($res);

                    if (!is_object($json) || !property_exists($json, 'result') || !is_array($json->result) || ($json->result == [])) {
                        print( "\r$nb / $total");
                        continue;
                    }

                    if ($getopt->getOption('csv')) {
                        try {
                            $type = Hal_ResearchData::TYPE_DOI;

                            $metaResearchData = Hal_ResearchData::load($doi, $type);

                            $schollixInfo = $metaResearchData->getSchollixInfo($json->result[0]);
                            array_unshift($schollixInfo, $docid, $doi);
                            fputcsv($fh, $schollixInfo);
                            print ("\r$nb / $total");
                            $nb++;
                        } catch (Exception $e) {
                            $this -> displayError("\nCan't retreive info for doi: $doi");
                            $nberror++;
                        }
                    } else {
                        try {
                            $type = Hal_ResearchData::TYPE_DOI;

                            $metaResearchData = Hal_ResearchData::load($doi, $type);

                            $schollixInfo = $metaResearchData->getSchollixInfo($json->result[0]);
                            switch ($metaResearchData->retreiveInfo($schollixInfo, true)) {
                                case Hal_ResearchData::MAJ :
                                    $this->verbose($doi . ' : ADDED');
                                    break;
                                case Hal_ResearchData::SAME :
                                    $this->verbose($doi . ' : No change');
                                    break;
                                case Hal_ResearchData::NOTFOUND:
                                    $this->verbose($doi . ' : NOT Found');
                                    $metaResearchData->delete(); // On nettoie les liens n'ayant plus d'URL valide
                                    break;
                                case Hal_ResearchData::NOUPD:
                                    $this->verbose($doi . ' : Unknow pb');
                                    break;
                            }
                            print ("\r$nb / $total");
                            $nb++;
                        } catch (Exception $e) {
                            $this -> displayError("\nCan't retreive info for doi: $doi");
                            $nberror++;
                        }
                    }
                }
                curl_close($curl);
            }
            if ($getopt->getOption('csv')) {
                fclose($fh);
            }
            $this->verbose("Total errors: $nberror");

    }
}

$script = new Schollix();
$script -> run();