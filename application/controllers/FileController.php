<?php
/**
 * Ouverture de fichiers
 */
class FileController extends Hal_Controller_Action
{

    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $params = $this->getRequest()->getParams();
        $document = Hal_Document::find(Ccsd_Tools::ifsetor($params['docid'], 0), Ccsd_Tools::ifsetor($params['identifiant'], ''), Ccsd_Tools::ifsetor($params['version'], 0));

        if ( false === $document || !$document->existFile() ) {
            // Dépôt inexistant ou pas de fichier
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Aucun document trouvé !");
            $this->redirect(PREFIX_URL . 'error/pagenotfound');
            exit;
        }
        $visible = false;
        if ( $document->isOnline() ) {
            if ( $document->isIndexed() || in_array($this->getRequest()->getClientIp(), Ccsd_Thumb::$THUMB_IP) ) {
                $visible = true;
            }
        }
        if ( !$visible && ( $document->getStatus() == Hal_Document::STATUS_BUFFER || $document->getStatus() == Hal_Document::STATUS_TRANSARXIV ) && Hal_Auth::isModerateur() ) {
            $visible = true;
        }
        if ( !$visible && $document->getStatus() == Hal_Document::STATUS_VALIDATE && Hal_Auth::isValidateur() ) {
            $visible = true;
        }
        if ( !$visible && ( Hal_Document_Acl::isOwner($document) || Hal_Auth::isHALAdministrator() || Hal_Auth::isAdministrator() ) ) {
            $visible = true;
        }
        if ( !$visible ) {
            $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Document non accessible !");
            $this->redirect(PREFIX_URL . 'index/index');
            exit;
        }
        $file = false;
        if ( isset($params['main']) ) {
            $file = $document->getDefaultFile();
        } else if ( isset($params['filename']) ) {
            $file = $document->getFile(str_replace('@', '/', $params['filename']));
        } else if ( isset($params['fileid']) ) {
            $file = $document->getFileByFileId((int)$params['fileid']);
        }

        if ( $file ) {
            // Accès direct pour ccsd04
            if (in_array($this->getRequest()->getClientIp(), Ccsd_Thumb::$THUMB_IP)) {
                $this->openFile($file);
                exit;
            }

            /* On regarde si le document a été déposé dans le portail courant
             * et si il est en ligne, sinon pas la peine de rediriger vers un autre portail
             * par exemple il est peut être en modération et le modérateur ne veut pas être redirigé
             * et devoir se ré-authentifier sur un autre portail
             */
            if ( (SITEID != $document->getSid()) && ($document->isOnline()) ) {
                return $this->redirect($this->getRedirectUrl($document, $params), ['code' => 301]);
            }

            $canRead = $file->canRead() || Hal_Auth::isHALAdministrator() || Hal_Auth::isAdministrator() || Hal_Auth::isModerateur() || Hal_Auth::getUid() == $document->getContributor('uid') || in_array(Hal_Auth::getUid(), $document->getOwner()) || Hal_Document_Filerequest::canRead($document->getDocid(), Hal_Auth::getUid()) || (isset($params['key']) && $params['key'] == $file->getMd5());
            if (!$canRead) {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Document non disponible : fin d'embargo le " . $file->getDateVisible());
                $this->redirect(PREFIX_URL);
                exit;
            }
            Hal_Document_Visite::add($document->getDocid(), Hal_Auth::getUID(), 'file', $file->getFileid());

            if (isset($params['format']) && ($params['format'] == "player")) {
                $this->view->document = $document;
                $this->view->file = $file;
                $this->render('video');

            } else {
                if (in_array($document->getTypDoc(), array('MAP', 'SON', 'VIDEO','SOFTWARE'))) {
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
                $this->redirect(PREFIX_URL);
            }
        } else {
            $this->redirect(PREFIX_URL . 'error/pagenotfound');
        }
    }

    /**
     *
     * @param $document Hal_Document
     * @param $params array
     * @return string
     */
    protected function getRedirectUrl($document, $params)
    {
        if (isset($params['docid'])) {
            $portail = Hal_Site::loadSiteFromId($document->getSid());
            $result = $portail->getUrl() . DIRECTORY_SEPARATOR . 'file' . DIRECTORY_SEPARATOR . 'index' . DIRECTORY_SEPARATOR;
            $result .= 'docid' . DIRECTORY_SEPARATOR . $params['docid'];
            if (isset($params['main'])){
                $result .= DIRECTORY_SEPARATOR . 'main' . DIRECTORY_SEPARATOR . '1';
            } else if (isset($params['fileid'])) {
                $result .= DIRECTORY_SEPARATOR . 'fileid' . DIRECTORY_SEPARATOR . $params['fileid'];
            } else if (isset($params['filename'])) {
                $result .= DIRECTORY_SEPARATOR . 'filename' . DIRECTORY_SEPARATOR . $params['filename'];
            }
        } else {
            $result = $document->getUri(isset($params['version']) && $params['version'] != '');
            if (isset($params['main'])) {
                $result .= DIRECTORY_SEPARATOR . 'document';
            } else if (isset($params['filename'])) {
                $result .= DIRECTORY_SEPARATOR . 'file' . DIRECTORY_SEPARATOR . $params['filename'];
            }
        }
        return $result;
    }


    /**
     *
     */
    public function thumbAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $request = $this->getRequest();
        $docid       = $request -> getParam('docid'      , 0);
        $identifiant = $request -> getParam('identifiant', '');
        $version     = $request -> getParam('version'    , 0);
        $id          = $request -> getParam('id'         , 0);
        // Filename are encoded for hierarchie so it can be put in url
        $filename    = str_replace('@','/',$request -> getParam('filename'   , ''));
        $format      = $request -> getParam('format'     , null);

        // Accepted format... and prepare for Url so with a '/'
        $format = (in_array($format, ['thumb','small','medium','large']) ? "/$format" : '');

        $document = Hal_Document::find($docid, $identifiant, $version);
        if (false === $document || !$document->existFile()) {
            // Dépôt inexistant ou pas de fichier
            exit;
        }
        if ( $id ) {
            $imagetteId = (int) $id;
        } else if ( $filename) {
            $imagetteId = $document->getFileThumbByFilename($filename);
        } else { // imagette principale du dépôt
            $imagetteId = $document->getThumb();
        }
        if ( $imagetteId ) {
            $this->redirect( "http:". THUMB_URL."/$imagetteId$format");
        }
        // Todo: ELSE
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
}