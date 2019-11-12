<?php
/**
 * Created by PhpStorm.
 * User: bblondelle
 * Date: 26/06/18
 * Time: 16:54
 */


if (file_exists(__DIR__ . '/../../vendor/autoload.php'))
    require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . "/../../library/Hal/Script.php";

/**
 * Give a docid and this script print to stdout the tex coverpage/
 */
class transferStructure extends Hal_Script
{
    /**
     */
    protected $options = array(
        'oldS|o-i' => 'StructId de l\'ancienne structure à traiter',
        'newS|n-i' => 'StructId de la nouvelle à traiter'
    );

    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt)
    {
        $oldId = $getopt->oldS;
        $newId = $getopt->newS;

        // Attention: Le SPACE_NAME doit etre positionne a AUREHAL pour pouvoir sauvegarder les structures
        RuntimeConstDef('hal_test.archives-ouvertes.fr', 'AUREHAL', Hal_Site_Portail::MODULE, 'https');

        $oldStruct = new Ccsd_Referentiels_Structure($oldId);
        $newStruct = new Ccsd_Referentiels_Structure($newId);
        if ($oldStruct->getStructid() != 0 && $newStruct->getStructid() != 0) {

            $arrayTransfer = $oldStruct->transferStruct($newStruct);
            /** @var Ccsd_Referentiels_Structure $resOldStruct */
            $resOldChildren = $arrayTransfer['oldchilds'];
            /** @var Ccsd_Referentiels_Structure $resNewStruct */
            $resNewChildren = $arrayTransfer['newchilds'];

            $this->println('', '***********************', 'blue');
            $this->println('', '*  Ancienne structure *', 'blue');
            $this->println('', '***********************', 'blue');
            $this->println($oldStruct->getStructid() . "\n");

            $this->println('', '*************************************', 'blue');
            $this->println('', '*  Enfants de l\'ancienne structure *', 'blue');
            $this->println('', '*************************************', 'blue');

            foreach ($resOldChildren as $k => $child) {
                /** @var Ccsd_Referentiels_Structure $child */
                $this->println($child->getStructid());
            }

            $this->println('', '***********************', 'blue');
            $this->println('', '*  Nouvelle structure *', 'blue');
            $this->println('', '***********************', 'blue');
            $this->println($newStruct->getStructid() . "\n");

            $this->println('', '*************************************', 'blue');
            $this->println('', '*  Enfants de la nouvelle structure *', 'blue');
            $this->println('', '*************************************', 'blue');

            foreach ($resNewChildren as $k => $child) {
                $this->println($child->getStructid());
            }
        } else {
            $this->println('', '*************************', 'blue');
            $this->println('', '*  Structure non valide *', 'blue');
            $this->println('', '*************************', 'blue');
        }
    }
}

$script = new transferStructure();
$script->run();