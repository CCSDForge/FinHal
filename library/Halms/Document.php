<?php

class Halms_Document
{
    /**
     * Statut des papiers
     */
    const STATUS_INITIAL = 0; //Initial - entré dans halms
    const STATUS_INITIAL_BLOCKED = 1; // bloqué - editeur refusant transfert PMC
    const STATUS_INITIAL_EMBARGO = 2; // soumis délai embargo
    const STATUS_INITIAL_AHEADOFPRINT = 6; // admin ne souhaite pas transférer
    const STATUS_INITIAL_UNKNOWN = 3; // admin ne souhaite pas transférer
    const STATUS_INITIAL_READY = 4; // Pret transfert DCL
    const STATUS_WAIT_FOR_DCL = 5; // document transféré sur DCL
    const STATUS_XML_QA = 7; // Retour de DCL
    const STATUS_XML_CONTROLLED = 8; // Retour de DCL controlé
    const STATUS_XML_ERROR_REPORTED_AUTHOR = 10; //Erreur remontée par l'auteur
    const STATUS_XML_FINISHED = 11; //Dépôt pret pour PMC
    const STATUS_WAIT_FOR_PMC = 12; //En attente de retour de PMC
    const STATUS_PMC_ONLINE= 13; //En ligne sur PMC

    const ACTION_ADDFILES = 90; // Ajout de fichiers
    const ACTION_DELFILES = 91; // Suppression de fichiers

    /**
     * Tables HALMS
     */
    const TABLE_HALMS = "DOC_HALMS";
    const TABLE_HISTORY = "DOC_HALMS_HISTORY";

    const HALMS_DIR = "/sites/halms/";
    const HALMS_USERNAME = 'HalMS Admin';
    const HALMS_MAIL = "halms@ccsd.cnrs.fr";

    /**
     * Infos connexion serveur DCL
     */
    const DCL_SERVER = "ftp.dclab.com";
    const DCL_PORT = "22";
    const DCL_LOGIN = "inserm";
    const DCL_PWD = "9Jnb4yzJ";
    const DCL_USERNAME = "DCL";
    const DCL_MAILS = 'response@dclab.com,dclinserm@dclab.com';
    const DCL_DIR = "from_dcl";

    const PMC_HOST = 'ftp-private.ncbi.nlm.nih.gov';
    const PMC_MAIL = 'yannick.barborini@ccsd.cnrs.fr';//'pmc@ncbi.nlm.nih.gov';
    const PMC_USERNAME = 'PMC';
    const PMC_USER = 'inserm';
    const PMC_PWD = 'by7UWOIa';


    protected $_docid = 0;

    protected $_status = null;

    protected $_pathdoc = '';

    protected $_pathhalms = '';

    protected $_pathdcl = '';

    protected $_pathpmc = '';

    protected $_sftp = null;

    protected $_connexion = null;


    public function __construct($docid = 0, $load = true)
    {
        $this->_docid = $docid;

        $this->_pathdoc = PATHDOCS . wordwrap(sprintf("%08d", $this->_docid), 2, DIRECTORY_SEPARATOR, 1) . DIRECTORY_SEPARATOR;

        $this->_pathhalms = $this->_pathdoc . 'halms' . DIRECTORY_SEPARATOR;

        $this->_pathdcl = $this->_pathdoc . 'dcl' . DIRECTORY_SEPARATOR;

        $this->_pathpmc = $this->_pathdoc . 'pmc' . DIRECTORY_SEPARATOR;

        if ($load) {
            $this->load();
        }
    }

    public function load()
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE_HALMS, 'DOCSTATUS')->where('DOCID = ?', $this->_docid);
        $this->_status = $db->fetchOne($sql);
    }

    public function getDocid()
    {
        return $this->_docid;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getHalmsPath()
    {
        return $this->_pathhalms;
    }

    public function getDclPath()
    {
        return $this->_pathdcl;
    }


    public function changeStatus($newStatus)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $res = $db->update(self::TABLE_HALMS, ['DOCSTATUS' =>  $newStatus], 'DOCID = ' .  $this->_docid);
        $this->_status = $newStatus;
        return $res;
    }

    /**
     * Copie les fichier dans un répertoire pour l'envoi sur DCL
     */
    public function copyFiles($docid)
    {
         if (! is_dir($this->_pathhalms)) {
            //On créé le repertoire
            mkdir($this->_pathhalms);
        } else {
            //On vide le repertoire
            foreach($this->listDir($this->_pathhalms) as $file) {
                @unlink($this->_pathhalms . $file);
            }
        }

        $document = Hal_Document::find($docid);

        //Enregistrement du fichier de méta
        $res = $this->createXmlFile($document);

        $idManuscrit = $idAnnex = 1;
        foreach($document->getFiles() as $file) {
            if ($file->getDefault()) {
                $res = copy($file->getPath(), $this->_pathhalms . 'halms' . $docid . '.pdf') && $res;
            } else if ($file->getType() == Hal_Settings::FILE_TYPE_SOURCES){
                $res = copy($file->getPath(), $this->_pathhalms . 'halms' . $docid . '-manuscrit_' . $idManuscrit . '.' . $file->getExtension()) && $res;
                $idManuscrit++;
            } else {
                $res = copy($file->getPath(), $this->_pathhalms . 'halms' . $docid . '-supplement_' . $idAnnex . '.' . $file->getExtension()) && $res;
                $idAnnex++;
            }
        }
        return $res;
    }

    public function createXmlFile(Hal_Document $document)
    {
        $xmlcontent = $this->createMetaXml($document);
        if (! $xmlcontent) {
            return false;
        }
        return file_put_contents($this->_pathhalms . 'halms' . $document->getDocid() . '.xml', $xmlcontent);
    }

    /**
     * Liste les fichiers du repertoire halms
     * @param $dir
     * @return array
     */
    public function listDir($dir)
    {
        $files = array_diff(scandir($dir), array('..', '.'));
        rsort($files);
        return $files;
    }

    public function listDclDir()
    {
        return $this->listDir($this->_pathdcl);
    }


    public function createManifest($return = 'str')
    {
        $manifest = [
            'Meta'  =>  '',
            'PDF'  =>  '',
            'Manuscrit'  =>  [],
            'Annex'  =>  [],
        ];

        foreach ($this->listDir($this->_pathhalms) as $file)
        {
            if ($file == 'halms' . $this->_docid . '.xml') {
                $manifest['Meta'] = $file;
            } elseif ($file == 'halms' . $this->_docid . '.pdf') {
                $manifest['PDF'] = $file;
            } elseif (preg_match('/halms[0-9]+\-supplement_([0-9]+)/', $file, $matches)) {
                $manifest['Annex'][$matches[1]] = $file;
            } elseif (preg_match('/halms[0-9]+\-manuscrit_([0-9]+)/', $file, $matches)) {
                $manifest['Manuscrit'][$matches[1]] = $file;
            } else {
                $manifest['Manuscrit'][] = $file;
            }
        }

        ksort($manifest['Manuscrit']);
        ksort($manifest['Annex']);


        if ($return == 'array') {
            return $manifest;
        }

        $manifestTxt = '';
        foreach ($manifest as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $manifestTxt .= $key . "\t" . $k . "\t" . $v . "\n";
                }
            } else {
                $manifestTxt .= $key . "\t" . $value . "\n";
            }
        }
        return $manifestTxt;

    }

    public function delFile($filename)
    {
        return unlink($this->_pathhalms . $filename);
    }

    public function createZip()
    {
        $res = true;
        //Création de l'archive ZIP
        $zip = new ZipArchive();

        $zipName = $this->_pathdoc . "halms" .$this->_docid . ".zip";

        if (is_file($zipName)) {
            @unlink($zipName);
        }

        if ($zip->open($zipName, ZipArchive::CREATE) !== TRUE) {
            return false;
        }
        $res = $zip->addFromString('manifest.txt', $this->createManifest()) && $res;
        foreach($this->listDir($this->_pathhalms) as $file) {
            $zip->addFile($this->_pathhalms . $file, $file) && $res;
        }
        return $res;
    }

    public function createPmcZip()
    {
        $res = true;
        if (! is_dir($this->_pathpmc)) {
            mkdir ($this->_pathpmc);
        }

        //Création de l'archive ZIP
        $zip = new ZipArchive();

        $zipName = $this->_pathpmc . "halms" .$this->_docid . ".zip";

        if (is_file($zipName)) {
            @unlink($zipName);
        }

        if ($zip->open($zipName, ZipArchive::CREATE) !== TRUE) {
            return false;
        }

        foreach($this->listDir($this->_pathdcl) as $file) {
            $pathParts = pathinfo($this->_pathdcl . $file);
            if (isset($pathParts['extension']) && in_array($pathParts['extension'], ['txt', 'xml', 'pdf', 'gif', 'jpg', 'tif'])) {
                if ($file == "halms" .$this->_docid . "_edited.pdf") {
                    continue;
                } else if ($pathParts['extension'] == 'xml' && $file != "halms" .$this->_docid . ".xml") {
                    continue;
                }
                $zip->addFile($this->_pathdcl . $file, $file) && $res;
            }
        }
        $zip->close();
        return $res;
    }

    static public function initSFTP($server = self::DCL_SERVER, $port = self::DCL_PORT, $login = self::DCL_LOGIN, $pwd = self::DCL_PWD)
    {
        if (($connexion = ssh2_connect($server, $port)) === false) {
            // connexion serveur echouee
            return false;
        }
        if (ssh2_auth_password($connexion, $login, $pwd) === false) {
            // authentification echouee
            return false;
        }
        return ssh2_sftp($connexion);
    }

    static public function listDCL()
    {
        $sftp = self::initSFTP();
        if ($sftp === false) {
            return ['result' => false, 'msg' => "Probleme de connexion sur le serveur DCL fin de la procedure"];
        }
        try {
            $files = scandir("ssh2.sftp://" . intval($sftp) . '/' . self::DCL_DIR. '/');
        } catch ( Exception $e ) {
            return ['result' => false, 'msg' => $e->getMessage ()];
        }
        return ['result' => true, 'files' => $files];
    }

    public function uploadDCL()
    {
        if (APPLICATION_ENV == ENV_DEV) {
            //Evite d'installer ssh2
            return ['result' => true, 'msg' => "Envoi du package " . $this->_pathdoc . "halms" . $this->_docid . ".zip"];
        }

        if ($this->_sftp == null) {
            $this->_sftp = self::initSFTP();
            if ($this->_sftp === false) {
                return ['result' => false, 'msg' => "Probleme de connexion sur le serveur DCL fin de la procedure"];
            }
        }

        $zipLocal = $this->_pathdoc . "halms" . $this->_docid . ".zip";
        $zipDestination = "halms" . $this->_docid . ".zip";

        try {
            $flux = fopen ( "ssh2.sftp://" . intval($this->_sftp) . '/' . $zipDestination, 'w' );
            if (! $flux)
                throw new Exception ( "Probleme ouverture fichier distant impossible : " . $zipDestination );
            $content = file_get_contents ( $zipLocal );
            if ($content == false)
                throw new Exception ( "Probleme ouverture fichier local impossible : " . $zipLocal );
            if (fwrite ( $flux, $content ) === false)
                throw new Exception ( "Probleme de transfert sur le fichier : " . $zipLocal );
        } catch ( Exception $e ) {
            return ['result' => false, 'msg' => $e->getMessage ()];
        }
        return ['result' => true, 'msg' => "Envoi du package " . $zipLocal];
    }

    public function deleteDCL()
    {
        if ($this->_sftp == null) {
            $this->_sftp = self::initSFTP();
            if ($this->_sftp === false) {
                return false;
            }
        }
        return ssh2_sftp_unlink($this->_sftp, '/' . self::DCL_DIR . '/' . "halms" . $this->_docid . ".zip");
    }

    public function downloadDCL()
    {
        if ($this->_sftp == null) {
            $this->_sftp = self::initSFTP();
            if ($this->_sftp === false) {
                return ['result' => false, 'msg' => "Probleme de connexion sur le serveur DCL fin de la procedure"];
            }
        }

        $zipDestination = "halms" . $this->_docid . ".zip";
        $zipLocal = $this->_pathdoc . "dcl" . $this->_docid . ".zip";
        try {
            $content = file_get_contents( "ssh2.sftp://" . intval($this->_sftp) . '/' . self::DCL_DIR . '/' . $zipDestination );
            if ($content == false)
                throw new Exception ( "Probleme récupération du fichier distant : " . $zipDestination );

            if (file_put_contents($zipLocal, $content) === false)
                throw new Exception ( "Probleme d'enregistrement du fichier local : " . $zipLocal );
        } catch ( Exception $e ) {
            return ['result' => false, 'msg' => $e->getMessage ()];
        }
        return ['result' => true, 'msg' => "Récupération du fichier " . $zipLocal];
    }

    public function unzipDCLArchive()
    {
        $zipLocal = $this->_pathdoc . "dcl" . $this->_docid . ".zip";
        if (is_file($zipLocal)) {
            if (! is_dir($this->_pathdoc . "dcl")) {
                mkdir($this->_pathdoc . "dcl");
            }
            $zip = new ZipArchive;
            $zip->open($zipLocal);
            $zip->extractTo($this->_pathdoc . "dcl");
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * Méthode permettant la génération des sorties HTML et PDF
     */
    public function generate()
    {
        $dclDir = $this->_pathdoc . "dcl/";
        $mathmlDir = $dclDir . "/mathml/";
        $xmlFile = $dclDir . 'halms' . $this->_docid . '.xml';
        $indexFile = $dclDir . 'index.xhtml';
        $dtdPath = self::HALMS_DIR . "journal-publishing-dtd-2.2/";
        $pdfFile = $dclDir . 'halms' . $this->_docid . '_edited.pdf';

        if (! is_file($xmlFile)) {
            //Le fichier XML n'est pas présent dans l'archive de DCL
            return false;
        }

        // 1- Création de la version html

        if (is_file($indexFile)) {
            @unlink($indexFile);
        }
        $cmd = '/sites/halms/transform.sh' . " " . $xmlFile . " " . $dclDir;
        exec( $cmd );
        if (! is_file($indexFile)) {
            //Erreur dans la conversion
            return false;
        }

        // 2- Création de la version PDF

        // 2.1 - Génération des images
        if (! is_dir($mathmlDir)) {
            mkdir($mathmlDir);
        }
        putenv("JAVA_HOME=" . JAVA_HOME);
        putenv("LC_ALL=fr_FR");
        $cmd = JAVA_BIN . " -cp /sites/halms/halmstool halmstool.commandline.Main ". $xmlFile . " " . $mathmlDir . " UNIX";
        exec( $cmd );

        // 2.2 - Génération du pdf
        $cmd = JAVA_BIN . " -Xms256m -Xmx512m -jar ". self::HALMS_DIR . "inserm-disc-pdfgenerator-1.0-jar-with-dependencies.jar " . $dtdPath . " " . $dclDir . " " . $xmlFile . " " . $pdfFile;

        exec( $cmd );

        return is_file($pdfFile);
    }

    public function sendMail($to, $tags, $subject, $body)
    {
        $mail = new Halms_Mail();
        $mail->addTo($to['email'], $to['name']);
        if ($to['name'] != self::HALMS_USERNAME) {
            $mail->addCc(self::HALMS_MAIL, self::HALMS_USERNAME);
        }

        $mail->clearTags();
        foreach($tags as $tag => $replace) {
            $mail->addTag('%%' . $tag . '%%', $replace);
        }
        $mail->replaceTags(Zend_Registry::get( 'Zend_Translate' )->translate($subject));
        $mail->replaceTags(Zend_Registry::get( 'Zend_Translate' )->translate($body));

        $mail->setSubject ($mail->replaceTags(Zend_Registry::get( 'Zend_Translate' )->translate($subject)));
        $mail->setRawBody ($mail->replaceTags(Zend_Registry::get( 'Zend_Translate' )->translate($body)));
        $mail->write();
    }

    /**
     * Création du FRONT de l'article pour envoi a Pubmed
     * @return string xml
     */
    public function createMetaXml(Hal_Document $document)
    {
        $pmid = $document->getIdsCopy('pubmed');

        if (! $pmid) {
            //Pas de pmid, pas normal
            return false;
        }

        $dp = new Ccsd_Dataprovider_Pubmed(Zend_Db_Table_Abstract::getDefaultAdapter());
        $doc = $dp->getDocument($pmid);

        $pubmedMetas = $doc->getMetadatas();

        //Zend_Debug::dump($pubmedMetas);exit;
        $dom = new DOMImplementation;
        $xml = new Ccsd_DOMDocument();
        $xml->appendChild($dom->createDocumentType(
            "article",
            "-//NLM//DTD Journal Publishing DTD v2.3 20070328//EN",
            "http://dtd.nlm.nih.gov/publishing/2.3/journalpublishing.dtd"
        ));

        $xml->encoding = 'utf-8';
        $xml->version = '1.0';
        $xml->formatOutput = true;
        $xml->substituteEntities = true;
        $xml->preserveWhiteSpace = false;

        $article = $xml->createElement('article');

        $root = $xml->createElement('front');


        // JOURNAL
        $journal = $xml->createElement('journal-meta');

        if ( ( $oJ = $document->getMeta('journal') ) instanceof Ccsd_Referentiels_Journal ) {
            $title = $xml->createElement('journal-id', $oJ->JNAME);
            $title->setAttribute('journal-id-type', 'nml-ta');
            $journal->appendChild($title);

            if ($oJ->SHORTNAME ) {
                $shortTitle = $xml->createElement('journal-title', $oJ->SHORTNAME);
                $journal->appendChild($shortTitle);
            }
            if (! $oJ->ISSN && ! $oJ->EISSN) {
                //On récupère dans Pubmed l'info
                $issn = Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['journal']['issn']['value'], false);
                if ($issn) {
                    $issn = $xml->createElement('issn',  $issn);
                    $issnType = Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['journal']['issn']['issntype'], '');
                    $type = $issnType == 'Electronic' ? 'epub' : 'ppub';
                    $issn->setAttribute('pub-type', $type);
                    $journal->appendChild($issn);
                } else {
                    //Pas d'ISSN
                    return false;
                }
            } else {
                if ($oJ->ISSN ) {
                    $issn = $xml->createElement('issn',  $oJ->ISSN);
                    $issn->setAttribute('pub-type', 'ppub');
                    $journal->appendChild($issn);
                }
                if ($oJ->EISSN ) {
                    $eissn = $xml->createElement('issn', $oJ->EISSN);
                    $eissn->setAttribute('pub-type', 'epub');
                    $journal->appendChild($eissn);
                }
            }

        }
        $root->appendChild($journal);

        $articleMeta = $xml->createElement('article-meta');

        //IDENTIFIANTS
        $identifiers = array_merge(['manuscript' => $document->getId()], $document->getMeta('identifier'));

        foreach ($identifiers as $type => $id) {
            if ($type == 'pubmed') {
                $type = 'pmid';
            } else if (! in_array($type, ['manuscript', 'pubmed', 'doi'])) {
                continue;
            }
            $articleId = $xml->createElement('article-id', $id);
            $articleId ->setAttribute('pub-id-type', $type);
            $articleMeta->appendChild($articleId);
        }

        //CATEGORIE
        $categorie = $xml->createElement('article-categories');

        $subGroup = $xml->createElement('subj-group');
        $subGroup->setAttribute('subj-group-type', "heading");

        $subject1 = $xml->createElement('subject', "Article");
        $subGroup->appendChild($subject1);

        $sousSubGroup = $xml->createElement('subj-group');
        $sousSubGroup->setAttribute('subj-group-type', "subrepository");

        $subject2 = $xml->createElement('subject', "Inserm subrepository");
        $sousSubGroup->appendChild($subject2);

        $subGroup->appendChild($sousSubGroup);

        $categorie->appendChild($subGroup);

        $articleMeta->appendChild($categorie);

        //TITRE
        $titreGroup = $xml->createElement('title-group');

        $titreArticle = $xml->createElement('article-title',$document->getMainTitle());
        $titreGroup->appendChild($titreArticle);

        $articleMeta->appendChild($titreGroup);

        //AUTEUR - AFFILIATION
        $contribGroup = $xml->createElement('contrib-group');
        $correspondingAuteur = false;
        $tableauGlobalAffiliations = array();

        foreach ($document->getAuthors() as $author) {
            $contrib = $xml->createElement('contrib');
            $contrib->setAttribute('contrib-type', "author");

            $name = $xml->createElement('name');
            $surname = $xml->createElement('surname',$author->getLastname());
            $name->appendChild($surname);
            $givenNames = $xml->createElement('given-names', $author->getFirstname());
            $name->appendChild($givenNames);

            $contrib->appendChild($name);


            foreach ($author->getStructid() as $id) {
                if ( ($indiceAffiliationCourante = array_search($id, $tableauGlobalAffiliations)) !== false ) {
                    // L'affiliation a déjà été définie
                    $ref = (int)$indiceAffiliationCourante+1;
                } else {
                    // il s'agit d'une nouvelle affiliation
                    $tableauGlobalAffiliations[] = $id;
                    $ref = count($tableauGlobalAffiliations);
                }
                $xref = $xml->createElement('xref', $ref);
                $xref->setAttribute('rid', "A" . $ref );
                $xref->setAttribute('ref-type', "aff");
                $contrib->appendChild($xref);
            }
            if (! $correspondingAuteur ) {
                if ($author->getQuality() == 'crp') {
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
            $articleMeta->appendChild($contribGroup);
        }

        $indice = 1;
        foreach($tableauGlobalAffiliations as $iDaffiliation) {
            $structureCourante = new Ccsd_Referentiels_Structure($iDaffiliation);

            $structName = $structureCourante->getStructname();
            if ($structureCourante->getSigle()) {
                $structName = $structureCourante->getSigle() . ', ' . $structName;
            }
            $affiliation = $xml->createElement('aff', $structName);
            $label = $xml->createElement('label', $indice);
            $affiliation->insertBefore($label, $affiliation->firstChild);
            //$affiliation-> = '<label>' . $indice . '</label>' . $structureCourante->getStructname();
            //$affiliation = $xml->createElement('aff', '<label>' . $indice . '</label>' . $structureCourante->getStructname());
            $affiliation->setAttribute('id', "A" . $indice);
            $indice++;

            $parents = $structureCourante->getAllParents();
            foreach ($parents as $affiliationParent) {
                if ($affiliationParent['struct']->getTypestruct() == Ccsd_Referentiels_Structure::TYPE_INSTITUTION ) {
                    if(isset($affiliationParent['code']))
                        $institution = $xml->createElement('institution', $affiliationParent['struct']->getStructname() . " - ".$affiliationParent['code']);
                    else
                        $institution = $xml->createElement('institution', $affiliationParent['struct']->getStructname());
                    $affiliation->appendChild($institution);
                }
            }

            if ( $structureCourante->getAddress() != '' ) {
                $adresse = $xml->createElement('addr-line', $structureCourante->getAddress());
                $affiliation->appendChild($adresse);
            }

            $articleMeta->appendChild($affiliation);
        }

        if ( $correspondingAuteur ) {
            $authorNotes = $xml->createElement('author-notes');

            $correspondantAuthor = $xml->createElement('corresp', $noteCorrespondingAuteur);
            $correspondantAuthor->setAttribute('id', "FN1");

            $mail = $xml->createElement('email', $emailCorrespondingAuteur);

            $correspondantAuthor->appendChild($mail);

            $authorNotes->appendChild($correspondantAuthor);

            $articleMeta->appendChild($authorNotes);
        }

        //DATE
        $tradMonth = array("Jan"=>"01", "Feb"=>"02", "Mar"=>"03", "Apr"=>"04", "May"=>"05", "Jun"=>"06", "Jul"=>"07", "Aug"=>"08", "Sep"=>"09","Oct"=>"10","Nov"=>"11","Dec"=>"12");

        $ppubY = Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['journal']['pubdate']['year'], false);
        $ppubM = Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['journal']['pubdate']['month'], false);
        if ($ppubY  && $ppubM) {
            $ppubDate = $xml->createElement('pub-date');
            $ppubDate->setAttribute('pub-type', "ppub");

            $month = $xml->createElement('month', $tradMonth[$ppubM]);
            $ppubDate->appendChild($month);

            $year = $xml->createElement('year', $ppubY);
            $ppubDate->appendChild($year);

            $articleMeta->appendChild($ppubDate);
        } else {
            $ppubDate = $xml->createElement('pub-date');
            $ppubDate->setAttribute('pub-type', "ppub");
            $year = $xml->createElement('year', substr($document->getMeta('date'), 0, 4));
            $ppubDate->appendChild($year);

            $articleMeta->appendChild($ppubDate);
        }

        if ('Electronic' == Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['articledate']['datetype'], false)) {
            $epubDate = $xml->createElement('pub-date');
            $epubDate->setAttribute('pub-type', "epub");

            $epubD = Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['articledate']['day'], false);
            if ($epubD) {
                $day = $xml->createElement('day', $epubD);
                $epubDate->appendChild($day);
            }

            $epubM = Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['articledate']['month'], false);
            if ($epubM) {
                $month = $xml->createElement('month', $epubM);
                $epubDate->appendChild($month);
            }

            $epubY = Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['articledate']['year'], false);
            if ($epubY) {
                $year = $xml->createElement('year', $epubY);
                $epubDate->appendChild($year);
            }

            $articleMeta->appendChild($epubDate);

        }

        //INFO PUBLICATION
        if ( $document->getMeta('volume') != '' ) {
            $volume = $xml->createElement('volume', $document->getMeta('volume'));
            $articleMeta->appendChild($volume);
        }

        if ( $document->getMeta('issue') != '' ) {
            $issue = $xml->createElement('issue', $document->getMeta('issue'));
            $articleMeta->appendChild($issue);
        }


        if ( trim($document->getMeta('page'), ' ,.') != '' ) {
            $pagination = $document->getMeta('page');
        } else {
            $pagination = Ccsd_Tools::ifsetor($pubmedMetas['medlinecitation']['article']['pagination']['medlinepgn'], '-');
        }
        list($f,$l) = explode('-', $pagination);

        $fpage = $xml->createElement('fpage', $f);
        $articleMeta->appendChild($fpage);
        $lpage = $xml->createElement('lpage', $l);
        $articleMeta->appendChild($lpage);

        $abstract = $xml->createElement('abstract');

        $paragraph = $xml->createElement('p', $document->getAbstract('en'));
        $paragraph->setAttribute('id', "P1");

        $abstract->appendChild($paragraph);

        $articleMeta->appendChild($abstract);

        //MOTS CLES
        if ( count($kwls = $document->getKeywords()) ) {
            $kwdGroup = $xml->createElement('kwd-group');
            $kwdGroup->setAttribute('kwd-group-type', "Author");
            $vide = true;
            foreach ( $kwls as $lang => $keywords ) {
                if ($lang == "en") {
                    $vide = false;
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
            if ( ! $vide ) {
                $articleMeta->appendChild($kwdGroup);
            }
        }

        //MOTS CLES MESH
        $kwdGroup = $xml->createElement('kwd-group');
        $kwdGroup->setAttribute('kwd-group-type', "MESH");
        $vide = true;
        foreach ( $document->getMeta('mesh') as $mesh ) {
            $vide = false;
            $kwd = $xml->createElement('kwd', $mesh);
            $kwdGroup->appendChild($kwd);
        }
        if ( ! $vide ) {
            $articleMeta->appendChild($kwdGroup);
        }

        $root->appendChild($articleMeta);
        $article->appendChild($root);
        $xml->appendChild($article);

        return $xml->saveXML();
    }

    /**
     * Indique si le fichier xml généré est valide
     * @return bool
     */
    public function isValidMetaXml()
    {
        $xmlFile = $this->_pathhalms . 'halms' . $this->_docid . '.xml';

        if (is_file($xmlFile)) {
            $xml = new DOMDocument();
            $xml->load($xmlFile);
            return $xml->validate();
        }
        return false;
    }


    public function uploadPMC()
    {
        if (! $this->createPmcZip()) {
            //erreur lors de la création du zip
            return false;
        }

        // Mise en place d'une connexion
        $ftpStream = ftp_connect(self::PMC_HOST);
        if (!$ftpStream) { return false; }

        // Ouverture d'une session
        $result = ftp_login ($ftpStream, self::PMC_USER, self::PMC_PWD);
        if (!$result) { return false; }

        // turn on passive mode transfers
        ftp_pasv ($ftpStream, true) ; //les données de connexion sont initiées par le client

        if(!ftp_put($ftpStream, '/halms' . $this->_docid . '.zip', $this->_pathpmc . 'halms' . $this->_docid . '.zip', FTP_BINARY)) {
            return false;
        }
        ftp_close($ftpStream);
        return true;
    }

    /**
     * Mise à jour du document sur HAL
     */
    public function updateHal($pmcid)
    {
        //Modification du dépôt dans HAL (ajout du Pubmed Central ID
        $document = Hal_Document::find($this->_docid);
        try{
            $document->addIdExt($this->_docid, 'pubmedcentral', 'PMC' . $pmcid);
        } catch (Exception $e) {}
        //Réindexation du dépôt
        Hal_Document::deleteCaches($this->_docid);
        Ccsd_Search_Solr_Indexer::addToIndexQueue(array($this->_docid));

    }


    public static function getDocuments($docstatus = null, $limit = null, $uid = null, $returnDocid = false, $cond = null)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->distinct()->from(array('h' => self::TABLE_HALMS), $returnDocid ? 'DOCID' : '*');
        if ($docstatus !== null) {
            if (! is_array($docstatus)) {
                $docstatus = [$docstatus];
            }
            $sql->where('h.DOCSTATUS IN (?)', $docstatus);
        } else {
            $sql->where('h.DOCSTATUS IS NOT NULL');
        }
        $sql->joinLeft(['l' => Halms_Document_Logger::TABLE], 'l.DOCID = h.DOCID AND l.STATUS = h.DOCSTATUS', 'COMMENT');
        $sql->group('h.DOCID');
        $sql->order('DATE_ACTION DESC');
        if ($limit) {
            $sql->limit($limit);
        }
        if ($uid) {
            $sql->join(array('d' => Hal_Document::TABLE), 'd.DOCID = h.DOCID', null)
                ->where('d.UID = ?', $uid);
        }
        if ($cond) {
            $sql->where($cond);
        }

        if ($returnDocid) {
            return $db->fetchCol($sql);
        }
        return $db->fetchAll($sql);

    }

    /**
     * Retourne la liste des statuts possilbes d'un document
     * @return array
     */
    public static function getDocStatus()
    {
        $status = [];
        $reflect = new ReflectionClass('Halms_Document');
        foreach ($reflect->getConstants() as $const => $value) {
            if (substr($const, 0, 7) === 'STATUS_') {
                $status[] = $value;
                }
            }
        return $status;
    }

    /**
     * Vérifie si un document est dans HALMS
     * @param $q
     * @return int
     */
    public static function searchById($q)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(array('H' => self::TABLE_HALMS), 'DOCID');
        if (is_numeric($q)) {
            $sql->where('H.DOCID = ?', (int) $q);
        } else {
            $sql->from(array('D' => Hal_Document::TABLE), null)
                ->where('H.DOCID = D.DOCID')
                ->where('D.IDENTIFIANT LIKE ?', '%' . $q . '%');
        }
        return $db->fetchOne($sql);
    }

    public static function getStateImg($docstatus)
    {
        if (in_array($docstatus, [self::STATUS_INITIAL, self::STATUS_INITIAL_BLOCKED,self::STATUS_INITIAL_EMBARGO,self::STATUS_INITIAL_UNKNOWN,self::STATUS_INITIAL_AHEADOFPRINT])) {
            return 1;
        } else if (in_array($docstatus, [self::STATUS_INITIAL_READY, self::STATUS_WAIT_FOR_DCL])) {
            return 2;
        } else if (in_array($docstatus, [self::STATUS_XML_QA, self::STATUS_XML_CONTROLLED,self::STATUS_XML_ERROR_REPORTED_AUTHOR])) {
            return 3;
        } else if (in_array($docstatus, [self::STATUS_XML_FINISHED])) {
            return 4;
        } else if (in_array($docstatus, [self::STATUS_WAIT_FOR_PMC])) {
            return 5;
        } else {
            return 6;
        }
    }

    public static function getPmcidFromPmid ($pmid)
    {
        $out = 0;

        $resource = curl_init('http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=pmc&retmax=1&term=' . $pmid . '[PMID]');
        curl_setopt($resource, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($resource, CURLOPT_FOLLOWLOCATION, true);
        $return = curl_exec($resource);
        curl_close($resource);
        if ( preg_match('/<Count>(\d+)<\/Count>.*<IdList>[[:space:]]*<Id>(\d+)<\/Id>[[:space:]]*<\/IdList>/', $return, $matches) && count($matches) == 3 && $matches[1] == 1 ) {
            $out = $matches[2];
        }
        return (int)$out;
    }


}
