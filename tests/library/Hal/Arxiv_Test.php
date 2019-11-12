<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 30/05/17
 * Time: 14:25
 */

/** Pour les tests d'envoie reel vers Arxiv, il faut "mocker qq fonctions, on surcharge.  */
class Hal_Document_ForTest extends Hal_Document {

    /** l'originale va dans la base de données chercher test (qui n'existe pas...) */
    public function getArXivSwordCollection() {
        return 'test';
    }

    /** l'originale utilise Hal_User, on shunte */
    public function setContributorFromArray($userAsArray) {
        $this->_uid = $userAsArray;
    }

    /** Pas d'originale...
     * TODO: A mettre??? */
    public function setSubmittedDate($date = null) {
        $this->_submittedDate = $date == null ? date('Y-m-d H:i:s') : $date;
    }

    /** l'originale va dans la base de données chercher test  */
    public function getArxivCategories()
    {
        return  ['test.dis-nn' => 'test.dis-nn'];
    }

}

class Arxiv_Test extends PHPUnit_Framework_TestCase
{
    /** @var  Hal_Document_File */
    static private $file1, $file2, $file3, $file4, $file5;
    /** @var string */
    static private $rootDoc = PATHDOCS . '/01/01/01'; // juste pour eviter des erreurs multiples phpstorm PATHDOCS
    /** @var  Hal_Document[] : des documents qui echoueront sur Arxiv */
    static private $doc_fail;
    /** @var Hal_Document[] */
    static private $doc_OnHold;


    static public function setUpBeforeClass()
    {
        // parent::__construct();
        /* Prepare Files objects for testing */
        self::$file1 = new Hal_Document_File();
        self::$file1->set(['name' => 'MyFile1.pdf', 'path' => self::$rootDoc . '/01/Chapter.pdf', 'format' => 'pdf', 'type' => 'file', 'extension' => 'pdf']);
        self::$file2 = new Hal_Document_File();
        self::$file2->set(['name' => 'MyFile2.pdf', 'path' => self::$rootDoc . '/02/MyFile2.pdf', 'format' => 'pdf', 'type' => 'src', 'extension' => 'pdf']);
        self::$file3 = new Hal_Document_File();
        self::$file3->set(['name' => 'MyFile3.jpg', 'path' => self::$rootDoc . '/02/MyFile3.jpg', 'format' => 'jpg', 'type' => 'annex', 'extension' => 'jpg']);
        self::$file4 = new Hal_Document_File();
        self::$file4->set(['name' => 'MyFile4.zip', 'path' => self::$rootDoc . '/02/MyFile4.zip', 'format' => 'zip', 'type' => 'src', 'extension' => 'zip']);
        self::$file5 = new Hal_Document_File();
        self::$file5->set(['name' => 'MyFile5.doc', 'path' => self::$rootDoc . '/02/MyFile5.doc', 'format' => 'doc', 'type' => 'src', 'extension' => 'doc']);


    }

    public function test_domain2collection()
    {
        $this->assertEquals('cs', Hal_Arxiv::domain2collection('info.info-mm'));
        $this->assertEquals('math', Hal_Arxiv::domain2collection('math.math-na'));
        $domain = 'math.math-mp';
        $domain = Hal_Arxiv::transformArxivCode($domain);
        $this->assertEquals('physics', Hal_Arxiv::domain2collection($domain)); // PAS D'accord avec le resultat!!!
        $this->assertEquals('physics', Hal_Arxiv::domain2collection('phys.cond.cm-s'));
        $this->assertEquals('physics', Hal_Arxiv::domain2collection('phys.phys.phys-geo-ph'));
        $this->assertEquals('physics', Hal_Arxiv::domain2collection('nlin.nlin-si'));
        $this->assertEquals('physics', Hal_Arxiv::domain2collection('phys.qphy'));
        $this->assertEquals('q-bio', Hal_Arxiv::domain2collection('sdv.bbm.bs'));
        //$this -> assertEquals('eess'   , Hal_Arxiv::domain2collection('info.info-ts'));
        //$this -> assertEquals('eess'   , Hal_Arxiv::domain2collection('spi.signal'));
    }

    /**
     * @dataProvider provide_getDomains2ArxivCategories
     * @param $domains
     * @param $result
     */
    public function test_getDomains2ArxivCategories($domains, $result)
    {
        $this->assertEquals($result, Hal_Arxiv::getDomains2ArxivCategories($domains));

    }

    /**
     * @return array
     */
    public function provide_getDomains2ArxivCategories()
    {
        return [
            'mbio.b-mn' => [
                ['info.info-sd', 'info.info-cg'],
                ['cs.CG' => 'Computational Geometry [cs.CG]', 'cs.SD' => 'Sound [cs.SD]']
            ],
            'math.math-mp' => [
                ['math.math-mp', 'math.math-ca'],
                ['math-ph' => 'Mathematical Physics [math-ph]', 'math.CA' => 'Classical Analysis and ODEs [math.CA]']
            ],
            'phys.mphy' => [
                ['phys.mphy', 'phys.meca.acou'],
                ['math-ph' => 'Mathematical Physics [math-ph]', 'physics.class-ph' => 'Acoustics [physics.class-ph]']
            ],
            'info.info-bi' => [
                ['info.info-bi', 'sdv.bc.ic'],
                ['q-bio.QM' => 'Quantitative Methods [q-bio.QM]', 'q-bio.CB' => 'Cell Behavior [q-bio.CB]']
            ],
            'sdv.bibs' => [
                ['sdv.bibs', 'phys.hthe'],
                ['q-bio.QM' => 'Quantitative Methods [q-bio.QM]', 'hep-th' => 'High Energy Physics - Theory [hep-th]']
            ],
        ];

    }

    /**
     * @deprecated
     * @see Hal_Transfert_Arxiv_Test
     */
    public function bad_test_getSwordInfo()
    {
        /** @var Hal_Document|PHPUnit_Framework_MockObject_MockObject $document */
        $document = $this->createMock(Hal_Document::class);
        // Le pdf par default du document est file1 pour tous les tests suivants
        $document->method('getDefaultFile')->willReturn(self::$file1);
        // cas pas de fichier source
        $this->assertEquals(
            ['setFile' => self::$rootDoc . '/01/Chapter.pdf', 'setHeaders' => 'Content-Type: application/pdf'],
            Hal_Arxiv::getSwordInfo($document, [])
        );
        // Cas ou fichier source n'est pas latex, on recupere le pdf principal du document
        $this->assertEquals(
            ['setFile' => self::$rootDoc . '/01/Chapter.pdf', 'setHeaders' => 'Content-Type: application/pdf'],
            Hal_Arxiv::getSwordInfo($document, [self::$file5])
        );
        // Cas plusieurs fichier source: on va creer un zip
        $zipfile = PATHTEMPDOCS . 'arxiv-0.zip';
        $this->assertEquals(
            ['setFile' => $zipfile, 'setHeaders' => 'Content-Type: application/zip'],
            Hal_Arxiv::getSwordInfo($document, [self::$file2, self::$file3, self::$file4, self::$file5])
        );
        $this->assertFileExists($zipfile);
    }

    /**
     * @dataProvider provide_send
     * @param Hal_Document $document
     * @param $result
     * @deprecated
     * @see          Hal_Transfert_Arxiv_Test
     */
    public function bad_test_send($document, $result)
    {
        // DANGER FONCTION SEND A APPELLER
        // Pour l'instant Test shunter->

        // Premier test pour verifier le fonctionnement de la sous classe
        $this->assertEquals(['test'], $document->getDomains());
        $response = Hal_Arxiv::send($document);

        //verification structure de la reponse
        $this->assertArrayHasKey('alternate', $response);
        $this->assertArrayHasKey('result', $response);
        $this->assertArrayHasKey('reason', $response);
        $this->assertArrayHasKey('edit', $response);
        // verification de valeur escomptee
        $this->assertEquals($result['result'], $response['result']);
        $this->assertEquals($result['reason'], $response['reason']);
        $this->assertRegExp($result['regexp-alternate'], $response['alternate']);
        $this->assertRegExp($result['regexp-edit'], $response['edit']);

        $tracking_info = Hal_Arxiv_TrackingInfo::getTrackingInfo($response['alternate']);
        $submitId = $tracking_info->get_submissionId();
        $this->assertRegExp("/\d+/", $submitId);
        Hal_Arxiv::deleteSubmission($submitId);

    }

    /**
     * @return array
     * @uses test_send
     * @deprecated
     * @see  Hal_Transfert_Arxiv_Test
     */
    public function provide_send()
    {
        /* Prepare Document Object for testing */
        $doc = new Hal_Document_ForTest();
        $xmlfile = __DIR__ . '/../../ressources/test_sword_arxivOk.xml';
        $contentFile = file_get_contents($xmlfile);

        $content = new DOMDocument();
        $content->loadXML($contentFile);

        $doc->loadFromTEI($content);
        $doc->setDocid(1010103, false);
        $doc->setFormat(Hal_Document::FORMAT_FILE);
        $doc->setContributorFromArray(['uid' => 1, 'fullname' => 'B Marmol', 'email' => 'Bruno.Marmol@nowhere.com', 'lastname' => 'Marmol', 'firstname' => 'Bruno']);
        $doc->setSubmittedDate();
        foreach ($doc->getFiles() as $file) {
            $path = $file->getPath();
            $file->setPath(PATHDOCS . '01/01/01/03/' . $path);
        }

        $metas = $doc->getHalMeta();

        $metas->setMeta('domain', ['test'], 'Hal tests', 0);
        self::$doc_OnHold[0] = $doc;

        return [
            1 => [self::$doc_OnHold[0], ['result' => 'WARN', 'reason' => 'On hold sur ArXiv', 'regexp-alternate' => '/https?:/', 'regexp-edit' => '|https?://arxiv.org/sword-app/edit/\d*.atom|']]
        ];
    }

    /**
     * @return array
     * @uses  test_attendreArxiv2 Provider for
     */
    public function provide_attendreArxiv()
    {
        return [
            'Ok' => [1, 'https://arxiv.org/sword-app/edit/XXXXX.atom', 'https://arxiv.org/resolve/app/11110101', 10,
                ['arxivid' => '0704.2929', 'result' => 'OK', 'alternate' => 'https://arxiv.org/resolve/app/11110101', 'edit' => 'https://arxiv.org/sword-app/edit/XXXXX.atom', 'reason' => '']],
            // Transformation de http en https sur alternate
            // Bon il est plus submitted maintenant... Il est accepte!
            'Submitted: si Fail: Modifier le numero (resolve/app) pour un Submitted' => [2, 'https://arxiv.org/sword-app/edit/XXXXX.atom', 'https://arxiv.org/resolve/app/17070248', 10,
                ['result' => 'OK', 'alternate' => 'https://arxiv.org/resolve/app/17070248', 'edit' => 'https://arxiv.org/sword-app/edit/XXXXX.atom', 'reason' => '']],
            // Bon il est plus submitted maintenant... Il est accepte!
            'Submitted (V2 donc il y a un arxivId en plus: si Fail: Modifier le numero (arxivId et resolve/app) pour un Submitted' => [2, 'https://arxiv.org/sword-app/edit/XXXXX.atom', 'https://arxiv.org/resolve/app/17050128', 10,
                ['arxivid' => '1705.02191', 'result' => 'OK', 'alternate' => 'https://arxiv.org/resolve/app/17050128', 'edit' => 'https://arxiv.org/sword-app/edit/XXXXX.atom', 'reason' => '']],
            'On Hold: si Fail,modifier le numero pour un On Hold' => [3, 'https://arxiv.org/sword-app/edit/XXXXX.atom', 'https://arxiv.org/resolve/app/17070260', 10,
                ['result' => 'WARN', 'reason' => 'On hold sur ArXiv', 'alternate' => 'https://arxiv.org/resolve/app/17070260', 'edit' => 'https://arxiv.org/sword-app/edit/XXXXX.atom']],
            //'incomplete' => [4, '','', 1,
            //   []],
        ];
    }


    public function test_subject2archive()
    {
        $testArray = [
            'info.info-mm' => 'cs',
            'math.math-na' => 'math',
            'math.math-mp' => 'physics',
            'phys.cond.cm-s' => 'physics',
            'nlin.nlin-si' => 'physics',
            'spi.signal' => 'eess',
            'info.info-ts' => 'eess',
            'sdv.bbm.bs' => 'q-bio',
            'phys.phys.phys-geo-ph' => 'physics',
            'phys.qphy' => 'physics'


        ];
        $haldomain = array_keys($testArray);

        $arxibSubects = Hal_Arxiv::haldomaines2arxivsubjects($haldomain);
        foreach ($testArray as $domain => $expected) {
            $this->assertEquals($expected, Hal_Arxiv::subject2archive($arxibSubects[$domain]));
        }
    }

    public function test_haldomaines2arxivsubjects() {
        $this->assertEquals(['info.info-cv' => 'cs.CV'], Hal_Arxiv::haldomaines2arxivsubjects([ 'info.info-CV' ] ));
    }
}