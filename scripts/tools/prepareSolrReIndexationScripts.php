<?php
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../../library/Hal/Script.php';

/**
 * Class prepareSolrReIndexationScripts
 * Generation of customized scripts for reindexing solr cores in parallel, using several servers
 */

class prepareSolrReIndexationScripts extends Hal_Script
{

    const DEFAULT_RANGE_OF_DOCIDS = 50000;
    const DEFAULT_BUFFER_OF_DOCS = 5;
    const DEFAULT_NUM_OF_FILES = 5;
    const DEFAULT_SOLR_CORE = 'hal';
    const DEFAULT_ENV = 'production';
    const DEFAULT_DELCACHE = 'yes';
    const DEFAULT_INDEXPDF = 'yes';
    const SOLR_CORE_TABLE = 'table';
    const SOLR_CORE_PRIMARY_KEY = 'primaryKey';
    const SOLR_CORE_WHERE_COND = 'whereCond';


    /**
     * @var array
     */
    protected $options = [
        'path|p=s' => 'Tell me where to write the scripts (full path plz)',
        'range|r=i' => 'How big is the range of docid (default: ' . self::DEFAULT_RANGE_OF_DOCIDS . ')',
        'numfiles|n=i' => 'Number of scripts to output (defaults to ' . self::DEFAULT_NUM_OF_FILES . ')',
        'buffer|b=i' => 'Number of docs to buffer/post to solr (default: ' . self::DEFAULT_BUFFER_OF_DOCS . ')',
        'indexpdf=s' => 'Documents only: Index full text of PDF (default: ' . self::DEFAULT_INDEXPDF . ')',
        'delcache=s' => 'Documents only: Rebuild all documents caches (default: '. self::DEFAULT_DELCACHE . ')',
        'core|c=s' => 'Solr Core (default ' . self::DEFAULT_SOLR_CORE . ')'
    ];

    /**
     * @var array
     */
    protected $coreOptions = [
        self::DEFAULT_SOLR_CORE => [self::SOLR_CORE_TABLE => 'DOCUMENT', self::SOLR_CORE_PRIMARY_KEY => 'DOCID', self::SOLR_CORE_WHERE_COND => 'DOCSTATUS=11 OR DOCSTATUS=111'],
        'ref_author' => [self::SOLR_CORE_TABLE => 'REF_AUTHOR', self::SOLR_CORE_PRIMARY_KEY => 'AUTHORID', self::SOLR_CORE_WHERE_COND => ''],
        'ref_structure' => [self::SOLR_CORE_TABLE => 'REF_STRUCTURE', self::SOLR_CORE_PRIMARY_KEY => 'STRUCTID', self::SOLR_CORE_WHERE_COND => ''],
        'ref_journal' => [self::SOLR_CORE_TABLE => 'REF_JOURNAL', self::SOLR_CORE_PRIMARY_KEY => 'JID', self::SOLR_CORE_WHERE_COND => ''],
        'ref_projanr' => [self::SOLR_CORE_TABLE => 'REF_PROJANR', self::SOLR_CORE_PRIMARY_KEY => 'ANRID', self::SOLR_CORE_WHERE_COND => ''],
        'ref_projeurop' => [self::SOLR_CORE_TABLE => 'REF_PROJEUROP', self::SOLR_CORE_PRIMARY_KEY => 'PROJEUROPID', self::SOLR_CORE_WHERE_COND => ''],

    ];


    /**
     * @var int
     */
    private $_minDocid;
    /**
     * @var int
     */
    private $_maxDocid;

    /**
     * @param Zend_Console_Getopt $args
     * @throws Zend_Db_Statement_Exception
     */
    public function main($args)
    {

        $outputPath = $args->path;
        $core = $args->core;
        $range = $args->range;
        $numberOfScripts = $args->numfiless;
        $environment = $args->environment;
        $delcache = $args->delcache;
        $buffer = $args->buffer;
        $indexpdf = $args->indexpdf;

        if ($range == null) {
            $range = self::DEFAULT_RANGE_OF_DOCIDS;
        }

        if ($core == null) {
            $core = self::DEFAULT_SOLR_CORE;
        }

        if ($numberOfScripts == null) {
            $numberOfScripts = self::DEFAULT_NUM_OF_FILES;
        }

        if ($environment == null) {
            $environment = self::DEFAULT_ENV;
        }

        if ($delcache == null) {
            $delcache = self::DEFAULT_DELCACHE;
        }

        if ($buffer == null) {
            $buffer = self::DEFAULT_BUFFER_OF_DOCS;
        }


        if ($indexpdf == null) {
            $indexpdf = self::DEFAULT_INDEXPDF;
        }


        $this->getMinMaxDocid($core);

        $this->verbose('MIN DOCID: ' . $this->getMinDocid());
        $this->verbose('MAX DOCID: ' . $this->getMaxDocid());

        $sysCommand = '"nohup su - nobody -s /bin/sh -c "';
        $phpCommand = '"/usr/bin/php ' . __DIR__ . '/../solrJob.php' . ' -c ' . $core . ' -e ' . $environment . ' --delcache ' . $delcache . ' -b ' . $buffer . ' --indexpdf ' . $indexpdf . ' --sqlwhere"';


        for ($i = $this->getMinDocid(); $i <= $this->getMaxDocid(); $i = $i + $range) {

            $next = $i + $range;
            $linesOfCommands[] = '$MY_SYSCOMMAND "$MY_PHPCOMMAND \'DOCID >= ' . $i . ' AND DOCID < ' . $next . '\'" &';
        }


        $this->verbose('Ranges: ' . $range);
        $this->verbose('Lines: ' . count($linesOfCommands));
        $this->verbose('Chunks: ' . $numberOfScripts);

        echo count($linesOfCommands) / $numberOfScripts;
        $slicesOfCakes = round(count($linesOfCommands) / $numberOfScripts, 0, PHP_ROUND_HALF_UP);
        $this->verbose('slicesOfCakes: ' . $slicesOfCakes);

        $linesToWrite = array_chunk($linesOfCommands, $slicesOfCakes);

        $fileNumber = 1;
        foreach ($linesToWrite as $lines) {

            $contentToWrite = '#!/bin/sh'
                . PHP_EOL
                . 'MY_SYSCOMMAND=' . $sysCommand
                . PHP_EOL
                . 'MY_PHPCOMMAND=' . $phpCommand
                . PHP_EOL

                . implode(PHP_EOL, $lines);

            $fileName = 'solrReindex' . ucfirst($core) . ucfirst($environment) . $fileNumber . '.sh';
            file_put_contents($outputPath . DIRECTORY_SEPARATOR . $fileName, $contentToWrite);
            $fileNumber++;
        }


    }

    /**
     * @param string $core
     * @throws Zend_Db_Statement_Exception
     */
    private function getMinMaxDocid($core = self::DEFAULT_SOLR_CORE)
    {
        $db = Zend_Db_Table::getDefaultAdapter();


        $table = $this->getCoreOption($core, self::SOLR_CORE_TABLE);
        $whereCond = $this->getCoreOption($core, self::SOLR_CORE_WHERE_COND);
        $primaryKey = $this->getCoreOption($core, self::SOLR_CORE_PRIMARY_KEY);

        $sql = $db->select()->from($table, [new Zend_Db_Expr('MIN(' . $primaryKey . ')'), new Zend_Db_Expr('MAX(' . $primaryKey . ')')]);

        if ($whereCond != '') {
            $sql->where($whereCond);
        }

        $stmt = $db->query($sql);
        $row = $stmt->fetch(Zend_Db::FETCH_NUM);

        $this->setMinDocid($row[0]);
        $this->setMaxDocid($row[1]);


    }

    /**
     * @param string $core
     * @param string $key
     * @return array|string
     */
    private function getCoreOption(string $core = self::DEFAULT_SOLR_CORE, $key = '')
    {

        if ($key != '') {
            return $this->getCoreOptions()[$core][$key];
        }

        return $this->getCoreOptions()[$core];

    }

    /**
     * @return array
     */
    public function getCoreOptions(): array
    {
        return $this->coreOptions;
    }

    /**
     * @return int
     */
    public function getMinDocid(): int
    {
        return $this->_minDocid;
    }

    /**
     * @param int $minDocid
     */
    public function setMinDocid(int $minDocid)
    {
        $this->_minDocid = (int)$minDocid;
    }

    /**
     * @return int
     */
    public function getMaxDocid(): int
    {
        return $this->_maxDocid;
    }

    /**
     * @param int $maxDocid
     */
    public function setMaxDocid(int $maxDocid)
    {
        $this->_maxDocid = (int)$maxDocid;
    }
}


$script = new prepareSolrReIndexationScripts();
$script->run();