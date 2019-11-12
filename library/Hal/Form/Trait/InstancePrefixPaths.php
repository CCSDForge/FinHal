<?php

trait Hal_Form_Trait_InstancePrefixPaths {
     
    public function loadInstancePrefixPaths ()
    {
        if ($this->_context == "HAL") {
            $this->addPrefixPath('Hal_Form_Element','Hal/Form/Element', Zend_Form::ELEMENT);
            $this->addPrefixPath('Hal_Form_Decorator', 'Hal/Form/Decorator', Zend_Form::DECORATOR);
        }
    }
    
}