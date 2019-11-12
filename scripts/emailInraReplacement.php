<?php
/**
 * Created by PhpStorm.
 * User: genicot
 * Date: 09/04/19
 * Time: 14:34
 */

/**
 * Class emailInraReplacement
 * Classe permettant de gérer le flux d'import de prodinra
 */


if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require __DIR__ . "/../library/Hal/Script.php";



class emailInraReplacement extends Hal_Script
{



    /**
     */
    protected $options = array(
        'path|p' => 'chemin des répertoires contenant les notices',
        'filters|f' => 'liste des filtres à appliquer au flux'
    );

    /**
     * @param Zend_Console_Getopt $getOpt
     */
    public function main($getOpt){

        // A l'appel de main on doit creer deux fichiers de log ( good log and bad log )

        // on vérifie que le fichier de remplacement en csv est bien présent

        // on vérifie que les paramètres d'accès à la base de donnée du CAS fonctionnent bien.

        // on ouvre le fichier csv et pour chaque ligne :
        //    - on fait une requête vérifiant si l'email est en base
        //    - si oui alors on fait le remplacement et on log ce remplacement
        //    - si non alors on passe

        // fin du traitement et résumé


        $this->enableLogs();

        $path = "../data/emailInra/conversion_email.csv";

        $this->verbose('*********************************************');
        $this->verbose('**    Boucle de remplacement des emails    **');
        $this->verbose('*********************************************');
        $this->verbose('> Début du script: ' . date("H:i:s" . $this->_init_time));

        $dbCAS = new PDO('mysql:host=localhost;dbname=CAS_dev_users', 'username', 'password', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));


        $handle = fopen($path,'r');

        if (is_file($path) && $handle !== false) {
            $i=0;
            $count = 0;
            while(($data = fgetcsv($handle,1000,';'))){
                if ($i>0) {
                    //data[0] => given name
                    //data[1] => surname
                    //data[2] => email
                    //data[3] => proxy email 1
                    //data[4] => proxy email 2
                    //data[5] => proxy email 3
                    //data[6] => proxy email 4

                    // requete à la base pour trouver les proxy 1,2,3
                    // nettoyage des adresses ( minuscules, sans espace, avec suppression du préfixe, et neutralisation des apostrophes)
                    $data[2] = $this->clean($data[2]);
                    $data[3] = $this->clean($data[3]);
                    $data[4] = $this->clean($data[4]);
                    $data[5] = $this->clean($data[5]);
                    $data[6] = $this->clean($data[6]);

                    // Si au moins une des adresses proxy est différente de l'adresse principale
                    if (($data[2] !== $data[3] && $data[3]!=="") || ($data[2] !== $data[4]  && $data[4]!=="") || ( $data[2] !== $data[5]  && $data[5]!=="") || ( $data[2] !== $data[6]  && $data[6]!=="")) {
                        $sql = "Select uid from T_UTILISATEURS where ";
                        if ($data[2] != $data[3] && $data[3] != '') $sql .= "EMAIL = '" . $data[3] . "' OR ";
                        if ($data[2] != $data[4] && $data[4] != '') $sql .= "EMAIL = '" . $data[4] . "' OR ";
                        if ($data[2] != $data[5] && $data[5] != '') $sql .= "EMAIL = '" . $data[5] . "' OR ";
                        if ($data[2] != $data[6] && $data[6] != '') $sql .= "EMAIL = '" . $data[6] . "' OR ";

                        $sqlsearch = substr($sql, 0, -3);

                        $sqlsearch .= " ORDER BY TIME_MODIFIED DESC, TIME_REGISTERED DESC LIMIT 1 ";


                        $result = $dbCAS->query($sqlsearch);
                        if ($result === false)
                            $this->println("erreur sur la requete :".$sqlsearch);
                        else {
                            $result = $result->fetchAll(PDO::FETCH_COLUMN);

                            // si aucun résultat on passe
                            // si un résultat on remplace
                            // si plusieurs résultats ???????
                            if (count($result) == 1) {
                                //$this->println($sqlsearch);
                                $this->println('ancien mail trouvé pour l uid ' . $result[0] . '');
                                $count++;
                                $sqlupdate = " Update T_UTILISATEURS set EMAIL='" . $data[2] . "' WHERE UID='" . $result[0] . "'";
                                //$dbCAS->query($sqlupdate);
                                $this->println($sqlupdate);
                            } else if (count($result) > 1) {
                                $this->println(count($result).' comptes ont été trouvé : ');
                                $this->println($sqlsearch);
                                $count = $count + count($result);
                            }
                        }
                    }
                }
                $i++;

            }
            $this->println("nombre d'email modifié total : $count");

        }
    }


    /**
     * fonction de nettoyage de l'email
     * @param $email
     * @return string
     */
    public function clean($email){
        return trim(str_replace("'","\'", str_replace(['smtp:', 'sip:'], '', strtolower($email))));
    }






}

$script = new emailInraReplacement();
$script->run();

