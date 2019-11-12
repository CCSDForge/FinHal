<?php

//php traduction.php --source c:\www\halv3\ --locales fr-en --output . 

set_include_path(implode(PATH_SEPARATOR, array_merge(array('/sites/hal_test/library', '/sites/phplib_test', '/sites/library_test'), array(get_include_path()))));
require_once "Zend/Console/Getopt.php";

try {

	$opts = new Zend_Console_Getopt(array(
		'source=s'    => 'repertoire a scanner',
		//'exclude=s'   => 'chemin vers un fichier content la liste des repertoires a exclure du scan au format XML | optionnel',
		//'transform=s' => 'chemin vers la feuille XSL | optionnel',
		'output=s'    => 'chemin du repertoire pour la sortie',
		'locales=s'   => 'verifie les traductions pour les locales donnees , exemple : en-fr-es'
	));
	
	$opts->addRules(array(
		'verbose|v' => 'Print verbose output'
	));
	
	$opts->parse();
	
	$source = $opts->getOption("source");
	
	if (!$source) {
		require_once "Zend/Console/Getopt/Exception.php";
		throw new Zend_Console_Getopt_Exception(
				"Option \"source|s\" est obligatoire",
				$opts->getUsageMessage());
	}

} catch (Zend_Console_Getopt_Exception $e) {
	print "\n";
	print "\n";
	print "|***********************************************************************************************************************|\n";
	print "|***********************************************************************************************************************|\n";
	print "|**                                                                                                                   **|\n";
	print "|** Script Traduction(s)                                                                                              **|\n";
	print "|** @author Loic COMPARET                                                                                             **|\n";
	print "|**                                                                                                                   **|\n";
	print "|** Scanne tous les repertoires et tous les fichiers et recherche toutes les cles necessaires a une traduction        **|\n";
	print "|**                                                                                                                   **|\n";
	print "|**                                                                                                                   **|\n";
	print "|** Enregistre le resultat dans un fichier                                                                            **|\n";
	print "|**                                                                                                                   **|\n";
	print "|** Possibilite d'appliquer le resultat a une transformation XSL   (à faire)                                          **|\n";
	print "|**                                                                                                                   **|\n";
	print "|**                                                                                                                   **|\n";
	print "|***********************************************************************************************************************|\n";
	print "|***********************************************************************************************************************|\n";
	print "|_______________________________________________________________________________________________________________________|\n";
	print "\n";
	echo $e->getUsageMessage();
	exit;
}

/**
@TODO cas des pluriels
 */

$aResult = array();

function scan ($repertoire, array &$aResult = array(), $translator = null)
{
	$resource = opendir($repertoire) or die("Erreur le repertoire $repertoire existe pas");

	while($fichier = @readdir($resource))
	{
		if ($fichier == "." || $fichier == ".." || $fichier == ".svn") continue;

		$path = $repertoire . DIRECTORY_SEPARATOR . $fichier;
		
		if ('languages' == $fichier) {
			$translator->addTranslation($path);
		}

		if(is_dir($path)) {
			scan($path, $aResult, $translator);
		} else find($path, $aResult);
	}

	closedir($resource);
}

function find ($path, &$aResult)
{
	$handle = fopen($path, 'r');

	$line = 0;

	if ($handle) {
		while (!feof($handle)) {
			$pos  = 0;
			$data = fgets ($handle);
			$line++;

			while (($pos = strpos($data, '->translate', $pos)) !== FALSE) {

				$pos += 11;  //on ajoute la chaine "->translate"

				$data = substr($data, $pos);

				$pos = strpos($data, "(") + 1; //on se place après "("

				$data = substr($data, $pos);

				$capture = "";
				$bracket_begin = 1;
				$bracket_end = 0;
				$is_quoting_d = false;
				$is_quoting_s = false;
				$is_echaping  = false;

				$tmp = str_split($data, 1);

				foreach ($tmp as $i => $c) {
					switch ($c) {
						case '"':
							if (!$is_quoting_s) {
								if ($i == 0) {
									$is_quoting_d = !$is_quoting_d;
								} else if ($tmp[$i-1] != "\\") {
									$is_quoting_d = !$is_quoting_d;
								}
							} else if ($bracket_begin > $bracket_end) {
								$capture .= $c;
							}
							break;
						case "'":
							if (!$is_quoting_d) {
								if ($i == 0) {
									$is_quoting_s = !$is_quoting_s;
								} else if ($tmp[$i-1] != "\\") {
									$is_quoting_s = !$is_quoting_s;
								}
							} else if ($bracket_begin > $bracket_end) {
								$capture .= $c;
							}
							break;
						case "(":
							if (!$is_quoting_s && !$is_quoting_d) {
								$bracket_begin++;
							}
								
							if ($bracket_begin > $bracket_end) {
								$capture .= $c;
							}
							break;
						case ")":
							if (!$is_quoting_s && !$is_quoting_d) {
								$bracket_end++;

								if ($bracket_begin == $bracket_end) {
									$pos += $i;
									break 2;
								}
							}
								
							if ($bracket_begin > $bracket_end) {
								$capture .= $c;
							}
							break;
						case ";":
							if (!$is_quoting_d && !$is_quoting_s) {
								$pos += $i;
								break 2;
							} else if ($bracket_begin > $bracket_end) {
								$capture .= $c;
							}
							break;
						case ",":
							if (!$is_quoting_d && !$is_quoting_s && $bracket_begin == $bracket_end+1) {
								$pos += $i;
								break 2;
							} else if ($bracket_begin > $bracket_end) {
								$capture .= $c;
							}
							break;
						case "?":
							if (!$is_quoting_d && !$is_quoting_s) {
								if ($tmp[$i+1] == ">") {
									$pos += $i;
									break 2;
								}
							} else if ($bracket_begin > $bracket_end) {
								$capture .= $c;
							}
							break;
						default :
							if ($bracket_begin > $bracket_end) {
								$capture .= $c;
							}
							break;
					}
				}

				$aResult["$path:$line"] = $capture;
			}
		}
		fclose($handle);
	}
}

function output ($path, $aResult, $translator, $locales) {
	$path = realpath ($path);
	
	if (!$path || !is_dir ($path)) {
		throw new Exception ("Le chemin du repertoire n'est pas valide");
	}

	foreach ($locales as $l) {
		
		$aTranslated   = array();
		$aNoTranslated = array();
		$translated    = $path . DIRECTORY_SEPARATOR . "translated_$l" . ".txt";
		$no_translated = $path . DIRECTORY_SEPARATOR . "no_translated_$l" . ".txt";
		
		if (!file_exists ($translated)) {
			touch ($translated);
		}
		
		file_put_contents ($translated, "");
		
		if (!file_exists ($no_translated)) {
			touch ($no_translated);
		}
		
		file_put_contents ($no_translated, "");

		foreach ($aResult as $file => $key) {
			$file = array(
				substr($file, 0, strrpos($file, ':')),
				substr($file, strrpos($file, ':')+1)
			);
			
			if ($translator->isTranslated($key,$l)) {
				if (!array_key_exists($file[0], $aTranslated)) {
					$aTranslated[$file[0]] = array ();
				}
				
				$aTranslated[$file[0]][$file[1]] = array (
					'origin'     => $key,
					'traduction' => $translator->translate($key, $l)
				);

			} else {
				if (!array_key_exists($file[0], $aNoTranslated)) {
					$aNoTranslated[$file[0]] = array ();
				}
				$aNoTranslated[$file[0]][$file[1]] = $key;
			}
		}
		
		if (!empty ($aTranslated)) {
			foreach ($aTranslated as $k => $v) {				
				file_put_contents ($translated, "$k : \n\r", FILE_APPEND);
				foreach ($v as $line => $data) {
					file_put_contents ($translated, "\r", FILE_APPEND);
					file_put_contents ($translated, "Ligne " . $line . "\r", FILE_APPEND);
					file_put_contents ($translated, "Original : " . $data['origin'] . "\r", FILE_APPEND);
					file_put_contents ($translated, "Traduction : " . $data['traduction'] . "\r", FILE_APPEND);
				}
				file_put_contents ($translated, "\n\r", FILE_APPEND);
			}
		}
		

		
		if (!empty ($aNoTranslated)) {
			foreach ($aNoTranslated as $k => $v) {
				file_put_contents ($no_translated, "$k : \n\r", FILE_APPEND);
				foreach ($v as $line => $data) {
					file_put_contents ($no_translated, "\r", FILE_APPEND);
					file_put_contents ($no_translated, "Ligne " . $line . "\r", FILE_APPEND);
					file_put_contents ($no_translated, "Original : " . $data . "\r", FILE_APPEND);
				}
				file_put_contents ($no_translated, "\n\r", FILE_APPEND);
			}
		}
		
	}
}

$locales = $opts->getOption('locales');

if (!$locales) {
	$locales = 'fr-en';
}

$locales = explode ('-', $locales);
if (!is_array ($locales)) {
	$locales = array ($locales);
}

foreach ($locales as $l) {
	require_once "Zend/Locale.php";
	require_once "Zend/Locale/Exception.php";
	try {
		Zend_Locale::findLocale($l);
	} catch (Zend_Locale_Exception $e) {
		echo $e->getMessage();
		exit;
	}
}

require_once "Zend/Translate.php";
$translator = new Zend_Translate(
	Zend_Translate::AN_ARRAY,
	null,
	$locales[0],
	array(
		'scan' => Zend_Translate::LOCALE_DIRECTORY,
		'disableNotices' => true
	)
);

if ($translator->isAvailable($locales[0])) {
    $translator->setLocale($locales[0]);
}

/*$entries_to_avoid = null;
$exclude = $opts->getOption('exclude');
if ($exclude && file_exists (realpath ($exclude))) {
	
	$entries_to_avoid = file_get_contents(realpath ($exclude));
	
}*/

scan(realpath ($source), $aResult, $translator);

if ($opts->getOption('output')) {
	output ($opts->getOption('output'), $aResult, $translator, $locales);
}

if ($opts->getOption('v') || $opts->getOption('verbose')) {
	print_r ($aResult);
	
	print_r(count ($aResult) . " resultats (total)");
}























