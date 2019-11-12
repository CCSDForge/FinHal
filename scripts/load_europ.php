<?php
/**
 * Recuperation des projets Europeens
 *
 */

$rootUrl = 'http://api.openaire.eu/oai_pmh';
$localopts = array(
                   'listfunder|l' => 'List accepted funder', 
                   'test'         => 'Testing mode',
                   'noop'         => 'Testing mode',
                   'dryrun'       => 'Testing mode',
                   'maxpage-d'    => 'Max oai page from Openaire to retreive (test purpose)'
        );

if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

/** @var Zend_Console_Getopt $opts */
$test    = isset($opts->test) || isset($opts->noop) || isset($opts->dryrun);

$maxpage = $test ? 2 : '';    // En test, 2 par default, vide si pas de test
$maxpage = isset($opts->maxpage) ? $opts->maxpage : $maxpage;

$unchanged = 0;
$changed   = 0;
$inserted  = 0;
$badFUnder = 0;
header('Content-Type: plain/text; charset=UTF-8');

if (isset($opts->listfunder)) {
    print "Accepted funders:\n";
    print "\t EC      : European Concil\n";
    print "\t WT      : \n";
    print "\t FCT     : \n";
    print "\t HORIZON : \n";
    print "\t NHMRC   : National Health and Medical Research Council\n";
    print "\t ARC     : \n";
    exit;
}

verbose("Environnement: " .  APPLICATION_ENV);
verbose("DB: " . HAL_NAME);
if ($test) {
    verbose("En test, seulement $maxpage pages de projets seront recuperees");
}

$stmtEuropI = $db->prepare("INSERT INTO `REF_PROJEUROP` (NUMERO,ACRONYME,TITRE,FUNDEDBY,SDATE,EDATE,CALLID,VALID) VALUES (:NUMERO,:ACRONYME,:TITRE,:FUNDEDBY,:SDATE,:EDATE,:CALLID,:VALID)");
$stmtEuropU = $db->prepare("update `REF_PROJEUROP` set NUMERO=:NUMERO,ACRONYME=:ACRONYME,TITRE=:TITRE,FUNDEDBY=:FUNDEDBY,SDATE=:SDATE,EDATE=:EDATE,CALLID=:CALLID,VALID=:VALID where PROJEUROPID=:PROJEUROPID");

// Récupération des ProjEurop : OAI
$records = oaiOpenaireListrecords($debug);
$changed   = 0;
$unchanged = 0;
$inserted  = 0;

if ( count($records) ) {
    verbose(count($records).' projets Européens à intégrer/modifier');
    foreach( $records as $pe ) {
        $pe  = normalize_pe($pe);
        $ret = addOrUpdateProject($pe);
        switch ($ret) {
            case 'm':
                $changed++;
                break;     //Modif
            case 'u':
                $unchanged++;
                break;   //Unchange
            case 'i':
                $inserted++;
                break;   //Inserted
            default:
                println("PANIC: Bad return for addOrUpdateProject ($ret)");
        }
    }
}
println();

verbose("$unchanged projets inchanges");
verbose("$changed projets modifies");
verbose("$inserted projets nouveaux");
verbose("$badFUnder projets sans financeur connu");

/**
 * @param array
 * @return array
 */
function normalize_pe($pe) {
  if (empty($pe['TITRE'])) {
      $pe['TITRE'] = $pe['ACRONYME'];
      $pe['ACRONYME'] = '';
  }
  return $pe;
}


/**
 * @param array
 */
function addOrUpdateProject($pe) {
    global $db;
    global $test;
    global $debug;
    global $stmtEuropI;
    global $stmtEuropU;
    $res = '';
    $bind = [];
    $md5 = md5(strtolower('numero'.$pe['NUMERO'].'acronyme'.$pe['ACRONYME'].'titre'.$pe['TITRE']));
    $exist = $db->query("SELECT * from `REF_PROJEUROP` where MD5 = UNHEX('" . $md5 ."')")->fetchAll();
    $bind[':TITRE']    = $pe['TITRE'];
    $bind[':ACRONYME'] = $pe['ACRONYME'];
    $bind[':NUMERO']   = $pe['NUMERO'];
    $bind[':FUNDEDBY'] = $pe['FUNDEDBY'];
    $bind[':CALLID']   = $pe['CALLID'];
    $bind[':SDATE']    = Ccsd_Tools_String::stringToMysqlDate($pe['SDATE']);
    $bind[':EDATE']    = Ccsd_Tools_String::stringToMysqlDate($pe['EDATE'], '9999-12-31');
    $bind[':VALID']    = 'VALID';
    $display = (defined($pe['ACRONYME']) && ($pe['ACRONYME'] != '')) ? $pe['ACRONYME']: $pe['NUMERO'];
    if ( $exist ) {
        $info=$exist[0];
        if (   ($info['FUNDEDBY'] == $bind[':FUNDEDBY'])
            && ($info['CALLID']   == $bind[':CALLID'])
            && ($info['SDATE']    == $bind[':SDATE'])
            && ($info['EDATE']    == $bind[':EDATE'])
            && ($info[''] == $bind[':'])) {
            // Le projet n'est pas modifie.
            verbose("Projet $display inchange");
            $ret = 'u';
        } else {
            $bind[':PROJEUROPID'] = $exist['PROJEUROPID'];
            verbose("Update $display" . $pe['TITRE']);
            if (!$test) {
                $res = $stmtEuropU->execute( $bind );
            }
            $ret = 'm';
        }
    } else {
        verbose("Insert $display (" . $pe['TITRE'] . ")");
        if (!$test) {
            $res = $stmtEuropI->execute( $bind );
        }
        $ret = 'i';
    }
    if ($debug && ( $res != 'u' ) && ( $stmtEuropI->errorInfo()[0] != 23000 )) {
        // Modif de base et probleme de mise a jour: on donne l'erreur SQL
        print_r($pe);
        print_r($stmtEuropI->errorInfo());
    }
    return ($ret);
}

/**
 * @param bool $debug
 * @return array
 */
function oaiOpenaireListrecords($debug) {
    global $test;
    global $maxpage;
    global $rootUrl;
    global $badFUnder;
    $records = [];
    $funders = [];
    $i = 0;
    $page = 1;
    $repositoryUrl = $rootUrl;
    $request = $repositoryUrl.'?verb=ListRecords&set=projects&metadataPrefix=oaf';
    do {
        $continue = false;
        debug($request);
        $xml = oai_pmh_get($request);
        libxml_use_internal_errors(true);
        $s = simplexml_load_string($xml, 'SimpleXMLElement', 0, 'oai', true);
        if ( !$s ) {
            echo 'SimpleXML Error'.PHP_EOL;
            echo $xml;
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                echo display_xml_error($error, explode("\n", $xml));
            }
            libxml_clear_errors();
        }
        if ( isset($s->error) ) {
            if ( 'noRecordsMatch' != $s->error['code'] ) {
                die("Error from server\n code: " . $s->error['code'] . "\n value:  " . (string)$s->error."\n");
            }
        } else {
            $s->registerXPathNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
            $s->registerXPathNamespace('oaf', 'http://namespace.openaire.eu/oaf');
            foreach( $s->xpath('//oaf:project') as $project ) {
                if ( $debug ) {
                    println($project->originalId);
                    $originalId = ( strpos((string)$project->originalId, ':') !== false ) ? strstr((string)$project->originalId, ':', true) : (string)$project->originalId;
                    if ( !in_array(str_replace('_','',strtoupper($originalId)), $funders) ) {
                        $funders[] = str_replace('_','',strtoupper($originalId));
                    }
                }
                if ( $project ) {
                    $contracttype =@ ( strpos((string)$project->contracttype->attributes()['schemename'], ':') !== false ) ? strstr((string)$project->contracttype->attributes()['schemename'], ':', true) : (string)$project->contracttype->attributes()['schemename'];
                    if ( $contracttype == '' ) {
                        $contracttype =@ (string)$project->fundingtree->funder->shortname;
                    }
                    $funder = substr(strtoupper($contracttype), 0, 7);
                    switch ( $funder ) {
                        case 'EC':
                            $records[$i]['FUNDEDBY'] = 'EC:'.(string)$project->fundingtree->funding_level_2->parent->funding_level_1->parent->funding_level_0->name.':'.(string)$project->fundingtree->funding_level_2->name;
                            break;
                        case 'WT':
                            $records[$i]['FUNDEDBY'] = 'WT::'.(string)$project->fundingtree->funding_level_1->name;
                            break;
                        case 'FCT':
                            $records[$i]['FUNDEDBY'] = 'FCT::'.(string)$project->fundingtree->funding_level_1->name;
                            break;
                        case 'HORIZON':
                            $records[$i]['FUNDEDBY'] = (string)$project->fundingtree->funding_level_0->name;
                            break;
                        case 'NHMRC':
                            $records[$i]['FUNDEDBY'] = 'NHMRC::'.(string)$project->fundingtree->funding_level_0->name;
                            break;
                        case 'ARC':
                            $records[$i]['FUNDEDBY'] = 'ARC::'.(string)$project->fundingtree->funding_level_0->name;
                            break;
                        default:
                            println('FUNDER ERROR:'.$funder);
                            $badFUnder++;
                            // Mais on garde qd meme...
                    }
                    $records[$i]['NUMERO']   = (string)$project->code;
                    $records[$i]['ACRONYME'] = (string)$project->acronym;
                    $records[$i]['TITRE']    = (string)$project->title;
                    $records[$i]['SDATE']    = (string)$project->startdate;
                    $records[$i]['EDATE']    = (string)$project->enddate;
                    $records[$i]['CALLID']   = (string)$project->callidentifier;
                    $i++;
                }
            }
        }
        if ( isset($s->ListRecords->resumptionToken) && (string)$s->ListRecords->resumptionToken != '' ) {
            $continue = true;
            $request = $repositoryUrl.'?verb=ListRecords&resumptionToken='.urlencode((string)$s->ListRecords->resumptionToken);
            $resumptionToken_attributes = $s->ListRecords->resumptionToken->attributes();
            if ( $debug ) {
                println('token ('.(string)$s->ListRecords->resumptionToken.') : '.$resumptionToken_attributes['cursor'].'/'.$resumptionToken_attributes['completeListSize']);
            }
        }
        if ($test && ($page >= $maxpage)) {
            $continue = false;
        }
        $page ++;
        print("\n$page / $maxpage  ($i records)");
    } while ( $continue );
    if ( $debug ) {
        print_r($funders);
    }
    return $records;
}

/**
 * @param $request
 * @return string
 */
function oai_pmh_get($request) {
    $curl = curl_init($request);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 120);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 120);
    $return = curl_exec($curl);
    if ( curl_errno($curl) ) {
        die(curl_error($curl));
    }
    curl_close($curl);
    return trim($return);
}

/**
 * @param $error
 * @param $xml
 * @return string
 */
function display_xml_error($error, $xml) {
    $return  = $xml[$error->line - 1] . "\n";
    $return .= str_repeat('-', $error->column) . "^\n";

    switch ($error->level) {
        case LIBXML_ERR_WARNING:
            $return .= "Warning $error->code: ";
            break;
        case LIBXML_ERR_ERROR:
            $return .= "Error $error->code: ";
            break;
        case LIBXML_ERR_FATAL:
            $return .= "Fatal Error $error->code: ";
            break;
        default:
            println("PANIC: Xml error Level not knowed ($error->level)");
    }

    $return .= trim($error->message) .
        "\n  Line: $error->line" .
        "\n  Column: $error->column";

    if ($error->file) {
        $return .= "\n  File: $error->file";
    }

    return "$return\n\n--------------------------------------------\n\n";
}
