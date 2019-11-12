<?php



require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../library/Hal/Script.php';

/**
 * Class loadAnrScript
 *
 * INPUT: CSV de la base projet fourni par l'ANR
 *
 * Objectif: Remplissage de la table REF_PROJANR
 */
class loadAnrScript extends Hal_Script {
    protected $options = [
        'filename|f=s' => 'path to CSV file of ANR Project',
        'year|y=s' => 'limit to year',
        'test|t' => 'Do nothing, just print message',
    ];

    /**
     * @param $data
     * @return array
     */
    private function cleanCsvEntry ($data) {
        $entry = [];
        $num = count($data);
        for ($c=0; $c < $num; $c++) {
            $value = $data[$c];
            $value = trim(Ccsd_Tools_String::stripCtrlChars($value));

            if (strlen($data[$c])>500 ) {
                $this -> println("String too long: $value");
            }
            $entry[$c] = trim(Ccsd_Tools_String::stripCtrlChars($value));
        }
        return $entry;
    }
    /**
     * @param Zend_Console_Getopt $args
     */
    public function main($args) {

        $file = $args -> filename;
        $year = $args -> year;
        $db = Zend_Db_Table::getDefaultAdapter();
        $stmtANRInsert = $db->prepare("INSERT INTO `REF_PROJANR` (TITRE,ACRONYME,REFERENCE,INTITULE,ACROAPPEL,ANNEE,VALID) VALUES (:TITRE,:ACRONYME,:REFERENCE,:INTITULE,:ACROAPPEL,:ANNEE,:VALID)");
        $stmtANRUpdate = $db->prepare("UPDATE `REF_PROJANR` set TITRE=:TITRE,ACRONYME=:ACRONYME,REFERENCE=:REFERENCE,INTITULE=:INTITULE, ACROAPPEL=:ACROAPPEL,ANNEE=:ANNEE,VALID=:VALID where ANRID=:ANRID");

        // Récupération des ProjANR, csv -> Référence;Titre;Acronyme;Intitulé du programme;Acronyme du programme;Année
        $csv = [];
        $line = 0;
        if (($handle = fopen($file, "r")) !== FALSE) {
            $header = fgetcsv($handle, 0, "\t"); // suppress header
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $csv[] = $this->cleanCsvEntry($data);
                $line++;
            }
            fclose($handle);
        }

        if ( count($csv) ) {
            $this -> verbose(count($csv).' projets ANR à intégrer' . "($line)") ;
            $line=2;
            $nbExist=0;
            $nbNew=0;
            $nbErr=0;
            $md5array = [];
            $sql = $db->select()->from('REF_PROJANR', [ 'MD5', 'ANRID' ] );
            $stmt = $db->query($sql);
            while ($row = $stmt->fetch(Zend_Db::FETCH_NUM)) {
                $md5array[bin2hex($row[0])] = $row[1];
            }
            foreach( $csv as $anr ) {
                if (count($anr) < 5) {
                    print "$line: bad line \n";
                    var_dump($anr);
                    exit;
                }
                // Entetes CSV 2017
                // REFERENCE,ACRONYME_PROJET,TITRE_FR,ACRONYME_PROGRAMME,ANNEE
                list($reference, $annee, $accronyme, $titleFr, $titleEn, $intProg, $acroappel) = $anr;
                if (($year != null) && ($annee != $year)) {
                    continue;
                }
                // $exist = $db->query("SELECT ANRID from `REF_PROJANR` where MD5 = UNHEX('" . $md5 ."')")->fetch();
                $title = ($titleEn == '') ? $titleFr : $titleEn;
                $md5 = md5(mb_strtolower('titre'.$title.'acronyme'.$accronyme.'reference'.$reference));

                $exist = array_key_exists($md5, $md5array);
                $bind=[];
                $bind[':REFERENCE'] = $reference;
                $bind[':ACRONYME']  = $accronyme;
                $bind[':TITRE']     = $title;
                $bind[':INTITULE']  = $intProg;
                $bind[':ACROAPPEL'] = $acroappel;
                $bind[':ANNEE']     = $annee;
                $bind[':VALID']     = 'VALID';
                if ($args -> test) {
                    if ( $exist ) {
                        $this -> verbose("$reference: projet existe");
                        $nbExist++;
                    } else {
                        $this -> verbose("$reference: nouveau projet");
                        $nbNew++;
                    }
                } else {
                        if ($exist) {
                            $this->verbose("$reference: projet existe");
                            $nbExist++;
                            $bind[':ANRID'] = $exist['ANRID'];
                            $lastCommand = $stmtANRUpdate;
                            try {
                                $res = $stmtANRUpdate->execute($bind);
                            } catch (Exception $e) {
                                $this -> debug('ERROR: ne devrait pas arrive...: ' . $e->getMessage());
                                $nbErr++;
                                $res  = false;
                            }
                        } else {
                            $this->verbose("$reference: nouveau projet");
                            $nbNew++;
                            $lastCommand = $stmtANRInsert;
                            try {
                                $res = $stmtANRInsert->execute($bind);
                            } catch (Exception $e) {
                                $this -> debug('ERROR: ne devrait pas arrive...:' . $e->getMessage());
                                $nbNew--;
                                $nbErr++;
                                $res  = false;
                            }
                        }

                    if ($res) {
                        $this -> debug('# ANR inserted or updated into REF_PROJANR');
                    } else {
                        if ($lastCommand->errorInfo()[0] == 23000) {
                            $this -> debug('# ANR already on REF_PROJANR ');
                        } else {
                            if ($this -> isDebug()) {
                                // On test debug, pas la peine d'appeler print_r pour ignorer le resultat
                                $this -> debug($anr[0]);
                                $this -> debug(print_r($stmtANRInsert->errorInfo(), true));
                            }
                        }
                    }
                }
                $line++;
            }
            $this -> verbose('Nombre de projets ajoutes  : ' . $nbNew);
            $this -> verbose('Nombre de projets existants: ' . $nbExist);
            $this -> verbose('Nombre de projets en erreur: ' . $nbErr);
        }
    }
}

$script = new loadAnrScript();
$script -> run();