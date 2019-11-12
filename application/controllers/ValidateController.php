<?php
/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 10/01/2014
 * Time: 10:04
 */
class ValidateController extends Hal_Controller_Action {
	public function init() {
		if (! Hal_Auth::isValidateur ( SITEID )) {
			$this->_helper->FlashMessenger->setNamespace ( 'danger' )->addMessage ( "Vous ne disposez pas des privilèges pour accéder à cet espace." );
		}
	}
	public function indexAction() {
		$this->renderScript ( 'index/submenu.phtml' );
	}
	
	/**
	 * Documents à expertiser + validation expertise des docs
	 */
	public function documentsAction() {
		$moderation = new Hal_Moderation ();
		$params = $this->getRequest ()->getParams ();
		
		$docid = $this->getRequest ()->getParam ( 'docid' );
		$comment = htmlspecialchars ( $this->getRequest ()->getParam ( 'comment' ) );
		
		if ($docid != null) {
			
			if (isset ( $params ['validate-action'] )) {
				
				$validatorAnswer = "Réponse de l'expert : ";
				if ($params ['validate-action'] == 'accept') {
					$validatorAnswer .= 'Acceptation.';
				} else {
					$validatorAnswer .= 'Refus.';
				}
				
				if ($comment) {
					$validatorAnswer .= ' ' . 'Commentaire : ' . $comment;
				}
				
				$doc = new Hal_Document ( $docid, '', '', true );
				
				
				foreach ( Hal_Validation::getDocValidators ( $docid ) as $uid => $fullname ) {
					$validator = new Hal_User ();
					$validator->find ( $uid );
					
					// acusé de reception pour le validateur
					if ($uid == Hal_Auth::getUid ()) {
						$mail = new Hal_Mail ();
						$mail->prepare ( $validator, Hal_Mail::TPL_ALERT_VALIDATOR_CONFIRM_VALIDATION, array (
								$doc 
						) );
						$mail->writeMail ();
					}
					
					$mail = new Hal_Mail ();
					// Tous les validateurs sont prévenus de la fin de l'expertise
					$mail->prepare ( $validator, Hal_Mail::TPL_ALERT_VALIDATOR_END_VALIDATION, array (
							$doc 
					) );
					$mail->writeMail ();
				}
				
				Hal_Validation::delDocInValidation ( $docid );
				
				$doc->annotate ( Hal_Auth::getUid (), $validatorAnswer );
				
				$doc->putOnModeration ();
				Hal_Document_Logger::log ( $docid, Hal_Auth::getUid (), Hal_Document_Logger::ACTION_MODERATE );
				
				foreach ( $doc->getModerators () as $uid ) {
					$moderator = new Hal_User ();
					$moderator->find ( $uid );
					
					$mail = new Hal_Mail ();
					$mail->prepare ( $moderator, Hal_Mail::TPL_ALERT_MODERATOR, array (
							$doc 
					) );
					$mail->writeMail ();
				}
				
				$this->_helper->FlashMessenger->setNamespace ( 'success' )->addMessage ( "Le document a été remis en modération. Les modérateurs ont été informés par e-mail." );
				
				$this->redirect ( $this->view->url ( array (
						'controller' => 'validate',
						'action' => 'documents' 
				) ) );
			}
			
			// Affichage de documents
			
			$messages = new Hal_Evaluation_Validation_Message ();
			$this->view->responses = $messages->getList ( Hal_Auth::getUid () );
			$this->view->document = new Hal_Document ();
			$this->view->docid = $docid;
			
			return $this->render ( 'documents-actions' );
		}
		
		$validation = new Hal_Validation ();
		$this->view->documents = $validation->getDocuments ();
		
		$this->render ( 'documents' );
	}
	
	/**
	 * Gestion des réponses prédéfinies du modérateur
	 */
	public function messageAction() {
		$request = $this->getRequest ();
		if ($request->isPost ()) {
			$messageids = Hal_Evaluation_Validation_Message::getIds ( Hal_Auth::getUid (), Hal_Auth::isAdministrator () );
			$insert = 0;
			foreach ( $request->getPost () as $data ) {
				if (trim ( $data ['title'] ) == '' || trim ( $data ['message'] ) == '')
					continue;
				$insert ++;
				$data ['messageid'] = substr ( $data ['messageid'], 0, 4 ) == "tmp_" ? 0 : $data ['messageid'];
				if (! isset ( $data ['uid'] )) {
					$data ['uid'] = Hal_Auth::getUid ();
				}
				
				$m = new Hal_Evaluation_Validation_Message ();
				$m->setOptions ( $data );
				$m->save ();
				
				$key = array_search ( $data ['messageid'], $messageids );
				if ($key !== false) {
					unset ( $messageids [$key] );
				}
			}
			
			foreach ( $messageids as $messageid ) {
				$m = new Hal_Evaluation_Validation_Message ( $messageid, false );
				$m->delete ();
			}
			
			if ($insert) {
				$this->_helper->FlashMessenger->setNamespace ( 'success' )->addMessage ( "Les réponses prédéfinies ont été mises à jour." );
			}
		}
		
		$m = new Hal_Evaluation_Validation_Message ();
		$this->view->form = $m->getForm ();
		$this->view->messages_list = $m->getList ( Hal_Auth::getUid () );
		$this->view->isAdmin = Hal_Auth::isAdministrator ();
	}
	
	/**
	 * Gestion disponibilité de l'expert
	 */
	public function availabilityAction() {
		if ($this->getRequest ()->isPost ()) {
			$action = $this->getRequest ()->getParam ( 'expert-action' );
			
			switch ($action) {
				case 'terminate' :
					$res = Hal_Validation::setExpertNotAvailable ( Hal_Auth::getUid () );
					
					break;
				case 'engage' :
					$res = Hal_Validation::setExpertAvailable ( Hal_Auth::getUid () );
					break;
				default :
					
					break;
			}
			
			if ($res == true) {
				$this->_helper->FlashMessenger->setNamespace ( 'success' )->addMessage ( "Modifications sauvegardées" );
			}
		}
		
		$this->view->isAvailable = Hal_Validation::isAvailable ( Hal_Auth::getUid () );
	}
}













