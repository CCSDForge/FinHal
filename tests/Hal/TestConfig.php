<?php

namespace Hal;
/**
 * Class TestHalConfig
 * @package Hal
 */
class HalConfig_Test extends \PHPUnit_Framework_TestCase
{
    /**

     */
    public function testConfig()
    {
        $config = Config::getInstance();
        $opts = $config->getOption('tarteaucitron.domain');
        $this->assertEquals('.archives-ouvertes.fr', $opts);
    }


}