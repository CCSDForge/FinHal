<?php
/**
 * Created by PhpStorm.
 * User: yannou
 *
 * cron qui attend
 */

// Non standard en general: choix de la ss-application
putenv('APPLICATION_DIR=application-halms');

/* Environnements */
$listActions = array('dcl', 'pmc', 'hal', 'generate');
$defaultAction = 'dcl';

$localopts = array(
    'action|a-s' => 'Action (' . implode('|', $listActions). ') (défaut: ' . $defaultAction . ')',
    'docid-i' => 'Identifiant du document (pour generate uniquement)',
    'test|t'         => 'Testing mode',
    );
/* Environnements */
$listActions = array('dcl', 'pmc', 'hal', 'generate');
$defaultAction = 'dcl';

if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

define('DOCS_CACHE_PATH', CACHE_ROOT . "/" . APPLICATION_ENV . "/docs/");

$test    = isset($opts->test);
//Action
define('ACTION', (isset($opts->a) && in_array($opts->a, $listActions)) ? $opts->a : $defaultAction);

Zend_Registry::set('languages', array('fr','en','es','eu'));
Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( Zend_Registry::get('Zend_Locale') ) );

println();
println('','****************************************', 'blue');
println('','** Récupération des documents de DCL  **', 'blue');
println('','****************************************', 'blue');
println('> Début du script: ', date("H:i:s", $timestart), '');
println('> Environnement: ', APPLICATION_ENV, '');
println('', '----------------------------------------', 'yellow');

if (ACTION == 'dcl') {
    println('> Vérification de la présence de nouveaux documents convertis par DCL');

    //Liste des documents en attente de traitement par DCL
    $docids = Halms_Document::getDocuments(Halms_Document::STATUS_WAIT_FOR_DCL, null, null, true);
    if (count($docids)) {
        println('> Documens à vérifier :' . implode(', ', $docids));

        $dclFiles = Halms_Document::listDCL();
        if ($dclFiles['result']) {
            //Fichiers récupérés chez DCL
            foreach($dclFiles['files'] as $filename) {
                foreach($docids as $docid) {
                    if ($filename == 'halms' . $docid . '.zip') {
                        $halms = new Halms_Document($docid);

                        //Un fichier est à récupérer
                        println('> Document : ' . $docid);
                        println("\t" . '- Zip présent chez DCL');

                        //On récupère le zip dans l'espace du dépôt
                        $result = $halms->downloadDCL();
                        if ($result['result']) {
                            println("\t" . "- Récupération du zip - OK");
                            //Dezip du fichier
                            if ($test) {
                                println('Mode test: stop action');
                            } else {
                                if ($halms->unzipDCLArchive()) {
                                    println("\t" . "- Extraction de l'archive - OK");

                                    //On génère les versions html et pdf
                                    if ($halms->generate()) {
                                        println("\t" . "- Génération version HTML et PDF - OK");
                                        //Changement du statut
                                        $halms->changeStatus(Halms_Document::STATUS_XML_QA);

                                        //Envoi de mails aux administrateurs HALMS
                                        $halms->sendMail(
                                            ['email' => Halms_Document::HALMS_MAIL, 'name' => Halms_Document::HALMS_USERNAME],
                                            ['HALMS_ID' => "HALMS_" . $halms->getDocid(), 'USER' => Halms_Document::HALMS_USERNAME],
                                            'mail_dcl_return_subject',
                                            'mail_dcl_return_content');
                                        //Log
                                        Halms_Document_Logger::log($docid, 100000, Halms_Document::STATUS_XML_QA);

                                        //Suppression du zip chez DCL
                                        $halms->deleteDCL();
                                    } else {
                                        println("\t" . "- Erreur dans la génération des versions html et pdf");
                                    }
                                } else {
                                    println("\t" . "- Erreur dans l'extraction de l'archive");
                                }
                            }
                        } else {
                            println("\t" . '- Erreur de récupération du zip - ' . $result['msg']);
                        }
                        println();
                    }
                }
            }
        }
    } else {
        println('> Aucun document');
    }
} else if (ACTION == 'pmc') {
    println("> Envoi des articles sur PubMed Central");
    println("\t" . "Changement de statut pour les documents dont le délai d'intervention par les auteurs est passé");
    $cond = 'DOCSTATUS = ' . Halms_Document::STATUS_XML_CONTROLLED . ' AND DATE_ADD(DATEMODIF,INTERVAL 15 DAY) < "' . date('Y-m-d') . '"' ;
    $cond .= ' OR DOCSTATUS = ' . Halms_Document::STATUS_XML_FINISHED  ;
    $docids = Halms_Document::getDocuments([Halms_Document::STATUS_XML_CONTROLLED, Halms_Document::STATUS_XML_FINISHED], null, null, true, $cond);
    if (count($docids) == 0) {
        println("> Aucun document");
    }
    foreach ($docids as $docid) {
        println("\t- document : " . $docid);
        $halms = new Halms_Document($docid);
        if ($test) {
            println("\t\tFake envois a PMC");
        } else {
            if ($halms->uploadPMC()) {
                $halms->changeStatus(Halms_Document::STATUS_WAIT_FOR_PMC);
                Halms_Document_Logger::log($docid, 100000, Halms_Document::STATUS_WAIT_FOR_PMC, "Transfert sur PMC");
            }
        }
    }
} else if (ACTION == 'hal') {
    println("> Vérification de l'attribution d'un Pubmed Central Id sur un dépôt");
    $docids = Halms_Document::getDocuments(Halms_Document::STATUS_WAIT_FOR_PMC, null, null, true);
    if (count($docids) == 0) {
        println("> Aucun document");
    }
    foreach ($docids as $docid) {
        println("\t" . "- document : " . $docid);
        $document = Hal_Document::find($docid);
        $pmid = $document->getIdsCopy('pubmed');
        if (!$pmid) {
            return false;
        }
        println("\t" . "- pmid : " . $pmid);

        $pmcid = Halms_Document::getPmcidFromPmid($pmid);

        if ($pmcid != 0) {
            println("\t" . "- pmcid : " . $pmcid);
            $halms = new Halms_Document($docid);
            if ($test) {
                println("\t" . "Fake Attribution du pmcid : " . $pmcid);
            } else {
                $halms->updateHal($pmcid);
            }

            //Envoi du mail au déposant
            $contrib = $document->getContributor();
            if ($test) {
                println("\t" . "Fake envoie mail " . $contrib['email']);
            } else {
                $halms->sendMail(
                    ['email' => $contrib['email'], 'name' => $contrib['fullname']],
                    ['HALMS_ID' => "HALMS_" . $docid, 'USER' => $contrib['fullname'], 'PMCID' => $pmcid],
                    'mail_author_online_subject',
                    'mail_author_online_content');

                $halms->changeStatus(Halms_Document::STATUS_PMC_ONLINE);
                Halms_Document_Logger::log($docid, 100000, Halms_Document::STATUS_PMC_ONLINE, "En ligne sur PubMed Central");
            }
        }
    }
} else if (ACTION == 'generate') {
    $docid = isset($opts->$docid) ? $opts->$docid : null;
    if ($docid) {
        println("\t" . "- Test de génération des versions HTML et PDF pour le docid :" . $docid);
        $halms = new Halms_Document($docid);
        if ($halms->generate()) {
            println("\t" . "- Génération version HTML et PDF - OK");
        } else {
            println("\t" . "- Génération version HTML et PDF - KO");
        }
    }
}

$timeend = microtime(true);
$time = $timeend - $timestart;
println('> Fin du script: ' . date("H:i:s", $timeend));
println('> Script executé en ' . number_format($time, 3) . ' sec.');
println();
