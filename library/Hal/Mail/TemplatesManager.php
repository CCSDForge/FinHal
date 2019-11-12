<?php

class Hal_Mail_TemplatesManager
{
	const TEMPLATES_FOLDER = 'emails';
	
	private static function getTemplatesFromFolder($path)
	{
		$result = array();
		
		if (is_dir($path)) {
			$langDir = opendir($path);
			while($lang = readdir($langDir)) {
				// Parcours de tous les dossiers de langue
				if($lang != '.' && $lang != '..' && $lang != '.svn' && is_dir($path . $lang)) {
					$langs[] = $lang;
					if (!is_dir($path.$lang . '/' . self::TEMPLATES_FOLDER)) {
						continue;
					}
					
					// On parcourt le répertoire des templates, si il existe
					$dir = opendir($path.$lang . '/' . self::TEMPLATES_FOLDER);
					while($file = readdir($dir)) {
						if($file != '.' && $file != '..' && !is_dir($path.$lang.'/'.$file)) {
							$result[$lang][] = $file;							
						}
					}
				}
			}
		}
		
		return $result;
	}
	
	public static function getList()
	{

		// $default_templates = self::getTemplatesFromFolder(APPLICATION_PATH.'/'.LANGUAGES);
		// $custom_templates = self::getTemplatesFromFolder(SPACE.'/'.LANGUAGES);
				
		$db = Zend_Db_Table_Abstract::getDefaultAdapter();
		
		$select = $db->select()
		->from(Hal_Mail_Template::T_MAIL_TEMPLATES)
		->where('SID IS NULL')
		->orWhere('SID = ?', SITEID);
		
		$templates = $db->fetchAssoc($select);
		
		// On retire de la liste les templates par défaut qui ont une version modifiée
		foreach ($templates as $template) {
			if ($template['PARENTID']) {
				unset($templates[$template['PARENTID']]);
			}
		}
		
		return $templates;
	}

	public static function getTemplateForm(Hal_Mail_Template $template = null, $langs=null)
	{
		$form = new Ccsd_Form();
		$form->setAction('/administratemail/savetemplate?id='.$template->getId());
		$form->setDecorators(array(
				'FormElements', 
				array('HtmlTag', array('tag' => 'div', 'class' => 'tab-content')),
				'Form',
		));
				
		if (!$langs) {
			$langs = Zend_Registry::get('website')->getLanguages();
		}
		
		$locale = Zend_Registry::get("Zend_Translate")->getLocale();
		$defaultLang = (array_key_exists($locale, $langs)) ? $locale : 'fr';
		
		foreach ($langs as $lang) {
			
			$subform = new Ccsd_Form_SubForm();
			
			$class = 'tab-pane fade';
			if (count($langs) == 1 || $lang == $defaultLang) {
				$class .= ' in active';
			}
			
			$subform->setDecorators(array(
					'FormElements',
					array(	'HtmlTag', 
							array(	'tag' 	=> 'div', 
									'class' => $class, 
									'style' => 'margin-top: 20px', 
									'id' 	=> $lang.'_form')
					)
			));
						
			// Template name
			$name = new Ccsd_Form_Element_Text('name');
			$name->setLabel(Zend_Registry::get("Zend_Translate")->translate('Nom du template'));
			$subform->addElement($name);
			
			// Mail subject
			$subject = new Ccsd_Form_Element_Text('subject');
			$subject->setLabel(Zend_Registry::get("Zend_Translate")->translate('Sujet du mail'));
			$subform->addElement($subject);
			
			$body = new Ccsd_Form_Element_Textarea('body');
			$body->setLabel(Zend_Registry::get("Zend_Translate")->translate('Corps du message'));
			$body->setAttribs(array('rows'=>10, 'style'=>'width:538px'));
			$subform->addElement($body);
			
			$form->addSubForm($subform, $lang);
		}
		
		if ($template) {
			$defaults = self::getTemplateFormDefaults($template, $langs);
			$form->setDefaults($defaults);
		}
		
		return $form;
	}
	
	private static function getTemplateFormDefaults(Hal_Mail_Template $template, $langs)
	{
		$defaults = array();
		$template->loadTranslations();
								
		foreach ($langs as $lang) {
						
			$defaults[$lang]['name'] = $template->getName($lang);
			$defaults[$lang]['subject'] = $template->getSubject($lang);
			$defaults[$lang]['body'] = nl2br($template->getBody($lang));
		}
				
		return $defaults;
	}
	
	public static function getTemplatePath($key, $locale=null) 
	{
		if (!$locale) {
			$locale = Zend_Registry::get('Zend_Translate')->getLocale();
		}
		$applicationPath = APPLICATION_PATH.'/languages/'.$locale.'/emails';
		$localPath = REVIEW_LANG_PATH.$locale.'/emails';
		
		if (file_exists($localPath.'/custom_'.$key.'.phtml')) {
			$result['path'] = $localPath;
			$result['key'] 	= 'custom_'.$key;
			$result['file'] = $result['key'].'.phtml';
		} elseif (file_exists($applicationPath.'/'.$key.'.phtml')) {
			$result['path'] = $applicationPath;
			$result['key'] 	= $key;
			$result['file'] = $result['key'].'.phtml';
		} else {
			$result = false;
		}
		
		return $result;
	}
}
