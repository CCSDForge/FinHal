<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 22/08/17
 * Time: 09:09
 */

class BootstrapTest extends PHPUnit_Framework_TestCase
{

    public function testValueOfGlobales() {
        $g = new Globales();
        $this -> assertEquals(false, $g->USE_MAIL);
        $this -> assertEquals(USE_MAIL, $g->USE_MAIL);

        $this -> assertEquals(USE_TRACKER, $g->USE_TRACKER);
        $this -> assertRegExp('|^https?://|' , HAL_URL);
        $this -> assertRegExp('|^https?://|' , AUREHAL_URL);
        $this -> assertRegExp('|^//|' , THUMB_URL);
    }

    public function testView() {
        /** @var Zend_Controller_Action_Helper_ViewRenderer $viewRenderer */
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $this ->assertNotNull($viewRenderer->view);
    }

}