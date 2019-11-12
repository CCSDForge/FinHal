<?php
/**
 * Created by PhpStorm.
 * User: yannou
 * Date: 07/05/2014
 * Time: 16:16
 *
 * /Applications/MAMP/bin/php/php5.5.10/bin/php Documents/htdocs/phpstorm/halv3/scripts/tamponnate.php
 */

$localopts = array(
    'col|c-s'  => 'Nom ou SID des collections (séparateur ",") (défaut: toutes les collections automatiques)',
    'from|f-s' => 'Le script se base sur les dépôts depuis la date données (défaut: veille)',
    'test|t'   => 'Lance le script en mode test (sans tamponnage/détamponnage)',
    'del'      => 'Détamponne une collection',
    'detamp'   => 'Détamponne les documents répondants au critère de la collection qui sont tamponnés',
);
$rootId = 100000;

require_once(__DIR__ . '/../public/bddconst.php');
if (file_exists(__DIR__ . "/loadHalHeader.php")) {
    require_once __DIR__ . '/loadHalHeader.php';
} else {
    require_once 'loadHalHeader.php';
}

/* Environnements */
$listEnv = array('testing', 'preprod', 'production');
$defaultEnv = 'testing';

Zend_Registry::set('languages', array('fr','en','es','eu'));
Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));

Zend_Registry::set ( ZT, Hal_Translation_Plugin::checkTranslator ( 'fr' ) );
Zend_Registry::set ( ZT, Hal_Translation_Plugin::checkTranslator ( 'en' ) );
Zend_Registry::set ( ZT, Hal_Translation_Plugin::checkTranslator ( 'es' ) );
Zend_Registry::set ( ZT, Hal_Translation_Plugin::checkTranslator ( 'eu' ) );
/** @var Zend_Console_Getopt $opts */
//Date de dépôt des documents
$from = (isset($opts->f)) ? $opts->f : date("Y-m-d", strtotime( '-30 days' ) );
//mode test
$test = isset($opts->t);
//Débug
$verbose = isset($opts->v);
$debug   = isset($opts->d);
$debug |= $verbose;

//detamponnage d'une collection
$del = isset($opts->del);
$detamp = isset($opts->detamp);


$collections = array();
//Récupération des collections
if (isset($opts->c)) {
    $colSid = $colName = array();
    foreach (explode(',', $opts->c) as $c) {
        $colSid[] = (int) $c;
        $colName[] = (string) $c;
    }
    //On vérifie l'existance de la collection
    $sql = $db->select()
        ->from(array('s' => 'SITE'), array('SID', 'SITE'))
        ->join(array('c' => 'COLLECTION_SETTINGS'), 's.SID=c.SID', null)
        ->where($db->quoteInto('s.SID IN (?)', $colSid) . ' OR ' . $db->quoteInto('s.SITE IN (?)', $colName))
        ->where('s.TYPE = "COLLECTION"')
        ->where('c.SETTING = "mode"')
        ->where('c.VALUE = "auto"');
    $collections = $db->fetchPairs($sql);
    if (!$collections) {
        die ("La collection " . $opts->c . " n'existe pas !!" . PHP_EOL . PHP_EOL);
    }
} else {
    //On récupère toutes les collections automatiques
    $sql = $db->select()
        ->from(array('s' => 'SITE'), array('SID', 'SITE'))
        ->join(array('c' => 'COLLECTION_SETTINGS'), 's.SID=c.SID', null)
        //->where('s.SID <= 666')
        ->where('s.TYPE = "COLLECTION"')
        ->where('c.SETTING = "mode"')
        ->where('c.VALUE = "auto"');
    $collections = $db->fetchPairs($sql);
}


println();
println('','****************************************', 'blue');
println('','** Script de Tamponnage des documents **', 'blue');
println('','****************************************', 'blue');
println('> Début du script: ', date("H:i:s", $timestart), '');
println('> Environnement: ', APPLICATION_ENV, '');
println('> Dépôts depuis: ', $from, '');
println('> Nombre de collection: ', count($collections), '');
println('', '----------------------------------------', 'yellow');

foreach ($collections as $sid => $name) {

    $site = Hal_Site::loadSiteFromId($sid);
    if ($debug) {
        println('','** Collection: '. $name . ' (SID=' . $sid . ')', 'blue');
    }

    if ($del) {
        /*
         * Détamponnage des documents tamponnés automatiquement à partir d'une date donnée
         */
        if ($debug) {
            println('> Détamponnage des documents depuis le ', $from, 'blue');
        }
        $sql = $db->select()->from('DOC_TAMPON', 'DOCID')->where('UID = ?', $rootId)->where('SID = ?', $sid)->where('DATESTAMP > ?', $from);
        $docToDeTamponnate = $db->fetchCol($sql);
        if ($debug) {
            println('> Nombre de documents à détamponner modifiés depuis le ' . $from . ': ', count($docToDeTamponnate), 'blue');
        }
        foreach($docToDeTamponnate as $docid) {
            if (! $test) {
                $res = Hal_Document_Collection::del($docid, $site);
                if ($verbose) {
                    println('> Détamponnage du document ' . $docid . ': ', ($res ? 'OK' : 'KO'), ($res ? 'green' : 'red'));
                }
            }
        }
        continue;
    }

    if ($detamp) {
        /*
         * Détamponnage des documents détamponnés par un utilisateur puis retamponnés ensuite par le script de tamponnage par erreur
         */
        if ($debug) {
            println('> Vérification du tamponnage ', $from, 'blue');
        }
        $sql = $db->select()->distinct()->from('DOC_LOG', 'DOCID')->where('UID != ?', $rootId)->where('LOGACTION = ?', Hal_Document_Logger::ACTION_DELTAMPON)->where('MESG = ?', $sid)->where('DATELOG > ?', $from);
        $docDeTamponnate = $db->fetchCol($sql);
        if ($debug) {
            println('> Nombre de documents détamponner manuellement depuis le ' . $from . ': ', count($docDeTamponnate), 'blue');
        }
        $sql = $db->select()->distinct()->from('DOC_TAMPON', 'DOCID')->where('DOCID IN (?)', count($docDeTamponnate)?$docDeTamponnate:0)->where('SID = ?', $sid);
        $docToDeTamponnate = $db->fetchCol($sql);
        if ($debug) {
            println('> Nombre de documents à détamponner modifiés depuis le ' . $from . ': ', count($docToDeTamponnate), 'blue');
        }
        foreach($docToDeTamponnate as $docid) {
            if (! $test) {
                $res = Hal_Document_Collection::del($docid, $site);
                if ($verbose) {
                    println('> Détamponnage du document ' . $docid . ': ', ($res ? 'OK' : 'KO'), ($res ? 'green' : 'red'));
                }
            }
        }
        continue;
    }

    $docToTamponnate = $docToDeTamponnate = array();

    //Récupération du critère de tamponnage de la collection
    $critere = Hal_Site_Collection::getFullCritere($sid);

    if (trim($critere) == '') {
        /*
         * Collection automatique sans critère
         * On tamponne la collection en se basant sur les sous-collections
         */

        if ($debug) {
            println('** Collection: ' . $name . ' (SID=' . $sid . ') ', 'Aucun critère', 'red');
        }

        //On récupère les sous-collections
        $sql = $db->select()
            ->from(array('s' => 'SITE'), array('SID', 'SITE'))
            ->join(array('c' => 'COLLECTION_SETTINGS'), 's.SID=c.SID', 'VALUE')
            ->join(array('p' => 'SITE_PARENT'), 's.SID=p.SID', null)
            ->where('p.SPARENT = ?', $sid)
            ->where('c.SETTING = "mode"');

        $critereDetamp = [];
        $docCollectionsFilles = [];
        foreach ($db->fetchAll($sql) as $collectionFille) {
            if ($debug) {
                println('> Collection fille: ', $collectionFille['SITE'] . ' (SID=' . $collectionFille['SID'] . ') - mode ' . $collectionFille['VALUE'], 'blue');
            }

            $critere = Hal_Site_Collection::getFullCritere($collectionFille['SID']);
            if (trim($critere) == '()') {
                $critere = '';
            }

            if ($collectionFille['VALUE'] == 'auto' && $critere != '') {
                //collection fille en mode automatique

                $res = Hal_Site_Collection::getAssociatedPortail($collectionFille['SID']);
                if ($res) {
                    $critere = '(' . $critere . ' OR sid_i:' . $res . ')';
                }
                //Tamponnage
                $docToTamponnate = array_merge($docToTamponnate, addStamp($site, $critere, $from, $test, $debug, $verbose));

                //Détamponnage - Récupération des documents des collections filles
                $solrRequest  = "q=*&";
                $solrRequest .= "fq=collId_i:" . $collectionFille['SID'] . "&fq=modifiedDate_s:" . urlencode('[' . $from . ' TO NOW]') . "&";
                $solrRequest .= "rows=100000&wt=phps&fl=docid&omitHeader=true";
                println($solrRequest);
                $res = unserialize(Ccsd_Tools::solrCurl($solrRequest, 'hal', 'select', 0));
                if (isset($res['response']['numFound']) && isset($res['response']['docs'])) {
                    foreach ($res['response']['docs'] as $d) {
                        $docCollectionsFilles[] = $d['docid'];
                    }
                }
            } else {
                //collection fille en mode manuel ou automatique sans critère

                //Tamponnage
                $sqlTmp = $db->select()
                    ->from('DOC_TAMPON', 'DOCID')
                    ->where('SID = ?', $sid);

                $sql2 = $db->select()
                    ->from('DOC_TAMPON', 'DOCID')
                    ->where('SID = ?', $collectionFille['SID'])
                    ->where('DATESTAMP >= ?', $from)
                    ->where('DOCID NOT IN (' . $sqlTmp . ')');
                $docToTamponnate = $db->fetchCol($sql2);
                if (!$test) {
                    foreach ($docToTamponnate as $docid) {
                        $res = Hal_Document_Collection::add($docid, $site);
                        if ($verbose) {
                            println('> Tamponnage du document ' . $docid . ': ', ($res ? 'OK' : 'KO'), ($res ? 'green' : 'red'));
                        }
                    }
                }
                if ($debug && count($docToTamponnate)) {
                    println('> Nombre de documents à tamponner modifiés depuis le ' . $from . ': ', count($docToTamponnate), 'blue');
                }
                if ($verbose && count($docToTamponnate)) {
                    println('> Liste des documents à tamponner: ', implode(', ', $docToTamponnate), 'blue');
                }

                //Détamponnage
                //On récupère tous les documents modifiés depuis la date $from tamponnés avec le tampon fille
                $sql2 = $db->select()
                    ->from(['tampon' => 'DOC_TAMPON'], 'DOCID')
                    ->join(['document' => 'DOCUMENT'], 'document.DOCID = tampon.DOCID', null)
                    ->where('tampon.SID = ?', $collectionFille['SID'])
                    ->where('document.DATEMODIF >= ?', $from);
                $docCollectionsFilles = array_merge($docCollectionsFilles, $db->fetchCol($sql2));
            }

        }
        if ($docCollectionsFilles) {
            $docCollectionsFilles = array_unique($docCollectionsFilles);
            $sql2 = $db->select()
                ->from('DOC_TAMPON', 'DOCID')
                ->where('SID = ?', $sid)
                ->where('UID = ?', $rootId)
                ->where('DOCID NOT IN (?)', $docCollectionsFilles)
                ->where('DATESTAMP >= ?', $from);
            $docToDeTamponnate = $db->fetchCol($sql2);
            //println(implode(', ', $docToDeTamponnate));exit;

            if (!$test) {
                foreach ($docToDeTamponnate as $docid) {
                    $res = Hal_Document_Collection::del($docid, $site);
                    if ($verbose) {
                        println('> Detamponnage du document ' . $docid . ': ', ($res ? 'OK' : 'KO'), ($res ? 'green' : 'red'));
                    }
                }
            }
            if ($debug && count($docToDeTamponnate)) {
                println('> Nombre de documents à détamponner modifiés depuis le ' . $from . ': ', count($docToDeTamponnate), 'blue');
                println('> Liste des documents à détamponner: ', implode(', ', $docToDeTamponnate), 'blue');
            }
            if ($verbose && count($docToDeTamponnate)) {
                println('> Liste des documents à détamponner: ', implode(', ', $docToDeTamponnate), 'blue');
            }
        }
    } else {
        /*
         * Collection automatique avec critère
         */

        $res = Hal_Site_Collection::getAssociatedPortail($sid);
        if ($res) {
            //Exception pour les portails/collections
            $critere = '(' . $critere . ' OR sid_i:' . $res . ')';
        }

        if ($debug) {
            println('> Critère de tamponnage: ', $critere, 'blue');
        }

        //Tamponnage
        $docToTamponnate = addStamp($site, $critere, $from, $test, $debug, $verbose);

        //Détamponnage
        $docToDeTamponnate = delStamp($site, $critere, $from, $test, $debug, $verbose, $db);

    }

    if (! $debug ) {
        if (count($docToTamponnate) || count($docToDeTamponnate)) {
            println('** Collection: '. $name . ' (SID=' . $sid . ') ', 'tamponnage: ' . count($docToTamponnate) /*.' | détamponnage:' . count($docToDeTamponnate)*/, 'blue');
        }
    } else {
        println('', '----------------------------------------', 'yellow');
    }
}

$timeend = microtime(true);
$time = $timeend - $timestart;
println('> Fin du script: ' . date("H:i:s", $timeend));
println('> Script executé en ' . number_format($time, 3) . ' sec.');
println();

/**
 * Tamponnage d'une collection
 *
 * Récupère de SolR les documents en ligne répondants au critère de la collection et non tamponnés pour la collection
 *
 * @param Hal_Site $site identifiant de la collection
 * @param string $critere critère de tamponnage
 * @param string $from date de modification des dépôts
 * @param boolean $test mode test
 * @param boolean $debug active les débug
 * @param boolean $verbose active les débugs complets
 * @return array liste des document tamponnés
 * @throws Exception
 */
function addStamp($site, $critere, $from, $test, $debug, $verbose)
{
    $sid = $site->getSid();
    $docToTamponnate = array();

    $solrRequest  = "q=*&";
    $solrRequest .= "fq=status_i:11&fq=" . urlencode($critere) . "&fq=NOT(collId_i:" . $sid . ")&fq=modifiedDate_s:" . urlencode('[' . $from . ' TO NOW]') . "&";
    $solrRequest .= "rows=1000000&wt=phps&fl=docid&omitHeader=true";

    if ($verbose) {
        println('> Requête SolR tamponnage: ', $solrRequest, 'blue');
    }
    $res = unserialize(Ccsd_Tools::solrCurl($solrRequest, 'hal', 'select', 0));
    if (isset($res['response']['numFound']) && isset($res['response']['docs'])) {
        foreach ($res['response']['docs'] as $d) {
            //On vérifie avant que le document n'ait pas été détamponné manuellement
            if (Hal_Document_Collection::isDeleted($d['docid'], $sid)) {
                //Le document a été détamponné, on ne le retamponne pas
                if ($verbose) {
                    println('> Tamponnage du document ' . $d['docid'] . ': ', 'KO - document détamponné manuellement', 'red');
                }
            } else {
                if (!$test) {
                    $res = Hal_Document_Collection::add($d['docid'], $site);
                    if ($verbose) {
                        println('> Tamponnage du document ' . $d['docid'] . ': ', ($res ? 'OK' : 'KO'), ($res ? 'green' : 'red'));
                    }
                }
                $docToTamponnate[] = $d['docid'];
            }
        }
        if ($debug && count($docToTamponnate)) {
            println('> Nombre de documents à tamponner modifiés depuis le ' . $from . ': ', count($docToTamponnate), 'blue');
        }
        if ($verbose && count($docToTamponnate)) {
            println('> Liste des documents à tamponner: ', implode(', ', $docToTamponnate), 'blue');
        }
    }
    return $docToTamponnate;
}

/**
 * Détamponnage d'une collection
 *
 * Récupère de SolR les documents ne répondant plus au critère de la collection et tamponnés pour la collection
 *
 * @param Hal_site $site :  collection
 * @param string $critere critère de tamponnage
 * @param string $from date de modification des dépôts
 * @param boolean $test mode test
 * @param boolean $debug active les débug
 * @param boolean $verbose active les débugs complets
 * @param Zend_Db_Adapter_Abstract $db
 * @return array liste des document tamponnés
 * @throws Exception
 */
function delStamp($site, $critere, $from, $test, $debug, $verbose, $db)
{
    $sid = $site->getSid();
    global $rootId;
    $docToDeTamponnate = array();

    $solrRequest  = "q=*&";
    $solrRequest .= "fq=collId_i:" . $sid . "&fq=NOT(" . urlencode($critere) . ")&fq=modifiedDate_s:" . urlencode('[' . $from . ' TO NOW]') . "&";
    $solrRequest .= "rows=1000000&wt=phps&fl=docid&omitHeader=true";

    println('--');
    println($solrRequest);
    println('--');

    if ($verbose) {
        println('> Requête SolR détamponnage: ', $solrRequest, 'blue');
    }
    $res = unserialize(Ccsd_Tools::solrCurl($solrRequest, 'hal', 'select', 0));
    if (isset($res['response']['numFound']) && isset($res['response']['docs'])) {

        foreach($res['response']['docs'] as $d) {
            $docToDeTamponnate[] = $d['docid'];
        }
        //Pour les documents à détamponner, on vérifie qu'ils n'ont pas été tamponnés par des utilisateurs
        if (count($docToDeTamponnate)) {
            $sql = $db->select()
                ->from('DOC_TAMPON', 'DOCID')
                ->where('UID = ?', $rootId)
                ->where('SID = ?', $sid)
                ->where('DOCID IN (?)', $docToDeTamponnate);
            $docToDeTamponnate = $db->fetchCol($sql);
        }

        if ($debug) {
            println('> Nombre de documents à détamponner modifiés depuis le ' . $from . ': ', count($docToDeTamponnate), 'blue');
        }

        foreach($docToDeTamponnate as $docid) {
            if (! $test) {
                $res = Hal_Document_Collection::del($docid, $site);
                if ($verbose) {
                    println('> Détamponnage du document ' . $docid . ': ', ($res ? 'OK' : 'KO'), ($res ? 'green' : 'red'));
                }
            }
        }
        if ($verbose && count($docToDeTamponnate)) {
            println('> Liste des documents à détamponner: ', implode(', ', $docToDeTamponnate), 'blue');
        }
    }
    return $docToDeTamponnate;
}


/**
 * TMP : Vérifie qu'1 seule version d'un dépôt est tamponné
 *
foreach ($collections as $sid => $name) {
    //Pour toutes les collections automatiques
    $sql = $db->select()
        ->from(array('c' => 'DOC_TAMPON'), '')
        ->joinLeft(array('d' => 'DOCUMENT'), 'c.DOCID = d.DOCID', 'IDENTIFIANT')
        ->where('c.SID = ?', $sid)
        ->group('d.IDENTIFIANT')
        ->having('COUNT(*) > 1');
    println($sql);
    $count = 0;
    foreach($db->fetchCol($sql) as $identifiant) {
        //Récupération des docids des versions à 111
        $sql2 = $db->select()
            ->from('DOCUMENT', 'DOCID')
            ->where('IDENTIFIANT = ?', $identifiant)
            ->where('DOCSTATUS = 111');
        $docids = $db->fetchCol($sql2);
        foreach ($docids as $docid) {
            $res = Hal_Document_Collection::del($docid, $sid);
            println('> ' . $identifiant . ' - Détamponnage du docid ' . $docid . ': ', ($res ? 'OK' : 'KO'), ($res ? 'green' : 'red'));
        }
        $count += count($docids);
    }
    println('collection ' . $name . ' détamponnage de ' . $count . ' docids');
}
*/