<?php

return [
    // 账户
    'account'       =>  [
        // 认证
        'authentication' =>  \App\Common\Authentication::IDCARD,
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
    ],

    // 上传设置
    'upload'        =>  [
        // 图片类型
        'image'     =>  [
            // 路径
            'path'  =>  '../public/upload/',
            // 10MB
            'size'  =>  1024 * 1024 * 10,
            // 后缀
            'ext'   =>  ['png', 'jpg', 'gif', 'bmp'],
        ],
    ],
];