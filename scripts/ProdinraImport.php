<?php
/**
 * Created by PhpStorm.
 * User: genicot
 * Date: 04/03/19
 * Time: 14:34
 */

/**
 * Class prodinraImport
 * Classe permettant de gérer le flux d'import de prodinra
 */

if (file_exists(__DIR__ . '/../vendor/autoload.php'))
    require_once __DIR__ . '/../vendor/autoload.php';

require __DIR__ . '/../library/Hal/Script.php';


putenv('PORTAIL=INRA');
define('DEFAULT_CONFIG_PATH','/applis/hal/hal/data/notices_prodinra/');
define('SPACE','/applis/hal/hal/');
define('SPACE_NAME','HAL');
define('SITEID','5798');



class ProdinraImport extends Hal_Script
{



    /**
     */
    protected $options = array(
        'path|p' => 'chemin des répertoires contenant les notices',
        'filters|f' => 'liste des filtres à appliquer au flux'
    );

    protected $sid=5798;
    protected $uid=132775;
    protected $path = '../data/notices_prodinra/';

    protected $languageDetector ;


    public function main($getOpt){

        $session = new Hal_Session_Namespace();

        $halUser = Hal_User::createUser($this->uid);

        Hal_Auth::setIdentity($halUser);

        Zend_Registry::set('lang','fr');

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

        $this->environment='preprod';


        $this->enableLogs();



        $this->verbose('****************************************');
        $this->verbose('**      Boucle d import Prodinra      **');
        $this->verbose('****************************************');
        $this->verbose('> Début du script: ' . date('H:i:s' . $this->_init_time));

        $this->initLanguageDetector();


        if (is_dir($this->path)) {

          $this->processFile($this->path);
        }
    }

    public function initLanguageDetector(){
        ob_start();
        $this->languageDetector = new \LanguageDetection\Trainer();
        $this->languageDetector->setMaxNgrams(9000);
        $this->languageDetector->learn();
        ob_end_clean();
    }


    public function processFile($path){

        $arrayType=[];
        $totalArticle = 0;
        $articleATraiter = 0;
        //$array_codique = [];

        //$filenameCsv = 'Liste_token_Prodinra.csv';
        $delimiteur = ';';

        //$fichier_csv= fopen($filenameCsv,'w+');

        //fprintf($fichier_csv,chr(0xEF).chr(0xBB).chr(0xBF));

        //fputcsv($fichier_csv,['attachmentId','fileName','version','fileMimeType','original','accessCondition'],';');

        $count_doc_test = 0;
        $count_doc_test_max = 1;
        $go = false;

        if ($dh = opendir($path)){
            while (($file = readdir($dh)) !== false) {
                if ($file !== '.' && $file !=='..' && $file!=='meta.ini' ) {


                    $this->println(realpath($path . '' . DIRECTORY_SEPARATOR . '' . $file));
                    $docXml = new DOMDocument();
                    try {
                        $docXml->load(realpath($path . '' . DIRECTORY_SEPARATOR . '' . $file), LIBXML_ERR_ERROR);
                        $domxpath = new DOMXPath($docXml);
                    }
                    catch (Error $e){
                        $error = true;
                    }
                    foreach (Ccsd_Externdoc_Inra::$NAMESPACE as $key => $value) {
                        $domxpath->registerNamespace($key, $value);
                    }

                    $produits = $domxpath->query(Ccsd_Externdoc_Inra::XPATH_ROOT);

                    //$count_author=0;
                    //$count_author_identifie = 0;

                    foreach($produits as $key=>$produit) {
                        if ($count_doc_test < $count_doc_test_max) {


                            $xsiType = $produit->firstChild->getAttribute('xsi:type');
                            if (!in_array($xsiType, $arrayType, true)) {
                                $arrayType[] = $xsiType;
                            }

                            $category = str_replace('ns2:', '', $xsiType);

                            //$this->println($category);
                            if ($category === 'bookTranslation'
                                || $category === 'articleTranslation'
                                || $category === 'chapterTranslation'
                                || $category === 'book'
                                || $category === 'chapter'
                                || $category === 'article') {
                                $inraDoc = $this->processDoc($produit, $key . '-' . $file);
                                /**
                                 * $array_Pertinent_Identifier = [
                                 * '406213',
                                 * '78',
                                 * '417115',
                                 * '406213',
                                 * '465836',
                                 * '464914',
                                 * '455868',
                                 * '282917',
                                 * '464914',
                                 * '455868',
                                 * '282917',
                                 * '464905',
                                 * '464914',
                                 * '406213',
                                 * '355594',
                                 * '384294',
                                 * '458477'
                                 * ];
                                 **/
                                $array_Pertinent_Identifier = ['317020'];

                                //$this->println(json_encode($inraDoc->getJelCode(),JSON_PRETTY_PRINT));

                                if ($inraDoc->getMetadatas()['identifier']['prodinra'] === $array_Pertinent_Identifier[0]) {
                                    $go = true;
                                }

                                if (!is_array($inraDoc->getMetadatas())) {
                                    $this->println('pas de metadatas : ' . $inraDoc->getIdentifier());
                                } else if (!array_key_exists('identifier', $inraDoc->getMetadatas()) && false) {
                                    // cas d'une erreur à identifier
                                    $this->println($category);
                                    $this->println(json_encode($inraDoc->getMetadatas(), JSON_PRETTY_PRINT));
                                    //} else if ($inraDoc !== null && $inraDoc->getHalSending() === 'false' && in_array($inraDoc->getMetadatas()['identifier']['prodinra'],$array_Pertinent_Identifier,true)) {
                                //} else if ($inraDoc !== null && $inraDoc->getHalSending() === 'false') {
                                } else if ($inraDoc !== null && $inraDoc->getHalSending() === 'false' && $go) {
                                    $token =
                                    $array_token = [];
                                    $this->println(json_encode($inraDoc->getMetadatas(),JSON_PRETTY_PRINT));


                                    //$count_doc_test++;
                                    //$this->println(json_encode($inraDoc->getHalDomain(),JSON_PRETTY_PRINT));
                                    /**
                                     * if (!empty($inraDoc->getAttachmentInfos())) {
                                     * $attachments = $inraDoc->getAttachmentInfos();
                                     * foreach ($attachments as $attachment) {
                                     * fputcsv($fichier_csv, $attachment, $delimiteur);
                                     * }
                                     * }
                                     **/

                                    // on a un doc INRA
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
                                    //$this->println(json_encode($inraDoc->getMetadatas(), JSON_PRETTY_PRINT));
                                    //$metadatas = array_merge($inraDoc->getMetadatas()['metas'], $inraDoc->getMetadatas());

                                    // 4 gestion de la création des métas au niveau méta

                                    $this->println($inraDoc->getMetadatas()['identifier']['prodinra']);
                                    /**
                                     * if (is_array($inraDoc->getTitle())) {
                                     * $this->println(implode(' ',$inraDoc->getTitle()));
                                     * } else {
                                     * $this->println($inraDoc->getTitle());
                                     * }
                                     **/
                                    $metas = $inraDoc->getMetadatas()['metas'];
                                    $metasIdentifier = ['identifier' => $inraDoc->getMetadatas()['identifier']];
                                    $metasLanguage = ['language' => $inraDoc->getMetadatas()['language']];

                                    $source = 'Prodinra';

                                    $filename = $inraDoc->getMetadatas()['identifier']['prodinra'];

                                    // 5 insertion des métas dans le document HAL
                                    $hal_doc = new Hal_Document();
                                    $hal_doc->setMetas($metas, 0, $source);
                                    $hal_doc->addMetas($metasIdentifier, 0, $source);
                                    $hal_doc->addMetas($metasLanguage, 0, $source);
                                    $hal_doc->setTypdoc($inraDoc->getHalTypology());

                                    //attribution de portail
                                    $hal_doc->setSid($this->sid);
                                    $hal_doc->setContributorId($this->uid);

                                    //$hal_doc = self::addFileHalDocument($hal_doc, realpath($path . '' . DIRECTORY_SEPARATOR . '' . 'meta.ini'));


                                    $hal_doc->setVersion(1);
                                    $hal_doc->setVersions([1]);
                                    $hal_doc->setInputType(Hal_Settings::SUBMIT_ORIGIN_SWORD);
                                    $hal_doc->setTypeSubmit(Hal_Settings::SUBMIT_INIT);

                                    // ici nous avons le document de créé !
                                    // ce qui nous permettra d'avoir un objet Hal cohérent pour amené le contexte à la recherche des auteurs dans le référentiel.

                                    // 1 Direct matching : s'appuie sur un identifiant fort (idhal, orcid, adresse email)
                                    // Si les informations fournies contiennent un identifiant auteur fort dans la base alors on peut directement l'identifier par ce biais.
                                    // Si l'identifiant fort n'existe pas en base On continue.

                                    // 2 Indirect matching : s'appuie sur un identifiant fort du document (halId, IdRef)
                                    // Si il s'avère que le document est déjà dans la base alors ses auteurs sont également enregistrés
                                    // Le matching se fait donc en trouvant le document, en récupérant ses auteurs et en établissant les correspondances auteur.
                                    // Si toutes les correspondances ne peuvent être faites alors on continue pour les auteurs non matchés.

                                    // 3 Context Matching
                                    // Si par le biais des algorithmes précédent certains auteurs ont été matché mais pas tous
                                    // On peut alors regarder tous les collaborateurs qui ont travaillé avec les auteurs identifiés et essayer de trouver correspondance dans ceux là.

                                    // Pour réaliser ces traitements nous avons donc besoin d'une méthode prenant en paramètre les informations auteurs d'un côté, et les informations document de l'autre.
                                    // on aura un tableau d'auteur d'un coté ( avec nom prénom + identifiant fort + structure si information disponible )
                                    // on aura un hal document de l'autre ( avec les identifiants forts aussi )

                                    // On va donc pouvoir appeler une seule fonction qui va ordonner cet algorithme
                                    // Les paramètres sont l'auteur d'un coté et le document de l'autre.


                                      $array_author = $inraDoc->getAuthors();

                                    $this->addAuthorHalDocument($hal_doc);
                                      /**
                                      *foreach ($array_author as $key_author=>$author) {
                                      *    $array_author[$key_author]['authorId'] = $this->findIdAuthor($author, $inraDoc);
                                      *    $author['authorid'] = $array_author[$key_author]['authorId'];
                                      *    if ($author['authorid'] !== null) {
                                      *        $count_author_identifie++;
                                      *    }
                                      *
                                      *    if ($author['authorid']===null){
                                      *
                                      *    }
                                      *
                                      *
                                      *
                                      *    if ($author['authorid'] === null) {
                                      *        $author['authorid'] = $this->createAuthor($author,$inraDoc);
                                      *    }
                                      *
                                      *}**/


                                    try {
                                        Hal_Document_Validity::isValid($hal_doc);
                                    } catch (Exception $e) {
                                        $this->println('erreur sur document prodinra : ' . $inraDoc->getMetadatas()['identifier']['prodinra']);
                                        $this->println('', " | META NOK | " . json_encode($e,JSON_PRETTY_PRINT), 'red');
                                        return false;
                                    }


                                    //sauvegarde du document (parce qu'il faut que versions soient alimenté)
                                    $result = $hal_doc->save(1,false);
                                    // 5 Génération de la TEI ( à fin de vérification )

                                    //Génération d'un fichier XML TEI
                                    $dirname = '../data/tei/' . $category;

                                    if (!is_dir($dirname)) {
                                        if (!mkdir($dirname, 0755, true) && !is_dir($dirname)) {
                                            throw new \RuntimeException(sprintf('Directory "%s" was not created', $dirname));
                                        }
                                    }
                                    /**
                                    *$fileManager = fopen($dirname . '/' . $filename . '.xml', 'wb');
                                    *fwrite($fileManager, $hal_doc->createTEI());
                                    *fclose($fileManager);
                                    **/

                                    if (isset($inraDoc->getMetadatas()['metas']['hal_sending'])) {
                                        if ($inraDoc->getMetadatas()['metas']['hal_sending'] === 'false') {
                                            $articleATraiter++;
                                        }
                                        $totalArticle++;
                                    }

                                    //$this->println(json_encode($inraDoc->getMetadatas(), JSON_PRETTY_PRINT));
                                }
                            }
                            //$this->println('total iteration auteur :'.$count_author);
                            //$this->println('total iteration auteur identifie :'.$count_author_identifie);
                            //$this->println('total article :' . $totalArticle);
                            //$this->println('article non présent :' . $articleATraiter);

                        }
                    }
                }
            }
        }
    }


    private function createAuthor(&$author,$inraDoc){

    }


    /**
     * @param $author []
     * @param $halDocument Hal_Document
     * @return int
     */
    private function findIdAuthor($author,$halDocument){

        $authorId=null;
        if (isset($author['email'])) {
            $authorId = $this->findAuthorByEmail($author['email']);
        }

        if ($authorId === null && (isset($author['firstname']) && !empty($author['firstname']) && isset($author['lastname']) && !empty($author['lastname']))){
            $authorId = $this->findAuthorByName($author['firstname'],$author['lastname']);
        }

        return $authorId;

    }

    public function findAuthorByName($firstname,$lastname){

        $dbHALV3 = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sqlRequest = "SELECT * FROM `REF_AUTHOR` WHERE FIRSTNAME ='".trim(addslashes($firstname))."' and LASTNAME = '".trim(addslashes($lastname))."' and VALID = 'VALID'";
        $ep = $dbHALV3->query($sqlRequest);
        $array_db = $ep->fetchAll();
        if (count($array_db)>0){
            return $array_db[0]['AUTHORID'];
        }
        else return null;

    }


    public function findAuthorByEmail($value)
    {
        //$dbHALV3 = Zend_Db_Table_Abstract::getDefaultAdapter();
        $dbHALV3 = new Zend_Db_Adapter_Pdo_Mysql(['host' => 'localhost', 'username' => 'root', 'password' => 'password', 'dbname'   => 'HALV3']);
        // 1 on fait une recherche direct sur l'email de l'auteur voir si on le trouve
        $sqlRequest = "SELECT * FROM `REF_AUTHOR` WHERE EMAIL ='".trim(addslashes($value))."' and VALID = 'VALID'";
        $ep = $dbHALV3->query($sqlRequest);
        $array_db = $ep->fetchAll();
        if (count($array_db)>0){
            return $array_db[0]['AUTHORID'];
        }
        else return null;
    }

    /**
     * Ajout d'un fichier à un document
     *
     * @param Hal_Document $document : objet correpondant au rapport à insérer
     * @param string $filepath : chemin du fichier à ajouter
     *
     * @return Hal_Document modifié
     */
    private static function addFileHalDocument(Hal_Document $document, string $filepath)
    {
        $file = new Hal_Document_File();
        $file->setType('file');
        $file->setOrigin('author');
        $file->setDefault(1);
        $file->setName(basename($filepath));
        $file->setPath($filepath);
        $file->setSize(filesize($filepath));

        $document->setFiles([$file]);
        $document->getFiles()[0]->setDefault(true);

        return $document;
    }




    public function processDoc($produit,$id){
            $xml = new DOMDocument();
            $xml->appendChild($xml->importNode($produit,true));
            return Ccsd_Externdoc_Inra::createFromXML($id, $xml);

    }


    function addAuthorHalDocument(Hal_Document $document)
    {
        //todo à revoir pour ne pas avoir ce pb (supprimer les contrôles)
        $author = new Hal_Document_Author();
        $author->setFirstname('Prodinra');
        $author->setLastname('Prodinra');

        $structId = [92114];

        $author->setStructid($structId);

        $document->addAuthorWithAffiliations($author,$structId);

        return $document;
    }


}

$script = new ProdinraImport();
$script->run();