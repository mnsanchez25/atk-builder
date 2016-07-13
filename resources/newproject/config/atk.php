<?php

$_baseDir = __DIR__ . '/../';

return [
    /**
     * change identifier to unique string
     */
    'identifier' => 'atk-skeleton',

    'language_basedir' => $_baseDir . 'src/languages/',

    'modules' => [
    ],

    'language' => 'it',

    'authentication' => 'db',

    'auth_usecryptedpassword' => true,
    'restrictive' => true,

    /** Security database configuration **/
    'securityscheme' => 'group',
    'auth_userpk' => 'id',
    'auth_userfk' => 'user_id',
    'auth_usernode' => 'Security.Users',
    'auth_usertable' => 'Security_Users',
    'auth_userfield' => 'username',
    'auth_passwordfield' => 'passwd',
    'auth_emailfield' => 'email',
    'auth_accountdisablefield' => 'disabled',
    'auth_leveltable' => 'Security_Users_Groups',
    'auth_levelfield' => 'group_id',
		'auth_accesstable' => 'security_accessrights',
		
		
		'setup_allowed_ips' => '127.0.0.1:127.0.0.0',

];
