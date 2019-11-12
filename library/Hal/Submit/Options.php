<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 26/10/17
 * Time: 11:00
 */
class Hal_Submit_Options
{
    /**
     * @var bool
     */
    protected $_completeMetas = true;

    /**
     * @var bool
     */
    protected $_completeAuthors = true;

    /**
     * @var bool
     */
    protected $_affiliateAuthors = true;


    /**
     * Hal_Submit_Options constructor.
     * @param bool $completeMetas
     * @param bool $completeAuthors
     * @param bool $affiliateAuthors
     */
    public function __construct ($completeMetas = true, $completeAuthors = true, $affiliateAuthors = true)
    {
        $this->_completeMetas = $completeMetas;
        $this->_completeAuthors = $completeAuthors;
        $this->_affiliateAuthors = $affiliateAuthors;
    }

    /**
     * @param $complete
     */
    public function setCompletemeta($complete)
    {
        $this->_completeMetas = $complete;
    }

    /**
     * @return bool
     */
    public function completeMeta()
    {
        return $this->_completeMetas;
    }

    /**
     * @param $complete
     */
    public function setCompleteauthors($complete)
    {
        $this->_completeAuthors = $complete;
    }

    /**
     * @return bool
     */
    public function completeAuthors()
    {
        return $this->_completeAuthors;
    }

    /**
     * @param $affiliate
     */
    public function setAffiliateauthors($affiliate)
    {
        $this->_affiliateAuthors = $affiliate;
    }

    /**
     * @return bool
     */
    public function affiliateAuthors()
    {
        return $this->_affiliateAuthors;
    }

    public function setOption($option, $value)
    {
        $classMethods = get_class_methods($this);
        $attrib = strtolower($option);

        $method = 'set' . ucfirst($attrib);
        if (in_array($method, $classMethods)) {
            $this->$method($value);
        }
    }

}