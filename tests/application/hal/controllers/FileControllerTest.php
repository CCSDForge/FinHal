<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 04/08/17
 * Time: 08:18
 */

class FileControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp() {
        // Comprends pas pourquoi il faut cela!!!
        $this->getFrontController()->setControllerDirectory(APPLICATION_PATH . '/controllers');
        $this->getFrontController()->getDispatcher()->setControllerDirectory(APPLICATION_PATH . '/controllers');
    }

    public function testThumbAction () {
        $this->request->setMethod('POST')
            ->setPost(array(
                'docid' => 700002,
              ));
        $this -> dispatch('/file/thumb');
        $this->assertController('file');
        $this->assertAction('thumb');
        $response = $this -> getResponse();
        $this -> assertRegExp('//', $response -> getBody());
    }
}