<?php
/**
 * Created by PhpStorm.
 * User: iguay
 * Date: 06/11/18
 * Time: 16:59
 */

require __DIR__ . "/../library/Ccsd/Script.php";
require __DIR__ . "/../library/Hal/Script.php";
// todo nécessaire  a priori non ?
putenv('PORTAIL=hceres');

/**
 * Class ImportHceresScript
 * Insertion des données HCERES
 * - référentiel
 * - données=report
 */
class ImportHceresScript extends Hal_Script
{

    const SITE = 'hceres';

    const HCERESID    = 'HCERESID';
    const IDENTIFIANT = 'IDENTIFIANT';
    const LANGUAGE    = 'language';
    const HCERES_CAMPAGNE_LOCAL = 'hceres_campagne_local';
    const LOCAL_REFERENCE     = 'localReference';
    const HCERES_ENTITE_LOCAL = 'hceres_entite_local';

    //const SPACE = SPACE_DATA.'/'.SPACE_PORTAIL.'/'.self::SITE.'/';

    const REPORT_RECH = 'REPORT_RECH';
    const REPORT_LABO = 'REPORT_LABO';
    const REPORT_MAST = 'REPORT_MAST';
    const REPORT_DOCT = 'REPORT_DOCT';
    const REPORT_LICE = 'REPORT_LICE';
    const REPORT_LPRO = 'REPORT_LPRO';

    const REP_IMPORT = 'import/';

    protected $sid = 5408;

    /**
     */
    protected $options = array(
        'referentiel|ref=s' => 'Fichier référentiel à charger',
        'report|rap=s' => 'Fichier des rapports à charger'
    );

    /**
     * Tableau de correspondance entre les colonnes et les champs du référentiel
     */
    protected $correspRef = [
        0   =>    self::HCERESID,
        1   =>    self::IDENTIFIANT,
        2   =>    self::IDENTIFIANT,
        3   =>    self::IDENTIFIANT,
        4   =>    'NOM',
        5   =>    'NOM_USAGE',
        6   =>    'SIGLE',
        7   =>    'NOM_ALIAS',
        9   =>    'TYPEHCERES', //voir pour l'enum
        10  =>    'STYPEHCERES',
        11  =>    'ADRESSE',
        12  =>    'PAYSID',
        13  =>    'VILLE',
        14  =>    'REGION'
    ];

    /**
     * Tableau de correspondace entre les colonnes et les champs du rapport
     */
    protected $correspReport = [
        0   =>     self::LOCAL_REFERENCE,
        3   =>    'type',
        1   =>    'title',
        21  =>    'abstract',
        20  =>    'keyword',
        8   =>     self::LANGUAGE,
        7   =>    'date',
        //   =>    'hceres_dom_local',
        //   =>    'hceres_domsci_local',
        //   =>    'hceres_domapp_local',
        //   =>    'hceres_erc_local',
        9   => self::HCERES_CAMPAGNE_LOCAL,
        //0   =>    'hceres_entite_local',
        //   =>    'hceres_etabsupport_local',
        //   =>    'hceres_etabassoc_local',
        //   =>    'hceres_cohabilitation_local',
        2   =>    'file'
    ];

    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt)
    {
        /** Environnement Hal generique */
        RuntimeConstDef('', self::SITE);

        $oPortail = Hal_Site::exist(self::SITE);
        if (!is_object($oPortail)) {
            $this->println('', '**************************************', 'red');
            $this->println('', '*  Portail '.self::SITE.' inexistant *', 'red');
            $this->println('', '**************************************', 'red');
            return;
        }
        $this->sid = $oPortail->getSid();
        define ('SITEID', $this->sid);

        // traitement du référentiel
        if ($getopt->referentiel) {
            //Chemin vers le ficher CSV du référentiel des entités évaluées
            //$referentielFile = SPACE . self::REP_IMPORT . 'referentiel.txt';

            $referentielFile = SPACE . self::REP_IMPORT . $getopt->referentiel;

            $this->loadReferential($referentielFile);
        }

        // traitement des rapports
        if ($getopt->report) {
            //Chemin vers le fichier CSV de rapports
            //reportFile = SPACE . self::REP_IMPORT . 'rapportshceres.txt';
            $reportFile = SPACE . self::REP_IMPORT . $getopt->report;

            //Compte propriétaire des dépôts
            $uid = 1;  //'ybarborini'

            //Chemin vers le répertoire contenant les fichiers des rapports
            $reportDir = SPACE . self::REP_IMPORT . 'pdf/';

            $this->loadReport($reportFile, $this->sid, $uid, $reportDir);
        }
        $this->verbose("******************\n");
        $this->verbose("*  Fin du script *\n");
        $this->verbose("******************\n");
    }

    /**
     * @param string $csvFile
     */
    private function loadReferential(string $csvFile)
    {
        if (file_exists($csvFile)) {
            $src = file_get_contents($csvFile);

            foreach (explode("\r", $src) as $rowNb => $row) {
                if ($rowNb == 0) {
                    continue;
                }
                $data = explode("\t", mb_convert_encoding($row,'utf-8','utf-16'));
                if (trim($data[0]) == '') {
                    continue;
                }
                $this->insertDataReferential($data);
            }
        } else {
            $this->println('', '************************************************', 'red');
            $this->println('', '*  Fichier référentiel '.$csvFile.' inexistant *', 'red');
            $this->println('', '************************************************', 'red');
        }
    }

    /**
     * Ajout des données du référentiel
     *
     * @param array                    $data    : données à insérer
     */
    private function insertDataReferential(array $data)
    {
        $entity = [];
        foreach ($this->correspRef as $column => $field) {
            if (isset($data[$column])) {
                if ($column == 0) { //hceresid
                    $data[$column] = intval($data[$column]);
                } else if ($column == 12) {
                    $data[$column] = strtolower(substr(trim($data[$column]), 0, 2 ));
                }
                if (!isset($entity[$field]) || trim($entity[$field]) == '') {
                    $entity[$field] = trim($data[$column]);
                }
            }
        }

        /** echo '"' . $entity['type'] . '"';*/
        $this->verbose($entity['type']."\n");

        return;

        /**
        $entity['VALID'] = 'VALID';
        $db = Zend_Db_Table::getDefaultAdapter();
        try {
            if ($entity[self::HCERESID]!= '' && $db->insert('REF_HCERES_NEW', $entity)) {
                $result = ' OK';
            } else {
                $result = ' NOK';
            }
        } catch(Exception $e) {
            $result = ' NOK';
            var_dump($entity);
        }
        $this->println('', 'Insertion de la donnée ' . $entity[self::HCERESID] . ': ' . $result, blue);
        */
    }


    /**
     * @param string $csvFile
     * @param int $sid
     * @param int $uid
     */
    function loadReport(string $csvFile, int $sid, int $uid, string $reportDir)
    {
        if(file_exists($csvFile)) {

            $src = file_get_contents($csvFile);

            $crlf = "\r\n";
            if (!strpos($src, $crlf . $crlf)) {
               $crlf = "\n";
            }

            foreach (explode($crlf, $src) as $rowNb => $row) {
                if ($rowNb == 0) {
                    continue;
                }

                $data = explode("\t", mb_convert_encoding($row,'utf-8','utf-16'));
                if (trim($data[0]) == '') {
                    continue;
                }
                $this->insertReport($data, $sid, $uid, $reportDir);
            }
        } else {
            $this->println('', '************************************************', 'red');
            $this->println('', '*  Fichier rapport'.$csvFile.' inexistant *', 'red');
            $this->println('', '************************************************', 'red');
        }
    }

    /**
     * @param array $data
     * @param int $sid
     * @param int $uid
     *
     * @return bool
     */
    function insertReport(array $data, int $sid, int $uid, string $reportDir)
    {
        $report = [];
        foreach ($this->correspReport as $column => $field) {
            if (isset($data[$column]) && (!isset($report[$field]) || trim($report[$field]) == '')) {
                $report[$field] = preg_replace('/\s\s+/', '', trim($data[$column]));
            }
        }
        if (! isset($report['type']) || $report['type'] == '') {
            return false;
        }
        $report[self::LANGUAGE] = strtolower($report[self::LANGUAGE]);
        list($day, $month, $year) = explode('/', $report['date']);
        $report['date'] = $year . '-' . $month . '-' . $day;
        $report[self::LANGUAGE] = strtolower($report[self::LANGUAGE]);
        $report[self::HCERES_CAMPAGNE_LOCAL] = substr($report[self::HCERES_CAMPAGNE_LOCAL], 0, 5);

        if ($report['type'] == 'RETAB') {
            $report['type'] = 'REPORT_ETAB';
        }

        $this->verbose($report[self::LOCAL_REFERENCE]."\n");

        //todo voir pour supprimer les domaines
        $report['domain'] = ['shs'];
        //todo modifier l'id de l'établissement
        //$report['hceres_entite_local'] = new Ccsd_Referentiels_Hceres(1);
        $report[self::HCERES_ENTITE_LOCAL] = 1;

        if ($report['file'] != '' && is_file($reportDir . $report['file'])) {
            $report['file'] = $reportDir . $report['file'];
        } else {
            $this->println('', '************************************************', 'red');
            $this->println('', '*  Rapport '.$reportDir . $report['file'].' non présent *', 'red');
            $this->println('', '************************************************', 'red');
            return false;
        }
        $document = $this->createHalDocument($sid, $uid, $report['type']);

        $this->addMetaHalDocument($document, $report);

        $this->addFileHalDocument($document, $report['file']);

        $this->addAuthorHalDocument($document);

        try {
            Hal_Document_Validity::isValid($document);
        } catch(Exception $e) {
            $this->println('', " | META NOK | ". serialize($e->getMessage()), 'red');
            return false;
        }
        $document->setTypeSubmit(Hal_Settings::SUBMIT_INIT);
        $docid = $document->save(1, false);
        if ($docid == 0 ) {
            $this->println('', " | INSERT NOK ", 'red');
        } else {
            $this->verbose(" | " . $document->getId() ."\n");


        }
        return true;
    }

    /**
     * Création d'un document
     * @param $sid
     * @param $uid
     * @param $typdoc
     * @return Hal_Document
     */
    private function createHalDocument($sid, $uid, $typdoc)
    {
        $document = new Hal_Document();
        $document->setTypdoc($typdoc);

        $document->setSid($sid);
        $document->setContributorId($uid);
        $document->setInputType(Hal_Settings::SUBMIT_ORIGIN_SWORD);

        return $document;
    }

    /**
     * Ajout des métadonnées au document
     * @param Hal_Document $document
     * @param array $report
     * @return Hal_Document
     */
    private function addMetaHalDocument(Hal_Document $document, array $report)
    {
        $type = $report['type'];
        $docMeta = [];
        foreach ($report as $meta => $value) {
             switch ($meta) {
                 case self::HCERES_ENTITE_LOCAL :
                     $docMeta[$meta] = new Ccsd_Referentiels_Hceres($value);
                     break;
                 case 'title':
                 case 'abstract':
                     //Par défaut le titre et le résumé sont en français
                     $docMeta[$meta]['fr'] = $value;
                     break;
                 case self::LOCAL_REFERENCE :
                     $docMeta[$meta][] = $value;
                     break;
                 case'keyword' :
                     $docMeta[$meta]['fr'] = explode(',', $value);
                     break;
                 case self::LANGUAGE:
                 case 'date':
                 case self::HCERES_CAMPAGNE_LOCAL:
                 case 'domain':
                     if ($meta == 'date') {
                         $value = str_replace('/', '-', $value);
                     }
                     $docMeta[$meta] = $value;
                     break;
                 case 'hceres_dom_local':
                     if (in_array($type, [self::REPORT_RECH, self::REPORT_LABO, self::REPORT_MAST, self::REPORT_DOCT])) {
                         $docMeta[$meta] = explode(',', $value);
                     }
                     break;
                 case 'hceres_domsci_local':
                     if (in_array($type, [self::REPORT_LABO, self::REPORT_DOCT])) {
                         $docMeta[$meta] = $value;
                     }
                     break;
                 case 'hceres_domapp_local':
                     if (in_array($type, [self::REPORT_LABO, self::REPORT_LICE, self::REPORT_LPRO, self::REPORT_MAST])) {
                         $docMeta[$meta] = $value;
                     }
                     break;
                 case 'hceres_erc_local':
                     if (in_array($type, [self::REPORT_LABO])) {
                         $docMeta[$meta] = explode(',', $value);
                     }
                     break;
                 case 'hceres_etabsupport_local':
                     if (in_array($type, [self::REPORT_RECH, self::REPORT_LABO, self::REPORT_LICE, self::REPORT_LPRO, self::REPORT_MAST, self::REPORT_DOCT])) {
                         $docMeta[$meta] = new Ccsd_Referentiels_Hceres($value);
                     }
                     break;
                 case 'hceres_etabassoc_local':
                     if (in_array($type, [self::REPORT_RECH, self::REPORT_LABO, self::REPORT_DOCT])) {
                         $docMeta[$meta] = explode(',', $value);
                     }
                     break;
                 case 'hceres_cohabilitation_local':
                     if (in_array($type, [self::REPORT_LICE])) {
                         $docMeta[$meta] = explode(',', $value);
                     }
                     break;
                 case 'file':
                 case 'type':
                     continue 2;
                 default:
                     break;
             }
        }

        $document->setMetas($docMeta);
        return $document;
    }

    /**
     * Ajout d'un fichier à un document
     * @param Hal_Document $document
     * @param string $filepath
     * @return Hal_Document
     */
    private function addFileHalDocument(Hal_Document $document, string $filepath)
    {
        $file = new Hal_Document_File();
        $file->setType('file');
        $file->setOrigin('author');
        $file->setDefault(1);
        $file->setName(basename($filepath));
        $file->setPath($filepath);
        $file->setSize(filesize($filepath));

        $document->setFiles([$file]);
        $document->getFiles()[0]->setDefault(true);

        return $document;
    }


    private function addAuthorHalDocument(Hal_Document $document)
    {
        //todo à revoir pour ne pas avoir ce pb (supprimer les contrôles)
        $author = new Hal_Document_Author();
        $author->setFirstname('Hceres');
        $author->setLastname('Hceres');
        $document->addAuthor($author);

        return $document;
    }

}

$script = new ImportHceresScript();
$script->run();