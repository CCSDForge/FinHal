<?php

//set_time_limit(0);

/**
 * Récupération/Affichage des paramètres dans la console
 */
function println($s = '', $v = '', $color = '', $newline=true)
{
    global $nocolor;
    $maybeNewline='';
    if ($newline) {
        $maybeNewline=PHP_EOL;
    }
    if (($v == '') || $nocolor) {
        echo $s, $v, $maybeNewline;
    } else {
        $color_array = array( 'red' => '31m', 'green' => '32m', 'yellow' => '33m', 'blue' => '34m' );
        $colorStart   = "\033[";
        $colorEnd     = "\033[0m";
        $c            = isset($color_array[$color]) ? $color_array[$color] : '30m';
        echo $s,$colorStart,$c,$v,$colorEnd, $maybeNewline;
    }
}

/** add debug */
function debug($msg, $colormsg = '', $color = '', $newline=true) {
    println($msg, $colormsg, $color, $newline);
}

$timestart = microtime(true);

debug('');
debug('', '----------------------------------------', 'yellow');
debug('> Début du script: ', date("H:i:s", $timestart), '');
debug('', '----------------------------------------', 'yellow');


// token pour l'utilisateur sdenoux
//$token_auth = 'ae436f72c03df187ce39f453875ecba4';
$token_auth='5c29dc447214ee8bbe6dea7c6ba5ad0d';

$res = 0;

for ($i=1;$i<90000;$i++) {
// we call the REST API
    $url = "http://piwik-local.ccsd.cnrs.fr/";
    $url .= "?module=API";
    $url .= "&format=json";
    $url .= "&token_auth=$token_auth";
    $url .= "&method=VisitsSummary.getVisits";
    $url .= "&idSite=1";
    $url .= "&period=year";
    $url .= "&date=today";
    $id = '';
    
    for($j=0;$j<8-strlen($i);$j++) {
        $id.='0';
    }

    $id .= $i;
    $url .= "&segment=pageTitle=@hal-".$id;



    $fetched = file_get_contents($url);
    $content = json_decode($fetched);

// case error
    if (!$content) {
        print("Error, content fetched = " . $fetched);
    }

//print("<h1>Résultat de la requete : ".$url."</h1>\n");
//print($content);
    foreach ($content as $row) {
        $res+=$row;
    }
}

print($res);

$timeend = microtime(true);
$time = $timeend - $timestart;
debug('', '----------------------------------------', 'yellow');
debug('> Script executé en ' . number_format($time, 3) . ' sec.');
debug('', '----------------------------------------', 'yellow');