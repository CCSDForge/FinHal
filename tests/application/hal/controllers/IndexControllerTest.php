<?php

use \PHPUnit\Framework\TestCase;

class IndexControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{

    public function setUp()
    {
        $this->bootstrap = new Zend_Application(APPLICATION_ENV, APPLICATION_INI);

        parent::setUp();
    }

    /** TODO: delete this function when you add test
     *  Its just a placeholder to avoid Warning of phpunit*/
    public function testFoo() {
        $this->assertTrue(true);
    }
}

