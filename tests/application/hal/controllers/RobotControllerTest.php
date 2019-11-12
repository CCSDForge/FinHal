<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 04/08/17
 * Time: 08:18
 */

class robotControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp() {
        // Comprends pas pourquoi il faut cela!!!
        $this->getFrontController()->setControllerDirectory(APPLICATION_PATH . '/controllers');
        $this->getFrontController()->getDispatcher()->setControllerDirectory(APPLICATION_PATH . '/controllers');
    }

    public function testsitemapAction () {

        $this -> dispatch('/robots/sitemap');
        $this->assertController('robots');
        $this->assertAction('sitemap');
        $response = $this -> getResponse();
        $this -> assertRegExp('/sitemapindex/', $response -> getBody(), "Verifier que " .SPACE . "public/sitemap/sitemap.xml existe.");
        $this -> assertRegExp('|public/sitemap/articles|', $response -> getBody());

    }

    public function testIndexAction () {

        $this -> dispatch('/robots/index');
        $this->assertController('robots');
        $this->assertAction('index');
        $response = $this -> getResponse();
        $this -> assertRegExp('/The API is far more efficient/', $response -> getBody());
        $this -> assertRegExp('/Disallow/', $response -> getBody());
    }
}