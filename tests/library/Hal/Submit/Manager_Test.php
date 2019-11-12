<?php
/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 23/02/17
 * Time: 15:35
 */

class Hal_Submit_Manager_Test extends PHPUnit_Framework_TestCase {

    protected $_session;

    public function setUp()
    {
        $this->_session = new Hal_Session_Namespace();
        $this->_session->document = new Hal_Document();
        $this->_session->submitStatus = new Hal_Submit_Status();
        $this->_session->submitOptions = new Hal_Submit_Options();

    }

    public function testMergeMetasInAddFileToDocument()
    {
        // Lorsqu'il y a des métadonnées écrites manuellement dans le dépot (UID!=0),
        // l'ajout de métadonnées automatiquement ne supprime pas les métadonnées manuelles
        /** @var Hal_Document $doc */
        $doc = $this->_session->document;
        $doc->addMetas(["title"=>["en"=>"TOTO"]], 1256);

        $submitManager = new Hal_Submit_Manager($this->_session);

        $submitManager->addFileToDocument($submitManager->fileDataToFileObject("ART.pdf", __DIR__."/../../../ressources/ART.pdf"), '/tmp');

        // Test le merge des métadonnées dans la fonction addFileToDocument
        self::assertEquals(["en"=>"TOTO"], $doc->getMeta('title'));
    }

    public function testAddSimplePdf()
    {
        /** @var Hal_Document $doc */
        $doc = $this->_session->document;

        $submitManager = new Hal_Submit_Manager($this->_session);
        $id = $submitManager->addFileToDocument($submitManager->fileDataToFileObject("Test.pdf", __DIR__."/../../../ressources/FR.pdf"), '/tmp');

        // Le fichier est ajouté
        self::assertEquals(1, count($doc->getFiles()));

        // Le fichier est le fichier principal
        self::assertEquals(true, $doc->getFile($id[0])->getDefault());

        // Des métadonnées ont été récupérées par grobid
        self::assertEquals(false, empty($doc->getMetasFromSource('grobid')));

        // Aucune Métadonnée n'a été récupérée par crossref (car pas de DOI)
        self::assertEquals(true, empty($doc->getMetasFromSource('doi')));
    }

    public function testAddPdfWithDoi()
    {
        /** @var Hal_Document $doc */
        $doc = $this->_session->document;

        $submitManager = new Hal_Submit_Manager($this->_session);
        $id = $submitManager->addFileToDocument($submitManager->fileDataToFileObject("ART-WithDOI.pdf", __DIR__."/../../../ressources/ART-WithDOI.pdf"), '/tmp');

        // Le fichier est ajouté
        self::assertEquals(1, count($doc->getFiles()));

        // Le fichier est le fichier principal
        self::assertEquals(true, $doc->getFile($id[0])->getDefault());

        // Des métadonnées ont été récupérées par grobid
        self::assertEquals(false, empty($doc->getMetasFromSource('grobid')));

        // Des Métadonnées ont été récupérées par crossref
        // Plus d'actualite, grobid fait la jointure avec Crossref, Hal ne fait plus l'appel lui meme
        // self::assertEquals(false, empty($doc->getMetasFromSource('doi')));
    }

    public function testAddImage()
    {
        /** @var Hal_Document $doc */
        $doc = $this->_session->document;

        $submitManager = new Hal_Submit_Manager($this->_session);
        $id = $submitManager->addFileToDocument($submitManager->fileDataToFileObject("Brazil.jpg", __DIR__."/../../../ressources/Brazil.jpg"), '/tmp');

        // Le fichier est ajouté
        self::assertEquals(1, count($doc->getFiles()));

        // Le fichier est le fichier principal
        self::assertEquals(true, $doc->getFile($id[0])->getDefault());

        // Des métadonnées ont été récupérées de l'image
        self::assertEquals(false, empty($doc->getMetasFromSource('image')));

        // Aucune Métadonnée n'a été récupérée par grobid
        self::assertEquals(true, empty($doc->getMetasFromSource('grobid')));

        // Aucune Métadonnée n'a été récupérée par crossref (car pas de DOI)
        self::assertEquals(true, empty($doc->getMetasFromSource('doi')));
    }

    // Le problème de ce test est qu'on ne converti pas les fichiers en local !!!
    // De plus, faire un MOCK d'une fonction static n'est pas trivial...
    /*public function testAddDoc()
    {
        $doc = new Hal_Document();
        $status = new Hal_Submit_Status();
        $id = Hal_Submit_Manager::addFileToDocument($doc, $status, "krakow.doc", __DIR__."/../../../ressources/krakow.doc", '/tmp');

        // Le fichier est ajouté et converti
        self::assertEquals(2, count($doc->getFiles()));

        // Le fichier converti est le fichier principal
        self::assertEquals(true, $doc->getFile($id[1])->getDefault());

        // Des métadonnées ont été récupéré par grobid
        self::assertEquals(true, empty($doc->getMetasFromSource('grobid')));

        // Aucune Métadonnée n'a été récupérée par crossref (car pas de DOI)
        self::assertEquals(true, empty($doc->getMetasFromSource('doi')));
    }*/

    public function testAddLatex()
    {
        /** @var Hal_Document $doc */
        $doc = $this->_session->document;

        $submitManager = new Hal_Submit_Manager($this->_session);
        $id = $submitManager->addFileToDocument($submitManager->fileDataToFileObject("paper.tex", __DIR__."/../../../ressources/paper.tex"), '/tmp');

        // Le fichier est ajouté
        self::assertEquals(1, count($doc->getFiles()));

        // Le fichier est le fichier principal
        self::assertEquals(false, $doc->getFile($id[0])->getDefault());

        // Aucune Métadonnée n'a été récupérée par grobid
        self::assertEquals(true, empty($doc->getMetasFromSource('grobid')));

        // Aucune Métadonnée n'a été récupérée par crossref (car pas de DOI)
        self::assertEquals(true, empty($doc->getMetasFromSource('doi')));
    }

    // Le problème de ce test est qu'on ne converti pas les fichiers en local !!!
    // De plus, faire un MOCK d'une fonction static n'est pas trivial...
    /*public function testAddZippedFiles()
    {
        $doc = new Hal_Document();
        $status = new Hal_Submit_Status();
        $id = Hal_Submit_Manager::addFileToDocument($doc, $status, "ART_TEST.zip", __DIR__."/../../../ressources/ART_TEST.zip", '/tmp');

        // Le fichier est ajouté
        self::assertEquals(2, count($doc->getFiles()));

        // Le fichier est le fichier principal
        self::assertEquals(false, $doc->getFile($id[1])->getDefault());

        // Des métadonnées ont été récupérées par Grobid
        self::assertEquals(false, empty($doc->getMetasFromSource('grobid')));

        // Des métadonnées ont été récupérée par Crossref (car pas de DOI)
        self::assertEquals(false, empty($doc->getMetasFromSource('doi')));
    }*/

    public function testSwitchMainFile()
    {
        /** @var Hal_Document $doc */
        $doc = $this->_session->document;

        $submitManager = new Hal_Submit_Manager($this->_session);
        $submitManager->addFileToDocument($submitManager->fileDataToFileObject("Test.pdf", __DIR__."/../../../ressources/Test.pdf"), '/tmp');

        self::assertEquals(["en"=>"Early degassing of lunar urKREEP by crust-breaching impact(s)"], $doc->getMeta('title'));

        $submitManager->addFileToDocument($submitManager->fileDataToFileObject("ART.pdf", __DIR__."/../../../ressources/ART.pdf"), '/tmp');

        $doc->majMainFile("ART.pdf");

        $metasArray = $submitManager->createMetadatas(__DIR__."/../../../ressources/ART.pdf", "pdf");
        $submitManager->loadExternalMeta(["grobid" => $metasArray]);


        // Test le merge des métadonnées dans la fonction addFileToDocument
        self::assertEquals(["en"=>"Estimating the frequency of nuclear accidents"], $doc->getMeta('title'));

    }

    public function testRecupDatePubli()
    {
        $submitManager = new Hal_Submit_Manager($this->_session);
        // DOI
        $metas = $submitManager->createMetadatas("10.1371/journal.pone.0081280", "doi");
        self::assertEquals("2013-11-21", $metas["metas"][Ccsd_Externdoc::META_DATE]);

        //PubMed
        $metas = $submitManager->createMetadatas("28678778", "pubmed");
        self::assertEquals("2017-07-13", $metas["metas"][Ccsd_Externdoc::META_DATE]);
    }

    public function testRecupLang()
    {
        $submitManager = new Hal_Submit_Manager($this->_session);
        $metas = $submitManager->createMetadatas(__DIR__."/../../../ressources/FR.pdf", "pdf");
        self::assertEquals("fr", $metas["metas"][Ccsd_Externdoc::META_LANG]);
    }

    public function testRecupMultipleLangTitle()
    {
        $submitManager = new Hal_Submit_Manager($this->_session);
        // DOI
        $metas = $submitManager->createMetadatas("10.4000/cybergeo.23737", "doi");
        self::assertEquals("The thermal rehabilitation of the old buildings of Paris: how to conciliate protection of urban heritage and energetic performance?", $metas[Ccsd_Externdoc::META][Ccsd_Externdoc::META_TITLE]["en"]);
        self::assertEquals("La réhabilitation thermique des bâtiments anciens à Paris : comment concilier protection du patrimoine et performance énergétique ?", $metas[Ccsd_Externdoc::META][Ccsd_Externdoc::META_TITLE]["fr"]);
    }

    /**
     * @param $text
     * @param $lang
     * @throws Exception
     * @dataProvider provideTestLanguage
     */
    public function testDetectLanguage($text, $lang) {
        $detector = new Ccsd_Detectlanguage();
        $bestLangInfo = $detector->detect($text);
        if ($bestLangInfo) {
            $langTrouve = $bestLangInfo['langid'];
            $this->assertEquals($lang, $langTrouve);
        } else {
            $this->assertEquals($lang, "en");
        }
    }

    /**
     * Provide example to the language detection system
     * @return array
     */
    public function provideTestLanguage() {
        return
            [ '1' => [ 'The thermal rehabilitation of the old buildings of Paris: how to conciliate protection of urban heritage and energetic performance?' , 'en'] ,
              '2' => [ "Antecedents of well-being for artisan entrepreneurship: A first exploratory study" , "en" ] ,
              '3' => [ "In vitro cytotoxic effects of secondary metabolites of DEHP and its alternative plasticizers DINCH and DINP on a L929 cell line " , "en"] ,
              '4' => [ "Est-il moral de s’endetter ? Familles endettes dans le roman LITTLE DORRIT (1855-1857) DE CHARLES DICKENS" , "fr"],
              '5' => [ "Le Diable dans les occasionnels (deuxième article)" , 'fr'],
              '6' => [ "Affectivity in mathematical learning: experimental case in University of Veracruz, Mexico", "en"],
              '7' => [ "Divulgation volontaire sur le Business model : le cas des entreprises du CAC40" , "fr"],
              '8' => ["Importance et rôles du contrôle de gestion dans une Université publique : une étude de cas", 'fr'],
        ];
    }
}