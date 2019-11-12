<?php
/**
 * Insertion des vidéos de Campus AAR en base
 */

//require_once 'loadZendHeader.php';
define('LOGFILE', '/sites/logs/php/hal/campusaar.log');

// Définition des variables par paramètre du script
$localopts = array(
    'login-l' => 'Login du déposant',
    'mdp-m' => 'Mot de passe du déposant',
    'pid-p' => 'Identifiant du portail',
    'src-s' => "Chemin vers le fichier d'entrée",
    'stock-k' => "Chemin vers le dossier de stockage",
    'thesaurus-t' => 'Chemin vers les traduction de thesaurus'
 );

require_once 'loadHalHeader.php';

if (isset($opts)) {
    $login = $opts->login;
    $mdp = $opts->mdp;
    $pid = $opts->pid;
    $src = $opts->src;
    $stock = $opts->stock;
    $thesaurus = $opts->thesaurus;
}

define ('SPACE', dirname(__FILE__) . '/../data/portail/campus-aar/');
define('DEFAULT_SPACE', dirname(__FILE__) . '/../data/portail/default/');

// Définition des variables par entrée en ligne de commande
if (!isset($login) && !isset($mdp)) {
    println('', "Données de l'utilisateur", 'green');
    $login = getParam("Veuillez rentrer votre login", true);
    $mdp = getParam("Veuillez rentrer votre mot de passe", true);
} 

if(!isset($pid)) {
    println();
    println('', 'Portail de dépôt', 'green');
    $pid = getParam("Veuillez rentrer l'identifiant du portail", true, ["4547", "4604"], "4604");
}

// C'est pas contre toi, @Bruno ! Il faudrait que je change pleins de trucs dans Hal_Document si je voulais pas définir une constante non constante... donc pour l'instant, c'est pas clean
define ('SITEID', $pid);

if (!isset($src)) {
    println();
    println('', "Fichier d'entrée", 'green');
    $src = getParam("Veuillez rentrer le chemin vers le fichier d'entrée", true);
}

if (!isset($stock))
    $stock = getParam("Veuillez rentrer le chemin vers le dossier de stockage des vidéos", true);

if (!isset($thesaurus))
    $thesaurus = getParam("Veuillez rentrer le chemin vers le dossier de traduction des thesaurus", "/sites/hal/data/portail/default/languages/fr/");

/*---------  SCRIPT  -----------*/

$correspTable = [
    0 => 'file',
    1 => 'title_fr',
    2 => 'title_en',
    3 => 'subTitle',
    4 => 'abstract_fr',
    5 => 'abstract_en',
    6 => 'domain',
    7 => 'campusaar_classaar',
    8 => 'keywords_fr',
    9 => 'language',
    10 => 'date',
    11 => 'campusaar_lieu',
    12 => 'duration',
    13 => 'localReference',
    14 => 'campusaar_collaboration',
    15 => 'anrProject',
    16 => 'seeAlso',
    18 => 'authors_aut',
    19 => 'authors_int',
    20 => 'authors_dis',
    21 => 'authors_ctb',
    22 => 'authors_dir',
    23 => 'authors_sad',
    24 => 'authors_edt',
    25 => 'authors_med',
    26 => 'authors_pro',
    27 => 'campusaar_productor_local',
    28 => 'campusaar_genre',
    29 => 'campusaar_refAnalyse_local'
];

Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'fr' ) );


/* Authentification Du déposant
*/
function authenticateUser($login, $mdp)
{
    $mySqlAuthAdapter = new Ccsd_Auth_Adapter_Mysql($login, $mdp);
    $halUser = new Hal_User();
    $mySqlAuthAdapter->setIdentity($halUser);
    $result = Hal_Auth::getInstance()->authenticate($mySqlAuthAdapter);
    
    if($result->getCode() == Zend_Auth_Result::SUCCESS)
        return $halUser->getUid();
    else
        return 0;
}

/**
* Création de la structure Auteur 
*/
function createHalDocument($pid, $uid, $metas)
{
    $document = new Hal_Document();
    $document->setTypdoc('VIDEO');
    
    $document->setSid($pid);
    $document->setContributorId($uid);
    
    $document->setMetas($metas);
    return $document;
}

/**
* Création de la structure Auteur
*/
function createHalAuthors($authorstruct, $document)
{
    foreach($authorstruct as $author) {
        
        $authorDocument = new Hal_Document_Author();
        
        $authorName = array();
        $authorName['lastname'] = $author['lastname'];
        $authorName['firstname'] = $author['firstname'];
        $authorDocument->set($authorName);
        
        $authorDocument->setQuality($author['quality']);

        if(isset($author['structid'])) {
            $structidx = $document->addStructure(new Hal_Document_Structure($author['structid']));
            $authorDocument->addStructidx($structidx);
        }
        
        $document->addAuthor($authorDocument);
    }
}

/**
* Création de la structure Auteur 
*/
function createHalFile($stock, $filename, $document)
{
    $file = new Hal_Document_File();
    $file->setType('file');
    $file->setOrigin('author');
    $file->setDefault(1);
    $file->setName($filename);
    $file->setPath($stock.$filename);
   
    //??!!??
    //$file->setDefault(true);
    $document->setFiles([$file]);
    $document->getFiles()[0]->setDefault(true);
}

/**
 * Utilitaires pour récupérer les données
 * 
 */

function cleanValue($value)
{
    return trim ($value, ' "');
}

function explodeValue($value, $separators = false)
{    
    if ($separators !== false) {
        foreach($separators as $separator)
            $value = str_replace($separator, "\r", $value);
    }
    $return = [];
    foreach (explode("\r", cleanValue($value)) as $v) {
        $v = cleanValue($v);
        if ($v != '') {
            $return[] = $v;
        }
    }
    return $return;
}

function explodeKeyValue($value, $separators = false)
{
    if ($separators !== false) {
        foreach($separators as $separator)
            $value = str_replace($separator, "\r", $value);
    }
    $return = [];
    $pairValue = explode("\r", cleanValue($value));

    $return[cleanValue($pairValue[0])] = cleanValue($pairValue[1]);

    return $return;
}

/**
 *
 *
 */
function getTradArrayFromFile($file)
{
    $content = file_get_contents($file);

    $lines['line'] = explodeValue($content, [",\n"]);

    $values = array();
    foreach ($lines['line'] as $k => $line)
        $values = array_merge(explodeKeyValue($line, [" => "]), $values);

    return $values;
}

/**
 * Récupération du code pour une traduction
 *
 * @param $traduction
 * @param $tradArray
 * @return string
 */
function getCodeFromTrad($searched, $tradArray)
{
    foreach ($tradArray as $code => $trad) {
        if ($trad == $searched)
            return $code;
    }

    return "";
}

/*
 * Traduction des données en metadatas pour HAL
 * $metadatas = ['metas' => [], 'autstruct' => ['authors' => [], 'structures' => []], 'file' => ''];
 */
function getMetasFromData($data, $correspTable, $thesaurus)
{
    $gavFilepath = $thesaurus . "gav.php";
    $aarFilepath = $thesaurus . "aar.php";

    $gavArray = getTradArrayFromFile($gavFilepath);
    $classaarArray = getTradArrayFromFile($aarFilepath);

    $metas = array();
    
    for ($i=0 ; $i<sizeof($data) ; $i++) {

        if (isset($correspTable[$i])) {
            $field = $correspTable[$i];

            $value = $data[$i];

            if ($value == '') continue;

            $author_quality = ['authors_aut', 'authors_int', 'authors_dis', 'authors_dir', 'authors_ctb', 'authors_pro', 'authors_med', 'authors_sad', 'authors_edt', 'authors_prd'];

            $metas['metas']['licence'] = 'http://creativecommons.org/licenses/by-nc-nd/';

            if ($field == 'title_fr') {
                $metas['metas']['title']['fr'] = cleanValue($value);
            } else if ($field == 'subTitle') {
                $metas['metas'][$field]['fr'] = cleanValue($value);
            } else if ($field == 'abstract_fr') {
                $metas['metas']['abstract']['fr'] = cleanValue($value);
            } else if ($field == 'title_en') {
                $metas['metas']['title']['en'] = cleanValue($value);
            } else if ($field == 'abstract_en') {
                $metas['metas']['abstract']['en'] = cleanValue($value);
            } else if ($field == 'language') {
                $values = explodeValue($value);

                if (isset($values)) {
                    $metas['metas'][$field] = strtolower($values[0]);
                    array_shift($values);
                }

                foreach ($values as $lang)
                    $metas['metas']['campusaar_language'][] = strtolower($lang);

            } else if ($field == 'campusaar_refinterne' || $field == 'seeAlso') {
                $metas['metas'][$field] = explodeValue($value, [",", ";"]);
            } else if ($field == 'keywords_fr') {
                $metas['metas']['keyword']['fr'] = explodeValue($value, [",", ";"]);
            } else if ($field == 'campusaar_collaboration') {
                $metas['metas'][$field] = [cleanValue($value)];
            } else if ($field == 'campusaar_classaar') {
                $metasInt['metas'] = explodeValue($value, [",", ";"]);

                foreach ($metasInt['metas'] as $interMetas)
                    $metas['metas'][$field][] = getCodeFromTrad($interMetas, $classaarArray);
                
            } else if ($field == 'campusaar_genre'){
                $metasInt['metas'] = explodeValue($value, [",", ";"]);

                foreach ($metasInt['metas'] as $interMetas)
                    $metas['metas'][$field][] = getCodeFromTrad($interMetas, $gavArray);

            } else if ($field == 'duration') {
                $metas['metas'][$field] = cleanValue($value);
            } else if ($field == 'file') {
                $metas['file'] = cleanValue($value).'.mp4';
            } else if ($field == 'domain') {
                $metas['metas'][$field] = [];
                
                foreach(explodeValue($value) as $v) {
                    
                    // On choisit d'abord le domaine exacte dans les SHS
                    $jsonContent = file_get_contents('https://api.archives-ouvertes.fr/ref/domain?q=fr_domain_s:"Sciences de l'."'".'Homme et Société/"' . str_replace(' ', '%20', $v) .'"');
                    $decodedContent = json_decode($jsonContent);
                    if (isset($decodedContent->response) && isset($decodedContent->response->docs)) {
                        foreach ($decodedContent->response->docs as $d) {
                            $metas['metas'][$field][] = trim(substr($d->label_s, 0, strpos($d->label_s, ' =')));
                        }
                    }
                    
                    // Si on a pas trouvé le domaine exacte, on prend le premier trouvé
                    else {
                        $jsonContent = file_get_contents('https://api.archives-ouvertes.fr/ref/domain?q="' . str_replace(' ', '%20', $v) .'"');
                        $decodedContent = json_decode($jsonContent);
                        
                        if (isset($decodedContent->response) && isset($decodedContent->response->docs)) {
                            foreach($decodedContent->response->docs as $d) {
                                $metas['metas'][$field][] = trim(substr($d->label_s, 0, strpos($d->label_s, ' =')));
                                break;
                            }
                        }
                    }
                    
                }
            } else if (in_array ($field, $author_quality)) {
                foreach(explodeValue($value) as $v) {
                                                            
                    $idx = false;
                    $tmp = explode( '/ ', $v);
                    $tmp[0] = trim($tmp[0]);
                    $author = [
                        'lastname' => substr($tmp[0], 0, strrpos($tmp[0], ' ')),
                        'firstname' => substr($tmp[0], strrpos($tmp[0], ' ') +1),
                        'quality' => substr($field, strrpos($field, '_') +1),
                    ];
                    
                    if (count($tmp) == 2) {
                        // Récupération de l'identifiant uniquement
                        preg_match("/^[ ]?[0-9]*/", $tmp[1], $identifiant);
                        
                        if (isset($identifiant[0]))
                            $author['structid'] = (int)$identifiant[0];
                    }
                    
                    $metas['authors'][] = $author;
                }
            } else {
                $metas['metas'][$field] = cleanValue($value);
            }

        }
    }
        
    return $metas;
}

/*
 * Principale fonction
 * 1- Récupération des données
 * 2- Création du Hal_Document / Hal_Document_File / Hal_Doument_Author
 * 3- Validation du Hal_Document
 * 4- Enregistrement en base 
 */
function main($pid, $uid, $src, $stock, $correspTable, $thesaurus)
{

    if(file_exists($src)) {

        $src = mb_convert_encoding(file_get_contents($src),'utf-8','utf-16');
        
        foreach(explode("\n", $src) as $rowNb => $row) {

            if ($rowNb == 0) {
                continue;
            }    

            $data = explode("\t", $row);

            $metadatas = getMetasFromData($data, $correspTable, $thesaurus);

            debug("Traitement du fichier : " . $metadatas['file']);
                           
            if (isset($metadatas['file']) && $metadatas['file'] != "" && file_exists($stock. $metadatas['file'])) {

                //---- Création des structures de HAL (Document ; Document_File ; Document_Author)
                $document = createHalDocument($pid, $uid, $metadatas['metas']);
                createHalFile($stock, $metadatas['file'], $document);
                createHalAuthors($metadatas['authors'], $document);
    
                //---- Vérification de la validité du document
                $docValid = Hal_Document_Validity::isValid($document);

                //---- Enregistrement du document
                if (isset($docValid['valid'])) {
            
                    $document->setTypeSubmit(Hal_Settings::SUBMIT_INIT); 
                    $document->initFormat();

                    if ($docid = $document->save(0, false)) {
                        debug("FICHIER ENREGISTRÉ AVEC DOCID = ".$docid);
                        exit;
                    } else {
                        debug("IMPOSSIBLE D'ENREGISTRER LE FICHIER");
                    }
                } else {
                    debug("DOC NON VALIDE");
                }
            } else {
                debug("LE FICHIER N'EXISTE PAS !");   
                exit;
            }
        }
    } else {
        debug("LE FICHIER D'ENTRÉE : ". $src ." N'EXISTE PAS !");
    }
}

$uid = authenticateUser($login, $mdp);

if($uid)
    main($pid, $uid, $src, $stock, $correspTable, $thesaurus);
else
    debug("UTILISATEUR NON AUTHENTIFIÉ !");



?>
