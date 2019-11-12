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
        echo '# AUREHAL robots.txt' . PHP_EOL;
        echo '# If you want to download lots of metadata, please use our API at https://api.archives-ouvertes.fr/' . PHP_EOL;
        echo '# The API is far more efficient for metadata harvesting.' . PHP_EOL;
        echo '# To learn more, please contact hal.support@ccsd.cnrs.fr' . PHP_EOL;

        echo "User-Agent: *\n";
        if (APPLICATION_ENV == ENV_PROD) {
            $pathsToDissallow = [
                '/*/browse*',
                '/login',
                '/user',
                '/error'
            ];

            foreach ($pathsToDissallow as $path) {
                echo 'Disallow: ' . $path . PHP_EOL;
            }
            echo "# Sitemap\n";
            echo "Sitemap: " . $this->getRequest()->getScheme() . '://' . $_SERVER['HTTP_HOST'] . PREFIX_URL . "robots/sitemap\n";
        } else {
            echo "Disallow: *\n";
        }

    }

    public function sitemapAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        header('Content-Type: text/xml; charset: utf-8');
        $sitemap = SPACE . 'public/sitemap/sitemap.xml';
        if (is_file($sitemap)) {
            include $sitemap;
        }
    }

}

