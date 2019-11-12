<?php
class Hal_Mail extends Ccsd_Mail {
	// Templates liés aux comptes
	const TPL_ACCOUNT_CREATE = 'account_create';
	const TPL_ACCOUNT_LOST_LOGIN = 'account_lost_login';
	const TPL_ACCOUNT_LOST_PWD = 'account_lost_pwd';

	// Templates liés aux documents
	const TPL_NOT_SUBMITTED = 'notice_submitted'; // Dépôt d'une notice
	const TPL_DOC_SUBMITTED = 'document_submitted'; // Dépôt d'un document
	const TPL_DOC_SUBMITTED_ONLINE = 'document_submitted_online'; // Dépôt d'un document avec passage en ligne immédiat
	const TPL_DOC_ACCEPTED = 'document_accepted'; // acceptation d'un dépôt
	const TPL_DOC_ACCEPTED_ARXIV = 'document_accepted_arxiv'; // acceptation d'un dépôt avec transfert arXiv
	const TPL_DOC_TOUPDATE = 'document_toupdate'; // le document doit être modifié
	const TPL_DOC_TOUPDATE_REMINDER = 'document_toupdate_reminder'; // le document doit être modifié - Relance
	const TPL_DOC_ADMINMODIFY = 'document_adminmodify'; // le document retourne en modération par un Administrateur

	const TPL_DOC_REFUSED = 'document_refused'; // le document est refusé
	const TPL_DOC_DELETED = 'document_deleted'; // le document est supprimé
    const TPL_DOC_FUSION = 'document_fusion'; // le document est fusionné

    // alertes
	const TPL_ALERT_MODERATOR = 'alert_moderator_new_submission'; // Alerte les moderateurs des nouveaux dépôts
	const TPL_ALERT_CORRESPONDING = 'alert_corresp_author_document_accepted'; // Alerte les auteurs correspondant lorsque le dépôt est accepté
	const TPL_ALERT_USER_SEARCH = 'alert_user_search'; // Alerte un utilisateur si il y a des nouveaux dépôts correspondant à sa recherche
	// alertes validation
	const TPL_ALERT_VALIDATOR = 'alert_validator_new_validation'; //alerte un expert sci sur un nouveau doc à expertiser - Relance
	const TPL_ALERT_VALIDATOR_REMINDER = 'alert_validator_new_validation_reminder'; //alerte un expert sci sur un nouveau doc à expertiser - Relance

	const TPL_ALERT_VALIDATOR_END_VALIDATION = 'alert_validator_end_validation'; //alerte un expert sci sur une validation terminée
	const TPL_ALERT_VALIDATOR_CONFIRM_VALIDATION = 'alert_validator_confirm_validation'; // confirme à un expert que sa validation est prise en compte

	const TPL_DOC_CLAIMOWNERSHIP = 'document_claim_ownership'; // Demande de propriété
	const TPL_DOC_CLAIMOWNERSHIP_DIRECT = 'document_claim_ownership_direct'; // Partage de propriété
	const TPL_DOC_CLAIMOWNERSHIP_OK = 'document_claim_ownership_ok'; // Acceptation d'une demande de propriété
	const TPL_DOC_CLAIMOWNERSHIP_KO = 'document_claim_ownership_ko'; // Refus d'une demande de propriété
	const TPL_DOC_FILE_ACCESS = 'document_file_access'; // Accès au fichiers sous embargo
	const TPL_DOC_FILE_ACCESS_OK = 'document_file_access_ok'; // Acceptation d'une demande d'accès embargo
	const TPL_DOC_FILE_ACCESS_KO = 'document_file_access_ko'; // Refus d'une demande d'accès embargo

    const TPL_ALERT_OWNERSHIP = 'document_alert_ownership'; // Alerte de copropriété du document
    const TPL_ALERT_REFSTRUCT = 'document_alert_refstruct'; // Alerte de copropriété du document
    const TPL_ALERT_ADMIN = 'document_alert_admin'; // Alerte de copropriété du document

    const TPL_REF_STRUCT_AJOUT = 'ref_structure_ajout'; // Ajout d'une sous-structure dans le référentiel
    const TPL_REF_STRUCT_FUSION = 'ref_structure_fusion'; // Fusion de structure dans le référentiel
    const TPL_REF_STRUCT_MODIF = 'ref_structure_modification'; // Modification de structure dans le référentiel
    const TPL_REF_STRUCT_SUPPR = 'ref_structure_suppression'; // Suppression de structure dans le référentiel

	protected $_portail;


	/**
	 * Sujet du mail (pour le débug)
	 *
	 * @var string
	 */
	protected $_subjectText = '';

    /**
     * Hal_Mail constructor.
     * @param string $charset
     */
	public function __construct($charset = 'UTF-8') {
		if (isset ( $charset )) {
			parent::__construct ( $charset );
		}
		if (APPLICATION_ENV != ENV_DEV && APPLICATION_ENV != ENV_TEST && APPLICATION_ENV != ENV_PREPROD) {
			$this->setPath ( CCSD_MAIL_PATH );
		}
		$this->setFrom ( Hal_Settings::getMailFrom (), $this->getPortail() );
		$this->setReplyTo ( $this->getFrom () );
		$this->setReturnPath ( $this->getFrom () );
		$this->addHeader ( 'X-Mailer', 'HAL' );
	}

    /**
     * @param string $to
     * @param string $tplName
     * @param array  $tags
     * @param string $lang
     */
	public function prepare($to, $tplName, $tags = array(), $lang = null) {
		// Destinataire
        if ($to instanceof Ccsd_User_Models_User) {
			$this->addTo ( $to->getEmail (), $to->getFullName () );
			if ($to instanceof Hal_User) {
				$lang = $to->getLangueid ();
			}
			$tags [] = $to;
		} else if (is_array ( $to )) {
			$this->addTo ( $to [0], $to [1] );
		} else if ($to != null) {
			$this->addTo ( $to );
		}

		// Langue
		if ($lang == null) {
			$lang = Zend_Registry::get ( 'lang' );
		}
		$this->setLang ( $lang );

		// Modèle de mail
		$subject = Zend_Registry::get ( 'Zend_Translate' )->translate ( $tplName, $this->getLang () );
		$content = '';
		$fileTemplate = PATH_TRANSLATION . '/' . $this->getLang () . '/emails/' . $tplName . '.phtml';
        if (is_file ( $fileTemplate )) {
			$content = file_get_contents ( $fileTemplate );
		}

        if ($tplName == Hal_Mail::TPL_ALERT_USER_SEARCH) {
            $this->setTagsForAlerts($tags);
        } else if (isset($tags['APPLI']) && ($tags['APPLI'] == "AuréHAL")) {
            $this->setTagsRef($tags);
        } else {
            $this->setTags($tags);
        }

        $this->_subjectText = $this->replaceTags ( $subject );
		$this->setSubject ( $this->_subjectText );
		$this->setRawBody ( $this->replaceTags ( $content ) );
	}

    /**
     * @param array $tags
     *     Array of tagname => value
     * If value is a Ccsd_User_Models_User or Hal_Document, tagname is not used
     *     and standard Tags for object are setted
     * Else, a tag %%TAGNAME%% is setted to value
     */
	public function setTags($tags) {
		$this->clearTags ();
		// Tags communs
		$this->addTag ( '%%PORTAIL%%', $this->getPortail() );
		$this->addTag ( '%%PORTAIL_URL%%', Zend_Registry::get ( 'website' )->getUrl () );
        
        if (Hal_Auth::isAdministrator()){
            $this->addTag ( '%%ADMIN%%', Hal_Auth::getScreenName() );
        }

        foreach ( $tags as $tagName => $value ) {
			if ($value instanceof Ccsd_User_Models_User) {
				// Tags pour un utilisateur
				$this->addTag ( '%%USER%%', $value->getFullName () );
			} else if ($value instanceof Hal_Document) {
				// Tags pour un document
                $this->addTag ( '%%DOCID%%', $value->getDocid() );
				$this->addTag ( '%%DOC_ID%%', $value->getId () );
				$this->addTag ( '%%DOC_PWD%%', $value->getPwd () );
				$this->addTag ( '%%DOC_VERSION%%', $value->getVersion () );
				$this->addTag ( '%%DOC_URL%%', $value->getUri () );
				$this->addTag ( '%%DOC_TITLE%%', $value->getMainTitle () );
				$this->addTag ( '%%DOC_TYPDOC%%', $value->getTypDoc() );
				$this->addTag ( '%%DOC_AUTHORS%%', $value->getListAuthors () );
				$this->addTag ( '%%DOC_LABOS%%', $value->getListStructures () );
				$this->addTag ( '%%DOC_DOMAINS%%', $value->getListDomains () );
				$this->addTag ( '%%DOC_DATE%%', $value->getSubmittedDate () );
				$this->addTag ( '%%DOC_REF%%', $value->getCitation ( 'full' ) );
				$this->addTag ( '%%DOC_CONTRIBUTOR%%', $value->getContributor ('fullname'));
            } else if ($tagName == 'SCREEN_NAME') {
			    // Todo: pourquoi ce cas est il different du suivant?
                $this->addTag ( '%%SCREEN_NAME%%', $value );
			} else if ((is_string ( $value )) || (is_int ( $value )) ) {
				$this->addTag ( '%%' . strtoupper ( $tagName ) . '%%', $value );
			}
		}
	}
    /**
     * Cas spécifique pour les alertes qui plantent à cause de Hal_Auth::isAdministrator
     * @param array $tags
     */
    public function setTagsForAlerts($tags) {
        $this->clearTags();
        // Tags communs
        $this->addTag('%%PORTAIL%%', $this->getPortail());
        $this->addTag('%%PORTAIL_URL%%', Zend_Registry::get('website')->getUrl());

        foreach ($tags as $tagName => $value) {
            if ($value instanceof Ccsd_User_Models_User) {
                // Tags pour un utilisateur
                $this->addTag('%%USER%%', $value->getFullName());
            } else if ((is_string($value)) || ( is_int($value))) {
                $this->addTag('%%' . strtoupper($tagName) . '%%', $value);
            }
        }
    }
    /**
     * Cas spécifique pour les alertes sur les référentiels
     * @param array $tags
     */
    public function setTagsRef($tags) {
        $this->clearTags();
        // Tags communs
        $this->addTag('%%PORTAIL%%', $this->getPortail());

        foreach ($tags as $tagName => $value) {
            if ($value instanceof Ccsd_User_Models_User) {
                // Tags pour un utilisateur
                $this->addTag('%%USER%%', $value->getFullName());
            } else if ((is_string($value)) || ( is_int($value))) {
                $this->addTag('%%' . strtoupper($tagName) . '%%', $value);
            }
        }
    }
    /**  */
    public function getBody() {
		return $this->getRawBody ();
	}
	/** getter */
	public function getSubjectText() {
		return $this->_subjectText;
	}
	/**  */
	public function writeMail() {
        try {
			if (APPLICATION_ENV == ENV_DEV || APPLICATION_ENV == ENV_TEST || APPLICATION_ENV == ENV_PREPROD) {
				if (Ccsd_Tools::isFromCli() === true) {
					Zend_Debug::dump($this, 'Debug output Mail',true);
				}
				$session = new Hal_Session_Namespace ();
				if (! is_array ( $session->mail )) {
					$session->mail = array ();
				}
				$session->mail [] = $this;
			} else {
				$this->write ();
			}
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

/**
 * HUM: TODO: je ne vois pas a quoi sert la variable protected $_portail
 *            setPortail la definie
 *            getPortail ne l'utilise pas...  Ccsd::Mail non plus
 *      D'autre part, je ne vois pas pourquoi le portail serait une propriete de l'object Mail?
 */
	public function getPortail() {
		if ((defined ( 'SITENAME' )) && (SITENAME != '')) {
			$portail = SITENAME;
		} else {
			try {
				$portail =  Zend_Registry::get ( 'website' )->getSiteName ();
			} catch ( Exception $e ) {
				$portail = 'HAL';
			}
		}

		if ($portail == '') {
			return 'HAL';
		}

		return strtoupper($portail);
	}

    /**
     * @param string $portail
     */
	public function setPortail($portail) {
		$this->_portail = $portail;
	}


}
