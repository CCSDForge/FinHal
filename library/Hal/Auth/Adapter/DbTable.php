<?php
/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 23/08/18
 * Time: 10:13
 */

class Hal_Auth_Adapter_DbTable extends Ccsd_Auth_Adapter_DbTable
{
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
}