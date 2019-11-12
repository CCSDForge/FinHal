<?php
/**
 * Created by PhpStorm.
 * User: iguay
 * Date: 06/11/18
 * Time: 16:59
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . "/../library/Hal/Script.php";

// todo nécessaire  a priori non ?
putenv('PORTAIL=hceres');

/**
 * Class ImportHceresScript
 * Insertion des données HCERES
 * - données du référentiel
 * - données des rapports
 */
class ImportHceresScript extends Hal_Script
{

    const SITE = 'hceres';

    const HCERESID    = 'HCERESID';
    const IDENTIFIANT = 'IDENTIFIANT';
    const CODE_UAI    = 'CODE_UAI';
    const CODE_RNSR   = 'CODE_RNSR';
    const CODE_ACCREDITATION = 'CODE_ACCREDITATION';
    const LANGUAGE    = 'language';
    const HCERES_CAMPAGNE_LOCAL = 'hceres_campagne_local';
    const LOCAL_REFERENCE     = 'localReference';
    const HCERES_ENTITE_LOCAL = 'hceres_entite_local';
    const NEW_HCERESID = 'NEW_HCERESID';
    const TITLE = 'title';
    const HCERES_ETABSUPPORT_LOCAL = 'hceres_etabsupport_local';
    const HCERES_FORMEVAL_LOCAL = 'hceres_formeval_local';
    const HCERES_DOM_LOCAL = 'hceres_dom_local';
    const HCERES_DOMSCI_LOCAL = 'hceres_domsci_local';
    const HCERES_ERC_LOCAL = 'hceres_erc_local';
    const HCERES_TEAM_LOCAL = 'hceres_team_local';
    const HCERES_MENTION_LOCAL = 'hceres_mention_local';
    const HCERES_SPECIALITIES_LOCAL = 'hceres_specialities_local';
    const HCERES_DIPLOME_LOCAL = 'hceres_diplome_local';
    const HCERES_DOMFORM_LOCAL = 'hceres_domform_local';
    const HCERES_LABO_LOCAL = 'hceres_labo_local';
    const KEYWORD = 'keyword';
    const ABSTRACT1 = 'abstract';

    const REPORT_RECH = 'REPORT_RECH';
    const REPORT_LABO = 'REPORT_LABO';
    const REPORT_MAST = 'REPORT_MAST';
    const REPORT_DOCT = 'REPORT_DOCT';
    const REPORT_LICE = 'REPORT_LICE';
    const REPORT_LPRO = 'REPORT_LPRO';

    const REP_IMPORT = 'import/';
    const INEXISTANT_LC = ' inexistant *';
    const C = '************************************************';

    protected $sid = 5408; /* SID du portail de l'hceres */
    protected $uid = 524718; /* Compte propriétaire des dépôts */
    //protected $loadRef = false; /* Chargement du référentiel */
    //protected $loadReport = true; /* Chargement des rapports */
    //Chemin vers le répertoire contenant les fichiers des rapports
    protected $reportDir = '';
    protected $debug = false;

    /**
     */
    protected $options = array(
        'referentiel|ref=s' => 'Fichier référentiel à charger',
        'report|rep=s' => 'Fichier des rapports à charger'
    );

    /**
     * Tableau de correspondance entre les colonnes et les champs du référentiel
     */
    protected $mappingRef = [
        0   =>    self::HCERESID,
        1   =>    self::CODE_UAI,
        2   =>    self::CODE_RNSR,
        3   =>    self::CODE_ACCREDITATION,
        4   =>    'NOM',
        5   =>    'NOM_USAGE',
        6   =>    'SIGLE',
        7   =>    'NOM_ALIAS',
        8   =>    self::NEW_HCERESID,
        9   =>    'TYPEHCERES', //voir pour l'enum
        10  =>    'STYPEHCERES',
        11  =>    'ADRESSE',
        12  =>    'PAYSID',
        13  =>    'VILLE',
        14  =>    'REGION'
    ];
    const COL_HCERESID = 0; // numéro de la colonne contenant l'identifiant de la structure HCERES
    const COL_PAYSID = 12;  // numéro de la colonne contenant l'identifiant du pays

    /**
     * Tableau de correspondace entre les colonnes et les champs du rapport
     */
    protected $mappingReport = [
        0   => self::HCERES_ENTITE_LOCAL,
        1   => self::LOCAL_REFERENCE,
        2   => self::TITLE,
        3   => 'file',
        4   => 'type',
        5   => self::HCERES_ETABSUPPORT_LOCAL, //multiple
        6   => self::HCERES_FORMEVAL_LOCAL, //multiple
        7   =>    'hceres_formprop_local', //multiple
        8   =>    'date',
        9   =>     self::LANGUAGE,
        10   =>    self::HCERES_CAMPAGNE_LOCAL,
        11   => self::HCERES_DOM_LOCAL, //multiple
        12   => self::HCERES_DOMSCI_LOCAL, //multiple
        13   => self::HCERES_ERC_LOCAL,
        14   => self::HCERES_TEAM_LOCAL,
        15   => self::HCERES_MENTION_LOCAL,
        16   => self::HCERES_SPECIALITIES_LOCAL,
        17   => self::HCERES_DIPLOME_LOCAL,
        18   => self::HCERES_DOMFORM_LOCAL,
        19   => self::HCERES_LABO_LOCAL,
        20  => self::KEYWORD,
        21  => self::ABSTRACT1,
    ];

    protected $mappingTypdoc = [
        'RCT'       => 'REPORT_COOR',
        'RETAB'     => 'REPORT_ETAB',
        'RER'       => self::REPORT_LABO,
        'RCR'       => self::REPORT_RECH,
        'RLI'       => self::REPORT_LICE,
        'RLIPRO'    => self::REPORT_LPRO,
        'RGM'       => 'REPORT_GMAST',
        'RGL'       => 'REPORT_GLICE',
        'RMA'       => self::REPORT_MAST,
        'RPED'      => self::REPORT_DOCT,
        'RCF'       => 'REPORT_FORM',
        'RCHP'      => 'REPORT_FPROJ',
        'RPA'       => 'REPORT_RPA',
        'RCOINT'    => 'REPORT_RCOINT',
        'RETABINT'  => 'REPORT_RETABINT',
        'RFOINT'    => 'REPORT_RFOINT',
        'RRECHINT'  => 'REPORT_INTER'
    ];

    protected $metaList = [
        'REPORT_ETAB'    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1],
        'REPORT_COOR'    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL],
        self::REPORT_LABO    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_DOM_LOCAL, self::HCERES_DOMSCI_LOCAL, self::HCERES_ERC_LOCAL, self::HCERES_TEAM_LOCAL],
        self::REPORT_LICE    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_MENTION_LOCAL, self::HCERES_SPECIALITIES_LOCAL, self::HCERES_DIPLOME_LOCAL, self::HCERES_DOMFORM_LOCAL],
        self::REPORT_LPRO    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_MENTION_LOCAL, self::HCERES_SPECIALITIES_LOCAL, self::HCERES_DIPLOME_LOCAL, self::HCERES_DOMFORM_LOCAL],
        self::REPORT_MAST    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_MENTION_LOCAL, self::HCERES_SPECIALITIES_LOCAL, self::HCERES_DIPLOME_LOCAL, self::HCERES_DOMFORM_LOCAL, self::HCERES_DOM_LOCAL],
        'REPORT_GMAST'   =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_MENTION_LOCAL, self::HCERES_DIPLOME_LOCAL, self::HCERES_DOMFORM_LOCAL],
        'REPORT_GLICE'   =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_MENTION_LOCAL, self::HCERES_DIPLOME_LOCAL, self::HCERES_DOMFORM_LOCAL],
        self::REPORT_DOCT    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_DOM_LOCAL, self::HCERES_DOMSCI_LOCAL, self::HCERES_LABO_LOCAL],
        self::REPORT_RECH    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_DOM_LOCAL],
        'REPORT_INTER'   =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL],
        'REPORT_RPA'     =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL],
        'REPORT_RCOINT'  =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL],
        'REPORT_RETABINT'=>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL],
        'REPORT_RFOINT'  =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL],
        'REPORT_FORM'    =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_DOMFORM_LOCAL, self::HCERES_FORMEVAL_LOCAL],
        'REPORT_FPROJ'   =>  [self::HCERES_ENTITE_LOCAL, self::LOCAL_REFERENCE, self::TITLE, 'file', 'type', 'date', self::LANGUAGE, self::HCERES_CAMPAGNE_LOCAL, self::KEYWORD, self::ABSTRACT1, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_DOMFORM_LOCAL, self::HCERES_FORMEVAL_LOCAL, 'hceres_formprop_local']
    ];




    /**
     * Fonction principale
     *
     * @param Zend_Console_Getopt $getopt
     *
     * @return void pas de retour
     */
    public function main($getopt)
    {
        /** Environnement Hal generique */
        RuntimeConstDef('', self::SITE);

        $oPortail = Hal_Site::exist(self::SITE);
        if (!is_object($oPortail)) {
            $this->println('', '**************************************', 'red');
            $this->println('', '*  Portail '.self::SITE. self::INEXISTANT_LC, 'red');
            $this->println('', '**************************************', 'red');
            return;
        }
        $this->sid = $oPortail->getSid();
        define ('SITEID', $this->sid);

        // debug ou non
        if ($getopt->getOption('debug')) {
            $this->debug = true;
        }

        $this->reportDir = SPACE . self::REP_IMPORT . 'pdf/';

        // traitement du référentiel
        if ($getopt->getOption('referentiel')) {
            //Chemin vers le ficher CSV du référentiel des entités évaluées
            $referentielFile = SPACE . self::REP_IMPORT . $getopt->referentiel;

            $this->loadReferential($referentielFile);
        }

        // traitement des rapports
        if ($getopt->getOption('report')) {
            //Chemin vers le fichier CSV de rapports
            $reportFile = SPACE . self::REP_IMPORT . $getopt->report;

            $this->loadReport($reportFile);
        }
        $this->verbose("******************\n");
        $this->verbose("*  Fin du script *\n");
        $this->verbose("******************\n");
    }

    /**
     * Import des données du référentiel des structures HCERES
     *
     * @param string $csvFile : fichier CSV contenant les structures HCERES à importer
     *
     * @return void pas de retour
     */
    private function loadReferential(string $csvFile)
    {
        if (file_exists($csvFile)) {
            $src = file_get_contents($csvFile);

            foreach (explode("\n", $src) as $rowNb => $row) {
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
            $this->println('', self::C, 'red');
            $this->println('', '*  Fichier référentiel '.$csvFile. self::INEXISTANT_LC, 'red');
            $this->println('', self::C, 'red');
        }
    }

    /**
     * Ajout des données du référentiel
     *
     * @param array $data : données à insérer ou modifier
     *
     * @return void pas de retour
     */
    private function insertDataReferential(array $data)
    {
        $entity = [];
        foreach ($this->mappingRef as $column => $field) {
            if (isset($data[$column])) {
                if ($column == self::COL_HCERESID) { //hceresid
                    $data[$column] = intval($data[$column]);
                } else if ($column == self::COL_PAYSID) {
                    $data[$column] = strtolower(substr(trim($data[$column]), 0, 2 ));
                }
                if (!isset($entity[$field]) || trim($entity[$field]) == '') {
                    $entity[$field] = trim($data[$column]);
                }
            }
        }

        if (isset($entity[self::NEW_HCERESID])) {
            if ($entity[self::NEW_HCERESID] == '') {
                unset($entity[self::NEW_HCERESID]);
            } else {
                $entity[self::NEW_HCERESID] = intval($entity[self::NEW_HCERESID]);
            }
        }

        if (isset($entity[self::CODE_RNSR])) {
             $entity[self::CODE_RNSR] = substr($entity[self::CODE_RNSR], 0, 10);
        }

        $entity['VALID'] = 'VALID';

        $db = Zend_Db_Table::getDefaultAdapter();
        try {
            if ($entity[self::HCERESID]!= '' && isset($entity['NOM']) && $db->insert('REF_HCERES', $entity)) {
                $result = ' OK';
            } else {
                $result = ' NOK';

                var_dump($entity);
            }
        } catch(Exception $e) {
            //println($e->getMessage());
            $result = ' NOK';
            echo 'NOK : '.$e->getMessage();
            exit;
        }
        if ($this->debug) {
            println('Insertion de la donnée ' . $entity[self::HCERESID] . ': ' . $result);
        }
    }


    /**
     * Import des données du référentiel des strucutures HCERES
     *
     * @param string $csvFile: fichier CSV contenant les rapports à importer
     */
    private function loadReport(string $csvFile)
    {
        if(file_exists($csvFile)) {
            $src = file_get_contents($csvFile);

            $crlf = "\r";
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
                $this->insertReport($data);
            }
        } else {
            $this->println('', self::C, 'red');
            $this->println('', '*  Fichier rapport'.$csvFile. self::INEXISTANT_LC, 'red');
            $this->println('', self::C, 'red');
        }
    }

    /**
     * Ajout des données du rapport
     *
     * @param array $data : données à insérer ou modifier
     *
     * @return bool
     */
    private function insertReport(array $data)
    {
        //Mapping entre colonne et métadonnée
        $report = $this->getReportFromSrc($data);

        //Nettoyage des différentes métadonnées
        $report = $this-> cleanReport($report);
        if ($report['file'] == '' ) {
            echo $report[self::HCERES_ENTITE_LOCAL] . " | INSERT NOK (pdf " .$report['filesrc'] . " inexistant)\n";
            return false;
        }
        //Filtre pour ne retenir que les bonnes métadonnées
        $report = $this->filterReport($report);

        //Eviter de redéposer des rapports déja déposés
        $sql = "SELECT DOCID FROM DOC_METADATA WHERE METANAME='".self::HCERES_ENTITE_LOCAL."' AND METAVALUE = '" . $report[self::HCERES_ENTITE_LOCAL] . "' AND DOCID IN (SELECT DOCID FROM DOC_METADATA WHERE METANAME='localReference' AND METAVALUE = '" . $report['localReference'] . "')";
        $db = Zend_Db_Table::getDefaultAdapter();
        $docid = $db->fetchOne($sql);
        if ($docid) {
            $sql = "SELECT IDENTIFIANT FROM DOCUMENT WHERE DOCID = " . $docid;
            $id = $db->fetchOne($sql);
            if ($id) {
                echo $report[self::HCERES_ENTITE_LOCAL] . " | " . $db->fetchOne($sql)  . " \n";
                return true;
            }
        }

        if (! isset($report['type']) || $report['type'] == '') {
            echo $report[self::HCERES_ENTITE_LOCAL] . " | INSERT NOK (Type de document inexistant)\n";
            return false;
        }
        $report['domain'] = ['shs'];

        $document = $this->createHalDocument($report['type']);

        $this->addMetaHalDocument($document, $report);

        $this->addFileHalDocument($document, $report['file']);

        $this->addAuthorHalDocument($document);

        try {
            Hal_Document_Validity::isValid($document);
        } catch(Exception $e) {
            var_dump($document);
            $this->println('', $report[self::HCERES_ENTITE_LOCAL] . " | META NOK | ". serialize($e->getMessage()), 'red');
            return false;
        }
        $document->setTypeSubmit(Hal_Settings::SUBMIT_INIT);

        //Cppie du PDF pour les autres rapports qui l'utiliserait
        $tmpFile = $this->reportDir . 'tmp.pdf';
        @copy($report['file'], $tmpFile);

        $docid = $document->save(1, false);

        @copy($tmpFile, $report['file']);

        if ($docid == 0 ) {
            $this->println('', $report[self::HCERES_ENTITE_LOCAL] ." | INSERT NOK ", 'red');
        } else {
            $this->verbose($report[self::HCERES_ENTITE_LOCAL] ." | " . $document->getId() ."\n");
        }
        return true;
    }

    /**
     * Création d'un tableau de métadonnées
     *
     * @param array $data
     *
     * @return array
     */
    private function getReportFromSrc(array $data) : array
    {
        $report = [];
        foreach ($this->mappingReport as $column => $field) {
            if ((isset($data[$column])) && (!isset($report[$field]) || trim($report[$field]) == '')) {
                $report[$field] = preg_replace('/\s\s+/', '', trim($data[$column]));
            }
        }
        return $report;
    }

    /**
     * Nettoyage des données du rapport
     *
     * @param array $report : données du rapport issues du fichier Excel
     *
     * @return array : données du rapport nettoyées pour insertion dans Hal
     */
    private function cleanReport(array $report) : array
    {
        if (!array_key_exists(self::LANGUAGE, $report) || !array_key_exists('type', $report)) {
            var_dump($report);exit;
        }
        $reportTmp = [];
        foreach ($report as $meta => $value) {
            if (trim($value) != '') {
                $reportTmp[$meta] = $value;
            }
        }
        $report = $reportTmp;

        //Mise en minuscule de la langue
        $report[self::LANGUAGE] = strtolower($report[self::LANGUAGE]);
        //Reformatage de la date
        list($day, $month, $year) = explode('/', $report['date']);
        $report['date'] = $year . '-' . $month . '-' . $day;
        //Typologie HAL
        if (! isset($report['type']) || ! array_key_exists($report['type'], $this->mappingTypdoc)){
            var_dump($report);
            die('Type de rapport non reconnu : ' . $report['type']);
        }
        $report['type'] = $this->mappingTypdoc[$report['type']];

        foreach ([self::TITLE, self::ABSTRACT1] as $meta) {
            if (isset($report[$meta])) {
                $report[$meta] = ['fr' => $report[$meta]];
            }
        }

        //Conservation uniquement du code
        foreach (['hceres_campagne_local', self::HCERES_ERC_LOCAL] as $meta) {
            if (isset($report[$meta])) {
                $report[$meta] = substr($report[$meta], 0, strpos( $report[$meta], ' '));
            }
        }
        foreach ([self::KEYWORD, self::HCERES_ERC_LOCAL, self::HCERES_ETABSUPPORT_LOCAL, self::HCERES_DOMFORM_LOCAL, self::HCERES_LABO_LOCAL, self::HCERES_DOM_LOCAL, self::HCERES_TEAM_LOCAL, self::HCERES_DOMSCI_LOCAL] as $meta) {
            if (isset($report[$meta])) {
                $value = [];
                foreach (explode('|', $report[$meta]) as $v) {
                    $v = trim($v);
                    if ($v != '' && $v != 'Autre') {
                        $value[] = $v;
                    }
                }

                if ($meta == self::KEYWORD) {
                    $report[$meta] = ['fr' => $value];
                } else {
                    $report[$meta] = $value;
                }
            }
        }

        //Fichier
        if ($report['file'] != '' && is_file($this->reportDir . $report['file'])) {
            $report['file'] = $this->reportDir . $report['file'];
        } else {
            $report['filesrc'] = $report['file'];
            $report['file'] = '';
        }

        //todo voir pour supprimer les domaines
        $report['domain'] = ['shs'];

        return $report;

    }

    /**
     * Filtrage des données du rapport par rapport à la liste des métas nécessaires pour Hal
     *
     * @param array $report : tableau des metas du rapport
     *
     * @return array : tableau des metas filtrées du rapport
     */
    private function filterReport($report)
    {
        $newReport = [];

        $typdoc = $report['type'];

        foreach ($report as $meta => $value) {
            if (in_array($meta, $this->metaList[$typdoc])) {
                $newReport[$meta] = $value;
            }
        }
        return $newReport;
    }


    /**
     * Création d'un document Hal
     *
     * @param string $typdoc : type de document
     *
     * @return Hal_Document
     */
    private function createHalDocument($typdoc)
    {
        $document = new Hal_Document();
        $document->setTypdoc($typdoc);

        $document->setSid($this->sid);
        $document->setContributorId($this->uid);
        $document->setInputType(Hal_Settings::SUBMIT_ORIGIN_SWORD);

        return $document;
    }

    /**
     * Ajout des métadonnées du rapport à l'objet Hal_Document
     *
     * @param Hal_Document $document : objet correpondant au rapport à insérer
     * @param array $report : tableau des données du rapport à insérer
     *
     * @return Hal_Document modifié
     */
    private function addMetaHalDocument(Hal_Document $document, array $report)
    {
        $docMeta = [];
        foreach ($report as $meta => $value) {
            if ($meta == 'file' || $meta == 'type') {
                continue;
            }

            if (in_array($meta, [self::HCERES_ENTITE_LOCAL, self::HCERES_ETABSUPPORT_LOCAL])) {
                if (is_array($value)) {
                    $newValue = [];
                    foreach ($value as $v) {
                        $newValue[] = new Ccsd_Referentiels_Hceres($v);
                    }
                    $value = $newValue;
                } else {
                    $value = new Ccsd_Referentiels_Hceres($value);
                }
            }
            $docMeta[$meta] = $value;
        }

        $document->setMetas($docMeta);
        return $document;
    }

    /**
     * Ajout d'un fichier à un document
     *
     * @param Hal_Document $document : objet correpondant au rapport à insérer
     * @param string $filepath : chemin du fichier à ajouter
     *
     * @return Hal_Document modifié
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

    /**
     * Ajout de l'auteur HCERES au document
     *
     * @param Hal_Document $document : objet correpondant au rapport à insérer
     *
     * @return Hal_Document modifié
     */
    private function addAuthorHalDocument(Hal_Document $document)
    {
        $author = new Hal_Document_Author();
        $author->setFirstname('rapport');
        $author->setLastname('Hcéres');
        $document->addAuthor($author);

        return $document;
    }

}

echo "debut\n";
$script = new ImportHceresScript();
echo "suite\n";
$script->run();
echo "fin\n";