<?php
/**
 * Convertisseur d'une vidéo au format mp4
 * CRON à installer
 *
 * Created by PhpStorm.
 * User: yannick
 * Date: 24/05/2016
 * Time: 17:50
 * Modified by : Sarah !
 */


/*
  ---------------------------------------------------
  Security:
  escapeshellarg * MUST*   be used to escape individual arguments to shell functions coming from user input
  escapeshellarg ** MUST ** be used to escape individual arguments to shell functions coming from user input
  escapeshellarg *** MUST *** be used to escape individual arguments to shell functions coming from user input
---------------------------------------------------
*/


foreach (['/opt/ffmpeg/ffmpeg', '/usr/bin/ffmpeg'] as $cmd) {
    if (file_exists($cmd)) {
        define('FFMPEG', $cmd);
        break;
    }
}

if (!defined('FFMPEG')) {
    die("Can't find ffmpeg");
}

// escapeshellarg strip les lettres accentuees si on n'est pas dans une locale Utf8
setlocale(LC_CTYPE, "fr_FR.UTF-8");

$localopts = [
    'docid|D=i' => 'Docid du document a convertir',
    'normalize' => 'Converti un mp4 en mp4 afin de le normaliser',
    'test|t' => 'Test mode',
    // 'convertOnly' => "Reconverti le fichier, sans changer la base de donnee (hormi la duree et filezise)"
];
require_once 'loadHalHeader.php';


define('LOG_PATH', '/sites/logs/php/hal/convertVideo-' . APPLICATION_ENV . '.log');
define('LOCKFILE', 'processing-' . APPLICATION_ENV);


if (!need_user('apache')) {
    print "WARNING: ce script devrait etre lance en utilisateur " . APACHE_USER . "\n";
}

$test = isset($opts->test);
if (isset($opts->normalize) && (!isset($opts->D))) {
    die("Normalize option must be used with a docid (-D option)");
}

verbose('');
verbose('', '****************************************', 'blue');
verbose('', "**  Script de conversion d'une vidéo  **", 'blue');
verbose('', '****************************************', 'blue');
verbose('> Début du script: ', date("H:i:s", $timestart), '');
verbose('> Environnement: ', APPLICATION_ENV, '');
verbose('', '----------------------------------------', 'yellow');

loginfile(LOG_PATH, '***** Nouvelle itération du script *****');
loginfile(LOG_PATH, 'Environnement : ' . APPLICATION_ENV);

// 1- Selection des vidéos en attente de modération
/** @var Zend_Db_Adapter_Pdo_Mysql $db */

$normalizeMode = false;

if (isset($opts->normalize)) {
    $sql = $db->select()
        ->from(['d' => Hal_Document::TABLE])
        ->join(['file1' => Hal_Document_File::TABLE], "d.DOCID = file1.DOCID AND file1.EXTENSION = 'mp4'");
    $sql->where("d.TYPDOC = 'VIDEO'");
    $sql->where('d.DOCID = ?', (int)$opts->D);
    $normalizeMode = true;
} else {
    if (isset($opts->D)) {
        $sql = $db->select()
            ->from(['d' => Hal_Document::TABLE])
            ->join(['file1' => Hal_Document_File::TABLE], "d.DOCID = file1.DOCID AND file1.EXTENSION != 'mp4'")
            ->joinLeft(['file2' => Hal_Document_File::TABLE], 'file1.DOCID=file2.DOCID AND file1.FILEID != file2.FILEID', ['EXTENSION2' => 'file2.EXTENSION']);
        $sql->where("d.TYPDOC = 'VIDEO'");
        $sql->where('d.DOCID = ?', (int)$opts->D);
    } else {
        $sql = $db->select()
            ->from(['d' => Hal_Document::TABLE])
            ->join(['file1' => Hal_Document_File::TABLE], "d.DOCID = file1.DOCID AND file1.MAIN = 1 AND file1.EXTENSION != 'mp4'")
            ->joinLeft(['file2' => Hal_Document_File::TABLE], 'file1.DOCID=file2.DOCID AND file1.FILEID != file2.FILEID', []);
        $sql->where("d.TYPDOC = 'VIDEO'  AND file2.EXTENSION is NULL");
    }
}

/** Recherche de Document de type video qui ont un fichier video principal qui n'est pas de type MP4 et qui n'a pas de fichier correspondant en Mp4
 * // select * from DOCUMENT
 * //       join DOC_FILE as d1 on DOCUMENT.DOCID = d1.DOCID
 * //       left join DOC_FILE as d2 ON d1.DOCID=d2.DOCID AND d1.FILEID != d2.FILEID
 * //    where d1.EXTENSION != 'mp4' AND DOCUMENT.TYPDOC = 'VIDEO'  AND d2.EXTENSION is NULL;
 */


/**
 * Convert $fromfile into $tofile with ffmpeg, and return size of $tofile if success, 0 if failure
 * @param string $fromfile
 * @param string $tofile
 * @return int
 */
function ffmpeg(string $fromfile, string $tofile): int
{
    global $test;


    $fromfileClean = escapeshellarg($fromfile);
    $tofileClean = escapeshellarg($tofile);


    if ($test) {
        print("Exec: " . FFMPEG . ' -i "' . $fromfileClean . '" "' . $tofileClean . '"' . "\n");
        $filesize = 1;
    } else {
        exec(FFMPEG . ' -i "' . $fromfileClean . '" "' . $tofileClean . '"');
        loganddebug('> Fichier converti : ', $tofileClean, 'red', LOG_PATH);

        if (!is_readable($tofile)) {
            $filesize = 0;
        } else {
            $filesize = filesize($tofile);
        }


    }
    return $filesize;
}

/**
 * @param string $file
 * @return int
 */
function getDuration(string $file): int
{
    global $test;

    $escFile = escapeshellarg($file);

    if ($test) {
        $duration = 424242;// dumb value for the dry run
        print("Exec: " . FFMPEG . " -i '" . $escFile . "' 2>&1 | grep Duration | awk '{print $2}' | tr -d ,\n");
    } else {
        $duration = shell_exec(FFMPEG . " -i '" . $escFile . "' 2>&1 | grep Duration | awk '{print $2}' | tr -d ,");
    }
    return (int)$duration;
}

/**
 * @param Zend_Db_Adapter_Pdo_Mysql $db
 * @param int $docid
 * @param int $duration
 * @throws Zend_Db_Adapter_Exception
 */
function insertOrUpdateDuration($db, $docid, $duration)
{
    global $test;
    $data = [
        'DOCID' => $docid,
        'METANAME' => 'duration',
        'METAVALUE' => $duration
    ];
    $sql = $db->select()
        ->from(Hal_Document_Metadatas::TABLE_META)
        ->where('DOCID = ?', $docid)
        ->where('METANAME = ?', 'duration');

    $rowDuration = $db->fetchRow($sql);

    // Enregistrement en base
    if (!$rowDuration) {
        if ($test) {
            print "Insert DB: " . Hal_Document_Metadatas::TABLE_META . " duration of $docid is $duration  \n";
        } else {
            $db->insert(Hal_Document_Metadatas::TABLE_META, $data);
        }
    } else {
        if ($test) {
            print "Update DB: " . Hal_Document_Metadatas::TABLE_META . " duration of $docid is $duration  \n";
        } else {
            $db->update(Hal_Document_Metadatas::TABLE_META, $data, "DOCID=" . $docid . " AND METANAME='duration'");
        }
    }

}

/**
 * @param Zend_Db_Adapter_Pdo_Mysql $db
 * @param int $fileid
 * @throws Zend_Db_Adapter_Exception
 */
function transformFileIntoAnnexe($db, $fileid)
{
    global $test;
    $docdata = [
        'MAIN' => 0,
        'FILETYPE' => 'annex'
    ];
    if ($test) {
        print("Update DB: " . Hal_Document_File::TABLE . " : Transformation en annexe de $fileid\n");
    } else {
        $db->update(Hal_Document_File::TABLE, $docdata, "FILEID=$fileid");
    }

}

/**
 * @param Zend_Db_Adapter_Pdo_Mysql $db
 * @param int $docid
 * @param array $data
 * @param int $main
 * @throws Zend_Db_Adapter_Exception
 */
function updateSomeInfo($db, $docid, $data, $main)
{
    global $test;
    if ($test) {
        print("Update DB: " . Hal_Document_File::TABLE . "\n");
        foreach ($data as $k => $v) {
            print "\tChange $k into $v\n";
        }
    } else {
        $db->update(Hal_Document_File::TABLE, $data, "DOCID=$docid AND MAIN=$main");
    }
}

/**
 * @param Zend_Db_Adapter_Pdo_Mysql $db
 * @param array $data
 * @throws Zend_Db_Adapter_Exception
 * @todo: devrait utiliser constructeur et save de
 * @see Hal_Document_File
 */
function insertFile($db, array $data)
{
    global $test;
    if ($test) {
        print("Insert DB: du nouveau fichier mp4\n");
    } else {
        $db->insert(Hal_Document_File::TABLE, $data);
    }
}

/**
 * @param array $row
 * @return array
 */
function renameMainFile(array $row)
{
    global $test;
    $filename = $row['FILENAME'];
    $newFilename = preg_replace('/\.mp4$/', '-author.mp4', $filename);
    $row['FILENAME'] = $newFilename;
    $row['OLDFILENAME'] = $filename;
    if ($test) {
        print "rename($filename, $newFilename)\n";
    } else {
        rename($filename, $newFilename);
    }
    return $row;
}

/**
 * @param Zend_Db_Adapter_Pdo_Mysql $db
 * @param array $row
 * @return bool
 */
function convert2mp4($db, $row, $normalizeMode)
{
    global $test;
    $docid = (int)$row['DOCID'];
    $identifiant = $row['IDENTIFIANT'];
    $filename = $row['FILENAME'];
    $fileid = $row['FILEID'];
    $datevisible = $row['DATEVISIBLE'];
    $docstatus = $row['DOCSTATUS'];
    $fileext2 = $row['EXTENSION2'];
    // Si fichier deja converti precedemment, (en cas d'argument docid, le fichier principal est deja mp4, et la base est deja correcte
    // seul la duration et le filesize seront mise a jour apres une nouvelle conversion
    $convertOnly = ($fileext2 == 'mp4');
    if ($convertOnly) {
        debug("Mode ConvertOnly\n");
    }

    loganddebug("> Traitement du document $identifiant :", $docid, 'red', LOG_PATH);

    $dirPath = Hal_Document::getRacineDoc_s($docid);
    //3- Vérification du flag "Déjà en cours de traitement"
    $lockfile = $dirPath . LOCKFILE;
    if (file_exists($lockfile)) {
        loganddebug('', '>> Déjà en cours de traitement !', 'red', LOG_PATH);
        return false;
    } else {
        touch($lockfile);
    }
    $filepath = $dirPath . $filename;
    // Remplacement extension par mp4
    if ($normalizeMode) {
        $newfilepath = $row['OLDFILENAME'];
    } else {
        $newfilepath = $dirPath . basename($filepath, '.' . pathinfo($filepath, PATHINFO_EXTENSION)) . '.mp4';
    }
    //Commande pour générer la version mp4 de la vidéo
    if ($convertOnly && file_exists($newfilepath)) {
        if ($test) {
            print "Unlink $newfilepath\n";
        } else {
            unlink($newfilepath);
        }
    }


    $filesize = ffmpeg($filepath, $newfilepath);


    if ($filesize == 0) {
        loganddebug('> ERREUR file has zero length : ', $newfilepath, 'red', LOG_PATH);
        unlink($lockfile);
        return false;
    }

    //5- Mise à jour de la métadonnée duration pour la vidéo
    $duration = getDuration($newfilepath);

    try {
        insertOrUpdateDuration($db, $docid, $duration);

        // Mise à jour du document en base: transformation en annexe
        if (!$convertOnly) {
            transformFileIntoAnnexe($db, $fileid);
        }

        if ($convertOnly) {
            $newdocdata = [
                'SIZE' => $filesize,
                'MD5' => md5($newfilepath),
            ];
            updateSomeInfo($db, $docid, $newdocdata, 1);

        } else {
            // Insertion du fichier converti
            $newdocdata = [
                'DOCID' => $docid,
                'FILENAME' => basename($newfilepath),
                'INFO' => '',
                'MAIN' => 1,
                'EXTENSION' => 'mp4',
                'TYPEMIME' => 'video/mp4',
                'SIZE' => $filesize,
                'MD5' => md5($newfilepath),
                'FILETYPE' => 'file',
                'FILESOURCE' => '',
                'DATEVISIBLE' => $datevisible,
                'TYPEANNEX' => '',
                'IMAGETTE' => NULL,
                'ARCHIVED' => NULL
            ];
            insertFile($db, $newdocdata);

        }
    } catch (Zend_Db_Adapter_Exception $e) {
        loganddebug('ERREUR FATALE: ' . $e->getMessage() . ' pour ', $newfilepath, 'red', LOG_PATH);
        // On pourrait qd meme recalculer le document...
        unlink($lockfile);
        return false;
    }
    if (!$test) {
        // Réindexation si le document est en ligne
        Hal_Document::deleteCaches($docid);
        if ($docstatus != Hal_Document::STATUS_BUFFER) {
            Ccsd_Search_Solr_Indexer::addToIndexQueue([$docid]);
        }
    }
    // Suppression du flag 'En cours de traitement'
    unlink($lockfile);
    return true;
}

debug('> Requete : ', $sql);

foreach ($db->fetchAll($sql) as $row) {
    if ($normalizeMode) {
        // EXTENSION2 n'existe pas...
        $row['EXTENSION2'] = null;
        $row = renameMainFile($row);
    }
    convert2mp4($db, $row, $normalizeMode);
}


$timeend = microtime(true);
$time = $timeend - $timestart;
debug('', '----------------------------------------', 'yellow');
debug('> Fin du script: ' . date("H:i:s", $timeend));
debug('> Script executé en ' . number_format($time, 3) . ' sec.');
loganddebug('', '', '', LOG_PATH);
