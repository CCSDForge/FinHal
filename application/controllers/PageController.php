<?php

/**
 * Controller permettant le rendu d'une page personnalisable
 *
 */
class PageController extends Hal_Controller_Action
{

    /**
     * Récupération du nom de la page à afficher
     * 
     * @see Zend_Controller_Action::init()
     */
    public function init ()
    {
        // Récupération du nom de la page
        $this->_page = $this->getRequest()->getActionName();
        $this->getRequest()->setActionName('render');
    }

    /**
     * Action récupérant le contenu de la page à afficher
     */
    public function renderAction ()
    {    	
    	$page = new Hal_Website_Navigation_Page_Custom(null, array(
    			'languages'	=>	Zend_Registry::get('languages'),
    			'page'	=>	$this->_page
    	));

    	// Sur le portail et l'instance HAL, on renvoit vers la doc externe quand la page n'est pas trouvée
        $webSiteId = Zend_Registry::get('website')->getSid();
        $oInstance = Hal_Instance::getInstance('');
        if ($webSiteId == 1 && $oInstance->getName() =='hal' && !file_exists($page->getPagePath(Zend_Registry::get('Zend_Locale')->getLanguage()))) {
            Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector')->gotoUrl('https://doc.archives-ouvertes.fr');
            return;
        }


    	if ((MODULE == SPACE_PORTAIL && Hal_Auth::isAdministrator()) || (MODULE == SPACE_COLLECTION && (Hal_Auth::isTamponneur(SITEID)) || Hal_Auth::isHALAdministrator() )) {
            $this->view->canEdit = true;
            if ($this->getRequest()->isPost()) {
                $params = $this->getRequest()->getPost();
                if (isset($params['method']) && $params['method'] == "edit") {
                    $this->view->mode = 'edit';
                    $form = $page->getContentForm();
                    $form->setAction(PREFIX_URL . 'page/' . $this->_page);
                    $this->view->form = $form;
                } elseif (isset($params['content'])) {
                    $params['content'] = array_filter($params['content']);
                    $page->setContent($params['content'], array_diff(Zend_Registry::get('website')->getLanguages(), $params['content']));
                    Hal_Cache::delete('home'); //Suppression du cache page d'accueil + page personnalisable
                }
            }
        }
    	$this->view->content = $page->getContent(Zend_Registry::get('lang'));
    	$this->view->page = $this->_page;
    }
    
    public function ajaxgetwidgetAction() 
    {
    	$this->_helper->layout()->disableLayout();
    	
    	if ($this->getRequest()->isXmlHttpRequest()) {
    		$this->view->settings = $this->getRequest()->getParam("settings");
    	}
    }
}