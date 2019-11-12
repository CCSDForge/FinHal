<?php
/**
 * Script permettant de copier la configuration d'un portail / d'une collection vers un autre portail / une autre collection.
 *
 *
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 16/01/18
 * Time: 10:10
 */

//println('');
//println('','*******************************************************************************', 'blue');
//println('',"**  Copie de la configuration d'une collection ou d'un portail vers un autre **", 'blue');
//println('','*******************************************************************************', 'blue');

putenv('CACHE_ROOT=/cache/hal');
putenv('DATA_ROOT=/data/hal');
putenv('DOCS_ROOT=/docs');

$localopts = [
    'model-i' => 'SID de la collection ou du portail à copier',
    'receiver-i'  => 'SID de la collection ou du portail qui récupère la configuration',
    'navigation=s'  => 'Voulez-vous copier la navigation ? (o|n)',
    'style=s'  => 'Voulez-vous copier le style ? (o|n)',
    'settings=s'  => 'Voulez-vous copier les settings ? (o|n)',
    'userrights=s'  => 'Voulez-vous copier les droits des utilisateurs ? (o|n)',
    'submitconf=s'  => 'Voulez-vous copier la configuration du dépôt ? (o|n)',
    'searchconf=s'  => 'Voulez-vous copier la configuration de la recherche ? (o|n)',
    'files=s'  => 'Voulez-vous copier les fichiers ? (o|n)',
    'header=s'  => 'Voulez-vous copier le header ? (o|n)',
    'footer=s'  => 'Voulez-vous copier le footer ? (o|n)',
];

/** @var Zend_Console_Getopt $opts */
require_once __DIR__ . '/../loadHalHeader.php';

$modelid = $opts->model;
$receiverid = $opts->receiver;

if (!$modelid || !$receiverid) {
    print('Les paramètres passés ne sont pas les bons');
    return false;
}

$navigation = !isset($opts->navigation) ? getParam("Voulez-vous copier la navigation ?", true, ['o', 'n'], 'o') : $opts->navigation == 'o' ? 'o' : 'n';
$style = !isset($opts->style) ? getParam("Voulez-vous copier le style ?", true, ['o', 'n'], 'o') : $opts->style == 'o' ? 'o' : 'n';
$settings = !isset($opts->settings) ? getParam("Voulez-vous copier les settings ?", true, ['o', 'n'], 'o') : $opts->settings == 'o' ? 'o' : 'n';
$userright = !isset($opts->userrights) ? getParam("Voulez-vous copier les droits des utilisateurs ?", true, ['o', 'n'], 'o') : $opts->userrights == 'o' ? 'o' : 'n';
$submit = !isset($opts->submitconf) ? getParam("Voulez-vous copier la configuration du dépôt ?", true, ['o', 'n'], 'o') : $opts->submitconf == 'o' ? 'o' : 'n';
$research = !isset($opts->searchconf) ? getParam("Voulez-vous copier la configuration de la recherche ?", true, ['o', 'n'], 'o') : $opts->searchconf == 'o' ? 'o' : 'n';
$files = !isset($opts->files) ? getParam("Voulez-vous copier les fichiers ?", true, ['o', 'n'], 'o') : $opts->files == 'o' ? 'o' : 'n';
$footer = !isset($opts->footer) ? getParam("Voulez-vous copier le footer ?", true, ['o', 'n'], 'o') : $opts->footer == 'o' ? 'o' : 'n';
$header = !isset($opts->header) ? getParam("Voulez-vous copier le header ?", true, ['o', 'n'], 'o') : $opts->header == 'o' ? 'o' : 'n';

$options = [];

if ($navigation == "o") {
    $options[] = Hal_Site::DUPLICATE_NAVIGATION;
}

if ($style == "o") {
    $options[] = Hal_Site::DUPLICATE_STYLE;
}

if ($settings == "o") {
    $options[] = Hal_Site::DUPLICATE_SETTINGS;
}

if ($userright == "o") {
    $options[] = Hal_Site::DUPLICATE_RIGHTS;
}

if ($submit == "o") {
    $options[] = Hal_Site::DUPLICATE_SUBMIT;
}

if ($research == "o") {
    $options[] = Hal_Site::DUPLICATE_SEARCH;
}

if ($files == "o") {
    $options[] = Hal_Site::DUPLICATE_FILES;
}

if ($footer == "o") {
    $options[] = Hal_Site::DUPLICATE_FOOTER;
}

if ($header == "o") {
    $options[] = Hal_Site::DUPLICATE_HEADER;
}

$model = Hal_Site::loadSiteFromId($modelid);
$receiver = Hal_Site::loadSiteFromId($receiverid);

$model->duplicate($receiver, $options);

