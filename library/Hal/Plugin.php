<?php

/**
 * Plugin permettant l'initialisation du site (portail ou collection)
 * Permet de charger la navigation, les acl et définition des différentes constantes spécifiques à l'environnement
 *
 */
class Hal_Plugin extends Zend_Controller_Plugin_Abstract
{
    /**
	 * @see Zend_Controller_Plugin_Abstract::dispatchLoopStartup()
	 */
	public function  dispatchLoopStartup (Zend_Controller_Request_Abstract $request)
	{
	    /** @var Zend_Controller_Request_Http $request */
	    $module   =  $request->getParam('_module', Hal_Site_Portail::MODULE);
        $name     =  $request->getParam('tampid', null);

        $envPortail = getenv('PORTAIL');
        $namePortail= $envPortail ? $envPortail : 'hal';

        $hostname =  $request->getServer('HTTP_HOST');
        $proto    = ($request->getServer('HTTPS') == 'on' ? 'https' : 'http');

        if (($module == Hal_Site_Collection::MODULE) && $name !== null) {
            //Initialisation du portail (dans le cas d'une collection)
            /** @var $portailCurrent Hal_Site_Portail */
            $portailCurrent = Hal_Site::exist($namePortail, Hal_Site::TYPE_PORTAIL, true);
        } else {
            // Ne doit pas pouvoir se produire!!!
            // On reviens simplement au Portail.
            $module = Hal_Site_Portail::MODULE;
            $name     = $namePortail;
        }

	    $type = RuntimeConstDef($hostname, $name, $module, $proto);

        //Initialisation du site
        /** @var $website Hal_Site_Portail or Hal_Site_Collection */
        $website = Hal_Site::exist($name, $type, true);

		if (! $website) {
			//Le site n'existe pas (ou n'est pas défini)
			if (MODULE == SPACE_PORTAIL) {
				$url = '/error/pagenotfound';
			} else {
				$url = '/error/collectionnotfound';
			}
			/** @var Zend_Controller_Action_Helper_Redirector $redirector */
			$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
    	    $redirector->gotoUrl($url);
    	    return;
		}
        // Maintenant, le site existe, on peut creer les repertoires inexistants
        foreach (array(SPACE_DATA, SPACE, DEFAULT_CONFIG_PATH, SHARED_DATA, PATH_PAGES, CACHE_PATH, CACHE_CV, DEFAULT_CACHE_PATH, PATHDOCS, PATHTEMPDOCS) as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true) || error_log("dispatchLoopStartup: Can t mk $dir");
            }
        }

		if (defined('PHPUNITTEST') && (defined('SITEID'))) {
            // Ne pas redefinir SITEID si phpunit...
            // Todo: Passer a des variables plutot qu'une constante!
            // En cours: Utiliser Hal_Site::getCurrent() -> getSid
        } else {
		    define('SITEID', $website->getSid());
        }
        //Si la collection est associée à un portail, on redirige sur le portail
        if (MODULE == SPACE_COLLECTION && (($sidPortail = Hal_Site_Collection::getAssociatedPortail(SITEID)) != false)) {
		    $portail = Hal_Site::loadSiteFromId($sidPortail);
            if ($portail === null) {
                Ccsd_Tools::panicMsg(__FILE__, __LINE__, "Pas de portail charge pour la Collection :" .  SITEID . " Portail Id associe: $sidPortail");
            }
            header('Location: ' . $portail->getUrl());
            exit;
        }


		Zend_Registry::set('website', $website);
        // On preferera passer par la classe des Site plutot que par Zend_Registry
        // Cas du Portail : $_current = Site du portail / $_currentPortail = Site du portail
        // Cas de la Collection : $_current = Site de la collection / $_currentPortail = Site du portail
        Hal_Site::setCurrent($website);
        if (($module == Hal_Site_Collection::MODULE) && $name !== null) {
            Hal_Site::setCurrentPortail($portailCurrent);
        } else {
            Hal_Site::setCurrentPortail($website);
        }

		//Navigation du site
		$navigationfile = SPACE . CONFIG . 'navigation.json';
		$config = null;
		if (is_file($navigationfile)) { //Navigation définie pour le site
			try {
				$config = new Zend_Config_Json($navigationfile, null, [ 'ignore_constants' => true ] );
			} catch (Exception $e) {
				//Erreur sur le fichier
				Ccsd_Alert::add(Ccsd_Alert::WEBSITE, "Le fichier 'navigation.json' pour le site " . $name . " semble corrompu !");
			}
		}
		if ($config == null) {
			//Pas de navigation pour le site ou pb : Récupération de la navigation par défaut
			$navigationfile = DEFAULT_CONFIG_PATH . '/navigation.json';
			if (file_exists($navigationfile)) {
				$config = new Zend_Config_Json($navigationfile, null, [ 'ignore_constants' => true ]);
			}
		}

		//Chargement des Acl
		$acl = new Hal_Acl();
		$navigationFiles = array($navigationfile, APPLICATION_PATH . '/configs/navigation.' . MODULE . '.json');
		$acl->loadFromNavigation($navigationFiles);
		//Zend_Debug::dump($acl->write());exit;
		/*if (is_file(SPACE . CONFIG . 'acl.ini')) {
        //Acl spécifique pour un site (portail ou collection)
        $acl->loadFromFile(SPACE . CONFIG . 'acl.ini');
		} else if (is_file(DEFAULT_CONFIG_PATH . '/acl.ini')) {
        //Acl par défaut
        $acl->loadFromFile(DEFAULT_CONFIG_PATH . '/acl.ini');
		} else {
        // Génération des Acl à partir du menu (menu public + menu connecté)
        $navigationFiles = array($navigationfile, APPLICATION_PATH . '/configs/navigation.' . $space . '.json');
        $acl->loadFromNavigation($navigationFiles);
        $acl->write(SPACE . CONFIG, 'acl.ini');
		} */
		Zend_Registry::set('acl', $acl);

		//echo $acl->write();exit;

		//Chargement de la navigation
		if (Hal_Auth::isLogged()) {
			$connectedConfig = new Zend_Config_Json(APPLICATION_PATH . '/configs/navigation.' . MODULE . '.json', null, [ 'ignore_constants' => true ]);
			if ($config != null) {
				$config = new Zend_Config(array_merge($config->toArray(), $connectedConfig->toArray()));
			} else {
				$config = $connectedConfig;
			}
		}

		//print_r(Hal_Auth::getRoles(true));exit;

		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
		//Initialisation du menu
		$viewRenderer->view->nav(new Ccsd_Navigation($config))
            ->setAcl($acl)
            ->setRoles(Hal_Auth::getRoles());

	}

}