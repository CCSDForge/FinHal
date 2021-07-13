<?php
/**
 * Affichage du CV d'un auteur
 *
 * Class IndexController
 */
class IndexController extends Zend_Controller_Action
{

    public function indexAction ()
    {
        $uri = $this->getRequest()->getParam('uri', '');

        $cv = new Hal_Cv(0, $uri);
        foreach(Ccsd_Tools_Params::getScriptArguments($this->getRequest()->getRequestUri()) as $filter => $value) {
            $cv->addFilter($filter, $value);
        }
        $cv->load();

        if ( $cv->exist() ) {
            if (! Hal_Cv::existCVForIdHal($cv->getIdHal())) {
                //Pas de CV défini
                return $this->redirect(HALURL . '/search/index/?qa[authIdHal_s][]=' . $cv->getUri());
            }
            $session = new Zend_Session_Namespace(SESSION_NAMESPACE);
            $session->idhal = $cv->getUri();
            $this->view->cv = $cv;
            Hal_Cv_Visite::add($cv->getIdHal(), Hal_Auth::getUID());
        }
    }

}