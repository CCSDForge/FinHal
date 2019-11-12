<?php

class Hal_Ini extends Zend_Config_Ini {  

    /**
     * Whether to make diff between sections
     *
     * @var boolean
     */
    protected $_makeDiff = false;
    
    /**
     * @see Zend_Config_Ini __contruct()
     */
    public function __construct($filename, $section = null, $options = false)
    {
        if (empty($filename)) {
            /**
             * @see Zend_Config_Exception
             */
            require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception('Filename is not set');
        }
    
        $allowModifications = true;
        
        if (is_array($options)) {
            if (isset($options['nestSeparator'])) {
                $this->_nestSeparator = (string) $options['nestSeparator'];
            }
            if (isset($options['skipExtends'])) {
                $this->_skipExtends = (bool) $options['skipExtends'];
            }
            /*if (isset($options['makeDiff'])) {
                $this->_makeDiff = (bool) $options['makeDiff'];
            }*/
        }

        $iniArray = $this->_loadIniFile($filename);

        if (null === $section) {
            // Load entire file
            $dataArray = array();
            foreach ($iniArray as $sectionName => $sectionData) {
                if(!is_array($sectionData)) {
                    $dataArray = $this->_arrayMergeRecursive($dataArray, $this->_processKey(array(), $sectionName, $sectionData));
                } else {
                    $dataArray[$sectionName] = $this->_processSection($iniArray, $sectionName);
                }
            }
            Zend_Config::__construct($dataArray, $allowModifications);
        } else {
            // Load one or more sections
            if (!is_array($section)) {
                $section = array($section);
            }
            $dataArray = array();

            /*if (!in_array ('metas', (array_intersect($section, array_keys($iniArray))))) {
            	array_unshift($section, 'metas');
            }*/

            foreach ($section as $sectionName) {
                if (!isset($iniArray[$sectionName])) {
                    continue;
                }
                $dataArray = $this->_arrayMergeRecursive($dataArray, $this->_processSection($iniArray, $sectionName));
            }

        	if (empty ($dataArray) && isset ($options['section_default']) && isset ($iniArray[$options['section_default']])) {
                $dataArray = $this->_arrayMergeRecursive($this->_processSection($iniArray, $options['section_default']), $dataArray);
                $section = array($iniArray[$options['section_default']]);
            } 
            
            Zend_Config::__construct($dataArray, $allowModifications);
        }
        
        /*if ($this->_makeDiff)
            $this->_arrayDiffRecursive($dataArray, $this->_arrayMergeRecursive($this->_processSection($iniArray, 'metas'), array()));*/
    
        $this->_loadedSection = $section;
        
        
    }
    
    /**
     * Fusionne plusieurs sources Ini entre elles.
     * 
     * Le paramètre $options doit être un tableau.
     * Pour définir ce tableau, vous devez spécifier comme clé du tableau, le 
     * chemin du fichier, et comme valeur à cette clé, les sections à charger.
     * 
     * Par exemple:
     * 
     * $src = array(
     *     'filepath1' => array(
     *                        section_name,
     *                        ...,
     *                    ),
     *     'filepath2' => array(
     *                        section_name,
     *                        ...,
     *                    ),
     * );
     * 
     * Le paramètre $options peut être fournit comme un booléen ou un tableau.
     * Si il est fournit comme un booléen, il fixe l'option $allowModifications 
     * de Zend_Config. Si il est fournit comme un tableau, il y a trois 
     * directives de configuration qui peuvent être fixées. Par exemple :
     *
     * $options = array(
     *     'allowModifications' => false,
     *     'nestSeparator'      => ':',
     *     'skipExtends'        => false,
     * );
     *
     * @param  array $src
     * @param  boolean|array $options
     * @throws Zend_Config_Exception
     * @return array
     */
    public static function file_merge ( $src = array (), $options = true )
    {
        if (!is_array ($src))
            throw new Zend_Config_Exception("This parameter '$src' must be an array, " . get_class($src) . ' given');

        $oConf = self::_firstProcess(key ($src), current($src), $options);
             
        array_shift($src);

        foreach ($src as $path => $sections)
            if (file_exists ($path))
                $oConf->merge( self::_process($path, $sections, $options) );



        return $oConf->toArray();
    }

    /**
     * Création d'un objet Hal_Ini (le premier de la liste)
     * 
     * @param  string        $filename
     * @param  mixed         $section
     * @param  boolean|array $options
     * @return Hal_Ini
     */
    protected static function _firstProcess ($filename, $section = null, $options = true)
    {
        if (empty ($section))
            $section = null;
        
        return new self ($filename, $section, $options);
    }
    
    /**
     * Création d'un objet Hal_Ini
     * 
     * @param  string        $filename
     * @param  mixed         $section
     * @param  boolean|array $options
     * @return Hal_Ini
     */
    protected static function _process ($filename, $section = null, $options = true)
    {
        if (empty ($section))
            $section = null;
        
        return new self($filename, $section, $options);
    }
    
    /**
     * Merge two arrays recursively, overwriting keys of the same name
     * in $firstArray with the value in $secondArray.
     *
     * @param  mixed $firstArray  First array
     * @param  mixed $secondArray Second array to merge into first array
     * @return array
     */
    protected function _arrayDiffRecursive($firstArray, $secondArray)
    {
        Ccsd_Tools::debug ($firstArray, $secondArray);
        
        
        
        if (is_array($firstArray) && is_array($secondArray)) {
            foreach ($secondArray as $key => $value) {
                if (isset($firstArray[$key])) {
                    $firstArray[$key] = $this->_arrayMergeRecursive($firstArray[$key], $value);
                } else {
                    if($key === 0) {
                        $firstArray= array(0=>$this->_arrayMergeRecursive($firstArray, $value));
                    } else {
                        $firstArray[$key] = $value;
                    }
                }
            }
        } else {
            $firstArray = $secondArray;
        }

        return $firstArray;
    }
    
    
}
