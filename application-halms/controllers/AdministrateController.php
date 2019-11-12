<?php

class AdministrateController extends Zend_Controller_Action
{
    protected $_session = null;

    public function init()
    {
        if (! Halms_Auth::isAdministrator()) {
            $this->view->message = "Accès non autorisé";
            $this->view->description = "";
            $this->redirect('/error/error');
        }
        $this->_session = new Zend_Session_Namespace(SESSION_NAMESPACE);
    }


    public function indexAction ()
    {
        /*
         * De quand date ce commentaire ? La classe Ccsd_Meta_Pubmed n'existe plus (voir Ccsd-Externdoc)
         * $pmid = 20613675;
        $pubmed = new Ccsd_Meta_Pubmed($pmid);
        $pubmedMetas = $pubmed->getMetas();
        Zend_Debug::dump($pubmedMetas);exit;*/

        $params = $this->getRequest()->getParams();

        //Recherche d'un dépôt
        if (isset($params['q']) && $params['q'] != '') {
            $docid = Halms_Document::searchById($params['q']);
            if ($docid) {
                $params['docid'] = $docid;
            } else {
                $this->view->q = $params['q'];
                $this->view->message = $this->view->translate('Aucun document trouvé');
            }
        }

        if (isset($params['docid'])) {
            $halmsDoc = new Halms_Document($params['docid']);
            $this->view->document = Hal_Document::find($params['docid']);
            $this->view->docStatus = $halmsDoc->getStatus();

            if (in_array($halmsDoc->getStatus(), [Halms_Document::STATUS_INITIAL, Halms_Document::STATUS_INITIAL_EMBARGO, Halms_Document::STATUS_INITIAL_BLOCKED, Halms_Document::STATUS_INITIAL_UNKNOWN, Halms_Document::STATUS_INITIAL_AHEADOFPRINT])) {
                $this->initial($halmsDoc, $params);
            } else if ($halmsDoc->getStatus() == Halms_Document::STATUS_INITIAL_READY || $halmsDoc->getStatus() == Halms_Document::STATUS_WAIT_FOR_DCL) {
                $this->dcl($halmsDoc, $params);
            } else if ($halmsDoc->getStatus() == Halms_Document::STATUS_XML_QA) {
                $this->qa($halmsDoc, $params);
            } else if ($halmsDoc->getStatus() == Halms_Document::STATUS_XML_ERROR_REPORTED_AUTHOR) {
                $this->author($halmsDoc, $params);
            } else if ($halmsDoc->getStatus() == Halms_Document::STATUS_XML_FINISHED || $halmsDoc->getStatus() == Halms_Document::STATUS_WAIT_FOR_PMC || $halmsDoc->getStatus() == Halms_Document::STATUS_PMC_ONLINE) {
                $this->pmc($halmsDoc, $params);
            } else {
                $this->render('default');
            }
        } else {
            $this->listDocuments($params);
        }
    }

    /**
     * Affichage de la liste des documents de HALMS
     */
    protected function listDocuments($params)
    {
        if (isset($params['status'])) {
            $this->_session->status = $params['status'];
        } else if (!isset($this->_session->status)) {
            $this->_session->status = Halms_Document::STATUS_INITIAL;
        }
        if (isset($params['limit'])) {
            $this->_session->limit = $params['limit'];
        } else if (!isset($this->_session->limit)) {
            $this->_session->limit = 100;
        }

        $this->view->docStatus = Halms_Document::getDocStatus();
        $this->view->defaultDocStatus = $this->_session->status;

        $this->view->limit = [25, 50, 100, 200, 0];
        $this->view->defaultLimit = $this->_session->limit;

        $this->view->documents = Halms_Document::getDocuments($this->_session->status, $this->_session->limit);

        $this->render('list');
    }

    protected function initial(Halms_Document $halmsDoc, $params)
    {
        //Document avec un statut initial
        if (isset($params['newstatus'])) {
            if ($halmsDoc->changeStatus($params['newstatus'])) {
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Changement de statut : OK');
                Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), $params['newstatus'], $params['comment']);
                if ($params['newstatus'] == Halms_Document::STATUS_INITIAL_READY) {
                    //Document valide pour DCL, on copie les fichiers dans le repertoire halms
                    if ($halmsDoc->copyFiles($params['docid'])) {
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Déplacement des fichiers dans HALMS : OK');
                    } else {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Déplacement des fichiers dans HALMS : KO');
                    }
                }
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Changement de statut : KO');
            }
            $this->redirect('/administrate');
        }
        //Affichage du document
        $this->render('initial');
    }


    protected function dcl(Halms_Document $halmsDoc, $params)
    {

        if (isset($params['refreshxml'])) {
            $halmsDoc->createXmlFile($this->view->document);
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Regénération du fichier XML');
        } else if (isset($_FILES['files'])) {
            if ($_FILES['files']['name'][0] == "") {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Aucun fichier déposé');
            } else {
                if (isset($params['oldfile'])) {
                    //remplacement d'un fichier par un autre
                    @unlink($halmsDoc->getHalmsPath() . $params['oldfile']);
                }

                $res = true;
                //Ajout de fichier au dépôt envoyé à DCL
                $filenames = [];
                foreach($_FILES['files']['name'] as $i => $filename) {
                    if (isset($params['oldfile'])) {
                        $filename = $params['oldfile'];
                    }
                    $filenames[] = $filename;
                    $res = copy($_FILES['files']['tmp_name'][$i], $halmsDoc->getHalmsPath() . $filename) && $res;
                }
                if ($res) {
                    Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::ACTION_ADDFILES, implode(', ', $filenames));
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Fichiers ajoutés : OK');
                } else {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Fichiers ajoutés : KO');
                }
            }
        } else if (isset($params['delfile'])) {
            //Suppression d'un fichier dans le package
            if ($halmsDoc->delFile($params['delfile'])) {
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Suppression du fichier : OK");
                Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::ACTION_DELFILES, ' : Suppression du fichier ' . $params['delfile']);
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Suppression du fichier : KO");
            }
        } else if (isset($params['newstatus'])) {
            if ($params['newstatus'] == Halms_Document::STATUS_INITIAL) {
                //remise au statut initial
                $halmsDoc->changeStatus($params['newstatus']);
                Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::STATUS_INITIAL);
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Remise à l'état initial : OK");
            } else if ($params['newstatus'] == Halms_Document::STATUS_INITIAL_READY) {
                //Modification du package
                $halmsDoc->changeStatus($params['newstatus']);
                Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::STATUS_INITIAL_READY, Ccsd_Tools::ifsetor($params['comment']));
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Remise à l'état prêt pour DCL : OK");
            } else {
                if ($halmsDoc->isValidMetaXml()) {
                    //Transfert chez DCL
                    if ($halmsDoc->createZip()) {
                        $return = $halmsDoc->uploadDCL();
                        if ($return['result']) {
                            $halmsDoc->changeStatus($params['newstatus']);
                            //Envoi de mail
                            $halmsDoc->sendMail(
                                ['email' => explode(',', Halms_Document::DCL_MAILS), 'name' => ''],
                                ['HALMS_ID' => "HALMS_" . $halmsDoc->getDocid(), 'USER' => Halms_Document::DCL_USERNAME, 'HALMS_ARCHIVE' => "halms" . $halmsDoc->getDocid() . ".zip"],
                                'mail_available_subject',
                                'mail_available_content');
                            Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::STATUS_WAIT_FOR_DCL, 'zip envoyé : ' . $return['msg']);
                            $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Envoi à DCL : OK");
                        } else {
                            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Envoi à DCL : KO");
                        }
                    } else {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Création de l'archive ZIP : KO");
                    }
                } else {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Validité du fichier XML : KO");
                }
            }
            $this->_redirect('/administrate');
        }

        //Afichage du document
        $this->view->docStatus = $halmsDoc->getStatus();

        if ($halmsDoc->getStatus() == Halms_Document::STATUS_INITIAL_READY) {
            $this->view->dirContent = $halmsDoc->createManifest('array');
            $this->render('initial-ready');
        } else if ($halmsDoc->getStatus() == Halms_Document::STATUS_WAIT_FOR_DCL) {
            $this->render('default');
        }
    }

    protected function qa(Halms_Document $halmsDoc, $params)
    {
        if (isset($_FILES['file'])) {
            if ($_FILES['file']['name'] != "") {
                if ($_FILES['file']['type'] != 'text/xml') {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Fichiers ajoutés : KO - format non autorisé');
                }
                if (isset($params['oldfile'])) {
                    //remplacement d'un fichier par un autre
                    @unlink($halmsDoc->getDclPath() . $params['oldfile']);
                }
                $res = copy($_FILES['file']['tmp_name'], $halmsDoc->getDclPath() . $params['oldfile']);
                if ($res) {
                    Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::ACTION_ADDFILES, $params['oldfile']);
                    //On regénère les fichiers HTML et PDF
                    if ($halmsDoc->generate()) {
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Regénération des versions HTML / PDF : OK');
                    } else {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Regénération des versions HTML / PDF : KO');
                    }
                } else {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Fichiers ajoutés : KO');
                }
            }
        } else if (isset($params['newstatus'])) {
            if ($params['newstatus'] == Halms_Document::STATUS_XML_CONTROLLED) {


                //XML  validé, on change le statut et envoie un mail au déposant
                $halmsDoc->changeStatus($params['newstatus']);
                //Envoi de mail
                $date = new DateTime('now');
                $date->modify('+15 day');
                $document = Hal_Document::find($halmsDoc->getDocid());
                $contrib = $document->getContributor();
                $halmsDoc->sendMail(
                    ['email' => $contrib['email'], 'name' => $contrib['fullname']],
                    ['HALMS_ID' => "HALMS_" . $halmsDoc->getDocid(), 'USER' => $contrib['fullname'], 'DOC_ID' => $document->getId(), 'DOC_TITLE' => $document->getMainTitle(),  'HALMS_LIMIT' => $date->format('Y-m-d')],
                    'mail_author_subject',
                    'mail_author_content');
                Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::STATUS_XML_CONTROLLED, "mail envoyé à l'auteur ");
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Envoi à l'auteur : OK");
            }
            $this->_redirect('/administrate');
        }
        $this->view->files = $halmsDoc->listDclDir();
        $this->view->docStatus = $halmsDoc->getStatus();
        $this->view->outputFiles = [
            'XML' => 'halms' . $this->view->document->getDocid() . '.xml',
            'PDF' => 'halms' . $this->view->document->getDocid() . '_edited.pdf',
            'HTML' => 'index.xhtml'];
        $this->view->btn = [Halms_Document::STATUS_XML_CONTROLLED];
        $this->render('qa');
    }

    protected function author(Halms_Document $halmsDoc, $params)
    {
        if (isset($_FILES['file'])) {
            if ($_FILES['file']['name'] != "") {
                if ($_FILES['file']['type'] != 'text/xml') {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Fichiers ajoutés : KO - format non autorisé');
                }
                if (isset($params['oldfile'])) {
                    //remplacement d'un fichier par un autre
                    @unlink($halmsDoc->getDclPath() . $params['oldfile']);
                }
                $res = copy($_FILES['file']['tmp_name'], $halmsDoc->getDclPath() . $params['oldfile']);
                if ($res) {
                    Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::ACTION_ADDFILES, $params['oldfile']);
                    //On regénère les fichiers HTML et PDF
                    if ($halmsDoc->generate()) {
                        $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Regénération des versions HTML / PDF : OK');
                    } else {
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Regénération des versions HTML / PDF : KO');
                    }
                } else {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Fichiers ajoutés : KO');
                }
            }
        } else if (isset($params['newstatus'])) {
            if ($params['newstatus'] == Halms_Document::STATUS_XML_FINISHED) {
                //XML  prêt pour le transfert PubMed Central
                $halmsDoc->changeStatus($params['newstatus']);
                Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::STATUS_XML_CONTROLLED, "mail envoyé à l'auteur ");
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Changement d'état de l'article : OK");
            }
            $this->_redirect('/administrate');
        }

        $this->view->msgAuthor = Halms_Document_Logger::getLastComment($halmsDoc->getDocid(),Halms_Document::STATUS_XML_ERROR_REPORTED_AUTHOR);
        $this->view->files = $halmsDoc->listDclDir();
        $this->view->docStatus = $halmsDoc->getStatus();
        $this->view->outputFiles = [
            'XML' => 'halms' . $this->view->document->getDocid() . '.xml',
            'PDF' => 'halms' . $this->view->document->getDocid() . '_edited.pdf',
            'HTML' => 'index.xhtml'];
        $this->view->btn = [Halms_Document::STATUS_XML_FINISHED];
        $this->render('error-reported-author');
    }

    protected function pmc(Halms_Document $halmsDoc, $params)
    {
        if (isset($params['newstatus'])) {
            if ($params['newstatus'] == Halms_Document::STATUS_WAIT_FOR_PMC) {
                if ($halmsDoc->uploadPMC()) {
                    $halmsDoc->sendMail(
                        ['email' => Halms_Document::PMC_MAIL, 'name' => ''],
                        ['HALMS_ID' => "HALMS_" . $halmsDoc->getDocid(), 'USER' => Halms_Document::PMC_USERNAME, 'HALMS_ARCHIVE' => "halms" . $halmsDoc->getDocid() . ".zip"],
                        'mail_available_subject',
                        'mail_available_content');
                    $halmsDoc->changeStatus($params['newstatus']);
                    Halms_Document_Logger::log($params['docid'], Hal_Auth::getUid(), Halms_Document::STATUS_WAIT_FOR_PMC, "Transfert sur PMC");
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage('Transfert sur PubMed Central : OK');
                } else {
                    $this->_helper->FlashMessenger->setNamespace('danger')->addMessage('Transfert sur PubMed Central : KO');
                }
            }
            $this->_redirect('/administrate');
        }

        $this->view->docStatus = $halmsDoc->getStatus();
        if ($this->view->docStatus == Halms_Document::STATUS_XML_FINISHED) {
            //On propose le transfert sur PMC
            $this->view->btn = [Halms_Document::STATUS_WAIT_FOR_PMC];
        }
        $this->render('default');
    }
}
