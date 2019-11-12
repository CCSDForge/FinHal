<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 21/09/18
 * Time: 10:06
 */
class LinkExt_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider loadProvider
     * @param string[] $res
     * @param string $id
     */
    public function testLoad($id, $res)
    {
        $url = $res['url'];
        $type = $res['type'];
        $this->assertEquals($type, Hal_LinkExt::id2type($id));

        $linkextObj = Hal_LinkExt::load($id);
        $this -> assertEquals($url, $linkextObj->getUrl());
        $this -> assertEquals($type, $linkextObj-> getIdtype());
        if (array_key_exists('doitype', $res)) {
            $doitype = $res['doitype'];
            $this -> assertEquals($doitype, $linkextObj->getIdSite());
        }
    }

    /**
     * provider pour testLoad et testRetreiveUrl
     * @return array
     */
    public function loadProvider() {
        return [
            'test DOI' => [ '10.5194/acp-13-6921-2013', [ 'url' => 'https://doi.org/10.5194/acp-13-6921-2013',  'type' => Hal_LinkExt::TYPE_DOI, 'doitype' => Hal_LinkExt::DOITYPE_OTHER]],
            'test Arxiv' =>  [ 'cond-mat/0211687' ,     [ 'url' => 'http://arxiv.org/pdf/cond-mat/0211687',  'type' => Hal_LinkExt::TYPE_ARXIV]],
            'test PMC'=> [ 'PMC4914967' ,               [ 'url' => 'https://www.ncbi.nlm.nih.gov/pmc/articles/PMC4914967/pdf',   'type' => Hal_LinkExt::TYPE_PMC]],
           // 'test other DOI' => [ 'abc' ,              [ 'url' => '' , 'type'=> '']],
        ];
    }

    /**
     * @param $id
     * @param $res
     * @dataProvider loadProvider
     */
    public function testRetreiveUrl($id, $res) {
        $url = $res['url'];
        $idtype = Hal_LinkExt::id2type($id);
        $linkobj = new Hal_LinkExt($idtype, $id, '');
        $linkobj-> retreiveUrl();
        $this->assertEquals($url, $linkobj->getUrl());
    }
}