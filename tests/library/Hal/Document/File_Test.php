<?php
/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 26/07/17
 * Time: 11:07
 */

class Hal_Document_File_Test extends PHPUnit_Framework_TestCase
{

    /**
     * @param $filename
     * @param $result
     * @dataProvider provideget_PDF_info
     */
    public function testget_PDF_info($filename, $result)
    {
        $actual = Hal_Document_File::get_PDF_info($filename);
        self::assertEquals($result, $actual);
    }
    /**
     * @return array
     */
    public function provideget_PDF_info() {
        return [
            '1'=> [RESSOURCESDIR . '/FR.pdf', ['Creator' => 'TeX',
                                               'Producer' => 'pdfTeX-1.40.10',
                                               'Pages' => '7',
                                               'Encrypted' => 'no',
                                               'PDF version' => '1.4',
                                               'Fonts' => [ 'Type 1' => 1,
                                                            'TrueType' => 1
                                               ] ]],
            '2'=> [RESSOURCESDIR . '/ART.pdf', [ 'Creator' => 'LaTeX with hyperref package',
                                                 'Producer' => 'medialab',
                                                 'Pages' => '33',
                                                 'Encrypted' => 'no',
                                                 'PDF version' => '1.4',
                                                 'Fonts' => [ 'Type 1' => 1,
                                                              'Type 1C' => 1,
                                                              'Type 3' => 1,] ] ],
            '3'=> [RESSOURCESDIR . '/Test.pdf', [ 'Creator' => 'HAL',
                                                  'Producer' => 'PDFLaTeX',
                                                  'Pages' => '12',
                                                  'Encrypted' => 'no',
                                                  'PDF version' => '1.7',
                                                  'Fonts' => [ 'Type 1' => 1,
                                                               'Type 1C' => 1] ]],
        ];
    }

    /**
     * @param $res
     * @param $aut1
     * @param $aut2
     *
     * @dataProvider provideMergeAuthors
     */
    public function testMerge2Authors($res, $a1, $a2)
    {
        $aut1 = new Hal_Document_Author();
        $aut2 = new Hal_Document_Author();

        $aut1->setLastname($a1['lastname']);
        $aut1->setFirstname($a1['firstname']);
        $aut1->setOthername($a1['othername']);
        $aut1->setEmail($a1['email']);

        $aut2->setLastname($a2['lastname']);
        $aut2->setFirstname($a2['firstname']);
        $aut2->setOthername($a2['othername']);
        $aut2->setEmail($a2['email']);

        $aut1->mergeAuthor($aut2);

        self::assertEquals($res['lastname'], $aut1->getLastname());
        self::assertEquals($res['firstname'], $aut1->getFirstname());
        self::assertEquals($res['othername'], $aut1->getOthername());
        self::assertEquals($res['email'], $aut1->getEmail());
    }

    public function provideMergeAuthors()
    {
        return [
            'Bon merge'=> [['lastname' => "Doe", 'firstname' => 'Smith', 'othername' => 'John', 'email' => 'john.doe@gmail.com'], ['lastname' => "Doe", 'firstname' => 'S', 'othername' => '', 'email' => 'john.doe@gmail.com'], ['lastname' => "Doe", 'firstname' => 'Smith', 'othername' => 'John', 'email' => '']]
        ];
    }

}