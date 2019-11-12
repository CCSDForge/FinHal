<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 29/06/17
 * Time: 14:45
 */
class AjaxController extends Hal_Controller_Action
{
    /*public function init() {
        if (!((Hal_Auth::isTamponneur() && $this->getRequest()->getActionName() == 'ajaxsearchstructure') || ($this->getRequest()->getActionName() == 'ajaxsearchuser'))) {
            $this->view->message = "Accès non autorisé";
            $this->view->description = "";
            $this->renderScript('error/error.phtml');
        }
        $this->_request = $this->getRequest();
        $this->_params = $this->_request->getParams();
    }*/

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

    /**
     * Récupération d'information sur un référentiel existant ou non
     */
    public function ajaxgetreferentielAction()
    {
        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {

            $this->_session = new Hal_Session_Namespace(SESSION_NAMESPACE);

            $this->noRender();

            $class = 'Ccsd_Referentiels_' . ucfirst($this->getRequest()->getParam('type',''));

            if (!class_exists ($class)) {
                echo false;
                exit;
            }

            /** @var Ccsd_Referentiels_Abstract $o */
            $o    = new $class();

            $this->view->element    = $this->getRequest()->getParam('element', '');

            $this->view->core       = $this->getRequest()->getParam('type', '');
            $this->view->form       = $o->getForm();

            $this->view->term = $this->getRequest()->getParam('term', '');

            $this->view->classname  = get_class($o);
            $this->view->identifier = uniqid('ref');

            $new  = $this->getRequest()->getParam('new' , false);
            $edit = $this->getRequest()->getParam('edit', false);
            $id   = $this->getRequest()->getParam('id'  , false);

            if ($new || $edit) {
                $this->view->form = new Ccsd_Form();
                $this->view->form->setAttrib('class', 'form-horizontal');
                $this->view->form->addElements($o->getForm()->getElements());
            }

            if ($new) {
                $this->view->label = "Nouveau";
                $this->view->labelSubmit = "Ajouter";
                $this->view->labelFct = "add_ref";

                if ($this->getRequest()->isPost()) {
                    $post = $this->getRequest()->getPost();
                    if ($this->view->form->isValid ($post)) {
                        $o->set ($this->view->form->getValues());
                        echo $o->__toString(array('showItem' => true));
                    } else {
                        $this->render('ref/new');
                    }
                } else {
                    if (isset ($this->_session->ajaxterm)) {
                        $this->view->term =  $this->_session->ajaxterm;

                        $this->view->form->populate (array("JNAME" => $this->view->term, "TITRE" => $this->view->term));

                        unset ($this->_session->ajaxterm);
                    }

                    $this->render('ref/new');
                }
            } else if ($edit) {
                $this->view->label = "Modification";
                $this->view->labelSubmit = "Modifier";
                $this->view->labelFct = "edit_ref";

                if ($request->isPost()) {
                    $post = $request->getPost();
                    if ($request->getParam('valid')) {
                        if ($this->view->form->isValid ($post)) {
                            $o->set($this->view->form->getValues());
                            echo $o->__toString(array('showItem' => true));
                        }
                    } else {
                        if (in_array($this->view->core, array ('anrproject', 'europeanproject'))) {
                            if (isset ($post[$this->view->element])) {
                                $post = array_shift($post[$this->view->element]);
                            }
                        }

                        $this->view->form->populate($post);
                    }
                }

                $this->render('ref/new');
            } else if ($id) {
                $o = $o->load($id);
                echo $o->__toString(array('showItem' => true));
            } else {
                $this->_session->ajaxterm = $this->view->term;
                $this->render('ref/index');
            }

            unset ($o);
        } else {
            $this->forward('index');
        }
    }
}