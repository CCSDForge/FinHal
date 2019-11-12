<?php

/**
 * Class EpisciencesController
 * Update HAL document meta when an article is published in an Episicences journal
 * TODO: use a feed like arxiv https://arxiv.org/help/bib_feed because it can be used by other OA repository, not only HAL
 *
 */
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
     * Update a document after its publication on Episciences (doctype, meta, log, params)
     * parameters are fetched from a post array
     * @return bool and display a json encoded array (status, message, success)
     * @throws Zend_Db_Adapter_Exception
     */
    public function publicationAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        /** @var Zend_Controller_Request_Http $request */
        $request = $this->getRequest();
        $result = ['status' => 0, 'message' => '', 'success' => 0, 'params' => $request->getPost()];
	
        // check identifier
        $identifier = $request->getPost('identifier');
        if (!$identifier) {
            $result['status'] = self::STATUS_ID_MISSING;
            $result['message'] = 'identifier could not be found';
            echo Zend_Json::encode($result);
            return false;
        }

        // check version
        $version = $request->getPost('version');
        if (!$version || !is_numeric($version)) {
            $result['status'] = self::STATUS_VERSION_MISSING;
            $result['message'] = 'version could not be found';
            echo Zend_Json::encode($result);
            return false;
        }

        // fetch journal id
        $rvcode = $request->getPost('rvcode');
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
        $volume = $request->getPost('volume');

        // check token
        $token = $request->getPost('token');
        if (!$token) {
            $result['status'] = self::STATUS_TOKEN_MISSING;
            $result['message'] = 'security token is missing';
            echo Zend_Json::encode($result);
            return false;
        }

        // check publication date
        $date = $request->getPost('date');

        // check for a DOI
        $doi = $request->getPost('doi');

        // check document type
        $typdoc = $request->getPost('typdoc');

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

        $db = Zend_Db_Table_Abstract::getDefaultAdapter();

        // set DOI
        if ($doi != '') {
           $document->addIdExt($document->getDocid(), Hal_Submit_Manager::DOI, $doi);
        }

        if ($typdoc) {
            $metadata['typdoc'] = $typdoc;
        }

        // set metadata

        foreach ($metadata as $name => $value) {
            if (is_null($value)) continue;
            if (array_key_exists($name, $document->getHalMeta()->getMeta())) {
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
        $episcienceSite = Hal_Site::loadSiteFromId(EPISCIENCES_SID);
        Hal_Document_Collection::add($document->getDocid(), $episcienceSite, EPISCIENCES_UID, true);

        $result['status'] = self::STATUS_SUCCESS;
        $result['success'] = true;
        echo Zend_Json::encode($result);
        return true;

    }
}
