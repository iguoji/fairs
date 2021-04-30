<?php

return [
    // 账户
    'account'       =>  [
        // 认证
        'authentic' =>  \App\Common\Authentic::IDCARD,
        // 登录
        'signin'    =>  [
            // 有效期
            'expire'    =>  60 * 60 * 24 * 7,
        ],
        // 邀请码
        'inviter'   =>  [
            // 启用
            'enable'    =>  false,
            // 长度
            'length'    =>  4,
        ],
    ],

    // 图形验证码
    'captcha'       =>  [
        // 长度
        'length'    =>  4,
    ]
];