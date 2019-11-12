<?php

$doctype = DOMImplementation::createDocumentType(
    "article",
    "-//NLM//DTD Journal Publishing DTD v2.3 20070328//EN",
    "http://dtd.nlm.nih.gov/publishing/2.3/journalpublishing.dtd"
);

$xml = DOMImplementation::createDocument(null, 'article', $doctype);

$root = $xml->createElement('front');

// JOURNAL
$journal = $xml->createElement('journal-meta');

if ( ( $oJ = $this->getMeta('journal') ) instanceof Ccsd_Referentiels_Journal ) {
    if ($oJ->SHORTNAME ) {
        $shortTitle = $xml->createElement('journal-id', $oJ->SHORTNAME);
        $shortTitle->setAttribute('type', 'nml-ta');
        $journal->appendChild($shortTitle);
    }
    
    $title = $xml->createElement('journal-title', $oJ->JNAME);
    $journal->appendChild($title);
    
    if ($oJ->ISSN ) {
        $issn = $xml->createElement('issn',  $oJ->ISSN);
        $issn->setAttribute('pub-type', 'ppub');
        $journal->appendChild($issn);
    }    
    
    if ($oJ->EISSN ) {
        $eissn = $xml->createElement('eissn', $oJ->EISSN);
        $eissn->setAttribute('pub-type', 'epub');
        $journal->appendChild($eissn);
    }    
}
$root->appendChild($journal);

//IDENTIFIANT HAL
$article = $xml->createElement('article-meta');

$articleId = $xml->createElement('article-id', $this->_identifiant);
$articleId = $xml->setAttribute('pub-id-type', "manuscript");
$article->appendChild($articleId);

//identifiant autre
foreach ($this->getMeta('identifier') as $code => $identifiant) {
    if ( $code == 'pubmed') {
        $articleId = $xml->createElement('article-id', $identifiant);
        $articleId = $xml->setAttribute('pub-id-type', "pmid");
        $article->appendChild($articleId);
        continue;
    }
    if ( $code == 'doi') {         
        $articleId = $xml->createElement('article-id', $identifiant);
        $articleId = $xml->setAttribute('pub-id-type', "doi");
        $article->appendChild($articleId);
    } 
}

$root->appendChild($article);

//CATEGORIE
$categorie = $xml->createElement('article-categories');

$subGroup = $xml->createElement();
$subGroup->setAttribute('subj-group-type', "heading");

$subject = $xml->createElement('subject', "Article");
$subGroup->appendChild($subject);

$sousSubGroup = $xml->createElement();
$sousSubGroup->setAttribute('subj-group-type', "subrepository");

$subject = $xml->createElement('subject', "Inserm subrepository");
$sousSubGroup->appendChild($subject);

$subGroup->appendChild($sousSubGroup);

$categorie->appendChild($subGroup);

$root->appendChild($categorie);

//TITRE
$titreGroup = $xml->createElement('title-group');

foreach ($this->getTitle() as $lang => $titre) {
    if ( $lang == "en" ) {
        $titreArticle = $xml->createElement('article-title',$titre);
        break;
    }    
}

$titreGroup->appendChild($titreArticle);
$root->appendChild($titreGroup);


//AUTEUR - AFFILIATION
$contribGroup = $xml->createElement('contrib-group');
$correspondingAuteur = false;
$tableauGlobalAffiliations = array();

foreach ($this->_authors as $author) {
    
    $contrib = $xml->createElement('contrib');
    $contrib->setAttribute('contrib-type', "author");
    
    $name = $xml->createElement('name');    
    $surname = $xml->createElement('surname',$author->getLastname());
    $name->appendChild($surname);   
    $givenNames = $xml->createElement('given-names', $author->getFirstname());
    $name->appendChild($givenNames);
    
    $contrib->appendChild($name);
    

    foreach ($author->getStructid() as $id) {
        $xref = $xml->createElement('xref');
        $xref->setAttribute('ref-type', "aff");
        if ( ($indiceAffiliationCourante = array_search($id, $tableauGlobalAffiliations)) !== false ) {
             $tableauGlobalAffiliations[] = $id;
             $xref->setAttribute('rid', "A" . count($tableauGlobalAffiliations) + 1 );
        } else {
            $xref->setAttribute('rid', "A" . $indiceAffiliationCourante + 1 );
        }
        $contrib->appendChild($xref);
    }    
    
    if (! $correspondingAuteur ) {
        if ($author->author->getQuality() == 'crp') {
            $correspondingAuteur = true;
            $xref = $xml->createElement('xref', "*");
            $xref->setAttribute('rid', "FN1");
            $xref->setAttribute('ref-type', "author-notes");   
            $contrib->appendChild($xref);
            $noteCorrespondingAuteur = "* Correspondence should be addressed to ". $author->getFirstname() . " " .  $author->getLastname();
            $emailCorrespondingAuteur = $author->getEmail();
        } 
    }       
    
    $contribGroup->appendChild($contrib);   
    $root->appendChild($contribGroup);
    
    $indice = 1;
    foreach($tableauGlobalAffiliations as $iDaffiliation) {
        
        $structureCourante = new Ccsd_Referentiels_Structure($iDaffiliation);
        $affiliation = $xml->createElement('aff', "NOM COMPLET");
        $affiliation->setAttribute('id', "A" . $indice);
    
        $label = $xml->createElement('label', $indice);
        $affiliation->appendChild($label);
    
        $parents = $structureCourante->getAllParents();
        foreach ($parents as $affiliationParent) {
            if ($affiliationParent['struct']->getTypestruct() == Ccsd_Referentiels_Structure::TYPE_INSTITUTION ) {
                $institution = $xml->createElement('institution', $affiliationParent['struct']->getStructname());
                $affiliation->appendChild($institution);
            }    
        }
    
        $adresse = $xml->createElement('addr-line', $structureCourante->getAddress());
        $affiliation->appendChild($adresse);
    
        $root->appendChild($affiliation);
    }        
}
if ( $correspondingAuteur ) {
    $authorNotes = $xml->createElement('author-notes');
    
    $correspondantAuthor = $xml->createElement('corresp', $noteCorrespondingAuteur);
    $correspondantAuthor->setAttribute('id', "FN1");
    
    $authorNotes->appendChild($correspondantAuthor);
    
    $mail = $xml->createElement('mail', $emailCorrespondingAuteur);
    
    $authorNotes->appendChild($mail);

    $root = $xml->appendChild($authorNotes);
}    

//DATE

if ( $this->getMeta('date') != '' ) {
    $publicationDate = $xml->createElement('pub-date');
    $publicationDate = $xml->setAttribute('pub-type', "ppub");
    
    $tabDate = explode('-', $this->getMeta('date'));
    switch ( count($tabDate) ) {
        case 3 :
            $day = $xml->createElement('day', $tabDate[2]);
            $publicationDate->appendChild(str_pad($day, 2, "0", STR_PAD_LEFT));
        case 2 :   
            $month = $xml->createElement('month', $tabDate[1]);
            $publicationDate->appendChild(str_pad($jour, 2, "0", STR_PAD_LEFT));
        case 1 :
            $annee = $xml->createElement('year', $tabDate[0]);
            $publicationDate->appendChild($annee);
        break;    
    }
    $publicationDate->appendChild($root);
}

if ( $this->getMeta('edate') != '' ) {
    $publicationDate = $xml->createElement('pub-date');
    $publicationDate = $xml->setAttribute('pub-type', "epub");
    
    $tabDate = explode('-', $this->getMeta('edate'));
    switch ( count($tabDate) ) {
        case 3 :
            $day = $xml->createElement('day', $tabDate[2]);
            $publicationDate->appendChild(str_pad($day, 2, "0", STR_PAD_LEFT));
        case 2 :   
            $month = $xml->createElement('month', $tabDate[1]);
            $publicationDate->appendChild(str_pad($jour, 2, "0", STR_PAD_LEFT));
        case 1 :
            $annee = $xml->createElement('year', $tabDate[0]);
            $publicationDate->appendChild($annee);
        break;    
    }       
    $root->appendChild($publicationDate);
}
//INFO PUBLICATION

if ( $this->getMeta('volume') != '' ) {
    $volume = $xml->createElement('volume', $this->getMeta('volume'));
    $root->appendChild($volume);
}    

if ( $this->getMeta('issue') != '' ) {
    $issue = $xml->createElement('issue', $this->getMeta('issue'));
    $root->appendChild($issue);
}    

if ( $this->getMeta('page') != '' ) {
    $pagination = $xml->createElement('pagination', $this->getMeta('page'));
    $root->appendChild($pagination);
}


$abstract = $xml->createElement('abstract');

$paragraph = $xml->createElement('p', RESUME);
$paragraph = $xml->setAttribute('id', "P1");

$abstract->appendChild($paragraph);

$root->appendChild($abstract);

if ( count($kwls = $this->getKeywords()) ) {
    $kwdGroup = $xml->createElement('kwd-group');
    $kwdGroup->setAttribute('kwd-group-type', "Author");    
    foreach ( $kwls as $lang=>$keywords ) {
        if ($lang == "en") {
            if ( is_array($keywords) ) {
                foreach ( $keywords as $keyword ) {
                    $kw = $xml->createElement('kwd', $keyword);
                    $kwdGroup->appendChild($kw);
                }
            } else {
                $kw = $xml->createElement('kwd', $keywords);
                $kwdGroup->appendChild($kw);
            }
            break;
        }
    }
    $root->appendChild($kwdGroup);
}

$kwdGroup = $xml->createElement('kwd-group');
$kwdGroup->setAttribute('kwd-group-type', "MESH");

$vide = true;
foreach ( $this->getMeta('mesh') as $mesh ) {
    $vide = false;
    $kwd = $xml->createElement('kwd', $mesh);
    $kwdGroup->appendChild($kwd);
}
if ( ! $vide ) {
    $root->appendChild($kwdGroup);
}    

$root->appendChild($article);
