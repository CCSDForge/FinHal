<?php

set_time_limit(0);
ini_set("memory_limit", '4096M');
ini_set("display_errors", '1');
define('DEFAULT_ENV', 'production');
define('PATH_ARINRA', '/docs/revuesinra');

define('ENV_DEV', 'production' );
define('CONFIG', 'config/');
define('SPACE_DATA', __DIR__ . '/../data');
define('SPACE_PORTAIL', 'portail');
define('MODULE', SPACE_PORTAIL);
define('PORTAIL', 'default');
define('SPACE', SPACE_DATA . '/'. MODULE . '/' . PORTAIL . '/');

set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/phplib'), array(get_include_path()))));

require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance ();
$autoloader->setFallbackAutoloader ( true );

define('APPLICATION_PATH', __DIR__ . '/../application' );

try {
    $opts = new Zend_Console_Getopt ( array (
        'help|h' => ' cette aide',
        'debug|v' => ' verbose',
        'application_env|e=s' => ' definit APPLICATION_ENV (par defaut = ' . DEFAULT_ENV . ')'
    ) );
    $parseResult = $opts->parse ();
} catch ( Zend_Console_Getopt_Exception $e ) {
    exit ( $e->getMessage () . PHP_EOL . PHP_EOL . $opts->getUsageMessage () );
}

if ( $opts->help != false || ( $opts->application_env != false && !in_array($opts->application_env, array('testing', 'preprod', 'production')) ) ) {
    die($opts->getUsageMessage() . PHP_EOL);
}

if ($opts->application_env == FALSE) {
    define('APPLICATION_ENV', DEFAULT_ENV);
} else {
    define('APPLICATION_ENV', $opts->application_env);
}

switch (APPLICATION_ENV) {
    case 'testing':
        set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/hal_test/library', '/sites/library_test'), array(get_include_path()))));
        break;

    case 'preprod':
        set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/hal_preprod/library', '/sites/library_preprod'), array(get_include_path()))));
        break;

    case 'production':
        set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/hal/library', '/sites/library'), array(get_include_path()))));
        break;
}

try {
    $application = new Zend_Application ( APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini' );
    foreach ( $application->getOption ( 'consts' ) as $const => $value ) {
        define( $const, $value );
    }
} catch ( Exception $e ) {
    echo $e->getMessage ();
}

$db = Zend_Db::factory('PDO_MYSQL', $application->getOption('resources')['db']['params']);
Zend_Db_Table::setDefaultAdapter($db);

Zend_Registry::set('languages', array('fr','en'));
Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));

Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'fr' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'en' ) );

$site = Hal_Site::loadSiteFromId(1);
$site->registerSiteConstants();

Zend_Registry::set('website', $site);

$collecSid = array(
    '2924'=>'ARINRA',
    '3043'=>'ARINRA-ABABB',
    '3039'=>'ARINRA-ABEILLE',
    '3036'=>'ARINRA-ADSF',
    '2928'=>'ARINRA-AFS',
    '2925'=>'ARINRA-AGRO',
    '3037'=>'ARINRA-AGRODEV',
    '3041'=>'ARINRA-AGSA',
    '3038'=>'ARINRA-ANNAZOO',
    '2927'=>'ARINRA-ANRES',
    '2930'=>'ARINRA-APID',
    '2933'=>'ARINRA-ARV',
    '3163'=>'ARINRA-CESR',
    '3046'=>'ARINRA-DST',
    '2929'=>'ARINRA-GSE',
    '3040'=>'ARINRA-GSEVO',
    '3435'=>'ARINRA-JPH',
    '2932'=>'ARINRA-LELAIT',
    '2931'=>'ARINRA-PRODANIM',
    '3042'=>'ARINRA-REAE',
    '2926'=>'ARINRA-RND',
    '3044'=>'ARINRA-RNDEV',
    '3045'=>'ARINRA-VETR',
    '4077'=>'ARINRA-ETUDEREC'
);

$collecJournal = array(
    'ARINRA-ABABB'=>'annales de biologie animale, biochimie, biophysique',
    'ARINRA-ABEILLE'=>"les annales de l'abeille",
    'ARINRA-APID'=>'apidologie',
    'ARINRA-ADSF'=>'annales des sciences forestières',
    'ARINRA-AFS'=>'annals of forest science',
    'ARINRA-AGRO'=>'agronomie',
    'ARINRA-AGRODEV'=>'agronomy for sustainable development',
    'ARINRA-AGSA'=>'annales de génétique et de sélection animale',
    'ARINRA-ANNAZOO'=>'annales de zootechnie',
    'ARINRA-ANRES'=>'animal research',
    'ARINRA-ARV'=>'annales de recherches vétérinaires',
    'ARINRA-CESR'=>"cahiers d'économie et sociologie rurales",
    'ARINRA-DST'=>'dairy science & technology',
    'ARINRA-GSE'=>'genetics selection evolution',
    'ARINRA-GSEVO'=>'génétique sélection évolution',
    'ARINRA-LELAIT'=>'le lait',
    'ARINRA-PRODANIM'=>'inra productions animales',
    'ARINRA-REAE'=>"revue d'etudes en agriculture et environnement",
    'ARINRA-RND'=>'reproduction nutrition development',
    'ARINRA-RNDEV'=>'reproduction nutrition développement',
    'ARINRA-VETR'=>'veterinary research',
    'ARINRA-ETUDEREC'=>'études et recherches sur les systèmes agraires et le développement'
);

$journalId = array(
    'annales de biologie animale, biochimie, biophysique'=>104755,
    "les annales de l'abeille"=>101089,
    'apidologie'=>10587,
    'annales des sciences forestières'=>45656,
    'annals of forest science'=>10482,
    'agronomie'=>10287,
    'agronomy for sustainable development'=>38209,
    'annales de génétique et de sélection animale'=>101090,
    'annales de zootechnie'=>101088,
    'animal research'=>38262,
    'annales de recherches vétérinaires'=>101095,
    "cahiers d'économie et sociologie rurales"=>20646,
    'dairy science & technology'=>59644,
    'genetics selection evolution'=>13602,
    'génétique sélection évolution'=>101091,
    'le lait'=>101092,
    'inra productions animales'=>102171,
    "revue d'etudes en agriculture et environnement"=>102045,
    'reproduction nutrition development'=>18569,
    'reproduction nutrition développement'=>103509,
    'veterinary research'=>19840,
    'études et recherches sur les systèmes agraires et le développement'=>99249
);

$domainsTampid = array(
    'ARINRA-ABABB'=>array('sdv.ba','sdv.bbm.bc','sdv.bbm.bp'),
    'ARINRA-ABEILLE'=>array('sdv.ba.zi','sdv.bid','sdv.ee','sdv.sa.spa'),
    'ARINRA-APID'=>array('sdv.ba.zi','sdv.bid','sdv.ee','sdv.sa.spa'),
    'ARINRA-ADSF'=>array('sdv.sa.sf'),
    'ARINRA-AFS'=>array('sdv.sa.sf'),
    'ARINRA-AGRO'=>array('sdv.sa','sdv.ee'),
    'ARINRA-AGRODEV'=>array('sdv.sa','sdv.ee'),
    'ARINRA-AGSA'=>array('sdv.gen.ga'),
    'ARINRA-ANNAZOO'=>array('sdv.sa.zoo'),
    'ARINRA-ANRES'=>array('sdv.sa.zoo'),
    'ARINRA-ARV'=>array('sdv.bbm.bm','sdv.gen.ga','sdv.bc','sdv.bc.ic','sdv.mp','sdv.imm','sdv.neu','sdv.spee','sdv.ba'),
    'ARINRA-CESR'=>array('shs.hist', 'shs.socio', 'shs.eco'),
    'ARINRA-DST'=>array('sdv.aen','sdv.ida'),
    'ARINRA-GSE'=>array('sdv.gen.ga'),
    'ARINRA-GSEVO'=>array('sdv.gen.ga'),
    'ARINRA-LELAIT'=>array('sdv.aen','sdv.ida'),
    'ARINRA-PRODANIM'=>array('sdv.sa.spa'),
    'ARINRA-REAE'=>array('sdv.sa','sdv.ee'),
    'ARINRA-RND'=>array('sdv.bdlr','sdv.aen','sdv.bdd'),
    'ARINRA-RNDEV'=>array('sdv.Bdlr','sdv.aen','sdv.bdd'),
    'ARINRA-VETR'=>array('sdv.bbm.bm','sdv.gen.ga','sdv.bc','sdv.bc.ic','sdv.mp','sdv.imm','sdv.neu','sdv.spee','sdv.ba'),
    'ARINRA-ETUDEREC'=>array('sdv.sa'),
);

$halUser = new Hal_User( array('UID'=>200075) );
Hal_Auth::setIdentity($halUser);

if ( $opts->debug != false ) {
    echo "Lecture du répertoire : " . PATH_ARINRA . PHP_EOL;
}

$j = array();
$error = $dble = $f = 0;
if ( $dir = opendir(PATH_ARINRA) ) {
    while (($rev = readdir($dir)) !== false) {
        if ($rev != '.' && $rev != '..' && $rev != 'logs' && is_dir(PATH_ARINRA . '/' . $rev)) {
            if ($revd = opendir(PATH_ARINRA . '/' . $rev)) {
                if ( $opts->debug != false ) {
                    echo "Lecture du sous-répertoire : " . PATH_ARINRA. '/' . $rev . PHP_EOL;
                }
                while (($file = readdir($revd)) !== false) {
                    if ($file != '.' && $file != '..' && preg_match('/\.xml$/', $file)) {
                        try {
                            set_error_handler('HandleXmlError');
                            $xml = new DomDocument();
                            $xml->substituteEntities = true;
                            $xml->preserveWhiteSpace = false;
                            $xml->load(PATH_ARINRA . '/' . $rev . '/' . $file);
                            $xpath = new DOMXPath($xml);
                            $xpath->registerNamespace('xml', "http://www.w3.org/XML/1998/namespace");
                            restore_error_handler();
                            $pdf = substr(str_replace('.edps.', '.', $file), 0, -4) . '.pdf';
                            if (is_file(PATH_ARINRA . '/' . $rev . '/' . $pdf)) {
                                $f++;
                                $article = new Hal_Document();
                                $article->delAutStruct();
                                $article->setTypeSubmit(Hal_Settings::SUBMIT_INIT);
                                $article->setFormat(Hal_Document::FORMAT_FILE);
                                $article->setContributor(new Hal_User(array('uid'=>200075)));
                                $article->setSid(1);
                                $article->setTypdoc('ART');
                                $article->addMeta('peerReviewing', '1');
                                $article->addMeta('popularLevel', '0');
                                $article->addMeta('audience', '2');
                                $articleFile = new Hal_Document_File();
                                $articleFile->setType('file');
                                $articleFile->setOrigin('publisherPaid');
                                $articleFile->setDefault(true);
                                $articleFile->setName($pdf);
                                $articleFile->setPath(PATH_ARINRA . '/' . $rev . '/' . $pdf);
                                $article->addFile($articleFile);
                                if (isset($xml->doctype->systemId)) {
                                    $dtd = basename($xml->doctype->systemId);
                                    if ($dtd == 'EDPSArticle.prod-0.02.dtd') {
                                    } else if ($dtd == 'edp-article6.xml.dtd') {
                                    } else if ($dtd == 'journalpublishing.dtd' || $dtd == 'edppublishing3.dtd') {
                                    } else if ($dtd == 'edp-article5.xml.dtd') {
                                    } else if ($dtd == 'A++V2.4.dtd') {
                                        $article->addMeta('language', strtolower($xpath->query('/Publisher/Journal/Volume/Issue/Article/ArticleInfo')->item(0)->getAttribute('Language')));
                                        $article->addMeta('identifier', ['doi' => @$xpath->query('/Publisher/Journal/Volume/Issue/Article/ArticleInfo/ArticleDOI')->item(0)->nodeValue]);
                                        $article->addMeta('title', ['en' => @$xpath->query('/Publisher/Journal/Volume/Issue/Article/ArticleInfo/ArticleTitle[@Language="En"]')->item(0)->nodeValue]);
                                        $journal = strtolower(Ccsd_Tools::space_clean(@$xpath->query('/Publisher/Journal/JournalInfo/JournalTitle')->item(0)->nodeValue));
                                        $article->addMeta('volume', @$xpath->query('/Publisher/Journal/Volume/VolumeInfo/VolumeIDStart')->item(0)->nodeValue);
                                        $article->addMeta('issue', @$xpath->query('/Publisher/Journal/Volume/Issue/IssueInfo/IssueIDStart')->item(0)->nodeValue);
                                        $article->addMeta('date', @$xpath->query('/Publisher/Journal/Volume/Issue/IssueInfo/IssueHistory/OnlineDate/Year')->item(0)->nodeValue);
                                        $pagef =@ $xpath->query('/Publisher/Journal/Volume/Issue/Article/ArticleInfo/ArticleFirstPage')->item(0)->nodeValue;
                                        $pagel =@ $xpath->query('/Publisher/Journal/Volume/Issue/Article/ArticleInfo/ArticleLastPage')->item(0)->nodeValue;
                                        $article->addMeta('page', $pagef.'-'.$pagel);
                                        $article->addMeta('abstract', ['en' => @trim($xpath->query('/Publisher/Journal/Volume/Issue/Article/ArticleHeader/Abstract[@Language="En"]/Para')->item(0)->nodeValue)]);
                                        $kw = [];
                                        foreach ( $xpath->query('/Publisher/Journal/Volume/Issue/Article/ArticleHeader/KeywordGroup[@Language="En"]/Keyword') as $k ) {
                                            $kw[] = trim($k->nodeValue);
                                        }
                                        $article->addMeta('keyword', ['en' => $kw]);
                                        foreach ( $xpath->query('/Publisher/Journal/Volume/Issue/Article/ArticleHeader/AuthorGroup/Author/AuthorName') as $a ) {
                                            $aa = Ccsd_Tools::dom2array($a);
                                            $author = new Hal_Document_Author();
                                            $author->setQuality('aut');
                                            if ( isset($aa['GivenName']) && is_array($aa['GivenName']) ) {
                                                $author->setFirstname(@$aa['GivenName'][0]);
                                            } else {
                                                $author->setFirstname(@$aa['GivenName']);
                                            }
                                            $author->setLastname($aa['FamilyName']);
                                            $article->addAuthor($author);
                                        }
                                    }
                                } else if ( $xml->getElementsByTagName('database')->length ) {
                                    $lang = substr(strtolower($xpath->query('/record/language')->item(0)->nodeValue), 0, 2);
                                    $lang = ( $lang == 'fr' ) ? 'fr' : 'en';
                                    $article->addMeta('language', $lang);
                                    $article->addMeta('title', [$lang => Ccsd_Tools::space_clean(@$xpath->query('/record/titles/title')->item(0)->nodeValue)]);
                                    $article->addMeta('journal', $journalId[$journal]);
                                    $journal = strtolower(Ccsd_Tools::space_clean(@$xpath->query('/record/periodical/full-title')->item(0)->nodeValue));
                                    $article->addMeta('date', @$xpath->query('/record/dates/year')->item(0)->nodeValue);
                                    $article->addMeta('page', @$xpath->query('/record/pages')->item(0)->nodeValue);
                                    $article->addMeta('volume', @$xpath->query('/record/volume')->item(0)->nodeValue);
                                    foreach ( $xpath->query('/record/contributors/authors/author') as $a ) {
                                        list($last, $first) = explode(',', $a->nodeValue);
                                        if ( $last && $first ) {
                                            $author = new Hal_Document_Author();
                                            $author->setQuality('aut');
                                            $author->setFirstname(trim($first));
                                            $author->setLastname(trim($last));
                                            $article->addAuthor($author);
                                        }
                                    }
                                }
                                if ( $journal == "revue d'etudes en agriculture et environnement / review of agricultural and environmental studies" ) {
                                    $journal = "revue d'etudes en agriculture et environnement";
                                }
                                if ( array_key_exists($journal, $journalId) ) {
                                    if ( isset($j[$journal]) ) {
                                        $j[$journal]++;
                                    } else {
                                        $j[$journal] = 1;
                                    }
                                    $article->addMeta('journal', $journalId[$journal]);
                                    $arinra = array_flip($collecJournal)[$journal];
                                    if ( count($domainsTampid[$arinra]) ) {
                                        $article->addMeta('domain', $domainsTampid[$arinra]);
                                    }
                                    $collections = [array_flip($collecSid)['ARINRA'], array_flip($collecSid)[$arinra]];
                                    $doublons = Hal_Document_Doublonresearcher::doublon($article);
                                    if ( count($doublons) ) {
                                        $dble++;
                                        echo 'doublon : '.$rev . '/' . $pdf . PHP_EOL;
                                        $sql = $db->select()
                                            ->distinct()
                                            ->from(Hal_Document::TABLE, 'DOCID')
                                            ->where('IDENTIFIANT = ?', str_replace('democrite-', 'in2p3-', str_replace('ccsd-', 'hal-', key($doublons))));
                                        foreach ( $db->fetchCol($sql) as $docid ) {
                                            foreach ( $collections as $col ) {
                                                Hal_Document_Collection::add($docid, $col, 200075, false);
                                            }
                                        }
                                        continue;
                                    }
                                    try {
                                        $article->save(0, false);
                                        if ( $article->getDocid() ) {
                                            foreach ( $collections as $col ) {
                                                Hal_Document_Collection::add($article->getDocid(), $col, 200075, false);
                                            }
                                        }
                                        $bind = array(
                                            'DOCSTATUS' => Hal_Document::STATUS_VISIBLE,
                                            'DATEMODER' => date('Y-m-d H:i:s')
                                        );
                                        $db->update(Hal_Document::TABLE, $bind, 'DOCID = ' . $article->getDocid());
                                        Hal_Document_Logger::log($article->getDocid(), 100000, Hal_Document_Logger::ACTION_MODERATE, '');
                                        //$article->createImagettes();
                                        Ccsd_Search_Solr_Indexer::addToIndexQueue(array($article->getDocid()));
                                        echo 'OK : '.$rev . '/' . $pdf . ' -> ' . $article->getDocid() . PHP_EOL;
                                        // copie du fichier xml dans répertoire de log
                                        if ( !is_dir(PATH_ARINRA . '/logs') ) {
                                            mkdir(PATH_ARINRA . '/logs');
                                        }
                                        rename(PATH_ARINRA . '/' . $rev . '/' . $file, PATH_ARINRA . '/logs/' . $file);
                                    } catch ( Exception $e ) {
                                        $article->delete(null, '', false);
                                        echo 'KO : '.$rev . '/' . $pdf . ' -> ' . $article->getDocid() . PHP_EOL;
                                        echo $e->getMessage() . PHP_EOL;
                                    }
                                    sleep(2);
                                } else {
                                    $error++;
                                    echo $journal.' unknown' . PHP_EOL;
                                }
                            }
                        } catch (Exception $e) {
                            echo $e->getMessage() . PHP_EOL;
                            continue;
                        }
                    }
                }
            }
        }
    }
}

if ( $opts->debug != false ) {
    echo PHP_EOL;
    foreach ($j as $journal => $count) {
        echo $journal . ' : ' . $count . PHP_EOL;
    }
    echo PHP_EOL;
    echo 'Nombre total de fichiers : ' . $f . PHP_EOL;
    echo 'Nombre total de papiers : ' . array_sum($j) . PHP_EOL;
    echo 'Nombre total de doublon : ' . $dble . PHP_EOL;
    echo 'Nombre total de erreur : ' . $error . PHP_EOL;
}

exit();

function HandleXmlError($errno, $errstr, $errfile, $errline) {
    return true;
}
