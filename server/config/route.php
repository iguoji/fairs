<?php

return [
    '*'     =>  [
        '/'                             =>      [['GET'],               [\App\Open\Index::class,    'index'],                                       ],

        '/admin/signin'                 =>      [['GET', 'POST'],       [\App\Admin\Index::class,   'singin'],          \App\Middleware\Admin::class],
        '/admin/index'                  =>      [['GET'],               [\App\Admin\Index::class,   'index'],                                       ],



        '/account/signin'               =>      [['POST'],              [\App\Open\Account::class, 'signin']],
        '/account/signup'               =>      [['POST'],              [\App\Open\Account::class, 'signup']],
        '/account/forgot'               =>      [['POST'],              [\App\Open\Account::class, 'forgot']],

        '/account/resetPwd'             =>      [['POST'],              [\App\Open\Account::class, 'resetPwd'],         \App\Middleware\Token::class],
        '/account/safeword'             =>      [['POST'],              [\App\Open\Account::class, 'safeword'],         \App\Middleware\Token::class],
        '/account/profile'              =>      [['POST'],              [\App\Open\Account::class, 'profile'],          \App\Middleware\Token::class],
        '/account/edit'                 =>      [['POST'],              [\App\Open\Account::class, 'edit'],             \App\Middleware\Token::class],
        '/account/bindPhone'            =>      [['POST'],              [\App\Open\Account::class, 'bindPhone'],        \App\Middleware\Token::class],
        '/account/bindEmail'            =>      [['POST'],              [\App\Open\Account::class, 'bindEmail'],        \App\Middleware\Token::class],

        '/account/authentic'            =>      [['POST'],              [\App\Open\Account::class, 'authentic'],        \App\Middleware\Token::class],

        '/account/address'              =>      [['POST'],              [\App\Open\Address::class, 'my'],               \App\Middleware\Token::class],
        '/account/address/save'         =>      [['POST'],              [\App\Open\Address::class, 'save'],             \App\Middleware\Token::class],
        '/account/address/edit'         =>      [['POST'],              [\App\Open\Address::class, 'edit'],             \App\Middleware\Token::class],
        '/account/address/remove'       =>      [['POST'],              [\App\Open\Address::class, 'remove'],           \App\Middleware\Token::class],
        '/account/address/default'      =>      [['POST'],              [\App\Open\Address::class, 'default'],          \App\Middleware\Token::class],

        '/account/bank'                 =>      [['POST'],              [\App\Open\AccountBank::class, 'my'],           \App\Middleware\Token::class],
        '/account/bank/save'            =>      [['POST'],              [\App\Open\AccountBank::class, 'save'],         \App\Middleware\Token::class],
        '/account/bank/edit'            =>      [['POST'],              [\App\Open\AccountBank::class, 'edit'],         \App\Middleware\Token::class],
        '/account/bank/remove'          =>      [['POST'],              [\App\Open\AccountBank::class, 'remove'],       \App\Middleware\Token::class],
        '/account/bank/default'         =>      [['POST'],              [\App\Open\AccountBank::class, 'default'],      \App\Middleware\Token::class],
    ],
];