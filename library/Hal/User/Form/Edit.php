<?php

/**
 * Class Hal_User_Form_edit
 *
 * @property Ccsd_Form_SubForm ccsd  : Subform
 */
class Hal_User_Form_edit extends Ccsd_User_Form_Accountedit
{

    public function init ()
    {
        parent::init();
        /** @var Ccsd_View $view */
        $view = $this->getView();
        $view->jQuery()->addJavascriptFile(CCSDLIB . "/js/bootstrap-typeahead.js");

        $ccsd_sub_form = new Ccsd_Form_SubForm();
        $ccsd_sub_form->setElements($this->getElements());
        $ccsd_sub_form->setLegend("Informations de mon compte CCSD.");
        $ccsd_sub_form->setDecorators(
                array(
                        array(
                                'ViewScript',
                                array(
                                        'viewScript' => 'user/form_edit.phtml',
                                        'name' => 'ccsd'
                                )
                        )
                ));

        $this->clearElements();

        $hal_sub_form = new Ccsd_Form_SubForm();
        $hal_sub_form->setConfig(new Zend_Config_Ini(__DIR__ . '/../config/account.ini', 'hal-account'));
        $hal_sub_form->setLegend("Informations de mon profil HAL.");
        $hal_sub_form->setDecorators(
                array(
                        array(
                                'ViewScript',
                                array(
                                        'viewScript' => 'user/form_edit.phtml',
                                        'name' => 'hal'
                                )
                        )
                ));

        /**
         * liste des langues *
         * @var Ccsd_Form_Element_MultiTextLang $langueId
         */
        $langueId = $hal_sub_form->getElement('LANGUEID');

        try {
            // liste des langues du portail en cours
            $availLang = Zend_Registry::get('languages');
        } catch (Exception $e) {
            // liste des langues de Hal
            $availLang = Hal_Translation_Plugin::getAvalaibleLanguages();
        }

        $lng = [];
        foreach ($availLang as $l) {
            $lng[$l] = 'lang_' . $l;
        }

        $langueId->setMultiOptions($lng);
        /**
         * // liste des langues *
         */

        $this->addSubForms(array(
                "ccsd" => $ccsd_sub_form,
                "hal" => $hal_sub_form
        ));

        $this->setActions(true);
    }
}