<?php

class Hal_Form_Decorator_Identifiant extends Ccsd_Form_Decorator_Multi
{
    protected function render_default ($element, $class , $style)
    {
    	$xhtml = "";

        $xhtml .= "<button type='button' class='$class'  style='$style' data-toggle='tooltip' data-placement='right' ";

        if ($element->isClone()) {
            $xhtml .= "' onclick='" . $this->add . "(this, \"" . $element->getFullyQualifiedName() . "\");'";
            $xhtml .= "' ><i class='glyphicon glyphicon-plus'></i>";
        } else {
            $xhtml .= " data-original-title='" . Ccsd_Form::getDefaultTranslator()->translate("Supprimer cet identifiant") . "' onclick='" . $this->delete . "(this);'";
            $xhtml .= "' ><i class='glyphicon glyphicon-trash'></i>";
        }
    
        $xhtml .= "</button>";
        
        return $xhtml;
    }
}