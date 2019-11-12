<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 04/08/17
 * Time: 08:18
 */

class rightsControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp() {
        // Comprends pas pourquoi il faut cela!!!
        $this->getFrontController()->setControllerDirectory(APPLICATION_PATH . '/controllers');
        $this->getFrontController()->getDispatcher()->setControllerDirectory(APPLICATION_PATH . '/controllers');
    }

    public function testfoo ()
    {
        // Remove this test when writting your own one
    }

    public function BADtestindexAction () {
        /* redirection vers CAS si pas loggue */
        $this -> dispatch('/rights');
        $this->assertController('rights');
        $this->assertAction('index');
        $response = $this -> getResponse();
        $this -> assertRegExp('/sitemapindex/', $response -> getBody());
    }

    public function BAD2testIndexAuthAction () {
        // L'url n'est de toute facon pas dispo...
        $halUser = Hal_User::createUser(401900);
        Hal_Auth::setIdentity($halUser);
        $this -> dispatch('/rights/index');
        $this->assertController('rights');
        $this->assertAction('index');
        $response = $this -> getResponse();
        $this -> assertRegExp('/The API is far more efficient/', $response -> getBody());
    }
}