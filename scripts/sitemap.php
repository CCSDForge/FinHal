<?php

/**
 * Build sitemaps
 * @see https://www.sitemaps.org/fr/
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../library/Hal/Script.php';

/**
 * Class SitemapScript
 *
 * Creation des sitemaps de l'ensemble des portails (ou du portail spécifié en argument)
 */
class SitemapScript extends Hal_Script
{

    const MODULE = 'portail';
    const MAX_URL_PER_SITEMAP = 25000;
    const SPACE_TABULATION = '    ';
    const XML_FILE_PROLOG  ='<?xml version="1.0" encoding="UTF-8"?>';
    const SITEMAP_NAME_EXTENSION = '.xml.gz';
    const SITEMAP_FILENAME = 'sitemap';
    const SITEMAP_INDEX_FILENAME =  'sitemap.xml';
    const PUBLIC_SITEMAP_DIRECTORY = '/public/sitemap/';
    const SITEMAP_NAMESPACE = 'xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';

    const UNLIMITED = 2000;

    protected $options = [
        'sid|s-s' => 'Nom du portail int SID ou string NAME (défaut: tous les portails)',
        'test|t' => 'Mode test'
    ];

    /**
     * @param string $url
     * @param string $lastmod
     * @param bool $tab
     * @return string
     */
    public function doUrl($url, $lastmod, $tab) {
        $tabulation = $tab ? self::SPACE_TABULATION : '';
        $entry = '<url>' . PHP_EOL;
        $entry .= "$tabulation<loc>$url</loc>\n";
        if ($lastmod != null) {
            $entry .= "$tabulation<lastmod>$lastmod</lastmod>\n";
        }
        $entry .= '</url>' . PHP_EOL;
        return $entry;
    }
    /**
     * @param array $row
     * @param DateTime $oldestValidDate
     * @return string
     */
    public function doRow($row, $oldestValidDate) {
        $entry = '';
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
                // Date invalide...
                $documentModificationDateYmd = null;
            }
        }

        $entry .= $this->doUrl($row['uri_s'], $documentModificationDateYmd , true);

        if ($row['submitType_s'] == Hal_Document::FORMAT_FILE) {
            $entry .= $this->doUrl($row['uri_s']. "/document", $documentModificationDateYmd , true);
        }
        return $entry;
    }

    /**
     * @param $nbFile int
     * @param $url string
     * @return string
     */
    private function doMainSitemapFile($url, $nbFile) {
        $sitemap = self::XML_FILE_PROLOG . PHP_EOL;
        $sitemap .= '<sitemapindex ' . self::SITEMAP_NAMESPACE . '>' . PHP_EOL;
        for ($i = 1; $i <= $nbFile; $i++) {
            try {
                $sitemapFileDate = new DateTime();
            } catch (Exception $e) {
                // Normalement: pas d'exception vu que nous demandons la date du jour!
                Ccsd_Tools::panicMsg(__FILE__,__LINE__, "Ne devrait jamais se produire...");
                $sitemapFileDate = '';
            }

            $tabulation = self::SPACE_TABULATION;
            $sitemap .= '<sitemap>' . PHP_EOL;
            $sitemap .=  $tabulation. '<loc>' . $url . self::PUBLIC_SITEMAP_DIRECTORY . self::SITEMAP_FILENAME . $i . self::SITEMAP_NAME_EXTENSION . '</loc>' . PHP_EOL;
            $sitemap .=  $tabulation . '<lastmod>' . $sitemapFileDate->format(DATE_W3C) . '</lastmod>' . PHP_EOL;
            $sitemap .= '</sitemap>' . PHP_EOL;
        }
        $sitemap .= '</sitemapindex>' . PHP_EOL;
        return $sitemap;

    }

    /**
     * @param Zend_Console_Getopt $opts
     * @throws Zend_Db_Statement_Exception
     */
    public function main($opts)
    {
        /**
         * We need for th moment to define MODULE to 'portail'
         * Bad, but not terrible, we mangage only portail in those script...
         * @see Hal_Settings::getConfigFile() used in Hal_Search_Solr
         */
        define('MODULE', Hal_Site_Portail::MODULE);
        //Création du sitemap pour l'indexation de google
        $this->need_user('apache');

        $wantedSite = '';
        if (isset($opts->s)) {
            $wantedSite = $opts->s;
        }
        $test = isset($opts->t);

        $this->debug("Environnement: " . APPLICATION_ENV);


        $oldestValidDate = DateTime::createFromFormat('Y-m-d', '1970-01-01');

        $sites = Hal_Site::search($wantedSite, Hal_Site::TYPE_PORTAIL, self::UNLIMITED);
        $xmlHeader = self::XML_FILE_PROLOG . PHP_EOL;
        try {
            foreach ($sites as $siterow) {
                $sid = $siterow['SID'];
                $portail = $siterow['SITE'];
                $url = $siterow['URL'];
                $this->debug('# ------------------------------------------------');
                $this->debug('# ' . $portail . ' (SID: ' . $sid . ')');
                $dest = SPACE_DATA . '/' . Hal_Site_Portail::MODULE . '/' . $portail . self::PUBLIC_SITEMAP_DIRECTORY;
                $this->debug('# Repertoire de destination: ' . $dest);
                if (!$test && !is_dir($dest)) {
                    mkdir($dest, 0777, true);
                }

                $querySolR = ['fq' => 'sid_i:' . $sid, 'wt' => 'phps', 'q' => '*:*', 'fl' => 'halId_s,version_i,uri_s,submitType_s,modifiedDate_tdate', 'sort' => 'docid desc', 'rows' => self::MAX_URL_PER_SITEMAP, 'cursorMark' => '*'];
                $queryFq = Hal_Search_Solr_Search::getDefaultFiltersAsURL(Hal_Settings::getConfigFile('solr.hal.defaultFilters.json', 'json', $portail, false));

                $this->verbose('- Filtre : ' . $queryFq);

                $fileId = 0;
                $page = 1;

                while (true) {
                    $this->debug("\nPage: $page");
                    $result = unserialize($this->solr($querySolR, $queryFq));
                    $nbitems = count($result['response']['docs']);
                    $this->debug(' (Nb docs: ' . $nbitems . ')');
                    if ($nbitems) {
                        $fileId++;

                        $sitemap = $xmlHeader;
                        $sitemap .= '<urlset ' . self::SITEMAP_NAMESPACE . '>' . PHP_EOL;
                        foreach ($result['response']['docs'] as $row) {
                            $sitemap .= $this->doRow($row, $oldestValidDate);
                        }
                        $sitemap .= '</urlset>' . PHP_EOL;

                        //Enregistrement du fichier
                        $filename = $dest . self::SITEMAP_FILENAME . $fileId . self::SITEMAP_NAME_EXTENSION;
                        if (!$test) {
                            $this->verbose('- Enregistrement du fichier : ' . $filename);
                            file_put_contents("compress.zlib://$filename", $sitemap);
                        } else {
                            print('Test, but we would have output our data in: ' . $filename);
                        }
                    }
                    if ($querySolR['cursorMark'] == $result['nextCursorMark']) {
                        break;
                    }
                    $querySolR['cursorMark'] = $result['nextCursorMark'];
                    $page++;

                    $this->debug(' (Mark:' . $querySolR['cursorMark'] . ')');
                }

                //Création du fichier sitemap
                $sitemap = $this->doMainSitemapFile($url, $fileId);
                $filename = $dest . self::SITEMAP_INDEX_FILENAME;
                if ($test) {
                    print "Test, but we would have output sitemap main file to: $filename";
                    print $sitemap;
                } else {
                    file_put_contents($filename, $sitemap);
                    $this->verbose('- Enregistrement du fichier : ' . $filename);
                }
            }
        } catch (Zend_Config_Exception $e) {
            $this->displayError("Config file error: " . $e->getMessage());
        } catch (Ccsd_FileNotFoundException $e) {
            $this->displayError("Config file not found: " . $e->getMessage());
        }
    }

    /**
     * @param $a
     * @param $fq
     * @return bool|string
     * @throws Zend_Config_Exception
     * @throws Ccsd_FileNotFoundException
     */
    private function solr($a, $fq)
    {
        $options ['env'] = APPLICATION_ENV;
        $options ['defaultEndpoint'] = Ccsd_Search_Solr::ENDPOINT_MASTER;
        $core = 'hal';
        $options ['core'] = 'hal';

        $s = new Ccsd_Search_Solr($options);
        $endpointArray = $s->getEndpoints();
        $host = $endpointArray ['endpoint'] ['master'] ['host'];
        $port = $endpointArray ['endpoint'] ['master'] ['port'];
        $path = $endpointArray ['endpoint'] ['master'] ['path'];

        $user = $endpointArray ['endpoint'] ['master'] ['username'];
        $pwd  = $endpointArray ['endpoint'] ['master'] ['password'];
        $url = "http://$host:$port$path/$core/select";
        // ?&extractOnly=true&indent=false&extractFormat=text&wt=phps';

        $query = [];
        foreach ($a as $p => $v) {
            $query[] = $p . '=' . rawurlencode($v);
        }
        $tuCurl = curl_init();
        curl_setopt($tuCurl, CURLOPT_USERAGENT, 'CCSD HAL sitemap creator');
        curl_setopt($tuCurl, CURLOPT_URL, $url .'?' . $fq . '&' . implode('&', $query));
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($tuCurl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($tuCurl, CURLOPT_TIMEOUT, 300); // timeout in seconds
        curl_setopt($tuCurl, CURLOPT_USERPWD, "$user:$pwd");
        $this->debug('(');
        $info = curl_exec($tuCurl);
        $this->debug(')');
        if (curl_errno($tuCurl) == CURLE_OK) {
            return $info;
        } else {
            exit(curl_errno($tuCurl));
        }
    }
}

$script = new SitemapScript();
$script -> run();
