<?php

return [
    '*'     =>  [
        '/account/signin'             =>      [['POST'],       [\App\Open\Account::class, 'signin']],
        '/account/signup'             =>      [['POST'],       [\App\Open\Account::class, 'signup']],
        '/account/forgot'             =>      [['POST'],       [\App\Open\Account::class, 'forgot']],
        '/account/resetPwd'           =>      [['POST'],       [\App\Open\Account::class, 'resetPwd']],
        '/account/profile'            =>      [['POST'],       [\App\Open\Account::class, 'profile']],
    ]
];