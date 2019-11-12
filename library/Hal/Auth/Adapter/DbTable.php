<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 23/08/18
 * Time: 10:13
 */

/** @deprecated  */
class Hal_Auth_Adapter_DbTable extends \Ccsd\Auth\Adapter\DbTable
{

    /**
     * @var \Ccsd_User_Models_User
     */
    protected $_identityStructure = null;
    /**
     * Permet de passer l'objet Hal_User a l'authentification pour recuperer l'ensemble des information de l;utilisateur
     * @return Zend_Auth_Result
     * @throws Zend_Auth_Adapter_Exception
     */
    public function authenticate()
    {
        $authResult = parent::authenticate();
        if ($authResult->getCode() == Zend_Auth_Result::SUCCESS) {
            // Si le resultat est Ok, on change le type de identity dans le resultat pour faire que ce soit un User applicatif

            $userMapper = new Ccsd_User_Models_UserMapper();
            $username = $this->_identity;
            $rows = $userMapper->findByUsername($username);
            if (count($rows) > 1) {
                throw new Zend_Auth_Adapter_Exception('Deux username ($username) present dans la table!');
            }
            $row = $rows[0];
            $user = new Hal_User();
            $user->setUid($row->UID)
                ->setUsername($row->USERNAME)
                ->setEmail($row->EMAIL)
                ->setCiv($row->CIV)
                ->setLastname($row->LASTNAME)
                ->setFirstname($row->FIRSTNAME)
                ->setMiddlename($row->MIDDLENAME);
            $user->setTime_registered($row->TIME_REGISTERED)
                ->setTime_modified($row->TIME_MODIFIED)
                ->setValid($row->VALID);
            $authResult = new Zend_Auth_Result($authResult->getCode(), $user, $authResult->getMessages());
        }
        return $authResult;
    }

    /**
     * Fonction d'authentification version 2
     * avec non plus retour d'un objet Hal User mais retour d'un tableau de valeurs
     * permettant par la suite de construire l'objet Hal User
     * (redécoupage des processus 13/02/2019 - JB)
     */

    public function authenticate2()
    {
      $authResult = parent::authenticate();
        if ($authResult->getCode() == Zend_Auth_Result::SUCCESS) {

            $userMapper = new Ccsd_User_Models_UserMapper();
            $username = $this->_identity;
            $rows = $userMapper->findByUsername($username);
            if (count($rows) > 1) {
                throw new Zend_Auth_Adapter_Exception('Deux username ($username) present dans la table!');
            }
            $row = $rows[0];
            $authResult = new Zend_Auth_Result($authResult->getCode(), $row, $authResult->getMessages());
        }
        return $authResult;
    }
    /**
     * Initialisation de la structure de l'identité utilisateur
     *
     * @param $identity
     */
    public function  setIdentityStructure($identity) {
        // Par compat, on met la structure dans identity aussi
        $this->_identity = $identity;
        $this->_identityStructure = $identity;
    }

    /**
     * @return Ccsd_User_Models_User
     */
    public function  getIdentityStructure() {
        return $this->_identityStructure;
    }
}