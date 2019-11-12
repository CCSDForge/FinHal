<?php
define('APPLICATION_ENV', 'production');

set_include_path(implode(PATH_SEPARATOR, array_merge(array('./../library', '/sites/phplib', '/sites/library'), array(get_include_path()))));
//set_include_path(implode(PATH_SEPARATOR, array_merge(array( './../library', '/Users/laurent/Zend/library', '/Users/laurent/PhpstormProjects/library'), array(get_include_path()))));

// Autoloader
require_once ('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

$query = ['wt'=>'phps', 'q'=>'*:*', 'fl'=>'docid', 'sort'=>'docid desc', 'rows'=>'100', 'cursorMark'=>'*'];
$page = 0;
while (true) {
    println(date('c'));
    Zend_Debug::dump($query, 'Doing search:');
    $result = unserialize(solr($query));
    Zend_Debug::dump($result, 'Solr result:');
    $page += count($result['response']['docs']);
    println($page.'/'.$result['response']['numFound']);
    if ( $query['cursorMark'] == $result['nextCursorMark'] ) {
        break;
    }
    $query['cursorMark'] = $result['nextCursorMark'];
}

function println($var) {
    echo $var."\n";
}

function solr($a) {
    $query = [];
    foreach ( $a as $p=>$v) {
        $query[] = $p.'='.rawurlencode($v);
    }
    $tuCurl = curl_init();
    curl_setopt ( $tuCurl, CURLOPT_USERAGENT, 'CcsdToolsCurl' );
    curl_setopt ( $tuCurl, CURLOPT_URL, 'http://ccsdsolrvip.in2p3.fr:8080/solr/hal/select?'.implode('&', $query) );
    curl_setopt ( $tuCurl, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt ( $tuCurl, CURLOPT_CONNECTTIMEOUT, 10 );
    curl_setopt ( $tuCurl, CURLOPT_TIMEOUT, 300 ); // timeout in seconds
    curl_setopt ( $tuCurl, CURLOPT_USERPWD, 'ccsd:ccsd12solr41' );
    $info = curl_exec ( $tuCurl );
    if (curl_errno ( $tuCurl ) == CURLE_OK) {
        return $info;
    } else {
        exit(curl_errno( $tuCurl ));
    }
}