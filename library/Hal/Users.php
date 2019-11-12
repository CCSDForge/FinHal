<?php

/**
 * Created by PhpStorm.
 * User: yannick
 * Date: 30/01/2014
 * Time: 11:47
 */
class Hal_Users extends Ccsd_User_Models_DbTable_User {

    public function search($q, $limit = 100, $valid=false)
    {
        $res = parent::search($q, $limit, $valid);

        $toreturn = [];

        $halUser = new Hal_User();
        foreach ($res as $user) {
            $halUser->setUid($user['UID'])->loadRoles();
            $user['ROLES'] = $halUser->getRoles();
            $user['HAS_HAL_ACCOUNT'] = $halUser->hasHalAccountData($halUser->getUid());
            $halUser->HasCV();
            $halUser->hasIdhal();
            $user['CV'] = $halUser->getIdhal();

            // On privilégie les comptes existants dans HAL
            if ($user['HAS_HAL_ACCOUNT']) {
                array_unshift($toreturn, $user);
            } else {
                array_push($toreturn, $user);
            }
        }
        return $toreturn;
    }

}
