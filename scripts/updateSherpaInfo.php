<?php
/** Mise a jour de Sherpa REF_JOURNAL
 *  Le principe, on mets a jour les JOURNAL_LIMIT plus anciennes donnees
 *  IL FAUT donc absolument reecrire la date de "Derniere consultation" de Sherpa
 *  D'ou le SORT BY SHERPA_DATE
 *
 * Effet de bord, le jour ou plus de JOURNAL_LIMIT qui ne sont pas sur Sherpa, plus de mise a jour
 * Plus il y en a et moins la mise a jour se fait vite...  Car la date n'est pas mise a jour pour ceux la!!!
 */
$localopts = array(
        'issn|i=s' => ' ISSN de la revue à vérifier',
        'number|n=s' => ' nombre de revue max à vérifier',
        'test|t' => 'Mode test',
);

define( 'JOURNAL_LIMIT', 1000 );

require_once __DIR__ . '/loadHalHeader.php';

define('MODULE', SPACE_PORTAIL);
define('SPACE', SPACE_DATA . '/'. MODULE . '/' . PORTAIL . '/');

$test = isset($opts->t);
if ($opts->number == false) {
    $limit = JOURNAL_LIMIT;
} else {
    $limit = ($opts->number>JOURNAL_LIMIT) ? JOURNAL_LIMIT : (int)$opts->number;
}

Zend_Registry::set('languages', array('fr','en'));
Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));

Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'fr' ) );
Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'en' ) );

$sql = "SELECT JID,ISSN,JNAME,PUBLISHER FROM REF_JOURNAL WHERE ISSN != '' AND ISSN IS NOT NULL AND VALID='VALID'".( ($opts->issn != false) ? " AND ISSN = '".$opts->issn."'" : "" )." ORDER BY SHERPA_DATE ASC LIMIT ".$limit;
$arrayOfJournal = $db->fetchAll($sql);

if ( $opts->debug != false ) {
    Ccsd_Log::message("Nombre de revues à vérifier sur SHERPA : " . count($arrayOfJournal), true, 'INFO');
}
$nbnoinfo = 0;
foreach ( $arrayOfJournal as $journal ) {
    if ( $opts->debug != false ) {
        Ccsd_Log::message("Recupération des données sur Sherpa (ISSN : ".$journal['ISSN'].")", true, 'INFO');
    }
    $romeo = sherpaRomeoAPI($journal['ISSN']);
    if ( count($romeo) ) {
        $bind = [];
        // On prefere garder le titre et le publisher car le chercheur connait cela, pas l'Issn.
        // S'il se trompe dans l'Issn, on aurait perdu l'information importante
        /*if ($romeo['jname'] != $journal['JNAME'] ) {
            $bind['JNAME'] = $romeo['jname'];
        }
        if ($romeo['publisher'] != $journal['PUBLISHER']) {
            $bind['PUBLISHER'] = $romeo['publisher'];
        }*/
        if ($romeo['romeocolour'] != '') {
            $bind['SHERPA_COLOR'] = $romeo['romeocolour'];
        }
        if ($romeo['preprints'] != '') {
            $bind['SHERPA_PREPRINT'] = $romeo['preprints'];
        }
        if ($romeo['postprints'] != '') {
            $bind['SHERPA_POSTPRINT'] = $romeo['postprints'];
        }
        $bind['SHERPA_PRE_REST'] = implode('][', $romeo['prerestrictions']);
        $bind['SHERPA_POST_REST'] = implode('][', $romeo['postrestrictions']);
        $bind['SHERPA_COND'] = implode('][', $romeo['conditions']);
        $bind['SHERPA_DATE'] = date("Y-m-d");

        if ($test) {
            println('Fake Update: ' . $journal['JID'] . " (" . $journal['ISSN'] . ")");
            continue;
        }
        try {
            $db->update("REF_JOURNAL", $bind, "JID =" . $journal['JID']);
            Ccsd_Search_Solr_Indexer::addToIndexQueue([$journal['JID']], 'AUREHAL', 'UPDATE', Ccsd_Referentiels_Journal::$core);
            $docids = Ccsd_Referentiels_Journal::getRelatedDocid($journal['JID']);
            if (count($docids)) {
                Hal_Document::deleteCaches($docids);
                Ccsd_Search_Solr_Indexer::addToIndexQueue($docids, 'AUREHAL');
            }
            if ($opts->debug != false) {
                Ccsd_Log::message("Done (JID : " . $journal['JID'] . "). " . count($docids) . " document(s) reindexed)", true, 'INFO');
            }
        } catch (Exception $e) {
            if ($opts->debug != false) {
                Ccsd_Log::message("Error (JID : " . $journal['JID'] . "). " . $e->getMessage(), true, 'INFO');
            }
        }
    } else {
        $nbnoinfo ++;
        $db->update("REF_JOURNAL", [ 'SHERPA_DATE' => date("Y-m-d") ]  ,  "JID =" . $journal['JID']);
        if ($opts->debug != false) {
            Ccsd_Log::message("Done (JID : " . $journal['JID'] . "). No info on Sherpa", true, 'INFO');
        }
    }
}

println('Nombre de journaux non presents sur Sherpa:', $nbnoinfo, ($nbnoinfo > JOURNAL_LIMIT / 2 ? 'red' : 'green'));

exit();
    
function sherpaRomeoAPI($issn) {
    if ( $issn != '' ) {
        try {
            $romeo = @ new SimpleXMLElement(file_get_contents('http://www.sherpa.ac.uk/romeo/api29.php?issn=' . $issn . '&ak=Pcd695QRpIQ'));
            if ( isset($romeo->journals->journal->issn) && $romeo->journals->journal->issn == $issn ) {
                $out = ['jname'=>'', 'publisher'=>'', 'romeocolour'=>'', 'preprints'=>'', 'prerestrictions'=>[], 'postprints'=>'', 'postrestrictions'=>[], 'conditions'=>[]];
                $out['jname'] = (string)$romeo->journals->journal->jtitle;
                $out['publisher'] =@ (string)$romeo->publishers->publisher->name;
                $out['romeocolour'] =@ (string)$romeo->publishers->publisher->romeocolour;
                $out['preprints'] =@ (string)$romeo->publishers->publisher->preprints->prearchiving;
                if ( isset($romeo->publishers->publisher->preprints->prerestrictions->prerestriction) ) {
                    foreach ( $romeo->publishers->publisher->preprints->prerestrictions->prerestriction as $r ) {
                        $out['prerestrictions'][] = strip_tags((string)$r);
                    }
                }
                $out['postprints'] =@ (string)$romeo->publishers->publisher->postprints->postarchiving;
                if ( isset($romeo->publishers->publisher->postprints->postrestrictions->postrestriction) ) {
                    foreach ( $romeo->publishers->publisher->postprints->postrestrictions->postrestriction as $r ) {
                        $out['postrestrictions'][] = strip_tags((string)$r);
                    }
                }
                if ( isset($romeo->publishers->publisher->conditions->condition) ) {
                    foreach ( $romeo->publishers->publisher->conditions->condition as $c ) {
                        $out['conditions'][] = strip_tags((string)$c);
                    }
                }
                return $out;
            }
        } catch (Exception $e) {
            return [];
        }
    }
    return [];
}