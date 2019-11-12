<?php
require __DIR__ . "/../library/Hal/Script.php";

/**
 * Class recupLinkExtScript
 * Récupération des Liens Extérieurs (Istex, etc...)
 */
class recupLinkExtScript extends Hal_Script
{
    protected $options = [
        'ref|r' => "Pour extraire les doi des références biblio",
        'from=s' => "Date de début des dépôts à récupérer",
        'docid=i' => "Docid du document à récupérer",
        'continue|c' => "Permet de continuer là où le script s'est arrêté",
        'page=i' => "Pour continuer le script à une certaine page"
    ];

    /**
     * @param Zend_Console_Getopt $getopt
     */
    public function main($getopt)
    {
        $db = Zend_Db_Table::getDefaultAdapter();

        $this->verbose('****************************************');
        $this->verbose("**  Récupération des Liens Extérieurs (Istex, etc...)  **");
        $this->verbose('****************************************');
        $this->verbose('> Environnement: ' . APPLICATION_ENV);
        $this->verbose('----------------------------------------');

        $refOption = $getopt->getOption('ref');
        $docid = $getopt->getOption('docid');
        $continueOption = $getopt->getOption('continue');
        $pageOption = $getopt->getOption('page');
        $fromOption = $getopt->getOption('from');
        if ($continueOption && $refOption) {
            die("Can't --continue for References, only with Document\n");
        }
        if ($continueOption && $docid) {
            die("Can't use --continue with a specified Docid\n");
        }
        if ($docid && $pageOption) {
            die("Can't use --docid with a page option\n");
        }
        if ($docid && $fromOption) {
            die("Can't use --docid with a from option\n");
        }

        // Récupération des liens extérieurs
        if ($refOption) { // Dans les références (DOI)
            $dbRef = Hal_Db_Adapter_ReferenceBiblio::getAdapter();
            $table = Hal_Document_References::DOC_REFERENCES;
            $idFieldName = 'DOI';
            $conditions[] = 'DOI IS NOT NULL';
            $idSite = Hal_LinkExt::TYPE_DOI;
            $sql = $dbRef->select()->distinct($idFieldName)
                ->from($table, $idFieldName);
        } else { // Dans les documents
            $table = ['h' => Hal_Document_Meta_Identifier::TABLE_COPY];
            $idFieldName = 'LOCALID';
            $conditions[] = 'CODE = "' . Hal_LinkExt::TYPE_DOI . '"';
            $idSite = Hal_LinkExt::TYPE_DOI;
            $sql = $db->select()->distinct($idFieldName)
                ->from($table, $idFieldName);
            $sql->join(['d' => 'DOCUMENT'], 'h.DOCID=d.DOCID', null);
            $conditions[] = "d.FORMAT != 'file'";
        }

        if ($docid) {
            $conditions[] = 'd.DOCID = ' . $docid;
        }

        // SELECT DISTINCT `h`.`LOCALID`, LINKID FROM `DOC_HASCOPY` AS `h` LEFT JOIN DOC_LINKEXT ON LOCALID=LINKID   WHERE (CODE = "doi")  and LINKID is NULL LIMIT 1000;
        // Reprendre la récupération des liens sans traiter les liens extérieurs déjà traité
        if ($continueOption) {
            $sql->joinLeft(Hal_LinkExt::TABLE_LINKEXT, $idFieldName . '=LINKID');
            $conditions[] = 'LINKID IS NULL';
        }
        // Ajout des conditions WHERE sur la requête
        foreach ($conditions as $condition) {
            $sql->where($condition);
        }

        // Récupération des documents depuis une date
        if ($fromOption) {
            $sql->where("DATESUBMIT >= '" . $getopt->getOption('from') . "'");
        }

        // Reprendre la récupération des liens à une certaine page
        if ($pageOption) {
            $i = $pageOption;
        } else {
            $i = 1;
        }

        while (true) {
            $this->verbose('****************************************');
            $this->verbose('Traitement de la page n°' . $i);
            $this->verbose('****************************************');

            $sql->limitPage($i, 1000);

            $rows = $db->fetchAll($sql);

            if ($rows == null) {
                break; // This is the end!
            } else {
                foreach ($rows as $row) {
                    $idvalue = $row[$idFieldName];

                    $metaLinkExt = Hal_LinkExt::load($idvalue, $idSite);
                    switch ($metaLinkExt->retreiveUrl($idSite)) {
                        case Hal_LinkExt::MAJ :
                            $this->verbose($idvalue . ' : ADDED');
                            break;
                        case Hal_LinkExt::SAME :
                            $this->verbose($idvalue . ' : No change');
                            break;
                        case Hal_LinkExt::NOTFOUND:
                            $this->verbose($idvalue . ' : NOT Found');
                            $metaLinkExt->delete(); // On nettoie les liens n'ayant plus d'URL valide
                            break;
                        case Hal_LinkExt::NOUPD:
                            $this->verbose($idvalue . ' : Unknow pb');
                            break;
                        default:
                            $this->verbose($idvalue . ': ERROR, we got an unexpected value. This is bad. Fix it in database.');
                            break;
                    }
                }
            }

            $i++;
        }

        $this->verbose('----------------------------------------');
        $this->verbose('> Script executé en ' . $this->getDisplayableRunTime());
    }
}

$script = new recupLinkExtScript();
$script->run();



