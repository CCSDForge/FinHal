<?php

/**
 * Class Document_Tei_Loader_Test
 */
class Document_Tei_Creator_Test extends PHPUnit_Framework_TestCase
{

    /**
     * @param $fileName
     * @return DOMDocument
     */
    protected function loadFile($fileName)
    {
        $domDocument = new DOMDocument();
        $domDocument->loadXML(file_get_contents(RESSOURCESDIR . '/tei/' . $fileName ));
        return $domDocument;
    }

    /**
     * @return array
     * @see testLoadMetaSoftware()
     */
    public function provideMetas()
    {
        return [
        ];
    }

    public function testCreator()
    {
        # $xml = $this->loadFile('art-inpress.xml');
        $xml = $this->loadFile('preprint.xml');
        $document = new Hal_Document();
        $document -> loadFromTEI($xml);
        $document->setContributorId(1);
        $tei = $document->createTEI();
        $this->assertEquals($xml->saveXML(), $tei);
    }

    public function testChangeXmlId() {
        $this->assertEquals("ref1234-b2", Hal_Document_Tei_Creator::changeXmlIdValue('b2', '1234' ));

    }
}
