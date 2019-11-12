<?php
/**
 * Le fichier donnee etait de type CSV
 *
 * Pas d'annee, donc on recupere l'annee avec le debut de la reference.
 * Pas d'appel a projet: on prends donc le type (IDEX, LABX, IDFI,... dans la reference aussi)
 * Le libelle de l'acction n'est pas repetee dans le fichier, donc on la garde d'une ligne sur l'autre
 *
 * Certaines lignes d'entete sont repetes plusieurs fois...
 * Il y a des lignes vides!
 */


require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../library/Hal/Script.php';

/**
 * Class loadAnrPIAScript
 */
class loadAnrPIAScript extends Hal_Script {
    protected $options = [
        'filename|f=s' => 'path to CSV file of ANR Project',
        'test|t' => 'Do nothing, just print message',
    ];

    /**
     * @param $data
     * @return array
     */
    private function cleanCsvEntry ($data) {
        $entry = [];
        foreach ($data as $value) {
            $value = str_replace('_x000D_', '', $value);
            $value = trim(Ccsd_Tools_String::stripCtrlChars($value));
            if (strlen($value)>500 ) {
                $this -> println("String too long: $value");
            }
            $entry[] = $value;
        }
        return $entry;
    }
    /**
     * @param Zend_Console_Getopt $args
     */
    public function main($args) {

        $file  = $args -> getOption('filename');
        $test  = $args -> getOption('test');
        $db = Zend_Db_Table::getDefaultAdapter();
        $stmtANRInsert = $db->prepare("INSERT INTO `REF_PROJANR` (TITRE,ACRONYME,REFERENCE,INTITULE,ACROAPPEL,ANNEE,VALID) VALUES (:TITRE,:ACRONYME,:REFERENCE,:INTITULE,:ACROAPPEL,:ANNEE,:VALID)");
        $stmtANRUpdate = $db->prepare("UPDATE `REF_PROJANR` set TITRE=:TITRE,ACRONYME=:ACRONYME,REFERENCE=:REFERENCE,ACROAPPEL=:ACROAPPEL,ANNEE=:ANNEE,VALID=:VALID where ANRID=:ANRID");

        // Récupération des ProjANR, csv -> Référence;Titre;Acronyme;Intitulé du programme;Acronyme du programme;Année
        $csv = [];
        $line = 0;
        if (($handle = fopen($file, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 4096, "\t")) !== FALSE) {
                $nbinfo = count($data);
                if (($nbinfo <= 2 ) || ($nbinfo == 0)) {
                    continue;
                }
                if ($data[1] == 'Action Nom') {
                    continue;
                }
                if ($data[2] == '') {
                    continue;
                }
                if (count($data) < 5) {
                    print "$line: bad line \n";
                    var_dump($data);
                    exit;
                }
                $csv[] = $this->cleanCsvEntry($data);
                $line++;
            }
            fclose($handle);
        }

        if ( count($csv) ) {
            $this -> verbose(count($csv).' projets PIA ANR à intégrer' . "($line)") ;
            $globalacroappel = null;
            $line=2;
            $nbExist=0;
            $nbNew=0;
            $nbErr=0;
            $md5array = [];
            $sql = $db->select()->from('REF_PROJANR', [ 'MD5', 'ANRID' ] );
            $stmt = $db->query($sql);
            try {
            while ($row = $stmt->fetch(Zend_Db::FETCH_NUM)) {
                $md5array[bin2hex($row[0])] = $row[1];
            }
                foreach ($csv as $anr) {
                    $nbinfo = count($anr);

                    // Entetes CSV 2017
                    // REFERENCE,ACRONYME_PROJET,TITRE_FR,ACRONYME_PROGRAMME,ANNEE
                    list($foo, $intitule, $reference, $accronyme, $title) = $anr;
                    if ($intitule == '') {
                        $intitule = $globalacroappel;
                    } else {
                        $globalacroappel = $intitule;
                    }
                    $data = [];
                    $match = preg_match('/^(\d+)-([^\-]+)-/', $reference, $data);
                    if (!$match) {
                        $this->displayError("Bad reference (no year found): $reference");
                        continue;
                    }
                    $annee = 2000 + $data[1];
                    $appel = $data[2];
                    $md5 = md5(mb_strtolower('titre' . $title . 'acronyme' . $accronyme . 'reference' . $reference));
                    // $exist = $db->query("SELECT ANRID from `REF_PROJANR` where MD5 = UNHEX('" . $md5 ."')")->fetch();
                    $exist = array_key_exists($md5, $md5array);
                    $bind = [];
                    $bind[':REFERENCE'] = $reference;
                    $bind[':ACRONYME']  = $accronyme;
                    $bind[':ACROAPPEL'] = $appel;
                    $bind[':TITRE']     = $title;
                    $bind[':INTITULE']  = $intitule;
                    $bind[':ANNEE']     = $annee;
                    $bind[':VALID']     = 'VALID';
                    if ($test) {
                        if ($exist) {
                            $this->verbose("$reference: projet existe");
                            $nbExist++;
                        } else {
                            $this->verbose("$reference: nouveau projet en $annee ($accronyme) pour action: ($intitule)");
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
                                $this->debug('ERROR: ne devrait pas arrive...');
                                $nbErr++;
                                $res = false;
                            }
                        } else {
                            $bind[':INTITULE'] = '';
                            $this->verbose("$reference: nouveau projet en $annee ($accronyme) pour action: ($intitule)");
                            $nbNew++;
                            $lastCommand = $stmtANRInsert;
                            try {
                                $res = $stmtANRInsert->execute($bind);
                            } catch (Exception $e) {
                                $this->debug('ERROR: ne devrait pas arrive...');
                                $nbNew--;
                                $nbErr++;
                                $res = false;
                            }
                        }

                        if ($res) {
                            $this->debug('# ANR inserted or updated into REF_PROJANR');
                        } else {
                            if ($lastCommand->errorInfo()[0] == 23000) {
                                $this->debug('# ANR already on REF_PROJANR ');
                            } else {
                                if ($this->isDebug()) {
                                    // On test debug, pas la peine d'appeler print_r pour ignorer le resultat
                                    $this->debug($anr[0]);
                                    $this->debug(print_r($stmtANRInsert->errorInfo(), true));
                                }
                            }
                        }
                    }
                    $line++;
                }
            } catch (Zend_Db_Statement_Exception $e) {
                $this->displayError("Pb db: " . $e ->getMessage());
            }
            $this -> verbose('Nombre de projets ajoutes  : ' . $nbNew);
            $this -> verbose('Nombre de projets existants: ' . $nbExist);
            $this -> verbose('Nombre de projets en erreur: ' . $nbErr);
        }
    }
}

$script = new loadAnrPIAScript();
$script -> run();