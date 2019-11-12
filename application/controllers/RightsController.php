<?php

/**
 * Class RightsController
 *
 * Ce controller affiche le menu Privilege sur sa page d'index.
 */
class RightsController extends Hal_Controller_Action
{
    public function indexAction()
    {
        $this->renderScript('index/submenu.phtml');
    }

}