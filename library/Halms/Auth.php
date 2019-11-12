<?php
/**
 * Authentification sur HAL
 * @author Yannick Barborini
 *
 */
class Halms_Auth extends Hal_Auth
{

    /**
     * Retourne les privilèges d'un utilisateur pour le site courant
     * @return array|void
     */
    public static function getRoles()
    {
        $roles = array();
        if (self::isLogged()) {
            $userRoles = self::getInstance()->getIdentity()->getRoles();
            if (is_array($userRoles)) {
                foreach ($userRoles as $roleId => $data) {
                    if ($roleId == Halms_Acl::ROLE_ADMINHALMS) {
                        $roles[] = $roleId;
                    }
                }
            }
            if (count($roles) == 0) {
                $roles[] = Halms_Acl::ROLE_MEMBER;
            }
        } else {
            $roles[] = Halms_Acl::ROLE_GUEST;
        }
        //Zend_Debug::dump($roles);exit;
        return array_unique($roles);
    }

    /**
     * Indique si l'utilisateur connecté est administrateur du portail courant
     * @return bool
     */
    static public function isAdministrator()
    {
        return self::is(Halms_Acl::ROLE_ADMINHALMS);
    }


}