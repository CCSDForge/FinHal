<?php

/**
 * Class Hal_Export
 * @deprecated     : Ne semble pas du tout utilisee,
 * @see Ccsd_Export
 */
class Hal_Export
{

    protected $_fileName;

    protected $_fileCache;

    public function __construct ()
    {}
    /**
     * @return string
     */
    public function getFileName ()
    {
        return $this->_fileName;
    }
    /**
     * @param string $_fileName
     * @return Hal_Export
     */
    public function setFileName ($_fileName)
    {
        $this->_fileName = $_fileName;
        return $this;
    }
    /**
     * @return string $_fileCache
     */
    public function getFileCache ()
    {
        return $this->_fileCache;
    }
    /**
     * @param string $_fileCache
     * @return Hal_Export
     */
    public function setFileCache ($_fileCache)
    {
        $this->_fileCache = $_fileCache;
        return $this;
    }
}

