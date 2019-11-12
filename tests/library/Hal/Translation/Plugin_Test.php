<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 02/08/17
 * Time: 14:23
 */

class Tanslation_Plugin_Test extends PHPUnit_Framework_TestCase
{
    public function test_getAvalaibleLanguages() {
        $this -> assertEquals(['fr','en', 'es', 'eu'], Hal_Translation_Plugin::getAvalaibleLanguages());
    }

    public function test_object() {
        $plugin = new Hal_Translation_Plugin();
        Hal_Translation_Plugin::initLanguages();
        $langs = Zend_Registry::get('languages');
        $this -> assertEquals(['fr','en', 'es', 'eu'], $langs);
    }

    public function test_translator()
    {
        // Put By Bootstrap
        /** @var Zend_Translate_Adapter_Array $translator */
        $translator = Zend_Registry::get('Zend_Translate');
        $this->assertEquals('', $translator->translate(''));
        $this->assertEquals('Abracadabra', $translator->translate('Abracadabra'));
        $this->assertEquals('Domaine', $translator->translate('Domaine'));
        $this->assertEquals('Intitulé texte', $translator->translate('metadatalist_metaLabel_t'));
        $this->assertEquals('Métadonnées', $translator->translate('ref_metadata'));

        $translator -> setLocale('en');
        $this->assertEquals('', $translator->translate(''));
        $this->assertEquals('Abracadabra', $translator->translate('Abracadabra'));
        $this->assertEquals('Subject field', $translator->translate('Domaine'));
        // Not translation Yet!!!!  Hope those test will soon fail!
        $this->assertEquals('Intitulé texte', $translator->translate('metadatalist_metaLabel_t'));
        $this->assertEquals('Métadonnées', $translator->translate('ref_metadata'));

    }
}