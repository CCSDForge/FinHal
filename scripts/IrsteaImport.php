<?php




if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../library/Hal/Script.php';


putenv('PORTAIL=INRA');
define('DEFAULT_CONFIG_PATH','/applis/hal/hal/data/notices_Irstea/');
define('SPACE','/');



class IrsteaImport extends Hal_Script
{


    /**
     */
    protected $options = array(
        'path|p' => 'chemin des répertoires contenant les notices',
        'filters|f' => 'liste des filtres à appliquer au flux'
    );

    protected $sid = 5798;
    protected $uid = 132775;
    protected $path = '../data/notices_Irstea/';


    public function main($getOpt)
    {

        // A l'appel de main on doit creer deux fichiers de log ( good log and bad log )

        //On vérifie que les filtres existent dans le dossier mappingFilter

        //On inclue ces filtres

        //on crée un repertoire/fichier de log pour chaque filtre.

        //dans un premier temps on va dans le répertoire contenant les notices ( on verra si autre moissonnage nécessaire )

        //chaque fichier contient un document. Donc pour chaque fichier on va :
        // - créer un document vide
        // - extraire via xpath les infos du fichier
        // - modifier la typologie selon règles spécifiques
        // - trouver les correspondances d'auteurs ou de structure (creer les auteurs si besoin est)
        // - sauvegarder le document.

        // penser à gestion des erreurs qui log


        $this->enableLogs();


        $this->verbose('****************************************');
        $this->verbose('**      Boucle d import IRSTEA      **');
        $this->verbose('****************************************');
        $this->verbose('> Début du script: ' . date('H:i:s' . $this->_init_time));


        if (is_dir($this->path)) {

            $this->processFile($this->path);
        }
    }

    public function processFile($path)
    {

        $arrayType = [];
        $totalArticle = 0;
        $articleATraiter = 0;
        //$array_codique = [];
        if ($dh = opendir($path)) {
            while (($file = readdir($dh)) !== false) {
                if ($file !== '.' && $file !== '..') {

                    $docXml = new DOMDocument();
                    $docXml->recover=true;
                    //$docXml->strictErrorChecking=false;
                    $docXml->load(realpath($path . '' . DIRECTORY_SEPARATOR . '' . $file));
                    $domxpath = new DOMXPath($docXml);
                    foreach (Ccsd_Externdoc_Irstea::$NAMESPACE as $key => $value) {
                        $domxpath->registerNamespace($key, $value);
                    }

                    $produits = $domxpath->query(Ccsd_Externdoc_Irstea::XPATH_ROOT);

                    foreach ($produits as $key => $produit) {

                        /**
                        $xsiType = $produit->firstChild->getAttribute('xsi:type');
                        if (!in_array($xsiType, $arrayType, true)) {
                        $arrayType[] = $xsiType;
                        }
                         **/
                        //if ($xsiType === 'ns2:article') {
                        //if ($xsiType === 'ns2:articleTranslation'){
                        $irsteaDoc = $this->processDoc($produit, $key . '-' . $file);
                        if ($irsteaDoc !== null) {

                            // on a un doc IRSTEA
                            // 1 faire alignement sur ref auteur
                            // 2 faire alignement sur ref structure
                            //   alignement sur ref de licence ???
                            // 3 aggrégation des metadata dans un tableau global
                            // 4 creation d'un objet HAL_DOCUMENT
                            // 5 export TEI du document avec éventuellement comparaison de données


                            // 1 En attente de ref auteur


                            // 2 En attente de ref structure


                            // 3 aggrégation des métadatas dans un tableau global

                            //$this->println('document ' . $key . '-' . $file);
                            //$this->println(json_encode($irsteaDoc->getMetadatas(), JSON_PRETTY_PRINT));
                            //$metadatas = array_merge($irsteaDoc->getMetadatas()['metas'], $irsteaDoc->getMetadatas());

                            // 4 gestion de la création des métas au niveau méta

                            $metas = $irsteaDoc->getMetadatas()['metas'];
                            $metasIdentifier = ['identifier' => $irsteaDoc->getMetadatas()['identifier']];
                            $metasLanguage = ['language' => $irsteaDoc->getMetadatas()['language']];

                            $source = 'Prodinra';

                            //$filename = $irsteaDoc->getMetadatas()['identifier']['Prodinra'];

                            // 5 insertion des métas dans le document HAL
                            $hal_doc = new Hal_Document();
                            $hal_doc->setMetas($metas, 0, $source);
                            $hal_doc->addMetas($metasIdentifier, 0, $source);
                            $hal_doc->addMetas($metasLanguage, 0, $source);
                            $hal_doc->setTypdoc($irsteaDoc->getHalTypology());

                            //attribution de portail
                            $hal_doc->setSid($this->sid);
                            $hal_doc->setContributorId($this->uid);
                            //$hal_doc->setInputType(Hal_Settings::SUBMIT_ORIGIN_SWORD);

                            /**
                             * try {
                             * Hal_Document_Validity::isValid($hal_doc);
                             * } catch(Exception $e) {
                             * var_dump($hal_doc);
                             * $this->println('',  " | META NOK | ". serialize($e->getMessage()), 'red');
                             * return false;
                             * }
                             **/

                            //sauvegarde du document (parce qu'il faut que versions soient alimenté)
                            // $hal_doc->save(1,false);
                            // 5 Génération de la TEI ( à fin de vérification )

                            //Génération d'un fichier XML TEI
                            //$fileManager = fopen($path.'../tei/'.$filename.'.xml','wb');
                            //fwrite($fileManager,$hal_doc->createTEI());
                            //fclose($fileManager);

                            if (isset($irsteaDoc->getMetadatas()['metas']['hal_sending'])) {
                                if ($irsteaDoc->getMetadatas()['metas']['hal_sending'] === 'false') {
                                    $articleATraiter++;
                                }
                                $totalArticle++;
                            }

                            $this->println('total article :' . $totalArticle);
                            $this->println('article non présent :' . $articleATraiter);
                            $this->println(json_encode($irsteaDoc->getMetadatas(), JSON_PRETTY_PRINT));
                        }



                    }
                }
            }
        }
    }


    public function processDoc($produit, $id)
    {
        $xml = new DOMDocument();
        $xml->appendChild($xml->importNode($produit, true));
        return Ccsd_Externdoc_Irstea::createFromXML($id, $xml);

    }

}

$script = new IrsteaImport();
$script->run();



