<?php

/**
 * Class RobotsController
 * robots.txt
 */
class RobotsController extends Hal_Controller_Action
{
    /**
     * La page Robot.txt
     */
    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->view->sitemapUrl = $this->getSiteMapUrl();
        $this->getResponse()->setHeader('Content-Type', 'text/plain charset: utf-8');
    }

    /**
     * @return string : une Url
     */
    public function getSiteMapUrl()
    {
        return $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . PREFIX_URL . "robots/sitemap\n";
    }

    /**
     * Retourne la page de siteMap
     */
    public function sitemapAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=UTF-8');
        $sitemap = SPACE . 'public/sitemap/sitemap.xml';
        if (is_file($sitemap)) {
            include $sitemap;
        }
    }

}

