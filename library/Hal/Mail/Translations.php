<?php

class Hal_Mail_Translations
{
	
	// Charge les traductions du dossier passé en paramètre
	// Si un fichier est passé en paramètre, on ne charge que celui-ci
	// Sinon, on charge tous les fichiers contenus dans le dossier
	public static function loadTranslations($path, $file=null)
	{
		$translator = Zend_Registry::get('Zend_Translate');
	
		if (is_dir($path)) {
			$langDir = opendir($path);
			while($lang = readdir($langDir)) {
				// Parcours de tous les dossiers de langue
				if($lang != '.' && $lang != '..' && $lang != '.svn' && is_dir($path.$lang)) {
					$langs[] = $lang;
					 
					// Ne charge que les traductions du fichier passé en paramètre
					if ($file) {
						if (file_exists($path.$lang.'/'.$file)) {
							$translator->addTranslation($path.$lang.'/'.$file, $lang);
						}
					}
					 
					// Charge toutes les traductions
					else {
						$dir = opendir($path.$lang);
						while($file = readdir($dir)) {
							if($file != '.' && $file != '..' && !is_dir($path.$lang.'/'.$file)) {
								$translator->addTranslation($path.$lang.'/'.$file, $lang);
							}
						}
					}
				}
			}
		}
	
		return $translator;
	}
	
	
	// Renvoie les traductions demandées, dans toutes les langues
	public static function getTranslations($path, $file=null, $pattern=false)
	{
		$translations = array();
		if (is_dir($path)) {
			$langDir = opendir($path);
			while($lang = readdir($langDir)) {
				// Parcours de tous les dossiers de langue
				if($lang != '.' && $lang != '..' && is_dir($path.$lang)) {
		    
					$langs[] = $lang;
		    
					// Ne renvoie que les traductions du fichier passé en paramètre
					if ($file) {
						if (file_exists($path.$lang.'/'.$file)) {
							$translations[$lang] = self::readTranslation($path.$lang.'/'.$file, $lang, $pattern);
						}
					}
		    
					// Renvoie les traductions de tous les fichiers
					else {
						$dir = opendir($path.$lang);
						$tmp = array();
						while($file = readdir($dir)) {
							if($file != '.' && $file != '..' && !is_dir($path.$lang.'/'.$file)) {
								$tmp += self::readTranslation($path.$lang.'/'.$file, $lang, $pattern);
							}
						}
						$translations[$lang] = $tmp;
					}
				}
			}
		}
		 
		return $translations;
	}
	 
	// Renvoie les traductions qui ne correspondent pas au pattern passé en paramètre
	// Peut scanner tous les fichiers du répertoire, ou chercher celui passé en paramètre
	public static function getOtherTranslations($path, $file=null, $pattern)
	{
		$translations = array();
	
		if ($file && !preg_match('#(.*).php#', $file)) {
			$file .= '.php';
		}
	
		$translations = self::getTranslations($path, $file);
		// Filtre les traductions en fonction du pattern
		if (!empty($translations)) {
			foreach ($translations as $lang=>$tmp_translation) {
				foreach($tmp_translation as $key=>$translation) {
					if (preg_match($pattern, $key)) {
						unset($translations[$lang][$key]);
					}
				}
			}
		}
	
		return $translations;
	}
	
	// Lit le contenu d'un fichier de traduction
	// Peut filtrer le résultat par langue et par expression régulière
	public static function readTranslation($file, $lang=null, $pattern=false)
	{
		$res = array();
		 
		if (is_file($file) ) {
			 
			try{
				$translation = new Zend_Translate('array', $file, $lang, array('disableNotices' => true));
			} catch (Zend_Translate_Exception $e) {
				return $res;
			}
			 
			$list = $translation->getList();
			 
			if (is_array($list)) {
	
				if ($lang != null && in_array($lang, $list)) {
					foreach ($translation->getMessages($lang) as $key => $value) {
						if (!$pattern || preg_match($pattern, $key)) {
							$res[$key] = $value;
						}
					}
				} else if (count($list)){
					foreach ($translation->getList() as $l) {
						foreach ($translation->getMessages($l) as $key => $value) {
							if (!$pattern || preg_match($pattern, $key)) {
								$res[$key][$l] = $value;
							}
						}
					}
				}
			}
		} else {
			//echo "ERROR : " . $file;
		}
		 
		return $res;
	}
	
	
	public static function writeTranslations($translations, $path, $file, $mode="w")
	{
		if (!is_dir($path)) {
			return false;
		}
	
		self::multi_ksort($translations);
		$langs = array_keys($translations);
	
		foreach ($langs as $lang) {
	
			if (!is_dir($path.$lang)) {
				mkdir($path.$lang);
			}
			 
			$filePath = $path.$lang.'/'.$file;
			$result = '<?php'.PHP_EOL.'return '.var_export($translations[$lang], true).';';
			file_put_contents ($filePath, $result);
		}
	}
	
	public static function multi_ksort(&$arr)
	{
		ksort($arr);
		foreach ($arr as &$a) {
			if (is_array($a) && !empty($a)) {
				self::multi_ksort($a);
			}
		}
	}
	
}