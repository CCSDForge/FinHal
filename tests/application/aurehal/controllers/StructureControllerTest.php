<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 01/09/17
 * Time: 13:30
 */
class StructureControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function setUp()
    {
        // Comprends pas pourquoi il faut cela!!!
        $this->getFrontController()->setControllerDirectory(APPLICATION_PATH . '/controllers');
        $this->getFrontController()->getDispatcher()->setControllerDirectory(APPLICATION_PATH . '/controllers');
        $halUser = Hal_User::createUser(401900);
        Hal_Auth::setIdentity($halUser);
    }

    public function testIndexAction()
    {
        $this->dispatch('/structure/index');
        /** @var SearchController $controller */
        $this->assertController('structure');
        $this->assertAction('index');
        // $controller = new StructureController($this ->getRequest(), $this->getResponse());
        $response = $this->getResponse();
        $this->assertRegExp('/Consultation des structures de recherche/', $response->getBody());

    }
    public function testBrowseAction()
    {
        $this->request->setMethod('POST')
            ->setPost(array(
                'critere' => 'name_s:INRIA',
            ));
        $this->dispatch('/structure/browse');
        /** @var SearchController $controller */
        $this->assertController('structure');
        $this->assertAction('browse');
        // $controller = new StructureController($this ->getRequest(), $this->getResponse());
        $response = $this->getResponse();
        $this->assertRegExp('|http://aurehal-local.ccsd.cnrs.fr/structure/read/id/300009|', $response->getBody());

    }
}