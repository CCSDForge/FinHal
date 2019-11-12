<?php

class Aurehal_View_Helper_Documents extends Zend_View_Helper_Abstract {
		
	public function documents($id, $class) {
		
		/*@var Zend_Controller_Action_Helper_ViewRenderer $viewRenderer*/
		$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
		$this->setView($viewRenderer->view);

		$documents = $relations = array ();
		
		$documents[null] = array ();
		
		foreach ( $class::getRelatedDocid($id) as $docid ) {
			if ( ($document = Hal_Document::find($docid)) != FALSE ) {
				 
				$structures = array ();
				
                if ($class == "Ccsd_Referentiels_Author") {
                    $doc_structures = $document->getStructuresAuthor($id);
                } else {
                    $doc_structures = $document->getStructures();
                }
				 
				if (is_array ($doc_structures) && !empty ($doc_structures)) {
					foreach ($doc_structures as $structure) {
						$structures[$structure->getStructid()]  = $structure->getStructname();
						$relations[$structure->getStructid()] = array('name' => $structure->getStructname(), 'type' => $structure->getTypestruct());
						$documents[$structure->getStructid()][] = array(
							'docid'     => $docid,
							'citation'  => $document->getCitation('full'),
							'format'    => $document->getFormat(),
							'ref'		=> $document->getId(true),
							'ref_url'   => $document->getUri(true),
							'structids' => $structures
						);
					}
				} else {
					$documents[null][] = array(
						'docid'     => $docid,
						'citation'  => $document->getCitation('full'),
						'format'    => $document->getFormat(),
						'ref'		=> $document->getId(true),
						'ref_url'   => $document->getUri(true),
						'structids' => array()
					);
				}
			}
		}
		
		$this->view->id        = $id;
		$this->view->documents = $documents;
		$this->view->relations = $relations;
		
		echo $this->view->render ('partials/documents.phtml');
	}
	
}