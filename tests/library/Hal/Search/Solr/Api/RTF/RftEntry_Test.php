<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 21/03/18
 * Time: 11:33
 */

class Hal_Search_Solr_Api_RTF_RftEntry_Test extends PHPUnit_Framework_TestCase
{

    /**
     * @param string $s
     * @param int $order
     * @param string $res
     * @dataProvider provideToString
     */
    public function testToString($s, $emphases, $order, $res) {
        $rtfEntry = new Hal_Search_Solr_Api_RTF_RtfEntry($s, $order);
        foreach ($emphases as $emphase) {
            $rtfEntry->emphase($emphase);
        }
        $this -> assertRegExp($res, "$rtfEntry");
    }

    /**
     * Data provider
     */
    public function provideToString()
    {
        return [
            1 => [
                "J. A. Ordonez, J. A. Masso, M. P. Marmol, M. Ramos. Contribution à l'étude du fromage \" Roncal \". <i>Le Lait</i>, INRA Editions, 1980, 60 (595_596), pp.283-294. <a target=\"_blank\" href=\"https://hal.archives-ouvertes.fr/hal-00928857\">&#x3008;hal-00928857&#x3009;</a>",
                ["INRA Editions"],
                1,
                '/\\{\\\\u12296\\}  hal \\\\endash \s* 00928857/xs'
                #"/\\ u12296 hal  \\ endash 00928857\\ u12297  /xs",
                # "\\pard\\plain\\s62\\ql\\fi-567\\li567\\sb0\\sa0\\f0\\fs20\\sl240\\slmult1 \\sb60 \\li450\\fi0  [1]\\tab\nJ. A. Ordonez, J. A. Masso, M. P. Marmol, M. Ramos. Contribution {\u224} l\\rquote {\u233}tude du fromage \\rdblquote  Roncal \\rdblquote . {\\i Le Lait}, INRA Editions, 1980, 60 (595_596), pp.283\\endash 294. \\u12296 hal\\endash 00928857\\u12297 \\par\n"]
            ],
            2 => [
                "J. A. Ordonez, J. A. Masso, M. P. Marmol, M. Ramos. Contribution à l'étude du fromage \" Roncal \". <i>Le Lait</i>, INRA Editions, 1980, 60 (595_596), pp.283-294. <a target=\"_blank\" href=\"https://hal.archives-ouvertes.fr/hal-00928857\">&#x3008;hal-00928857&#x3009;</a>",
                ["INRA Editions"],
                1,
                '/\{\\\\u224\}/xs'
                #"/\\ u12296 hal  \\ endash 00928857\\ u12297  /xs",
                # "\\pard\\plain\\s62\\ql\\fi-567\\li567\\sb0\\sa0\\f0\\fs20\\sl240\\slmult1 \\sb60 \\li450\\fi0  [1]\\tab\nJ. A. Ordonez, J. A. Masso, M. P. Marmol, M. Ramos. Contribution {\u224} l\\rquote {\u233}tude du fromage \\rdblquote  Roncal \\rdblquote . {\\i Le Lait}, INRA Editions, 1980, 60 (595_596), pp.283\\endash 294. \\u12296 hal\\endash 00928857\\u12297 \\par\n"]
            ],
        ];
    }

}