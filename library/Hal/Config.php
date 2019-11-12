<?php


namespace Hal;

/**
 * Singleton to Store HAL Configuration
 */
class Config
{
    /** @var \Hal\Config */
    private static $_instance;

    /** @var array */
    private $config = null;

    /** @param array  $config */
    private function __construct($config) {
        $this->config = $config;
    }
    /** @param array  $config */
    static public function init($config) {
       self::$_instance  = new self($config);
    }
    /** @return \Hal\Config */
    static function getInstance() {
        return self::$_instance;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getOption($name, $default=null) {
        $value = $this->getSubOption($name, $this->config);
        if ($value !== null) {
            return $value;
        } else {
            return $default;
        }
    }

    /**
     * @param $name
     * @param $array
     * @return mixed|null
     */
    private function getSubOption($name, $array) {
        $sub = preg_match("/([^\.]+)\.(.*)/", $name, $matches);
        if ($sub) {
            $suboption = $matches[1];
            $rest = $matches[2];
            if (array_key_exists($suboption, $array)) {
                return $this->getSubOption($rest, $array[$suboption]);
            } else {
                return null;
            }
        } else {
            if (array_key_exists($name, $array)) {
                return  $array[$name];
            } else {
                return null;
            }
        }
    }
}