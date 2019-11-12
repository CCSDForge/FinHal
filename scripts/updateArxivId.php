<?php
$localopts = array(
        'test|t' => 'Mode test',
);

require_once __DIR__ . '/loadHalHeader.php';

$test = isset($opts->t);

define('SPACE', SPACE_DATA . '/'. MODULE . '/' . PORTAIL . '/');

Zend_Registry::set('languages', array('fr','en'));
Zend_Registry::set('Zend_Locale', new Zend_Locale('fr'));

Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'fr' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'en' ) );

$arrayOfDocId = $db->fetchAll("SELECT DOCID,PENDING FROM DOC_IDARXIV WHERE (ARXIVID IS NULL OR ARXIVID = '') AND PENDING IS NOT NULL ORDER BY DOCID ASC");

if ( $opts->debug != false ) {
    Ccsd_Log::message("Nombre de documents à vérifier sur arXiv : " . count($arrayOfDocId), true, 'INFO');
}
foreach ( $arrayOfDocId as $docid ) {
    $document = Hal_Document::find($docid['DOCID']);
    if ( $document ) {
        try {
            $arxiv =@ new SimpleXMLElement(file_get_contents($docid['PENDING']));
            switch ( $arxiv->status ) {
                case 'published':
                    if ($test) {
                        println('Get Id :' . (string)$arxiv->arxiv_id);
                    } else {
                        $db->query('UPDATE DOC_IDARXIV SET ARXIVID ="'. (string)$arxiv->arxiv_id .'" WHERE DOCID = '.$docid['DOCID']);
                        $db->query('INSERT IGNORE INTO DOC_HASCOPY (DOCID,CODE,LOCALID) VALUES ('.$docid['DOCID'].',"arxiv","'.(string)$arxiv->arxiv_id .'")');
                        Hal_Document::deleteCaches([$docid['DOCID']]);
                        Ccsd_Search_Solr_Indexer::addToIndexQueue([$docid['DOCID']]);
                        if ( $opts->debug != false ) {
                            Ccsd_Log::message('Update OK for '.$document->getDocid().': '.(string)$arxiv->arxiv_id, true, 'INFO');
                        }
                    }
                    break;
                case 'submitted':
                    if ( $opts->debug != false ) {
                        Ccsd_Log::message($document->getDocid().': no Id arXiv found, still submitted status', true, 'INFO');
                    }
                    break;
                case 'on hold':
                case 'unknown':
                    if ( $opts->debug != false ) {
                        Ccsd_Log::message('Error for '.$document->getDocid().': status -> '.$arxiv->status.' -> '.(string)$arxiv->error, true, 'INFO');
                    }
                    break;
                case 'user deleted':
                case 'removed':
                    if ($test) {
                        println("Doc deleted on arxiv: " . $docid['DOCID']);
                    } else {
                        $db->query("DELETE FROM DOC_IDARXIV WHERE DOCID = ".$docid['DOCID']);
                        if ( $opts->debug != false ) {
                            Ccsd_Log::message('Removed OK for '.$document->getDocid(), true, 'INFO');
                        }
                    }
                    break;
                default:
                    if ( $opts->debug != false ) {
                        Ccsd_Log::message('Error for '. $document->getDocid() .': status -> '.$arxiv->status, true, 'INFO');
                    }
            }
        } catch ( Exception $e ) {
            if ( $opts->debug != false ) {
                Ccsd_Log::message( "Error for " . $document->getDocid() . ' non trouvé !', true, 'ERR' );
            }
        }

    } else {
        if ( $opts->debug != false ) {
            Ccsd_Log::message("Docid " . $docid['DOCID'] . ' non trouvé !', true, 'ERR');
        }
    }

}

exit();

// SELECT d.DOCID,m.METAVALUE FROM DOCUMENT d, DOC_METADATA m, DOC_IDARXIV a WHERE d.DOCID=m.DOCID AND d.DOCID=a.DOCID AND d.DOCSTATUS IN (11, 111) AND m.METANAME='title' AND ( a.ARXIVID='' OR a.ARXIVID IS NULL) order by d.DATEMODER DESC
// INSERT INTO DOC_HASCOPY (DOCID,CODE,LOCALID) SELECT DOCID, 'arxiv', ARXIVID from DOC_IDARXIV where (ARXIVID != '' OR ARXIVID IS NOT NULL) AND DOCID NOT IN (select DOCID from DOC_HASCOPY where CODE='arxiv')