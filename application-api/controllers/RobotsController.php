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
        echo "# API robots.txt\n";
        echo "User-Agent: *\n";
        if (APPLICATION_ENV == ENV_PROD) {

            $pathsToDissallow = ['/search/', '/*/search/', '/ref/', '/oai/', '/sword/'];

            foreach ($pathsToDissallow as $path) {
                echo 'Disallow: ' . $path . PHP_EOL;
            }

        } else {
            echo "Disallow: *\n";
        }
    }
}

