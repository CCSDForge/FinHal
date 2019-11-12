<?php

/**
 * Documentation
 *
 */
class DocumentationController extends Hal_Controller_Action
{

    public function indexAction ()
    {
        return $this->forward('documentation', 'page');
    }
}

