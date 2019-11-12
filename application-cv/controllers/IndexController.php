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
        $request = $this->getRequest();
        $uri = $request->getParam('uri', '');

        $cv = new Hal_Cv(0, $uri);
        foreach(Ccsd_Tools_Params::getScriptArguments($request->getRequestUri()) as $filter => $value) {
            $cv->addFilter($filter, $value);
        }
        $cv->load();

        if ( $cv->exist() ) {
            if (! Hal_Cv::existCVForIdHal($cv->getIdHal())) {
                //Pas de CV défini
                $this->redirect(HALURL . '/search/index/?qa[authIdHal_s][]=' . $cv->getUri());
            }

            $session = new Zend_Session_Namespace(SESSION_NAMESPACE);
            $session->idhal = $cv->getUri();
            $showWidget = ($request->getParam('noWidget', '') === '') ? true : false;
            $this->view->cv = $cv;
            $this->view->showWidget = $showWidget;
            Hal_Cv_Visite::add($cv->getIdHal(), Hal_Auth::getUID());
        }
    }

}