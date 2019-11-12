<?php
/**
 * Vérifie si les données des CV sont à jour par rapport à solr
 * User: yannick
 * Date: 20/10/15
 * Time: 13:29
 */

set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/phplib'), array(get_include_path()))));

require_once ('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

define('APPLICATION_PATH', __DIR__ . '/../application');
define ('SPACE_DATA', realpath(dirname(__FILE__) . '/../data'));
define ('SPACE_PORTAIL', 'portail');

define('APPLICATION_ENV', 'production');

try {
    if ( APPLICATION_ENV == 'production' ) {
        $library = array('/sites/library');
    } else if ( APPLICATION_ENV == 'preprod' ) {
        $library = array('/sites/library_preprod');
    } else if ( APPLICATION_ENV == 'testing' ) {
        $library = array('/sites/library_test');
    } else {
        $library[] = realpath(APPLICATION_PATH . '/../../library');
    }
    set_include_path(implode(PATH_SEPARATOR, array_merge($library, array(get_include_path()))));

    $application = new Zend_Application(APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini');
    $application->getBootstrap()->bootstrap(array('db'));
    $db = Zend_Db_Table_Abstract::getDefaultAdapter();
    foreach ($application->getOption('consts') as $const => $value) {
        define($const, $value);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

$sql = $db->select()->from('REF_IDHAL', ['IDHAL', 'URI'])->order('IDHAL ASC');
foreach ($db->fetchAll($sql) as $row) {

    $cv = new Hal_Cv($row['IDHAL']);
    $counters = getCounters($cv);

    println('> CV ' . $row['URI'] . ' (' . $row['IDHAL'] . ') : ' . (($counters['cache'] == $counters['solr']) ? 'OK' : 'KO'));
    if ($counters['cache'] != $counters['solr']) {
        println('  - documents cache : ' . $counters['cache']);
        println('  - documents solr  : ' . $counters['solr']);
        //On supprime le cache
        unlink(CACHE_CV . '/' . $cv->getCacheFilename());
        file_get_contents('https://cv.archives-ouvertes.fr/' . $row['URI']);
        println();
    }
}



function getCounters($cv, $defaultFilters = '&fq=status_i%3A11')
{
    $nbDocsCache = $nbDocsSolr = 0;

    //Récupération du fichier de cache
    $cache = CACHE_CV . '/' . $cv->getCacheFilename();
    if (is_file($cache)) {
        //Nb de docs en cache
        $content = unserialize(file_get_contents($cache));

        if (isset($content['grouped']['docType_s']['matches'])) {
            $nbDocsCache = $content['grouped']['docType_s']['matches'];
        }

        //Nb de docs avec requete solr
        $cv->load(false);
        $url = 'ccsdsolrvip.in2p3.fr:8080/solr/hal/select?' . $cv->createSolrRequest() . $defaultFilters;

        $tuCurl = curl_init ();
        curl_setopt ( $tuCurl, CURLOPT_USERAGENT, 'CcsdToolsCurl' );
        curl_setopt ( $tuCurl, CURLOPT_URL, $url );
        curl_setopt ( $tuCurl, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $tuCurl, CURLOPT_CONNECTTIMEOUT, 10 );
        curl_setopt ( $tuCurl, CURLOPT_TIMEOUT, 10 );
        curl_setopt ( $tuCurl, CURLOPT_USERPWD, 'ccsd' . ':' . 'ccsd12solr41' );
        $info = curl_exec ( $tuCurl );
        if ($info) {
            $info = unserialize($info);
            if (isset($info['grouped']['docType_s']['matches'])) {
                $nbDocsSolr = $info['grouped']['docType_s']['matches'];
            }
        }
    }

    return ['cache' => $nbDocsCache, 'solr' => $nbDocsSolr];
}




/**********************************/

function println($var = '') {
    echo $var."\n";
}