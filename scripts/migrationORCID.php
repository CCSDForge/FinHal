<?php
/**
 * Created by PhpStorm.
 * User: genicot
 * Date: 04/03/19
 * Time: 14:34
 */

/**
 * Class prodinraImport
 * Classe permettant de gérer le flux d'import de prodinra
 */


if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require __DIR__ . "/../library/Hal/Script.php";



class migrationORCID extends Hal_Script
{



    /**
     */
    protected $options = array(

    );


    public function main($getOpt){

        $this->enableLogs();

        $path = "../data/notices_prodinra/";

        $this->verbose('****************************************');
        $this->verbose('**      Boucle de transformation      **');
        $this->verbose('**              ORCID                 **');
        $this->verbose('****************************************');
        $this->verbose('> Début du script: ' . date("H:i:s" . $this->_init_time));

        // initialisation de la connexion à la BDD CAS
        $dbCAS = new PDO('mysql:host=localhost;dbname=CAS_dev_users', 'username', 'password', array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"));


        // initialisation de la connexion à la BDD HAL
        $db = Zend_Db_Table::getDefaultAdapter();

        // construction de la requete permettant de récupérer tous les comptes possédant un identifiant externe
        $sqlsearch = 'select * from REF_IDHAL inner join REF_IDHAL_IDEXT on REF_IDHAL.IDHAL=REF_IDHAL_IDEXT.IDHAL WHERE REF_IDHAL_IDEXT.SERVERID = 4 ';
        $result = $db->query($sqlsearch);
        if ($result === false) {
            $this->println("erreur sur la requete :" . $sqlsearch);
        }
        else {
            $result = $result->fetchAll();
            // Parcours des résultats
            $this->println("Début du traitement d'insertion ");
            $i=0;
            foreach ($result as $association){
                $valid=true;
                $email = null;
                $name = null;
                $uidCcsd = $association['UID'];
                $orcid = $association['ORCID'];

                // Si identifiant ORCID et UID alors on peut créer l'association et la sauver
                $asso = new \Ccsd\Auth\Asso\Orcid($orcid,$uidCcsd,$name,$email,$valid);
                //$asso->save();

                $i++;
                $this->println('traitement de la ligne '.$i.' effectué');

            }
        }




    }




}

$script = new migrationORCID();
$script->run();