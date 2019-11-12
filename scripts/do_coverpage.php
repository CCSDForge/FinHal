<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require __DIR__ . "/../library/Hal/Script.php";

/**
 * Class doCoverpageScript
 * Refaire la page de couverture des documents
 *
 */
class doCoverpageScript extends Hal_Script
{
    /**
     */
    protected $options = array(
        'docid|D-i' => 'Docid du document à traiter',
        'sql|s=s' => 'Requête SQL des documents à supprimer (table DOCUMENT uniquement)',
        'test|t' => "Pas d'action effectuée"
    );

    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt)
    {
        $db = Zend_Db_Table::getDefaultAdapter();
        $this -> need_user('apache');
        $where = 'MAIN = 1 AND FILETYPE = "file" AND EXTENSION = "pdf"';
        if ($getopt->docid) {
            $where .= ' AND DOCID=' . $getopt->docid;
        } else {
            if ($getopt->sql) {
                $where .= "AND (" .  $getopt->sql .')';
            }
        }
        try {
            $sql = $db->select()->distinct()->from('DOC_FILE', 'DOCID')->where($where)->order('DOCID ASC');
            foreach ($db->fetchCol($sql) as $docid) {
                $document = Hal_Document::find($docid);
                if (!$document) {
                    Ccsd_Log::message($docid . ' PB in find document');
                    continue;
                }

                $pdf = $document->getRacineCache() . $docid . '.pdf';
                Ccsd_Log::message($pdf);
                if ($getopt->test)  {
                    $this -> println('', 'Il faut refaire la coverPage de ' . $docid);
                } else {
                    @unlink($pdf);
                    if ($document->makeCoverPage()) {
                        Ccsd_Log::message($docid . ' OK');
                    } else {
                        Ccsd_Log::message($docid . ' KO');
                    }
                }
            }
        } catch (Exception $e) {
            Ccsd_Log::message('Exception : ' . $e->getMessage());
        }
        // Dans les logs, on voit que le script a termine correctement
        Ccsd_Log::message("Fin du script.");

    }
}

$script = new doCoverpageScript();
$script->run();













