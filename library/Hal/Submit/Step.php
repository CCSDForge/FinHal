<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 07/02/17
 * Time: 14:10
 */
abstract class Hal_Submit_Step
{
    /** @var string  */
    protected $_name = "";
    /** @var bool  */
    protected $_validity;
    /** @var array  */
    protected $_errors;
    /** @var int  */
    protected $_mode;

    public function __construct($mode = Hal_Settings::SUBMIT_MODE_SIMPLE, $validity = false, $errors = [])
    {
        $this->_mode = $mode;
        $this->_validity = $validity;
        $this->_errors = $errors;
    }

    /**
     * @param Hal_View $view
     * @param Hal_Document $document
     * @param $type
     * @param bool $verifValidity
     * @return mixed
     */
    abstract public function initView(Hal_View &$view, Hal_Document &$document, $type, $verifValidity = false);

    /**
     * @param Hal_Document $document
     * @param string $type
     */
    public function updateValidity(Hal_Document &$document, $type)
    {
        $this->_validity = false;
        $this->_errors = [];

        try{
            $this->_validity = Hal_Document_Validity::isValid($document, $this->_name);
        } catch (Hal_Document_Exception $e) {
            $this->_errors = $e->getErrors()[$this->_name];
        }
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->_name;
    }
    /**
     * @param Hal_View $view
     * @param Hal_Document $document
     * @param $type
     * @param bool $verifValidity
     */
    public function getHtml($view, Hal_Document &$document, $type, $verifValidity = false)
    {
        $this->initView($view, $document, $type, $verifValidity);
        return $view->render(SubmitController::SUBMIT_CONTROLER . '/step-' . $this->_name . '/index.phtml');
    }
    /**
     * @return bool
     */
    public function getValidity()
    {
        return $this->_validity;
    }

    public function setValidity($validity)
    {
        $this->_validity = $validity;
    }

    public function getErrors()
    {
        return $this->_errors;
    }

    public function setErrors($errors)
    {
        $this->_errors = $errors;
    }

    public function getMode()
    {
        return $this->_mode;
    }

    public function setMode($mode)
    {
        $this->_mode = $mode;
    }

    /**
     * @param Hal_Document $document
     * @param $SubmitType
     * @param $params
     */
    public function submit(Hal_Document &$document, $SubmitType, $params) {
        // Defined only for Recap/Meta subclass
    }
}