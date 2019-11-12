<?php
/**
 * Ouverture de fichiers
 */
class FileController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $params = $this->getRequest()->getParams();
        $document = Hal_Document::find(Ccsd_Tools::ifsetor($params['docid'], 0), Ccsd_Tools::ifsetor($params['identifiant'], ''), Ccsd_Tools::ifsetor($params['version'], 0));

        if (false === $document || !$document->existFile()) { // Dépôt inexistant ou pas de fichier
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Aucun document trouvé !");
            $this->redirect(PREFIX_URL);
            exit;
        }
        $file = false;
        if ( isset($params['main']) ) {
            $file = $document->getDefaultFile();
        } else if ( isset($params['filename']) ) {
            $file = $document->getFile($params['filename']);
        } else if ( isset($params['fileid']) ) {
            $file = $document->getFileByFileId((int)$params['fileid']);
        }
        if ( $file ) {
            // Accès direct pour ccsd04
            if (in_array($this->getRequest()->getClientIp(), Ccsd_Thumb::$THUMB_IP)) {
                $this->openFile($file);
                exit;
            }
            $canRead = $file->canRead() || Hal_Auth::isHALAdministrator() || Hal_Auth::isAdministrator() || Hal_Auth::getUid() == $document->getContributor('uid') || in_array(Hal_Auth::getUid(), $document->getOwner()) || (isset($params['key']) && $params['key'] == $file->getMd5());
            if (!$canRead) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Document non disponible : fin d'embargo le " . $file->getDateVisible());
                $this->redirect(PREFIX_URL);
                exit;
            }

            if ($file->isVideo()) {
                $this->view->document = $document;
                $this->view->file = $file;
                $this->render('video');
            } else {
                if (in_array($document->getTypDoc(), array('MAP', 'SON', 'VIDEO'))) {
                    $this->openFile($file);
                    exit;
                } else if ($document->getTypDoc() == 'IMG') {
                    $defaultfileid = ($document->getDefaultFile()) ? $document->getDefaultFile()->getFileid() : null;
                    if (isset($params['main']) || $defaultfileid == $file->getFileid()) {
                        $jpeg = $document->get('jpeg', false);
                        if ($jpeg) {
                            header("Content-type: image/jpeg");
                            header("Content-Length: " . filesize($jpeg));
                            header('Content-Disposition: inline; filename="' . $file->getName() . '"');
                            if (ENV_DEV == APPLICATION_ENV) {
                                ob_end_flush();
                                readfile($jpeg);
                            } else {
                                header("X-Sendfile: " . $jpeg);
                            }
                            exit;
                        }
                    }
                    $this->openFile($file);
                    exit;
                } else {
                    $defaultfileid = ($document->getDefaultFile()) ? $document->getDefaultFile()->getFileid() : null;
                    if (isset($params['main']) || $defaultfileid == $file->getFileid()) {
                        $pdf = $document->get('pdf', false);
                        if ($pdf) {
                            header("Content-type: application/pdf");
                            header("Content-Length: " . filesize($pdf));
                            header('Content-Disposition: inline; filename="' . $file->getName() . '"');
                            if (ENV_DEV == APPLICATION_ENV) {
                                ob_end_flush();
                                readfile($pdf);
                            } else {
                                header("X-Sendfile: " . $pdf);
                            }
                            exit;
                        }
                    }
                    $this->openFile($file);
                    exit;
                }
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Aucun document trouvé !");
                $this->redirect(PREFIX_URL . 'index/index');
            }
        }
    }

    public function thumbAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $params = $this->getRequest()->getParams();
        $document = Hal_Document::find(Ccsd_Tools::ifsetor($params['docid'], 0), Ccsd_Tools::ifsetor($params['identifiant'], ''), Ccsd_Tools::ifsetor($params['version'], 0));

        if (false === $document || !$document->existFile()) { // Dépôt inexistant ou pas de fichier
            exit;
        }

        if ( isset($params['id']) ) {
            $imagetteId = (int)$params['id'];
        } else if ( isset($params['filename']) ) {
            $imagetteId = $document->getFileThumbByFilename($params['filename']);
        } else { // imagette principale du dépôt
            $imagetteId = $document->getThumb();
        }
        if ( $imagetteId ) {
            header("Location: ".THUMB_URL."/" . $imagetteId . ((isset($params['format'])&&in_array($params['format'], ['thumb','small','medium','large']))?'/'.$params['format']:''));
            exit;
        }
        exit;
    }

    /**
     * Ouverture d'un fichier dans l'espace temporaire d'un utilisateur
     * (au moment du dépôt)
     */
    public function tmpAction()
    {
        $this->_session = new Zend_Session_Namespace(SESSION_NAMESPACE);

        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $fid = $this->getRequest()->getParam('fid', false);
        if (false !== $fid && isset($this->_session->document) && $this->_session->document->existFile($fid)) {
            //Le fichier existe, on l'affiche
            $this->openFile($this->_session->document->getFile($fid));
        }
    }

    /**
     * @param Hal_Document_File $file
     */
    public function openFile ($file)
    {
        header("Content-type: " . $file->getTypeMIME());
        header("Content-Length: " . $file->getSize(true));
        header('Content-Disposition: inline; filename="' . $file->getName() . '"');
        if (ENV_DEV == APPLICATION_ENV) {
            ob_end_flush();
            readfile($file->getPath());
        } else {
            header("X-Sendfile: " . $file->getPath());
        }
    }

    public function packageAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $params = $this->getRequest()->getParams();
        if ( isset($params['docid']) && isset($params['filename']) ) {
            $document = Hal_Document::find($params['docid']);

            $filepath = $document->getRacineDoc() . 'halms/' . $params['filename'];

            $docfile = new Hal_Document_File();
            $docfile->setPath($filepath);
            $docfile->setTypeMIME(Ccsd_File::getMimeType($filepath));
            $docfile->setSize(filesize($filepath));
            $docfile->setName($params['filename']);
            $this->openFile($docfile);
        }
    }

    public function dclAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $params = $this->getRequest()->getParams();
        if ( isset($params['docid']) && isset($params['filename']) ) {
            $document = Hal_Document::find($params['docid']);
            $filepath = $document->getRacineDoc() . 'dcl/' . $params['filename'];
            if (! is_file($filepath) && preg_match('#filename/(.*)#', $this->getRequest()->REQUEST_URI, $matches)) {
                $filepath = $document->getRacineDoc() . 'dcl/' . $matches[1];
            }

            $docfile = new Hal_Document_File();
            $docfile->setPath($filepath);
            $docfile->setTypeMIME(Ccsd_File::getMimeType($filepath));
            $docfile->setSize(filesize($filepath));
            $docfile->setName($params['filename']);
            $this->openFile($docfile);
        }
    }
}