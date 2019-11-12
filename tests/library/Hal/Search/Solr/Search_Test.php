<?php
/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 04/05/17
 * Time: 10:05
 */

class Search_Test extends PHPUnit_Framework_TestCase
{

    public function testSearchAffiliation()
    {
        $data = [
            'lastName_t' => "Denoux",
            'firstName_t' => "Sarah",
            'structName_t' => "CCSD",
            //'type_s' => "institution",
            //'country_s' => "France",
            //'producedDate_s' => "2016",
            //'keyword_t' => "informatique",
            //'keyword_t' => "test"
        ];

        Hal_Search_Solr_Search_Affiliation::rechAffiliations($data);
    }

    public function testgetDomains()
    {
        // Il faudrait pouvoir changer le SITEID pour faire des tests corrects sur cette fonction.
        /*define('SITEID', 8);
        $translator = new Zend_Translate(Zend_Translate::AN_ARRAY, PATH_TRANSLATION, null, array(
            'scan'           => Zend_Translate::LOCALE_DIRECTORY,
            'disableNotices' => true
        ));
        Zend_Registry::set('Zend_Translate', $translator);

        $domains = Hal_Search_Solr_Search::getDomainConsultationArray('portal');*/
    }
}