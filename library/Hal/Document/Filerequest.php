<?php
/**
 * Gestion des demandes d'accès aux documents sous embargo
 */

class Hal_Document_Filerequest
{
    const TABLE = 'DOC_FILE_REQUEST';

    /**
     * @var null|Zend_Db_Adapter_Abstract
     */
    protected $_db = null;


    public function __construct()
    {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
    }

    /**
     * Un propriétaire accepte de donner le fichier
     * @param Hal_Document $document
     * @param int $uid
     * @return bool
     */
    public function acceptRequest($document, $uid)
    {
        try {
            if ( self::haveRequest($document->getDocid(), (int)$uid) ) {
                $this->_db->delete(self::TABLE, 'DOCID = ' . $document->getDocid() . ' AND UID = ' . (int)$uid);
                Hal_Document_Logger::log($document->getDocid(), Hal_Auth::getUid(), Hal_Document_Logger::ACTION_REQUESTFIE, 'accept: '.$uid);
                //Envoi du mail
                $user = new Hal_User();
                $user->find($uid);
                $mail = new Hal_Mail();
                $defaultFile = $document->getDefaultFile();
                if ($defaultFile instanceof Hal_Document_File) {
                    $url = $document->getUri(true) . '/document/' . $defaultFile->getMd5();
                    $mail->prepare($user, Hal_Mail::TPL_DOC_FILE_ACCESS_OK, ['document' => $document, 'ACCEPTEDBY' => Hal_Auth::getFullName(), 'DOC_FILE_URL' => $url, 'DOC_FILENAME' => $document->getDefaultFile()->getName()]);
                    $mail->writeMail();
                    return true;
                } else {
                    return false;
                }
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Refus de la demande de fichier
     * @param Hal_Document $document
     * @param int $uid
     * @return bool
     */
    public function refusedRequest($document, $uid)
    {
        try {
            if ( self::haveRequest($document->getDocid(), (int)$uid) ) {
                $this->_db->delete(self::TABLE, 'DOCID = ' . $document->getDocid() . ' AND UID = ' . (int)$uid);
                Hal_Document_Logger::log($document->getDocid(), Hal_Auth::getUid(), Hal_Document_Logger::ACTION_REQUESTFIE, 'rejection: '.$uid);
                //Envoi du mail
                $user = new Hal_User();
                $user->find($uid);
                $mail = new Hal_Mail();
                $mail->prepare($user, Hal_Mail::TPL_DOC_FILE_ACCESS_KO, ['document' => $document, 'REFUSEDBY' => Hal_Auth::getFullName(), 'DOC_FILENAME' => $document->getDefaultFile()->getName()]);
                $mail->writeMail();
                return true;
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Envoi d'une demande
     * @param Hal_Document $document
     * @param int $uid
     * @return bool
     */
    public function addRequest($document, $uid)
    {
        $bind = array('UID'=>$uid, 'DOCID'=>$document->getDocid());
        try{
            $this->_db->insert(self::TABLE, $bind);
            Hal_Document_Logger::log($document->getDocid(), $uid, Hal_Document_Logger::ACTION_REQUESTFIE, 'ask');
            //recuperation des infos pour le contenu du mail
            try {
                $webSiteUrl = Zend_Registry::get('website')->getUrl();
            } catch (Exception $e) {
                $webSiteUrl = 'https://' . $_SERVER['SERVER_NAME'];
            }
            $tags = ['REQUEST_URL' => $webSiteUrl,
                'REQUEST_UID' => Hal_Auth::getUid(),
                'REQUEST_USER' => Hal_Auth::getFullName(),
                'REQUEST_USER_EMAIL' => Hal_Auth::getUser()->getEmail(),
                'DOC_FILENAME' => $document->getDefaultFile()->getName(),
                'document' => $document];
            //Envoi du mail a tous les proprietaires
            foreach ($document->getOwner() as $owner_uid) {
                $owner = new Hal_User();
                $owner->find($owner_uid);
                $mail = new Hal_Mail();
                $mail->prepare($owner, Hal_Mail::TPL_DOC_FILE_ACCESS, $tags);
                $mail->writeMail();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Liste les demandes de fichiers envoyées
     * @param int $uid
     * @return array
     */
    public function getRequest($uid)
    {
        $sql = $this->_db->select()
            ->from(self::TABLE, ['DOCID', 'DATECRE'])
            ->where('UID = ?', $uid)
            ->order('DATECRE DESC');
        return $this->_db->fetchAll($sql);
    }

    /**
     * Liste les historiques des demandes de fichiers
     * @param int $uid
     * @return array
     */
    public function getRequestHistory($uid)
    {
        $sql = $this->_db->select()
            ->from(Hal_Document_Logger::TABLE, ['DOCID', 'MESG', 'DATELOG'])
            ->where('UID = ?', $uid)
            ->where('LOGACTION = ?', Hal_Document_Logger::ACTION_REQUESTFIE)
            ->where('MESG LIKE "accept:%" OR MESG LIKE "rejection:%"')
            ->order('DATELOG DESC');
        return $this->_db->fetchAll($sql);
    }

    /**
     * Liste les acceptations de lire des fichiers sous embargo
     * @param int $uid
     * @return array
     */
    public function getAccept($uid)
    {
        $sql = $this->_db->select()
            ->from(Hal_Document_Logger::TABLE, ['DOCID'])
            ->where('LOGACTION = ?', Hal_Document_Logger::ACTION_REQUESTFIE)
            ->where('MESG = ?', 'accept: '.$uid)
            ->order('DATELOG DESC');
        return $this->_db->fetchAll($sql);
    }

    /**
     * Liste les refus de lire des fichiers sous embargo
     * @param int $uid
     * @return array
     */
    public function getRejection($uid)
    {
        $sql = $this->_db->select()
            ->from(Hal_Document_Logger::TABLE, ['DOCID'])
            ->where('LOGACTION = ?', Hal_Document_Logger::ACTION_REQUESTFIE)
            ->where('MESG = ?', 'rejection: '.$uid)
            ->order('DATELOG DESC');
        return $this->_db->fetchAll($sql);
    }

    /**
     * Liste les fichiers demandés
     * @param int $uid
     * @return array
     */
    public function getDocidsWhereRequest($uid)
    {
        $sql = $this->_db->select()
            ->from(['r'=>self::TABLE], '')
            ->from(['d'=>Hal_Document::TABLE], ['r.UID','d.DOCID','r.DATECRE'])
            ->where('d.DOCID=r.DOCID')
            ->where('d.UID = ?', $uid)
            ->order('r.DATECRE ASC');
        return $this->_db->fetchAll($sql);
    }

    /**
     * Le document docid est il dispo pour uid
     * @param int
     * @param int
     * @return bool
     */
    public static function canRead($docid, $uid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(Hal_Document_Logger::TABLE, 'count(*)')
            ->where('DOCID = ?', $docid)
            ->where('LOGACTION = ?', Hal_Document_Logger::ACTION_REQUESTFIE)
            ->where('MESG = ?', 'accept: '.$uid);
        return (bool)$db->fetchOne($sql);
    }

    /**
     * Le document docid a t il déjà été refusé pour uid
     * @param int
     * @param int
     * @return bool
     */
    public static function alreadyRejected($docid, $uid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
            ->from(Hal_Document_Logger::TABLE, 'count(*)')
            ->where('DOCID = ?', $docid)
            ->where('LOGACTION = ?', Hal_Document_Logger::ACTION_REQUESTFIE)
            ->where('MESG = ?', 'rejection: '.$uid);
        return (bool)$db->fetchOne($sql);
    }

    /**
     * Le document docid a t il été demandé par uid
     * @param int
     * @param int
     * @return bool
     */
    public static function haveRequest($docid, $uid)
    {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()->from(self::TABLE, 'count(*)')->where('DOCID = ?', $docid)->where('UID = ?', $uid);
        return (bool)$db->fetchOne($sql);
    }

}