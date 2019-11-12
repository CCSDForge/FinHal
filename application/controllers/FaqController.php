<?php

/**
 * FAQ
 *
 */
class FaqController extends Hal_Controller_Action
{

    public function indexAction ()
    {
        return $this->forward('faq', 'page');
    }
}

