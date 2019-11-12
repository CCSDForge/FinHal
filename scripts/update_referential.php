<?php

$timeStart = microtime(true);
define('MAX_ROWS', '100');
$localopts = array(
    'test|t'   => 'Lance le script en mode test',
);

if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

$test    = isset($opts->test);

if ($test) {
    println("Can't test this script!");
    exit(1);
}
       
Ccsd_Referentiels_Update::lockRows(MAX_ROWS);
foreach ( Ccsd_Referentiels_Update::getLockedRows() as $row ) {
    $nb = Ccsd_Referentiels_Update::process($row);
    if ( $nb !== false ) {
        Ccsd_Referentiels_Update::done($row['UPDATEID']);
        echo "Ligne (".$row['UPDATEID'].") traitée : ".$row['REF'].", ".$row['CURRENTID'].", ".$row['DELETEDID']." - " . $nb . " document(s) impactés" . PHP_EOL;
    } else {
        Ccsd_Referentiels_Update::error($row['UPDATEID']);
        echo "! Ligne (".$row['UPDATEID'].") non traitée : ".$row['REF'].", ".$row['CURRENTID'].", ".$row['DELETEDID'] . PHP_EOL;
    }
}

$timeEnd = microtime(true);
$time = $timeEnd - $timeStart;

$exec_time = number_format($time, 3);
echo 'Début du script: ' . date("Y-m-d H:i:s", $timeStart) . PHP_EOL;
echo 'Fin du script: ' . date("Y-m-d H:i:s", $timeEnd) . PHP_EOL;
echo 'Script executé en ' . $exec_time . ' sec.' . PHP_EOL;
exit(0);

