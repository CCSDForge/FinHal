<?php
// Script Quotidien permettant la création d'un fichier contenant les fiches HAL (avec un labo du CNRS) vers LabIntel
// Nom du fichier créé : IM_PRDPUB_FI1AAAAMMJJ.xml (AAAAMMJJ étant la date au format anglais)

$localopts = array(
        'date-s'  => "Traitement des dépôts du jour indiqué (yyyy-mm-dd; défaut: la veille)",
        'test|t' => 'Mode test',
);


if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

define('LABINTEL_ROOT_FILE', '/sites/labintelhal/current/');

$docType = ['ART'=>'ART', 'COMM'=>'COL', 'POSTER'=>'COL', 'PRESCONF'=>'COL', 'OUV'=>'OUV', 'COUV'=>'COV', 'DOUV'=>'OUV', 'PATENT'=>'BRE', 'THESE'=>'TRU', 'HDR'=>'TRU', 'MEM'=>'TRU', 'ETABTHESE'=>'TRU'];

if ($opts->date == false) {
    $date = date('Y-m-d', strtotime('-1 day'));
} else {
    $date = $opts->date;
    preg_match('/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/', $date, $matches);
    if ( isset($matches[1]) && isset($matches[2]) && isset($matches[3]) && checkdate($matches[2], $matches[3], $matches[1]) ) {
        // Ok
    } else {
        help($opts);
    }
}
$strDate = str_replace('-', '', $date);

Zend_Registry::set('languages', array('fr','en','es','eu'));
Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));

Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'fr' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'en' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'es' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'eu' ) );

if ( $debug ) {
	println('Export XML vers LabIntel pour date = '.$date);
	println('Lancement du script ('.date('d/m/Y \à H:i:s').')');
}

// Nouveauté ou mise à jour
if ( $file = fopen(LABINTEL_ROOT_FILE.'IM_PRDPUB_FI1'.$strDate.'.xml', 'w') ) {
	if ( $debug ) {
		println('Ouverture du fichier '.LABINTEL_ROOT_FILE.'IM_PRDPUB_FI1'.$strDate.'.xml');
	}
	fputs($file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
    fputs($file, '<PUBCNRS>'.PHP_EOL);

    $solrRequest = "q=*&fq=status_i:11&fq=".urlencode("NOT(docType_s:UNDEFINED OR docType_s:IMG OR docType_s:SON OR docType_s:VIDEO OR docType_s:MAP)")."&fq=structAcronym_s:CNRS&fq=modifiedDate_s:" . urlencode('["' . $date . ' 00:00:00" TO "'.$date.' 23:59:59"]') . "&rows=1000000&wt=phps&fl=docid&omitHeader=true";
    if ($debug) {
        println('> Requête SolR : '. $solrRequest);
    }
    $res = unserialize(Ccsd_Tools::solrCurl($solrRequest));
    if (isset($res['response']['numFound']) && isset($res['response']['docs'])) {
        if ( $debug ) {
            println('# de notices à exporter : '.$res['response']['numFound']);
        }
        foreach ($res['response']['docs'] as $d) {
            $document = Hal_Document::find($d['docid']);
            if ( false === $document ) {
                continue;
            }
            $type_s = '';
            if ( !array_key_exists($document->getTypDoc(), $docType) ) {
                $type_p = 'AUT';
            } else {
                $type_p = $docType[$document->getTypDoc()];
                if ( $type_p == 'ART' ) {
                    $peer = $document->getMeta('peerReviewing');
                    $type_s = ( $peer != '' ) ? ( ($peer == 1) ? 'ACL' : 'SCL') : '';
                }
                if ( $type_p == 'COL' ) {
                    $invite = $document->getMeta('invitedCommunication');
                    if ( $invite != '' && $invite == 1 ) {
                        $type_s = 'INV';
                    } else {
                        $proceedings = $document->getMeta('proceedings');
                        $type_s = ( $proceedings != '' ) ? ( ($proceedings == 1) ? 'ACT' : 'COM' ) : '';
                    }
                }
            }
            foreach ( $document->getCodeCNRSStructures() as $code_cnrs ) {
                if ($debug) {
                    println('  Labo -> ' . $code_cnrs);
                }
                fputs($file, output_publication($document, $code_cnrs, $type_p, $type_s));
            }
        }
    }
	fputs($file, '</PUBCNRS>');
	fclose($file);

	if ( $debug ) {
		println('Fermeture du fichier '.LABINTEL_ROOT_FILE.'IM_PRDPUB_FI1'.$strDate.'.xml');
	}
}
// Articles supprimés
if ( $file = fopen(LABINTEL_ROOT_FILE.'IM_PRDSUPUB_FI1'.$strDate.'.xml', 'w') ) {
	if ( $debug ) {
		println('Ouverture du fichier '.LABINTEL_ROOT_FILE.'IM_PRDSUPUB_FI1'.$strDate.'.xml');
	}
	fputs($file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n");
	fputs($file, '<PUBCNRS>'.PHP_EOL);
    try {
        fputs($file, output_deleted_publications($date, $debug));
    } catch ( Exception $e ) {}
	fputs($file, '</PUBCNRS>');
	fclose($file);

	if ( $debug ) {
		println('Fermeture du fichier '.LABINTEL_ROOT_FILE.'IM_PRDSUPUB_FI1'.$strDate.'.xml');
	}
}

if ( $debug ) {
	println('Fin du script ('.date('d/m/Y \à H:i:s').')');
}

/**
 * Formatage de la ressource pour LabIntel
 *
 * @param Hal_Document $d
 * @param string $code_cnrs
 * @param string $type_p
 * @param string $type_s
 * @return string
 */
function output_publication( $d, $code_cnrs, $type_p, $type_s ) {
	$xml = '';
	// on n'exporte cette publication que si elle est complète.
	$auteurs = $d->getListAuthors(5);
	if ( $code_cnrs != '' && $type_p != '' && $auteurs != '' ) {
		$xml .= '<DESC_REF>'.PHP_EOL;
        $xml .= '<INFO_REF>'.PHP_EOL;
        $xml .= '<COD_UNIT>'.Ccsd_Tools_String::stripCtrlChars(Ccsd_Tools_String::xmlSafe($code_cnrs)).'</COD_UNIT>'.PHP_EOL;
		$xml .= '<TYPE_P>'.$type_p.'</TYPE_P>'.PHP_EOL;
		$xml .= '<TYPE_S>'.$type_s.'</TYPE_S>'.PHP_EOL;
		$xml .= '<STATUT>PUB</STATUT>'.PHP_EOL;
		$xml .= '<DATE_MAJ>'.$d->getLastModifiedDate('dd/MM/yyyy', 'fr').'</DATE_MAJ>'.PHP_EOL;
		$xml .= '<IDT_HAL>'.$d->getId(true).'</IDT_HAL>'.PHP_EOL;
		$xml .= '</INFO_REF>'.PHP_EOL;
		$xml .= '<TITR_REF>'.Ccsd_Tools_String::stripCtrlChars(Ccsd_Tools_String::xmlSafe($d->getMainTitle())).'</TITR_REF>'.PHP_EOL;
		$xml .= '<ANN_PUB>'.substr($d->getProducedDate(), 0,4).'</ANN_PUB>'.PHP_EOL;
		$xml .= '<LANG_REF>'.strtoupper($d->getMeta('language')).'</LANG_REF>'.PHP_EOL;
        $xml .= '<CONTRAT/>'.PHP_EOL;
		$xml .= '<AUTEURS>'.PHP_EOL;
		$xml .= '<LIST_AUT>'.Ccsd_Tools_String::stripCtrlChars(Ccsd_Tools_String::xmlSafe($auteurs)).'</LIST_AUT>'.PHP_EOL;
		$xml .= '</AUTEURS>'.PHP_EOL;
		$xml .= '<COLL_REF>'.PHP_EOL;
		$xml .= '<COL_LIB>'.Ccsd_Tools_String::stripCtrlChars(Ccsd_Tools_String::xmlSafe(strip_tags($d->getCitation()))).'</COL_LIB>'.PHP_EOL;
		$xml .= '</COLL_REF>'.PHP_EOL;
		$xml .= '</DESC_REF>'.PHP_EOL;
	}
    return $xml;
}

/*
 * Formatage des ressources supprimées pour LabIntel
 *
 * @param string
 * @param string
 * @return string
 */
function output_deleted_publications( $date, $debug ) {
    $db = Zend_Db_Table::getDefaultAdapter();
	// documents supprimés de HAL
	$sql = $db->select()->from('DOC_DELETED', 'IDENTIFIANT')->where("DATE_FORMAT(DATEDELETED, '%Y-%m-%d') = '".$date."'");
	$deleted = $db->fetchAll($sql);
    if ( $debug ) {
		println('# de notices supprimées : '.count($deleted));
	}
    $xml = "";
	foreach ( $deleted as $id ) {
		$xml .= '<DESC_REF>'.PHP_EOL;
		$xml .= '<INFO_REF>'.PHP_EOL;
		$xml .= '<IDT_HAL>'.$id['IDENTIFIANT'].'</IDT_HAL>'.PHP_EOL;
		$xml .= '</INFO_REF>'.PHP_EOL;
		$xml .= '</DESC_REF>'.PHP_EOL;
	}
	return $xml;
}

/////////////////////////////
function help($consoleOtps) {
    echo "** Script de traitement pour LabIntel **";
    echo PHP_EOL;
    echo $consoleOtps->getUsageMessage();
    exit;
}

