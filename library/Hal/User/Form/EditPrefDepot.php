<?php

class Hal_User_Form_EditPrefDepot extends Ccsd_Form
{

    public $_test;
    
    public function init ()
    {
        parent::init();
        
        $this->getView()->jQuery()->addJavascriptFile(CCSDLIB . "/js/bootstrap-typeahead.js");

        $pref_form = new Ccsd_Form_SubForm();
        $pref_form->setConfig(new Zend_Config_Ini(__DIR__ . '/../config/account.ini', 'pref-depot'));
        $pref_form->setLegend("Mes préférences de dépôt");
        $pref_form->setDescription("editpref_intro");
        $pref_form->setDecorators(
                array(
                        array(
                                'ViewScript',
                                array(
                                        'viewScript' => 'user/form_editpref.phtml',
                                        'name' => 'hal'
                                )
                        )
                ));
                
        /**
         * liste des langues *
         */
        //$langueId = $hal_sub_form->getElement('LANGUEID');

        try {
            // liste des langues du portail en cours
            $availLang = Zend_Registry::get('languages');
        } catch (Exception $e) {
            // liste des langues de Hal
            $availLang = Hal_Translation_Plugin::getAvalaibleLanguages();
        }

        /*foreach ($availLang as $l) {
            $lng[$l] = 'lang_' . $l;
        }*/

        //$langueId->setMultiOptions($lng);
        /**
         * // liste des langues *
         */

        
        // Ajout d'un décorateur pour remplacer le décorateur par défaut et ajout de marges
        foreach (["MODE","AUTODEPOT", "DEFAULT_AUTHOR"] as $elem) {
            $checkbox = $pref_form->getElement($elem);
            if (isset($checkbox)) {
                $checkbox->addDecorator('ViewHelper', array('class' => ''));
                $checkbox->setAttrib('style', 'margin:10px');
            }
        }
        
        
        $this->addSubForms(array("hal" => $pref_form));

        $this->setActions(true);
    }
}