<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 06/06/2016
 * Time: 16:49
 */

define('VHOST_TPL', 'vhost.conf');
define('URL_TPL', 'hal-%portail%.archives-ouvertes.fr');
define('PORTAL_ENV', 'environment');
define('PORTAL_NAME', 'name');
define('PORTAL_LABEL', 'label');
define('PORTAL_URL', 'url');
define('PORTAL_ALIAS', 'alias');
define('PORTAL_CATEGORY', 'category');
define('PORTAL_PREFIX', 'prefix');
define('PORTAL_ADMIN', 'admin');
define('PORTAL_COLLNAME', 'collectionName');
define('PORTAL_COLLID', 'collectionId');
define('PORTAL_SID', 'sid');
define('PORTAL_THUMB', 'imagette');
define('PORTAL_PIWIK', 'piwikid');
define('PORTAL_HIDE', 'hide');
define('PIWIK_SERVER', 'https://piwik-hal.ccsd.cnrs.fr');


define('DNS_DOMAINID', '3');
define('DNS_DOMAIN', 'archives-ouvertes.fr');
define('DNS_CONTENT', '193.48.96.10');

define('ROOTDIR', realpath(__DIR__ . '/../..'));
define('APPLICATION_PATH', ROOTDIR  . '/application');
define('SPACE_DATA', realpath(ROOTDIR . '/data'));
define('SPACE_CACHE', realpath(ROOTDIR . '/cache'));
define('SPACE_PORTAIL', 'portail');

define('ENV_DEV', 'development');
define('ENV_TEST', 'testing');
define('ENV_PREPROD', 'preprod');
define('ENV_PROD', 'production');

require_once(ROOTDIR  . '/public/bddconst.php');

$listEnv = [ENV_DEV, ENV_TEST, ENV_PREPROD, ENV_PROD];
$correspEnv = [ENV_DEV => '', ENV_TEST => 'test', ENV_PREPROD => 'preprod', ENV_PROD => ''];
$defaultEnv = ENV_PROD;

$listCat = ['GEN','INSTITUTION','THEME','PRES','UNIV','ECOLE','LABO','COLLOQUE','REVUE','AUTRE','SET','COMUE'];
$defaultCat = 'INSTITUTION';

$listLanguages = ['en', 'fr'];

set_include_path(implode(PATH_SEPARATOR, array_merge(array(ROOTDIR . '/library', '/sites/phplib'), array(get_include_path()))));

require_once ('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

//Environnement
println();
println('', '1- Environnement de création du portail', 'green');
$portal[PORTAL_ENV] = getParam("Sélectionnez l'environnement de création du portail", true, $listEnv, $defaultEnv);

define('APPLICATION_ENV', $portal[PORTAL_ENV]);

$portal[PORTAL_ENV] = $correspEnv[APPLICATION_ENV];


if (APPLICATION_ENV == ENV_DEV) {
    $vhostdir = realpath(ROOTDIR . '/data');
    $library = realpath(ROOTDIR . '/vendor/library');
    set_include_path(implode(PATH_SEPARATOR, array_merge([$library], array(get_include_path()))));
} else {
    $vhostdir = '/sites/conf/halv3';
}
define('VHOST_DIR', $vhostdir);

try {
    $application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
    $application->getBootstrap()->bootstrap(array('db'));
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    foreach ($application->getOption('consts') as $const => $value) {
        define($const, $value);
    }
    Zend_Registry::set('languages', $listLanguages);
    Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));
} catch (Exception $e) {
    die($e->getMessage());
}

//Portail URL
println();
println('', '2- URL du site', 'green');

while(true) {
    $portal[PORTAL_NAME] = getParam("identifiant court (ex: univ-diderot)", true);
    $sql = $db->select()->from('SITE', 'SID')
        ->where('SITE = ?', $portal[PORTAL_NAME])
        ->where('TYPE = ?', 'PORTAIL');
    $res = $db->fetchOne($sql);
    if ($res !== false) {
        println('', 'Le portail existe déjà', 'red');
    } else {
        break;
    }

}
$portal[PORTAL_URL] = str_replace('%portail%', $portal[PORTAL_NAME], URL_TPL);
$portalUrl = getParam("URL du portail", false, [], $portal[PORTAL_URL]);
$portalUrl = preg_replace('|https?://|', '', $portalUrl);  // nettoyage car on ajout le http apres
$portal[PORTAL_URL] =$portalUrl;

$portal[PORTAL_ALIAS] = getParam("Autre URL du portail (alias)", false);

//Portail infos générales
println();
println('', '3- Informations générales', 'green');
$portal[PORTAL_LABEL] = getParam("Nom du portail", true);
$portal[PORTAL_CATEGORY] = getParam("Catégorie", true, $listCat, $defaultCat);
$portal[PORTAL_PREFIX] = getParam("Préfixe des documents du portail", false, [], 'hal-');

//Administrateur du portail
println();
println('', '4- Administrateur', 'green');

while(true) {
    $portal[PORTAL_ADMIN] = getParam("UID de l'administrateur du portail", true);
    $sql = $db->select()->from('USER', 'SCREEN_NAME')->where('UID = ?', $portal[PORTAL_ADMIN]);
    $res = $db->fetchOne($sql);
    if ($res === false) {
        println('', "Le compte n'existe pas", 'red');
    } else {
        break;
    }
}

//Configuration du portail
println();
println('', '4- Configuration du portail', 'green');


$portailCollection = getParam("Le portail est-il associé à une collection", true, ['o', 'n'], 'o');
if ($portailCollection == 'o') {
    while(true) {
        $collection = getParam("Identifiant de la collection (SID ou NAME)", true);
        $sql = $db->select()->from('SITE', ['SID', 'SITE'])
            ->where('SID = ?', (int)$collection)
            ->orWhere('SITE = ?', $collection);
        $res = $db->fetchRow($sql);
        if ($res == false) {
            println('', "La collection n'existe pas", 'red');
        } else {
            $portal[PORTAL_COLLID] = $res['SID'];
            $portal[PORTAL_COLLNAME] = $res['SITE'];
            break;
        }
    }
}

$hidePortail = getParam("Masquer le portail", true, ['o', 'n'], 'o');
$portal[PORTAL_HIDE] = $hidePortail === 'o';

//Création du PIWIKID
$portal[PORTAL_PIWIK] = 0;
$piwikid = addWebsite($portal[PORTAL_NAME], $portal[PORTAL_URL]);
if ($piwikid > 0) {
    $portal[PORTAL_PIWIK] = $piwikid;
}

println();
println('', '----------------------------------------', 'red');
println('Récapitulatif');
foreach ($portal as $meta => $value) {
    if ($meta == PORTAL_ENV) {
        $value = APPLICATION_ENV;
    }
    println($meta . ' : ', $value, 'yellow');
}
println('', '----------------------------------------', 'red');
if (getParam("Créer le portail", true, ['O', 'n'], 'n') == 'O') {
    println('', 'Création du portail...', 'yellow');

    if (APPLICATION_ENV != ENV_DEV) {
        //1- Enregistrement dans le DNS du nouveau portail
        $res = Ccsd_Dns::add('hal-' . $portal[PORTAL_NAME], DNS_DOMAINID, DNS_DOMAIN, DNS_CONTENT);
        println("- Enregistrement dans le DNS : ", getMsg($res), getColor($res));
    }
    // Création du vhost
    $corresp = [
        '%PORTAIL_URL%' => $portal[PORTAL_URL],
        '%PORTAIL_NAME%' => $portal[PORTAL_NAME],
        '%PORTAIL_ENV%' => (($portal[PORTAL_ENV] != '') ? '-' . $portal[PORTAL_ENV] : ''),
        '%PORTAIL_ALIAS%' => ''
    ];
    if ($portal[PORTAL_ALIAS] != '') {
        $corresp['%PORTAIL_ALIAS%'] = "ServerAlias " . $portal[PORTAL_ALIAS];
    }
    $vhostContent = file_get_contents(ROOTDIR . '/library/Hal/Default/' . VHOST_TPL);
    $vhostContent = str_replace(array_keys($corresp), array_values($corresp), $vhostContent);
    $vhostFile  = VHOST_DIR . '/' . $portal[PORTAL_NAME] . '.conf';
    if (is_file($vhostFile)) {
        println("- Enregistrement du virtualhost ($vhostFile) : ", 'KO - le fichier existe déjà', 'red');
    } else {
        $res = file_put_contents($vhostFile, $vhostContent);
        println("- Enregistrement du virtualhost ($vhostFile) : ", getMsg($res), getColor($res));
    }

    // Enregistrement en base
    $bind = [
        'TYPE'  =>  'PORTAIL',
        'SITE'  =>  $portal[PORTAL_NAME],
        'ID'  =>  $portal[PORTAL_PREFIX],
        'URL'  =>  'https://' . $portal[PORTAL_URL],
        'NAME'  =>  $portal[PORTAL_LABEL],
        'CATEGORY'  =>  $portal[PORTAL_CATEGORY],
        'DATE_CREATION'  =>  date('Y-m-d'),
        'IMAGETTE' => $portal[PORTAL_THUMB]
    ];
    $res = $db->insert('SITE', $bind);
    println("- Enregistrement en base : ", getMsg($res), getColor($res));
    if ($res) {
        $portal[PORTAL_SID] = $db->lastInsertId('SITE');
        println("- Identifiant du portail : ", $portal[PORTAL_SID], getColor($res));
    } else {
        println("", "Erreur lors de l'enregistrement en base", 'red');
        die();
    }

    // Droit administrateur
    $db->delete('USER_RIGHT', 'UID = ' . $portal[PORTAL_ADMIN] . ' AND SID = ' . $portal[PORTAL_SID]);
    $res =  $db->insert('USER_RIGHT', ['UID' => $portal[PORTAL_ADMIN], 'SID' => $portal[PORTAL_SID],  'RIGHTID' => 'administrator',  'VALUE' => '' ]);
    println("- Ajout du droit administrateur : ", getMsg($res), getColor($res));

    // Création des répertoires
    define('SPACE', '/data/hal/'  . APPLICATION_ENV . '/portail/' . $portal[PORTAL_NAME] . '/');
    define('CACHE', '/cache/hal/' . APPLICATION_ENV . '/portail/' . $portal[PORTAL_NAME] . '/');

    $directories = [SPACE, CACHE, SPACE . 'config', SPACE . 'languages', SPACE . 'layout', SPACE . 'pages', SPACE . 'public'];

    foreach ($listLanguages as $lang) {
        $directories[] = SPACE . 'languages/' . $lang;
    }

    foreach($directories as $directory) {
        if (is_dir($directory)) {
            println("- Création du répertoire ($directory): ", 'KO - le répertoire existe déjà', 'red');
            continue;
        }
        $res = @mkdir($directory);
        changeOwn($directory);
        println("- Création du répertoire ($directory): ", getMsg($res), getColor($res));
    }

    //4- Création du fichier de configuration solr.hal.defaultFilters.json
    if (isset($portal[PORTAL_COLLNAME])) {
        //portail collection
        $content = json_encode(['1' => 'collCode_s:' . $portal[PORTAL_COLLNAME]]);
    } else {
        //Portail classique
        $content = json_encode(['1' => 'instance_s:' . $portal[PORTAL_NAME]]);
    }

    $configFile = SPACE . 'config/solr.hal.defaultFilters.json';
    $res = file_put_contents($configFile, $content);
    changeOwn($configFile);
    println("- Création du fichier de configuration  ($configFile): ", getMsg($res), getColor($res));

    //4- langues du site
    $db->delete(Hal_Site_Settings_Collection::TABLE, 'SETTING="languages" AND SID = ' . $portal[PORTAL_SID]);
    $db->insert(Hal_Site_Settings_Collection::TABLE, ['SID' => $portal[PORTAL_SID], 'SETTING' => 'languages', 'VALUE' => serialize($listLanguages) ]);

    //5- Navigation du site
    $sql = file_get_contents(ROOTDIR . '/library/Hal/Default/navigation.sql');
    $sql = str_replace('%SID%', $portal[PORTAL_SID], $sql);
    $db->delete('WEBSITE_NAVIGATION', 'SID = ' . $portal[PORTAL_SID]);
    $res = $db->query($sql);
    println("- Enregistrement des langues du portail : ", getMsg($res), getColor($res));

    $file = ROOTDIR . '/library/Hal/Default/navigation.json';
    $fileDest = SPACE . 'config/navigation.json';
    $res = copy($file, $fileDest);
    changeOwn($fileDest);
    println("- Copie du fichier de navigation ($fileDest): ", getMsg($res), getColor($res));
    foreach ($listLanguages as $lang) {
        $file = ROOTDIR . '/library/Hal/Default/menu.' . $lang . '.php';
        $fileDest = SPACE . 'languages/' . $lang . '/menu.php';
        $res = copy($file, $fileDest);
        changeOwn($fileDest);
        println("- Copie de la traduction du menu ($fileDest): ", getMsg($res), getColor($res));
    }

    //6- Copie de la page d'accueil du portail
    foreach ($listLanguages as $lang) {
        $file = ROOTDIR . '/library/Hal/Default/index.' . $lang . '.html';
        $fileDest = SPACE . 'pages/index.' . $lang . '.html';
        $res = copy($file, $fileDest);
        changeOwn($fileDest);
        println("- Copie de la page d'accueil du portail ($fileDest): ", getMsg($res), getColor($res));
    }


    //7- Masquage du portail
    if ($portal[PORTAL_HIDE]) {
        $db->insert('PORTAIL_SETTINGS', ['SID' => $portal[PORTAL_SID], 'SETTING' => 'VISIBILITY', 'VALUE' => 'HIDDEN' ]);
    }

    //8- Lier le portail à une collection
    if (isset($portal[PORTAL_COLLID])) {
        $db->insert('PORTAIL_SETTINGS', ['SID' => $portal[PORTAL_SID], 'SETTING' => 'COLLECTION', 'VALUE' => $portal[PORTAL_COLLID] ]);
    }

    //9- Ajout du PIWIKID
    if ($piwikid > 0) {
        $db->delete(Hal_Site_Settings::TABLE, 'SETTING="PIWIKID" AND SID = ' . $portal[PORTAL_SID]);
        $db->insert(Hal_Site_Settings::TABLE, ['SID' => $portal[PORTAL_SID], 'SETTING' => 'PIWIKID', 'VALUE' => $portal[PORTAL_PIWIK] ]);
        println("- Création et Ajout du PIWIKID($piwikid): ", getMsg($res), getColor($res));
    }


    println('', 'Création du portail terminée...', 'yellow');
} else {
    println("ABANDON");
}
println();

/**
 * @param $text
 * @param bool $required
 * @param array $values
 * @param string $default
 * @return string
 */
function getParam($text, $required = true, $values = [], $default = '')
{
    if (count($values)) {
        $tmp = [];
        foreach ($values as $v) {
            $tmp[] = ($v == $default) ? $v . '[default]' : $v;
        }
        $text .= '(' . implode(', ', $tmp) . ')';
    } else if ($default) {
        $text .= '(' . $default. '[default]' . ')';
    }

    while (true) {
        print($text . ' : ');

        $res = trim(fgets(STDIN));

        if (count($values) && $default && !in_array($res, $values)) {
            $res = $default;
        } else if ($default && $res == '') {
            $res = $default;
        }

        if (!$required || $res != '') {
            break;
        }

    }
    return $res;
}

/**
 * @param string $s
 * @param string $v
 * @param string $color
 */
function println($s = '', $v = '', $color = '')
{
    if ($v != '') {
        switch($color) {
            case 'red'      :  $c = '31m';break;
            case 'green'    :  $c = '32m';break;
            case 'yellow'   :  $c = '33m';break;
            case 'blue'     :  $c = '34m';break;
            default         :  $c = '30m';break;
        }
        $v = "\033[" . $c . $v . "\033[0m";
    }

    print $s . $v . PHP_EOL;
}

function getColor($bool)
{
    return $bool ? 'green' : 'red';
}

function getMsg($bool)
{
    return $bool ? 'OK' : 'KO';
}

function changeOwn($resource)
{
    if (APPLICATION_ENV != ENV_DEV) {
        chgrp($resource, 'nobody');
        chown($resource, 'nobody');
    }
}

/**
 * Création d'une nouvelle entrée PIWIK
 * @param string $name
 * @param string $website
 * @return int identifiant du site sur la plateforme de stats
 */
function addWebSite($name, $website)
{
    //Vérification de la présence du site web
    $url = PIWIK_SERVER . '?module=API&method=SitesManager.getSitesIdFromSiteUrl&url=' . urlencode($website) . '&token_auth=' . PIWIK_KEY;
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_USERAGENT, "CCSD");
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $return = curl_exec($curl);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_status == 200) {

        $xml = simplexml_load_string($return);
        $row = $xml->xpath('/result/row/idsite');
        if (count($row) > 0) {
            //Site web déjà présent
            return $row[0];
        } else {
            //Site non présent
            $url = PIWIK_SERVER . '?module=API&method=SitesManager.addSite&siteName=' . urlencode($name) . '&urls[0]=' . urlencode($website) . '&token_auth=' . PIWIK_KEY;
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_USERAGENT, "CCSD");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            $return = curl_exec($curl);
            $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            if ($http_status == 200) {

                $xml = simplexml_load_string($return);
                $id = $xml->xpath('/result');
                return $id[0];
            } else {
                return 0;
            }
        }
    } else {
        return 0;
    }
}
