<?php

/**
 * Build sitemaps
 * @see https://www.sitemaps.org/fr/
 */


$localopts = [
    'sid|s-s' => 'Nom du portail int SID ou string NAME (défaut: tous les portails)',
    'test|t' => 'Mode test'
];

if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

//Création du sitemap pour l'indexation de google
define('MODULE', 'portail');

define('MAX_URL_PER_SITEMAP', 25000);
define('SPACE_TABULATION', str_repeat(' ', 4));
define('XML_FILE_PROLOG', '<?xml version="1.0" encoding="UTF-8"?>');


define('SITEMAP_NAME_EXTENSION', '.xml.gz');
define('SITEMAP_FILENAME', 'sitemap');
define('SITEMAP_INDEX_FILENAME', 'sitemap.xml');
define('PUBLIC_SITEMAP_DIRECTORY', '/public/sitemap/');
define('SITEMAP_NAMESPACE', 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"');

$where = '';
if (isset($opts->s)) {
    if (preg_match('/^[0-9]+$/', $opts->s)) {
        $where = ' AND SID = ' . (int)$opts->s;
    } else {
        $where = ' AND SITE = "' . $opts->s . '"';
    }
}
$test = isset($opts->t);

debug("Environnement: " . APPLICATION_ENV);
debug("Database: " . HAL_PDO_URL);


//DB
$dbUser = HAL_USER;
$dbPwd = HAL_PWD;

$dbUserSolr = SOLR_USER;
$dbPwdSolr = SOLR_PWD;

$dbHALV3 = new PDO(HAL_PDO_URL, $dbUser, $dbPwd, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));
$dbSOLR = new PDO(SOLR_PDO_URL, $dbUserSolr, $dbPwdSolr, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\''));

$query = $dbHALV3->query('SELECT SID, SITE, URL FROM SITE WHERE TYPE="PORTAIL"' . $where);


$oldestValidDate = DateTime::createFromFormat('Y-m-d', '1970-01-01');


$xmlHeader = XML_FILE_PROLOG . PHP_EOL;
$xmlHeader .= '<urlset ' . SITEMAP_NAMESPACE . '>' . PHP_EOL;

foreach ($query->fetchAll() as $row) {
    $sid = $row['SID'];
    $portail = $row['SITE'];
    $url = $row['URL'];

    debug('# ' . $portail . ' (SID: ' . $sid . ')');

    $querySolR = ['fq' => 'sid_i:' . $sid, 'wt' => 'phps', 'q' => '*:*', 'fl' => 'halId_s,version_i,uri_s,submitType_s,modifiedDate_tdate', 'sort' => 'docid desc', 'rows' => MAX_URL_PER_SITEMAP, 'cursorMark' => '*'];
    $queryFq = Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json', 'json', $portail, false));

    verbose('- Filtre : ' . $queryFq);

    $fileId = 0;
    $page = 1;


    while (true) {
        debug("\nPage: $page", '', '', false);
        $result = unserialize(solr($querySolR, $queryFq));
        $nbitems = count($result['response']['docs']);
        debug(' (Nb docs: ' . $nbitems . ')', '', '', false);
        if ($nbitems) {
            $fileId++;

            $sitemap = $xmlHeader;
            foreach ($result['response']['docs'] as $row) {
                $documentModificationDateYmd = null;
                if ($row['modifiedDate_tdate']) {
                    try {
                        $documentModificationDate = new DateTime($row['modifiedDate_tdate']);

                        if ($documentModificationDate < $oldestValidDate) {
                            $documentModificationDateYmd = $oldestValidDate->format('Y-m-d');
                        } else {
                            $documentModificationDateYmd = $documentModificationDate->format('Y-m-d');
                        }

                    } catch (Exception $exception) {
                        $documentModificationDateYmd = null;
                    }
                }

                $sitemap .= '<url>' . PHP_EOL;
                $sitemap .= SPACE_TABULATION . '<loc>' . $row['uri_s'] . '</loc>' . PHP_EOL;
                if ($documentModificationDateYmd != null) {
                    $sitemap .= SPACE_TABULATION . '<lastmod>' . $documentModificationDateYmd . '</lastmod>' . PHP_EOL;
                }
                $sitemap .= '</url>' . PHP_EOL;

                if ($row['submitType_s'] == Hal_Document::FORMAT_FILE) {
                    $sitemap .= '<url>' . PHP_EOL;
                    $sitemap .= SPACE_TABULATION . '<loc>' . $row['uri_s'] . "/document</loc>" . PHP_EOL;
                    if ($documentModificationDateYmd != null) {
                        $sitemap .= SPACE_TABULATION . '<lastmod>' . $documentModificationDateYmd . '</lastmod>' . PHP_EOL;
                    }
                    $sitemap .= '</url>' . PHP_EOL;
                }

            }
            $sitemap .= '</urlset>' . PHP_EOL;


            //Enregistrement du fichier
            $dest = SPACE_DATA . '/' . MODULE . '/' . $portail . PUBLIC_SITEMAP_DIRECTORY;


            if (!$test) {

                if (!is_dir($dest)) {
                    mkdir($dest, 0777, true);
                }
                $filename = $dest . SITEMAP_FILENAME . $fileId . SITEMAP_NAME_EXTENSION;
                file_put_contents("compress.zlib://$filename", $sitemap);
                verbose('- Enregistrement du fichier : ' . $filename);
            } else {
                verbose('Test, but we would have output our data in: ' . $dest);
            }
        }
        if ($querySolR['cursorMark'] == $result['nextCursorMark']) {
            break;
        }
        $querySolR['cursorMark'] = $result['nextCursorMark'];
        debug('', ' (Mark:' . $querySolR['cursorMark'] . ')', 'blue', false);
        $page++;
    }

    //Création du fichier sitemap

    $sitemap = XML_FILE_PROLOG . PHP_EOL;
    $sitemap .= '<sitemapindex ' . SITEMAP_NAMESPACE . '>' . PHP_EOL;
    for ($i = 1; $i <= $fileId; $i++) {
        $sitemapFileDate = new DateTime();
        $sitemap .= '<sitemap>' . PHP_EOL;
        $sitemap .= SPACE_TABULATION . '<loc>' . $url . PUBLIC_SITEMAP_DIRECTORY . SITEMAP_FILENAME . $i . SITEMAP_NAME_EXTENSION . '</loc>' . PHP_EOL;
        $sitemap .= SPACE_TABULATION . '<lastmod>' . $sitemapFileDate->format(DATE_W3C) . '</lastmod>' . PHP_EOL;
        $sitemap .= '</sitemap>' . PHP_EOL;
    }
    $sitemap .= '</sitemapindex>' . PHP_EOL;
    $filename = $dest . SITEMAP_INDEX_FILENAME;

    if ($test) {
        print $sitemap;
    } else {
        file_put_contents($filename, $sitemap);
        verbose('- Enregistrement du fichier : ' . $filename);
    }
}
$timeend = microtime(true);
$time = $timeend - $timestart;
println('> Fin du script: ' . date("H:i:s", $timeend));
println('> Script executé en ' . number_format($time, 3) . ' sec.');

function solr($a, $fq)
{
    $query = [];
    foreach ($a as $p => $v) {
        $query[] = $p . '=' . rawurlencode($v);
    }
    $tuCurl = curl_init();
    curl_setopt($tuCurl, CURLOPT_USERAGENT, 'CCSD HAL sitemap creator');
    curl_setopt($tuCurl, CURLOPT_URL, 'http://ccsdsolrvip.in2p3.fr:8080/solr/hal/select?' . $fq . '&' . implode('&', $query));
    curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($tuCurl, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($tuCurl, CURLOPT_TIMEOUT, 300); // timeout in seconds
    curl_setopt($tuCurl, CURLOPT_USERPWD, 'ccsd:ccsd12solr41');
    $info = curl_exec($tuCurl);
    if (curl_errno($tuCurl) == CURLE_OK) {
        return $info;
    } else {
        exit(curl_errno($tuCurl));
    }
}