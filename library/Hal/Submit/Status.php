<?php

/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 31/01/2017
 * Time: 11:01
 */
class Hal_Submit_Status
{
    const VALIDITY = 'validity';
    const TYPDOCS = 'typdocs';
    const CURRENTTYPE = 'currenttype';

    /**
     * Statut des étapes du dépôt
     * @var Hal_Submit_Step[]
     */
    protected $_steps = [];

    /**
     * Types de document acceptés pour ce dépot
     */
    protected $_typdocs = [];

    /**
     * Type du document courant
     */
    protected $_currenttype = "";

    /**
     * Type de dépot
     */
    protected $_submitType;

    /**
     * Etape courante
     * @var mixed|null
     */
    protected $_currentStep = null;

    public function __construct($submitType = Hal_Settings::SUBMIT_INIT, $mode = Hal_Settings::SUBMIT_MODE_SIMPLE, $valid = false)
    {
        foreach (Hal_Settings::getSubmissionsSteps() as $s) {

            $stepClass = "Hal_Submit_Step_" . ucfirst($s);

            if (class_exists($stepClass)) {
                $this->_steps[$s] = new $stepClass($mode, $valid);
            }
        }
        $this->_currentStep = $this->getStepsList()[0];

        $this->_submitType = $submitType;
    }

    /**
     * @param string $doctype
     * @param string $extension
     * @return array
     */
    public function getTypdocs($doctype, $extension = "")
    {
        if (in_array($this->_submitType, [Hal_Settings::SUBMIT_UPDATE, Hal_Settings::SUBMIT_REPLACE, Hal_Settings::SUBMIT_ADDFILE, Hal_Settings::SUBMIT_ADDANNEX, Hal_Settings::SUBMIT_MODERATE])) {
            return array_diff(Hal_Settings::getTypdocsAvailable(), Hal_Settings::getTypdocAssociated($doctype));
        } else {
            return Hal_Settings::getTypdocsFiltered($extension);
        }
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return array_map(function($value) {/** @var Hal_Submit_Step $value */return $value->getErrors();}, $this->_steps);
    }

    /**
     * Retourne la liste des étapes connues
     * @return array
     */
    public function getStepsList()
    {
        return array_keys($this->_steps);
    }

    /**
     * Retourne un tableau avec la validité des étapes
     * @return bool[]
     */
    public function getStepsValidity()
    {
        return array_map(function($value) {/** @var Hal_Submit_Step $value */return $value->getValidity();}, $this->_steps);
    }

    /**
     * Retourne les étapes Hal_Submit_Step
     * @return Hal_Submit_Step[]
     */
    public function getSteps()
    {
        return $this->_steps;
    }

    /**
     * Modification d'une étape du dépôt
     * @param string $step
     * @param bool $validity
     * @param array $errors
     */
    public function setStep($step, $validity, $errors = [])
    {
        if ($this->existStep($step)) {
            $this->_steps[$step]->setValidity($validity);
            $this->_steps[$step]->setErrors($errors);
        }
    }

    /**
     * Indique si une étape existe
     * @param string $step
     * @return bool
     */
    public function existStep($step)
    {
        return in_array($step, $this->getStepsList());
    }

    /**
     * Retourne l'étape courante
     * @return string
     */
    public function getCurrentStepName()
    {
        return $this->_currentStep;
    }

    public function getCurrentStep()
    {
        return $this->getStep($this->_currentStep);
    }

    public function getStep($step)
    {
        return $this->_steps[$step];
    }

    /**
     * Modification de l'étape courante
     * @param string $step
     */
    public function setCurrentStep($step)
    {
        if (!$this->existStep($step)) {
            $step = $this->getStepsList()[0];
        }
        $this->_currentStep = $step;
    }

    /**
     * Indique si toutes les étapes sont valides
     * @return bool
     */
    public function isValid()
    {
        $res = true;
        foreach ($this->getStepsValidity() as $step => $valid) {
            if ($step != Hal_Settings::SUBMIT_STEP_RECAP) {
                $res &= $valid;
            }
        }
        return $res;
    }

    public function setCurrentType($type)
    {
        $this->_currenttype = $type;
    }

    /**
     * Mise à Jour de la validité des étapes
     * @param Hal_Document $document
     * @param string $type
     * @param array $steps
     * @return $this
     */
    public function update (Hal_Document &$document, $type, $steps = null)
    {
        if ($steps != null) {
            if (! is_array($steps)) {
                $steps = [$steps];
            }

            // Mise à jour des étapes
            foreach ($steps as $step) {
                if ($step != Hal_Settings::SUBMIT_STEP_RECAP) {
                    $this->_steps[$step]->updateValidity($document, $type);
                }
            }

            // Validité de l'étape récap (dépendante des validités des autres étapes)
            if (in_array(Hal_Settings::SUBMIT_STEP_RECAP, $steps)) {
                $valid = true;
                foreach ($this->_steps as $step) {
                    if (!$step->getValidity() && $step->getName() != Hal_Settings::SUBMIT_STEP_RECAP) {
                        $valid = false;
                        break;
                    }
                }
                $this->_steps[Hal_Settings::SUBMIT_STEP_RECAP]->setValidity($valid);
            }
        }

        $this->_currenttype = $document->getTypDoc();

        if ($document->getDefaultFile()) {
            $this->_typdocs = $this->getTypdocs($this->_currenttype, $document->getDefaultFile()->getExtension());
        } else {
            $this->_typdocs = [];
        }

        return $this;
    }

    /**
     *
     */
    public function toArray() {
        $return = array();

        $return[self::VALIDITY] = $this->getStepsValidity();
        $return[self::TYPDOCS] = $this->_typdocs;
        $return[self::CURRENTTYPE] = $this->_currenttype;

        return $return;
    }

    public function getSubmitType()
    {
        return $this->_submitType;
    }

    public function setSubmitType($type)
    {
        $this->_submitType = $type;
    }

}