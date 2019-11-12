<?php

define('MAX_DOC_IN_ALERT', 500);
$localopts = array(
    'frequency|f=s' => ' --frequency=(day|week|month|push)',
    'test|t' => 'Lance le script en mode test (sans tamponnage/détamponnage)',
    'results|r' => 'Affiche les résultats',
    'uid|u=i' => "UID d'un seul utilisateur"
);


if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

switch ($opts->frequency) {
    case 'day' :
        $timeFilter = '&fq=releasedDate_tdate%3A[NOW%2FDAY-1DAY+TO+NOW%2FDAY]';
        break;
    case 'week' :
        $timeFilter = '&fq=releasedDate_tdate%3A[NOW%2FDAY-7DAY+TO+NOW%2FDAY]';
        break;
    case 'month' :
        $timeFilter = '&fq=releasedDate_tdate%3A[NOW%2FDAY-1MONTH+TO+NOW%2FDAY]';
        break;
    case 'push' :
        $timeFilter = '&fq=releasedDate_tdate%3A[NOW%2FMINUTE-5MINUTE+TO+NOW%2FMINUTE]';
        break;
    default :
        Ccsd_Log::message("La fréquence est obligatoire", true, 'ERR');
        echo $opts->getUsageMessage();
        exit (1);
        break;
}

//mode test
$test = isset($opts->t);

Zend_Registry::set('languages', array(
    'fr',
    'en',
    'es',
    'eu'
));
Zend_Registry::set('Zend_Locale', new Zend_Locale ('fr'));
Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator('fr'));
// Normalement, inutile car le fr lit deja tout!
//Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'en' ) );
//Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'es' ) );
//Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'eu' ) );

$u = new Hal_User_Search (array(
    'freq' => $opts->frequency
));

if ($opts->uid != false) {
    $searches = $u->getSearchAlerts($opts->uid);
} else {
    $searches = $u->getSearchAlerts();
}

$nbrSearches = count($searches);
if ($nbrSearches == 0) {
    Ccsd_Log::message("Pas de recherche avec la fréquence " . $opts->frequency, true, 'INFO');
    exit (1);
}
Ccsd_Log::message("Nombre de recherches avec la fréquence " . $opts->frequency . ' : ' . $nbrSearches . PHP_EOL, true, 'INFO');

$ch = curl_init();
curl_setopt($ch, CURLOPT_USERAGENT, 'sendAlert');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20); // timeout in seconds

foreach ($searches as $search) {
    $queryString = $u->prepareApiUrl($search->getUrl_api());
    $queryString .= $timeFilter;
    $queryString .= '&rows=' . MAX_DOC_IN_ALERT;
    if (!preg_match('/(&amp;|&)sort=[a-z]+/i', $queryString)) {
        $queryString .= '&sort=producedDateY_i+desc';
    }

    $queryString .= '&fl=label_s,uri_s,abstract_s';
    curl_setopt($ch, CURLOPT_URL, $queryString);
    if ($opts->verbose != false) {
        Ccsd_Log::message('Requête : ' . $search->lib . ' pour : ' . $search->getUser()->getFullName(), true, 'INFO');
        Ccsd_Log::message("User DB URL : " . $search->getUrl_api() . PHP_EOL, true, 'INFO');
        Ccsd_Log::message("Raw CURL URL : " . $queryString . PHP_EOL, true, 'INFO');
        Ccsd_Log::message("Decoded CURL URL : " . rawurldecode($queryString), true, 'INFO');
        Ccsd_Log::message("URL web UI : " . urldecode($search->url), true, 'INFO');
    }
    $info = curl_exec($ch);

    if (curl_errno($ch) !== CURLE_OK) {
        Ccsd_Log::message("Erreur CURL : " . curl_error($ch), true, 'ERR');
        Ccsd_Log::message("URL CURL : " . $queryString, true, 'ERR');
        Ccsd_Log::message("Requête pour : " . $search->getUser()->getFullName(), true, 'NOTICE');
        continue;
    } else {
        $results = json_decode($info, true);
        if ($opts->results != false) {
            Zend_Debug::dump($results, 'Résultats');
        }
    }

    if (isset ($results ['response']) && is_array($results ['response'])) {
        if ($results ['response'] ['numFound'] == 0) {
            Ccsd_Log::message($results ['response'] ['numFound'] . ' résultats pour ' . $search->getUser()->getFullName() . ' UID : ' . $search->getUser()->getUid() . PHP_EOL, true, 'NOTICE');
        } else {

            $docList = '';
            $i = 0;
            $webSite = Hal_Site::loadSiteFromId($search->getSid());
            Hal_Site::setCurrent($webSite);
            Zend_Registry::set('website', $webSite);
            Zend_Registry::set('lang', $search->getUser()->getLangueid());
            /** @var array $doc */
            foreach ($results ['response'] ['docs'] as $doc) {
                $docList .= PHP_EOL . PHP_EOL . '[# ' . $i . '] ' . html_entity_decode(strip_tags($doc ['label_s']));

                if (array_key_exists('abstract_s', $doc) && $doc ['abstract_s'] [0] != '') {
                    $docList .= PHP_EOL . Zend_Registry::get('Zend_Translate')->translate('Résumé : ', Zend_Registry::get('lang')) . Ccsd_Tools_String::truncate($doc ['abstract_s'] [0], 1000, '[...]');
                }

                $docList .= PHP_EOL . 'URL : <a href="' . $doc ['uri_s'] . '">' . $doc ['uri_s'] . '</a>';
                $docList .= PHP_EOL . str_repeat('-', 77);
                $i++;
            }
            $docList .= PHP_EOL;
            Ccsd_Log::message($results ['response'] ['numFound'] . ' résultats pour ' . $search->getUser()->getFullName() . ' UID : ' . $search->getUser()->getUid() . PHP_EOL, true, 'NOTICE');

            $tags = array();
            $tags ['USER'] = $search->getUser()->getFullName();
            $tags ['ALERT_NAME'] = $search->lib;
            $tags ['ALERT_FREQ'] = Zend_Registry::get('Zend_Translate')->translate('user_search_' . $search->getFreq(), Zend_Registry::get('lang'));
            $tags ['ALERT_NUM_DOCS'] = $results ['response'] ['numFound'];
            $tags ['ALERT_SEARCH_URL'] = $search->url;
            $tags ['ALERT_DOC_LIST'] = $docList;
            if ($results ['response'] ['numFound'] > MAX_DOC_IN_ALERT) {
                $tags ['ALERT_DOC_LIMIT'] = Zend_Registry::get('Zend_Translate')->translate('Liste des ' . MAX_DOC_IN_ALERT . ' premiers documents :');
            } else {
                $tags ['ALERT_DOC_LIMIT'] = '';
            }
            $mail = new Hal_Mail ();
            $mail->prepare($search->getUser(), Hal_Mail::TPL_ALERT_USER_SEARCH, $tags, Zend_Registry::get('lang'));
            if ($test) {
                Ccsd_Log::message("Fake envois de mail a " . $tags ['USER']);
            } else {
                $mail->writeMail();
            }
        }
    } else {
        Ccsd_Log::message("Pas de réponse CURL", true, 'ERR');
        Ccsd_Log::message("URL CURL : " . $queryString, true, 'ERR');
        Ccsd_Log::message("Requête pour : " . $search->getUser()->getFullName(), true, 'NOTICE');
    }
}

$timeend = microtime(true);
$time = $timeend - $timestart;

Ccsd_Log::message('Début du script: ' . date("H:i:s", $timestart) . '/ fin du script: ' . date("H:i:s", $timeend));
Ccsd_Log::message('Script executé en ' . number_format($time, 3) . ' sec.');
exit (0);



