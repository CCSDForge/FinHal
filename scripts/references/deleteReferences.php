<?php
/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen
 * Date: 08/02/2017
 * Time: 10:24
 * =============================================================================================================
 */

/**
 * =============================================== DESCRIPTION =================================================
 * This script allows to delete Hal document references when RERFHTML is NULL
 * =============================================================================================================
 */

// Constants and Configuration
require_once(__DIR__ . '/../loadHalHeader.php');
// Delete process
Hal_Document_References::deleteByRefHtmlNull();
// Debug
debug('', 'Delete has been processed with success when REFHTML IS NULL', 'yellow');
