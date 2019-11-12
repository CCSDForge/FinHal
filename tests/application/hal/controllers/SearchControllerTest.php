<?php

use \PHPUnit\Framework\TestCase;

function exception_error_handler($severity, $message, $file, $line) {

    throw new ErrorException($message, 0, $severity, $file, $line);
}

class SearchControllerTest extends Zend_Test_PHPUnit_ControllerTestCase
{
    public function setUp() {
        // Comprends pas pourquoi il faut cela!!!
        $this->getFrontController()->setControllerDirectory(APPLICATION_PATH . '/controllers');
        $this->getFrontController()->getDispatcher()->setControllerDirectory(APPLICATION_PATH . '/controllers');
    }



    public function  testgetExportMetadataFields() {
        //$front = $this->getFrontController();
        // If we dont put that line, we get: No default module defined for this application
        // Not good, why is the application not correctly initiated?
        //$front->setControllerDirectory(APPLICATION_PATH ."/controllers");

//        $this -> dispatch('/');
        $this -> dispatch('/search/index');
        /** @var SearchController $controller */
        $this -> assertController('search');
        $controller = new SearchController($this ->getRequest(), $this->getResponse());

        $this -> assertAction('index');
        $this -> assertEquals('fr', $controller-> get_lang_code('francais') );
        $this -> assertEquals('en', $controller-> get_lang_code('0francais') );
        $this -> assertEquals('tu', $controller-> get_lang_code('turk') );
        $this -> assertEquals('en', $controller-> get_lang_code('9') );
        $this -> assertEquals('vr', $controller-> get_lang_code('Vraiment, il exagere') );

        $this -> assertEquals('Titre', $controller-> get_translated_field('title', 'Normal'));
        $this -> assertEquals('Titre en francais', $controller-> get_translated_field('title', 'Normal', 'francais'));
        $this -> assertEquals('Titre en français', $controller-> get_translated_field('title', 'Normal', 'lang_fr'));
        $this -> assertEquals('Titre en russe', $controller-> get_translated_field('title', 'Normal', 'lang_ru'));

        $t = $controller-> getExportMetadataFields();

        $this -> assertArraySubset([
            'authOrganism_s' => "Auteur : Organisme payeur",
            'citationFull_s' => 'Citation complète',
            'af_keyword_s'   => 'Mots-clés en afrikaans',
            'ro_abstract_s'  => 'Résumé en roumain',
        ], $t, true, "\n----------\nTodo: Pb API Solr non maintenu...\n");

        // Changement de langue

    }
}

