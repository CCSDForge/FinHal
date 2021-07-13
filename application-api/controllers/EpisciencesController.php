<?php

class EpisciencesController extends Zend_Controller_Action
{

    const STATUS_SUCCESS = 1;
    const STATUS_ID_MISSING = 2;
    const STATUS_JOURNALID_MISSING = 3;
    const STATUS_JOURNALID_NOMATCH = 4;
    const STATUS_DOCUMENT_NOTFOUND = 5;
    const STATUS_TOKEN_MISSING = 6;
    const STATUS_TOKEN_INVALID = 7;
    const STATUS_VERSION_MISSING = 8;

    public function init()
    {
	// define episciences constants
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/episciences.ini', APPLICATION_ENV);
        foreach ($config->consts as $name => $value) {
            define($name, $value, true);
        }
    }

    /**
     * update a document after its publication on Episciences (doctype, meta, log, params)
     * parameters are fetched from a post array
     * @param string $identifier
     * @param int $version
     * @param string $rvcode episciences review short code
     * @param string $date publication date (optional)
     * @param string $volume episciences volume name (optional)
     * @param string $token security token (used for checking that the request was really sent from Episciences)
     * @return bool
     * return a boolean, and display a json encoded array (status, message, success)
     */
    public function publicationAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        $result = ['status' => 0, 'message' => '', 'success' => 0, 'params' => $this->getRequest()->getPost()];
	
        // check identifier
        $identifier = $this->getRequest()->getPost('identifier');
        if (!$identifier) {
            $result['status'] = self::STATUS_ID_MISSING;
            $result['message'] = 'identifier could not be found';
            echo Zend_Json::encode($result);
            return false;
        }

        // check version
        $version = $this->getRequest()->getPost('version');
        if (!$version || !is_numeric($version)) {
            $result['status'] = self::STATUS_VERSION_MISSING;
            $result['message'] = 'version could not be found';
            echo Zend_Json::encode($result);
            return false;
        }

        // fetch journal id
        $rvcode = $this->getRequest()->getPost('rvcode');
        if (!$rvcode) {
            $result['status'] = self::STATUS_JOURNALID_MISSING;
            $result['message'] = 'journal id could not be found';
            echo Zend_Json::encode($result);
            return false;
        }
        $rvid = preg_replace("/[^A-Za-z0-9_]/", '', $rvcode);
        if (!defined($rvid . '_ID') || !constant($rvid . '_ID')) {
            $result['status'] = self::STATUS_JOURNALID_NOMATCH;
            $result['message'] = 'journal id could not be matched';
            echo Zend_Json::encode($result);
            return false;
        }
        $rvid = constant($rvid . '_ID');

        // check volume name
        $volume = $this->getRequest()->getPost('volume');

        // check token
        $token = $this->getRequest()->getPost('token');
        if (!$token) {
            $result['status'] = self::STATUS_TOKEN_MISSING;
            $result['message'] = 'security token is missing';
            echo Zend_Json::encode($result);
            return false;
        }

        // check publication date
        $date = $this->getRequest()->getPost('date');

        // check document type
        $typdoc = $this->getRequest()->getPost('typdoc');

        if ($token != hash('sha256', EPISCIENCES_KEY . $rvcode . $volume . $identifier . $version)) {
            $result['status'] = self::STATUS_TOKEN_INVALID;
            $result['message'] = 'security token is not valid';
            echo Zend_Json::encode($result);
            return false;
        }

        // fetch document
        $document = Hal_Document::find(0, $identifier, $version);
        if (!$document) {
            $result['status'] = self::STATUS_DOCUMENT_NOTFOUND;
            $result['message'] = 'document could not be found';
            echo Zend_Json::encode($result);
            return false;
        }

        // prepare metadata
        $metadata = [
            'journal' => $rvid,
            'volume' => $volume,
            'date' => ($date) ? $date : date('Y-m-d'),
            'peerReviewing' => 1,
            'popularLevel' => 0,
            'audience' => 2
        ];
        if ($typdoc) {
            $metadata['typdoc'] = $typdoc;
        }

        // set metadata
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        foreach ($metadata as $name => $value) {
            if (is_null($value)) continue;
            if (array_key_exists($name, $document->getMeta())) {
                $db->update(Hal_Document_Metadatas::TABLE_META, ['METAVALUE' => $value], ['DOCID = ?' => $document->getDocid(), 'METANAME = ?' => $name]);
            } else {
                $db->insert(Hal_Document_Metadatas::TABLE_META, ['DOCID' => $document->getDocid(), 'METANAME' => $name, 'METAVALUE' => $value]);
            }
        }

        // update document type
        $db->update(Hal_Document::TABLE, ['TYPDOC' => $metadata['typdoc']], ['DOCID = ?' => $document->getDocid()]);

        // delete cache
        $document->deleteCache();

        // log
        Hal_Document_Logger::log($document->getDocid(), EPISCIENCES_UID, Hal_Document_Logger::ACTION_UPDATE);

        // stamp
        Hal_Document_Collection::add($document->getDocid(), EPISCIENCES_SID, EPISCIENCES_UID, true);

        $result['status'] = self::STATUS_SUCCESS;
        $result['success'] = true;
        echo Zend_Json::encode($result);
        return true;

    }
}
