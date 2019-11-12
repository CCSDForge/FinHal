<?php

/**
 * Class WebsiteController
 */
class WebsiteController extends Hal_Controller_Action
{
    /** @var Hal_Session_Namespace */
    protected $_session = null;

    public function init()
    {
        // Session courante
        $this->_session = new Zend_Session_Namespace (SESSION_NAMESPACE);
    }

    /**
     * Liste des sous menus du controller
     */
    public function indexAction()
    {
        $this->view->title = "Site Web";
        $this->renderScript('index/submenu.phtml');
    }

    /**
     * Configuration générale (langue)
     */
    public function commonAction()
    {
        $common = new Ccsd_Website_Common (SITEID, [
            'languages' => Hal_Translation_Plugin::getAvalaibleLanguages()
        ]);
        $form = $common->getForm();
        if ($this->getRequest()->isPost() && $form->isValid($this->getRequest()->getPost())) {
            $common->save($form->getValues());
            unset ($this->_session->website [SITEID]);
            Zend_Registry::set('languages', $form->getValue('languages'));
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les modifications ont bien été enregistrées.");
            $this->redirect(PREFIX_URL . 'website/common');
        }
        $this->view->form = $form;
        $this->view->piwik = $common->getPiwikid();
    }

    /**
     * Gestion des pages du site
     */
    public function menuAction()
    {
        $site = Hal_Site::getCurrent();
        $siteid = $site->getSid();
        if ((isset ($this->_session->website) && !is_array($this->_session->website)) || !isset ($this->_session->website [$siteid])) {
            // Récupération de la navigation du portail ou d'une collection
            if (!is_array($this->_session->website)) {
                $this->_session->website = [];
            }
            $nav = new Hal_Website_Navigation ($site, [
                'languages' => Zend_Registry::get('languages'),
                'sid' => $siteid
            ]);
            $nav ->load();
            $this->_session->website [$siteid] = $nav;
        }

        if ($this->getRequest()->isPost()) {

            $valid = true;
            $pagesDisplay = [];

            $pages = [];

            foreach ($this->getRequest()->getPost() as $id => $options) {

                if (substr($id, 0, 6) != 'pages_')
                    continue;
                $pageid = str_replace('pages_', '', $id);
                if (isset ($_FILES [$id] ['name']) && is_array($_FILES [$id] ['name'])) {
                    $options = array_merge($options, $_FILES [$id] ['name']);
                }

                // Cas particulier des filtres
                if (isset ($options ['filter']) && is_array($options ['filter'])) {
                    $options ['filter'] = implode(';', $options ['filter']);
                }
                $this->_session->website [$siteid]->setPage($pageid, $options);
                $this->_session->website [$siteid]->getPage($pageid)->initForm();

                $action = $this->_session->website [$siteid]->getPage($pageid)->getAction();

                if (($options ['type'] == 'Hal_Website_Navigation_Page_Structure') or ($options ['type'] == 'Hal_Website_Navigation_Page_Author') or ($options ['type'] == 'Hal_Website_Navigation_Page_Collections')) {

                    if (array_key_exists($action, $pages)) {
                        $pages [$action]++;
                        $valid = false;
                        $message = "Une seule page du type <b>" . $this->view->translate($this->_session->website [$siteid]->getPage($pageid)->getPageClassLabel()) . ' <i>' . $action . '</i></b> autorisée.';
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage($message);
                        $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Les modifications n'ont <b>pas</b> été sauvegardées.");
                    } else {
                        $pages [$action] = 1;
                    }
                }

                if ($options ['type'] != 'Hal_Website_Navigation_Page_File' && !$this->_session->website [$siteid]->getPage($pageid)->getForm($pageid)->isValid($options)) {
                    $pagesDisplay [$pageid] = true;
                    $valid = false;
                } else {
                    $pagesDisplay [$pageid] = false;
                }
            }
            //options['type'] = Hal_Website_Navigation_Page_Link / La limite de la valeur pour la page lien est de 255 (BDD)
            if ($valid) {
                // Tous les elements sont valides
                // Enregistrement du menu
                $this->_session->website [$siteid]->save();
                // Création de la navigation du site et des ACL
                $this->_session->website [$siteid]->createNavigation(SPACE . CONFIG . 'navigation.json');
                if (is_file(SPACE . CONFIG . 'acl.ini')) {
                    unlink(SPACE . CONFIG . 'acl.ini');
                }
                // Suppression des fichiers de cache pour la consultation
                Hal_Cache::delete('browse');
                // unset($this->_session->website);
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les modifications ont bien été enregistrées.");
                $this->redirect(PREFIX_URL . 'website/menu');
            } else {
                $this->_helper->FlashMessenger->setNamespace('danger')->addMessage("Erreur de saisie");
            }
            $this->view->pagesDisplay = $pagesDisplay;
        }

        // Zend_Debug::dump($this->_session->website->getOrder());exit;
        $this->view->pages = $this->_session->website [SITEID]->getPages();
        $this->view->order = $this->_session->website [SITEID]->getOrder();
        // Types de pages
        $pageTypes = $this->_session->website [SITEID]->getPageTypes(true);
        if (MODULE == SPACE_COLLECTION) {
            // On est dans une collection, on retire certaines pages
            foreach ($this->_session->website [SITEID]->getCollectionPageToExclude() as $page) {
                unset ($pageTypes [$page]);
            }
        }
        $this->view->pageTypes = $pageTypes;
    }

    /**
     * Ajout d'une nouvelle page
     */
    public function ajaxformpageAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset ($params ['type']) && $params ['type'] != '') {
            $this->view->i = $this->_session->website [SITEID]->addPage($params ['type']);
            $this->view->page = $this->_session->website [SITEID]->getPage($this->view->i);
            $this->render('menu-page-form');
        }
    }

    /**
     * Modification de l'ordre des pages
     */
    public function ajaxorderAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset ($params ['page']) && is_array($params ['page'])) {
            $this->_session->website [SITEID]->changeOrder($params ['page']);
        }
    }

    /**
     * Suppression d'une page du site
     */
    public function ajaxrmpageAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset ($params ['idx'])) {
            $this->_session->website [SITEID]->deletePage($params ['idx']);
        }
    }

    /**
     * Réinitialisation du menu
     */
    public function resetAction()
    {
        unset ($this->_session->website [SITEID]);
        $this->redirect(PREFIX_URL . 'website/menu');
    }

    /**
     * Modification de l'en-tête d'un site
     */
    public function headerAction()
    {
        $site = Hal_Site::getCurrent();
        $header = new Hal_Website_Header ($site);
        if ($this->getRequest()->isPost()) {
            if (isset($_POST['header'])) {
                $isValid = $header->isValid($this->getRequest()->getPost(), $_FILES);
                if (true === $isValid) { // Formulaire valide
                    $header->save($this->getRequest()->getPost(), $_FILES);
                    $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les modifications ont bien été enregistrées.");
                } else { // Erreur sur le formulaire
                    $this->view->errors = $isValid;
                    $header->setHeader($this->getRequest()->getPost(), $_FILES);
                    $this->view->forms = $header->getForms(false);
                }
            }
        }
        if (!isset ($this->view->forms)) {
            $this->view->forms = $header->getForms();
        }
    }

    /**
     * Modification de l'en-tête d'un site
     */
    public function footerAction()
    {
        $site = Hal_Site::getCurrent();
        $footer = new Hal_Website_Footer ($site);

        if ($this->getRequest()->isPost()) {
            if (isset($_POST['footer'])) {
                $footer->save($this->getRequest()->getPost());
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les modifications ont bien été enregistrées.");
            }
        }

        $this->view->form = $footer->getForm();
    }

    /**
     * Modification de l'en-tête d'un site
     */
    public function ajaxheaderAction()
    {
        $site = Hal_Site::getCurrent();
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $header = new Hal_Website_Header ($site);
        echo $header->getLogoForm($this->getRequest()->getParam('id', '0'));
    }

    /**
     * Personnalisation du style du site
     */
    public function styleAction()
    {
        $styles = new Ccsd_Website_Style (SITEID, SPACE . 'public/', SPACE_URL);
        if ($this->getRequest()->isPost() && $styles->getForm()->isValid($this->getRequest()->getParams())) {
            $styles->save($styles->getForm()->getValues());
            $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les modifications ont bien été enregistrées.");
        }
        $styles->populate();
        $this->view->form = $styles->getForm();
    }

    /**
     * Personnalisation des filtres de Recherche
     */
    public function searchAction()
    {
        if ($this->getRequest()->isPost()) {

            $docType = $this->getParam('docType_s', []);
            $submitType = $this->getParam('submitType_s', []);

            $search = new Hal_Website_Search($docType, $submitType);

            if ($search->save()) {
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les modifications ont bien été enregistrées.");
            } else {
                $this->_helper->FlashMessenger->setNamespace('error')->addMessage("Les modifications n'ont pas été enregistrées.");
            }
        } else {
            $search = new Hal_Website_Search();
        }

        $this->view->search = $search;


    }

    /**
     * Gestion des actualités
     */
    public function newsAction()
    {
        $news = new Hal_News ();
        $form = $news->getForm($this->getRequest()->getParam('newsid', 0));

        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getParams())) {
                $news->save(array_merge($form->getValues(), [
                    'uid' => Hal_Auth::getUid()
                ]));
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Les modifications ont bien été enregistrées.");
                Hal_Cache::delete('home.news');
                $this->redirect(PREFIX_URL . 'website/news');
            } else {
                // $this->_helper->FlashMessenger->setNamespace(MSG_ERROR)->addMessage("Erreur dans la saisie");
                $this->view->errors = $this->getRequest()->getParams();
            }
        }

        $this->view->news = $news->getListNews(false);
        $this->view->form = $news->getForm();
    }

    /**
     * Récupération du formulaire d'ajout/édition d'une actu
     */
    public function ajaxnewsformAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset ($params ['newsid'])) {
            $news = new Hal_News ();
            echo '<form method="post" action="' . PREFIX_URL . 'website/news">' . $news->getForm($params ['newsid']) . '</form>';
        }
    }

    /**
     * Suppression d'une actualité
     */
    public function ajaxnewsdeleteAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();

        $request = $this->getRequest();
        $params = $request->getPost();
        if ($request->isXmlHttpRequest() && $request->isPost() && isset ($params ['newsid'])) {
            $news = new Hal_News ();
            $news->delete($params ['newsid']);
            Hal_Cache::delete('home.news');
        }
    }

    /**
     * Affichage des ressources publiques d'un site
     */
    public function publicAction()
    {
        $dir = SPACE . 'public/';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $params = $this->getRequest()->getParams();
        if ($this->getRequest()->isPost() && isset ($params ['method'])) {
            if ($params ['method'] == 'remove') {
                // Suppression d'un fichier
                if (isset ($params ['name']) && is_file($dir . $params ['name'])) {
                    unlink($dir . $params ['name']);
                }
            } else if (isset ($_FILES ['file'] ['tmp_name']) && $_FILES ['file'] ['tmp_name'] != '') {
                // Ajout d'un fichier
                copy($_FILES ['file'] ['tmp_name'], $dir . Ccsd_File::renameFile($_FILES ['file'] ['name'], $dir));
                $this->_helper->FlashMessenger->setNamespace('success')->addMessage("Le fichier a été déposé.");
            }
        }

        $files = [];
        if (is_dir($dir)) {
            foreach (scandir($dir) as $file) {
                if (!in_array($file, [
                    '.',
                    '..'
                ])) {
                    $files [$file] = $dir . $file;
                }
            }
        }
        $this->view->files = $files;
    }
}

