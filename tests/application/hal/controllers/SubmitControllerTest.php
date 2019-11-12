<?php
/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 09/10/17
 * Time: 11:15
 */

use \PHPUnit\Framework\TestCase;


class SubmitControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function setUp(){
        Zend_Registry::set('Zend_Locale', 'fr');
    }

    public function testMetaValid()
    {
        $document = new Hal_Document();

        $document->setTypdoc('ART');
        $document->setMetas(['language'=>'fr', 'title'=>['fr'=>'test'], 'domain'=>['shs'], 'journal'=>'1', 'date'=>'2017']);


        self::assertEquals(true, Hal_Document_Validity::isValidMeta ($document));


    }
}