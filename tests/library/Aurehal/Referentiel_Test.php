<?php

class Aurehal_Controller_Referentiel_Test extends PHPUnit_Framework_TestCase
{
    private $_data;
    private $_obj;
    
    public function setUp()
    {
        $this->_data = array("IDHAL"=>6423, 
                      "FIRSTNAME"=>"Jane", 
                      "LASTNAME"=>"Birkin", 
                      "MIDDLENAME"=>"Jeannette", 
                      "EMAIL"=>"tech@ccsd.cnrs.fr", 
                      "URL"=>"www.toto.fr", 
                      "STRUCTID"=>0
                    );

        $this->_obj = new Ccsd_Referentiels_Author(0, $this->_data);
    }
    
    // Le comportement de cette fonction dépend de Ccsd_Referentiels_Author::getAcceptedValues
    // Les valeurs acceptées sont mise à jour si on passe une valeur
    // Les valeurs acceptées non mise à jour sont mise à null
    // Les valeurs préexistantes conservent leur valeur précédente
    // Les autres valeurs sont filtrées
    public function testGetFilteredDataWantedBehavior()
    {
        $acceptedValues = ["FIRSTNAME", "LASTNAME", "MIDDLENAME", "EMAIL", "STRUCTID", "ORGANISM"];

        $newData = $this->_obj->getFilteredData(array("IDHAL"=>20, "FIRSTNAME"=>"Nonette", "LASTNAME"=>"Bk", "TEST" => "TEST"), $acceptedValues);

        $this->assertEquals($newData, array("IDHAL"=>6423, 
                      "FIRSTNAME"=>"Nonette", 
                      "LASTNAME"=>"Bk", 
                      "MIDDLENAME"=>null, 
                      "EMAIL"=>null, 
                      "URL"=>"www.toto.fr",
                      "STRUCTID"=>null,
                      "ORGANISM"=>null
            ));
        $this->assertEquals(in_array("TEST", array_keys($newData)), false);
    }

    public function testGetFilteredDataWithEmptyArray()
    {
        $newData = $this->_obj->getFilteredData(array(), null);

        $this->assertEquals($newData, array("IDHAL"=>null,
            "FIRSTNAME"=>null,
            "LASTNAME"=>null,
            "MIDDLENAME"=>null,
            "EMAIL"=>null,
            "URL"=>null,
            "STRUCTID"=>null,
            "ORGANISM"=>null
        ));
    }
}
