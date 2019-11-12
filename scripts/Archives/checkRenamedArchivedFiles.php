<?php
/**
 * cherche les fichiers archivés qui ont remplacé la version de l'auteur en ligne
 * comportement stoppé le 2015-05-26
 */
ini_set ( "memory_limit", '4096M' );
ini_set ( "display_errors", '1' );
$timestart = microtime ( true );
define ( 'DEFAULT_ENV', 'production' );
define ( 'MAX_DOC_IN_ALERT', 500 );

define ( 'ENV_DEV', 'development' );
define ( 'CONFIG', 'config/' );
define ( 'SPACE_DATA', __DIR__ . '/../data' );
define ( 'SPACE_PORTAIL', 'portail' );
define ( 'MODULE', SPACE_PORTAIL );
define ( 'PORTAIL', 'default' );
define ( 'SPACE', SPACE_DATA . '/' . MODULE . '/' . PORTAIL . '/' );

define ( 'LOG_FILE', realpath ( sys_get_temp_dir () ) . '/' . basename ( __FILE__ ) . 'log' );

set_include_path ( implode ( PATH_SEPARATOR, array_merge ( array (
		'/sites/phplib' 
), array (
		get_include_path () 
) ) ) );

require_once ('Zend/Loader/Autoloader.php');
$autoloader = Zend_Loader_Autoloader::getInstance ();
$autoloader->setFallbackAutoloader ( true );

define ( 'APPLICATION_PATH', __DIR__ . '/../application' );

try {
	$opts = new Zend_Console_Getopt ( array (
			'help|h' => ' cette aide',
			'application_env|e=s' => 'definit APPLICATION_ENV (par defaut = ' . DEFAULT_ENV . ')' 
	) );
	$parseResult = $opts->parse ();
} catch ( Zend_Console_Getopt_Exception $e ) {
	exit ( $e->getMessage () . PHP_EOL . PHP_EOL . $opts->getUsageMessage () );
}

if ($opts->help != false || ($opts->application_env != false && ! in_array ( $opts->application_env, array (
		'testing',
		'preprod',
		'production',
		'development' 
) ))) {
	die ( $opts->getUsageMessage () . PHP_EOL );
}

if ($opts->application_env == FALSE) {
	define ( 'APPLICATION_ENV', DEFAULT_ENV );
} else {
	define ( 'APPLICATION_ENV', $opts->application_env );
}

switch (APPLICATION_ENV) {
	case 'development' :
		define ( 'APPLICATION_PATH', realpath ( __DIR__ . '/../../../../hal_test/application' ) );
		break;
	
	case 'testing' :
		define ( 'APPLICATION_PATH', realpath ( __DIR__ . '/../../../../hal_test/application' ) );
		break;
	
	case 'preprod' :
		define ( 'APPLICATION_PATH', realpath ( __DIR__ . '/../../../../hal_preprod/application' ) );
		break;
	
	case 'production' :
		define ( 'APPLICATION_PATH', realpath ( __DIR__ . '/../../../../hal/application' ) );
		break;
}

try {
	$application = new Zend_Application ( APPLICATION_ENV, APPLICATION_PATH . '/configs/application.ini' );
} catch ( Exception $e ) {
	
	echo $e->getMessage ();
}

$application->getBootstrap ()->bootstrap ( array (
		'db' 
) );

foreach ( $application->getOption ( 'consts' ) as $const => $value ) {
	define ( $const, $value );
}

$autoloader = Zend_Loader_Autoloader::getInstance ();
$autoloader->setFallbackAutoloader ( true );

define ( 'SPACE_DATA', APPLICATION_PATH . '/../data' );
define ( 'SPACE_PORTAIL', 'portail' );
define ( 'MODULE', 'portail' );

ini_set ( "display_errors", '1' );

Zend_Registry::set ( 'languages', Hal_Settings::getLanguages () );
Zend_Registry::set ( 'Zend_Locale', new Zend_Locale ( 'fr' ) );

Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'fr' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'en' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'es' ) );
Zend_Registry::set ( 'Zend_Translate', Hal_Translation_Plugin::checkTranslator ( 'eu' ) );

$db = Zend_Db_Table_Abstract::getDefaultAdapter ();
$sql = $db->select ()->from ( 'DOC_FILE' )->where ( "ARCHIVED < '2015-05-26'" )->order ( 'DOCID DESC' );
$filesArray = $db->fetchAll ( $sql );

$total = count ( $filesArray );

Ccsd_Log::message ( 'DB' . "\t" . 'FILE', '', '', LOG_FILE );




function isSameAsFile($filename, $filesize, $md5) {
	
	if ( (md5_file ( $filename ) == $md5) AND (filesize ( $filename ) == $filesize) ) {
		return true;
	} else {
		return false;
	}
	
}


function moveSubmitterBackupOnline($filename, $path ) {
	// tests.pdf => archivable_test.pdf
	//rename($path. $filename, $path . Ccsd_Archive::$prefixe_version_convertie_archivable . $filename);
	
	// TODO tests file exist avant rename
	
	Ccsd_Log::message ( 'mv '  . $path. $filename . ' '  . $path . Ccsd_Archive::$prefixe_version_convertie_archivable . $filename, '', '', LOG_FILE );
	
	// restaure original du déposant ORI1_test.pdf => tests.pdf
	//rename($path . Ccsd_Archive::$_PREFIXE_SAUVEGARDE_AVANT_CONVERSION . $filename, $path. $filename);
	Ccsd_Log::message ( 'mv ' . $path . Ccsd_Archive::$_PREFIXE_SAUVEGARDE_AVANT_CONVERSION . $filename . ' '  . $path. $filename, '', '', LOG_FILE );
	
	
}




$task = 0;
$impactedDeposits = 1;
foreach ( $filesArray as $file ) {
	
	$dbMd5 = $file ['MD5'];
	$dbSize = $file ['SIZE'];
	
	$d = Hal_Document::find ( $file ['DOCID'] );
	$filePath = $d->getRacineDoc () . $file ['FILENAME'];
	
	$fileMd5 = md5_file ( $filePath );
	$fileSize = filesize ( $filePath );
	
	Ccsd_Log::message ( $task . '/' . $total, '', '', LOG_FILE );
	
	if (($dbMd5 != $fileMd5) and ($dbSize != $fileSize)) {
		
		$originalFilePathFID = $d->getRacineDoc () . Ccsd_Archive::$_PREFIXE_SAUVEGARDE_AVANT_CONVERSION . $file ['FILEID'] . "." . $file ['EXTENSION'];
		$hasOriginalFID = file_exists ( $originalFilePathFID );
		
		$originalFilePathFilename = $d->getRacineDoc () . Ccsd_Archive::$_PREFIXE_SAUVEGARDE_AVANT_CONVERSION . $file ['FILENAME'];
		$hasOriginalFilename = file_exists ( $originalFilePathFilename );
		
		if ($hasOriginalFID) {
			Ccsd_Log::message ( 'DOCID ' . $file ['DOCID'] . ' SUBMITTER file found named : ' . $originalFilePathFID, '', '', LOG_FILE );
			
			if (isSameAsFile($originalFilePathFID, $dbSize, $dbMd5)) {
				Ccsd_Log::message ( 'DOCID ' . $file ['DOCID'] . ' SUBMITTER fileID md5 + size matches DB md5', '', '', LOG_FILE );
				//moveSubmitterBackupOnline($file ['FILEID'] . "." . $file ['EXTENSION'], $d->getRacineDoc () );
				Ccsd_Log::message ( 'Utilisation du fileid', '', 'ERR', LOG_FILE );
				
			} else {
				Ccsd_Log::message ( 'DOCID ' . $file ['DOCID'] . ' SUBMITTER fileID md5 + size DOES NOT match DB md5', '', '', LOG_FILE );
			}

		} elseif ($hasOriginalFilename) {
			if (isSameAsFile($originalFilePathFilename, $dbSize, $dbMd5)) {
				Ccsd_Log::message ( 'DOCID ' . $file ['DOCID'] . ' SUBMITTER file md5 + size matches DB md5', '', '', LOG_FILE );
				moveSubmitterBackupOnline($file ['FILENAME'], $d->getRacineDoc () );
				$impactedDeposits++;
				
			} else {
				Ccsd_Log::message ( 'DOCID ' . $file ['DOCID'] . ' SUBMITTER file md5 + size DOES NOT match DB md5', '', '', LOG_FILE );
			}
			
		} else {
			Ccsd_Log::message ( 'DOCID ' . $file ['DOCID'] . ' SUBMITTER file NOT found in : ' . $originalFilePathFilename, '', 'ERR', LOG_FILE );
		}
		
		Ccsd_Log::message ( 'DOCID ' . $file ['DOCID'] . ' DB md5  : ' . $dbMd5 . "\t FILE md5  : " . $fileMd5, '', '', LOG_FILE );
		Ccsd_Log::message ( 'DOCID ' . $file ['DOCID'] . ' DB size : ' . $dbSize . "\t FILE size : " . $fileSize, '', '', LOG_FILE );
	}
	
	$task ++;
}


Ccsd_Log::message ( 'Found ' . $impactedDeposits . ' deposit files to rename.', '', '', LOG_FILE );


$timeend = microtime ( true );
$time = $timeend - $timestart;

Ccsd_Log::message ( 'Début du script: ' . date ( "H:i:s", $timestart ) . '/ fin du script: ' . date ( "H:i:s", $timeend ), '', '', LOG_FILE );
Ccsd_Log::message ( 'Script executé en ' . number_format ( $time, 3 ) . ' sec.', '', '', LOG_FILE );
exit ();



