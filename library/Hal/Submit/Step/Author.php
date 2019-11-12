<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 07/02/17
 * Time: 14:13
 */
class Hal_Submit_Step_Author extends Hal_Submit_Step
{
    /**
     * @var string
     */
    protected $_name = "author";

    /**
     * @var array
     */
    protected $_authorsOrder;

    /**
     * Initiallisation de l'étape Auteur
     * @param Hal_View $view
     * @param Hal_Document $document
     * @param string $type
     * @param bool $verifValidity
     */
    public function initView(Hal_View &$view, Hal_Document &$document, $type, $verifValidity = false)
    {
        if (isset($this->_authorsOrder)) {
            $document->changeAuthorsOrder($this->_authorsOrder);
            unset($this->_authorsOrder);
        }

        $view->authormode = $this->_mode;
        $view->controller = SubmitController::SUBMIT_CONTROLER;

        $view->authors = $document->getAuthors();
        $view->structures = $document->getStructures();

        $view->valid = $this->_validity;
        if (!$this->_validity) {
            $view->errors = $this->_errors;
        }
    }

    /**
     * @param array $autOrder
     */
    public function setAuthorOrder($autOrder)
    {
        $this->_authorsOrder = $autOrder;
    }

}