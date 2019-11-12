<?php

class Hal_Document_Metadatas_Test extends PHPUnit_Framework_TestCase
{

    /**
     * @param $metas
     * @param $source1
     * @param $source2
     * @param $result
     *
     * @dataProvider provideGetMetasFromSource
     */
    public function testGetMetasFromSource($metas, $source1, $source2, $result)
    {
        $docMetas = new Hal_Document_Metadatas();

        $docMetas->addMetasFromArray($metas, $source1, 0);

        $metas = $docMetas->getMetaKeysFromSource($source2);

        self::assertEquals($metas, $result);
    }

    public function provideGetMetasFromSource()
    {
        return [
            "test simple" => [["title"=>["en"=>"test"], "language"=>"en"], "grobid", "grobid", ["title", "language"]],
            "test avec 2 sources diff" => [["title"=>["en"=>"test"], "language"=>"en"], "grobid", "doi", []]
        ];
    }

    /**
     * @param $docid
     * @param $result
     *
     * @dataProvider provideSave
     */
    public function testsave($docid, $result)
    {
        $docMetas = new Hal_Document_Metadatas();

        // TO DO : test avec keyword !!

        $docMetas->addMetasFromArray($result, "grobid", 0);
        $docMetas->save($docid, 0);
        $docMetas->load($docid);
        $r = $docMetas->getMeta(null);
        unset($r['LINKEXT']);
        self::assertEquals($result, $r);
    }

    public function provideSave()
    {
        return [
            'Load qui se passe bien' => [0, [
                'peerReviewing' => '1',
                'popularLevel' => '0',
                'language' => 'en',
                'journal' => (new Ccsd_Referentiels_Journal())->load('2872'),
                'date' => '2001',
                'volume' => '69',
                'page' => '655 - 701',
                'comment' => 'article soumis un an après sa parution',
                'classification' => '03.65.Ta, 03.65.ud, 03.67.a',
                'domain' => [0 => 'phys.qphy'],
                'localReference' => [0 => 'LKB 4'],
                'title' => ['en' => 'Do we really understand quantum mechanics?'],
                'abstract' => ['en' => 'This article presents a general discussion of several aspects of our presentunderstanding of quantum mechanics. The emphasis is put on the very specialcorrelations that this theory makes possible: they are forbidden by verygeneral arguments based on realism and local causality. In fact, thesecorrelations are completely impossible in any circumstance, except the veryspecial situations designed by physicists especially to observe these purelyquantum effects. Another general point that is emphasized is the necessity forthe theory to predict the emergence of a single result in a single realizationof an experiment. For this purpose, orthodox quantum mechanics introduces aspecial postulate: the reduction of the state vector, which comes in additionto the Schrödinger evolution postulate. Nevertheless, the presence inparallel of two evolution processes of the same object (the state vector) maybe a potential source for conflicts; various attitudes that are possible toavoid this problem are discussed in this text. After a brief historicalintroduction, recalling how the very special status of the state vector hasemerged in quantum mechanics, various conceptual difficulties are introducedand discussed. The Einstein Podolsky Rosen (EPR) theorem is presented withthe help of a botanical parable, in a way that emphasizes how deeply the EPRreasoning is rooted into what is often called "scientific method\'\'. Inanother section the GHZ argument, the Hardy impossibilities, as well as theBKS theorem are introduced in simple terms.'],
                //'keyword' => ['en' => ['Bell theorem', 'quantum measurement', 'foundations of quantum mechanics', 'alternative theories']],
                'identifier' => ['arxiv' => 'quant-ph/0209123']
            ]],
            'Load avec anrproject' => [0, [
                'peerReviewing' => '1',
                'popularLevel' => '0',
                'language' => 'en',
                'date' => '2008',
                'writingDate' => '2008',
                'volume' => '118',
                'page' => '718-742',
                'domain' => [0 => 'shs.eco'],
                'issue' => 'avril',
                'audience' => '2',
                'title' => ['en' => 'Optimal Degree of Public Information Dissemination'],
                //'keyword' => ['en' => ['Transparency', 'Beauty contest', 'Information']],
                'journal' => (new Ccsd_Referentiels_Journal())->load('96102'),
                'anrProject' => [0 => (new Ccsd_Referentiels_Anrproject())->load('5912')],
            ]],
            'Load avec europeanProject' => [0, [
                'peerReviewing' => '1',
                'popularLevel' => '0',
                'language' => 'en',
                'date' => '2012',
                'edate' => '2012-04-24',
                'volume' => '3',
                'page' => '796',
                'domain' => [0 => 'sdv.mp.vir'],
                'audience' => '2',
                'title' => ['en' => 'Bats host major mammalian paramyxoviruses.'],
                'abstract' => ['en' => 'The large virus family Paramyxoviridae includes some of the most significant human and livestock viruses, such as measles-, distemper-, mumps-, parainfluenza-, Newcastle disease-, respiratory syncytial virus and metapneumoviruses. Here we identify an estimated 66 new paramyxoviruses in a worldwide sample of 119 bat and rodent species (9,278 individuals). Major discoveries include evidence of an origin of Hendra- and Nipah virus in Africa, identification of a bat virus conspecific with the human mumps virus, detection of close relatives of respiratory syncytial virus, mouse pneumonia- and canine distemper virus in bats, as well as direct evidence of Sendai virus in rodents. Phylogenetic reconstruction of host associations suggests a predominance of host switches from bats to other mammals and birds. Hypothesis tests in a maximum likelihood framework permit the phylogenetic placement of bats as tentative hosts at ancestral nodes to both the major Paramyxoviridae subfamilies (Paramyxovirinae and Pneumovirinae). Future attempts to predict the emergence of novel paramyxoviruses in humans and livestock will have to rely fundamentally on these data.'],
                'funding' => [0 => 'This study was funded by the European Union FP7 projects EMPERIE (Grant agreement number 223498) and EVA (Grant agreement number 228292), the German Federal Ministry of Education and Research (BMBF; project code 01KIO701), the German Research Foundation (DFG; Grant agreement number DR 772/3-1) to CD; the German Federal Ministry of Education and Research (BMBF) through the National Research Platform for Zoonoses (project code 01KI1018), the Umweltbundesamt (FKZ 370941401) and the Robert Koch-Institut (FKZ 1362/1-924) to RGU; through the Government of Gabon, Total-Fina-Elf Gabon and the Ministère des Affaires Etrangères, France.'],
                'europeanProject' => [0 => (new Ccsd_Referentiels_Europeanproject())->load('18734')],
                'identifier' => ['doi' => '10.1038/ncomms1796', 'pubmed' => '22531181']
            ]]
        ];
    }

    /**
     * @param $name
     * @param bool $group
     *
     * @dataProvider providegetMetaValues
     */
    public function testGetMeta($name, $group, $result)
    {
        $docMetas = new Hal_Document_Metadatas();

        $metasArray = array(
            'lang' => 'en',
            'title' => ['en' => 'Testing Article'],
            'keyword' => ['en' => ["blabla", "bloblo", "blibli"]],
            'date' => '1999-12-10',
            'page' => '12',
            'identifier' => ['pdf' => 'myid']
        );

        $docMetas->addMetasFromArray($metasArray, "grobid", 0);

        self::assertEquals($result, $docMetas->getMeta($name, $group));
    }

    /**
     * @return array
     */
    public function providegetMetaValues()
    {
        return [
            'récupération du titre sans filtre' => ['title', null, ['en' => 'Testing Article']],
            'récupération de l\'abst sans filtre' => ['abstract', null, []],
            'récupération de l\'abst avec filtre' => ['abstract', 'en', ''],
            'récupération de la date' => ['date', null, '1999-12-10'],
            'récupération du volume' => ['volume', null, ''],
        ];
    }

    /**
     * @param $name
     * @param bool $group
     *
     * @dataProvider providemergeMetas
     */
    public function testMerge($result, $sourceRes, $val1, $source1, $uid1, $val2, $source2, $uid2)
    {
        $metas = new Hal_Document_Metadatas();
        $metas->addMetasFromArray($val1, $source1, $uid1);

        $newmetas = new Hal_Document_Metadatas();
        $newmetas->addMetasFromArray($val2, $source2, $uid2);

        $metas->merge($newmetas);
        $array = $metas->getMeta();

        self::assertEquals($result, $array);
        self::assertEquals($sourceRes, $metas->getHalMeta("title")->getHalValue("en")->getSource());
    }

    public function providemergeMetas()
    {
        return [
            'Merge de données Grobid et de données web' => [["title" => ["en"=>"TOTO"]], "web", ["title" => ["en"=>"TOTO"]], "web", 12563, ["title" => ["en"=>"TITI"]], "grobid", 0],
            'Merge de données Grobid avec d\'autres données Grobid' => [["title" => ["en"=>"TITI"]], "doi", ["title" => ["en"=>"TOTO"]], "grobid", 0, ["title" => ["en"=>"TITI"]], "doi", 0],
            'Merge de données web avec d\'autres données web' => [["title" => ["en"=>"TITI"]], "grobid", ["title" => ["en"=>"TOTO"]], "web", 12563, ["title" => ["en"=>"TITI"]], "grobid", 12563],
            'Merge aucune donnée avec données web' => [["title" => ["en"=>"TITI"]], "grobid", [], "web", 12563, ["title" => ["en"=>"TITI"]], "grobid", 12563],
        ];
    }

    public function testKeyword() {
        $kws = new Hal_Document_Meta_Keyword("keyword", "test unitaire", "fr", "test", 0, 0);
        $kws->addValue( "unit test", "en", "test", 0, 0);
        $this -> assertEquals(["test unitaire"], $kws->getValue('fr'));
        $this -> assertEquals(["unit test"], $kws->getValue('en'));
        $kws->addValue( "test unitaire 2", "fr", "test", 0, 0);
        $this -> assertEquals(["test unitaire", "test unitaire 2"], $kws->getValue('fr'));

        $kws2 = new Hal_Document_Meta_Keyword("keyword", "test unitaire", "fr", "test", 0, 0);
        $kws2->addValue( "merge test", "fr", "doi", 0, 0);

        $kws->merge($kws2);
        $this -> assertEquals(["test unitaire", "merge test"], $kws2->getValue('fr'));
    }
}