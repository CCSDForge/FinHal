<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 04/08/17
 * Time: 08:18
 */
/** Todo: Hum! It is more a SubmitControllerTest, maybe we must move that in application/controllers */

class View_Script_Submit_AjaxgetidentifierTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp() {
        $this->getFrontController()->setControllerDirectory(APPLICATION_PATH . '/controllers');
        $this->getFrontController()->getDispatcher()->setControllerDirectory(APPLICATION_PATH . '/controllers');
        $halUser = Hal_User::createUser(401900);
        Hal_Auth::setIdentity($halUser);
    }

    /**
     * Add the header to be recognize as Ajax request
     * @return Zend_Controller_Request_HttpTestCase
     */
    public function setAjaxRequest() {
        $request = $this -> getRequest();
        $request -> setHeader('X_REQUESTED_WITH', 'XMLHttpRequest');
        return $request;
    }


    /**
     * Pas d'identifiant
     * @param array $postArgs
     */
    public function ViewOutputWithNoId($postArgs) {
        $request = $this -> setAjaxRequest();
        $request->setMethod('POST')
            ->setPost($postArgs);
        $this -> dispatch('/submit/ajaxvalidateid'); /** @see SubmitController::ajaxvalidateidAction() */
        $this->assertController('submit');
        $this->assertAction('ajaxvalidateid');
        $response = $this -> getResponse();
        $this -> assertRegExp('/Aucun identifiant transmis/', $response -> getBody());
    }

    /**
     * Identifiant Ok
     * @param array $postArgs
     */
    public function ViewOutputWithId($postArgs) {
        $request = $this -> setAjaxRequest();
        $request->setMethod('POST')
            ->setPost($postArgs);
        $this -> dispatch('/submit/ajaxvalidateid');
        $this->assertController('submit');
        $this->assertAction('ajaxvalidateid');
        $response = $this -> getResponse();
        $this -> assertRegExp('/^$/', $response -> getBody());
    }
    /**
     * Identifiant NOk
     * @param array $postArgs
     */
    public function ViewOutputWithBadId($postArgs) {
        $request = $this -> setAjaxRequest();
        $request->setMethod('POST')
            ->setPost($postArgs);
        $this -> dispatch('/submit/ajaxvalidateid');
        $this->assertController('submit');
        $this->assertAction('ajaxvalidateid');
        $response = $this -> getResponse();
        $this -> assertRegExp('/est pas un .* valide/', $response -> getBody());
    }
    /**
     * @dataProvider provider
     * @param string $testType
     * @param array $postArgs
     */
    public function testViewOutputAjax($testType, $postArgs) {
        switch ($testType) {
            case 'Ok':
                $this -> ViewOutputWithId($postArgs);
                break;
            case 'Nok':
                $this -> ViewOutputWithBadId($postArgs);
                break;
            case 'NoId':
                $this -> ViewOutputWithNoId($postArgs);
                break;
        }
    }

    /**
     * Provide for @see testViewOutputAjax
     * @return array
     */
    public function provider() {
        return [
            'Arxiv1' => [ 'Ok'  , [ 'idtype' => 'ARXIV', 'id' => '1234.12345']],
            'Arxiv2' => [ 'Ok'  , [ 'idtype' => 'ARXIV', 'id' => 'quant-ph/0211192']],
            'Arxiv3' => [ 'NoId', [ 'idtype' => 'ARXIV', 'id' =>'']],
            'Arxiv4' => [ 'Nok' , [ 'idtype' => 'ARXIV' , 'id' =>'arxiv:1234.12345']],
            'Arxiv5' => [ 'NoId', [ 'idtype' => 'ARXIV']],
            // !!!!!!!!!!!!!! Strange no...
            'Arxiv6' => [ 'Ok'  , [ 'idtype' => 'ARXIV', 'id' => 123423465E-5]],
            'DOI1'    => [ 'Nok', [ 'idtype' => 'DOI'  , 'id' => 'doi:10.10.1038/nphys1170']],
            'DOI2'    => [ 'Nok', [ 'idtype' => 'DOI'  , 'id' => 'doi:10.1002/0470841559.ch1 ]']],
            'DOI3'    => [ 'Ok' , [ 'idtype' => 'DOI'  , 'id' => '10.1594/PANGAEA.726855']],
            'DOI4'    => [ 'Ok' , [ 'idtype' => 'DOI'  , 'id' => '10.3866/PKU.WHXB201112303']],
            'PUBMEDCENTRAL1' => ['Ok'  , ['idtype' => 'PUBMEDCENTRAL', 'id' => 'PMC1234567890']],
            'PUBMEDCENTRAL2' => ['Ok'  , ['idtype' => 'PUBMEDCENTRAL', 'id' => 'PMC12']],
            'PUBMEDCENTRAL3' => ['Nok' , ['idtype' => 'PUBMEDCENTRAL', 'id' => '1234']],
            'PUBMEDCENTRAL4' => ['Nok' , ['idtype' => 'PUBMEDCENTRAL', 'id' => ' PMC123']],
            'PUBMEDCENTRAL5' => ['NoId', ['idtype' => 'PUBMEDCENTRAL', 'id' => '']],
            'PUBMEDCENTRAL6' => ['Nok' , ['idtype' => 'PUBMEDCENTRAL', 'id' => 'PMC']],
            ];
    }

    /**
     * Mauvais type d'identifiant
     */
    public function testViewOutputWithBadTypeId() {
        $request = $this -> setAjaxRequest();
        $request->setMethod('POST')
            ->setPost(array(
                'id' => '1234.12345',
                'idtype' => 'IDNONEXIST'
            ));
        $this -> dispatch('/submit/ajaxvalidateid');

        $response = $this -> getResponse();
        $this -> assertRegExp("/Type d'identifiant non reconnu/", $response -> getBody());
    }

    public function testViewOutputAjaxWithoutAjaxHeader() {
        $request = $this -> getRequest();
        $request->setMethod('POST')
            ->setPost(array(
                'id' => '1234.12345',
                'idtype' => 'ARXIV'
            ));
        $this -> dispatch('/submit/ajaxvalidateid');

        $response = $this -> getResponse();
        $this -> assertRegExp("/Ressource manquante/", $response -> getBody());
    }


}