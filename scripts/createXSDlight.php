<?php
/**
 * Cr"éation d'un schéma XSD light se basant sur le schéma standard
 * User: yannick
 * Date: 29/11/2016
 * Time: 14:37
 */

$xsd = __DIR__ . '/../library/Hal/Sword/xsd/aofr.xsd';
$dest = __DIR__ . '/../library/Hal/Sword/xsd/inner-aofr.xsd';


$dom = new DOMDocument();
$dom->loadXML(file_get_contents($xsd));

$root = $dom->documentElement;
updateChildren($root);
$dom->save($dest);
header('Content-type: text/xml;');
echo $dom->saveXML();


function updateChildren($elem) {
    if ('xs:element' == $elem->nodeName) {
        if ($elem->hasAttribute('ref') /*&& ! in_array($elem->getAttribute('name'), ['TEI'])*/) {
            if (!$elem->hasAttribute('minOccurs')) {
                $elem->setAttribute('minOccurs', '0');
            }
        }
    }

    if ($elem->childNodes->length > 1) {
        foreach($elem->childNodes as $child) {
            if ($child->nodeType == XML_ELEMENT_NODE) {
                updateChildren($child);
            }
        }
    }
}