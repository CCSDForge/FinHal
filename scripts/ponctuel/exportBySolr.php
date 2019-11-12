<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 08/02/19
 * Time: 13:52
 */


if (file_exists(__DIR__ . '/../../vendor/autoload.php'))
    require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . "/../../library/Hal/Script.php";

/**
 * Execute une requete solR puis stock chaque resultat dans un fichier
 * Exemple: recuperation de tous les fichiers les  tei de l'annee 2015
 * exportBySolr -q structCountry_s:fr --fq producedDateY_i:2015 -f label_xml -n halId_s --ext .xml --dir madird
 *
 * &wt=json&fq=producedDateY_i:2015&fl=label_xml,halId_s&sort=docid asc&cursorMark=*
 *
 *
 */
class exportBySolr extends Hal_Script {
        protected $options = [
            'query|q=s'   => "Solr query string to get documents",
            'filter|fq-s'    => "Solr filter string",
            'dir|todir=s' => "Existing Path where to put documents ",
            'field|f=s'   => "Field name to take in result document and to save",
            'name|n-s'    => 'field name in document to use as filename',
            'limit|l-i'   => 'Limit to "arg" pages of results',
            'ext-s'       => 'Suffix (Extension) to append to filename (Add . yourself! eg:  .xml',
            'filename-s'    => "Put all result in this file (can't give name option)",
        ];

    /**
     * Boucle sur le tokens solr pour avoir tous les resultats
     * Pour chaque resultat, appel la fct <handler>
     * @param string[] $querySolR
     * @param void(array, int) $handler
     * @param int $limit
     * @return int[]
     *
     * @todo: A mettre ailleurs :fonction generique Solr!
     */
    private function solrLoop($querySolR, $handler, $limit = 10000000) {
        $page = 0;
        $nbDocs = 0;
        while (true) {
            $this->println("\rGet page: ",  $page + 1, Ccsd_Runable::BASH_BLUE, false);
            $solrResult = self::solr($querySolR, true);
            $result = unserialize($solrResult);
            $page++;
            foreach ($result['response']['docs'] as $row) {
                $handler($row, $nbDocs);
                $nbDocs++;
            }
            if ($page > $limit) {
                break;
            }
            if ($querySolR['cursorMark'] == $result['nextCursorMark']) {
                break;
            }
            $querySolR['cursorMark'] = $result['nextCursorMark'];
        }
        $this->println("\n");
        return [ $page, $nbDocs];
    }

    /**
     * @param Zend_Console_Getopt $opt
     */
    public function main($opt)
    {
        $filenameField = $opt->getOption('name');
        $contentField  = $opt->getOption('field');
        $dir     = $opt->getOption('dir');
        $ext     = $opt->getOption('ext');
        $limit   = $opt->getOption('limit');
        $query   = $opt->getOption('query');
        $filter  = $opt->getOption('filter');
        $oneFile = $opt->getOption('filename');

        if ($oneFile && $filenameField) {
            die("Can't have a fileNameField and a unique filename given at the same time... choose!");
        }

        if (!$limit) {
            $limit = 1000000000;
        }
        $fiellist = trim("$filenameField,$contentField", ", \t\n");

        $querySolR = [
            'q'    => $query,
            'fq'   => $filter,
            'fl'   => $fiellist,
            'sort' => "docid desc",
            'rows' => '1000',
            'wt'   => 'phps',
            'cursorMark' => '*'
        ];
        $handler = function ($row, $_) use ($filenameField, $contentField, $dir, $ext, $oneFile) {
            $content = $row[$contentField];
            if ($oneFile) {
                $name = $oneFile;
                $content .= "\n";
                $flags = FILE_APPEND;
            } else {
                $name = $row[$filenameField];
                $flags = null;
            }
            $filename = $dir . '/' . $name . $ext;
            file_put_contents($filename, $content, $flags);
        };
        $res = $this-> solrLoop($querySolR, $handler, $limit);
            $page   = $res[0];
            $nbDocs = $res[1];
        if ($this->isDebug()) {
            println("$page pages of results");
            println("$nbDocs documents written");
        }
    }

    /**
     * @todo: A mettre ailleurs pour sortir le pwd et les attribut de connextion
     *        On devrait pouvoir avoir une classe solr nouvelle qui prends en compte ce cas!
     * @param $a
     * @param bool $encode
     * @return bool|string
     */
    private static function solr($a, $encode = false)
    {
        $query = [];
        foreach ($a as $p => $v) {
            $query[] = $p . '=' . ($encode ? rawurlencode($v) : $v);
        }
        $tuCurl = curl_init();
        curl_setopt($tuCurl, CURLOPT_USERAGENT, 'CcsdToolsCurl');
        $url =  'http://ccsdsolrvip.in2p3.fr:8080/solr/hal/select?' . implode('&', $query);
        curl_setopt($tuCurl, CURLOPT_URL,$url);
        curl_setopt($tuCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($tuCurl, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($tuCurl, CURLOPT_TIMEOUT, 300); // timeout in seconds
        curl_setopt ( $tuCurl, CURLOPT_USERPWD, 'ccsd:ccsd12solr41' );
        $info = curl_exec($tuCurl);
        if (curl_errno($tuCurl) == CURLE_OK) {
            return $info;
        } else {
            exit(curl_errno($tuCurl));
        }
    }

}

$script = new exportBySolr();
$script->run();