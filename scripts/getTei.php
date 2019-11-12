<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 11/04/2017
 * Récupération de fichiers TEI
 */

$dirTeiFiles = '/Users/yannick/Documents/htdocs/conditor/';

/* @var integer $producedDateY */
$producedDateY = 2014;

/* @var array $typdoc*/
$typdoc = ['ART', 'COMM', 'COUV', 'OTHER', 'OUV', 'DOUV', 'UNDEFINED', 'REPORT', 'THESE', 'HDR', 'LECTURE'];


/* @var string $apiURL */
$apiURL = 'https://api.archives-ouvertes.fr/search?';

/* @var integer $rows */
$rows = 1000;


$start = 0;

$continue = true;

do {
    print($start . "\n");

    $url = $apiURL . "wt=json&fq=producedDateY_i:" . $producedDateY . "&fq=docType_s:(" . urlencode(implode(' OR ', $typdoc)) . ")" ;
    $url .= "&rows=" . $rows . "&start=" . $start ;
    $url .= "&fl=halId_s,version_i,docType_s,label_xml";
    try {
        $result = curl($url);
    } catch (Exception $e) {
        $continue = false;
    }

    $documents = getDocuments($result);
    if ($documents == false) {
        $continue = false;
    }

    foreach ($documents as $document) {
        treatDocument($document, $dirTeiFiles);
    }
    $start += $rows;

} while ($continue);

print('FIN' . "\n");



function curl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ( $httpCode != 200 ) {
        throw new Exception('Bad HTTP Code');
    }

    return $result;
}

function getDocuments($str)
{
    $array = json_decode($str);

    if (isset($array->response->docs) && count($array->response->docs)) {
        return $array->response->docs;
    }
    return false;
}

function treatDocument($document, $rootDir)
{
    $filename = $document->halId_s . 'v' . $document->version_i . '.xml';

    $dir = $rootDir . $document->docType_s . DIRECTORY_SEPARATOR;
    if (! is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    file_put_contents($dir . $filename, $document->label_xml);

    print($filename . "\n");


}