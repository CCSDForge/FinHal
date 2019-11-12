<?php

$xml->formatOutput = true;
$xml->substituteEntities = true;
$xml->preserveWhiteSpace = false;
$root = $xml->createElement('SIP');
$xml->appendChild($root);

$root->appendChild($xml->createElement('doc', $this->_identifiant));
