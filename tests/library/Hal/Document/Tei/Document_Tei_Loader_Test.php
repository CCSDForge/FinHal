<?php

/**
 * Class Document_Tei_Loader_Test
 */
class Document_Tei_Loader_Test extends PHPUnit_Framework_TestCase
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
     * @param $meta
     * @param $result
     *
     * @dataProvider provideMetas
     */
    public function testLoadMetaSoftware($meta, $result)
    {
        $xml = $this->loadFile('software.xml');

        $loader = new Hal_Document_Tei_Loader($xml);
        $metas = $loader->loadMetas('hal');

        $this->assertEquals($result, $metas[$meta]);
    }

    /**
     * @return array
     * @see testLoadMetaSoftware()
     */
    public function provideMetas()
    {
        return [
            ['typdoc', 'SOFTWARE'],
            ['codeRepository', 'https://github.com/truc'],
            ['version', '1.154.215'],
            ['programmingLanguage', ['java', 'javascript', 'nodejs']],
            ['softwareLicence', ['Bahyph License', 'Creative Commons Attribution 4.0', 'Licence non connue']],
        ];
    }

    public function testLoadMetaInPress()
    {
        $xml = $this->loadFile('art-inpress.xml');

        $loader = new Hal_Document_Tei_Loader($xml);
        $metas = $loader->loadMetas('hal');
        $this->assertEquals('1', $metas['inPress']);
    }

    public function testLoadMetaOtherType()
    {
        $xml = $this->loadFile('art-inpress.xml');

        $loader = new Hal_Document_Tei_Loader($xml);
        $metas = $loader->loadMetas('hal');
        $this->assertEquals('2', $metas['otherType']);
    }
}
