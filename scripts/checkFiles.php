<?php
/**
 * Script pour vérifier que les fichiers des dépôts en ligne sont là et non modifiés depuis leur dépôt
 */

$localopts = array(
    'p' => 'Fichier principal seulement',
    'docid-s' => 'Commencer a DOCID',
    'status|s-s' => 'seulement status X',
 );

/** @var Zend_Console_Getopt $opts */
require_once 'loadHalHeader.php';

Zend_Registry::set('Zend_Translate', Hal_Translation_Plugin::checkTranslator('fr'));

define('LOG_FILE', '/sites/logs/php/hal/' . basename(__FILE__) . 'log');

$verbose=false;
$debug=false;

if (isset($opts)) {
    if ($opts->verbose) {
        $verbose = true;
    }
    if ($opts->debug) {
        $debug = true;
    }
}

// Construction requete sql
$where = "1";
$checkPrincipal = false;
if ($opts->p) {
    $checkPrincipal = true;
    $where .= " AND MAIN = 1";
}

if ($opts->docid) {
    $where .= " AND DOCID >  $opts->docid";
}

$status = $opts->status;
if ($status) {
    if (preg_match('/\d+(,\d+)*/', $status)) {
        $where .= " AND DOCID IN (SELECT DOCID FROM DOCUMENT WHERE DOCSTATUS IN ($status)";
    } else {
        error_log("CheckFiles: Bad status: $status");
        exit(1);
    }
}
$checkUnused = !$checkPrincipal;

$db = Zend_Db_Table_Abstract::getDefaultAdapter();
// La requete de selection des fichiers
$sql = $db->select()->from('DOC_FILE')->where($where)->order('DOCID ASC');
// Pour savoir ou on en est...
$total = $db->fetchOne("SELECT COUNT(*) AS count FROM DOC_FILE WHERE $where");

/**
 * Compare la taille et le md5 d'un fichier avec la BDD
 * @param string $filename
 * @param int $dbFilesize
 * @param string $dbMd5
 * @param boolean $debug
 * @param int $docid
 * @param int $zeroFileSize
 * @return bool
 */
function isSameAsFile($filename, $dbFilesize, $dbMd5, $debug, $docid, &$zeroFileSize)
{
    $filemd5  = md5_file($filename);
    $filesize = filesize($filename);

    if ($filesize == 0) {
        $zeroFileSize++;
        Ccsd_Log::message("DOCID $docid Empty File", $debug, 'ERR', LOG_FILE);
    }

    $res = (($filemd5 == $dbMd5) && ((int)$filesize == (int)$dbFilesize));
    if ($res)  {
        Ccsd_Log::message("DOCID $docid DB md5  : $dbMd5 \t FILE md5  : $filemd5",       $debug, '', LOG_FILE);
        Ccsd_Log::message("DOCID $docid DB size : $dbFilesize \t FILE size : $filesize", $debug, '', LOG_FILE);

    } else {
        // Log different si inegalite
        if ($filemd5 == $dbMd5) {
            Ccsd_Log::message("DOCID $docid DB md5  : $dbMd5 \t FILE md5  : $filemd5",       $debug, 'ERR', LOG_FILE);
        }
        if ((int)$filesize == (int)$dbFilesize) {
            Ccsd_Log::message("DOCID $docid DB size : $dbFilesize \t FILE size : $filesize", $debug, 'ERR', LOG_FILE);
        }
    }
    return $res;
}

/**
 * @param string $dir
 * @return array
 */
function getListDir($dir) {
    $list = [];
    /**
     * @var RecursiveDirectoryIterator $iterator
     */
    try {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    } catch (UnexpectedValueException $e) {
        // Repertoire non existant... pas de fichier a verifier ;-)
        return $list;
    }
    $iterator->rewind();
    while($iterator->valid()) {
        if ($iterator->isDot()) {
            // the files to ignore
            $iterator->next();
            continue;
        }
        $filename = $iterator->getSubPathName();
        $list[$filename] = true;
        $iterator->next();
    }

    return $list;
}

$task = 0;
$missingFiles = 0;
$zeroFileSize = 0;
$mismatch = 0;
$match = 0;
$stmt = $db->query($sql);
debug("SQL request: $sql");
$prevdocid=-1;
$fileList = [];
while ($file = $stmt->fetch()) {
    $docid     = $file ['DOCID'];
    $dbMd5     = $file ['MD5'];
    $dbSize    = $file ['SIZE'];
    $principal = $file ['MAIN'];
    $filename  = $file ['FILENAME'];

    $racineDoc = Hal_Document::getRacineDoc_s($docid);
    $filePath = $racineDoc . $filename;

    // Les docid sont croissants!
    // On prepare pour regarder les fichiers en trop sur ce docid
    // Pas a faire si on ne s'occupe que des fichiers principaux!
    if ($checkUnused && $prevdocid != $docid) {
        // On regarde les fichiers restants de $fileList pour le docid precedent
        foreach ($fileList as $unused => $v) {
            Ccsd_Log::message("Fichier present mais pas dans DB pour DOCID $prevdocid: $unused", $verbose, 'ERR', LOG_FILE);
        }

        // On initialise $fileList pour le nouveau docid
        $fileList = getListDir($racineDoc);
        $prevdocid = $docid;
    }

    Ccsd_Log::message($task . '/' . $total, $verbose, '', LOG_FILE);

    $pathFilename = $racineDoc . $filename;
    // Si pas principal, on pourrait utiliser $fileList, mais si principal... NON
    $fileExist = file_exists($pathFilename);

    if ($checkUnused) {
        unset($fileList[$filename]);
    }
    $principalLog=$principal ? "(MAIN)": '';
    if ($fileExist) {
        Ccsd_Log::message("DOgrep archivable_ CID $docid file found named : $pathFilename", $debug, '', LOG_FILE);

        if (isSameAsFile($pathFilename, $dbSize, $dbMd5, $debug, $docid, $zeroFileSize)) {
            Ccsd_Log::message("DOCID $docid match found", $debug, '', LOG_FILE);
            $match++;
        } else {
            Ccsd_Log::message("DOCID $docid MISMATCH $principalLog", $verbose, 'ERR', LOG_FILE);
            $mismatch++;
        }
    } else {
        Ccsd_Log::message("DOCID $docid $principalLog file NOT found in : $pathFilename", $verbose, 'ERR', LOG_FILE);
        $missingFiles++;
    }
    $task++;
}

$timeend = microtime(true);
$time = $timeend - $timestart;

Ccsd_Log::message('Total Files: '     . $total, $verbose, '', LOG_FILE);
Ccsd_Log::message('Files OK: '        . $match, $verbose, '', LOG_FILE);
Ccsd_Log::message('Files mismatch: '  . $mismatch, $verbose, '', LOG_FILE);
Ccsd_Log::message('Files empty: '     . $zeroFileSize, $verbose, '', LOG_FILE);
Ccsd_Log::message('Files missing: '   . $missingFiles, $verbose, '', LOG_FILE);
Ccsd_Log::message('Début du script: ' . date("H:i:s", $timestart) . '/ fin du script: ' . date("H:i:s", $timeend), $verbose, '', LOG_FILE);
Ccsd_Log::message('Script executé en ' . number_format($time, 3) . ' sec.', $verbose, '', LOG_FILE);





