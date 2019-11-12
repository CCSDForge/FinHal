<?php

class Hal_Form_Element_Identifiant extends Ccsd_Form_Element_MultiTextSimpleLang {
    public $pathDir = __DIR__ ;
    public $relPublicDirPath =  "../../../../public";

    /**
     * @return string
     */
	public function getPrefix ()
	{ 
		return 'identifiant/';
	}

    /**
     *
     */
    public function loadDefaultDecorators ()
    {
        parent::loadDefaultDecorators();
 
        /* @var $decorator Ccsd_Form_Decorator_Group */
        $decorator  = $this->getDecorator('Group');
        $decorators = $decorator->getDecorators();
        $decorators[3] = array ('decorator' => 'Identifiers', 'options' => array ('class' => 'btn btn-sm btn-default', 'style' => 'border-radius:0; height: 30px; padding-top:0; padding-bottom: 0;'));
        $decorators[6] = array ('decorator' => 'Identifiant', 'options' => array ('class' => 'btn btn-sm btn-primary', 'style' => 'border-top-left-radius:0; border-bottom-left-radius:0; height: 30px; padding-top:0; padding-bottom: 0;'));
        $this->getDecorator('Group')->setDecorators ($decorators);
    }

    /**
     * @param Zend_Form_Decorator_Abstract $decorator
     * @param string $content
     * @return string
     */
    public function renderValues ($decorator, $content = '')
    {
    	$this->_unprocessedValues = $this->_value;
    	$key   = key($this->_languages);
    	
    	if (is_array($this->_value) && !empty($this->_value)) {
	    	foreach ($this->_value as $lang => $v) {
	    		$this->_value = $v;
	    		$clone = clone $this;
	    		$clone->_isClone = false;
	    		$clone->_indice = $lang;
	    		$clone->setAttrib('lang', $lang);
	    		$decorator->indice = $lang;
	    		$decorator->setElement($clone);
	    		$content = $decorator->render($content);
	    		$this->setJavascript($clone->getJavascript());
	    		unset ($clone);
	    	}
	    	
	    	$key   = key(array_diff_key($this->_languages, $this->_unprocessedValues ? $this->_unprocessedValues : array ()));
    	}
    	
    	$this->_value = "";
    	$clone = clone $this;
    	$clone->_isClone = true;
    	$clone->_indice  = $key;
    	$clone->setAttrib('lang', $key);    	 
    	$clone->_stillChoice = ($key === 0 || $key === '0') ? true : (bool)$key;
    	$decorator->indice = $key;
    	$decorator->setElement($clone);
    	$content = $decorator->render($content);
    	$this->setJavascript($clone->getJavascript());
    	$this->_value = $this->_unprocessedValues;
    	
    	return $content;
    }

    /**
     * @param string $value
     * @param mixed $context | Unused: for declaration compatibility
     * @return bool
     */
    public function isValid ($value, $context=null) {
    	        
    	if ( $value == null || !is_array($value) ) {
            $value = array();
        }
        $this->_value = array_filter($value);
    	$valid = true;
    	if (!empty ($this->_value)) {
	    	foreach ($this->_value as $type => $val) {
	    		
	    		$validator = "Ccsd_Form_Validate_Is" . strtolower($type);
	    		
	    		if (class_exists ($validator)) {
	    		    /** @var Zend_Validate $validator */
	    			$validator = new $validator ();
	    		
	    			if (!$validator->isValid($val)) {
	    				$valid = false;
	    				$this->setMessages($this->getMessages() + $validator->getMessages());
	    				$this->markAsError();
	    			}
	    		
	    		}
	    	}
    	}
    	
    	return $valid && parent::isValid($this->_value);
    }
   
    
}