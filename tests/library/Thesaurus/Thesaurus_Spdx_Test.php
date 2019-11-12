<?php

/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 02/11/2017
 * Time: 10:47
 */
class Thesaurus_Spdx_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var Thesaurus_Spdx
     */
    public $_thesaurus;

    public function setUp()
    {
        $this->_thesaurus = new Thesaurus_Spdx();
    }

    /**
     * Vérifie le chargement du thésaurus
     */
    public function testLoadThesaurus()
    {
        $this->assertNotEmpty($this->_thesaurus->getData());
    }

    public function testGetUrl()
    {
        $this->assertEquals(null,  $this->_thesaurus->getUrl('Licence not exists'));
        $this->assertEquals('https://fedoraproject.org/wiki/Licensing/Abstyles',  $this->_thesaurus->getUrl('Abstyles'));
        $this->assertEquals('https://fedoraproject.org/wiki/Licensing/Apple_MIT_License',  $this->_thesaurus->getUrl('Apple mit License'));
        $this->assertEquals('http://download.oracle.com/otn-pub/java/licenses/bsd.txt?AuthParam=1467140197_43d516ce1776bd08a58235a7785be1cc',  $this->_thesaurus->getUrl('BSD 3-Clause No Nuclear License'));
        $this->assertEquals('http://www.egenix.com/products/eGenix.com-Public-License-1.1.0.pdf',  $this->_thesaurus->getUrl('eGenix'));
    }

    public function testGetName()
    {
        $this->assertEquals(null,  $this->_thesaurus->getLicence('URL not exists'));
        $this->assertEquals('GNU Library General Public License v2 only',  $this->_thesaurus->getLicence('http://www.gnu.org/licenses/old-licenses/lgpl-2.0-standalone.html'));
        $this->assertEquals('JSON License',  $this->_thesaurus->getLicence('http://www.json.org/license.html'));
        $this->assertEquals('MirOS Licence',  $this->_thesaurus->getLicence('http://www.opensource.org/licenses/MirOS'));
    }

    public function testAutocomplete()
    {
        $expected = ["Licence Libre du Québec – Permissive version 1.1",
            "Licence Libre du Québec – Réciprocité forte version 1.1",
            "Licence Libre du Québec – Réciprocité version 1.1",
            "TORQUE v2.5+ Software License v1.1"];
        $this->assertEquals($expected, $this->_thesaurus->autocomplete('qu'));
    }
}