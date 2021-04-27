<?php

return [
    '*'     =>  [
        '/account/signin'             =>      [['POST'],       [\App\Open\Account::class, 'signin']],
        '/account/signup'             =>      [['POST'],       [\App\Open\Account::class, 'signup']]
    ]
];