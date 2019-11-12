<?php

/**
 * OAI Controler
 *
*/
class SparqlController extends Zend_Controller_Action
{

    public function init ()
    {
        /*$this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $action = strtolower($this->getRequest()->getActionName());
        if ( $action != 'xsl' ) {
            $website = new Hal_Site();
            // detection du portail
            if ( !$website->exist($action) ) {
                $this->redirect('/docs/oai/');
            }
            $website->load(true);
            Zend_Registry::set('website', $website);
            $this->indexAction();
        }*/

    }

	public function indexAction()
	{
        $this->view->layout ()->pageTitle = 'SPARQL';
        $this->view->layout ()->pageDescription = 'ENDPOINT';

        $form = new Ccsd_Form();
	    $form->addElement('select', 'default-graph-uri', [
            'label' =>  'Default Data Set Name',
	        'multiOptions' =>  [
                '0'   =>  '',
                '1'   =>  'HAL',
                '2'   =>  'AuréHAL - Structures',
                'jkl'   =>  'klmkmlk',
                ',;n'   =>  'klmkmlk',
            ]
        ]);
	    $form->addElement('textarea', 'query', [
	        'label' =>  'Query',
	        'rows' =>  '5',
            'value' =>  "SELECT DISTINCT ?g \nWHERE {\nGRAPH ?g { ?s ?p ?o }\n}"
        ]);
	    $form->addElement('select', 'format', [
	        'label' => 'Results Format',
            'multiOptions' =>  [
                'text/html'                         =>  'HTML',
                'application/vnd.ms-excel'          =>  'Spreadsheet',
                'application/sparql-results+xml'    =>  'XML',
                'application/sparql-results+json'   =>  'JSON',
                'application/javascript'            =>  'Javascript',
                'text/plain'                        =>  'NTriples',
                'application/rdf+xml'               =>  'RDF/XML',
                'text/csv'                          =>  'CSV',
                'text/tab-separated-values'         =>  'TSV',
                'graph'         =>  'Graphe',
            ]
        ]);
	    $form->setActions(true)->createSubmitButton('Run Query');

	    if ($this->getRequest()->isPost()) {

	        $url = 'http://ccsdsparqlvip.in2p3.fr:8890/sparql?';
            foreach ($this->getRequest()->getPost() as $key => $value) {
                if ('format' == $key && 'graph' == $value) {
                    $value = 'application/sparql-results+json';
                }
                $url .= $key . '=' . urlencode($value) . '&';
            }
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 60);
            curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
            $return = curl_exec($curl);

            if ($return) {
                $format = $this->getParam('format', 'text/html');

                if ($format == 'graph') {
                    $this->view->json = $return;
                } elseif ('text/html' == $format) {
                    $this->view->data = $return;
                } else {
                    $this->_helper->layout()->disableLayout();
                    $this->_helper->viewRenderer->setNoRender(true);
                    header('Content-Type: ' . $format);
                    echo $return;
                }
                return true;
            }
        }



	    $this->view->form = $form;
	}

	public function testAction()
    {
        $this->_helper->layout()->disableLayout();
    }

}

