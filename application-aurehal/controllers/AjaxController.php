<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 29/06/17
 * Time: 14:45
 */
class AjaxController extends Hal_Controller_Action
{
    /**
     * Autocompletion pour l'ajout d'une structure de recherche
     */
    public function ajaxsearchstructureAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        if (isset($_GET['term'])) {
            try {
                echo Hal_Document_Structure::search($_GET['term'], 'json', Ccsd_Tools::ifsetor($_GET['type'], null), 'label_html');
            } catch(Exception $e) {
                echo '';
            }
        }
    }
}