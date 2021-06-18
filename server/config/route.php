<?php

return [
    // 后台管理
    'admin.fairs.com'   =>  [
        // 首页
        '/'                                 =>  \App\Http\Admin\Index::class,
        '/index'                            =>  \App\Http\Admin\Index::class,

        // 地区
        '/regions'                          =>  \App\Http\Region\Index::class,
        // 上传
        '/file/upload'                      =>  \App\Http\File\Upload::class,

        // 账户
        '/accounts'                         =>  \App\Http\Account\Index::class,
        '/account/read'                     =>  \App\Http\Account\Read::class,
        '/account/save'                     =>  \App\Http\Account\Save::class,
        '/account/edit'                     =>  \App\Http\Account\Edit::class,
        '/account/remove'                   =>  \App\Http\Account\Remove::class,

        // 账户 - 实名认证
        '/account/authentications'          =>  \App\Http\Account\Authentication\Index::class,
        '/account/authentication/read'      =>  \App\Http\Account\Authentication\Read::class,
        '/account/authentication/edit'      =>  \App\Http\Account\Authentication\Edit::class,

        // 账户 - 推广
        '/account/relations'                =>  \App\Http\Account\Relation\Index::class,

        // 权限
        '/signin'                           =>  \App\Http\Rbac\Signin::class,
        '/signout'                          =>  \App\Http\Rbac\Signout::class,

        '/rbac/node'                        =>  \App\Http\Rbac\Node\Index::class,

        '/rbac/role'                        =>  \App\Http\Rbac\Role\Index::class,
        '/rbac/role/read'                   =>  \App\Http\Rbac\Role\Read::class,
        '/rbac/role/save'                   =>  \App\Http\Rbac\Role\Save::class,
        '/rbac/role/edit'                   =>  \App\Http\Rbac\Role\Edit::class,
        '/rbac/role/remove'                 =>  \App\Http\Rbac\Role\Remove::class,
        '/rbac/role/powers'                 =>  \App\Http\Rbac\Role\Powers::class,
        '/rbac/relation/edit'               =>  \App\Http\Rbac\Relation\Edit::class,

        '/rbac/admin'                       =>  \App\Http\Rbac\Admin\Index::class,
        '/rbac/admin/read'                  =>  \App\Http\Rbac\Admin\Read::class,
        '/rbac/admin/save'                  =>  \App\Http\Rbac\Admin\Save::class,
        '/rbac/admin/edit'                  =>  \App\Http\Rbac\Admin\Edit::class,
        '/rbac/admin/remove'                =>  \App\Http\Rbac\Admin\Remove::class,
        '/rbac/admin/logs'                  =>  \App\Http\Rbac\Admin\Logs::class,
    ],
    // 用户接口
    '*'     =>  [
        // 账户
        '/account/signup'                   =>  \App\Http\Account\Save::class,
        '/account/signin'                   =>  \App\Http\Account\Signin::class,
        '/account/profile'                  =>  \App\Http\Account\Read::class,
        '/account/edit'                     =>  \App\Http\Account\Edit::class,
        '/account/safeword'                 =>  \App\Http\Account\Safeword::class,
        '/account/bindPhone'                =>  \App\Http\Account\BindPhone::class,
        '/account/bindEmail'                =>  \App\Http\Account\BindEmail::class,
        '/account/forgot'                   =>  \App\Http\Account\Forgot::class,
        '/account/resetPwd'                 =>  \App\Http\Account\ResetPwd::class,

        // 账户 - 实名认证
        '/account/authentication'           =>  \App\Http\Account\Authentication\Save::class,

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