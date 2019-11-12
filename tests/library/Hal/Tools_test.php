<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 09/03/17
 * Time: 16:52
 */

/**
 * Class TestTranslationObject: on simule un object avec l'interface de traduction
 */
class TestTranslationObject {
    private $t = [ 'fr'=> "traduction_fr", 'en' => 'traduction_en' ];

    /** Seule fonction necessaire pour l'interface
     * @param $lang string
     */
    public function getlang($lang)
    {
        return isset($this->t[$lang]) ? $this->t[$lang] :'';
    }
}

/**
 * Classe de test
 * */
class Hal_Tools_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provideArray
     * @param $translations
     * @param $lang string
     * @param $preferred_lang_array string[]
     * @param $res string
     */
    public function testGetbylang($translations, $lang, $preferred_lang_array, $res)
    {
        $this -> assertEquals($res, Hal_Tools::getbylang($translations, $lang, $preferred_lang_array));
    }

    /** Data provider  */
    public function provideArray()
    {
        return [
            'Test 1:' => [ ['fr' => 'libelle fr', 'en' => 'libelle en'], 'fr', null, 'libelle fr'], // demande fr
            'Test 2:' => [ ['fr' => 'libelle fr', 'en' => 'libelle en'], 'en', null, 'libelle en'], // demande en
            'Test 3:' => [ ['fr' => 'libelle fr', 'en' => 'libelle en'], 'ru', null, 'libelle en'], // langage demande absent pas de priorite
            'Test 4:' => [ ['fr' => 'libelle fr', 'en' => 'libelle en'], 'ru', ['en','ru','fr'], 'libelle en'],  // premiere priorite
            'Test 5:' => [ ['fr' => 'libelle fr', 'en' => 'libelle en'], 'ru', ['it','ru','fr'], 'libelle fr'],  // derniere priorite
            'Test 6:' => [ 'libelle fr', 'ru', ['it','ru','fr'], 'libelle fr'],   // juste une chaine, elle est retournee a l'identique
            'Test 7:' => [ ['fr' => 'libelle fr', 'en' => 'libelle en'], null, null, 'libelle en'], // demande lang par defaut
            ];
    }

}