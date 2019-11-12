<?php

$localopts = array(
    'date-s'  => "Traitement des stats du jour indiqué (yyyy-mm-dd; défaut: la veille)",
);

require_once __DIR__ . '/loadHalHeader.php';

/** @var Zend_Console_Getopt */
if ($opts->date) {
    preg_match('/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/', $opts->date, $matches);
    if ( isset($matches[1]) && isset($matches[2]) && isset($matches[3]) && checkdate($matches[2], $matches[3], $matches[1]) ) {
        $date = $opts->date;
    } else {
        help($opts);
        exit (1);
    }
} else {
    $date = date('Y-m-d', strtotime('-1 day'));
}

// Prépration de la db
// On écrit les stats de consultation dans la base de HAL. Seule la lecture se fait sur le réplicat pour des questions de performances
$dbMaster = Hal_Db_Adapter_Stats::getAdapter(APPLICATION_ENV);

$Visit = $dbMaster->prepare("INSERT INTO STAT_VISITOR (`IP`,`ROBOT`,`AGENT`,`DOMAIN`,`CONTINENT`,`COUNTRY`,`CITY`,`LAT`,`LON`) VALUES (:IP,:ROBOT,:AGENT,:DOMAIN,:CONTINENT,:COUNTRY,:CITY,:LAT,:LON) ON DUPLICATE KEY UPDATE VID=LAST_INSERT_ID(VID)");
$Count = $dbMaster->prepare("INSERT INTO DOC_STAT_COUNTER (`DOCID`,`UID`,`CONSULT`,`FILEID`,`VID`,`DHIT`,`COUNTER`) VALUES (:DOCID,:UID,:CONSULT,:FILEID,:VID,:DHIT,:COUNTER) ON DUPLICATE KEY UPDATE COUNTER=COUNTER+1");

if ( $debug ) {
    println("Lancement du script pour la date : ".$date);
}

foreach (new DirectoryIterator(PATHTEMPDOCS.'visite/') as $file) {
    $lignes_significatives=0;
    $lignes_traitees = 0;
    if ( preg_match('/document-'.str_replace('-','',$date).'-ccsd(wb|hal)[0-9]+\.log$/', $file->getFilename()) ) {
        if ( $debug ) {
            println("Ouverture du fichier : ".$file->getFilename());
        }
        $f = fopen($file->getPathname(),'r');
        while ( ($data = fgetcsv($f)) !== false ) { // docid,IP,userAgent,uid,date,format,fileid
            if ( count($data) != 7 ) {
                continue;
            }

            $ip = long2ip($data[1]);
            if ( filter_var($ip, FILTER_VALIDATE_IP)===false ) {
                continue;
            }

            try {
                $v = new Ccsd_Visiteurs($ip, $data[2]);
                $isRobot = $v->isRobot();
                if ($isRobot == 0) {
                    $vData = $v->getLocalisation();
                    $bind = array();
                    $bind[':IP'] = $data[1];
                    $bind[':ROBOT'] = (int)$isRobot;
                    $bind[':AGENT'] = (string)$data[2];
                    $bind[':DOMAIN'] = $vData['domain'];
                    $bind[':CONTINENT'] = $vData['continent'];
                    $bind[':COUNTRY'] = $vData['country'];
                    $bind[':CITY'] = $vData['city'];
                    $bind[':LAT'] = $vData['lat'];
                    $bind[':LON'] = $vData['lon'];
                    $Visit->execute($bind);
                    $vid = $dbMaster->lastInsertId();
                    if ($vid) {
                        $bind = array();
                        $bind[':DOCID'] = $data[0];
                        $bind[':UID'] = $data[3];
                        $bind[':CONSULT'] = $data[5];
                        $bind[':FILEID'] = $data[6];
                        $bind[':VID'] = $vid;
                        $bind[':DHIT'] = substr($data[4], 0, 7) . '-00';
                        $bind[':COUNTER'] = 1;
                        $Count->execute($bind);
                    }
                    $lignes_significatives++;
                }
                $lignes_traitees++;
            } catch (Exception $e) {}
        }
        if ($debug) {
            println($lignes_traitees." lignes ont été traitées !");
            println($lignes_significatives." lignes significatives !");
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

if ( $debug ) {
    println("Le script s'est termine correctement!");
}
exit;

/////////////////////////////
function help($consoleOtps) {
    echo "** Script de traitement des stats de consultation des ressources HAL **";
    echo PHP_EOL;
    echo $consoleOtps->getUsageMessage();
    exit;
}
