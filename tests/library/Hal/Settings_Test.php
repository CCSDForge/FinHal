<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 01/02/17
 * Time: 10:55
 */

class Hal_Settings_Test extends PHPUnit\Framework\TestCase
{
    public function testGetFileTypes()
    {
        $types = Hal_Settings::getFileTypes('REPORT', 'doc');

        $this->assertEquals(['src', 'annex'], array_values($types));
    }

    /**
     * @param $portail
     * @param $result
     *
     * @dataProvider provideTypdocsSelect
     */
    public function testGetTypdocsSelect($portail, $result)
    {
        $typdocs = Hal_Settings::getTypdocsSelect($portail);

        $this->assertEquals($result, $typdocs);
    }

    /**
     * provider for @see testGetTypdocsSelect
     * @return array
     */
    public function provideTypdocsSelect()
    {
        return [
            "Types de document pour le portail HAL" => [null,
                [   ''=>'',
                    'typdoc_typdoc_1'=>['ART'=>'typdoc_ART', 'COMM'=>'typdoc_COMM', 'POSTER'=>'typdoc_POSTER', 'OUV'=>'typdoc_OUV', 'COUV'=>'typdoc_COUV', 'DOUV'=>'typdoc_DOUV', 'PATENT'=>'typdoc_PATENT', 'OTHER'=>'typdoc_OTHER'],
                    'typdoc_typdoc_2'=>['UNDEFINED' => 'typdoc_UNDEFINED', 'REPORT' => 'typdoc_REPORT'],
                    'typdoc_typdoc_3'=>['THESE' => 'typdoc_THESE', 'HDR' => 'typdoc_HDR', 'LECTURE' => 'typdoc_LECTURE'],
                    'typdoc_typdoc_4'=>['IMG' => 'typdoc_IMG', 'VIDEO' => 'typdoc_VIDEO', 'SON' => 'typdoc_SON', 'MAP' => 'typdoc_MAP', 'SOFTWARE' => 'typdoc_SOFTWARE']
                ]
            ]
        ];
    }

    public function testgetTypdocsAvailable() {
        $this ->assertEquals(
            ['ART','COMM','POSTER','OUV','COUV','DOUV','PATENT','OTHER', 'UNDEFINED', 'REPORT', 'THESE', 'HDR', 'LECTURE','IMG', 'VIDEO', 'SON', 'MAP', 'SOFTWARE'],
            Hal_Settings::getTypdocsAvailable(null));
    }

    public function testgetApplicationVersion() {
        $this->assertRegExp('/^[0-9]+$/', Hal_Settings::getApplicationVersion());
    }

    public function testgetCoreMetas() {
        $metas = Hal_Settings::getCoreMetas();
        $this->assertContains('title', $metas);
        $this->assertNotContains('inra_noSpecia', $metas);
    }
}