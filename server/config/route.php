<?php

return [
    '*'     =>  [
        '/account/signin'               =>      [['POST'],      [\App\Open\Account::class, 'signin']],
        '/account/signup'               =>      [['POST'],      [\App\Open\Account::class, 'signup']],
        '/account/forgot'               =>      [['POST'],      [\App\Open\Account::class, 'forgot']],

        '/account/resetPwd'             =>      [['POST'],      [\App\Open\Account::class, 'resetPwd'],         \App\Middleware\Token::class],
        '/account/profile'              =>      [['POST'],      [\App\Open\Account::class, 'profile'],          \App\Middleware\Token::class],
        '/account/edit'                 =>      [['POST'],      [\App\Open\Account::class, 'edit'],             \App\Middleware\Token::class],

        '/account/authentic'            =>      [['POST'],      [\App\Open\Account::class, 'authentic'],        \App\Middleware\Token::class],

        '/account/address'              =>      [['POST'],      [\App\Open\Address::class, 'my'],               \App\Middleware\Token::class],
        '/account/address/save'         =>      [['POST'],      [\App\Open\Address::class, 'save'],             \App\Middleware\Token::class],
        '/account/address/edit'         =>      [['POST'],      [\App\Open\Address::class, 'edit'],             \App\Middleware\Token::class],
        '/account/address/remove'       =>      [['POST'],      [\App\Open\Address::class, 'remove'],           \App\Middleware\Token::class],
        '/account/address/default'      =>      [['POST'],      [\App\Open\Address::class, 'default'],          \App\Middleware\Token::class],
    ]
];