<?php

/**
 * Page a propos
 *
 */
class AboutController extends Hal_Controller_Action
{

    public function indexAction ()
    {
        $this->forward('about', 'page');
        return;
    }
}

