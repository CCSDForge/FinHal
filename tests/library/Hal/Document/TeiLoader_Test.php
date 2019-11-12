<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 04/01/17
 * Time: 09:32
 */

//namespace library\Hal\Document;

defined('SITEID') || define('SITEID', 0);


/**
 * Class Hal_Document_TeiLoader_Test
 */
class Hal_Document_TeiLoader_Test extends PHPUnit_Framework_TestCase
{

    static public $_xmlFiles = [
        __DIR__.'/../../../ressources/test_sword_preprint.xml',
        __DIR__.'/../../../ressources/test_sword_ouvrage.xml',
        __DIR__.'/../../../ressources/changement_sur_date.xml',
    ];

    /**
     * @param string $xmlFile
     * @return Hal_Document_Tei_Loader
     *
     */
    public static function createLoader($xmlFile)
    {
        try {
            $contentFile = file_get_contents($xmlFile);
            $content = new DOMDocument();
            $content->loadXML($contentFile);
            $content->schemaValidate(__DIR__ . '/../../../../library/Hal/Sword/xsd/inner-aofr.xsd');
        } catch (Exception $e) {
            print "BAD";
        }
        return new Hal_Document_Tei_Loader($content);
    }

    /**
     * @param $loader Hal_Document_Tei_Loader
     * @param $result
     *
     * @dataProvider provideLoadMetas
     */
    public function testloadMetas($loader, $result)
    {
        $metadatas = $loader->loadMetas();

        $this->assertEquals($result, $metadatas);
    }

    /** provider for  testloadMetas */
    public function provideLoadMetas()
    {
        return [
            'Test Metas Preprint' => [$this->createLoader(self::$_xmlFiles[0]),
                ['typdoc' => 'UNDEFINED',
                 'title' => ['en' => 'Estimating the risk of nuclear accidents'],
                 'language' => 'en',
                 'keyword' => ['en' => ["Probabilistic Risk Assesment", "Bayesian Analysis", "Nuclear Safety"]],
                 'domain' => ["math.math-mp", "phys.nucl"],
                 'abstract' => ["en" => "We used Bayesian methods to compare the predictions."],
                 'identifier' => ['arxiv' => "1608.08894"],
                 'comment' => "19 pages."]],
            'Test Metas Ouvrage' => [$this->createLoader(self::$_xmlFiles[1]),
                ['typdoc' => 'OUV', 'title' => ['en' => "Modelling Computing Systems"], 'subTitle' => ['en' => "Mathematics for Computer Science"],
                 'language' => 'en',
                 'domain' => ["info.info-mo", "math.math-oc"],
                 'abstract' => ['en' => "This engaging textbook presents the fundamental mathematics."],
                 'isbn' => '978-1-84800-321-7',
                 'eisbn' => "978-1-84800-322-4",
                 'bookTitle' => "Modelling Computing Systems",
                 'publisher' => ["Springer"],
                 'serie' => "Undergraduate Topics in Computer Science",
                 'volume' => "16",
                 'page' => "500",
                 'date' => "2013",
                 'publisherLink' => "http://www.springer.com",
                 'popularLevel' => "0",
                 'audience' => "2"]],
            'Test regression sur Date' => [$this->createLoader(self::$_xmlFiles[2]),
                [
                    'typdoc' => 'COUV',
                    'title' => ['en' => '3D Compression'],
                    'language' => 'en',
                    'domain' => ['info'],
                    'abstract' => ['en' => '3D compression techniques: the 3D coding and decoding schemes for reducing the size of 3D data to reduce transmission time and minimize distortion; '],
                    'localReference' => [3415],
                    'bookTitle' => 'Chapter in "3D Object Processing: Compression, Indexing and Watermarking"',
                    'scientificEditor' => ['Jean-Luc Dugelay, Atilla Baskurt, Mohamed Daoudi6'],
                    'publisher' => ['Wiley'],
                    'page' => '45-86',
                    'date' => '2008-04',
                    'audience' => '2',
                    'popularLevel' => '0',
                    'writingDate' => '2008-04',
                ]
            ]
        ];
    }

    /**
     * @param $loader Hal_Document_Tei_Loader
     * @param array $resultAut
     * @param $resultStruct
     *
     * @dataProvider provideLoadAuthors
     */
    public function testloadAuthors($loader, $resultAut, $resultStruct)
    {
        $autandstructs = $loader->loadAuthorsAndStructures();
        $authors = $autandstructs['authors'];
        $structures = $autandstructs['structures'];

        $this->assertEquals($resultAut, $authors);
        $this->assertEquals($resultStruct, $structures);
    }

    /**
     * @param $last string
     * @param $first string
     * @param $idx int[]
     * @return Hal_Document_Author
     */
    protected static function createAuthor($last, $first, $id)
    {
        $aut = new Hal_Document_Author();
        $aut->setLastname($last);
        $aut->setFirstname($first);
        $aut->setStructidx($id);

        return $aut;
    }

    /** Provider pour testloadAuthors */
    public function provideLoadAuthors()
    {
        $aut1 = self::createAuthor('raju', 's.', [0]);

        $aut2 = self::createAuthor('Moller', 'Faron', [0]);
        $aut3 = self::createAuthor('Struth', 'Georg', [1]);

        return [
            'Test Authors Preprint' => [self::createLoader(self::$_xmlFiles[0]), [$aut1],        [ new Hal_Document_Structure(247160)]],
            'Test Authors Ouvrage'  => [self::createLoader(self::$_xmlFiles[1]), [$aut2, $aut3], [ new Hal_Document_Structure(36731),  new Hal_Document_Structure(205974)]],
        ];
    }

    /**
     * @param $loader Hal_Document_Tei_Loader
     * @param $result
     *
     * @dataProvider provideLoadFiles
     */
    public function testloadFiles($loader, $result)
    {
        $files = $loader->loadFiles();

        for ($i = 0 ; $i < count($files) ; $i++) {
            // Il faut retirer le "path" qui est temporaire
            $array = $files[$i]->toArray();
            unset($array['path']);
            unset($result[$i]['path']);

            // Il faut retirer la date et l'imagette qui ne sont pas fixes
            unset($array['imagetteUrl']);
            unset($result[$i]['imagetteUrl']);
            unset($array['dateVisible']);
            unset($result[$i]['dateVisible']);

            $this->assertEquals($result[$i], $array);
        }
    }

    /** Provider for  testloadFiles */
    public function provideLoadFiles()
    {
        return [
            'Test Authors Preprint' => [$this->createLoader(self::$_xmlFiles[0]),
                [['fileid' => 0,
                  'name' => 'WAC-grame.pdf',
                  'comment' => '',
                  'fileType'=> 'file',
                  'typeAnnex' => '',
                  'fileSource' => 'author',
                  'typeMIME' => 'application/pdf',
                  'imagette' => 0,
                  'imagetteUrl' => '//thumb.ccsd.cnrs.fr/0/small',
                  'extension' => 'pdf',
                  "dateVisible" => date("Y-m-d"),
                  'size' => '0 B',
                  'default' => '1',
                  'defaultAnnex' => false,
                  'md5' => '',
                  'source' => 'author']]],
            'Test Authors Ouvrage' => [$this->createLoader(self::$_xmlFiles[1]), []],
        ];
    }

    /**
     * @param $loader Hal_Document_Tei_Loader
     * @param $result
     *
     * @dataProvider provideLoadRessources
     */
    public function testloadRessources($loader, $result)
    {
        $ress = $loader->loadRessources();

        $this->assertEquals($result, $ress);
    }

    /** Provider for  testloadRessources */
    public function provideLoadRessources()
    {
        return [
            'Test Ressources Preprint' => [$this->createLoader(self::$_xmlFiles[0]), []],
            'Test Ressources Ouvrage' => [$this->createLoader(self::$_xmlFiles[1]), [['URI' => 'bouzin.toto', 'RELATION' => 'machin', 'INFO' => 'bidule']]]
        ];
    }

    /**
     * @param $loader Hal_Document_Tei_Loader
     * @param $result
     *
     * @dataProvider provideLoadCollections
     */
    public function testloadCollections($loader, $result)
    {
        // On fait le test en root
        $col = $loader->loadCollections(1);

        //$this->assertEquals($result, $col);
    }

    /** Provider for  testloadCollections */
    public function provideLoadCollections()
    {
        // ON NE PEUT PAS VRAIMENT TESTER LES COLLECTIONS - Il faudrait pouvoir mocker...
        return [
            'Test Collections Preprint' => [self::createLoader(self::$_xmlFiles[0]), [[]]],
            'Test Collections Ouvrage'  => [self::createLoader(self::$_xmlFiles[1]), []]
        ];
    }
}
