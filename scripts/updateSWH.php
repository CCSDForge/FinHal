<?php
$localopts = array(
        'test|t' => 'Mode test',
);

require_once __DIR__ . '/loadHalHeader.php';

$test = isset($opts->t);

Hal_Transfert_SoftwareHeritage::check_all_pending_status(true);

