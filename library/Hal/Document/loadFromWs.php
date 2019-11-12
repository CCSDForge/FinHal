<?php

/**
 * ------------- METADONNEES ------------
 */
$dl = new Ccsd_Detectlanguage();
$embargo = date("Y-m-d");

foreach ( $metadatas->metaSimple as $meta ) {

    if ( !(isset($meta->metaName) && isset($meta->metaValue)) ) {
        continue;
    }
    if ( $meta->metaName == 'datevisible' && in_array($meta->metaValue, array('2A','1A','3M','1M','15J')) ) {
        $current_date = getDate();
        if ( $meta->metaValue == '15J' ) {
            $embargo = date("Y-m-d", mktime($current_date['hours'],$current_date['minutes'],$current_date['seconds'],$current_date['mon'],$current_date['mday']+15,$current_date['year']));
        } else if ( $meta->metaValue == '1M' ) {
            $embargo = date("Y-m-d", mktime($current_date['hours'],$current_date['minutes'],$current_date['seconds'],$current_date['mon']+1,$current_date['mday'],$current_date['year']));
        } else if ( $meta->metaValue == '3M' ) {
            $embargo = date("Y-m-d", mktime($current_date['hours'],$current_date['minutes'],$current_date['seconds'],$current_date['mon']+3,$current_date['mday'],$current_date['year']));
        } else if ( $meta->metaValue == '1A' ) {
            $embargo = date("Y-m-d", mktime($current_date['hours'],$current_date['minutes'],$current_date['seconds'],$current_date['mon'],$current_date['mday'],$current_date['year']+1));
        } else if ( $meta->metaValue == '2A' ) {
            $embargo = date("Y-m-d", mktime($current_date['hours'],$current_date['minutes'],$current_date['seconds'],$current_date['mon']+3,$current_date['mday'],$current_date['year']+2));
        }
    }
    if ( $meta->metaName == 'title' || $meta->metaName == 'english_title' ) {
        $langueid = $dl->detect($meta->metaValue);
        if ( count($langueid) && isset($langueid['langid']) ) {
            $lg = strtolower($langueid['langid']);
        } else {
            $lg = 'en';
        }
        $this->_metas['title'][$lg] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'sstitle') {
        $langueid = $dl->detect($meta->metaValue);
        if ( count($langueid) && isset($langueid['langid']) ) {
            $lg = strtolower($langueid['langid']);
        } else {
            $lg = 'en';
        }
        $this->_metas['subTitle'][$lg] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'abstract' || $meta->metaName == 'english_abstract' || $meta->metaName == 'abstract_ml' ) {
        $langueid = $dl->detect($meta->metaValue);
        if ( count($langueid) && isset($langueid['langid']) ) {
            $lg = strtolower($langueid['langid']);
        } else {
            $lg = 'en';
        }
        $this->_metas['abstract'][$lg] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'keyword' || $meta->metaName == 'english_keyword' || $meta->metaName == 'keyword_ml' ) {
        $langueid = $dl->detect($meta->metaValue);
        if ( count($langueid) && isset($langueid['langid']) ) {
            $lg = strtolower($langueid['langid']);
        } else {
            $lg = 'en';
        }
        $this->_metas['keyword'][$lg] = array_filter(explode(';', trim($meta->metaValue)));
    }
    if ( $meta->metaName == 'keyword_mesh') {
        $this->_metas['mesh'] = array_filter(explode(';', trim($meta->metaValue)));
    }
    if ( $meta->metaName == 'keyword_jel') {
        $this->_metas['jel'] = str_replace(':', '.', array_filter(explode(';', trim($meta->metaValue))));
    }
    if ( $meta->metaName == 'domain') {
        $this->_metas['domain'] = str_replace(array(':','_'), array('.','-'), array_map('strtolower', array_filter(explode(';', $meta->metaValue))));
    }
    if ( $meta->metaName == 'idext') {
        if ( $this->getContributor('uid') == 172486 ) {
            $this->_metas['identifier']['ensam'] = trim($meta->metaValue);
        } else if ( $this->getContributor('uid') == 149226 ) {
            $this->_metas['identifier']['sciencespo'] = trim($meta->metaValue);
        } else if ( $this->getContributor('uid') == 146910 ) {
            $this->_metas['identifier']['oatao'] = trim($meta->metaValue);
        }
    }
    if ( $meta->metaName == 'arxivid') {
        $this->_metas['identifier']['arxiv'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'doi') {
        $this->_metas['identifier']['doi'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'pmid') {
        $this->_metas['identifier']['pubmed'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'bibcode') {
        $this->_metas['identifier']['bibcode'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'langue') {
        $this->_metas['language'] = strtolower(trim($meta->metaValue));
    }
    if ( $meta->metaName == 'writing_date') {
        $this->_metas['writingDate'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'classification') {
        $this->_metas['classification'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'comment') {
        $this->_metas['comment'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'ref_interne') {
        $this->_metas['localReference'] = array(trim($meta->metaValue));
    }
    if ( $meta->metaName == 'collaboration') {
        $this->_metas['collaboration'] = array(trim($meta->metaValue));
    }
    if ( $meta->metaName == 'financement') {
        $this->_metas['funding'] = array(trim($meta->metaValue));
    }
    if ( $meta->metaName == 'journal') {
        $data = array('JNAME'=>trim($meta->metaValue));
        $this->_metas['journal'] = new Ccsd_Referentiels_Journal(0, $data);
    }
    if ( $meta->metaName == 'projetanr') {
        $data = array('REFERENCE'=>trim($meta->metaValue));
        $this->_metas['anrProject'][] = new Ccsd_Referentiels_Anrproject(0, $data);
    }
    if ( $meta->metaName == 'projeteurope') {
        $data = array('ACRONYME'=>trim($meta->metaValue));
        $this->_metas['europeanProject'][] = new Ccsd_Referentiels_Europeanproject(0, $data);
    }
    if ( $meta->metaName == 'isbn') {
        $this->_metas['isbn'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'volume') {
        $this->_metas['volume'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'issue') {
        $this->_metas['issue'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'page') {
        $this->_metas['page'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'serie') {
        $this->_metas['serie'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'titouv') {
        $this->_metas['bookTitle'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'audience') {
        $this->_metas['audience'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'datepub' || $meta->metaName == 'defencedate' || $meta->metaName == 'datebrevet' ) {
        $this->_metas['date'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'dateepub') {
        $this->_metas['edate'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'edcom' && $this->getTypDoc() != 'ART' ) {
        $this->_metas['publisher'] = array(trim($meta->metaValue));
    }
    if ( $meta->metaName == 'edsci') {
        $this->_metas['scientificEditor'] = array(trim($meta->metaValue));
    }
    if ( $meta->metaName == 'titconf') {
        $this->_metas['conferenceTitle'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'dateconf') {
        $this->_metas['conferenceStartDate'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'datefinconf') {
        $this->_metas['conferenceEndDate'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'ville') {
        $this->_metas['city'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'pays') {
        $this->_metas['country'] = strtolower(trim($meta->metaValue));
    }
    if ( $meta->metaName == 'dircoll') {
        $this->_metas['seriesEditor'] = array(trim($meta->metaValue));
    }
    if ( $meta->metaName == 'description') {
        $this->_metas['description'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'numbrevet') {
        $this->_metas['number'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'orgthe') {
        $this->_metas['authorityInstitution'] = array_filter(explode(';', trim($meta->metaValue)));
    }
    if ( $meta->metaName == 'numnat') {
        $this->_metas['nnt'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'directeurThese') {
        $this->_metas['director'] = trim($meta->metaValue);
    }
    if ( $meta->metaName == 'jury') {
        $this->_metas['committee'] = trim($meta->metaValue);
    }

    if ( $meta->metaName == 'collection') {
        foreach ( array_filter(explode(';', trim($meta->metaValue))) as $tampid ) {
            $collection = Hal_Site::exist($tampid, Hal_Site::TYPE_COLLECTION, true);
            if ($collection->getSid() != 0) {
                $this->_collections[] = $collection;
            }
        }
    }

}

/**
 * ------------- AUTEURS / STRUCTURES ------------
 */
$this->_authors = $idLab = array();
foreach ( $metadatas->metaAutLab->labs as $lab ) {
    if ( isset($lab->labId) && isset($lab->knownLabid) && isset($lab->shortName) && isset($lab->name) && isset($lab->address) && isset($lab->url) && isset($lab->pays) && isset($lab->affiliation) ) {
        if ( $lab->knownLabid != '' && $lab->knownLabid != 0 ) {
            $structidx = $this->addStructure($lab->knownLabid);
        } else {
            $structidx = $this->addStructure(createStructure($lab));
        }
        if ($structidx !== false) {
            $idLab[$lab->labId] = $structidx;
        }
    }
}

foreach ($metadatas->metaAutLab->authors as $author) {
    if ( isset($author->labIds) && isset($author->lastName) && isset($author->firstName) && isset($author->otherName) && isset($author->email) && isset($author->url) && isset($author->organism) && isset($author->researchTeam) && isset($author->corresponding) ) {
        $oAuthor = new Hal_Document_Author();
        $oAuthor->setQuality('aut');
        if ( $author->lastName ) {
            $oAuthor->setLastname($author->lastName);
        }
        if ( $author->firstName ) {
            $oAuthor->setFirstname($author->firstName);
        }
        if ( $author->otherName ) {
            $oAuthor->setOthername($author->otherName);
        }
        if ( $author->email ) {
            $oAuthor->setEmail($author->email);
        }
        if ( $author->url ) {
            $oAuthor->setUrl($author->url);
        }
        if ( $author->corresponding ) {
            $oAuthor->setQuality('crp');
        }
        if ( ( is_array($author->labIds) || is_object($author->labIds) ) && count($author->labIds) ) {
            foreach ($author->labIds as $labid) {
                if ( array_key_exists($labid, $idLab) ) {
                    $oAuthor->addStructidx($idLab[$labid]);
                }
            }
        }
        $this->addAuthor($oAuthor);
    }
}

/**
 * ------------- FICHIERS ------------
 */

$this->_files = array();
$format = array(0=>'author', 1=>'author', 2=>'greenPublisher', 3=>'publisherAgreement');

if ( $fullText && isset($fullText->right) && in_array($fullText->right, array(1, 2, 3)) && isset($fullText->files) && ( is_object($fullText->files) || is_array($fullText->files) ) && count($fullText->files) ) {
    do {
        $uniqid = 'soap'.uniqid();
    } while ( is_dir(PATHTEMPDOCS.$uniqid) );
    define('PATHTEMPIMPORT', PATHTEMPDOCS.$uniqid);
    mkdir(PATHTEMPIMPORT);
    foreach ( $fullText->files as $file ) {
        $oFile = new Hal_Document_File();
        $oFile->setType('file');
        $oFile->setOrigin($format[$fullText->right]);
        $oFile->setName($file->name);
        $oFile->setDateVisible($embargo);
        $oFile->setDefault(true);
        if ( $file->local ) {
            $content = base64_decode($file->content);
        } else {
            $content = file_get_contents($file->content);
        }
        file_put_contents(PATHTEMPIMPORT.DIRECTORY_SEPARATOR.trim($file->name), $content);
        $oFile->setPath(PATHTEMPIMPORT.DIRECTORY_SEPARATOR.trim($file->name));

        $this->_files[] = $oFile;
    }
}

/**
 * ------------- UTILE ------------
 */

function createStructure($lab)
{
    $structure = new Hal_Document_Structure();
    $structure->setTypestruct('laboratory');
    if ( $lab->name ) {
        $structure->setStructname($lab->name);
    }
    if ( $lab->shortName ) {
        $structure->setSigle($lab->shortName);
    }
    if ( $lab->address ) {
        $structure->setAddress($lab->address);
    }
    if ( $lab->url ) {
        $structure->setUrl($lab->url);
    }
    $pays = 'FR';
    if ( $lab->pays ) {
        $structure->setPaysid(strtoupper($lab->pays));
        $pays = strtoupper($lab->pays);
    }
    foreach ( $lab->affiliation as $affi ) {
        if ( $affi ) {
            preg_match('/^(.+)( : ([a-z0-9])+)*$/i', $affi, $match);
            $aff = new Ccsd_Referentiels_Structure(0, array('STRUCTNAME'=>$match[1], 'TYPESTRUCT'=>'institution', 'PAYSID'=>$pays));
            $aff->save();
            $structure->addParent($aff, Ccsd_Tools::ifsetor($match[3], ''));
        }
    }
    return $structure;
}
