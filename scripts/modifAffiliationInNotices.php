<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require __DIR__ . "/../library/Hal/Script.php";

/**
 * Class transfertStuctureDocid
 */
class modifAffiliationInNotices extends Hal_Script {

    static private $url_format = "https://api.archives-ouvertes.fr/search/?q=(structId_i:%s)%%20AND%%20((defenseDateY_i:%s)%%20OR%%20(producedDateY_i:%s))&fl=docid&wt=csv";

    protected $options  = array(
        'date=s'     => 'Date de début',
        'docid|D-s'  => 'Docids (séparés par des virgules)',
        'from|f=i'   => 'Structure de départ',
        'to=i'       => "Structure d'arrivée",
        'noop|n'     => "Pas d'action effectuée, pour tester",
        'uid-s'      => 'UID ou login de la personne qui fait la modification'
    );

    /**
     * @param int    $struct
     * @param string $date
     * @return int[]     array of Docids
     */
    private function getDocumentByStructureAndDate($struct, $date) {
        $url = sprintf(self::$url_format, $struct, $date,$date);
        $curlR = curl_init($url);
        curl_setopt($curlR, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($curlR);
        if ($res) {
            $lineNumber = 0;
            $docids = [] ;
            $line = strtok($res, "\n\r");
            while ($line !== false) {
                $lineNumber++;
                if (preg_match('/^\s*(#|docid$)/', $line)) {
                    $line = strtok("\n\r");
                    continue;
                }
                $line = trim($line);
                if (!preg_match('/^\d+$/', $line)) {
                    fwrite(STDERR,  "Incorrect line ($lineNumber): $line\n");
                    $line = strtok("\n\r");
                    continue;
                }
                $docids[] = $line;
                $line = strtok("\n\r");
            }
            return $docids;
        } else {
            // Error
            fwrite(STDERR,  "Curl error for $url\n");
            return [];
        }
    }

    /**
     * @param int $from
     * @param int $to
     * @param int[] $docids
     * @param int $uid
     * @param bool $test
     * @return bool
     * @throws Zend_Db_Adapter_Exception
     */
    private function transfertAffiliation($from, $to, $docids, $uid=0, $test=false)
    {
        if (count($docids) == 0) {
            return true;
        }
        $db = Zend_Db_Table::getDefaultAdapter();
        // On récupère les identifiants de la table AUTSTRUCT pour un ensemble de STRUCTID et un ensemble de DOCID
        $sql = $db->select()
            ->from(array("das" => "DOC_AUTSTRUCT"), "das.AUTSTRUCTID")
            ->join(array("da" => "DOC_AUTHOR"), "das.DOCAUTHID = da.DOCAUTHID", "das.AUTSTRUCTID")
            ->where("da.DOCID IN (?)", $docids)
            ->where("das.STRUCTID = ?", $from);

        $res = $db->fetchCol($sql);

        $this->debug("$sql");
        // affiliation auteur/structure
        if (empty($res)) {
            $this->println('', '**************************************************************', 'blue');
            $this->println('Aucun auteur à modifier.');
            $this->println('', '**************************************************************', 'blue');
            return false;
        }

        // On modifie le STRUCTID pour l'ensemble des résultats trouvés
        $where['AUTSTRUCTID IN (?)'] = $res;
        if ($test) {
            $resString = implode(',', $res);
            $this->println("\nWill: UPDATE DOC_AUTSTRUCT set STRUCTID = '$to' where AUTSTRUCTID IN ($resString)\n");
        } else {
            // Chgment de la structure
            $db->update('DOC_AUTSTRUCT', array('STRUCTID' => $to), $where);
            // On réindexe les documents modifiés
            Ccsd_Search_Solr_Indexer::addToIndexQueue($docids);
         }


        foreach ($docids as $d) {
            // On log la modification
            if (!$test) {
                Hal_Document_Logger::log($d, $uid, Hal_Document_Logger::ACTION_MODIF, 'Remplacement de la structure ' . $from . ' par la structure ' . $to . ' depuis le script modifAffiliationInNotices.');
            }
        }
        return true;
    }

    /**
     * Verifie que l'id donnee correspondent a des structures du referentiel
     * Si DISPLAY est vrai, alors le nom de la structure est affichee.
     * @param int $id
     * @param bool $display
     * return bool
     */
    private function verifyStruct($id, $display=false) {
        $id = trim($id);
        $s = new Ccsd_Referentiels_Structure($id);
        if (!$s) {
            die("Struct $id don't exist\n");
        }
        if ($display) {
            print "$id\t";
            print $s->getStructname();
            print "\n";
        }
    }

    /**
     * Lecture du fichier csv par stdin
     * @return array
     */
    private function readFromToFromStdin() {
        $fromTo = [];
        while ($line = fgets(STDIN)) {
            $line = trim($line);
            if (preg_match('/^#/', $line)) {
                continue;
            }
            list($from, $to) = explode(',', $line);
            $this->verifyStruct($from, true);
            $this->verifyStruct($to, true);
            $fromTo[$from] = $to;
        }
        return $fromTo;
    }
    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt) {
        $manualDocids =  false;

        $this -> need_user('apache');
        /** @var string $date */
        $date = $getopt -> getOption('date');
        /** @var int $uid */
        $uid = $getopt  -> getOption('uid');
        /** @var bool $test */
        $test = $getopt -> getOption('noop');
        /** @var int $from */
        $from = $getopt -> getOption('from');
        /** @var int $to */
        $to   = $getopt -> getOption('to');
        /** @var int[] $docids */
        $docids = $getopt -> getOption('docid');
        var_dump($docids);
        if ($docids) {
            $docids = explode(',' ,$docids);
            $manualDocids = true;
        }
        /** @var int[] $docids */
        if (!$uid) {
            die("You must supply an Uid\n");
        }

        $corresp=[];
        if (isset($to) xor isset($from)) {
            die ("Si vous donner --from, vous devez donner --to et reciproquement\n");
        }
        if (isset($docids) && count($docids) > 0) {
            if (!(isset($to) && isset($from))) {
                die("Si vous precisez des docids, vous devez precisez --from et --to\n");
            }
        } else {
            if (isset($date)) {
                if (!preg_match('/^\d{4}$(-\d\d-\d\d)?$/', $date)) {
                    die("Date not correct: must verify YYYY or YYYY-MM-dd\n");
                }
            } else {
                die ("Vous devez fournir soit une liste de docids, soit une date correcte.");
            }
        }
        // On construit le|les couples de structures
        if (isset($to) && isset($from)) {
            $this->debug("Get arg\n");
            $this->verifyStruct($from, true);
            $this->verifyStruct($to, true);
            $corresp[$from] = $to;
        } else {
            $this->debug("Get Stdin\n");
            $corresp = $this->readFromToFromStdin();
        }
        if (!$this -> y_or_n("C'est bon ?", false)) {
            print "Abandon...\n";
            exit(0);
        }
        try {
            foreach ($corresp as $from => $to) {
                $this->verbose("Do transfert from $from to $to...\n");
                if ($manualDocids) {
                    // Docids fournit en linge de commande from et to sont obligatoirement fournie: c'est verifie plus haut
                    // Il n'y a qu'une seul iteration!
                    $this->transfertAffiliation($from, $to, $docids, $uid, $test);
                } else {
                    // Pas de docids fournit, on effectue la requete solr
                    $docids = $this->getDocumentByStructureAndDate($from, $date);
                    $this->verbose("Docids consideres: " . implode(", ", $docids));

                    $this->transfertAffiliation($from, $to, $docids, $uid, $test);
                }
                $this->verbose("Done.\n");
            }
        } catch (Zend_Db_Adapter_Exception $e) {
            die ("Problem with database: " . $e -> getMessage());
        }
    }
}

$script = new modifAffiliationInNotices();
$script->run();