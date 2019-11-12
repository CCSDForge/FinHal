<?php
class IndexController extends Zend_Controller_Action {
	public function indexAction() {
		$this->redirect ( '/docs' );
	}
	/**
	 * robots.txt
	 */
	public function robotsAction() {
	    $this->forward('index', 'robots');
	}
}