<?php

/**
 * ------------- METADONNEES ------------
 */
$this->_metas = array();

/**
 * title
 * subTitle
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:analytic/tei:title');

foreach($entries as $entry) {
    if ( $entry->nodeValue ) {
        $lang = $entry->getAttribute('xml:lang');
        $type = $entry->getAttribute('type');

        if (! $lang || $lang == "und") {
            $lang = 'en';
        }
        $nodeValue = preg_replace('/^\⋆*(.+?)\⋆*$/u', '$1', $entry->nodeValue);
        $nodeValue = trim($nodeValue, " *\t\n\r\0\x0B");
        if ($type == 'sub') {
            $this->_metas['subTitle'][$lang] = $nodeValue;
        } else {
            $this->_metas['title'][$lang] = $nodeValue;
        }
    }
}

/**
 * language
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:langUsage/tei:language');
if ($entries->length == 1) {
    $language = $entries->item(0)->getAttribute('ident');
    if ($language != '') {
        $this->_metas['language'] = strtolower($language);
    }
}

/**
 * keyword
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:textClass/tei:keywords[@scheme="author"]/tei:term');
foreach($entries as $entry) {
    if ( $entry->nodeValue ) {
        $lang = $entry->getAttribute('xml:lang');
        $lang = ($lang == '' || $lang == "und") ? 'en' : $lang;
        $this->_metas['keyword'][$lang][] = $entry->nodeValue;
    }
}

/**
 * classification
 * acm
 * mesh
 * jel
 * jet
 * domain
 * type
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:textClass/tei:classCode');
foreach($entries as $entry) {
    $scheme = $entry->getAttribute('scheme');
    $meta = '';
    $multiple = true;
    $value = $entry->nodeValue;
    if ($scheme == "classification") {
        $meta = 'classification';
        $multiple = false;
    } else if ($scheme == "acm") {
        $meta = 'acm';
        /** Begin ZMO - Get the value of the attribute n and assign it to the value of meta */
        $n = $entry->getAttribute('n');
        if ($n != '') {
            $value = $n;
        }
        /** End ZMO */
    } else if ($scheme == "mesh") {
        $meta = 'mesh';
    } else if ($scheme == "jel") {
        $meta = 'jel';
        /** Begin ZMO - Get the value of the attribute n and assign it to the value of meta */
        $n = $entry->getAttribute('n');
        if ($n != '') {
            $value = $n;
        }
        /** End ZMO */
    } else if ($scheme == "jet") {
        $meta = 'jet';
    } else if ($scheme == "halDomain") {
        $meta = 'domain';
        $n = $entry->getAttribute('n');
        if ($n != '') {
            $value = $n;
        }
    } else if ($scheme == "halTypology") {
        $n = $entry->getAttribute('n');
        if ($n == '') {
            $n = 'UNDEFINED';
        }
        $this->setTypdoc($n);
        continue;
    }
    if ($meta != '') {
        if ($multiple) {
            if ( !isset($this->_metas[$meta]) || !is_array($this->_metas[$meta]) ) {
                $this->_metas[$meta] = [];
            }
            if ( !in_array($value, $this->_metas[$meta]) ) {
                $this->_metas[$meta][] = $value;
            }
        } else {
            $this->_metas[$meta] = $value;
        }
    }
}

/**
 * abstract
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:abstract');
foreach($entries as $entry) {
    $lang = $entry->getAttribute('xml:lang');
    $lang = ($lang == '' || $lang == "und") ? 'en' : $lang;
    $this->_metas['abstract'][$lang] = $entry->nodeValue;
}

/**
 * collaboration
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:particDesc/tei:org');
foreach($entries as $entry) {
    $type = $entry->getAttribute('type');
    if ($type == 'consortium') {
        if (! isset($this->_metas['collaboration'])) {
            $this->_metas['collaboration'] = [];
        }
        $this->_metas['collaboration'][] = $entry->nodeValue;
    }
}

/**
 * nnt
 * isbn
 * number
 * localReference
 * journal
 * bookTitle
 * source
 */
//$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr[node() = "tei:idno" or "tei:title"]');
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:title |
                          /tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:idno');
$journal = array('VALID'=>'INCOMING', 'JID' => '', 'ISSN'  =>  '', 'EISSN' =>  '', 'JNAME' => '', 'PUBLISHER' => '', 'URL' => '');
$dataJournal = false;
foreach($entries as $entry) {
    $type = $entry->getAttribute('level');
    if (! $type) {
        $type = $entry->getAttribute('type');
    }
    $meta = '';
    $multiple = false;
    $value = $entry->nodeValue;
    if ($type == 'nnt') {
        $meta = 'nnt';
    } else if ($type == 'isbn') {
        $meta = 'isbn';
    } else if ($type == 'eisbn') {
        $meta = 'eisbn';
    } else if ($type == 'patentNumber') {
        $meta = 'number';
    } else if ($type == 'reportNumber') {
        $meta = 'number';
    } else if ($type == 'localRef') {
        $meta = 'localReference';
        $multiple = true;
    } else if ($type == 'halJournalId') {
        $meta = 'journal';
        $value = new Ccsd_Referentiels_Journal($value);
    } else if ($type == 'issn') {
        $journal['ISSN'] = $entry->nodeValue;
        $dataJournal = true;
    } else if ($type == 'eissn') {
        $journal['EISSN'] = $entry->nodeValue;
        $dataJournal = true;
    } else if ($type == 'j') {
        $journal['JNAME'] = $entry->nodeValue;
        $dataJournal = true;
    } else if ($type == 'm') {
        $meta = 'bookTitle';
        if ($this->getTypdoc() == 'COMM') {
            $meta = 'source';
        }
    }
    if ($meta != '') {
        if ($multiple) {
            $this->_metas[$meta][] = $value;
        } else {
            $this->_metas[$meta] = $value;
        }
    }
}

if (! isset($this->_metas['journal']) && is_array($journal) && count($journal)) {
    if (isset($journal['ISSN']) && $journal['ISSN'] != '') {
        $solrResult = Ccsd_Referentiels_Journal::search('issn_s:' . $journal['ISSN'], 1);
    } else if (isset($journal['EISSN']) && $journal['EISSN'] != '') {
        $solrResult = Ccsd_Referentiels_Journal::search('eissn_s:'.$journal['EISSN'], 1);
    }
    if (isset($solrResult[0]['docid'])) {
        $this->_metas['journal'] = new Ccsd_Referentiels_Journal($solrResult[0]['docid']);
    } else if ($dataJournal) {
        $this->_metas['journal'] = new Ccsd_Referentiels_Journal(0, $journal);
    }
}

/**
 * conferenceTitle
 * conferenceStartDate
 * conferenceEndDate
 * city
 * country
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:meeting');
if ($entries->length == 1) {
    $entry = $entries->item(0);
    if ($entry->hasChildNodes()) {
        foreach($entry->childNodes as $node) {
            $meta = '';
            $value = $node->nodeValue;
            if ($node->nodeName == 'title') {
                $meta = 'conferenceTitle';
            } else if ($node->nodeName == 'date') {
                $type = $node->getAttribute('type');
                if ($type == 'start') {
                    $meta = 'conferenceStartDate';
                } else if ($type == 'end') {
                    $meta = 'conferenceEndDate';
                }
            } else if ($node->nodeName == 'settlement') {
                if ( ! in_array($this->getTypdoc(), array('PATENT', 'IMG', 'MAP', 'LECTURE'))) {
                    $meta = 'city';
                }
            } else if ($node->nodeName == 'country') {
                $country = strtolower($node->getAttribute('key'));
                $country = ($country == '') ? 'en' : $country;
                if (! in_array($this->getTypdoc(), array('PATENT', 'IMG', 'MAP', 'LECTURE'))) {
                    $meta = 'country';
                    $value = $country;
                }
            }
            if ($meta != '') {
                $this->_metas[$meta] = $value;
            }
        }
    }
}


/**
 * conferenceOrganizer
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:respStmt/tei:name');
foreach($entries as $entry) {
    $this->_metas['conferenceOrganizer'][] = $entry->nodeValue;
}

/**
 * scientificEditor
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:editor');
foreach($entries as $entry) {
    $this->_metas['scientificEditor'][] = $entry->nodeValue;
}

/**
 * city
 * country
 */
if (in_array($this->getTypdoc(), array('PATENT', 'IMG', 'MAP', 'LECTURE'))) {
    $entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:settlement');
    if ($entries->length == 1) {
        $this->_metas['city'] = $entries->item(0)->nodeValue;
    }

    $entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:country');
    if ($entries->length == 1) {
        $this->_metas['country'] = strtolower($entries->item(0)->getAttribute('key'));
    }
}

/**
 * publisher
 * serie
 * volume
 * issue
 * page
 * date
 * edate
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:imprint');
if ($entries->length == 1) {
    $entry = $entries->item(0);
    if ($entry->hasChildNodes()) {
        foreach($entry->childNodes as $node) {
            $meta = '';
            $multiple = false;
            $value = $node->nodeValue;
            if ($node->nodeName == 'publisher') {
                $multiple = true;
                $meta = 'publisher';
            } else if ($node->nodeName == 'biblScope') {
                $unit = $node->getAttribute('unit');
                if ($unit == 'serie') {
                    $meta = 'serie';
                } else if ($unit == 'volume') {
                    $meta = 'volume';
                } else if ($unit == 'issue') {
                    $meta = 'issue';
                } else if ($unit == 'pp') {
                    $meta = 'page';
                }
            } else if ($node->nodeName == 'date') {
                $type = $node->getAttribute('type');
                if ($type == 'datePub') {
                    $meta = 'date';
                    $precision = $node->getAttribute('precision');
                    if ( $precision == 'unknown' ) {
                        $this->_metas['circa'] = '1';
                    }
                } else if ($type == 'dateDefended') {
                    if (in_array($this->getTypdoc(), array('THESE', 'HDR', 'MEM', 'ETABTHESE'))) {
                        $meta = 'date';
                    }
                } else if ($type == 'dateEpub') {
                    $meta = 'edate';
                }
            }
            if ($meta != '') {
                if ($multiple) {
                    $this->_metas[$meta][] = $value;
                } else {
                    $this->_metas[$meta] = $value;
                }
            }
        }
    }
}
/**
 * seriesEditor
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:series/tei:editor');
foreach($entries as $entry) {
    $this->_metas['seriesEditor'] = $entry->nodeValue;
}

/**
 * scientificEditor
 * authorityInstitution
 * director
 * supervisorEmail
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:authority');
foreach($entries as $entry) {
    $type = $entry->getAttribute('type');
    if ($type == 'institution') {
        $this->_metas['authorityInstitution'][] = $entry->nodeValue;
    } else if ($type == 'school') {
        $this->_metas['thesisSchool'][] = $entry->nodeValue;
    } else if ($type == 'supervisor') {
        $this->_metas['director'][] = $entry->nodeValue;
    } else if ($type == 'jury') {
        $this->_metas['committee'][] = $entry->nodeValue;
    } else if ($type == 'supervisorEmail') {
        $this->_metas[$instance.'_directorEmail'] = $entry->nodeValue;
    }
}

/**
 * identifier (doi, arxiv, ...)
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:idno');
foreach($entries as $entry) {
    $type = strtolower($entry->getAttribute('type'));
    if ( in_array($type, Hal_Document::$_serverCopy) ) {
        $this->_metas['identifier'][$type] = $entry->nodeValue;
    }
}

/**
 * seeAlso
 * publisherLink
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:ref');
foreach($entries as $entry) {
    $type = $entry->getAttribute('type');
    if ($type == 'seeAlso') {
        $this->_metas['seeAlso'] = $entry->nodeValue;
    } else if ($type == 'publisher') {
        $this->_metas['publisherLink'] = $entry->nodeValue;
    }
}

/**
 * funding
 * projanr
 * projeurop
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:titleStmt/tei:funder');
foreach($entries as $entry) {
    $ref = $entry->getAttribute('ref');
    if ($ref == '') {
        $this->_metas['funding'][] = $entry->nodeValue;
    } else {
        $ref = str_replace('#', '', $ref);
        if (strpos($ref, 'projanr') !== false) {
            $this->_metas['anrProject'][] = new Ccsd_Referentiels_Anrproject(str_replace('projanr-', '', $ref));
        } else if ( strpos($ref, 'localProjanr') !== false) {
            $orgs = $xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg[@type="projects"]/tei:org[@xml:id="' . $ref . '"]');
            if ($orgs->length == 1) {
                $org = $orgs->item(0);
                if ($org->hasChildNodes()) {
                    $data = array();
                    foreach($org->childNodes as $node) {
                        if ($node->nodeName == 'orgName') {
                            $type = $node->getAttribute('type');
                            if ($type == 'program') {
                                $data['INTITULE'] = $node->nodeValue;
                            } else {
                                $data['ACRONYME'] = $node->nodeValue;
                            }
                        } else if ($node->nodeName == 'desc') {
                            $data['TITRE'] = $node->nodeValue;
                        } else if ($node->nodeName == 'idno') {
                            $data['REFERENCE'] = $node->nodeValue;
                        }
                    }
                }
                $this->_metas['anrProject'][] = new Ccsd_Referentiels_Anrproject(0, $data);
            }
        } else if (strpos($ref, 'projeurop') !== false) {
            $this->_metas['europeanProject'][] = new Ccsd_Referentiels_Europeanproject(str_replace('projeurop-', '', $ref));
        } else if (strpos($ref, 'localProjeurop') !== false) {
            $orgs = $xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg[@type="projects"]/tei:org[@xml:id="' . $ref . '"]');
            if ($orgs->length == 1) {
                $org = $orgs->item(0);
                if ($org->hasChildNodes()) {
                    $data = array();
                    foreach($org->childNodes as $node) {
                        if ($node->nodeName == 'orgName') {
                            $data['ACRONYME'] = $node->nodeValue;
                        } else if ($node->nodeName == 'idno') {
                            $type = $node->getAttribute('type');
                            if ($type == 'program') {
                                $data['FUNDEDBY'] = $node->nodeValue;
                            } else if ($type == 'number') {
                                $data['NUMERO'] = $node->nodeValue;
                            } else if ($type == 'call') {
                                $data['CALLID'] = $node->nodeValue;
                            }
                        } else if ($node->nodeName == 'desc') {
                            $data['TITRE'] = $node->nodeValue;
                        } else if ($node->nodeName == 'date') {
                            $type = $node->getAttribute('type');
                            if ($type == 'start') {
                                $data['SDATE'] = $node->nodeValue;
                            } else if ($type == 'end') {
                                $data['EDATE'] = $node->nodeValue;
                            }
                        }
                    }
                }
                $this->_metas['europeanProject'][] = new Ccsd_Referentiels_Europeanproject(0, $data);
            }
        }
    }
}

/**
 * comment
 * description
 * audience
 * invitedCommunication
 * popularLevel
 * peerReviewing
 * proceedings
 * reportType
 * imageType
 * lectureType
 * degreeType
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:notesStmt/tei:note');
foreach($entries as $entry) {
    $meta = '';
    $multiple = false;
    $type = $entry->getAttribute('type');
    $value = $entry->getAttribute('n');
    if ($type == 'commentary') {
        $meta = 'comment';
        $value = $entry->nodeValue;
    } else if ($type == 'description') {
        $meta = 'description';
        $value = $entry->nodeValue;
    } else if ($type == 'audience') {
        $meta = 'audience';
    } else if ($type == 'invited') {
        $meta = 'invitedCommunication';
    } else if ($type == 'popular') {
        $meta = 'popularLevel';
    } else if ($type == 'peer') {
        $meta = 'peerReviewing';
    } else if ($type == 'proceedings') {
        $meta = 'proceedings';
    } else if ($type == 'report') {
        $meta = 'reportType';
    } else if ($type == 'image') {
        $meta = 'imageType';
    } else if ($type == 'lecture') {
        $meta = 'lectureType';
    } else if ($type == 'degree') {
        $meta = $instance.'_degreeType';
    } else if ( $type == 'pastel_thematique' ) {
        $meta = 'pastel_thematique';
        $multiple = true;
    } else if ( $type == 'pastel_library' ) {
        $meta = 'pastel_library';
        $multiple = true;
    }
    if ($meta != '') {
        if ( $multiple ) {
            $this->_metas[$meta][] = $value;
        } else {
            $this->_metas[$meta] = $value;
        }
    }
}

/**
 * writingDate
 *
 */

$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:editionStmt/tei:edition/tei:date[@type="whenWritten"]');
if ($entries->length == 1) {
    $entry = $entries->item(0);
    $this->_metas['writingDate'] = $entry->nodeValue;
}

/**
 * licence
 *
*/
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:publicationStmt/tei:availability/tei:licence');
if ($entries->length == 1) {
    $licence = $entries->item(0)->getAttribute('target');
    if ( in_array($licence, Hal_Settings::getKnownLicences()) ) {
        $this->_metas['licence'] = $licence;
    }
}

/**
 * ------------- AUTEURS / STRUCTURES ------------
 */
$this->_authors = array();

$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:analytic/tei:author');
foreach($entries as $entry) {
    /**
     * Récupération des infos auteurs
     */
    $author = new Hal_Document_Author();
    $author->setQuality($entry->getAttribute('role'));

    $loaded = false;
    $idnos = $xpath->query('tei:idno', $entry);
    foreach($idnos as $idno) {
        $type = $idno->getAttribute('type');
        if (strtolower($type) == 'halauthor' || strtolower($type) == 'halauthorid') {
            $author->setAuthorid((int)$idno->nodeValue);
            $author->load();
            if ( $author->getAuthorid() != 0 ) {
                $loaded = true;
            }
            /** Begin ZMO - Allow string and numeric form for idhal */
        } else if (strtolower($type) == 'idhal') {
            if($idno->getAttribute('notation') == 'numeric') {
                $idhal = new Hal_Cv($idno->nodeValue);
            } else {
                $idhal = new Hal_Cv(0, (string)$idno->nodeValue);
            }
            $idhal->load(false);
            $author->setAuthorid($idhal->getCurrentFormAuthorId());
            $author->load();
            if ($author->getAuthorid() != 0) {
                $loaded = true;
            }
            /** End ZMO */
        } else {
            if ( Hal_Cv::existFromIdext($type, trim($idno->nodeValue)) ) {
                $author->setAuthorid(Hal_Cv::getFromIdext($type, trim($idno->nodeValue)));
                $author->load();
                if ( $author->getAuthorid() != 0 ) {
                    $loaded = true;
                } else {
                    $author->addIdAuthor($type, $idno->nodeValue);
                }
            } else {
                $author->addIdAuthor($type, $idno->nodeValue);
            }
        }
    }
    if (! $loaded) {
        if ($entry->hasChildNodes()) {
            $info = [];
            foreach($entry->childNodes as $node) {
                if ($node->nodeName == 'persName' && $node->hasChildNodes()) {
                    foreach($node->childNodes as $snode) {
                        if ($snode->nodeName == 'forename') {
                            $type = $snode->getAttribute('type');
                            if ($type == 'first') {
                                $info['firstname'] = trim($snode->nodeValue);
                            } else if ($type == 'middle') {
                                $info['othername'] = trim($snode->nodeValue);
                            }
                        } else if ($snode->nodeName == 'surname') {
                            $info['lastname'] = trim($snode->nodeValue);
                        }
                    }
                } else if ($node->nodeName == 'email') {
                    /** Begin ZMO - Get Non MD5 emails */
                    if($node->getAttribute('type') != 'md5' && $node->getAttribute('type') != 'domain') {
                        $mail = filter_var(trim($node->nodeValue), FILTER_VALIDATE_EMAIL);
                        if ($mail !== false) {
                            $info['email'] = $mail;
                        }
                    }
                    /** End ZMO */
                } else if ($node->nodeName == 'ptr') {
                    $info['url'] = trim($node->getAttribute('target'));
                } else if ($node->nodeName == 'orgName') {
                    $ref = str_replace('#', '', $node->getAttribute('ref'));
                    if (strpos($ref, 'struct-') !== false) {
                        $info['organismid'] = trim(str_replace('struct-', '', $ref));
                    }
                }
            }
            $info = array_filter($info);
            if ( isset($info['email']) && filter_var($info['email'], FILTER_VALIDATE_EMAIL) && Hal_Cv::existFromAuthorInfo($info['firstname'], $info['lastname'], $info['email'])  ) {
                $author->setAuthorid(Hal_Cv::getFromAuthorInfo($info['firstname'], $info['lastname'], $info['email']));
                $author->load();
                if ( $author->getAuthorid() == 0 ) {
                    $author->set($info);
                }
            } else {
                $author->set($info);
            }
        }
    }

    /**
     * Récupération des structures
     */
    $affiliations = $xpath->query('tei:affiliation', $entry);
    foreach($affiliations as $affiliation) {
        $structidx = false;
        $ref = str_replace('#', '', $affiliation->getAttribute('ref'));
        if (strpos($ref, 'struct-') !== false) {
            $structidx = $this->addStructure(str_replace('struct-', '', $ref));
        } else if (strpos($ref, 'localStruct-') !== false) {
            $structure = $xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg/tei:org[@xml:id="' . $ref . '"]');
            if ($structure->length == 1) {
                $structidx = $this->addStructure(createStructure($structure->item(0), $xpath));
            }
        }
        if ($structidx !== false) {
            $author->addStructidx($structidx);
        }
    }
    $authorIdx = $this->addAuthor($author);
}

/**
 * ------------- RESSOURCES LIEES ------------
 */
$this->_related = array();

$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:relatedItem');
foreach($entries as $entry) {
    $target = $entry->getAttribute('target');
    $type = $entry->getAttribute('type');
    if ($target != '' && $type != '') {
        $this->_related[] = array('URI'=>basename($target), 'RELATION'=>$type, 'INFO'=>$entry->nodeValue);
    }
}

/**
 * ------------- COLLECTIONS ------------
 */
$this->_collections = array();

$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:seriesStmt/tei:idno');

foreach($entries as $entry) {
    $type = $entry->getAttribute('type');
    $tampid = $entry->getAttribute('n');
    if ($type == 'stamp' && $tampid != '') {
        $collection = Hal_Site::exist($tampid, Hal_Site::TYPE_COLLECTION, true);
        if ($collection->getSid() != 0 && $collection->isTamponneur($this->getContributor('uid')) ) {
            $this->_collections[] = $collection;
        }
    }
}

/**
 * ------------- FICHIERS ------------
 */

$this->_files = array();

$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:editionStmt/tei:edition/tei:ref');
foreach($entries as $entry) {
    $type = $entry->getAttribute('type');
    $subType = $entry->getAttribute('subtype');
    $target = $entry->getAttribute('target');
    $main = $entry->getAttribute('n');
    $file = new Hal_Document_File();
    $file->setType($type);
    $file->setPath($target);
    $file->setName(basename($target));
    if ($type == Hal_Settings::FILE_TYPE) {
        if ( $subType == '') {
            $subType = 'author';
        }
        $file->setOrigin($subType);
        $file->setDefault($main);
    } else if ($type == Hal_Settings::FILE_TYPE_ANNEX) {
        $file->setDefaultannex($main);
        $file->setFormat($subType);
    }
    // On determine les emplacements et/ou Recuperation des fichiers (ftp/url pour grobid)               
    $ok = $file->setFileInfos ($pathImport);
    if (!$ok) {
        // Le fichier n'a pas ete correctement recupere
        continue;
    }
    
    $descriptions = $xpath->query('tei:desc', $entry);
    if ($descriptions->length == 1) {
        $description = $descriptions->item(0);
        $file->setComment($description->nodeValue);
    }

    $dates = $xpath->query('tei:date', $entry);
    if ($dates->length == 1) {
        $date = $dates->item(0)->getAttribute('notBefore');
    } else {
        $date = date("Y-m-d");
    }
    $file->setDateVisible($date);
    $this->_files[] = $file;        
}
if ( count($this->_files) == 1 ) {
    $this->_files[0]->setDefault(true);
}

/**
 * ------------- UTILE ------------
 */

/**
 * Methode permettant de récupérer une structure
 *
 * @param $node
 * @param $xpath
 * @param null $structure
 * @return Hal_Document_Structure|null
 */


function createStructure($node, $xpath, $structure = null)
{
    if ($structure == null) {
        $structure = new Hal_Document_Structure();
    }
    $structure->setTypestruct(strtolower($node->getAttribute('type')));
    if ($node->hasChildNodes()) {
        foreach($node->childNodes as $item) {
            if ($item->nodeName == 'orgName') {
                $type = $item->getAttribute('type');
                if ($type == 'acronym') {
                    $structure->setSigle($item->nodeValue);
                } else {
                    $structure->setStructname($item->nodeValue);
                }

            } else if ($item->nodeName == 'desc') {
                $addrLines = $xpath->query('tei:address/tei:addrLine', $item);
                if ($addrLines->length == 1) {
                    $structure->setAddress($addrLines->item(0)->nodeValue);
                }
                $addrCountry = $xpath->query('tei:address/tei:country', $item);
                if ($addrCountry->length == 1) {
                    $structure->setPaysid(strtolower($addrCountry->item(0)->getAttribute('key')));
                }
                if (isset($item->ref)) {
                    $structure->setUrl($item->ref->nodeValue);
                }
            } else if ($item->nodeName == 'listRelation') {
                $relations = $xpath->query('tei:relation', $item);
                foreach($relations as $relation) {
                    $name = $relation->getAttribute('name');
                    $active = $relation->getAttribute('active');
                    $active = str_replace('#', '', $active);
                    if (strpos($active, 'struct-') !== false) {
                        $structure->addParent(new Ccsd_Referentiels_Structure(str_replace('struct-', '', $active)), $name);
                    } else if (strpos($active, 'localStruct-') !== false) {
                        $structures = $xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg/tei:org[@xml:id="' . $active . '"]');
                        if ($structures->length == 1) {
                            $structure->addParent(createStructure($structures->item(0), $xpath), $name);
                        }
                    }
                }
            }
        }
    }
    return $structure;
}


/** Begin ZMO - To get the geographic coordinates of image docs
 *  listPlace
 *  place
 *  placeName
 *  location
 *  geo
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:listPlace/tei:place/tei:location/tei:geo');
foreach($entries as $entry) {
    if ($entry->nodeValue) {
        $geoLocalisation = preg_split('/(\s|&|,)/', $entry->nodeValue);
        $this->_metas['latitude'] = $geoLocalisation[0];
        $this->_metas['longitude'] = $geoLocalisation[1];
    }
}

/** Begin ZMO - To get the duration of video or audio docs
 *  recordingStmt
 *  recording
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:recordingStmt/tei:recording');
foreach ($entries as $entry) {
    $interval = new DateInterval($entry->getAttribute('dur'));
    $this->_metas['duration'] = $interval->format('%h:%i:%s');
}

/** Begin ZMO - To get the local meta for portal
 *  fs
 *  f
 *  string
 */
$entries = $xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:editionStmt/tei:edition/tei:fs/tei:f');
foreach ($entries as $entry) {
    if ($entry->hasChildNodes()) {
        foreach($entry->childNodes as $node) {
            $metasList = Hal_Referentiels_Metadata::metaList();
            // Meta with id and text
            if (in_array($entry->getAttribute('name'), $metasList)) {
                // if numeric, we get the value
                if ($node->nodeName == 'numeric') {
                    $this->_metas[$entry->getAttribute('name')] = $node->nodeValue;
                }
            // Meta based free text
            } else {
                $this->_metas[$entry->getAttribute('name')] = $node->nodeValue;
            }
        }
    }
}
/** End ZMO */
