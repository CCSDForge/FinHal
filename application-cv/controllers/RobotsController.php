<?php

/**
 * Class RobotsController
 * robots.txt
 */
class RobotsController extends Zend_Controller_Action
{

    public function indexAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Type: text/plain; charset: utf-8');
        echo "# CV robots.txt\n";
        echo "User-Agent: *\n";
        if (APPLICATION_ENV == ENV_PROD) {

            $pathsToDissallow = ['/user/*', '/error/*', '/*/primaryDomain_s/*', '/*/keyword_s/*'];

            foreach ($pathsToDissallow as $path) {
                echo 'Disallow: ' . $path . PHP_EOL;
            }

            echo "# Sitemap\n";
            echo "Sitemap: " . $this->getRequest()->getScheme() . '://' . $_SERVER['HTTP_HOST'] . "/robots/sitemap\n";
        } else {
            echo "Disallow: *\n";
        }

    }

    public function sitemapAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Type: text/xml; charset: utf-8');
        $sitemap = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        $filename = 'cvs.phps';
        if (Hal_Cache::exist($filename, 86400)) {
            $cvs = unserialize(Hal_Cache::get($filename));
        } else {
            $cvs = Hal_Cv::liste();
            Hal_Cache::save($filename, serialize($cvs));
        }
        foreach ($cvs as $cv) {
            $sitemap .= "<url>" . PHP_EOL;
            $sitemap .= "<loc>" . $this->getRequest()->getScheme() . '://' . $_SERVER['HTTP_HOST'] . '/' . $cv['URI'] . "</loc>" . PHP_EOL;
            $sitemap .= "<lastmod>" . date('Y-m-d') . "</lastmod>" . PHP_EOL;
            $sitemap .= "<changefreq>daily</changefreq>" . PHP_EOL;
            $sitemap .= "<priority>1</priority>" . PHP_EOL;
            $sitemap .= "</url>" . PHP_EOL;
        }
        $sitemap .= "</urlset>" . PHP_EOL;
        echo $sitemap;
    }

}

