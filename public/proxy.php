<?php

if ( isset($_SERVER['REQUEST_URI']) && strlen($_SERVER['REQUEST_URI']) ) {
    $requestUri = rawurldecode($_SERVER['REQUEST_URI']);
    if ( preg_match('%^/aut/([^/]+)%', $requestUri, $match) ) {
        header('Location: /search/index/?qa[auth_t][]='.urlencode($match[1]));
        exit;
    }
    if ( preg_match('%^/autid/([0-9]+)%', $requestUri, $match) ) {
        header('Location: /search/index/?qa[authId_i][]='.(int)$match[1]);
        exit;
    }
    if ( preg_match('%^/lab/([^/]+)%', $requestUri, $match) ) {
        header('Location: /search/index/?qa[structure_t][]='.urlencode($match[1]));
        exit;
    }
    if ( preg_match('%^/labid/([0-9]+)%', $requestUri, $match) ) {
        header('Location: /search/index/?qa[structId_i][]='.(int)$match[1]);
        exit;
    }
    if ( preg_match('%^/autlab/([^/]+)/([^/]+)%', $requestUri, $match) ) {
        header('Location: /search/index/?qa[auth_t][]='.urlencode($match[1]).'&qa[structure_t][]='.urlencode($match[1]));
        exit;
    }
    if ( preg_match('%^/docs/([0-9]{2})/([0-9]{2})/([0-9]{2})/([0-9]{2})/archives/thumb%', $requestUri, $match) ) {
        header('Location: /file/thumb/docid/' . $match[1] . $match[2] . $match[3] . $match[4] . '/format/large');
        exit;
    }
    if ( preg_match('%^/docs/([0-9]{2})/([0-9]{2})/([0-9]{2})/([0-9]{2})/([^/]+)/([^/]+)%', $requestUri, $match) ) {
        header('Location: /file/index/docid/' . ltrim( $match[1] . $match[2] . $match[3] . $match[4], '0') . '/filename/' . $match[6]);
        exit;
    }
    if ( preg_match('%^/([A-Z0-9_-]+)/([a-z]{2})/?$%', $requestUri, $match) ) {
        header('Location: /' . $match[1] . '/?lang=' . $match[2]);
        exit;
    }
    if ( preg_match('%^/rss\.php\?(.*)$%', $requestUri, $match) ) {
        $portail = ( getenv('PORTAIL') ) ? getenv('PORTAIL') : 'hal';
        header('Location: http://api.archives-ouvertes.fr/search/rss/?portail='.$portail.'&'.$match[1]);
        exit;
    }
    if ( preg_match('%^/affiche\_img\.php\?.*dir=/documents/([0-9]+)/([0-9]+)/([0-9]+)/([0-9]+)/.*$%', $requestUri, $match) ) {
        header('Location: /file/thumb/docid/'.$match[1].$match[2].$match[3].$match[4].'/format/large');
        exit;
    }
    if ( preg_match('%^/view\_by\_stamp\.php\?.*label=([A-Z0-9_-]+).*$%', $requestUri, $match) ) {
        header('Location: /'.$match[1]);
        exit;
    }
}

if ( isset($_GET['action_todo']) ) {
    switch($_GET['action_todo']) {
        case 'search' :
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: /search');
            exit;
            break;
        case 'browse' :
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: /search');
            exit;
            break;
        case 'register' :
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: /user/create');
            exit;
            break;
        case 'lost_pwd' :
            header('HTTP/1.1 301 Moved Permanently');
            header('Location: /user/lostpassword');
            exit;
            break;
    }
}

header('HTTP/1.1 301 Moved Permanently');
header('Location: /');
