<?php
/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 06/10/17
 * Time: 11:42
 */

class Hal_Submit_Step_Meta_Test extends PHPUnit_Framework_TestCase
{

    /**
     * @param $result
     * @param $data
     *
     * @dataProvider provideMergeInterDomains
     */
    public function testMergeInterDomains($result, $data)
    {
        self::assertEquals($result, Hal_Document_Meta_Domain::mergeInterDomains($data));

    }

    public function provideMergeInterDomains()
    {
        return [
          'Merge qui se passe bien' => [['language'=>'fr', 'domain' => ['sde', 'sbe.info', 'sif', 'plo.ju']], ['language'=>'fr', 'domain'=>['sde', 'sbe.info'], 'domain_inter'=>['sif', 'plo.ju']]],
          'Merge sans domain' => [['language'=>'fr', 'domain' => ['sif', 'plo.ju']], ['language'=>'fr', 'domain_inter'=>['sif', 'plo.ju']]],
          'Merge sans domain inter' => [['language'=>'fr', 'domain' => ['sde', 'sbe.info']], ['language'=>'fr', 'domain'=>['sde', 'sbe.info']]],
          'Merge sans domain inter ni domain' => [['language'=>'fr'], ['language'=>'fr']],
          'Merge sans données' => [[], []]
        ];
    }

    /**
     *
     *
     * @param $result
     * @param $data
     *
     * @dataProvider provideExplodeInterDomains
     */
    public function testExplodeInterDomains($result, $data)
    {
        $domainList = [ 'sde' => ['sde.bio' => ['sde.bio.che', 'sde.bio.bib'], 'sde.mac'], 'sbe' => ['sbe.truc'], 'shs.langue', 'shs.truc'=>['shs.truc.blob', 'shs.truc.gno']];

        $form = new Zend_Form();
        $thesaurus = new Ccsd_Form_Element_Thesaurus('domain');
        $thesaurus->setData($domainList);
        $form->addElement($thesaurus);

        self::assertEquals($result, Hal_Document_Meta_Domain::explodeInterDomains($data, $form));
    }

    public function provideExplodeInterDomains()
    {
        return [
            'Triple niveaux de domaines' => [['domain'=> ['sbe', 'sde.bio.che', 'sde.mac'], 'domain_inter' => ['sdi.gui', 'spo']], ['domain'=>['sbe', 'sdi.gui', 'spo', 'sde.bio.che', 'sde.mac']]],
            'Sous-domain à la racine' => [['domain'=> ['shs.truc']], ['domain'=> ['shs.truc']]],
            'Sous-sous domaine avec un sous-domaine à la racine' => [['domain'=> ['shs.truc.blob']], ['domain'=> ['shs.truc.blob']]],
            'Liste vide' => [[], []]
        ];
    }
}