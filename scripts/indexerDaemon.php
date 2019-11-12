<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 11/01/18
 * Time: 16:54
 */

require_once __DIR__ . "/../vendor/library/Ccsd/Daemon.php";
require_once __DIR__ . "/../library/Hal/Script.php";

/** Load des Cores */
require_once __DIR__ . "/../library/Hal/Search/Solr/Indexer/DocumentCore.php";
require_once __DIR__ . "/../library/Hal/Search/Solr/Indexer/RefauthorCore.php";
require_once __DIR__ . "/../library/Hal/Search/Solr/Indexer/RefdomainCore.php";
require_once __DIR__ . "/../library/Hal/Search/Solr/Indexer/RefjournalCore.php";
require_once __DIR__ . "/../library/Hal/Search/Solr/Indexer/RefmetadataCore.php";
require_once __DIR__ . "/../library/Hal/Search/Solr/Indexer/RefprojetanrCore.php";
require_once __DIR__ . "/../library/Hal/Search/Solr/Indexer/RefprojeteuropeCore.php";
require_once __DIR__ . "/../library/Hal/Search/Solr/Indexer/RefstructureCore.php";


/**
 * Class IndexerHalDaemon
 *
 * Script d'indexation du core de Hal
 */
class IndexerHalScript extends Hal_Script {
    /** @var string  */
    public static $coreName = 'hal';
    /** @var array  */
    protected $options = [
        'docid|D=s'  => ' % pour réindexer tous les DOCID',
        'file=s'     => 'path to the file with docids (1 per line)',
        'cron=s'     => ' update ou delete pour un cron',
        'sqlwhere-s' => '= pour spécifier la condition SQL à utiliser pour trouver les DOCID',
        'delete=s'   => " Suppression de l'index de Solr avec requête de type solr (docid:19) (*:*)",
        'buffer|b=i' => " Nombre de doc à envoyer en même temps à l'indexeur",
        'delcache=s' => '[HAL] yes (par défaut) ou no : Supprime les cachemains des documents HAL',
        'indexpdf=s' => '[HAL] yes (par défaut) ou no : Indexe aussi les texte integral des PDF de HAL',
        'killafter|k-i'   => "Nbr de second avant de stopper les processus",
        'process|p-i'=> 'Nbr de processus demons a lancer',
        'core|c=s'   => 'Nom du core (hal, refauthor,...)',
        'corelist|l' => 'Liste les cores disponibles'
    ];

    /** @var  Ccsd_Script */
    private $subprogram;
    /**
     * Initialize  Options for the indexer
     */
    public function  __construct($subprogram = null) {
        $this -> subprogram = $subprogram;
        parent::__construct();
    }

    /** @param Ccsd_Search_Solr_Indexer */
    public function setSubprogram($classObj) {
        $this->subprogram = $classObj;
    }

    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt) {
        $this -> subprogram -> main($this->getOpts());
    }
}

$script     = new IndexerHalScript();
$name2core = Ccsd_Search_Solr_Indexer_Core::getCores();

if (isset($script->getOpts()->corelist)) {
    print "Liste des cores disponible:\n";
    foreach ($name2core as $coreName=>$class) {
        print "\t$coreName\n";require_once __DIR__ . "/../library/Hal/Script.php";

    }
    exit(0);
}

$core = $script->getOpts()-> core;
if ($core == null) {
     $script->println("Error: Core name not specified");
    exit (1);
}


if (in_array($core, array_keys($name2core) )) {
    $subprogram = new $name2core[$core]();
} else {
    $script->println("Error: Core name not permitted");
    exit (1);
}

$script->setSubprogram($subprogram);

/** Environnement Hal generique */
RuntimeConstDef('hal.archives-ouvertes.fr', 'hal');

$nbprocess  = $script->getOpts()->process;
$nbprocess  = ($nbprocess ? (int) $nbprocess : 1);

$iter = $script->getOpts()->iter;
$iter = ($iter ? (int) $iter : 10);

// Pas de demon si non lance par cron
$daemonize = isset($script->getOpts()->cron);

if ($daemonize) {
    $daemon = new Ccsd_Daemon($script, ['kill_after' => $iter, 'sleep_time' => 4, 'instances' => $nbprocess]);
    $daemon -> main(true);
} else {
    $script -> main($script->getOpts());
}