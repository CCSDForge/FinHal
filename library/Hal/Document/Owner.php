<?php
/**
 * Gestion des propriétaires de documents
 * User: yannick
 * Date: 09/01/2014
 * Time: 11:20
 */

class Hal_Document_Owner
{
    const TABLE         = 'DOC_OWNER';
    const TABLE_CLAIM   = 'DOC_OWNER_CLAIM';
    
    /**
     * Statuts d'un papiers
     */
    const STATUS_VISIBLE = 11; // article visible

    /**
     * @var null|Zend_Db_Adapter_Abstract
     */
    protected $_db = null;


    public function __construct()
    {
        $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
    }

    /**
     * Enregistre les propriétaires d'un document
     * @param string $identifiant
     * @param array $owners
     */
    public function saveDocOwners($identifiant, $owners)
    {
        if (! is_array($owners)) {
            $owners = array($owners);
        }
        $document = Hal_Document::find(0, $identifiant);
        if ( $document instanceof Hal_Document && $document->_docid != 0 ) {
            $this->_db->delete(self::TABLE, 'IDENTIFIANT = "' . $identifiant . '"');
            foreach ($owners as $uid) {
                $bind = array(
                    'IDENTIFIANT' => $identifiant,
                    'UID' => $uid,
                );
                $this->_db->insert(self::TABLE, $bind);
            }
        }
    }

    /**
     * Retourne toutes les demandes de propriété dont uid est le proprietaire ou le contributeur du depot
     * @param $uid
     * @return array
     */
    public function getClaimOwnership($uid)
    {
        // Atention: si un seul proprietaire, alors la table doc_owner ne contient pas d'entree pour l'identifiant
        // il faut donc faire un leftJoin et non pas un inner join
        $uid = (int) $uid;
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $sql = $db->select()
             ->from    (['d' => Hal_Document::TABLE] ,  ['DOCID'])
             ->joinLeft(['o' => self::TABLE]         , 'd.IDENTIFIANT=o.IDENTIFIANT',[])
             ->from    (['c' => self::TABLE_CLAIM]   , ['IDENTIFIANT', 'UID'])
             ->where('d.IDENTIFIANT=c.IDENTIFIANT')
             ->where('d.DOCSTATUS = ?', (int) self::STATUS_VISIBLE)
             ->where('d.UID = ? OR o.UID = ?', $uid, $uid)
             ->group(['c.UID','c.IDENTIFIANT']);    // Couple demandeur,doc demande unique (les jointure peuvent creer une duplication...)
        return $db->fetchAll($sql);
    }

    /**
     * Le propriétaire accepte de donner la propriété d'un de ses dépôt
     * @param Hal_Document $document
     * @param int $uid
     * @param string $mailTpl
     * 
     * @return bool propriété partagée
     */
    public function acceptClaimOwnership($document, $uid, $mailTpl = Hal_Mail::TPL_DOC_CLAIMOWNERSHIP_OK)
    {
        if ($mailTpl == null) {
            $mailTpl = Hal_Mail::TPL_DOC_CLAIMOWNERSHIP_OK;
        }
        $this->_db->delete(self::TABLE_CLAIM, 'IDENTIFIANT = "' . $document->getId() . '" AND UID = ' . (int) $uid);

        if (!$this->hasOwnership($uid, $document->getId())) {
            $bind = array(
                'IDENTIFIANT' => $document->getId(),
                'UID'   =>  $uid,
            );
            if ( $this->_db->insert(self::TABLE, $bind) ) {
                Hal_Document_Logger::log($document->getDocid(), $uid, Hal_Document_Logger::ACTION_SHARE, 'accept');
                $document->deleteCache();
                Ccsd_Search_Solr_Indexer::addToIndexQueue([$document->getDocid()]);
                //Envoi du mail
                $user = new Hal_User();
                $user->find($uid);
                $mail = new Hal_Mail();
                $mail->prepare($user, $mailTpl, array($document, 'SCREEN_NAME'=>Hal_Auth::getScreenName()));
                $mail->writeMail();
            } else {
                return false;
            }
        }
        return true;
    }

    /**
     * Refus de la demande de propriété
     * @param Hal_Document $document
     * @param int $uid
     */
    public function refusedClaimOwnership($document, $uid)
    {
        $this->_db->delete(self::TABLE_CLAIM, 'IDENTIFIANT = "' . $document->getId() . '" AND UID = ' . (int) $uid);
        Hal_Document_Logger::log($document->getDocid(), $uid, Hal_Document_Logger::ACTION_SHARE, 'refuse');
        //Envoi du mail
        $user = new Hal_User();
        $user->find($uid);
        $mail = new Hal_Mail();
        $mail->prepare($user, Hal_Mail::TPL_DOC_CLAIMOWNERSHIP_KO, array($document));
        $mail->writeMail();
    }

    /**
     * Envoi d'une demande de propriété
     * @param $document
     * @param $uid
     * @return bool
     */
    public function addClaimOwnership($document, $uid, $message)
    {
        $bind = array( 'UID'   =>  $uid, 'IDENTIFIANT' => $document->getId());
        try{
            $this->_db->insert(self::TABLE_CLAIM, $bind);
            Hal_Document_Logger::log($document->getDocid(), $uid, Hal_Document_Logger::ACTION_SHARE, 'demande');
       
            //recuperation des infos pour le contenu du mail
            $user = new Hal_User();
            $user->find($uid);

            $tags = array('document'    =>  $document,
                'REQUEST_USER'  =>  $user->getFullName(),
                'REQUEST_USER_EMAIL'  =>  $user->getEmail(),
                'REQUEST_MESSAGE' => $message);
            //Envoi du mail a tous les proprietaires
            $tabOwners = $document->getOwner();
            foreach ($tabOwners as $owner_uid) {
                $owner = new Hal_User ();
                $owner->find($owner_uid);
                
                $mail = new Hal_Mail ();
                $mail->prepare($owner, Hal_Mail::TPL_DOC_CLAIMOWNERSHIP, $tags);
                $mail->writeMail();
            }
   
     
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function addOwnership($document, $uid)
    {
        try{
            $this->_db->insert(self::TABLE, ['UID'   =>  $uid, 'IDENTIFIANT' => $document->getId()]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function removeOwnership(Hal_Document $document, $uid)
    {
        try{
            $this->_db->delete(self::TABLE, 'UID='.$uid.' AND IDENTIFIANT="'.$document->getId().'"');

            return true;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /**
     * Liste les demandes de propriétés envoyées
     * @param int $uid
     * @return array
     */
    public function getRequestOwnership($uid)
    {
        $sql = $this->_db->select()
            ->from(self::TABLE_CLAIM, array('DATECRE','IDENTIFIANT'))
            ->where('UID = ?', $uid);
        return $this->_db->fetchAll($sql);
    }

    /**
     * Indique si un utilisateur a déjà demandé la propriété d'un document
     * @param int $uid
     * @param string $identifiant
     * @return booleen
     */
    public function hasRequestedOwnership($uid, $identifiant)
    {
        $sql = $this->_db->select()
            ->from(self::TABLE_CLAIM, 'IDENTIFIANT')
            ->where('UID = ?', $uid)
            ->where('IDENTIFIANT = ?', $identifiant);
        return $this->_db->fetchCol($sql);
    }
    
    /**
     * Indique si un utilisateur a déjà le partage de propriété d'un document
     * @param int $uid
     * @param string $identifiant
     * @return booleen
     */
    public function hasOwnership($uid, $identifiant)
    {
        $sql = $this->_db->select()
            ->from(self::TABLE, 'IDENTIFIANT')
            ->where('UID = ?', $uid)
            ->where('IDENTIFIANT = ?', $identifiant);
        return $this->_db->fetchCol($sql);
    }

    /**
     * Partage de propriété à une liste de co-auteurs
     * @param $document
     * @param $listUid
     */
    public function shareOwnership($document, $listUid) {

        foreach ($listUid as $uid) {
            $this->addOwnership($document, $uid);
        }
    }


    /**
     * Modifie un identifiant pour tous les propriétaires d'un document en base
     * @param string $sOldIdent : ancien identifiant du document
     * @param string $sNewIdent : nouvel identifiant du document
     * @return boolean
     */
    public function updateIdentifiant($sOldIdent, $sNewIdent)
    {
        if (!isset($sOldIdent) || !isset($sNewIdent) || !is_string($sOldIdent) || !is_string($sNewIdent)) {
            return false;
        }
        $bind = [
            'IDENTIFIANT' => $sNewIdent,
        ];
        try {
            return $this->_db->update(self::TABLE, $bind, ['IDENTIFIANT = ?' => $sOldIdent]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Modifie un identifiant pour toutes les demandes de propriétés d'un document en base
     * @param string $sOldIdent : ancien identifiant du document
     * @param string $sNewIdent : nouvel identifiant du document
     * @return boolean
     */
    public function updateClaimIdentifiant($sOldIdent, $sNewIdent)
    {
        if (!isset($sOldIdent) || !isset($sNewIdent) || !is_string($sOldIdent) || !is_string($sNewIdent)) {
            return false;
        }
        $bind = [
            'IDENTIFIANT' => $sNewIdent,
        ];
        try {
            return $this->_db->update(self::TABLE_CLAIM, $bind, ['IDENTIFIANT = ?' => $sOldIdent]);
        } catch (Exception $e) {
            return false;
        }
    }

}