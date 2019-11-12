<?php
/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen Malla Osman
 * Date: 30/01/2017
 * Time: 11:34
 * =============================================================================================================
 */

/**
 * =============================================== DESCRIPTION =================================================
 * This script allows to create Hal document references
 * Mechanism :
 * (1) Get online Hal documents, (default since 1 hour)
 * (2) Check that each retrieved document has a file
 * (3) Then, save it in db [TABLE : GROBID_REFERENCES]
 * =============================================================================================================
 */

// Get the user input (CLI) for manuel creation
$localopts = [
    'docId|i-s'          => 'HAL Document ID',
    'DateModeration|t-s' => 'We can get docid based on the moderation date, Y-m-d H:i:s, by default get docs from last hour'
    //'online|o'         => 'For online docs, setup this flag to get them, (We get docs in moderation by default)',
    //'lastMonth|l'      => 'For online docs, setup this flag to get docs of last month (False by default)'
];

// Constants and Configuration
require_once(__DIR__ . '/../loadHalHeader.php');
Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator('fr'));

// Check if doc id is given by user input
$docIds = isset($opts->docId) ? (array)(int)$opts->docId : null;

// Check if Date moderation is given by user input
$date_moderation = isset($opts->DateModeration) ? $opts->DateModeration : '-1 hour';

// Check mode of processing (documents in moderation or online documents ?)
if (!isset($docIds)) {
    $docIds = Hal_Document::getVisibleDocIds($date_moderation);
    //$docIds = isset($opts->online) ? Hal_Document::getVisibleDocIds($opts->DATEMODER) : Hal_Moderation::getDocIds();
    if (empty($docIds)) {
        debug('', '---> There are no new submissions in moderation phase', 'red');
        exit;
    }
}

// Count documents (for debugging)
$nbDocs = count($docIds);

// Counter of the number of references
$cptDocs = 0;

// Check that each retrieved document has a file, if yes, save it in db [TABLE : GROBID_REFERENCES]
foreach ($docIds as $docId) {
    // Object Hal_Document_References
    $halDocReferences = new Hal_Document_References($docId);
    debug('---> Remaining documents : ' . $nbDocs--);
    debug('-->  DOCID ' . $docId);
    // Check that document has a file
    if (!Hal_Document_References::getFile($docId)) {
        debug('', '->   No file has been found', 'red');
        debug('', '----------------------------------', 'blue');
        continue;
    }
    // Save document in db (if it doesn't exist)
    if ($halDocReferences->save(Hal_Document_References::GROBID_REFERENCES, [Hal_Document_References::DOCID => $docId])) {
        debug('', '->   Document saved', 'green');
        $cptDocs++;
    } else {
        debug('', '->   Document already exist in db', 'red');
    }
    debug('', '----------------------------------', 'blue');
}

debug('', '-----------------------------------', 'yellow');
debug('', 'Nb of created documents = ' . $cptDocs, 'green');
debug('', '-----------------------------------', 'yellow');
