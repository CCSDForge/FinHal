<?php
/**
 *
 * Ce script permet de récupérer sous forme de CSV les DOI et métadonnées des jeux de données associés au DOI d'un article trouvé dans HAL.
 *
 * @see https://wiki.ccsd.cnrs.fr/wikis/ccsd/index.php/Schollix
 */

require_once __DIR__ . "/../library/Hal/Script.php";

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
    const BASEURL = 'https://api-dliservice-prototype-dli.d4science.org/v1/linksFromPid?pid=';
    protected  $localopts = array(
        'output|f' => 'Output CSV file',
        'dryrun|t' => 'Testing mode',
    );
    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt)
    {
        $fh = fopen("Scholix.csv", "w");
        fputcsv($fh, ['DOCID','HAL DOI','Data DOI','Title']);
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from('DOC_HASCOPY')
            ->join('DOCUMENT', "DOC_HASCOPY.DOCID = DOCUMENT.DOCID AND CODE = 'doi'")
            ->where("DOC_HASCOPY.DATECRE > '2016-01-01'");

        $this -> debug("$sql");
        $all = $db->fetchall($sql);

        $this -> verbose("Get " . count($all) . " DOIs\n");
        $nb = 0;
        $nberror=0;
        $total = 0;
        $curl = curl_init();
        foreach ($all as $row) {
            $doi = $row['LOCALID'];
            $docid = $row['DOCID'];
            $date = $row['DATECRE'];
            $this -> debug( "$docid:  $date : $doi  \n");
            $total++;
            $path = self::BASEURL . $doi;
            curl_setopt($curl, CURLOPT_URL, $path);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            $res = curl_exec($curl);

            /** @var stdClass[] $json */
            $json = json_decode($res);
            if (!is_array($json) || ($json == [])) {
                print( "\r$nb / $total");
                continue;
            }
            try {
                $csvInfo = $this->getSchollixInfo($json[0]);
                array_unshift($csvInfo, $docid, $doi);
                fputcsv($fh, $csvInfo);
                print ("\r$nb / $total");
                $nb++;
            } catch (Exception $e) {
                $this -> displayError("\nCan't retreive info for doi: $doi");
                $nberror++;
            }
        }
        curl_close($curl);
        fclose($fh);
        $this->verbose("Total errors: $nberror");
    }
    /**
     * @param stdClass $json : un tableau correspondant au json rendu par l'API LE champs identifiers untiquement
     * @return string[]  couple (doi, autre identifiant)
     */
    private function getDoi($json) {
        $doi='';
        $anotherIdent='';
        foreach ($json as $typedIdent) {
            if ($typedIdent -> schema  == 'doi') {
                $doi = $typedIdent -> identifier;
                continue;
            }
            if ($typedIdent -> schema  == 'dnetIdentifier') {
                continue;
            }
            $anotherIdent = $typedIdent -> schema . ':' . $typedIdent -> identifier;
        }
        return [$doi, $anotherIdent];
    }
    /**
     * @param stdClass $publishers : un tableau correspondant au json rendu par l'API LE champs identifiers untiquement
     * @return string[]
     */
    private function getPublishers($publishers) {
        $res = [];
        foreach ($publishers as $publisher) {
            $res [] = $publisher->name;
        }
        return $res;
    }
    /**
     * @param string $json : un tableau correspondant au json rendu par l'API LE champs title untiquement
     * @return string
     */
    private function getTitle($json) {
        return($json);
    }
    /**
     * @param stdClass  $json:  tableau de retour de l'API
     * @return array
     */
    private function getSchollixInfo($json) {
        $target = $json -> target;
        list($dataDoi, $otherId) = $this -> getDoi($target -> identifiers);
        $title = $this -> getTitle($target -> title);
        $publishersName = (isset($target->publisher))  ? $this -> getPublishers($target->publisher) : [];
        $res = [ $dataDoi,$otherId, $title ];
        return array_merge($res, $publishersName);
    }
}

$script = new Schollix();
$script -> run();