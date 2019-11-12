<?php
/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 26/07/17
 * Time: 11:07
 */

class Hal_Document_Author_Test extends PHPUnit_Framework_TestCase
{

    /**
     * @param $result
     * @param $l1
     * @param $f1
     * @param $l2
     * @param $f2
     *
     * @dataProvider provideAuthors
     */
    public function testIsConsideredSameAuthor($result, $l1, $f1, $l2, $f2)
    {
        $aut1 = new Hal_Document_Author();
        $aut2 = new Hal_Document_Author();

        $aut1->setLastname($l1);
        $aut1->setFirstname($f1);

        $aut2->setLastname($l2);
        $aut2->setFirstname($f2);

        self::assertEquals($result, $aut1->isConsideredSameAuthor($aut2));
    }

    public function provideAuthors() {
        return [
            'Initiale'=> [true, 'Dupont', 'Jean', 'Dupont', 'J'],
            'Prenoms complets'=> [true, 'Dupont', 'Jean', 'Dupont', 'Jean'],
            'Prenoms differents'=> [false, 'Dupont', 'Jean', 'Dupont', 'Jacques'],
            'Initiale avec point'=> [true, 'Dupont', 'Jean', 'Dupont', 'J.']
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