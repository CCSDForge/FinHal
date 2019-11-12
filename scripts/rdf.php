<?php
/**
 * Created by PhpStorm.
 * User: yannou
 *
 * cron qui attend
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . "/../library/Hal/Script.php";

/**
 * Class rdfScript
 *
 * Utilise pour mettre a jour les fichier RDF
 * Si: Id, on ne fait que l'Id
 * Si: Cron: On lit la base Queue
 *      Sinon, pour les document, on les fait TOUS (1000 par 1000)
 * Si From: on commence a partir d'un identifiant et on les fait tous....
 *      (Utile pour reprise apres erreur...)
 *      Non Valable pour le graphe "Document"
 */
class rdfScript extends Hal_Script
{
    static private $rdfConfig = [
        'idhal' => ['ref' => 'Hal_Cv',
            'rdf' => 'Hal_Rdf_Idhal'],
        'typdoc' => [
            'ref' => 'Ccsd_Referentiels_Typdoc',
            'rdf' => 'Hal_Rdf_Typdoc'],
        'author' => [
            'ref' => 'Ccsd_Referentiels_Author',
            'rdf' => 'Hal_Rdf_Author',
            'core' => 'ref_author'],
        'structure' => [
            'ref' => 'Ccsd_Referentiels_Structure',
            'rdf' => 'Hal_Rdf_Structure',
            'core' => 'ref_structure'],
        'subject' => [
            'ref' => 'Ccsd_Referentiels_Domain',
            'rdf' => 'Hal_Rdf_Domain',
            'core' => 'ref_domain'],
        'anrproject' => [
            'ref' => 'Ccsd_Referentiels_Anrproject',
            'rdf' => 'Hal_Rdf_Anrproject',
            'core' => 'ref_projanr'],
        'europeanproject' => [
            'ref' => 'Ccsd_Referentiels_Europeanproject',
            'rdf' => 'Hal_Rdf_Europeanproject',
            'core' => 'ref_projeurop'],
        'revue' => [
            'ref' => 'Ccsd_Referentiels_Journal',
            'rdf' => 'Hal_Rdf_Journal',
            'core' => 'ref_journal'],
        'document' => ['core' => 'hal']
    ];

    /**
     * @var array
     */
    protected $options = [
        'graph|g-s' => 'Graphe (author,idhal,typdoc,structure,subject,anrproject,europeanproject,revue,document) ',
        'id-s'      => 'Record Identifier',
        'from|f-s'  => 'Start from an Id',
        'cron-s'    => 'cron task, must use  ("update" or "delete")'];

    /**
     * @var bool
     */
    protected $workingWithCron = false;

    /**
     * @param Zend_Console_Getopt $options
     */
    function main($options)
    {
        $this->need_user('apache');
        $listGraphs = array_keys(self::$rdfConfig);
        $GrapheName = $options->getOption('graph');
        if ($GrapheName === null) {
            $this->displayError($options->getUsageMessage());
            die('Mauvais Graphe ($GrapheName)');
        }
        if ($GrapheName == 'all') {
            $GrapheNames = $listGraphs;
        } else {
            if (!in_array($GrapheName, $listGraphs)) {
                $this->displayError($options->getUsageMessage());
                die('Graphe non specifie');
            }
            $GrapheNames = [$GrapheName];
        }
        define('SITEID', 1);

        $id = $options->getOption('id');
        $from = $options->getOption('from');

        $this->verbose('****************************************');
        $this->verbose('**  Generation / suppression des RDF  **');
        $this->verbose('****************************************');
        $this->verbose('> Début du script: ' . date("H:i:s" . $this->_init_time));
        $this->verbose('> Environnement: ' . APPLICATION_ENV);
        $ids = [];
        if ($id) {
            $ids = explode(',', $id);
            $this->verbose('> Id: ' . $id);
        }
        if ($from) {
            $this->verbose('> From: ' . $from);
        }
        $cron = $options->getOption('cron');

        foreach ($GrapheNames as $GrapheName) {
            $this->verbose('> Graphe: ' . $GrapheName);
            if (isset($cron) && (array_key_exists('core', self::$rdfConfig[$GrapheName]))) {
                // Pas de mise a jour incrementale pour idhal et typdoc
                $queue = new Hal_Rdf_Queue();
                switch (strtoupper($cron)) {
                    case Ccsd_Queue::O_UPDATE:
                        //break omitted
                    case Ccsd_Queue::O_DELETE:
                        $queue->setOrigin($cron);
                        $this->verbose('Mode: $cron');
                        break;
                    default:
                        echo $options->getUsageMessage();
                        die('cron value must be "update" or "delete"');
                        break;
                }
                $queue->setCore(self::$rdfConfig[$GrapheName]['core']);
                $this->workingWithCron = true;
            } else {
                $queue = null;
                $this->workingWithCron = false;
            }
            try {
                $this->treatEntry($GrapheName, $from, $ids, $queue);
            } catch (Zend_Db_Exception $e) {
                $this->displayError("Probleme avec la base SQL:\n" . $e->getMessage());
            }
        }
    }
    /**
     * @param string $GrapheName
     * @param int[] $ids : Docid
     * @param string $from : Date
     * @param Hal_Rdf_Queue $queue
     * @throws Zend_Db_Exception
     */
    function treatEntry($GrapheName, $from, $ids, $queue) {
        if ('document' == $GrapheName) {
                $this->treatDocuments($ids, $queue);
            } else {
                $this->treatRefentialData($GrapheName, $ids, $from, $queue);
            }
    }
    /**
     * @param string $graph
     * @param int[] $ids : Docid
     * @param string $from : Date
     * @param Hal_Rdf_Queue $queue
     */
    function treatRefentialData($graph, $ids, $from, $queue)
    {

        foreach ($this->getIdsFromRef(self::$rdfConfig[$graph]['ref'], $ids, $from, $queue) as $docid) {
            if (($this->workingWithCron) && ($queue->getOrigin() == Ccsd_Queue::O_DELETE)) {
                $this->delete(self::$rdfConfig[$graph]['rdf'], $docid);
            } else {
                $this->debug('Processing ' . $graph . " \t " . $docid);
                try {
                    $this->generate(self::$rdfConfig[$graph]['rdf'], $docid);
                } catch (Hal_Rdf_Exception $exception) {
                    if ($queue instanceof Hal_Rdf_Queue) {
                        $queue->setMessage($exception->getMessage());
                        $queue->putProcessedRowInError($docid);
                    }
                    $this->println('Processing ' . $docid . ' failed with: ' . $exception->getMessage(), ' Failed', Ccsd_Runable::BASH_RED);
                }
            }

            if ($queue instanceof Hal_Rdf_Queue) {
                $queue->deleteProcessedRows(array($docid));
            }
        }
    }

    /**
     * @param int[] $ids
     * @param Hal_Rdf_Queue $queue
     * @throws Zend_Db_Statement_Exception
     */
    function treatDocuments($ids = [], $queue = null)
    {
        $cursor = 0;
        if ($ids !== []) {
            $res = $ids;
        } else {
            $res = $this->getIdsFromDocuments($cursor, $queue);
        }
        while (count($res) > 0) {

            foreach ($res as $docid) {
                try {
                    if (($this->workingWithCron) && ($queue->getOrigin() == Ccsd_Queue::O_DELETE)) {
                        $resDelete = Hal_Rdf_Document::deleteCacheRdfFromDocid(Hal_Rdf_Document::getGraph(), $docid);
                        if ($resDelete) {
                            $this->debug('Delete: ' . $docid . ' OK');
                        } else {
                            $this->println('Delete: ' . $docid, ' Failed', Ccsd_Runable::BASH_RED);
                        }
                    } else {
                        // On n'implemente pas le Delete hors cron, il suffit d'effacer le fichier
                        // TODO: A revoir peut etre pour l'import dans Virtuoso
                        $rdf = new Hal_Rdf_Document($docid);
                        $this->debug('Processing: ' . $docid);
                        $rdf->getRdf(false);
                    }
                } catch (Hal_Rdf_Exception $exception) {
                    if ($queue instanceof Hal_Rdf_Queue) {
                        $queue->setMessage($exception->getMessage());
                        $queue->putProcessedRowInError($docid);
                    }
                    $this->println('Processing ' . $docid . ' failed with: ' . $exception->getMessage(), ' Failed', Ccsd_Runable::BASH_RED);
                    continue;
                }
                if ($queue instanceof Hal_Rdf_Queue) {
                    $queue->deleteProcessedRows(array($docid));
                }
                $cursor = $docid;
            }
            if ($ids !== []) {
                $res = [];  // fin de traitement: il y avait une  seule liste de  documents a traiter: pas de pagination...
            } else {
                $res = $this->getIdsFromDocuments($cursor, $queue);
            }
        }
    }

    /**
     * Get all ids from HAL documents
     * @param int $cursor
     * @param Hal_Rdf_Queue $queue
     * @return mixed
     * @throws Zend_Db_Statement_Exception
     */
    function getIdsFromDocuments($cursor, $queue = null)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        if ($queue == null) {
            $query = $db->query('SELECT DOCID FROM ' . Hal_Document::TABLE . ' WHERE DOCSTATUS IN (11, 111) AND DOCID > ' . $cursor . ' ORDER BY DOCID ASC LIMIT 1000');
            $reqSql = $query->fetchAll();
            return array_column($reqSql, 'DOCID');
        } else {
            return $queue->getListOfDocidFromQueue();
        }
    }

    /**
     * Get one or all ID from a referential
     * @param $class
     * @param int[] $ids
     * @param int $from
     * @param Hal_Rdf_Queue $queue
     * @return array
     * @uses Hal_Rdf_Anrproject
     * @uses Hal_Rdf_Structure
     * @uses Hal_Rdf_Author
     * @uses Hal_Rdf_Domain
     * @uses Hal_Rdf_Europeanproject
     * @uses Hal_Rdf_Idhal
     * @uses Hal_Rdf_Journal
     * @uses Hal_Rdf_Typdoc
     */
    function getIdsFromRef($class, $ids = [], $from = null, $queue = null)
    {
        //Queue param
        if ($queue != null) {
            return $queue->getListOfDocidFromQueue();
        }

        // Id param
        /** @var Ccsd_Referentiels_Abstract $ref */

        if ($ids != []) {
            $list=[];
            foreach ($ids as $id) {
                $ref = new $class();
                if (!$ref->exist($id)) {
                    die('Identifiant non valide:' . $id);
                }
                $list[] = $id;
            }
            return $list;
        }
        // From param
        $ref = new $class();
        return $ref->getIds($from);
    }

    /**
     * Generate RDF File
     * @param $class
     * @param $id
     * @throws Hal_Rdf_Exception
     */
    function generate($class, $id)
    {
        /** @var Hal_Rdf_Abstract $rdf */
        $rdf = new $class($id);
        $rdf->getRdf(false);
        $this -> debug($id . ':' .  'OK');
    }

    /**
     * Delete RDF File
     * @param $class
     * @param int $id
     * @return boolean
     */
    function delete($class, $id)
    {
        $rdf = new $class($id);
        /** @var Hal_Rdf_Abstract $rdf */
        try {
            $resDelete = $rdf->deleteRdf();
            $cacheFile = 'Delete: ' . $rdf->getCachePath() . $rdf->getCacheName();
            if ($resDelete) {
                $this->debug('Delete: ' . $cacheFile . ' OK');
            } else {
                $this->println('Delete: ' . $cacheFile , ' Failed', Ccsd_Runable::BASH_RED);
            }
            return $resDelete;
        } catch (Hal_Rdf_Exception $e) {
            return false;
        }

    }
}

$script = new rdfScript();
$script->run();
print $script->getDisplayableRunTime();