<?php

$localopts = array(
    'date-s'  => "Traitement des stats du jour indiqué (yyyy-mm-dd; défaut: la veille)",
);

require_once __DIR__ . '/loadHalHeader.php';

if ($opts->date == false) {
    $date = date('Y-m-d', strtotime('-1 day'));
} else {
    preg_match('/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/', $opts->date, $matches);
    if ( isset($matches[1]) && isset($matches[2]) && isset($matches[3]) && checkdate($matches[2], $matches[3], $matches[1]) ) {
        $date = $opts->date;
    } else {
        help($opts);
        exit(1);
    }
}

$dbMaster = Hal_Db_Adapter_Stats::getAdapter(APPLICATION_ENV);
// Prépration de la db
// On écrit les stats de consultation dans la base de HAL. Seule la lecture se fait sur le réplicat pour des questions de performances

$Visit = $dbMaster->prepare("INSERT INTO STAT_VISITOR (`IP`,`ROBOT`,`AGENT`,`DOMAIN`,`CONTINENT`,`COUNTRY`,`CITY`,`LAT`,`LON`) VALUES (:IP,:ROBOT,:AGENT,:DOMAIN,:CONTINENT,:COUNTRY,:CITY,:LAT,:LON) ON DUPLICATE KEY UPDATE VID=LAST_INSERT_ID(VID)");

$Count = $dbMaster->prepare("INSERT INTO CV_STAT_COUNTER (`IDHAL`,`UID`,`VID`,`DHIT`,`COUNTER`) VALUES (:IDHAL,:UID,:VID,:DHIT,:COUNTER) ON DUPLICATE KEY UPDATE COUNTER=COUNTER+1");

if ( $debug ) {
    println("\tla date : ".$date);
    println("\trepertoire : ".PATHTEMPDOCS);
    println("Lancement du script pour la date : ".$date);
}

$lignes_traitees = 0;
$lignes_singnificatives=0;

foreach (new DirectoryIterator(PATHTEMPDOCS.'visite/') as $file) {
    if ( preg_match('/cv-'.str_replace('-','',$date).'-ccsd(wb|hal)[0-9]+\.log$/', $file->getFilename()) ) {
        $lignes_traitees = 0;
        if ( $debug ) {
            println("Ouverture du fichier : ".$file->getFilename());
        }
        $f = fopen($file->getPathname(),'r');
        while ( ($data = fgetcsv($f)) !== false ) { // idhal,IP,userAgent,uid,date
            if ( count($data) != 5 ) continue;  // Bad line...

            $ip = long2ip($data[1]);
            if ( filter_var($ip, FILTER_VALIDATE_IP)===false ) continue;

            try {
                $v = new Ccsd_Visiteurs($ip, $data[2]);
                $isRobot   = (int)$v->isRobot();
                if ($isRobot == 0) {
                    $vData = $v->getLocalisation();
                    $bind = array();
                    $bind[':IP']        = $data[1];
                    $bind[':ROBOT']     = $isRobot;
                    $bind[':AGENT']     = (string)$data[2];
                    $bind[':DOMAIN']    = $vData['domain'];
                    $bind[':CONTINENT'] = $vData['continent'];
                    $bind[':COUNTRY']   = $vData['country'];
                    $bind[':CITY']      = $vData['city'];
                    $bind[':LAT']       = $vData['lat'];
                    $bind[':LON']       = $vData['lon'];
                    $Visit->execute($bind);
                    $vid = $dbMaster ->lastInsertId();
                    if ($vid) {
                        $bind = array();
                        $bind[':IDHAL']   = $data[0];
                        $bind[':UID']     = $data[3];
                        $bind[':VID']     = $vid;
                        $bind[':DHIT']    = substr($data[4], 0, 7) . '-00';
                        $bind[':COUNTER'] = $counter = 1;
                        $Count->execute($bind);
                    } else {
                        println('Error in VISITOR insert');
                        continue;
                    }
                    $lignes_singnificatives++;
                }
                $lignes_traitees++;
            } catch (Exception $e) {}
        }

        if ($debug) {
            println($lignes_traitees." lignes ont été traitées !");
            println($lignes_singnificatives." lignes significatives !");
        }
        if ( $debug ) {
            println("Archivage du fichier : ".$file->getFilename());
        }
        fclose($f);
        $repArchive = PATHTEMPDOCS.'archive_visite/'.substr($date, 0, 4).'/'.substr($date, 5, 2);
        if ( !is_dir($repArchive) ) {
            mkdir($repArchive, 0777, true);
        }
        rename($file->getPathname(), $repArchive.'/'.$file->getFilename());
    }
}

debug("Le script s'est termine correctement!");
exit;

/////////////////////////////
function help($consoleOtps) {
    echo "** Script de traitement des stats de consultation des CV HAL **";
    echo PHP_EOL;
    echo $consoleOtps->getUsageMessage();
    exit;
}
