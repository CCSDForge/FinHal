<?php

class Document_Test extends PHPUnit_Framework_TestCase
{

    public function setUp(){
        if (!defined('SITEID')) {
            define('SITEID', 1);
        }
        // Clean situation in DB
        $doc = new Hal_Document(1);
        $doc->load();
        try {
            $doc->delete();
        } catch (Exception $e) {
            // ok delete has problem, we don't care
        }
    }
    /**
     * @param $docid
     * @param $result
     *
     * @dataProvider provideSave
     */
    public function testsave($docid, $result)
    {
        $doc = new Hal_Document();

        // TO DO : test avec keyword !!
        // Est-ce que c'est la bonne manière de tester ??
        $doc->setTypeSubmit(Hal_Settings::SUBMIT_INIT);
        $doc->setMetas($result);
        $doc->setSid(1);
        $doc->save(0, false);
        $doc->setMetas([]);
        $doc->load();
        //if ( array_key_exists('journal', $result)) {
        //    $result['journal']->MD5 = null;
        //}

        $metas = $doc->getMeta();
        /* on ne veux pas tester les linkext (meta-autocalculee) */
        unset($metas['LINKEXT']);
        self::assertEquals($result, $metas);
    }

    /**
     * @return array
     */
    public function provideSave()
    {
        return [
            'Load qui se passe bien' => [1, [
                'peerReviewing' => '1',
                'popularLevel' => '0',
                'language' => 'en',
                'journal' => (new Ccsd_Referentiels_Journal())->load('2872'),
                'date' => '2001',
                'volume' => '69',
                'page' => '655 - 701',
                'comment' => 'article soumis un an après sa parution',
                'classification' => '03.65.Ta, 03.65.ud, 03.67.a',
                'domain' => [0 => 'phys.qphy'],
                'localReference' => [0 => 'LKB 4'],
                'title' => ['en' => 'Do we really understand quantum mechanics?'],
                'abstract' => ['en' => 'This article presents a general discussion of several aspects of our presentunderstanding of quantum mechanics. The emphasis is put on the very specialcorrelations that this theory makes possible: they are forbidden by verygeneral arguments based on realism and local causality. In fact, thesecorrelations are completely impossible in any circumstance, except the veryspecial situations designed by physicists especially to observe these purelyquantum effects. Another general point that is emphasized is the necessity forthe theory to predict the emergence of a single result in a single realizationof an experiment. For this purpose, orthodox quantum mechanics introduces aspecial postulate: the reduction of the state vector, which comes in additionto the Schrödinger evolution postulate. Nevertheless, the presence inparallel of two evolution processes of the same object (the state vector) maybe a potential source for conflicts; various attitudes that are possible toavoid this problem are discussed in this text. After a brief historicalintroduction, recalling how the very special status of the state vector hasemerged in quantum mechanics, various conceptual difficulties are introducedand discussed. The Einstein Podolsky Rosen (EPR) theorem is presented withthe help of a botanical parable, in a way that emphasizes how deeply the EPRreasoning is rooted into what is often called "scientific method\'\'. Inanother section the GHZ argument, the Hardy impossibilities, as well as theBKS theorem are introduced in simple terms.'],
                //'keyword' => ['en' => ['Bell theorem', 'quantum measurement', 'foundations of quantum mechanics', 'alternative theories']],
                'identifier' => ['arxiv' => 'quant-ph/0209123']
            ]],
            'Load avec anrproject' => [1, [
                'peerReviewing' => '1',
                'popularLevel' => '0',
                'language' => 'en',
                'date' => '2008',
                'writingDate' => '2008',
                'volume' => '118',
                'page' => '718-742',
                'domain' => [0 => 'shs.eco'],
                'issue' => 'avril',
                'audience' => '2',
                'title' => ['en' => 'Optimal Degree of Public Information Dissemination'],
                //'keyword' => ['en' => ['Transparency', 'Beauty contest', 'Information']],
                'journal' => (new Ccsd_Referentiels_Journal())->load('96102'),
                'anrProject' => [0 => (new Ccsd_Referentiels_Anrproject())->load('5912')]
            ]],
            'Load avec europeanProject' => [1, [
                'peerReviewing' => '1',
                'popularLevel' => '0',
                'language' => 'en',
                'date' => '2012',
                'edate' => '2012-04-24',
                'volume' => '3',
                'page' => '796',
                'domain' => [0 => 'sdv.mp.vir'],
                'audience' => '2',
                'title' => ['en' => 'Bats host major mammalian paramyxoviruses.'],
                'abstract' => ['en' => 'The large virus family Paramyxoviridae includes some of the most significant human and livestock viruses, such as measles-, distemper-, mumps-, parainfluenza-, Newcastle disease-, respiratory syncytial virus and metapneumoviruses. Here we identify an estimated 66 new paramyxoviruses in a worldwide sample of 119 bat and rodent species (9,278 individuals). Major discoveries include evidence of an origin of Hendra- and Nipah virus in Africa, identification of a bat virus conspecific with the human mumps virus, detection of close relatives of respiratory syncytial virus, mouse pneumonia- and canine distemper virus in bats, as well as direct evidence of Sendai virus in rodents. Phylogenetic reconstruction of host associations suggests a predominance of host switches from bats to other mammals and birds. Hypothesis tests in a maximum likelihood framework permit the phylogenetic placement of bats as tentative hosts at ancestral nodes to both the major Paramyxoviridae subfamilies (Paramyxovirinae and Pneumovirinae). Future attempts to predict the emergence of novel paramyxoviruses in humans and livestock will have to rely fundamentally on these data.'],
                'funding' => [0 => 'This study was funded by the European Union FP7 projects EMPERIE (Grant agreement number 223498) and EVA (Grant agreement number 228292), the German Federal Ministry of Education and Research (BMBF; project code 01KIO701), the German Research Foundation (DFG; Grant agreement number DR 772/3-1) to CD; the German Federal Ministry of Education and Research (BMBF) through the National Research Platform for Zoonoses (project code 01KI1018), the Umweltbundesamt (FKZ 370941401) and the Robert Koch-Institut (FKZ 1362/1-924) to RGU; through the Government of Gabon, Total-Fina-Elf Gabon and the Ministère des Affaires Etrangères, France.'],
                'journal' => (new Ccsd_Referentiels_Journal())->load('96102'),
                'europeanProject' => [0 => (new Ccsd_Referentiels_Europeanproject())->load('18734')],
                'identifier' => ['doi' => '10.1038/ncomms1796', 'pubmed' => '22531181']
            ]]
        ];
    }


     public function testgetMeta()
    {
        $doc = new Hal_Document();
        $metas = [
            'title' => ['fr' => 'Titre', 'en' => 'Title'],
            'date' => '2016-02-16',
            'licence' => 'http://creativecommons.org/licenses/by/',
            'identifier' => ['doi' => '10.2365', 'arxiv' => '14.2369'],
            'keyword' => ['es' => ['llave1', 'llave2']]
        ];
        $doc->setMetas($metas);
        self::assertEquals($metas, $doc->getMeta());
        self::assertEquals('http://creativecommons.org/licenses/by/', $doc->getMeta('licence'));
        self::assertEquals(['es' => ['llave1', 'llave2']], $doc->getMeta('keyword'));
        self::assertEquals(['doi' => '10.2365', 'arxiv' => '14.2369'], $doc->getMeta('identifier'));
        self::assertEquals('10.2365', $doc->getMeta('doi'));
    }

    /**
     * @param $licence
     * @param $result
     * @dataProvider provideLicences
     */
    public function testgetLicence($licence, $result)
    {
        $doc = new Hal_Document();
        $doc->setLicence($licence);
        self::assertEquals($result, $doc->getLicence());
    }

    /**
     * @return array
     */
    public function provideLicences()
    {
        return [
            'Licence Valide' => ['http://creativecommons.org/licenses/by/', 'http://creativecommons.org/licenses/by/'],
            'Licence vide' => ['', ''],
            'Licence Invalide' => ['Test', '']
        ];
    }

    /**
     * @param $title
     * @param $subtitle
     * @param $filter
     * @param $result
     *
     * @dataProvider provideGetTitle
     */
    public function testgetTitle($title, $subtitle, $filter, $result) {
        $doc = new Hal_Document();
        $lang = $filter == '' ? 'fr' : $filter;
        $doc->setMetas(['title' => $title, 'subTitle' => $subtitle,'language' => $lang ]);
        self::assertEquals($result, $doc->getTitle($filter, true));
        // Test equivalence fonction
        $gettitle = $doc->getTitle($lang, true);
    }

    /**
     * @return array
     */
    public function provideGetTitle() {
        return [
            //TO DO !! Ne pas mettre les : lorsque le sous-titre est vide
            'titre sans sous titre sans filtre' => [['fr' => 'Test'], ['fr' => ''], '', ['fr' => 'Test']],
            'titre avec sous titre null sans filtre' => [['fr' => 'Test'], null, '', ['fr' => 'Test']],
            'titre sans sous titre avec filtre' => [['fr' => 'Test'], ['fr' => ''], 'fr', 'Test'],
            'titre avec sous titre fr avec filtre' => [['fr' => 'Test'], ['fr' => 'Titre'], 'fr', 'Test : Titre'],
            'titre avec sous titre en avec filtre' => [['en' => 'Test'], ['en' => 'Title'], 'en', 'Test: Title'],
            'titre avec sous titre sans filtre' => [['fr' => 'Test'], ['fr' => 'Titre'], '', ['fr' =>'Test : Titre']],
            'titre avec sous titre avec filtre sans resultat' => [['fr' => 'Test'], ['fr' => 'Title'], 'en', ''],
            'pas de titre avec filtre' => [[], [], 'fr', ''],
            'titre sans rien' => [[], [], '', []],
        ];
    }

    /**
     * @param $subtitle
     * @param $groupMeta
     * @param $filter
     * @param $result
     *
     * @dataProvider provideSubtitles
     */
    public function testgetSubtitle($subtitle, $filter, $result) {
        $doc = new Hal_Document();
        $doc->setMetas(['subTitle' => $subtitle]);
        self::assertEquals($result, $doc->getSubTitle($filter));
    }

    /**
     * @return array
     * @see testgetSubtitle
     */
    public function provideSubtitles() {
        return [
            'soustitre sans filtre' => [['fr' => 'Soustitre', 'en' => 'SubTitle'], '', ['fr' => 'Soustitre', 'en' => 'SubTitle']],
            'soustitre avec filtre' => [['fr' => 'Soustitre', 'en' => 'SubTitle'], 'fr', 'Soustitre'],
            'sans soustitre avec filtre' => [[], 'fr', ''],
            'sans soustitre sans filtre' => [[], '', []],
        ];
    }

    /**
     * @param $abstract
     * @param $groupMeta
     * @param $filter
     * @param $result
     *
     * @dataProvider provideAbstracts
     */
    public function testgetAbstract($abstract, $filter, $result) {
        $doc = new Hal_Document();
        $doc->setMetas(['abstract' => $abstract]);
        self::assertEquals($result, $doc->getAbstract($filter));
    }

    /**
     * @return array
     */
    public function provideAbstracts() {
        return [
            'abstract sans filtre' => [['fr' => 'Résumé', 'en' => 'Abstract'], '', ['fr' => 'Résumé', 'en' => 'Abstract']],
            'abstract avec filtre' => [['fr' => 'Résumé', 'en' => 'Abstract'], 'fr', 'Résumé'],
            'sans abstract avec filtre' => [[], 'fr', ''],
            'sans abstract sans filtre' => [[], '', []],
        ];
    }

    /**
     * @param $domains
     * @param $groupMeta
     * @param $result
     *
     * @dataProvider provideDomains
     */
    public function testgetDomains($domains, $result) {
        $doc = new Hal_Document();
        $doc->setMetas(['domain' => $domains]);
        self::assertEquals($result, $doc->getDomains());
    }

    /**
     * @return array
     */
    public function provideDomains() {
        return [
            'domains' => [[0 => ['phys.qphy', 'scco.psyc'], 1 => ['math.math-dg']], [0 => ['phys.qphy', 'scco.psyc'], 1 => ['math.math-dg']]],
            'sans domains' => [[], []],
        ];
    }

    /**
     * @param $idscopy
     * @param $groupMeta
     * @param $filter
     * @param $result
     *
     * @dataProvider provideIdsCopy
     */
    public function testgetIdsCopy($idscopy, $filter, $result) {
        $doc = new Hal_Document();
        $doc->setMetas(['identifier' => $idscopy]);
        self::assertEquals($result, $doc->getIdsCopy($filter));
    }

    /**
     * @return array
     */
    public function provideIdsCopy() {
        return [
            'identifiant sans filtre' => [['doi' => '10.3659', 'arxiv' => '1450.3265'], '', ['doi' => '10.3659', 'arxiv' => '1450.3265']],
            'identifiant avec filtre' => [['doi' => '10.3659', 'arxiv' => '1450.3265'], 'doi', '10.3659'],
            'sans identifiant avec filtre' => [[], 'doi', ''],
            'sans identifiant sans filtre' => [[], '', []],
            'sans meta identifiant sans filtre' => [null, null, []],
        ];
    }

    /**
     * @param $keywords
     * @param $groupMeta
     * @param $filter
     * @param $result
     *
     * @dataProvider provideKeywords
     */
    public function testgetKeywords($keywords, $filter, $result) {
        $doc = new Hal_Document();
        $doc->setMetas(['keyword' => $keywords]);
        self::assertEquals($result, $doc->getKeywords($filter));
    }

    /**
     * @return array
     */
    public function provideKeywords() {
        return [
            'keywords sans filtre' => [['en' => ['key1', 'key2'], 'fr' => ['cle1', 'cle2']], '', ['en' => ['key1', 'key2'], 'fr' => ['cle1', 'cle2']]],
            'keywords avec filtre' => [['en' => ['key1', 'key2'], 'fr' => ['cle1', 'cle2']], 'fr', ['cle1', 'cle2']],
            //TO DO !! 'sans keywords avec filtre' => [[], [], 'fr', []],
            'sans keywords sans filtre' => [[], '', []],
        ];
    }

    /**
     * @param $domains
     * @param $result
     *
     * @dataProvider provideMaindomain
     */
    public function testgetMaindomain($domains, $result) {
        $doc = new Hal_Document();
        $doc->setMetas(['domain' => $domains]);
        self::assertEquals($result, $doc->getMainDomain());
    }

    /**
     * @return array
     */
    public function provideMaindomain() {
        return [
            'multiples domains' => [[0 => ['phys.qphy', 'scco.psyc'], 1 => ['math.math-dg']], ['phys.qphy', 'scco.psyc']],
            'unique domain' => [[0 => ['math.math-dg']], ['math.math-dg']],
            'sans domains' => [[], ''],
        ];
    }

    public function testclearMetas() {
        $doc = new Hal_Document();
        $doc->setMetas([
            'keyword' => ['en'=>['key1', 'key2'], 'fr'=>['cle1', 'cle2']],
            'date' => '2016-02-16',
            'licence' => 'http://creativecommons.org/licenses/by/',
        ]);
        $doc->clearMetas();

        self::assertEquals('http://creativecommons.org/licenses/by/', $doc->getMeta('licence'));
        self::assertEquals([], $doc->getMeta('keyword'));
        self::assertEquals('', $doc->getMeta('date'));
    }

    public function testdelMeta() {
        $doc = new Hal_Document();
        $doc->setMetas([
            'keyword' => ['en'=>['key1', 'key2'], 'fr'=>['cle1', 'cle2']],
            'date' => '2016-02-16',
            'licence' => 'http://creativecommons.org/licenses/by/',
        ]);
        $doc->delMeta('licence');

        self::assertEquals('', $doc->getMeta('licence'));
        self::assertEquals(['en'=>['key1', 'key2'], 'fr'=>['cle1', 'cle2']], $doc->getMeta('keyword'));
        self::assertEquals('2016-02-16', $doc->getMeta('date'));
    }

    /**
     * @param string $last
     * @param string $first
     * @param $idx
     * @return Hal_Document_Author
     */
    protected function createAuthor($last, $first, $idx)
    {
        $aut = new Hal_Document_Author();
        $aut->setLastname($last);
        $aut->setFirstname($first);
        $aut->setStructidx($idx);

        return $aut;
    }

    /**
     * @param $xmlFile
     * @param $metas
     * @param $authors
     * @param $files
     * @param $related
     *
     * @dataProvider provideLoadFromTEI
     */
    public function testloadFromTEI($xmlFile, $metas, $authors, $resFiles, $related, $structs)
    {
        $doc = new Hal_Document();
        $contentFile = file_get_contents($xmlFile);

        $content = new DOMDocument();
        $content->loadXML($contentFile);

        $doc->loadFromTEI($content);

        // Il faut retirer dateVisible qui dépend de la date du jour
        unset($metas["dateVisible"]);
        $this->assertEquals($doc->getMeta(), $metas);
        $this->assertEquals($doc->getAuthors(), $authors);

        $files = $doc->getFiles();

        for ($i = 0 ; $i < count($files) ; $i++) {
            // Il faut retirer le "path" qui est temporaire + la date visible et l'imagette
            $array = $files[$i]->toArray();
            unset($array['path']);
            unset($array['dateVisible']);
            unset($array['imagetteUrl']);
            unset($resFiles[$i]['path']);
            unset($resFiles[$i]['dateVisible']);
            unset($resFiles[$i]['imagetteUrl']);

            $this->assertEquals($resFiles[$i], $array);
        }

        $this->assertEquals($doc->getRelated(), $related);

        $this->assertEquals($doc->getStructures(), $structs);
    }

    /**
     * @return array
     */
    public function provideLoadFromTEI()
    {
        $aut1 = $this->createAuthor('raju', 's.', [0]);

        $aut2 = $this->createAuthor('Moller', 'Faron', [0]);
        $aut3 = $this->createAuthor('Struth', 'Georg', [1]);
        return [
            'Test load Preprint' => [__DIR__.'/../../ressources/test_sword_preprint.xml',
                                    ['title' => ['en' => 'Estimating the risk of nuclear accidents'], 'language' => 'en', 'keyword' => ['en' => ["Probabilistic Risk Assesment", "Bayesian Analysis", "Nuclear Safety"]], 'domain' => ["math.math-mp", "phys.nucl"], 'abstract' => ["en" => "We used Bayesian methods to compare the predictions."], 'identifier' => ['arxiv' => "1608.08894"], 'comment' => "19 pages."],
                                    [$aut1],
                                    [['fileid' => 0, 'name' => 'WAC-grame.pdf', 'comment' => '', 'fileType'=> 'file', 'typeAnnex' => '', 'fileSource' => 'author', 'typeMIME' => 'application/pdf', 'imagette' => 0, 'imagetteUrl' =>  '//thumb.ccsd.cnrs.fr/0/small', 'extension' => 'pdf', 'size' => '0 B', 'default' => true, 'defaultAnnex' => false, 'md5' => '',  'source' => 'author']],
                                    [],
                                    [new Hal_Document_Structure(247160)]
                                    ],
            'Test load Ouvrage' => [__DIR__.'/../../ressources/test_sword_ouvrage.xml',
                                    ['title' => ['en' => "Modelling Computing Systems"], 'subTitle' => ['en' => "Mathematics for Computer Science"], 'language' => 'en', 'domain' => ["info.info-mo", "math.math-oc"], 'abstract' => ['en' => "This engaging textbook presents the fundamental mathematics."], 'isbn' => '978-1-84800-321-7', 'eisbn' => "978-1-84800-322-4", 'bookTitle' => "Modelling Computing Systems", 'publisher' => ["Springer"], 'serie' => "Undergraduate Topics in Computer Science", 'volume' => "16", 'page' => "500", 'date' => "2013", 'publisherLink' => "http://www.springer.com", 'popularLevel' => "0", 'audience' => "2"],
                                    [$aut2, $aut3],
                                    [[]],
                                    [['URI' => 'bouzin.toto', 'RELATION' => 'machin', 'INFO' => 'bidule']],
                                    [new Hal_Document_Structure(36731), new Hal_Document_Structure(205974)]
                                    ]
        ];
    }

    /**
     * @dataProvider provideMakeTexCoverPage
     * @param $docid
     * @param $testsAndResults
     */
    public function testMakeTexCoverPage($xmlFile, $regexps) {
        $document = new Hal_Document();
        $contentFile = file_get_contents($xmlFile);

        $content = new DOMDocument();
        $content->loadXML($contentFile);

        $document->loadFromTEI($content);
        // La date de sousmission n'est pas dans la Tei, et le document n'etant pas publie, il faut le simuler.
        $document->set_submittedDate();

        $needdedFiles = [];
        $tex = $document -> makeTexCoverPage($needdedFiles);
        // file_put_contents('/tmp/cover.tex', $tex);
        foreach ($regexps as $regexp) {
            $this->assertRegExp($regexp, $tex);
        }
    }

    /**
     * Provider for testMakeTexCoverPage
     * @return array
     */
    public function provideMakeTexCoverPage() {
        return [
            1 => [__DIR__.'/../../ressources/test_sword_preprint.xml',[
                '/documentclass/',
                '/\{\\\\LARGE \\\\textbf\{Estimating the risk of nuclear accidents\}\}/', // verif de la presence du titre
                '/\\\\end\{document\}/'    // Le document est fini
            ] ],
            2 => [__DIR__.'/../../ressources/test_sword_ouvrage.xml', [
                '/Faron Moller, Georg Struth. [^\.]*\. Springer, 16, pp.500, 2013, Undergraduate Topics in Computer Science, 978-1-84800-321-7/'
            ] ],
            3 => [__DIR__.'/../../ressources/test_sword_preprint_multilingual.xml',[
                '/setCJKmainfont/',
            ] ],
        ];
    }

    /** @see Hal_Document::makeCoverPage()  */
    public function testMakeTexCoverPageI18n()
    {
        // Chinese Document
        $id='halshs-00188358';
        $document = new Hal_Document(0, $id,0, true );
        $needdedFiles = [];
        $tex = $document -> makeTexCoverPage($needdedFiles);
        $this -> assertRegExp('/xltxtra/',$tex);
        $this -> assertRegExp('/japanesefont/',$tex);
    }

    /**
     *
     */
    public function testgetFileByFileIdx()
    {
        $file = new Hal_Document_File();
        $file->setName('Test.pdf');
        $file->setFileid(356789);
        $doc = new Hal_Document();

        $doc->addFile($file);

        $fileById = $doc->getFileByFileIdx('0');

        self::assertEquals($fileById->getName(), 'Test.pdf');
    }

    /**
     * @param $result
     * @param $l1
     * @param $f1
     * @param $l2
     * @param $f2
     *
     * @dataProvider provideAddAuthor
     */
    public function testAddAuthor($result, $l1, $f1, $l2, $f2)
    {
        $aut1 = new Hal_Document_Author();
        $aut2 = new Hal_Document_Author();

        $aut1->setLastname($l1);
        $aut1->setFirstname($f1);

        $aut2->setLastname($l2);
        $aut2->setFirstname($f2);

        $doc = new Hal_Document();

        $doc->addAuthor($aut1);
        $doc->addAuthor($aut2);

        self::assertEquals($result, count($doc->getAuthors()));

    }

    public function provideAddAuthor()
    {
        return [
            'Initiale'=> [1, 'Dupont', 'Jean', 'Dupont', 'J'],
            'Prenoms complets'=> [1, 'Dupont', 'Jean', 'Dupont', 'Jean'],
            'Prenoms differents'=> [2, 'Dupont', 'Jean', 'Dupont', 'Jacques'],
            'Initiale avec point'=> [1, 'Dupont', 'Jean', 'Dupont', 'J.']
        ];
    }

    public function testxslt_dc() {
        $id='hal-01184451';
        $document = new Hal_Document(0, $id,0, true );
        $tei = $document->get('tei');
        $dcstring = Ccsd_Tools::xslt($tei, APPROOT . '/library/Hal/Document/xsl/dc.xsl',
            [ 'currentDate' => date('Y-m-d')]);
        $this -> assertRegExp('|<dc:rights>info:eu-repo/semantics/OpenAccess|', $dcstring);
    }

    public function testgetDocidFromId() {
        $id='hal-01184451';
        $document = new Hal_Document(0, $id,0, true );
        $docid = $document->getDocidFromId('hal-00956573');
        $this -> assertEquals(1174371, $docid);
    }

    public function testgetRacineCache_s()
    {
        $this->assertEquals(realpath(__DIR__."/../../cache/development/docs/00/18/83/58/"), realpath(Hal_Document::getRacineCache_s(188358)));

        // C'est comme ça que ça se passe mais c'est pas forcément bien !
        $this->assertEquals('', Hal_Document::getRacineCache_s("/188358"));

        $docpourri = new Hal_Document("trucpourri");
        $this->assertEquals(false, $docpourri->get('phps'));

    }

    public function testDoublonResearcher() {
        $id='1000014';
        $document = new Hal_Document($id,'', 0, true );
        $copy = $document->getIdsCopy();
        $docidsForId = [$id];
        $this -> assertNotEquals([], Hal_Document_Doublonresearcher::getDoublonsOnIds($copy, $docidsForId));

        $id='449842';
        $document = new Hal_Document($id,'', 0, true );
        $copy = $document->getIdsCopy();
        $docidsForId = [$id];
        $this -> assertEquals([], Hal_Document_Doublonresearcher::getDoublonsOnIds($copy, $docidsForId));

        $id='301205';
        $document = new Hal_Document($id,'', 0, true );
        $copy = $document->getIdsCopy();
        $docidsForId = [$id];
        $this -> assertNotEquals([], Hal_Document_Doublonresearcher::getDoublonsOnIds($copy, $docidsForId));

    }
}
