#! /usr/bin/env /opt/php5/bin/php
<?php

# MediHAL=57
# inria=2, democrite=6, inserm=11, archivesic=13,lirmm=15, ird=19, pasteur=21, cea=26, dumas= 39, afssa=42, riip=54, brgm=65, pastel=68, icp=99
$sitesSelfModer = [ 2, 6, 11, 13, 15, 19, 21, 26, 39, 42, 54, 65, 68, 99 ];
$excludeTypeDoc = [ "'img'", "'mem'", "'presconf'", "'video'", "'map'", "'son'" ];
$userProdinra   = 132775;
$userBMC        = 326461;
$enteteByUser   = ";Myriam Azzegag;Nathalie Frick;Noëlle Gourgouillon;Stéphane Pernez;Marie-Madeleine Decompte";
$moderateurInist= [317128         , 317148       , 317145            , 329700        ,526471];
$magron=102696;
$gala=320368;

$subject = 'Rapport hebdomadaire sur la modération';
$toInist = ["StatModeration@ccsd.cnrs.fr"];
$toCcsd  = ["StatModerationAdmin@ccsd.cnrs.fr"];

$from = 'ccsd-tech@ccsd.cnrs.fr';

$sitesSelfModerSQL  = "(" . implode(",", $sitesSelfModer)  . ")";
$excludeTypeDocSQL  = "(" . implode(",", $excludeTypeDoc)  . ")";
$moderateurInistSQL = "(" . implode(",", $moderateurInist) . ")";

$opts=null; // phpStorm want to know that variable but it is define in loadHalHeader
$localopts = array(
                    'date|D-s'  => "Traitement des stats du jour indiqué (yyyy-mm-dd; défaut: la semaine precedente)",
                    'to|t-s'    => "Envoyer le mail aux destinataires (separes par virgule)",
                    'from|f-s'  => "Adresse d'expedition",
                    'nomail'    => "Pas d'envoie de mail",
                    'long'      => "Envoie des deux rapports (court et long), defaut, seulement le court",
                    'test'      => "Comme --nomail",
                );

if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}


define('SPACE', dirname(__FILE__) . '/../data/portail/hal/');
define('DEFAULT_SPACE', dirname(__FILE__) . '/../data/portail/default/');

$now     =  date("Y-m-d");
$long    = true;
if (isset($opts->from)) {
    $from   = $opts->from;
}
if (isset($opts->to))   {
    $toCcsd = $toInist = explode(',',$opts->to);
}
$date    = isset($opts->date) ? $opts->date : date('Y-m-d', strtotime('-7 day'));
$nomail  = isset($opts->nomail) || isset($opts->test);
/**
 * @param      $request
 * @param bool $multi
 * @return array
 */
function execandfetch($request, $multi=false) {
    global $db;
    $res = array();
    if ($multi) {
        $query = $db->prepare($request);
        if (!is_array($multi)) {
            $multi = [ $multi ];
        }
        foreach ($multi as $value) {
            if (is_array($value)) {
                $bind = $value;
            } else {
                $bind = [ $value ];
            }
            $query->execute($bind);
            $results = $query->fetchColumn();
            $res[] = "$results";
        }
    } else {
        $nb = $db->query($request)->fetchColumn();
        $res[] = "$nb";
    }
    return $res;

}

/**
 * @param        $request
 * @param        $title
 * @param bool   $multi
 * @param string $other
 * @return string
 */
function display_result($request, $title, $multi=false, $other='') {
    $res = "$title;";
    $results = execandfetch($request, $multi);
    return $res . implode($results, ";") . ";" . $other . "\n";
}

verbose("Rapport de $date a $now\n");

$limitInist="DOCUMENT.SID not in $sitesSelfModerSQL AND DOCUMENT.TYPDOC not in $excludeTypeDocSQL AND DOCUMENT.UID != $userProdinra";
$inist_report="Rapport d'activité pour la periode: $date - $now\n\n";


# Calcul de P
$P = execandfetch("select count(*) from DOCUMENT where DOCSTATUS =  0 or DOCSTATUS = 10")[0]; # Nb de dépôts total à modérer restant
# Calcul de Sigma de Mi (moderation sur la periode)
$Smi =  execandfetch("select count(*) from DOCUMENT where DATEMODER > ?  and FORMAT!='notice'", $date )[0];
# Calcul de Sigma de Di  (depot sur la periode)
$Sdi = execandfetch("select count(*) from DOCUMENT where DATESUBMIT > ?  and FORMAT!='notice'", $date)[0];
$N = $P  + $Smi  - $Sdi;

# Calcul de P_inist
$P_inist   = execandfetch("select count(*) from DOCUMENT where DOCSTATUS = 0 and $limitInist and FORMAT != 'notice'", "Nb de dépôts à modérer aujourd'hui par équipe INIST")[0] ;
# Calcul de Sigma de Mi (moderation sur la periode) pas seulement par Inist
$Smi_inist = execandfetch("select count(*) from DOCUMENT where DATEMODER > ? and $limitInist and FORMAT != 'notice'", $date )[0];
# Calcul de Sigma de Di  (depot sur la periode)
$Sdi_inist = execandfetch("select count(*) from DOCUMENT where DATESUBMIT > ? and $limitInist  and FORMAT != 'notice'", $date )[0];
$N_inist = $P_inist  + $Smi_inist  - $Sdi_inist;



$global_moderation = ";\"Dépot a moderer\nau $date\";\"Dépot depuis\n$date\";\"Modérés depuis\n$date\";\"Reste à modérer\"\n";
$global_moderation .= "Sur HAL;$N; $Sdi;$Smi;$P \n";
$global_moderation .= "Vu par Inist;$N_inist;$Sdi_inist;$Smi_inist;  $P_inist\n";

$inist_report.=$global_moderation;
$inist_report.="\n";


$aide_calcul="On compte, dans les historiques des documents, \ndes actions «moderation» en date > $date";
$ArrayDateModerateurInist = array_map(
    function($uid) {
        global $date;
        return array($date, $date, $uid);
    },
    $moderateurInist);
$tableau = "\n$enteteByUser;Total;;;;'$aide_calcul'\n";

$excelSumFormula = "=SOMME(INDIRECT(\"B\"&LIGNE()&\":F\"&LIGNE()))";
$tableau .= display_result("select count(*) from DOCUMENT left join DOC_LOG on DOC_LOG.DOCID = DOCUMENT.DOCID where DATEMODER > ? and DOC_LOG.LOGACTION = 'moderate' and DOC_LOG.DATELOG > ? and DOC_LOG.UID = ? ",
    "nb de dépôts mise en ligne par",
    $ArrayDateModerateurInist,
    $excelSumFormula);
$tableau .=  display_result("select count(*) from DOCUMENT left join DOC_LOG on DOC_LOG.DOCID = DOCUMENT.DOCID where DATEMODER > ? and DOC_LOG.LOGACTION = 'askmodif' and DOC_LOG.DATELOG > ? and DOC_LOG.UID = ?",
    "Nb de demandes de modification par" ,
    $ArrayDateModerateurInist,
    $excelSumFormula);
$tableau .=  display_result("select count(*) from DOCUMENT left join DOC_LOG on DOC_LOG.DOCID = DOCUMENT.DOCID where DATEMODER > ? and DOC_LOG.LOGACTION = 'notice' and DOC_LOG.DATELOG > ? and DOC_LOG.UID = ?",
    "Nb de chgmt notices par" ,
    $ArrayDateModerateurInist,
    $excelSumFormula);
$tableau .=  display_result("select count(*) from DOCUMENT left join DOC_LOG on DOC_LOG.DOCID = DOCUMENT.DOCID where DOCSTATUS = 0 and DOCUMENT.SID != 57 and DOCUMENT.UID != $userProdinra and DOC_LOG.UID = ?",
    "nb de refus depuis le debut par",
    $moderateurInist,
    $excelSumFormula);

$global_moderation .= $tableau;
$inist_report.= $tableau;

$global_moderation .= "\n";
$global_moderation .= display_result("select count(*) from DOCUMENT where DOCSTATUS =  0 or DOCSTATUS = 10",                 "Nb de dépôts total à modérer restant");
$global_moderation .= display_result("select count(*) from DOCUMENT where DOCSTATUS = 10 and DOCUMENT.UID != $userProdinra", "Nb de dépôts Arxiv à modérer restant");
$global_moderation .= display_result("select count(*) from DOCUMENT where DOCSTATUS = 10 and DOCUMENT.UID = $userProdinra",  "Nb de dépôts Prodinra (ancien) à modérer restant");
$global_moderation .= display_result("select count(*) from DOCUMENT where DOCSTATUS =  0 and DOCUMENT.UID = $userBMC",       "Nb de dépôts BMC à modérer restant");
$global_moderation .= display_result("select count(*) from DOCUMENT where DOCSTATUS =  0 and DOCUMENT.TYPDOC = 'img'",       "Nb de dépôts de type image à modérer restant");

$global_moderation .= "----\n";
$global_moderation .= display_result("select count(*) from DOCUMENT where DOCSTATUS = 0 and $limitInist ", "Nb de dépôts à modérer aujourd'hui par équipe INIST") ;
$global_moderation .= display_result("select count(*) from DOCUMENT where DATESUBMIT > ? and FORMAT != 'notice' and $limitInist", "Nb de dépôts pour Inist de type file cette semaine", $date);
$global_moderation .= display_result("select count(*) from DOCUMENT where DATEMODER  > ? and FORMAT != 'notice'", "Nb de dépôts moderes cette semaine", $date);
$global_moderation .= display_result("select count(*) from DOCUMENT where DOCSTATUS = 0 and NOT ($limitInist) ", "Nb de dépôts à modérer aujourd'hui hors équipe INIST") ;

$global_moderation .= "\n";
$global_moderation .= "\n";

$global_moderation .= "\n";

$query = $db->prepare("select  SCREEN_NAME, count(*) as C from DOCUMENT 
                              left join DOC_LOG on DOC_LOG.DOCID = DOCUMENT.DOCID 
                              left join USER  on USER.UID=DOC_LOG.UID 
                       where 
                              DATEMODER > '$date' and DOC_LOG.LOGACTION = 'moderate' and DOC_LOG.DATELOG > '$date'
                       Group by SCREEN_NAME
                       HAVING C>0
                       ORDER BY C DESC");
$query->execute();
$results = $query->fetchAll();

$global_moderation .= "Nb de dépôts mise en ligne sur;=SOMME(INDIRECT(\"B\"&LIGNE()+1&\":B\"&LIGNE()+100\n";
array_map(
        function($a) {
            global $global_moderation;
            $global_moderation .= $a['SCREEN_NAME'] .';'. $a['C'] . "\n";
            },
        $results);

verbose($inist_report);
verbose("\n\n\n-------------------------\n\n\n");
verbose($global_moderation);

$Bof=sprintf("%c%c%c",0xEF,0xBB, 0xBF);


if ($debug) {
    if ($nomail) {
        print "Pas d'envoie de mail\n";
    } else {
        print "Mails desactive en mode debug\n";
        print "Envoie a " . toString($toInist) . " de $from du rapport court\n";
        if ($long) {
            print "Envoie a " . toString($toCcsd) . " de $from du rapport long\n";
        }
    }
} else {
    if ($nomail) {
        print "Pas d'envoie de mail\n";
    } else {
        $mail = new Zend_Mail('UTF-8');
        $mail->addTo($toInist);
        $mail->setFrom($from);
        $mail->setSubject($subject);
        $at = $mail->createAttachment($Bof.$inist_report,'text/csv');
        $title = 'Rapport en piece jointe';
        $mail->setBodyText($title);
        $mail->setBodyHtml($title);
        $at->filename = "Moderation-report-$now.csv";
        $mail->send();
    
        if ($long) {
            $mail = new Zend_Mail('UTF-8');
            $mail->addTo($toCcsd);
            $mail->setFrom('ccsd-tech@ccsd.cnrs.fr');
            $mail->setSubject($subject);
            $at = $mail->createAttachment($Bof.$global_moderation,'text/csv');
            $mail->setBodyText($title);
            $mail->setBodyHtml($title);
            $at->filename = "Moderation-report-long-$now.csv";
            $mail->send();
        }
    }
}
/**
 * @param array|string $o
 * @return string
 */
function toString ($o) {
    if (is_string($o)) {
        return $o;
    }
    if (is_array ($o)) {
        return implode(' ', array_map("toString",$o)) ;
    }
    // Else we return empty string
    return "";
}
?>
    
