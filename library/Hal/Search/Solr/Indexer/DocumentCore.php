<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 22/01/18
 * Time: 16:16
 */

require_once 'Ccsd/Search/Solr/Indexer/Core.php';
require_once 'Ccsd/Search/Solr.php';
require_once 'Ccsd/Search/Solr/Indexer.php';
require_once 'Ccsd/Search/Solr/Indexer/Halv3.php';
/**
 * Class Hal_Search_Solr_Indexer_Core
 *
 * Classe utilise avec les scripts d'indexation pour definir le core utilise et traiter le options specifique de ce Core
 *
 */
class Hal_Search_Solr_Indexer_DocumentCore extends  Ccsd_Search_Solr_Indexer_Core
{
    /** @var string  */
    public static $indexerClass =  'Ccsd_Search_Solr_Indexer_Halv3';
    /**
     * @param Zend_Console_Getopt $getopt
     */
    protected function treadIndexerOptions($getopt)
    {
        parent::treadIndexerOptions($getopt);

        $this->addIndexerOption('delcache', true);
        $this->addIndexerOption('indexpdf', true);

        // Specific options for Hal core....
        if ($getopt->delcache == 'no') {
            $this->setIndexerOption('delcache', false);
        }
        if ($getopt->indexpdf == 'no') {
            $this->setIndexerOption('indexpdf', false);
        }
    }

}
Ccsd_Search_Solr_Indexer_Core::registerCore('Hal_Search_Solr_Indexer_DocumentCore');
