<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 19/09/17
 * Time: 12:33
 */


/** Pour les tests d'envoie reel vers Arxiv, il faut "mocker qq fonctions, on surcharge.  */
class Hal_Document_Transfert_ForTest_SWH extends Hal_Document {

    /** l'originale va dans la base de données chercher test (qui n'existe pas...) */
    public function getSwordCollection() {
        return '1';
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
    public function getCategories()
    {
        return  ['test.dis-nn' => 'test.dis-nn'];
    }

}

class Hal_Transfert_SWH_Test extends PHPUnit_Framework_TestCase
{

    /**
     * @dataProvider provide_send
     * @param Hal_Document $document
     * @param $result
     */
    public function test_send($document, $result) {
        // Premier test pour verifier le fonctionnement de la sous classe
        # $this->assertEquals(['test'], $document-> getDomains());
        $transfert = Hal_Transfert_SoftwareHeritage::transfert($document);
        $response = $transfert->send();
        $transfert->save();
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

        $tracking_info = $transfert->verify_deposit($response['alternate']);
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
        $doc = new Hal_Document_Transfert_ForTest_SWH(1243065,null,0, true, true);
        // Changement de domain/categorie
        $metas=$doc ->getHalMeta();
        $metas -> setMeta('domain', ['test'], 'Hal tests', 0);

        $doc2 =  new Hal_Document_Transfert_ForTest_SWH(1243573,null,0, true, true);

        return [
            'a' => [ $doc2, ['result' => Hal_Transfert_Response::OK, 'reason' => '', 'regexp-alternate' => '/https?:/', 'regexp-edit' => '|https?://.*/1/hal/\d+/metadata|']],
            'b' => [ $doc,  ['result' => Hal_Transfert_Response::OK, 'reason' => '', 'regexp-alternate' => '/https?:/', 'regexp-edit' => '|https?://.*/1/hal/\d+/metadata|']],
        ];
    }
}
