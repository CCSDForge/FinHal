<?php

require __DIR__ . "/../library/Hal/Script.php";

/**
 * Class deleteDocumentScript
 * Script pour effacer des documents par docid ou par requetes Sql
 *
 */
class deleteDocumentScript extends Hal_Script
{
    /**
     */
    protected $options = array(
        'docid|D-i' => 'Docid du document à traiter',
        'sql|s=s'   => 'Requête SQL des documents à supprimer (table DOCUMENT uniquement)',
        'test|t'    => "Pas d'action effectuée"
    );

    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt)
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        $arrayOfDocId = [];
        if ($getopt->docid != false) {
            $arrayOfDocId[] = $getopt->docid;
        } else {
            if ($getopt->sql != false) {
                $arrayOfDocId = $db->fetchCol("SELECT DOCID FROM DOCUMENT WHERE " . $getopt->sql);
            }
        }

        Ccsd_Log::message("Nombre de documents à supprimer : " . count($arrayOfDocId), true, 'INFO');

        foreach ($arrayOfDocId as $docid) {
            $document = Hal_Document::find($docid);
            if (! $document) {
                Ccsd_Log::message("Docid " . $docid . ' non trouvé !', true, 'ERR');
                continue;
            }
            if ($getopt->test) {
                $this->println('', "Must delete $docid");
            } else {
                if ($document->delete(100000, 'Document farfelu', false)) {
                    Ccsd_Log::message("Docid " . $docid . ' supprimé', true, 'INFO');
                } else {
                    Ccsd_Log::message("Impossible de supprimer docid = " . $docid, true, 'ERR');
                }
            }
        }
    }
}

$script = new deleteDocumentScript();
$script->run();
