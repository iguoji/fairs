<?php

return [
    // 后台管理
    'admin.fairs.com'   =>  [
        // 首页
        '/'                                 =>  \App\Http\Admin\Index::class,
        '/index'                      =>  \App\Http\Admin\Index::class,

        // 权限
        '/signin'                     =>  \App\Http\Rbac\Signin::class,
    ],
    // 用户接口
    '*'     =>  [
        // 账户
        '/account/signup'                   =>  \App\Http\Account\Signup::class,
        '/account/signin'                   =>  \App\Http\Account\Signin::class,
        '/account/profile'                  =>  \App\Http\Account\Profile::class,
        '/account/edit'                     =>  \App\Http\Account\Edit::class,
        '/account/authentication'           =>  \App\Http\Account\Authentication::class,
        '/account/safeword'                 =>  \App\Http\Account\Safeword::class,
        '/account/bindPhone'                =>  \App\Http\Account\BindPhone::class,
        '/account/bindEmail'                =>  \App\Http\Account\BindEmail::class,
        '/account/forgot'                   =>  \App\Http\Account\Forgot::class,
        '/account/resetPwd'                 =>  \App\Http\Account\ResetPwd::class,

        // 收货地址
        '/account/address'                  =>  \App\Http\Address\My::class,
        '/account/address/save'             =>  \App\Http\Address\Save::class,
        '/account/address/edit'             =>  \App\Http\Address\Edit::class,
        '/account/address/remove'           =>  \App\Http\Address\Remove::class,
        '/account/address/default'          =>  \App\Http\Address\UseDefault::class,

        // 我的银行卡
        '/account/bank'                     =>  \App\Http\AccountBank\My::class,
        '/account/bank/save'                =>  \App\Http\AccountBank\Save::class,
        '/account/bank/edit'                =>  \App\Http\AccountBank\Edit::class,
        '/account/bank/remove'              =>  \App\Http\AccountBank\Remove::class,
        '/account/bank/default'             =>  \App\Http\AccountBank\UseDefault::class,
    ],
];