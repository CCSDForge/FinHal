<?php

class SubmissionsController extends Zend_Controller_Action
{
    /**
     * Page d'accueil du site
     */
    public function indexAction ()
    {
        $params = $this->getRequest()->getParams();

        if (isset($params['docid'])) {
            $document = Hal_Document::find((int) $params['docid']);
            if ($document && Hal_Auth::getUid() == $document->getContributor('uid')) {
                $halmsDoc = new Halms_Document($params['docid']);
                if ($this->getRequest()->isPost()) {
                    if ($halmsDoc->changeStatus($params['newstatus'])) {
                        if ($params['newstatus'] == Halms_Document::STATUS_XML_FINISHED) {
                            $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Votre document va être transféré sur PubMed Central');
                        } else {
                            //l'auteur a remonté des erreurs, on les envoie au administrateurs
                            $halmsDoc->sendMail(
                                ['email' => Halms_Document::HALMS_MAIL, 'name' => Halms_Document::HALMS_USERNAME],
                                ['HALMS_ID' => "HALMS_" . $halmsDoc->getDocid(), 'USER' => Halms_Document::HALMS_USERNAME, 'COMMENT' => $params['comment']],
                                'mail_author_reporting_subject',
                                'mail_author_reporting_content');
                            $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Vos remarques ont été transmises aux administrateurs');
                        }
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Changement de statut : OK');
                        Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), $params['newstatus'], $params['comment']);
                    } else {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Erreur dans le changement de statut de l'article");
                    }

                    $this->_redirect('/submissions');
                } else {
                    $halmsDoc = new Halms_Document($params['docid']);
                    $this->view->document = $document;
                    $this->view->docStatus = $halmsDoc->getStatus();
                    $this->view->pdf = 'halms' . $this->view->document->getDocid() . '_edited.pdf';
                    $this->view->html = 'index.xhtml';
                }
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Vous ne disposez pas des droits pour accéder à ce document");
                $this->_redirect('/submissions');
            }
            $this->view->hideHistory = true;
            $this->view->backLink = '/submissions';
            if ($halmsDoc->getStatus() == Halms_Document::STATUS_XML_CONTROLLED) {
                $this->render('controlled');
            } else {
                $this->render('default');
            }
        } else {
            //Récupération des dépôts dans HalMS
            $this->view->documents = Halms_Document::getDocuments(null, null, Hal_Auth::getUid());
        }
    }

}
