<?php
/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen Malla Osman
 * Date: 02/02/2017
 * Time: 17:21
 * =============================================================================================================
 */

/**
 * =============================================== DESCRIPTION =================================================
 * This script allows to update Hal documents references using CURL requests on Hal, CrossRef and aoDoi
 * =============================================================================================================
 */

// Get the user input (CLI) for manuel extraction
$localopts = array(
    'refId|f-s'       => 'HAL Reference ID - Update one reference',
    'docId|i-s'       => 'HAL Document ID - Update all references in one document',
    'refStatus|s-s'   => 'HAL Reference Status - string [NOT_UPDATED, UPDATED] - default -> NOT_UPDATED',
    'limitNumber|n-s' => 'Limit the number of requests (database lines), 100 by default',
);

// Constants and Configuration
require_once(__DIR__ . '/../loadHalHeader.php');
Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator('fr'));

// Get process id, for the parallelism process
$pid = getmypid();

// Check if doc id is given by user input
$docId = isset($opts->docId) ? (int)$opts->docId : null;

// Get RefStatus from user input, by default 'NOT_UPDATED'
$refStatus = isset($opts->refStatus) ? $opts->refStatus : 'NOT_UPDATED';

// Check if ref id is given by user input
$refIds = isset($opts->refId) ? (array)(int)$opts->refId : null;

// Get the limit number of requests
$limitNumber = isset($opts->limitNumber) ? (int)$opts->limitNumber : 100;

// If doc id isn't given by user input, get doc id(s) by refStatus
if (!isset($refIds)) {
    if (!isset($docId)) {
        $refIds = Hal_Document_References::getRefIdsForUpdating($refStatus, $pid, $limitNumber);
    } else {
        $refIds = Hal_Document_References::getRefIdsByDocId($docId, $refStatus, $pid, $limitNumber);
    }
    if (empty($refIds)) {
        exit;
    }
}

// Updating process
$ch = curl_init();
foreach ($refIds as $refId) {
    // Object Hal_Document_References
    $halReferences = new Hal_Document_References(Hal_Document_References::getDocIdByRefId($refId));
    // Update references
    $halReferences->update($ch, $refId, $pid);
    // Delete cache if exist
    $halReferences->deleteReferenceCache();
}
curl_close($ch);
