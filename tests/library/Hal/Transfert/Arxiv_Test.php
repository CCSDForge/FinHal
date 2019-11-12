<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 19/09/17
 * Time: 12:33
 */

class Hal_Document_Transfert_ForTest_Arxiv extends Hal_Document {

    /** l'originale utilise Hal_User, on shunte */
    public function setContributorFromArray($userAsArray) {
        $this->_uid = $userAsArray['uid'];
        $this->_contributor['email'] = $userAsArray['email'];
        $this->_contributor['lastname'] = $userAsArray['lastname'];
        $this->_contributor['firstname'] = $userAsArray['firstname'];
        $this->_contributor['fullname'] = $userAsArray['fullname'];
    }

    /** Pas d'originale...
     * TODO: A mettre??? */
    public function setSubmittedDate($date = null) {
        $this->_submittedDate = $date == null ? date('Y-m-d H:i:s') : $date;
    }


}
/** Pour les tests d'envoie reel vers Arxiv, il faut "mocker qq fonctions, on surcharge.  */
class Hal_Transfert_Arxiv_PartialMocker extends Hal_Transfert_Arxiv {

    /** Todo: plus dans DOC  a changer! */
    /** l'originale va dans la base de données chercher test (qui n'existe pas...) */
    public function getSwordCollection() {
        return 'test';
    }

    /** Todo: plus dans DOC  a changer! */
    /** l'originale va dans la base de données chercher test  */
    public function getArxivCategories()
    {
        return  ['test.dis-nn' => 'test.dis-nn'];
    }

}

class Hal_Transfert_Arxiv_Test extends PHPUnit_Framework_TestCase
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

    /**
     * @param $document
     * @param $result
     * @dataProvider provide_canTransfert
     */
    public function canTransfert_test($document, $result) {
        $transfert = new Hal_Transfert_Arxiv_PartialMocker();
        $this -> assertEquals($result, $transfert -> canTransfert($document));
    }

    /**
     * Provider for @see canTransfert_test
     * @return array
     */
    public function provide_canTransfert() {
        $doc1 = new Hal_Document();
        $doc2 = new Hal_Document();
        return [
            [$doc1, true],
            [$doc2, false],
        ];
    }

    /**
     * Temporarely set method as public for being able to test is
     * @param $object
     * @param $methodName
     * @return ReflectionMethod
     */
    public function setPublicMethodForTest($object, $methodName)
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method;
    }

    /**
     * Call a private method of an object
     * @param $object
     * @param $methodName
     * @param $args
     */
    public function invokePrivateMethod($object, $methodName, $args) {
        $method = $this -> setPublicMethodForTest($object, $methodName);
        return $method -> invokeArgs($object, $args);
    }


    public function transfertObj_test()
    {
        $transfert = new Hal_Transfert_Arxiv_PartialMocker(1024, '2017-12345', 'http://localhost/foobar');
        $transfert->save();
        $transfert_copy = new Hal_Transfert_Arxiv_PartialMocker();
        $this -> invokePrivateMethod($transfert_copy, 'load', array(1024));
        $transfert_copy -> setPendingUrl('http://localhost/newfoobar');
        $transfert->save();

        $transfert_copy2= new Hal_Transfert_Arxiv_PartialMocker();
        $this -> invokePrivateMethod($transfert_copy2, 'load', array(1024));

        $this->assertEquals('http://localhost/newfoobar', $transfert_copy2->getPendingUrl());

    }
    /**
     * @dataProvider provide_send
     * @param Hal_Document $document
     * @param $result
     */
    public function test_send($document, $result) {
        // Premier test pour verifier le fonctionnement de la sous classe
        $this->assertEquals(['test'], $document-> getDomains());
        $transfert = Hal_Transfert_Arxiv_PartialMocker::transfert($document);
        $response = $transfert->send();

        //verification structure de la reponse
        $this->assertArrayHasKey('alternate',$response );
        $this->assertArrayHasKey('result'   ,$response );
        $this->assertArrayHasKey('reason'   ,$response );
        $this->assertArrayHasKey('edit'     ,$response );
        // verification de valeur escomptee
        $this->assertEquals($result['result'], $response['result']);
        $this->assertEquals($result['reason'], $response['reason']);
        $this->assertRegExp($result['regexp-alternate'], $response['alternate']);
        $this->assertRegExp($result['regexp-edit'], $response['edit']);

        $tracking_info = Hal_Arxiv_TrackingInfo::getTrackingInfo($response['alternate']);
        $submitId = $tracking_info -> get_submissionId();
        $this->assertRegExp("/\d+/", $submitId);
        $transfert->deleteSubmission($submitId);

    }

    /**
     * @return array
     * @uses test_send
     */
    public function provide_send() {
        /* Prepare Document Object for testing */
        $doc = new Hal_Document_Transfert_ForTest_Arxiv();
        $xmlfile = __DIR__.'/../../../ressources/test_sword_arxivOk.xml';
        $contentFile = file_get_contents($xmlfile);

        $content = new DOMDocument();
        $content->loadXML($contentFile);

        $doc->loadFromTEI($content);
        $doc->setDocid(1010103,false);
        $doc->setVersion(1);
        $doc->setFormat(Hal_Document::FORMAT_FILE);
        $doc->setContributorFromArray(['uid' => 1, 'fullname' => 'B Marmol', 'email' => 'Bruno.Marmol@nowhere.com', 'lastname' => 'Marmol', 'firstname' => 'Bruno']);
        $doc -> setSubmittedDate();
        foreach ($doc->getFiles() as $file) {
            $path = $file -> getPath();
            $file ->setPath(PATHDOCS . '01/01/01/03/' . $path);
        }

        $metas=$doc ->getHalMeta();

        $metas -> setMeta('domain', ['test'], 'Hal tests', 0);
        self::$doc_OnHold[0] = $doc;

        return [
            1 => [ self::$doc_OnHold[0], ['result' => Hal_Transfert_Response::WARN, 'reason' => 'On hold sur ArXiv', 'regexp-alternate' => '/https?:/', 'regexp-edit' => '|https?://arxiv.org/sword-app/edit/\d*.atom|']]
        ];
    }

    public function test_delete() {
        $t = new Hal_Transfert_Arxiv(30);
        $this->invokePrivateMethod($t, "load", array(30));
        $t->save();

        $t->delete();

        $t = new Hal_Transfert_Arxiv(30);
        $this->assertFalse($this->invokePrivateMethod($t, "load", array(30)));
    }

    public function test_changeDocid() {
        // Initialisation: On veut un document 30 et pas de 31
        $t = new Hal_Transfert_Arxiv(30);
        $this->invokePrivateMethod($t, "load", array(30)); // des fois qu'il existe...
        $cible = new Hal_Transfert_Arxiv(31);
        if ($this->invokePrivateMethod($cible, "load", array(31))) {
            $cible->delete();
        }
        $testUrl = "http://test.de/transfert/doc30";
        $t->setPendingUrl($testUrl);
        $t->save();
        // On test le chgment maintenant
        Hal_Transfert_Arxiv::changeDocid(30,31);
        $t = new Hal_Transfert_Arxiv(30);
        $this->assertFalse($this->invokePrivateMethod($t, "load",  array(30)));  // le document 30 ne doit plus exister
        $t = new Hal_Transfert_Arxiv(31);
        $this->assertTrue($this->invokePrivateMethod($t, "load",  array(31)));     // Le document 31 existe: transfert reussi
        $this->assertEquals($testUrl, $t->getPendingUrl());

    }
}
