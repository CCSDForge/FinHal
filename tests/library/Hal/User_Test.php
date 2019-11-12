<?php

/**
 * Created by PhpStorm.
 * User: marmol
 * Date: 07/07/17
 * Time: 09:40
 */
class User_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider provide_getSqlDeleteRoles
     */
    public function test_getSqlDeleteRoles($arg, $res)
    {
        $user = new Hal_User(['Uid' => 1002]);
        if ($arg === null) {
            $this->assertEquals($res, $user->getSqlDeleteRoles());
        } else {
            $this->assertEquals($res, $user->getSqlDeleteRoles($arg));
        }
    }

    /**
     * Provider for @see test_getSqlDeleteRoles
     * @return array
     */
    public function provide_getSqlDeleteRoles()
    {
        return [
            'Just on role'                   => [[Hal_Acl::ROLE_ADMINSTRUCT,],
                'UID = 1002 AND ((RIGHTID = "adminstruct"))'],
            'Just on role admin'             => [[Hal_Acl::ROLE_ADMIN],
                'UID = 1002 AND ((RIGHTID = "administrator" AND SID = 0))'],
            'More than one role'             => [[Hal_Acl::ROLE_ADMINSTRUCT, Hal_Acl::ROLE_MODERATEUR],
                'UID = 1002 AND ((RIGHTID = "adminstruct") OR (RIGHTID = "moderateur"))'],
            'More than one role with admin ' => [[Hal_Acl::ROLE_ADMINSTRUCT, Hal_Acl::ROLE_ADMIN, Hal_Acl::ROLE_MODERATEUR],
                'UID = 1002 AND ((RIGHTID = "adminstruct") OR (RIGHTID = "administrator" AND SID = 0) OR (RIGHTID = "moderateur"))'],
            'Nothing => All'                 => [null,
                'UID = 1002 AND ((RIGHTID = "adminstruct") OR (RIGHTID = "moderateur") OR (RIGHTID = "validateur") OR (RIGHTID = "tamponneur") OR (RIGHTID = "administrator" AND SID = 0))'],
        ];
    }

    public function test_load()
    {
        $user = new Hal_User(['Uid' => 119773]);
        $user -> loadRoles();
        $roleas  = $user -> getRoles(Hal_Acl::ROLE_ADMINSTRUCT);
        $rolemod = $user -> getRoles(Hal_Acl::ROLE_MODERATEUR);
        $this -> assertArrayHasKey(5687, $roleas);
        $this -> assertArrayHasKey('site', $rolemod[0] );

    }

    public function test_toArray() {
        $user = new Hal_User(['Uid' => 119773]);
        $user -> loadRoles();
        $this -> assertEquals(['uid' => 119773,
                               'username' => null,
                               'civ' => null,
                               'lastname' => null,
                               'firstname' => null,
                               'middlename' => null,
                               'email' => null,
                               'time_registered' => null,
                               'time_modified' => null,
                               'ftp_home' => null,
                               'screen_name' => null,
                               'mode' => 1,
                               'domain' => Array (),
                               'autodepot' => Array (),
                               'licence' => '',
                               'laboratory' => Array (),
                               'institution' => Array (),
                               'langueid' => null,
                               'default_author' => 0,
                               'default_role' => 'aut',
                               'idhal' => null,
                               'nbdocvis' => 0,
                               'nbdocsci' => 0,
                               'nbdocref' => 0,
                               'cv' => null,
                               'aut' => 1,
                               'coaut' => 1,
                               'refstru' => Array (),
                               'admin' => 1],
            $user->toArray());
    }

    public function test_getStructAuth() {
        $user = new Hal_User(['Uid' => 119773]);
        $user -> loadRoles();
        $this -> assertEquals([5687 => 'Institut National de Recherche en Informatique et en Automatique (5687)'], $user -> getStructAuth());
    }

    public function test_addUser() {
        $user = new Hal_User(['Uid' => 7]);
        $user -> setScreen_name('Unit Test user');
        $user -> setDomain("Foo domain");
        $user -> setUsername("user.phpunit@example.com");
        $user -> setEmail("user.phpunit@example.com");
        $user -> setLastname("Phpunit-Test");
        $user -> setFirstname('FooBar');
        $user -> setValid(1);
        $user -> setLangueid('fr');
        $UarrayExpected = [
            'domain' => ["Foo domain"],
            'username' => 'user.phpunit@example.com',
            'lastname' =>'Phpunit-Test',
            'firstname' => 'FooBar',
            'email' => 'user.phpunit@example.com',
            'screen_name' => 'Unit Test user',
            'uid' => 7,
            // Ajouter des tests pour cela....
            'civ' => null,
            'middlename' => null,
            'ftp_home' => null,
            'mode' => 1,
            'time_modified' => null,
            'time_registered' => null,
            'autodepot' => Array (),
            'licence' => '',
            'laboratory' => Array (),
            'institution' => Array (),
            'langueid' => 'fr',
            'default_author' => 0,
            'default_role' => 'aut',
            'idhal' => null,
            'nbdocvis' => 0,
            'nbdocsci' => 0,
            'nbdocref' => 0,
            'cv' => null,
            'aut' => 1,
            'coaut' => 1,
            'refstru' => Array (),
            'admin' => 1
            ];
        $this -> assertEquals($UarrayExpected, $user -> toArray());
        $pwd = uniqid() . time();
        $user -> setPassword($pwd);
        $user -> save();
        $user -> savePrefDepot($user->getUid());
        $user = null;
        $this -> assertNotNull($user = Hal_User::createUser(7));
        // Cool, les valeur par defaut ne sont pas les memes suivant qu'on charge la base de donnee ou qu'on fasse le new
        // Cela permet de toujours hesiter entre null et ''
        $UarrayExpected = [
            'domain' => ["Foo domain"],
            'username' => 'user.phpunit@example.com',
            'lastname' =>'Phpunit-Test',
            'firstname' => 'FooBar',
            'email' => 'user.phpunit@example.com',
            'screen_name' => 'Unit Test user',
            'uid' => 7,
            // Ajouter des tests pour cela....
            'civ' => '',
            'middlename' => '',
            'ftp_home' => null,
            'mode' => 1,
            'autodepot' => Array (),
            'licence' => '',
            'laboratory' => Array (),
            'institution' => Array (),
            'langueid' => 'fr',
            'default_author' => 0,
            'default_role' => 'aut',
            'idhal' => false,
            'nbdocvis' => 0,
            'nbdocsci' => 0,
            'nbdocref' => 0,
            'cv' => false,
            'aut' => 1,
            'coaut' => 1,
            'refstru' => Array (),
            'admin' => 1
            ];
        $array_user = $user -> toArray();
        // Suppression de valeurs non comparables...
        unset($array_user['time_registered']);
        unset($array_user['time_modified']);
        $this -> assertEquals($UarrayExpected, $array_user);
        $user -> delete();
    }

    public function test_HasCV() {
        // Compte avec CV (donc idhal)
        $user = Hal_User::createUser(119084);
        $this -> assertTrue($user->HasIdhal());
        $this -> assertTrue($user->HasCV());

        // Compte avec idHal sans CV
        $user = Hal_User::createUser(301618);
        $this -> assertTrue($user->HasIdhal());
        $this -> assertFalse($user->HasCV());

        // Compte sans Idhal et donc sans CV
        $user = Hal_User::createUser(181747);
        $this -> assertFalse($user->HasIdhal());
        $this -> assertFalse($user->HasCV());
    }

    public function test_getRoles() {
        $user = Hal_User::createUser(301618);
        $this -> assertEquals([], $user -> getRoles());
    }
}