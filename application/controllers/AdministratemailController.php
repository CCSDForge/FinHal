<?php
 
class AdministratemailController extends Hal_Controller_Action
{

    public function indexAction()
    {
        $this->renderScript('index/submenu.phtml');
    }

	// Liste des templates de l'application
	public function templatesAction()
	{
		$this->view->templates = Hal_Mail_TemplatesManager::getList();
	}

	// Formulaire d'édition d'un template (affiché dans un modal)
	public function edittemplateAction()
	{
		$this->_helper->layout->disableLayout();
		
		$request = $this->getRequest();
		// $id = $request->getQuery('id');
		$params = $request->getParams();
		$id = $params['id'];
				 
		$oTemplate = new Hal_Mail_Template();
		$oTemplate->find($id);
		$template = $oTemplate->toArray();
		
		$langs = Zend_Registry::get('website')->getLanguages();
		$form = Hal_Mail_TemplatesManager::getTemplateForm($oTemplate, $langs);

		$this->view->langs = $langs;
		$this->view->form = $form;
		$this->view->template = $template;
		
		
	}
	
	// Enregistre une version modifiée d'un template 
	public function savetemplateAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

        Zend_Session::regenerateId();
		
		$request = $this->getRequest();
		$id = $request->getQuery('id');
		
		$template = new Hal_Mail_Template();
		$template->find($id);
				
		if (!$template) {
			throw new Zend_Exception('Ce template n\'existe pas');
		}
		
		$post = $request->getPost();
		foreach ($post as $lang=>$data) {
			foreach ($data as $field=>$value) {
				$options[$field][$lang] = $value;
			}
		}		
		
		$template->setOptions($options);
		
		if ($template->save()) {
			$this->_helper->FlashMessenger->setNamespace('success')->addMessage('Vos modifications ont bien été prises en compte');
		} else {
			$this->_helper->FlashMessenger->setNamespace('error')->addMessage('Les modifications ont échoué');
		}
		
		$this->_helper->redirector->gotoUrl('/administratemail/templates');
		return;
	}
	
	// Supprime un template personnalisé et restaure celui par défaut
	public function deletetemplateAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();

        Zend_Session::regenerateId();

		$request = $this->getRequest();
		$id = $request->getQuery('id');
		
		$template = new Hal_Mail_Template();
		$template->find($id);

		if ($template->delete()) {
			$this->_helper->FlashMessenger->setNamespace('success')->addMessage('Le template par défaut a été restauré');
		} else {
			$this->_helper->FlashMessenger->setNamespace('error')->addMessage('La suppression du template personnalisé a échoué');
		}
		
		$this->_helper->redirector->gotoUrl('/administratemail/templates');
		
	}
	
	
	// Historique des mails envoyés
	public function historyAction()
	{
		// $this->sendMail();
		
		$mails = new Hal_Mail();
		$history = $mails->getHistory();
		$this->view->history = $history;
		
	}
	
	// Détails d'un mail envoyé (dans un modal)
	public function viewAction()
	{
		$this->_helper->layout->disableLayout();
	
		$request = $this->getRequest();
		$id = $request->getParam('id');
			
		$oMail = new Hal_Mail('UTF-8');
		$oMail->find($id);
		$mail = $oMail->toArray();
		
		$this->view->mail = $mail;
		
		return;
	}
	
	// Module d'envoi de mails personnalisés
	public function sendAction()
	{
		$form = Hal_Mail_Send::getForm();
		$this->view->form = $form;
		
		$request = $this->getRequest();
		$post = $request->getPost();
		
		if ($post) {

            Zend_Session::regenerateId();
			
			$recipients = $post['recipientsForm']['recipientsList'];
			$recipients = Zend_Json::decode($recipients);
			
			$subject = $post['contentForm']['subject'];
			$content = $post['contentForm']['content'];
			$attachments = null; // TODO : gérer les fichiers joints
			
			if ($recipients) {
				foreach ($recipients as $recipient) {
				
					// Envoi du mail
					$mail = new Hal_Mail('UTF-8');
					$mail->addTo($recipient['mail'], $recipient['name']);
					$mail->setSubject($subject);
					$mail->setRawBody($content);
				
					$mail->addTag('%%SCREEN_NAME%%', $recipient['mail']);
				
					$mail->writeMail();
				}	
			} else {
				$errors[] = "Veuillez saisir au moins un destinataire";
			}
			
			if ($errors) {
				
				$message = '<p><strong>'.$this->view->translate("Votre message n'a pas pu être envoyé.").'</strong></p>';
				foreach ($errors as $error) {
					$message .= '<div>'.$error.'</div>';
				}
				$this->_helper->FlashMessenger->setNamespace('error')->addMessage($message);
				$this->_helper->redirector->gotoUrl('administratemail/send');
			} else {
				$message = '<strong>'.$this->view->translate("Votre message a bien été envoyé.").'</strong>';
				$this->_helper->FlashMessenger->setNamespace('success')->addMessage($message);
				$this->_helper->redirector->gotoUrl('administratemail/send');
			}
			
		}
	}
	
	// Récupère une liste de destinataires en fonction du type choisi (relecteurs, membres...)
	public function getrecipientsAction()
	{
		$this->_helper->layout()->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$request = $this->getRequest();
		$type = $request->getParam('type');
		
		$result = array();
		$recipients = Hal_UsersManager::getUsersWithRoles($type);
		foreach ($recipients as $recipient) {
			$user = array();
			$user['uid'] = $recipient->getUid();
			$user['name'] = $recipient->getFullName();
			$user['mail'] = $recipient->getEmail();
			$user['label'] = $user['name']. ' (' . $recipient->getUsername() . ') &lt;'.$user['mail'].'&gt;';
			$result[] = $user;
		}

		echo Zend_Json::encode(array_values($result));
	}
	
}