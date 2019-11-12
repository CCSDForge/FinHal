<?php
class DocsController extends Zend_Controller_Action {
	const SCHEMA_CACHE_LIFE_TIME = 86400; // 24H

	public function indexAction() {
		return;
	}
	public function refAction() {
		$resource = $this->_getParam ( 'resource' );
		switch ($resource) {

			case 'author' :
				$this->view->layout ()->pageTitle = 'Référentiel ' . $this->view->translate('ref_' . $resource);
				$this->view->layout ()->pageDescription = 'ref_' . $resource . '_description';
				$core = 'ref_' . $resource;
				break;
			case 'domain' :
				$this->view->layout ()->pageTitle = 'Référentiel ' . $this->view->translate('ref_' . $resource);
				$this->view->layout ()->pageDescription = 'ref_' . $resource . '_description';
				$core = 'ref_' . $resource;
				break;
			case 'journal' :
				$this->view->layout ()->pageTitle = 'Référentiel ' . $this->view->translate('ref_' . $resource);
				$this->view->layout ()->pageDescription = 'ref_' . $resource . '_description';
				$core = 'ref_' . $resource;
				break;
			case 'metadatalist' :
				$this->view->layout ()->pageTitle = 'Référentiel ' . $this->view->translate('ref_' . $resource);
				$this->view->layout ()->pageDescription = 'ref_' . $resource . '_description';
				$core = 'ref_' . $resource;
				break;
			case 'structure' :
			$this->view->layout ()->pageTitle = 'Référentiel ' . $this->view->translate('ref_' . $resource);
				$this->view->layout ()->pageDescription = 'ref_' . $resource . '_description';
				$core = 'ref_' . $resource;
				break;

			case 'europeanproject' :
				$this->view->layout ()->pageTitle = 'Référentiel ' . $this->view->translate('ref_' . $resource);
				$this->view->layout ()->pageDescription = 'ref_' . $resource . '_description';
				$core = 'ref_projeurop';
				break;

			case 'anrproject' :
				$this->view->layout ()->pageTitle = 'Référentiel ' . $this->view->translate('ref_' . $resource);
				$this->view->layout ()->pageDescription = 'ref_' . $resource . '_description';
				$core = 'ref_projanr';
				break;

			// not solr
			case 'authorstructure' :
				$this->view->layout ()->pageTitle = 'Référentiel';
				$this->view->layout ()->pageDescription = 'ref_' . $resource;
				$this->view->resourceName = $resource;
				return $this->render ( 'authorstructure' );
				break;
            // not solr
            case 'affiliation' :
                $this->view->layout ()->pageTitle = 'Référentiel';
                $this->view->layout ()->pageDescription = 'ref_' . $resource;
                $this->view->resourceName = $resource;
                return $this->render ( 'affiliation' );
                break;

			case 'doctype' :
				$this->view->layout ()->pageTitle = 'Référentiel';
				$this->view->layout ()->pageDescription = 'ref_' . $resource;
				$this->view->resourceName = $resource;
				return $this->render ( 'doctype' );
				break;

			case 'instance' :
				$this->view->layout ()->pageTitle = 'Référentiel';
				$this->view->layout ()->pageDescription = 'ref_' . $resource;
				$this->view->resourceName = $resource;
				return $this->render ( 'instance' );
				break;

			case 'metadata' :
				$this->view->layout ()->pageTitle = 'Référentiel';
				$this->view->resourceName = $resource;
				$this->view->layout ()->pageDescription = 'ref_' . $resource;
				return $this->render ( 'metadata' );
				break;

			default :
				$this->view->layout ()->pageTitle = 'Référentiels';
				return $this->render ( 'ref' );
				break;
		}


		$this->view->resourceName = $resource;
		$this->view->apiUrl = 'http://' . $_SERVER ['SERVER_NAME'] . '/ref/' . $resource . '/';

		$schema = $this->getRequest()->getParam('schema');


		if ($schema != null) {
			$cachedData = null;
			$cachedData = $this->getSchemaDoc ( $core );
			$this->view->schemaFields = $cachedData ['schemaFields'];
			$this->view->schemaDynamicFields = $cachedData ['schemaDynamicFields'];
			$this->view->schemaCopyFields = $cachedData ['schemaCopyFields'];
			$this->view->schemaFieldTypes = $cachedData ['schemaFieldTypes'];
			return $this->render ( 'schema' );
		}


		$this->render ( 'ref' );
	}
	private function getSchemaDoc($core) {
		$cacheName = Ccsd_Cache::makeCacheFileName ( '', '', $core );

		$cachedData = null;

 		if (Hal_Cache::exist ( $cacheName, self::SCHEMA_CACHE_LIFE_TIME )) {
			$cachedData = Hal_Cache::get ( $cacheName );
			if (false != $cachedData) {
				$cachedData = unserialize ( $cachedData );
			}
		}

		if ($cachedData == null) {

			$sc = new Ccsd_Search_Solr_Schema ( array (
					'env' => APPLICATION_ENV,
					'core' => $core,
					'handler' => 'schema'
			) );



			$sc->getSchemaFieldTypes ();

			$sc->getSchemaFields ()->getSchemaDynamicFields ()->getSchemaCopyFields ();

			$cachedData ['schemaFields'] = $sc->getFields ();
			$cachedData ['schemaDynamicFields'] = $sc->getDynamicFields ();
			$cachedData ['schemaCopyFields'] = $sc->getCopyFields ();
			$cachedData ['schemaFieldTypes'] = $sc->getFieldTypes();

			Hal_Cache::save ( $cacheName, serialize ( $cachedData ) );
		}

		return $cachedData;
	}

	/**
	 * Aide API search
	 */
	public function searchAction() {
		$this->view->layout ()->pageTitle = 'API HAL';
		$this->view->layout ()->pageDescription = 'API de recherche HAL';



		$this->view->apiUrl = 'http://' . $_SERVER ['SERVER_NAME'] . '/search/';
		$this->view->resourceName = 'hal';

		$schema = $this->getRequest()->getParam('schema');

		if ($schema != null) {
			$cachedData = null;
			$cachedData = $this->getSchemaDoc ( 'hal' );
			$this->view->schemaFields = $cachedData ['schemaFields'];
			$this->view->schemaDynamicFields = $cachedData ['schemaDynamicFields'];
			$this->view->schemaCopyFields = $cachedData ['schemaCopyFields'];
			$this->view->schemaFieldTypes = $cachedData ['schemaFieldTypes'];
			return $this->render ( 'schema' );
		}





		return $this->render ( 'ref' );
	}
	public function swordAction() {
		$this->view->layout ()->pageTitle = 'Import SWORD';
	}
	public function soapAction() {
		$this->view->layout ()->pageTitle = 'Import SOAP';
	}
	public function oaiAction() {
		$this->view->layout ()->pageTitle = 'Serveur OAI-PMH';
	}
}