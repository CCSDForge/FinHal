<?php
/**
 * ================================================= CREDIT ====================================================
 * Created by PhpStorm In CNRS-CCSD
 * User: Zahen
 * Date: 10/04/2017
 * Time: 16:36
 * =============================================================================================================
 */

/**
 * =============================================== DESCRIPTION =================================================
 *
 * =============================================================================================================
 */

class ViewController extends Zend_Controller_Action {

    public function rdfAction ()
    {
        $this->_helper->layout()->disableLayout();
        $params = $this->getRequest()->getParams();
        $this->_helper->viewRenderer->setNoRender();
        $this->view->addScriptPath(LIBRARYPATH . '/Ccsd/Rdf/');
        if (isset($params['author_id']) && $params['author_id'] != 0 ) {
            $this->view->author = new Hal_Document_Author($params['author_id']);
        } else if (isset($params['structure_id']) && $params['structure_id'] != 0) {
            $this->view->structure = new Ccsd_Referentiels_Structure($params['structure_id']);
        } else if (isset($params['subject_code']) && $params['subject_code'] != '') {
            $this->view->subject_code = $params['subject_code'];
        } else if (isset($params['journal_id']) && $params['journal_id'] != 0) {
            $this->view->journal = new Ccsd_Referentiels_Journal($params['journal_id']);
        } else if (isset($params['anrproject_id']) && $params['anrproject_id'] != 0) {
            $this->view->anrproject = new Ccsd_Referentiels_Anrproject($params['anrproject_id']);
        } else if (isset($params['europeanproject_id']) && $params['europeanproject_id'] != 0) {
            $this->view->europeanproject = new Ccsd_Referentiels_Europeanproject($params['europeanproject_id']);
        } else {
            $this->redirect(PREFIX_URL . 'index/index');
            exit;
        }
        if (isset($params['worklist'])) {
            $this->renderScript('author_workList.phtml');
        } else {
            $this->renderScript('gui.phtml');
        }
    }

}
