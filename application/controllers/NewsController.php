<?php

class NewsController extends Hal_Controller_Action
{
	
	public function indexAction()
	{
		if (Hal_Auth::isAdministrator() || Hal_Auth::isTamponneur()) {
        	$this->view->canEdit = true;
		}
		
		$news = new Hal_News(); 
		$this->view->news = $news->getListNews();
		
	}
	
	public function widgetAction()
	{
				
	}
	
}