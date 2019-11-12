<?php

class Hal_News extends Ccsd_News
{
	
	public function __construct()
	{
		$this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
		$this->_sid = SITEID;
		$this->_languages = Zend_Registry::get('languages');
		$this->_dirLangFiles = SPACE . 'languages/';
		$this->_sidField = 'SID';
	}

    public function getFeeds($online = true, $limit = 0)
    {
        $feeds = array();

        foreach($this->getListNews($online, 0, $limit) as $news) {
            $date = new Zend_Date();
            $date->set($news['DATE_POST']);
            $elem = array(
                'title'        => Zend_Registry::get('Zend_Translate')->translate($news['TITLE']),
                'description'  => Zend_Registry::get('Zend_Translate')->isTranslated($news['CONTENT']) ? Zend_Registry::get('Zend_Translate')->translate($news['CONTENT']) : '',
                'date'         => $date,
                'link'         => $news['LINK']
            );
            $feeds[] = $elem;
        }
        return $feeds;
    }

    public function getForm($newsid = 0)
    {
        $form = parent::getForm($newsid);
        $form->removeDecorator('Form');

        return $form;

    }
}