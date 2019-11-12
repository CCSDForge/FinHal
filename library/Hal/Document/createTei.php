<?php


$xml->formatOutput = true;
$xml->substituteEntities = true;
$xml->preserveWhiteSpace = false;
$root = $xml->createElement('TEI');
$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns', 'http://www.tei-c.org/ns/1.0');
$root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:hal', 'http://hal.archives-ouvertes.fr/');
$xml->appendChild($root);

$head = $xml->createElement('teiHeader');
$fd = $xml->createElement('fileDesc');
$ts = $xml->createElement('titleStmt');
$title = $xml->createElement('title', 'HAL TEI export of '.$this->_identifiant);
$ts->appendChild($title);
$fd->appendChild($ts);
$ps = $xml->createElement('publicationStmt');
$ps->appendChild($xml->createElement('distributor', 'CCSD'));
$headeravailability = $xml->createElement('availability');
$headeravailability->setAttribute('status', 'restricted');

$translator = Zend_Registry::get('Zend_Translate');

if ( $this->getLicence() != '' ) {
    $headerlicence = $xml->createElement('licence', $translator->translate(Hal_Referentiels_Metadata::getLabel('licence', $this->getLicence()), 'en'));
    $headerlicence->setAttribute('target', $this->getLicence());
} else {
    $headerlicence = $xml->createElement('licence', 'Distributed under a Creative Commons Attribution 4.0 International License');
    $headerlicence->setAttribute('target', 'http://creativecommons.org/licenses/by/4.0/');
}
$headeravailability->appendChild($headerlicence);
$ps->appendChild($headeravailability);
$headerdate = $xml->createElement('date');
$headerdate->setAttribute('when', date('c'));
$ps->appendChild($headerdate);
$fd->appendChild($ps);
$sd = $xml->createElement('sourceDesc');
$p = $xml->createElement('p', 'HAL API platform');
$p->setAttribute('part', 'N');
$sd->appendChild($p);
$fd->appendChild($sd);
$head->appendChild($fd);
$root->appendChild($head);
$text = $xml->createElement('text');

// //////////////////////
// text>body>listBibl //
// //////////////////////
$body = $xml->createElement('body');
$lb = $xml->createElement('listBibl');
$b = $xml->createElement('biblFull');
// /////////////
// titleStmt //
// /////////////
$ts = $xml->createElement('titleStmt');
// title
foreach ($this->getTitle() as $l => $t) {
    $tit = $xml->createElement('title', $t);
    $tit->setAttribute('xml:lang', $l);
    $ts->appendChild($tit);
}
// subtitle
foreach ($this->getMeta('subTitle') as $l => $t) {
    $stit = $xml->createElement('title', $t);
    $stit->setAttribute('xml:lang', $l);
    $stit->setAttribute('type', 'sub');
    $ts->appendChild($stit);
}
// auteurs/structures
$structures = array();
foreach ($this->_authors as $author) {
    /** @var  Hal_Document_Author $author */
    $aut = $xml->createElement('author');
    $aut->setAttribute('role', $author->getQuality());
    $persName = $xml->createElement('persName');
    $first = $xml->createElement('forename', $author->getFirstname());
    $first->setAttribute('type', 'first');
    $persName->appendChild($first);
    if ( $author->getOthername() ) {
        $middle = $xml->createElement('forename', $author->getOthername());
        $middle->setAttribute('type', 'middle');
        $persName->appendChild($middle);
    }
    $persName->appendChild($xml->createElement('surname', $author->getLastname()));
    $aut->appendChild($persName);
    /** Begin ZMO - MD5 emails */
    $email = $xml->createElement('email', Hal_Document_Author::getEmailHashed((string)$author->getEmail(), Hal_Settings::EMAIL_HASH_TYPE));
    $email->setAttribute('n',Hal_Settings::EMAIL_HASH_TYPE);
    $aut->appendChild($email);
    /** End ZMO */
    if ( $author->getUrl() ) {
        $url = $xml->createElement('ptr');
        $url->setAttribute('type', 'url');
        $url->setAttribute('target', $author->getUrl());
        $aut->appendChild($url);
    }
    if ( $author->getIdHal() ) {
        $idhal = $xml->createElement('idno', $author->getIdhalstring());
        $idhal->setAttribute('type', 'idhal');
        $aut->appendChild($idhal);
    }
    $authorid = $xml->createElement('idno', $author->getAuthorid());
    $authorid->setAttribute('type', 'halauthor');
    $aut->appendChild($authorid);
    foreach ( $author->getIdsAuthor($this->_docid) as $site=>$id ) {
        $ident = $xml->createElement('idno', $id);
        $ident->setAttribute('type', $site);
        $aut->appendChild($ident);
    }
    if ( $author->getOrganismId() ) {
        $org = $xml->createElement('orgName');
        $org->setAttribute('ref', '#struct-'.$author->getOrganismId());
        $aut->appendChild($org);
        if ( !in_array($author->getOrganismId(), $structures) ) {
            $structures[] = $author->getOrganismId();
        }
    }
    foreach ( $author->getStructid() as $id ) {
        $affi = $xml->createElement('affiliation');
        $affi->setAttribute('ref', '#struct-'.$id);
        $aut->appendChild($affi);
        if ( !in_array($id, $structures) ) {
            $structures[] = $id;
        }
    }
    $ts->appendChild($aut);
}
// contributeur
if ( $this->getContributor('lastname') != '' && $this->getContributor('firstname') != '' && $this->getContributor('email') != '' ) {
    $c = $xml->createElement('editor');
    $c->setAttribute('role', 'depositor');
    $persName = $xml->createElement('persName');
    $persName->appendChild($xml->createElement('forename', $this->getContributor('firstname')));
    $persName->appendChild($xml->createElement('surname', $this->getContributor('lastname')));
    $c->appendChild($persName);
    /** Begin ZMO - Md5 email of contributors -editors- */
    $email = $xml->createElement('email', Hal_Document_Author::getEmailHashed((string)$this->getContributor('email'),Hal_Settings::EMAIL_HASH_TYPE));
    $email->setAttribute('n',Hal_Settings::EMAIL_HASH_TYPE);
    $c->appendChild($email);
    $ts->appendChild($c);
    /** End ZMO */
}
// financements
if ( is_array($this->getMeta('anrProject')) ) {
    $projanr = array(); $i = 0;
    foreach ( $this->getMeta('anrProject') as $anr ) {
        if ( $anr instanceof Ccsd_Referentiels_Anrproject ) {
            $p = $xml->createElement('funder');
            $p->setAttribute('ref', '#projanr-'.$anr->ANRID);
            $ts->appendChild($p);
            $projanr[$i++] = $anr;
        }
    }
}
if ( is_array($this->getMeta('europeanProject')) ) {
    $projeurope = array(); $i = 0;
    foreach ( $this->getMeta('europeanProject') as $europe ) {
        if ( $europe instanceof Ccsd_Referentiels_Europeanproject ) {
            $p = $xml->createElement('funder');
            $p->setAttribute('ref', '#projeurop-'.$europe->PROJEUROPID);
            $ts->appendChild($p);
            $projeurope[$i++] = $europe;
        }
    }
}
if ( is_array($this->getMeta('funding')) ) {
    foreach ( $this->getMeta('funding') as $funder ) {
        $ts->appendChild($xml->createElement('funder', $funder));
    }
}
$b->appendChild($ts);
/////////////////
// editionStmt //
/////////////////
$es = $xml->createElement('editionStmt');
// Les différentes versions
foreach ( $this->_versions as $n=>$date ) {
    $edition = $xml->createElement('edition');
    $edition->setAttribute('n', 'v'.$n);
    $d = $xml->createElement('date', $date);
    $d->setAttribute('type', 'whenSubmitted');
    $edition->appendChild($d);
    if ( $this->_version == $n ) {
        $edition->setAttribute('type', 'current');
        if ( $this->getMeta('writingDate') ) {
            $edition->appendChild($d);
            $d = $xml->createElement('date', $this->getMeta('writingDate'));
            $edition->appendChild($d);
            $d->setAttribute('type', 'whenWritten');
        }
        $d = $xml->createElement('date', $this->_modifiedDate);
        $d->setAttribute('type', 'whenModified');
        $edition->appendChild($d);
        $d = $xml->createElement('date', $this->_releasedDate);
        $d->setAttribute('type', 'whenReleased');
        $edition->appendChild($d);
        $d = $xml->createElement('date', $this->_producedDate);
        $d->setAttribute('type', 'whenProduced');
        $edition->appendChild($d);
        if ( $this->_format == Hal_Document::FORMAT_FILE ) {
            $d = $xml->createElement('date', $this->getFirstDateVisibleFile());
            $d->setAttribute('type', 'whenEndEmbargoed');
            $edition->appendChild($d);
            $ref = $xml->createElement('ref');
            $ref->setAttribute('type', 'file');
            $ref->setAttribute('target', $this->getUri(true).$this->getUrlMainFile());
            $v = $this->getDateVisibleMainFile();
            if ( preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $v) ) {
                $date = $xml->createElement('date');
                $date->setAttribute('notBefore', $v);
                $ref->appendChild($date);
            }
            $edition->appendChild($ref);
        }
        foreach ( $this->_files as $file ) {
            if ( $file instanceof Hal_Document_File && in_array($file->getType(), array(Hal_Document::FORMAT_FILE, Hal_Document::FORMAT_ANNEX)) ) {
                $ref = $xml->createElement('ref');
                $ref->setAttribute('type', $file->getType());
                if ( $file->getType() == 'file' ) {
                    if ( $file->getOrigin() ) {
                        $ref->setAttribute('subtype', $file->getOrigin());
                    }
                } else if ( $file->getType() == 'annex' ) {
                    $ref->setAttribute('subtype', $file->getFormat());
                }
                $ref->setAttribute('n', (int)($file->getDefault()||$file->getDefaultannex()));
                $ref->setAttribute('target', $this->getUri().'/file/'.rawurlencode($file->getName()));
                if ( preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $file->getDateVisible()) ) {
                    $date = $xml->createElement('date');
                    $date->setAttribute('notBefore', $file->getDateVisible());
                    $ref->appendChild($date);
                }
                if ( $file->getComment() != '' ) {
                    $ref->appendChild($xml->createElement('desc', $file->getComment()));
                }
                $edition->appendChild($ref);
            }
        }
    }
    $es->appendChild($edition);
}
// Le déposant : responsable du dépôt
if ( $this->getContributor('uid') != '' && $this->getContributor('lastname') != '' && $this->getContributor('firstname') != '' && $this->getContributor('email') != '' ) {
    $respStmt = $xml->createElement('respStmt');
    $respStmt->appendChild($xml->createElement('resp', 'contributor'));
    $name = $xml->createElement('name');
    $name->setAttribute('key', $this->getContributor('uid'));
    $persName = $xml->createElement('persName');
    $persName->appendChild($xml->createElement('forename', $this->getContributor('firstname')));
    $persName->appendChild($xml->createElement('surname', $this->getContributor('lastname')));
    $name->appendChild($persName);
    /** Begin ZMO - Md5 email of responsables -RespStmt- */
    $email = $xml->createElement('email', Hal_Document_Author::getEmailHashed((string)$this->getContributor('email'),Hal_Settings::EMAIL_HASH_TYPE));
    $email->setAttribute('n',Hal_Settings::EMAIL_HASH_TYPE);
    $name->appendChild($email);
    $respStmt->appendChild($name);
    $es->appendChild($respStmt);
    /** End ZMO */
}
$b->appendChild($es);

/** Begin ZMO - Manage Additional information for portal
 *  edition>fs
 */
$processOnFS = false;
// Test if the return of the method getLocalMeta is an array and his size to not null
if(is_array($this->getLocalMeta()) && count($this->getLocalMeta())) {
    // Assign the return of this method getLocalMeta to the variable $localMetaList
    $localMetaList = $this->getLocalMeta();
    // Create a fs element
    $fs = $xml->createElement('fs');
    // Loop over the names of the retrieved local metas
    foreach ($localMetaList as $LML) {
        // Test if meta value not null
        if ($this->getMeta($LML) != '') {
            $metasList = Hal_Referentiels_Metadata::metaList();
            // Meta type list with id ans text (e.g. inria_presConf)
            if (in_array($LML, $metasList)) {
                ///////////// Creation of string element ////////////////
                // Create a f element
                $f = $xml->createElement('f');
                // Assign the name of the retrieved local meta to the attribute NAME of the f element
                $f->setAttribute('name', $LML);
                // Assign the value string to the attribut notation
                $f->setAttribute('notation', 'string');
                // Create a string element and assign the value of the retrieved local meta to it
                $string = $xml->createElement('string', $translator->translate(Hal_Referentiels_Metadata::getLabel($LML, $this->getMeta($LML)), 'en'));
                // Add the string element to the f element
                $f->appendChild($string);
                // Add the f element to the fs element
                $fs->appendChild($f);
                ///////////// Creation of numeric element ///////////////
                $f = $xml->createElement('f');
                $f->setAttribute('name', $LML);
                $f->setAttribute('notation', 'numeric');
                $numeric = $xml->createElement('numeric', $this->getMeta($LML));
                $f->appendChild($numeric);
                $fs->appendChild($f);
            // Meta based free text
            } else {
                $f = $xml->createElement('f');
                $f->setAttribute('name', $LML);
                $f->setAttribute('notation', 'string');
                $string = $xml->createElement('string', $this->getMeta($LML));
                $f->appendChild($string);
                $fs->appendChild($f);
            }
            $processOnFS = true;
        }
    }
    if ($processOnFS) {
        // Add the fs element to the edition element
        $edition->appendChild($fs);
    }
}
/** End ZMO */

/////////////////////k
// publicationStmt //
/////////////////////
$ps = $xml->createElement('publicationStmt');
$ps->appendChild($xml->createElement('distributor', 'CCSD'));
$hal = $xml->createElement('idno', $this->getId());
$hal->setAttribute('type', 'halId');
$ps->appendChild($hal);
$uri = $xml->createElement('idno', $this->getUri());
$uri->setAttribute('type', 'halUri');
$ps->appendChild($uri);
$bibtex = $xml->createElement('idno', $this->getKeyBibtex());
$bibtex->setAttribute('type', 'halBibtex');
$ps->appendChild($bibtex);
$citation = $xml->createElement('idno', $this->getCitation());
$citation->setAttribute('type', 'halRefHtml');
$ps->appendChild($citation);
$citation = $xml->createElement('idno', strip_tags($this->getCitation()));
$citation->setAttribute('type', 'halRef');
$ps->appendChild($citation);
if ( $this->getLicence() != '' ) {
    $headeravailability = $xml->createElement('availability');
    $headeravailability->setAttribute('status', 'restricted');
    $headerlicence = $xml->createElement('licence', $translator->translate(Hal_Referentiels_Metadata::getLabel('licence', $this->getLicence()), 'en'));
    $headerlicence->setAttribute('target', $this->getLicence());
    $headeravailability->appendChild($headerlicence);
    $ps->appendChild($headeravailability);
}
$b->appendChild($ps);
// //////////////
// seriesStmt //
// //////////////
$ss = $xml->createElement('seriesStmt');
foreach ($this->_collections as $collection) {
    if ($collection instanceof Hal_Site_Collection) {
        $idno = $xml->createElement('idno', $collection->getFullName());
        $idno->setAttribute('type', 'stamp');
        $idno->setAttribute('n', $collection->getShortname());
        foreach ($collection->getParents() as $parent) {
            if ($parent instanceof Hal_Site_Collection) {
                $idno->setAttribute('p', $parent->getShortname());
            }
        }
        $ss->appendChild($idno);
    }
}
$b->appendChild($ss);
// /////////////
// notesStmt //
// /////////////
$ns = $xml->createElement('notesStmt');
$comment = $this->getMeta('comment');
if ( $comment != '' ) {
    $note = $xml->createElement('note', $comment);
    $note->setAttribute('type', 'commentary');
    $ns->appendChild($note);
}
$description = $this->getMeta('description');
if ( $description != '' ) {
    $note = $xml->createElement('note', $description);
    $note->setAttribute('type', 'description');
    $ns->appendChild($note);
}
$audience = $this->getMeta('audience');
if ( $audience != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('audience', $audience), 'en'));
    $note->setAttribute('type', 'audience');
    $note->setAttribute('n', $audience);
    $ns->appendChild($note);
}
$typrap = $this->getMeta('reportType');
if ( $typrap != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('reportType', $typrap), 'en'));
    $note->setAttribute('type', 'report');
    $note->setAttribute('n', $typrap);
    $ns->appendChild($note);
}
$typimg = $this->getMeta('imageType');
if ( $typimg != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('imageType', $typimg), 'en'));
    $note->setAttribute('type', 'image');
    $note->setAttribute('n', $typimg);
    $ns->appendChild($note);
}
$typlect = $this->getMeta('lectureType');
if ( $typlect != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('lectureType', $typlect), 'en'));
    $note->setAttribute('type', 'lecture');
    $note->setAttribute('n', $typlect);
    $ns->appendChild($note);
}
$invite = $this->getMeta('invitedCommunication');
if ( $invite != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('invitedCommunication', $invite), 'en'));
    $note->setAttribute('type', 'invited');
    $note->setAttribute('n', $invite);
    $ns->appendChild($note);
}
$popular = $this->getMeta('popularLevel');
if ( $popular != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('popularLevel', $popular), 'en'));
    $note->setAttribute('type', 'popular');
    $note->setAttribute('n', $popular);
    $ns->appendChild($note);
}
$peer = $this->getMeta('peerReviewing');
if ( $peer != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('peerReviewing', $peer), 'en'));
    $note->setAttribute('type', 'peer');
    $note->setAttribute('n', $peer);
    $ns->appendChild($note);
}
$proceedings = $this->getMeta('proceedings');
if ( $proceedings != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('proceedings', $proceedings), 'en'));
    $note->setAttribute('type', 'proceedings');
    $note->setAttribute('n', $proceedings);
    $ns->appendChild($note);
}
$inria_degreeType = $this->getMeta('inria_degreeType');
if ( $inria_degreeType != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('inria_degreeType', $inria_degreeType), 'en'));
    $note->setAttribute('type', 'degree');
    $note->setAttribute('n', $inria_degreeType);
    $ns->appendChild($note);
}
$dumas_degreeType = $this->getMeta('dumas_degreeType');
if ( $dumas_degreeType != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('dumas_degreeType', $dumas_degreeType), 'en'));
    $note->setAttribute('type', 'degree');
    $note->setAttribute('n', $dumas_degreeType);
    $ns->appendChild($note);
}
$democrite_degreeType = $this->getMeta('democrite_degreeType');
if ( $democrite_degreeType != '' ) {
    $note = $xml->createElement('note', $translator->translate(Hal_Referentiels_Metadata::getLabel('democrite_degreeType', $democrite_degreeType), 'en'));
    $note->setAttribute('type', 'degree');
    $note->setAttribute('n', $democrite_degreeType);
    $ns->appendChild($note);
}
$b->appendChild($ns);
////////////////
// sourceDesc //
////////////////
$sd = $xml->createElement('sourceDesc');
$biblStruct = $xml->createElement('biblStruct');
// sourceDesc>biblStruct>analytic
$analytic = $xml->createElement('analytic');
// title
foreach ($this->getTitle() as $l => $t) {
    $tit = $xml->createElement('title', $t);
    $tit->setAttribute('xml:lang', $l);
    $analytic->appendChild($tit);
}
// subtitle
foreach ($this->getMeta('subTitle') as $l => $t) {
    $stit = $xml->createElement('title', $t);
    $stit->setAttribute('xml:lang', $l);
    $stit->setAttribute('type', 'sub');
    $analytic->appendChild($stit);
}
// auteurs/structures
foreach ($this->_authors as $author) {
    $aut = $xml->createElement('author');
    $aut->setAttribute('role', $author->getQuality());
    $persName = $xml->createElement('persName');
    $first = $xml->createElement('forename', $author->getFirstname());
    $first->setAttribute('type', 'first');
    $persName->appendChild($first);
    if ($author->getOthername()) {
        $middle = $xml->createElement('forename', $author->getOthername());
        $middle->setAttribute('type', 'middle');
        $persName->appendChild($middle);
    }
    $persName->appendChild($xml->createElement('surname', $author->getLastname()));
    $aut->appendChild($persName);
    if ( $author->getEmail() ) {
        /** Begin ZMO - MD5 the emails of authors in sourceDesc */
        $email = $xml->createElement('email', Hal_Document_Author::getEmailHashed((string)$author->getEmail(), Hal_Settings::EMAIL_HASH_TYPE));
        $email->setAttribute('n',Hal_Settings::EMAIL_HASH_TYPE);
        $aut->appendChild($email);
        /** End ZMO */
    }
    if ($author->getUrl()) {
        $url = $xml->createElement('ptr');
        $url->setAttribute('type', 'url');
        $url->setAttribute('target', $author->getUrl());
        $aut->appendChild($url);
    }
    if ($author->getIdHal()) {
        // Yannick and ZMO - Generate string idhal
        $idhal = $xml->createElement('idno', $author->getIdhalstring());
        $idhal->setAttribute('type', 'idHal');
        $idhal->setAttribute('notation', 'string');
        $aut->appendChild($idhal);
        // Yannick and ZMO - Generate numeric idhal
        $idhal = $xml->createElement('idno', $author->getIdHal());
        $idhal->setAttribute('type', 'idHal');
        $idhal->setAttribute('notation', 'numeric');
        $aut->appendChild($idhal);
    }
    $authorid = $xml->createElement('idno', $author->getAuthorid());
    $authorid->setAttribute('type', 'halAuthorId');
    $aut->appendChild($authorid);
    foreach ($author->getIdsAuthor($this->_docid) as $site => $id) {
        $ident = $xml->createElement('idno', $id);
        $ident->setAttribute('type', $site);
        $aut->appendChild($ident);
    }
    if ($author->getOrganismId()) {
        $org = $xml->createElement('orgName');
        $org->setAttribute('ref', '#struct-' . $author->getOrganismId());
        $aut->appendChild($org);
    }
    foreach ($author->getStructid() as $id) {
        $affi = $xml->createElement('affiliation');
        $affi->setAttribute('ref', '#struct-' . $id);
        $aut->appendChild($affi);
    }
    $analytic->appendChild($aut);
}
$biblStruct->appendChild($analytic);
// sourceDesc>biblStruct>monogr
$monogr = $xml->createElement('monogr');
if ( $this->getMeta('nnt') != '' ) {
    $idno = $xml->createElement('idno', $this->getMeta('nnt'));
    $idno->setAttribute('type', 'nnt');
    $monogr->appendChild($idno);
}
if ( $this->getMeta('number') != '' ) {
    $idno = $xml->createElement('idno', $this->getMeta('number'));
    $idno->setAttribute('type', strtolower($this->getTypDoc()).'Number');
    $monogr->appendChild($idno);
}
if ( $this->getMeta('isbn') != '' ) {
    $idno = $xml->createElement('idno', $this->getMeta('isbn'));
    $idno->setAttribute('type', 'isbn');
    $monogr->appendChild($idno);
}
foreach ($this->getMeta('localReference') as $ref) {
    $idno = $xml->createElement('idno', $ref);
    $idno->setAttribute('type', 'localRef');
    $monogr->appendChild($idno);
}
if ( ( $oJ = $this->getMeta('journal') ) instanceof Ccsd_Referentiels_Journal ) {
    $journal = $xml->createElement('idno', $oJ->JID);
    $journal->setAttribute('type', 'halJournalId');
    $journal->setAttribute('status', strtoupper($oJ->VALID));
    $monogr->appendChild($journal);
    if ( $oJ->ISSN ) {
        $journal = $xml->createElement('idno', $oJ->ISSN);
        $journal->setAttribute('type', 'issn');
        $monogr->appendChild($journal);
    }
    if ( $oJ->EISSN ) {
        $journal = $xml->createElement('idno', $oJ->EISSN);
        $journal->setAttribute('type', 'eissn');
        $monogr->appendChild($journal);
    }
    $journal = $xml->createElement('title', $oJ->JNAME);
    $journal->setAttribute('level', 'j');
    $monogr->appendChild($journal);
    if ( $oJ->PUBLISHER ) {
        $journalPublisher = $oJ->PUBLISHER;
    }
}
if ( $this->getMeta('bookTitle') != '' ) {
    $title = $xml->createElement('title', $this->getMeta('bookTitle'));
    $title->setAttribute('level', 'm');
    $monogr->appendChild($title);
}
if ( $this->getTypDoc() == 'COMM' && $this->getMeta('source') != '' ) {
    $title = $xml->createElement('title', $this->getMeta('source'));
    $title->setAttribute('level', 'm');
    $monogr->appendChild($title);
}
if ( !in_array($this->_typdoc, array('PATENT', 'IMG', 'MAP', 'LECTURE')) && ( $this->getMeta('conferenceTitle') != '' || $this->getMeta('conferenceStartDate') != '' || $this->getMeta('conferenceEndDate') != '' || $this->getMeta('city') != '' || $this->getMeta('country') != '' || count($this->getMeta('conferenceOrganizer')) ) ) {
    $meeting = $xml->createElement('meeting');
    if ( $this->getMeta('conferenceTitle') != '' )
        $meeting->appendChild($xml->createElement('title', $this->getMeta('conferenceTitle')));
    if ( $this->getMeta('conferenceStartDate') != '' ) {
        $d = $xml->createElement('date', $this->getMeta('conferenceStartDate'));
        $d->setAttribute('type', 'start');
        $meeting->appendChild($d);
    }
    if ( $this->getMeta('conferenceEndDate') != '' ) {
        $d = $xml->createElement('date', $this->getMeta('conferenceEndDate'));
        $d->setAttribute('type', 'end');
        $meeting->appendChild($d);
    }
    if ( $this->getMeta('city') != '' ) {
        $meeting->appendChild($xml->createElement('settlement', $this->getMeta('city')));
    }
    if ( $this->getMeta('country') != '' ) {
        $country = $xml->createElement('country', Zend_Locale::getTranslation(strtoupper($this->getMeta('country')), 'country', 'en'));
        $country->setAttribute('key', strtoupper($this->getMeta('country')));
        $meeting->appendChild($country);
    }
    $monogr->appendChild($meeting);
    if ( count($this->getMeta('conferenceOrganizer')) ) {
        $resp = $xml->createElement('respStmt');
        $resp->appendChild($xml->createElement('resp', 'conferenceOrganizer'));
        foreach ( $this->getMeta('conferenceOrganizer') as $orga ) {
            $resp->appendChild($xml->createElement('name', $orga));
        }
        $monogr->appendChild($resp);
    }
}
if ( in_array($this->_typdoc, array('PATENT', 'IMG', 'MAP', 'LECTURE')) ) {
    if ( $this->getMeta('city') != '' ) {
        $monogr->appendChild($xml->createElement('settlement', $this->getMeta('city')));
    }
    if ( $this->getMeta('country') != '' ) {
        $country = $xml->createElement('country', Zend_Locale::getTranslation(strtoupper($this->getMeta('country')), 'territory', 'en'));
        $country->setAttribute('key', strtoupper($this->getMeta('country')));
        $monogr->appendChild($country);
    }
}
foreach ( $this->getMeta('scientificEditor') as $edsci ) {
    $monogr->appendChild($xml->createElement('editor', $edsci));
}
// sourceDesc>biblStruct>monogr>imprint
$imprint  = $xml->createElement('imprint');
foreach ( $this->getMeta('publisher') as $publisher ) {
    $imprint->appendChild($xml->createElement('publisher', $publisher));
}
if ( isset($journalPublisher) && $journalPublisher != '' && !in_array(strtolower($journalPublisher), array_map('strtolower', $this->getMeta('publisher'))) ) {
    $imprint->appendChild($xml->createElement('publisher', $journalPublisher));
}
if ( $this->getMeta('publicationLocation') != '' ) {
    $imprint->appendChild($xml->createElement('pubPlace', $this->getMeta('publicationLocation')));
}
if ( $this->getMeta('serie') != '' ) {
    $bs = $xml->createElement('biblScope', $this->getMeta('serie'));
    $bs->setAttribute('unit', 'serie');
    $imprint->appendChild($bs);
}
if ( $this->getMeta('volume') != '' ) {
    $bs = $xml->createElement('biblScope', $this->getMeta('volume'));
    $bs->setAttribute('unit', 'volume');
    $imprint->appendChild($bs);
}
if ( $this->getMeta('issue') != '' ) {
    $bs = $xml->createElement('biblScope', $this->getMeta('issue'));
    $bs->setAttribute('unit', 'issue');
    $imprint->appendChild($bs);
}
if ( $this->getMeta('page') != '' ) {
    $bs = $xml->createElement('biblScope', $this->getMeta('page'));
    $bs->setAttribute('unit', 'pp');
    $imprint->appendChild($bs);
}
if ( $this->getMeta('date') != '' ) {
    $d = $xml->createElement('date', $this->getMeta('date'));
    if ( in_array($this->_typdoc, array('THESE', 'HDR', 'MEM', 'ETABTHESE')) ) {
        $d->setAttribute('type', 'dateDefended');
    } else {
        $d->setAttribute('type', 'datePub');
    }
    if ( $this->getMeta('circa') == 1 ) {
        $d->setAttribute('precision', 'unknown');
    }
    $imprint->appendChild($d);
}
if ( $this->getMeta('edate') != '' ) {
    $d = $xml->createElement('date', $this->getMeta('edate'));
    $d->setAttribute('type', 'dateEpub');
    $imprint->appendChild($d);
}
$monogr->appendChild($imprint);
foreach ( $this->getMeta('authorityInstitution') as $orgthe ) {
    $auth = $xml->createElement('authority', $orgthe);
    $auth->setAttribute('type', 'institution');
    $monogr->appendChild($auth);
}
foreach ( $this->getMeta('thesisSchool') as $school ) {
    $auth = $xml->createElement('authority', $school);
    $auth->setAttribute('type', 'school');
    $monogr->appendChild($auth);
}
foreach ( $this->getMeta('director') as $dir ) {
    $auth = $xml->createElement('authority', $dir);
    $auth->setAttribute('type', 'supervisor');
    $monogr->appendChild($auth);
}
if ( $this->getMeta('inria_directorEmail') != '' ) {
    $auth = $xml->createElement('authority', $this->getMeta('inria_directorEmail'));
    $auth->setAttribute('type', 'supervisorEmail');
    $monogr->appendChild($auth);
}
if ( $this->getMeta('memsic_directorEmail') != '' ) {
    $auth = $xml->createElement('authority', $this->getMeta('memsic_directorEmail'));
    $auth->setAttribute('type', 'supervisorEmail');
    $monogr->appendChild($auth);
}
foreach ( $this->getMeta('committee') as $jury ) {
    $auth = $xml->createElement('authority', $jury);
    $auth->setAttribute('type', 'jury');
    $monogr->appendChild($auth);
}
$biblStruct->appendChild($monogr);
// sourceDesc>biblStruct>series
if ( count($this->getMeta('seriesEditor')) || $this->getMeta('lectureName') != '' ) {
    $series = $xml->createElement('series');
    foreach ( $this->getMeta('seriesEditor') as $edcoll ) {
        $series->appendChild($xml->createElement('editor', $edcoll));
    }
    if ( $this->getMeta('lectureName') != '' ) {
        $series->appendChild($xml->createElement('title', $this->getMeta('lectureName')));
    }
    $biblStruct->appendChild($series);
}
// sourceDesc>biblStruct>idno
foreach ($this->_metas['identifier'] as $code => $id) {
    $idno = $xml->createElement('idno', $id);
    $idno->setAttribute('type', $code);
    $biblStruct->appendChild($idno);
}
foreach ( $this->getMeta('seeAlso') as $url ) {
    $ref = $xml->createElement('ref', $url);
    $ref->setAttribute('type', 'seeAlso');
    $biblStruct->appendChild($ref);
}
if ( $this->getMeta('publisherLink') != '' ) {
    $ref = $xml->createElement('ref', $this->getMeta('publisherLink'));
    $ref->setAttribute('type', 'publisher');
    $biblStruct->appendChild($ref);
}
// ressources HAL liées
foreach ( $this->_related as $info) {
    $item = $xml->createElement('relatedItem', $info['INFO']);
    $item->setAttribute('target', $info['URI']);
    $item->setAttribute('type', $info['RELATION']);
    $biblStruct->appendChild($item);
}
$sd->appendChild($biblStruct);
$b->appendChild($sd);

/** Begin ZMO - Manage Latitude and Longitude of image docs
 *  sourceDesc>listPlace
 */
// Test if the values of the elements latitude and longitude aren't null
if ($this->getMeta('latitude') !='' && $this->getMeta('longitude')!='') {
    // Create a listPlace element
    $listPlace = $xml->createElement('listPlace');
    // Create a place element
    $place = $xml->createElement('place');
    // Create a location element
    $location = $xml->createElement('location');
    // Create a geo element and assign the values of latitude and longitude to it
    $geo = $xml->createElement('geo', $this->getMeta('latitude') . ' ' . $this->getMeta('longitude'));
    // Add the geo element to the location element
    $location->appendChild($geo);
    // Add the location element to the place element
    $place->appendChild($location);
    // Add the place element to the listPlace element
    $listPlace->appendChild($place);
    // Add the listPlace element to the sourceDesc element
    $sd->appendChild($listPlace);
}
/** End ZMO */

/** Begin ZMO - Manage Duration of video docs
 *  sourceDesc>recordingStmt
 */
// Test if the value of the duration element isn't null
if($this->getMeta('duration') !='') {
    // Create a recordingStmt element
    $recordingStmt = $xml->createElement('recordingStmt');
    //Create a recording element
    $recording = $xml->createElement('recording');
    // Get the type of document
    if($this->getTypDoc() == 'VIDEO') {
        // if video, assign the value VIDEO to attribute TYPE of the recording element
        $recording->setAttribute('type', 'video');
    }
    else if ($this->getTypDoc() == 'SON') {
        // if audio, assign the value audio to attribute TYPE of the recording element
        $recording->setAttribute('type', 'audio');
    }
    // Assign the value of duration to the attribute dur of the recording element
    $recording->setAttribute('dur', $this->getMeta('duration'));
    // Add the recording element to the recordingStmt element
    $recordingStmt->appendChild($recording);
    // Add the recordingStmt element to the sourceDesc element
    $sd->appendChild($recordingStmt);
}
/** End ZMO */

// ///////////////
// profileDesc //
// ///////////////
$pd = $xml->createElement('profileDesc');
// profileDesc>langUsage>language
$lu = $xml->createElement('langUsage');
$lang = $xml->createElement('language', Zend_Locale::getTranslation($this->getMeta('language'), 'language', 'en'));
$lang->setAttribute('ident', $this->getMeta('language'));
$lu->appendChild($lang);
$pd->appendChild($lu);
// profileDesc>textClass
$textClass = $xml->createElement('textClass');
// keyword
if ( count($kwls = $this->getKeywords()) ) {
    $kws = $xml->createElement('keywords');
    $kws->setAttribute('scheme', 'author');
    foreach ( $kwls as $lang=>$keywords ) {
        if ( is_array($keywords) ) {
            foreach ( $keywords as $keyword ) {
                $kw = $xml->createElement('term', $keyword);
                $kw->setAttribute('xml:lang', $lang);
                $kws->appendChild($kw);
            }
        } else {
            $kw = $xml->createElement('term', $keywords);
            $kw->setAttribute('xml:lang', $lang);
            $kws->appendChild($kw);
        }
    }
    $textClass->appendChild($kws);
}
// classif
if ( ( $classif = $this->getMeta('classification') ) != '' ) {
    $kws = $xml->createElement('classCode', $classif);
    $kws->setAttribute('scheme', 'classification');
    $textClass->appendChild($kws);
}
// mesh
foreach ( $this->getMeta('mesh') as $mesh ) {
    $kws = $xml->createElement('classCode', $mesh);
    $kws->setAttribute('scheme', 'mesh');
    $textClass->appendChild($kws);
}
// jel
foreach ( $this->getMeta('jel') as $jel ) {
    $kws = $xml->createElement('classCode', Ccsd_Tools_String::getHalMetaTranslated($jel, 'en', '/', 'jel'));
    $kws->setAttribute('scheme', 'jel');
    $kws->setAttribute('n', $jel);
    $textClass->appendChild($kws);
}
// acm
foreach ( $this->getMeta('acm') as $acm ) {
    $kws = $xml->createElement('classCode', Ccsd_Tools_String::getHalMetaTranslated($acm, 'en', '/', 'acm'));
    $kws->setAttribute('scheme', 'acm');
    $kws->setAttribute('n', $acm);
    $textClass->appendChild($kws);
}
// domain
foreach ( $this->getMeta('domain') as $domain ) {
    $d = $xml->createElement('classCode', Ccsd_Tools_String::getHalDomainTranslated($domain, 'en', '/'));
    $d->setAttribute('scheme', 'halDomain');
    $d->setAttribute('n', $domain);
    $textClass->appendChild($d);
}
// typdoc
$typdoc = $xml->createElement('classCode', $translator->translate('typdoc_'.$this->getTypDoc(), 'en'));
$typdoc->setAttribute('scheme', 'halTypology');
$typdoc->setAttribute('n', $this->getTypDoc());
$textClass->appendChild($typdoc);
$pd->appendChild($textClass);
// profileDesc>abstract
foreach ($this->getAbstract() as $l => $t) {
    if ( is_array($t) ) {
        $t = current($t);
    }
    $abs = $xml->createElement('abstract', $t);
    $abs->setAttribute('xml:lang', $l);
    $pd->appendChild($abs);
}
// profileDesc>particDesc>org
if ( is_array($this->getMeta('collaboration')) && count($this->getMeta('collaboration')) ) {
    $collaboration = $xml->createElement('particDesc');
    foreach ( $this->getMeta('collaboration') as $collab ) {
        $org = $xml->createElement('org', $collab);
        $org->setAttribute('type', 'consortium');
        $collaboration->appendChild($org);
    }
    $pd->appendChild($collaboration);
}
$b->appendChild($pd);
//
$lb->appendChild($b);
$body->appendChild($lb);
$text->appendChild($body);

///////////////
// text>back //
///////////////
$back = $xml->createElement('back');
$need_back = false;
// structures
if ( isset($structures) && is_array($structures) && count($structures) ) {
    $listOrg = $xml->createElement('listOrg');
    $listOrg->setAttribute('type', 'structures');
    $parents = array();
    foreach ( $structures as $sid ) {
        $struct = new Ccsd_Referentiels_Structure($sid);
        if ( $struct->getStructid() == 0 ) {
            continue;
        }
        $parents = array_merge($parents, $struct->getParentsStructids());
        $s = new Ccsd_DOMDocument('1.0', 'utf-8');
        $s->loadXML('<root>'.$struct->getXML(false).'</root>');
        foreach ( $s->getElementsByTagName('root')->item(0)->childNodes as $child ) {
            $listOrg->appendChild($xml->importNode($child, true));
        }
    }
    foreach ( array_unique(array_diff($parents, $structures)) as $sid ) {
        $struct = new Ccsd_Referentiels_Structure($sid);
        if ( $struct->getStructid() == 0 ) {
            continue;
        }
        $s = new Ccsd_DOMDocument('1.0', 'utf-8');
        $s->loadXML('<root>'.$struct->getXML(false).'</root>');
        foreach ( $s->getElementsByTagName('root')->item(0)->childNodes as $child ) {
            $listOrg->appendChild($xml->importNode($child, true));
        }
    }
    $back->appendChild($listOrg);
    $need_back = true;
}
// projets
if ( (isset($projanr) && is_array($projanr) && count($projanr)) || (isset($projeurope) && is_array($projeurope) && count($projeurope)) ) {
    $listOrg = $xml->createElement('listOrg');
    $listOrg->setAttribute('type', 'projects');
    if ( isset($projanr) && is_array($projanr) ) {
        foreach ( $projanr as $p ) {
            if ( $p instanceof Ccsd_Referentiels_Anrproject ) {
                $s = new Ccsd_DOMDocument('1.0', 'utf-8');
                $s->loadXML('<root>'.$p->getXML(false).'</root>');
                foreach ( $s->getElementsByTagName('root')->item(0)->childNodes as $child ) {
                    $listOrg->appendChild($xml->importNode($child, true));
                }
            }
        }
    }
    if ( isset($projeurope) && is_array($projeurope) ) {
        foreach ( $projeurope as $p ) {
            if ( $p instanceof Ccsd_Referentiels_Europeanproject ) {
                $s = new Ccsd_DOMDocument('1.0', 'utf-8');
                $s->loadXML('<root>'.$p->getXML(false).'</root>');
                foreach ( $s->getElementsByTagName('root')->item(0)->childNodes as $child ) {
                    $listOrg->appendChild($xml->importNode($child, true));
                }
            }
        }
    }
    $back->appendChild($listOrg);
    $need_back = true;
}
if ( $need_back ) {
    $text->appendChild($back);
}
//
$root->appendChild($text);
