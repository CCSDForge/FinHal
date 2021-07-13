<?php

class File_Test extends PHPUnit_Framework_TestCase
{
    public function testFtpfilename()
    {
        $file = new Hal_Document_File();
        $file -> setPath(PATHDOCS . '/01/01/01/01/test.pdf');
        $this->assertEquals($file -> ftpfilename('ftp://ftp.ccsd.cnrs.fr/dir/monfichier.pdf'),'/ftp/0/dir/monfichier.pdf');

        $this->assertEquals(PATHDOCS . '/01/01/01/01/test.pdf', $file -> getPath());
    }

    public function testFtpSetFileInfos()
    {
        $file = new Hal_Document_File();
        $file -> setPath('ftp://ftp.ccsd.cnrs.fr/dir/monfichier.pdf');
        $this->assertTrue($file->ftpSetFileInfos());
        $this->assertEquals($file->getName(), 'monfichier.pdf');
        $this->assertEquals($file->getPath(), '/ftp/0/dir/monfichier.pdf');
    }
    public function testDiversFile()
    {
        $file = new Hal_Document_File();
        $file -> setPath(PATHDOCS . '/01/01/01/01/Chapter.pdf');
        $this->assertTrue($file->file_exists());
        $this->assertEquals($file->setSize(1000), 1000);
        $this->assertEquals($file->setSize(), 170072);
    }

    public function testSetFileInfos()
    {
        $file = new Hal_Document_File();
        $file -> setPath('ftp://ftp.ccsd.cnrs.fr/dir/monfichier.pdf');
        $this->assertTrue($file->setFileInfos());
        $this->assertEquals($file->getName(), 'monfichier.pdf');
        $this->assertEquals($file->getPath(), '/ftp/0/dir/monfichier.pdf');

        $file = new Hal_Document_File();
        $file -> setPath(PATHDOCS . '/01/01/01/01/Chapter.pdf');
        $this->assertTrue($file->setFileInfos());
        # $this->assertEquals($file->getName(), 'Chapter.pdf');
        $this->assertEquals($file->getPath(), PATHDOCS . '/01/01/01/01/Chapter.pdf');

        $file = new Hal_Document_File();
        $file -> setPath('https://hal.archives-ouvertes.fr/hal-00000001v2/document');
        $this->assertTrue($file->setFileInfos());
        $this->assertTrue($file->file_exists());
        $this->assertEquals('mq-anglais.pdf', $file->getName());
        $this->assertEquals('application/pdf', Ccsd_File::getMimeType($file->getPath()));
        
    }
    

}