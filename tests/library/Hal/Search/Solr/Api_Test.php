<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 21/03/18
 * Time: 16:21
 */

/**
 * Class Hal_Search_Solr_Api_Test
 */
class Hal_Search_Solr_Api_Test extends PHPUnit_Framework_TestCase
{
/**
     * @param string $s
     * @param int $order
     * @param string $res
     * @dataProvider provideToString
     */
    public function testFormatOuputAsRTF($file, $res) {
        $bibarray = file_get_contents($file);
        $res = file_get_contents($res);
        $rtf = Hal_Search_Solr_Api::formatOutputAsRTF3($bibarray);
        $rtfOrig = Hal_Search_Solr_Api::formatOutputAsRTF($bibarray);
        file_put_contents('/tmp/out.rtf', $rtf);

        #$this -> assertEquals($res, $rtf);

        // $rtf2 = Hal_Search_Solr_Api::formatOutputAsRTF2($bibarray);
        file_put_contents('/tmp/outOrig.rtf', $rtfOrig);
        //$this -> assertEquals($res, $rtf2);

    }

    /**
     * Data provider
     */
    public function provideToString()
    {
        return [
            1 => [
                RESSOURCESDIR . '/rtf/serialBiblio.php',
                RESSOURCESDIR . '/rtf/serialBiblio.rtf',
                ],
        ];
    }

}