<?php
/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen
 * Date: 30/01/2017
 * Time: 15:28
 * =============================================================================================================
 */

/**
 * =============================================== DESCRIPTION =================================================
 *
 * =============================================================================================================
 */

// Get the user input (CLN) for manuel extraction => Examples : 905319 | 498583
$localopts = array('docId|i-s' => 'HAL Document ID');

// Constants and Configuration
require_once(__DIR__ . '/../loadHalHeader.php');

// If docId is set as an input, assign it to docsId, if not :
// Get id(s) of document(s), awaiting moderation [DOC_STATUS 0 or 10], from db
$docId = isset($opts->docId) ? (int)$opts->docId : 19; // or 1

// HAL Document References (load references by default, if existed)
$halDocReferences = new Hal_Document_References($docId);

/** Create Cache, get references in HTML format */
echo $halDocReferences->getHTMLReferences();
