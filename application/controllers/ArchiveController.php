<?php

/**
 * Class ArchiveController
 *
 * Archivage au Cines
 */
class ArchiveController extends Hal_Controller_Action {
	public function indexAction() {
		$request = $this->getRequest ();
		if ($request->isPost ()) {
			$params = $request->getParams ();

			foreach ( $params as $param => $value ) {
				$this->view->$param = $value;
			}

			// Récupération de la liste des documents
			if (isset ( $params ['docid'] ) && $params ['docid'] != 0) {
				$this->view->docids = array (
						( int ) $params ['docid']
				);
			} else {
				$this->view->docids = Ccsd_Archive::getListe ( $params ['start'], $params ['end'], $params ['status'] );
			}
		} else {
			$this->view->start = date ( 'Y-m-d', strtotime ( '-1 day' ) );
			$this->view->end = date ( 'Y-m-d' );
			$this->view->status = array (
					Ccsd_Archive::ARCHIVE_REJETEE
			);
		}

		$this->view->listStatus = Ccsd_Archive::getEtats ();
	}
	public function ajaxdetailsAction() {
		if ($this->getRequest ()->isPost ()) {
			$docid = $this->getParam ( 'docid', 0 );
			$archive = new Ccsd_Archive ( $docid );

			$this->view->document = $archive->getDocument ();
			$this->view->status = $archive->getSTATUT ();
			$this->view->history = $archive->historique ();
		}
		$this->_helper->layout ()->disableLayout ();
		$this->render ( 'details' );
	}
	
	public function ajaxarchivedocAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
		if ($this->getRequest ()->isPost ()) {
			$docid = $this->getParam ( 'docid', 0 );
			$archive = new Ccsd_Archive ( $docid );
			$res = $archive->envoiArchivage ();

			if ($res == false) {
				$message = "Echec de l'archivage du docid : " . intval ( $docid );
				$style = 'alert-danger';
			} else {
				$message = "Archivage OK du docid : " . intval ( $docid );
				$style = 'alert-success';
			}

			echo Zend_Json::encode ( array (
					'message' => $message,
					'style' => $style
			) );
		}
	}
}

