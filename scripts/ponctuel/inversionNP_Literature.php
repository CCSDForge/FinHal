<?php

$localopts = array(
    'docid-i'  => 'Docid du document à traiter',
);

require_once __DIR__.'/../loadHalHeader.php';


$docid = $opts->docid;


$docrefs = new Hal_Document_References($docid);
$docrefs->revertPrenomNomInXML();

