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
 * This script allows to extract Hal document references using Grobid service
 * By default :
 * (1) Pass the doc file to Grobid and set a flag allowing us to know that this service has been executed
 * (2) Save retrieved references in db [TABLE : DOC_REFERENCES]
 * =============================================================================================================
 */

// Get the user input (CLI) for manuel extraction => Examples of DOCID : 905319 | 498583 | 694305
$localopts = [
    'docId|i-s'       => 'HAL Document ID',
    'limitNumber|n-s' => 'Limit the number of requests (database lines), 100 by default'
];

// Constants and Configuration
require_once(__DIR__ . '/../loadHalHeader.php');
Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator('fr'));

// Get the process id, for the parallelism process
$pid = getmypid();

// Check if doc id is given by user input
$docIds = isset($opts->docId) ? (array)(int)$opts->docId : null;

// Get the limit number of requests
$limitNumber = isset($opts->limitNumber) ? (int)$opts->limitNumber : 100;

// If doc id is not given by user input, get doc id(s) by GROBID_PROCESS
if (!isset($docIds)) {
    $docIds = Hal_Document_References::getDocIdsForExtracting($limitNumber, $pid);
    if (empty($docIds)) {
        debug('Exit because Docid empty after getDocIdsForExtracting');
        exit;
    }
}

// Count documents (for debugging)
$nbDocs = count($docIds);

// Extracting by Grobid
foreach ($docIds as $docId) {
    // Object HAL_Document_References
    $halReferences = new Hal_Document_References($docId);
    // Extract references
    $referencesXML = $halReferences->extract();
    if(!$referencesXML) {
        continue;
    }
    // Save the references in db
    foreach ($referencesXML as $referenceXML) {
        $halReferences->save(Hal_Document_References::DOC_REFERENCES, [
            Hal_Document_References::DOCID => $docId,
            Hal_Document_References::REFXML_ORIGINAL => $referenceXML]);
    }
    debug('', printf("%-10s | %-10s", '---> DOCID ' . $docId, 'PID '. $pid));
    debug('', '-->  Extraction and saving have been finished with success', 'green');
    debug('', '----------------------------------', 'blue');
}
