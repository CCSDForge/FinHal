<?php

class Hal_User_Form_EditPrefMail extends Ccsd_Form
{

    public $_test;
    
    public function init ()
    {
        parent::init();

        $this->getView()->jQuery()->addJavascriptFile(CCSDLIB . "/js/bootstrap-typeahead.js");

        $pref_form = new Ccsd_Form_SubForm();
        $pref_form->setConfig(new Zend_Config_Ini(__DIR__ . '/../config/account.ini', 'pref-mail'));
        $pref_form->setLegend("Mes préférences de réception de courriel");
        $pref_form->setDescription("Pour être automatiquement averti par courriel dès qu’un dépôt est mis en ligne, cochez la/les case(s)");
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

        $user = new Hal_User(['Uid' => Hal_Auth::getUid()]);
        $pref_form->addElement('multiCheckbox', 'adminstruct', [
            'label' =>  "Recevoir les notifications en tant que référent structure",
            'populate' => $user -> getStructAuth(),
            'order' => 20,
            'required' => false
        ]);

        if (!Hal_Auth::isAdminStruct()){
            $pref_form->removeElement(Hal_Acl::ROLE_ADMINSTRUCT);
        }


        if (!Hal_Auth::isAdministrator()){
            // //Si il n'est pas Administrateur du portail, on enlève l'élément du form
            $pref_form->removeElement(Hal_Acl::ROLE_ADMIN);
        }

        // Ajout d'un décorateur pour remplacer le décorateur par défaut et ajout de marges
        foreach (["author","member","adminstruct", "administrator"] as $elem) {
            $checkbox = $pref_form->getElement($elem);
            if (isset($checkbox)) {
                $checkbox->addDecorator('ViewHelper', array('class' => ''));
            }
        }

        $this->addSubForms(array("hal" => $pref_form));

        $this->setActions(true);
    }
}