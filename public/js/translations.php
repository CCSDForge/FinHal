<?php
header("content-type: application/x-javascript");
$lang = $_GET['lang'];
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application'));
$translations = [];

if (!in_array($lang, ['en', 'fr', 'es', 'eu'])) {
    $lang = 'fr';
}

$jsFile = APPLICATION_PATH . '/languages/' . $lang . '/js.php';

if (is_file($jsFile)) {
    $translations += include $jsFile;
}

echo "var locale = '" . $lang . "';" . PHP_EOL;
echo "var translations = " . json_encode($translations) . PHP_EOL;
?>

function translate ($key) {
if (translations[$key] == undefined) {
return $key;
}
return translations[$key];
}
