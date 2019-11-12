<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 03/01/17
 * Time: 09:38
 */
class Hal_Document_Tei_Loader
{
    /** @var DOMXPath|null  */
    private $_xpath = null;

    /**
     * Hal_Document_Tei_Loader constructor.
     * @param DOMDocument $xml
     */
    public function __construct(DOMDocument $xml)
    {
        $this->_xpath = new DOMXPath($xml);
        $this->_xpath->registerNamespace('', "http://www.tei-c.org/ns/1.0");
        $this->_xpath->registerNamespace('tei', "http://www.tei-c.org/ns/1.0");
        $this->_xpath->registerNamespace('hal', "http://hal.archives-ouvertes.fr/");
        $this->_xpath->registerNamespace('xml', "http://www.w3.org/XML/1998/namespace");
    }

    /**
     * @param string $instance
     * @return array
     */
    public function loadMetas($instance = 'hal') {

        /**
         * ------------- METADONNEES ------------
         */
        $finalMetas = array();
        $finalMetas["typdoc"] = '';

        /**
         * title
         * subTitle
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:analytic/tei:title');
        /** @var DOMElement $entry */
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
                    $finalMetas['subTitle'][$lang] = $nodeValue;
                } else {
                    $finalMetas['title'][$lang] = $nodeValue;
                }
            }
        }

        /**
         * language
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:langUsage/tei:language');
        if ($entries->length == 1) {
            $language = $entries->item(0)->getAttribute('ident');
            if ($language != '') {
                $finalMetas['language'] = strtolower($language);
            }
        }

        /**
         * keyword
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:textClass/tei:keywords[@scheme="author"]/tei:term');
        /** @var DOMElement $entry */
        foreach($entries as $entry) {
            if ( $entry->nodeValue ) {
                $lang = $entry->getAttribute('xml:lang');
                $lang = ($lang == '' || $lang == "und") ? 'en' : $lang;
                $finalMetas['keyword'][$lang][] = $entry->nodeValue;
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
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:textClass/tei:classCode');
        /** @var DOMElement $entry */
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

                $finalMetas["typdoc"] = $n;
                continue;
            }
            //JB :ajout de la gestion de l'indexation controlée
            else if ($scheme == "VOCINRA"){
                $meta = 'inra_indexation_local';
                $n = $entry->getAttribute('n');
                if ($n !== ''){
                    $value = $n;
                }
            }
            if ($meta != '') {
                if ($multiple) {
                    if ( !isset($finalMetas[$meta]) || !is_array($finalMetas[$meta]) ) {
                        $finalMetas[$meta] = [];
                    }
                    if ( !in_array($value, $finalMetas[$meta]) ) {
                        $finalMetas[$meta][] = $value;
                    }
                } else {
                    $finalMetas[$meta] = $value;
                }
            }
        }

        /**
         * abstract
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:abstract');
        foreach($entries as $entry) {
            $lang = $entry->getAttribute('xml:lang');
            $lang = ($lang == '' || $lang == "und") ? 'en' : $lang;
            $finalMetas['abstract'][$lang] = $entry->nodeValue;
        }

        /**
         * collaboration
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:profileDesc/tei:particDesc/tei:org');
        foreach($entries as $entry) {
            $type = $entry->getAttribute('type');
            if ($type == 'consortium') {
                if (! isset($finalMetas['collaboration'])) {
                    $finalMetas['collaboration'] = [];
                }
                $finalMetas['collaboration'][] = $entry->nodeValue;
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
//$entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr[node() = "tei:idno" or "tei:title"]');
        $entries = $this->_xpath->query(
            '/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:title |
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
                if ($finalMetas["typdoc"] == 'COMM') {
                    $meta = 'source';
                }
            }
            if ($meta != '') {
                if ($multiple) {
                    $finalMetas[$meta][] = $value;
                } else {
                    $finalMetas[$meta] = $value;
                }
            }
        }
        # TODO: Au lieu d'aller dans Solr, aller directement dans la BD, on cherche un identifiant!!!
        #       Il faudrait mettre les VALID en premier pour systematiquement prendre un journal deja valide.

        if (! isset($finalMetas['journal']) && is_array($journal) && count($journal)) {
            $solrResult=[];
            if (isset($journal['ISSN']) && $journal['ISSN'] != '') {
                $solrResult = Ccsd_Referentiels_Journal::search('issn_s:' . $journal['ISSN'], 1);
            } else if (isset($journal['EISSN']) && $journal['EISSN'] != '') {
                $solrResult = Ccsd_Referentiels_Journal::search('eissn_s:'.$journal['EISSN'], 1);
            }
            if (isset($solrResult[0]['docid'])) {
                $finalMetas['journal'] = new Ccsd_Referentiels_Journal($solrResult[0]['docid']);
            } else if ($dataJournal) {
                $finalMetas['journal'] = new Ccsd_Referentiels_Journal(0, $journal);
            }
        }

        /**
         * conferenceTitle
         * conferenceStartDate
         * conferenceEndDate
         * city
         * country
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:meeting');
        if ($entries->length == 1) {
            $entry = $entries->item(0);
            if ($entry->hasChildNodes()) {
                /** @var DOMElement $node */
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
                        if ( ! in_array($finalMetas["typdoc"], array('PATENT', 'IMG', 'MAP', 'LECTURE'))) {
                            $meta = 'city';
                        }
                    } else if ($node->nodeName == 'country') {
                        $country = strtolower($node->getAttribute('key'));
                        $country = ($country == '') ? 'en' : $country;
                        if (! in_array($finalMetas["typdoc"], array('PATENT', 'IMG', 'MAP', 'LECTURE'))) {
                            $meta = 'country';
                            $value = $country;
                        }
                    }
                    if ($meta != '') {
                        $finalMetas[$meta] = $value;
                    }
                }
            }
        }


        /**
         * conferenceOrganizer
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:respStmt/tei:name');
        foreach($entries as $entry) {
            $finalMetas['conferenceOrganizer'][] = $entry->nodeValue;
        }

        /**
         * scientificEditor
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:editor');
        foreach($entries as $entry) {
            $finalMetas['scientificEditor'][] = $entry->nodeValue;
        }

        /**
         * city
         * country
         */
        if (in_array($finalMetas["typdoc"], array('PATENT', 'IMG', 'MAP', 'LECTURE'))) {
            $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:settlement');
            if ($entries->length == 1) {
                $finalMetas['city'] = $entries->item(0)->nodeValue;
            }

            $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:country');
            if ($entries->length == 1) {
                $finalMetas['country'] = strtolower($entries->item(0)->getAttribute('key'));
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
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:imprint');
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
                            foreach (['precision' => ['value' => 'unknown', 'meta' => 'circa'], 'subtype' => ['value' => 'inPress', 'meta' => 'inPress']] as $attr => $content) {
                                if ( $node->getAttribute($attr) == $content['value'] ) {
                                    $finalMetas[$content['meta']] = '1';
                                }
                            }
                        } else if ($type == 'dateDefended') {
                            if (in_array($finalMetas["typdoc"], array('THESE', 'HDR', 'MEM', 'ETABTHESE'))) {
                                $meta = 'date';
                            }
                        } else if ($type == 'dateEpub') {
                            $meta = 'edate';
                        }
                    }
                    if ($meta != '') {
                        if ($multiple) {
                            $finalMetas[$meta][] = $value;
                        } else {
                            $finalMetas[$meta] = $value;
                        }
                    }
                }
            }
        }
        /**
         * seriesEditor
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:series/tei:editor');
        foreach($entries as $entry) {
            $finalMetas['seriesEditor'] = $entry->nodeValue;
        }

        /**
         * scientificEditor
         * authorityInstitution
         * director
         * supervisorEmail
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:monogr/tei:authority');
        foreach($entries as $entry) {
            $type = $entry->getAttribute('type');
            if ($type == 'institution') {
                $finalMetas['authorityInstitution'][] = $entry->nodeValue;
            } else if ($type == 'school') {
                $finalMetas['thesisSchool'][] = $entry->nodeValue;
            } else if ($type == 'supervisor') {
                $finalMetas['director'][] = $entry->nodeValue;
            } else if ($type == 'jury') {
                $finalMetas['committee'][] = $entry->nodeValue;
            } else if ($type == 'supervisorEmail') {
                $finalMetas[$instance.'_directorEmail'] = $entry->nodeValue;
            }
        }

        /**
         * identifier (doi, arxiv, ...)
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:idno');
        foreach($entries as $entry) {
            $type = strtolower($entry->getAttribute('type'));
            if ( in_array($type, Hal_Document::$_serverCopy) ) {
                $finalMetas['identifier'][$type] = trim($entry->nodeValue);
            }
        }

        /**
         * seeAlso
         * publisherLink
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:ref');
        foreach($entries as $entry) {
            $type = $entry->getAttribute('type');
            $value = trim($entry->nodeValue);
            if ($type == 'seeAlso') {
                if ($finalMetas["typdoc"] == 'SOFTWARE') {
                    $finalMetas['codeRepository'] = $value;
                } else {
                    $finalMetas['seeAlso'][] = $value;
                }
            } else if ($type == 'publisher') {
                $finalMetas['publisherLink'] = $value;
            }
        }
        /**
         * funding
         * projanr
         * projeurop
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:titleStmt/tei:funder');
        foreach($entries as $entry) {
            $ref = $entry->getAttribute('ref');
            if ($ref == '') {
                $finalMetas['funding'][] = $entry->nodeValue;
            } else {
                $ref = str_replace('#', '', $ref);
                if (strpos($ref, 'projanr') !== false) {
                    $finalMetas['anrProject'][] = new Ccsd_Referentiels_Anrproject(str_replace('projanr-', '', $ref));
                } else if ( strpos($ref, 'localProjanr') !== false) {
                    $orgs = $this->_xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg[@type="projects"]/tei:org[@xml:id="' . $ref . '"]');
                    if ($orgs->length == 1) {
                        $org = $orgs->item(0);
                        $data = array();
                        if ($org->hasChildNodes()) {
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
                        $finalMetas['anrProject'][] = new Ccsd_Referentiels_Anrproject(0, $data);
                    }
                } else if (strpos($ref, 'projeurop') !== false) {
                    // Si Id HAL indique, on prends par l'Id.
                    $finalMetas['europeanProject'][] = new Ccsd_Referentiels_Europeanproject(str_replace('projeurop-', '', $ref));
                } else if (strpos($ref, 'localProjeurop') !== false) {
                    // Sinon, on cherche
                    $orgs = $this->_xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg[@type="projects"]/tei:org[@xml:id="' . $ref . '"]');
                    if ($orgs->length == 1) {
                        $org = $orgs->item(0);
                        $data = array();
                        if ($org->hasChildNodes()) {
                            foreach($org->childNodes as $node) {
                                // TODO: remplacer par un switch
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
                        $finalMetas['europeanProject'][] = new Ccsd_Referentiels_Europeanproject(0, $data);
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
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:notesStmt/tei:note');
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
            } if ($type == 'other') {
                $meta = 'otherType';
            } else if ($type == 'image') {
                $meta = 'imageType';
            } else if ($type == 'lecture') {
                $meta = 'lectureType';
            } else if ($type == 'degree') {
                if ($instance == 'univ-lorraine') {
                    //Cas particulier, le "-" n'est pas accepté dans le nom d'un element dde formulaire
                    $meta = 'dumas_degreeType';
                } else {
                    $meta = $instance.'_degreeType';
                }
            } else if ( $type == 'pastel_thematique' ) {
                $meta = 'pastel_thematique';
                $multiple = true;
            } else if ( $type == 'pastel_library' ) {
                $meta = 'pastel_library';
                $multiple = true;
            } else if ( $type == 'programmingLanguage' ) {
                $meta = 'programmingLanguage';
                $multiple = true;
                $value = $entry->nodeValue;
            } else if ( $type == 'platform' ) {
                $meta = 'platform';
                $multiple = true;
                $value = $entry->nodeValue;
            } else if ( $type == 'version' ) {
                $meta = 'version';
                $value = $entry->nodeValue;
            }
            if ($meta != '') {
                if ( $multiple ) {
                    $finalMetas[$meta][] = $value;
                } else {
                    $finalMetas[$meta] = $value;
                }
            }
        }

        /**
         * writingDate
         *
         */

        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:editionStmt/tei:edition/tei:date[@type="whenWritten"]');
        if ($entries->length == 1) {
            $entry = $entries->item(0);
            $finalMetas['writingDate'] = $entry->nodeValue;
        }

        /**
         * licence
         * softwareLicence
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:publicationStmt/tei:availability/tei:licence');
        if ($entries->length > 0 ) {
            if ($finalMetas["typdoc"] == 'SOFTWARE') {
                $referencial = new Thesaurus_Spdx();
                foreach($entries as $entry) {
                    $url = $entry->getAttribute('target');
                    $licence = $referencial->getLicence($url);
                    if ($url == '' || !$licence) {
                        $licence = $entry->nodeValue;
                    }
                    $finalMetas['softwareLicence'][] = $licence;
                }
            } else {
                $licence = $entries->item(0)->getAttribute('target');
                if ( in_array($licence, Hal_Settings::getKnownLicences()) ) {
                    $finalMetas['licence'] = $licence;
                }
            }
        }

        /** Begin ZMO - To get the geographic coordinates of image docs
         *  listPlace
         *  place
         *  placeName
         *  location
         *  geo
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:listPlace/tei:place/tei:location/tei:geo');
        foreach($entries as $entry) {
            if ($entry->nodeValue) {
                $geoLocalisation = preg_split('/(\s|&|,)/', $entry->nodeValue);
                $finalMetas['latitude'] = $geoLocalisation[0];
                $finalMetas['longitude'] = $geoLocalisation[1];
            }
        }

        /** Begin ZMO - To get the duration of video or audio docs
         *  recordingStmt
         *  recording
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:recordingStmt/tei:recording');
        foreach ($entries as $entry) {
            try {
                $interval = new DateInterval($entry->getAttribute('dur'));
                $finalMetas['duration'] = $interval->format('%h:%i:%s');
            } catch (Exception $e) {
                continue;
            }
        }

        /** Begin ZMO - To get the local meta for portal
         *  fs
         *  f
         *  string
         */
        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:editionStmt/tei:edition/tei:fs/tei:f');
        foreach ($entries as $entry) {
            /** @var DOMElement $entry  */
            if ($entry->hasChildNodes()) {
                foreach($entry->childNodes as $node) {
                    $metasList = Hal_Referentiels_Metadata::metaList();
                    $metaName =  $entry->getAttribute('name');
                    if (strtolower($metaName) == 'univlorraine_urlfulltext') {
                        //todo peut être supprimé apres l'import : Evite de retoucher tous les fichiers XML pour l'import de l'université de Lorraine
                        $metaName = 'univLorraine_urlFulltext';
                    }

                    // Meta with id and text
                    if (in_array($metaName, $metasList)) {
                        // if numeric, we get the value
                        if ($node->nodeName == 'numeric') {
                            $finalMetas[$metaName] = $node->nodeValue;
                        }
                        // Meta based free text
                    } else {
                        $finalMetas[$metaName] = $node->nodeValue;
                    }
                }
            }
        }
        /** End ZMO */

        return $finalMetas;
    }

    /**
     * @return array
     */
    public function loadAuthorsAndStructures()
    {

        /**
         * ------------- AUTEURS / STRUCTURES ------------
         */
        $finalAuthors = array();
        $structArray = array();

        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:analytic/tei:author');
        /** @var DOMElement $entry */
        foreach($entries as $entry) {
            /**
             * Récupération des infos auteurs
             */
            $author = new Hal_Document_Author();
            $author->setQuality($entry->getAttribute('role'));

            $loaded = false;
            $idnos = $this->_xpath->query('tei:idno', $entry);
            /** @var DOMElement $idno */
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
                    /** @var DOMElement $node */
                    foreach($entry->childNodes as $node) {
                        if ($node->nodeName == 'persName' && $node->hasChildNodes()) {
                            /** @var DOMElement $snode */
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
            $affiliations = $this->_xpath->query('tei:affiliation', $entry);
            /** @var DOMElement $affiliation */
            foreach($affiliations as $affiliation) {
                $structidx = false;
                $ref = str_replace('#', '', $affiliation->getAttribute('ref'));
                if (strpos($ref, 'struct-') !== false) {
                    $structure = new Hal_Document_Structure((int)str_replace('struct-', '', $ref));
                    if ($structure->getStructid() ==0) {
                        // La structure indiquee ne corresponds a rien!
                        throw new Exception(Ccsd_Tools::translate("$ref ne corresponds pas a une structure HAL valide"));
                    }
                    $structArray[] = $structure;
                    $structidx = count($structArray)-1;
                } else if (strpos($ref, 'localStruct-') !== false) {
                    $structure = $this->_xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg/tei:org[@xml:id="' . $ref . '"]');
                    if ($structure->length == 1) {
                        $structure = $this->createStructure($structure->item(0), $this->_xpath);
                        $structArray[] = $structure;
                        $structidx = count($structArray) - 1;
                    } else {
                        // Ne devrait pas etre des Panic, juste warning
                        Ccsd_Tools::panicMsg(__FILE__, __LINE__, "To much org element with id: $ref");
                    }
                } else {
                    // Ne devrait pas etre des Panic, juste warning
                    Ccsd_Tools::panicMsg(__FILE__, __LINE__, "ref attribute for affiliation element is badly formatted: $ref");
                }
                if ($structidx !== false) {
                    $author->addStructidx($structidx);
                }
            }
            $finalAuthors[] = $author;
        }

        return ['authors' => $finalAuthors, 'structures' => $structArray];
    }

    /**
     * @return array
     */
    public function loadRessources()
    {
        /**
         * ------------- RESSOURCES LIEES ------------
         */
        $related = array();

        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:sourceDesc/tei:biblStruct/tei:relatedItem');
        /** @var DOMElement $entry */
        foreach($entries as $entry) {
            $target = $entry->getAttribute('target');
            $type = $entry->getAttribute('type');
            if ($target != '' && $type != '') {
                $related[] = array('URI'=>basename($target), 'RELATION'=>$type, 'INFO'=>$entry->nodeValue);
            }
        }
        return $related;
    }

    /**
     * @param $contributor
     * @return Hal_Site_Collection []
     * @throws Hal_Site_Exception
     */
    public function loadCollections($contributor)
    {
        /**
         * ------------- COLLECTIONS ------------
         */
        $collections = array();

        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:seriesStmt/tei:idno');

        foreach($entries as $entry) {
            /** @var DOMElement $entry */
            $type = $entry->getAttribute('type');
            $tampid = $entry->getAttribute('n');
            if ($type == 'stamp' && $tampid != '') {

                $collection = Hal_Site::exist($tampid, Hal_Site::TYPE_COLLECTION, true);
                /** @var Hal_Site_Collection $collection */
                if ($collection === false) {
                    throw new Hal_Site_Exception("Test collection $tampid not found");
                }
                if ($collection->getSid() != 0 && $collection->isTamponneur($contributor)) {
                    $collections[] = $collection;
                }
            }
        }

        return $collections;
    }


    /**
     * @param null $pathImport
     * @return Hal_Document_File[]
     */
    public function loadFiles($pathImport = null)
    {
        /**
         * ------------- FICHIERS ------------
         */

        $files = array();

        $entries = $this->_xpath->query('/tei:TEI/tei:text/tei:body/tei:listBibl/tei:biblFull/tei:editionStmt/tei:edition/tei:ref');
        foreach($entries as $entry) {
            /** @var DOMElement $entry */
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

            $descriptions = $this->_xpath->query('tei:desc', $entry);
            if ($descriptions->length == 1) {
                $description = $descriptions->item(0);
                $file->setComment($description->nodeValue);
            }

            $dates = $this->_xpath->query('tei:date', $entry);
            if ($dates->length == 1) {
                /** @var DOMElement $dateItem */
                $dateItem = $dates->item(0);
                $date = $dateItem->getAttribute('notBefore');
            } else {
                $date = date("Y-m-d");
            }
            $file->setDateVisible($date);
            $files[] = $file;
        }

        return $files;
    }

    /**
     * ------------- UTILE ------------
     */

    /**
     * Methode permettant de récupérer une structure
     *
     * @param DOMElement $node
     * @param DOMXPath $xpath
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
            /** @var DOMElement $item */
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
                        /** @var DOMElement $countryItem */
                        $countryItem = $addrCountry->item(0);
                        $structure->setPaysid(strtolower($countryItem->getAttribute('key')));
                    }
                    if (isset($item->ref)) {
                        $structure->setUrl($item->ref->nodeValue);
                    }
                } else if ($item->nodeName == 'listRelation') {
                    $relations = $xpath->query('tei:relation', $item);
                    /** @var DOMElement $relation */
                    foreach($relations as $relation) {
                        $name = $relation->getAttribute('name');
                        $active = $relation->getAttribute('active');
                        $active = str_replace('#', '', $active);
                        if (strpos($active, 'struct-') !== false) {
                            $structure->addParent(new Ccsd_Referentiels_Structure(str_replace('struct-', '', $active)), $name);
                        } else if (strpos($active, 'localStruct-') !== false) {
                            $structures = $xpath->query('/tei:TEI/tei:text/tei:back/tei:listOrg/tei:org[@xml:id="' . $active . '"]');
                            if ($structures->length == 1) {
                                /** @var DOMElement $strucItem */
                                $strucItem = $structures->item(0);
                                $structure->addParent($this->createStructure($strucItem, $xpath), $name);
                            }
                        }
                    }
                }
            }
        }
        return $structure;
    }
}
