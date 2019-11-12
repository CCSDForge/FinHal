<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 16/08/17
 * Time: 17:11
 */

set_include_path(__DIR__ . '/../library');
define('SOLRINIT', 1);

putenv('PORTAIL=hal');
// putenv('CACHE_ROOT=/cache/hal');
// putenv('DATA_ROOT=/data/hal');
// putenv('DOCS_ROOT=/docs');

$localopts = [
    'docid|D=s' => ' % pour réindexer tous les DOCID',
    'file=s' => 'path to the file with docids (1 per line)',
    'c=s' => ' core Solr',
    'cron=s' => ' update ou delete pour un cron',
    'sqlwhere-s' => '= pour spécifier la condition SQL à utiliser pour trouver les DOCID',
    'delete=s' => " Suppression de l'index de Solr avec requête de type solr (docid:19) (*:*)",
    'buffer|b=i' => " Nombre de doc à envoyer en même temps à l'indexeur",
    'delcache=s' => '[HAL] yes (par défaut) ou no : Supprime les caches des documents HAL',
    'indexpdf=s' => '[HAL] yes (par défaut) ou no : Indexe aussi les texte integral des PDF de HAL'
    ];

require_once(__DIR__ . '/../public/bddconst.php');
if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

# accepted core: hal, ref*   (pas episciences/sciencesocnf)

define('DEFAULT_CONFIG_PATH', DEFAULT_CONFIG_ROOT .  SPACE_PORTAIL);

require __DIR__ . '/library/solr/indexer/job.php';

