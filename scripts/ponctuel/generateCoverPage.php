<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 26/03/18
 * Time: 17:06
 */
if (file_exists(__DIR__ . '/../../vendor/autoload.php'))
    require_once __DIR__ . '/../../vendor/autoload.php';

require __DIR__ . "/../../library/Hal/Script.php";

/**
 * Give a docid and this script print to stdout the tex coverpage/
 */
class generateCoverpageScript extends Hal_Script{
    /**
     */
    protected $options  = array(
    'docid|D-i'  => 'Docid du document à traiter',
    );

    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt) {
        $this -> need_user('apache');
        $docid = $getopt->docid;

        $document  = new Hal_Document($docid, '',  0, true, true);
        $files=[];
        $tex = $document -> makeTexCoverPage($files);

        print $tex;
    }
}

$script = new generateCoverpageScript();
$script->run();