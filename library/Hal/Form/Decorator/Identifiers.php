<?php

class Hal_Form_Decorator_Identifiers extends Ccsd_Form_Decorator_Lang
{
    
    protected function renderSelectedChoice ($element)
    {
    	$xhtml = "";
    
    	$function = $this->init . "(this, '" . $element->getFullyQualifiedName(). "');";
    
    	$values = $element->getUnprocessedValues();
    	$languages = $element->getLanguages();
    
    	if (!isset ($values) || !is_array($values) || empty($values)) {
    		$values = array (key($languages) => "");
    	}
    
    	foreach ($languages as $code => $libelle) {
    		$xhtml .= "<li";
    
    		if (!$element->isClone() || in_array($code, array_keys($values)) && $this->indice != $code) {
    			$xhtml .= " class='disabled'";
    		}
    		 
    		$xhtml .= ">";
    		$xhtml .= "<a val='$code' href='javascript:void(0);' onclick=\"" . $function . "\">$libelle</a></li>";
    	}
    
    	$xhtml .= "</ul>";
    
    	return $xhtml;
    }

}